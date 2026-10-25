<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json; charset=utf-8");
clearstatcache();

include("../modelos/modelo_crud.php");

$objeto = new crud();
$bd_conexion = "cliente";

// Leer datos de configuración
require_once dirname(__DIR__) . '/funciones/config_helper.php';
$datos_conexion = obtener_config_sistema();
$file = $datos_conexion;

// Determinar la URL base segura (HTTPS) para el QR del comensal
$url_base = "https://mijardinrestaurant.test/mesa";
if (!empty($datos_conexion['url_web_mesa'])) {
    $url_base = rtrim($datos_conexion['url_web_mesa'], '/');
} elseif (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '192.168') !== false) {
    $ip_server = explode(':', $_SERVER['HTTP_HOST'])[0];
    $url_base = "https://" . $ip_server . "/mesa";
}

if (isset($_GET['accion']) && $_GET['accion'] == "obtener_pin") {
    $mesa_input = trim($_POST['mesa'] ?? '');

    if (empty($mesa_input)) {
        echo json_encode(['success' => false, 'error' => 'Debe ingresar o seleccionar una mesa.']);
        exit;
    }

    $mesa_clean = preg_replace('/^E/i', '', $mesa_input);
    $documento = "E" . $mesa_clean;

    // 1. Conectar a base de datos satélite local mijar26273_sistema en puerto 3307
    $host_db = $datos_conexion['host'] ?? '127.0.0.1';
    $user_db = $datos_conexion['user'] ?? 'root';
    $pass_db = $datos_conexion['pw'] ?? 'root';
    $port_db = (int) ($datos_conexion['port'] ?? 3307);

    $bd_satelite = $datos_conexion['bd_sistema'] ?? 'mijar26273_sistema';
    $mysqli_sistema = @new mysqli($host_db, $user_db, $pass_db, $bd_satelite, $port_db);
    if ($mysqli_sistema->connect_error) {
        $bd_alt = ($bd_satelite === 'mijar26273_sistema') ? 'mijar_sistema' : 'mijar26273_sistema';
        $mysqli_sistema = @new mysqli($host_db, $user_db, $pass_db, $bd_alt, $port_db);
    }
    if ($mysqli_sistema->connect_error) {
        echo json_encode(['success' => false, 'error' => 'No se pudo conectar a la base local satélite (' . ($bd_satelite ?? 'mijar26273_sistema') . '): ' . $mysqli_sistema->connect_error]);
        exit;
    }

    // 2. Obtener o generar PIN aleatorio de 6 dígitos en mijar26273_sistema (3307)
    $query = "SELECT token_pin, estado FROM z_status_pago WHERE documento = '" . $mysqli_sistema->real_escape_string($documento) . "' LIMIT 1";
    $res = $mysqli_sistema->query($query);
    $registro = $res ? $res->fetch_assoc() : null;

    $pin = '';
    if (!empty($registro['token_pin'])) {
        $pin = $registro['token_pin'];
    } else {
        $pin = (string) rand(100000, 999999);
        $fecha_ahora = date('Y-m-d H:i:s');
        $insert = "INSERT INTO z_status_pago (documento, token_pin, estado, created_at, updated_at) 
                   VALUES ('" . $mysqli_sistema->real_escape_string($documento) . "', '" . $pin . "', '0', '" . $fecha_ahora . "', '" . $fecha_ahora . "')
                   ON DUPLICATE KEY UPDATE token_pin = '" . $pin . "', updated_at = '" . $fecha_ahora . "';";
        $mysqli_sistema->query($insert);
    }

    // 3. Recopilar artículos de opermv en adminbahiap (puerto 3307)
    $items = [];
    $total_bs = 0;
    
    $q_opermv = "SELECT fechayhora, cantidad, nombre, preciounit, montoneto FROM opermv WHERE documento = '" . $documento . "' ORDER BY fechayhora ASC";
    $res_opermv = @$objeto->general_query($bd_conexion, $q_opermv);
    
    if ($res_opermv && mysqli_num_rows($res_opermv) > 0) {
        while ($it = mysqli_fetch_array($res_opermv)) {
            $cant = (float) $it['cantidad'];
            $monto = (float) $it['montoneto'];
            $total_bs += $monto;
            $items[] = [
                'hora' => date('h:i A', strtotime($it['fechayhora'] ?? 'now')),
                'cant' => $cant,
                'nombre' => mb_convert_encoding($it['nombre'], 'UTF-8', 'ISO-8859-1'),
                'preciounit' => (float) $it['preciounit'],
                'total' => $monto,
            ];
        }
    } elseif (isset($_POST['productos_array']) && is_array($_POST['productos_array'])) {
        $hora_str = date('h:i A');
        foreach ($_POST['productos_array'] as $p) {
            $d = explode('|', $p);
            $cant = isset($d[12]) ? (float)$d[12] : 1;
            $precio = isset($d[2]) ? (float)$d[2] : 0;
            $subtotal = $cant * $precio;
            $total_bs += $subtotal;
            $items[] = [
                'hora' => $hora_str,
                'cant' => $cant,
                'nombre' => mb_convert_encoding($d[1] ?? 'Producto', 'UTF-8', 'ISO-8859-1'),
                'preciounit' => $precio,
                'total' => $subtotal,
            ];
        }
    }

    $tasa = (float) ($datos_conexion['monedafactor'] ?? 1);
    if ($tasa <= 0) $tasa = 1;
    $total_usd = $total_bs / $tasa;

    // 4. Guardar copia local en mijar26273_sistema.z_mesas_activas (puerto 3307)
    $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
    $fecha_act = date('Y-m-d H:i:s');
    $ins_mesa = "INSERT INTO z_mesas_activas (documento, mesa, token_pin, total_bs, total_usd, tasa_dia, items_json, estado, created_at, updated_at)
                 VALUES (
                     '" . $mysqli_sistema->real_escape_string($documento) . "',
                     '" . $mysqli_sistema->real_escape_string("MESA " . $mesa_clean) . "',
                     '" . $pin . "',
                     " . round($total_bs, 2) . ",
                     " . round($total_usd, 2) . ",
                     " . round($tasa, 4) . ",
                     '" . $mysqli_sistema->real_escape_string($items_json) . "',
                     'ACTIVA',
                     '" . $fecha_act . "',
                     '" . $fecha_act . "'
                 )
                 ON DUPLICATE KEY UPDATE 
                     token_pin = '" . $pin . "',
                     total_bs = " . round($total_bs, 2) . ",
                     total_usd = " . round($total_usd, 2) . ",
                     tasa_dia = " . round($tasa, 4) . ",
                     items_json = '" . $mysqli_sistema->real_escape_string($items_json) . "',
                     estado = 'ACTIVA',
                     updated_at = '" . $fecha_act . "';";
    $mysqli_sistema->query($ins_mesa);

    $url_qr = $url_base . "?t=" . $pin;

    echo json_encode([
        'success' => true,
        'mesa' => $mesa_clean,
        'documento' => $documento,
        'pin' => $pin,
        'url' => $url_qr,
        'total_items' => count($items),
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
