<?php
/**
 * ============================================================================
 * SINCRONIZADOR LOCAL A LA NUBE - MI JARDIN RESTAURANT
 * ============================================================================
 * Lee las mesas activas de Premium Soft y la tabla satélite local (puerto 3307)
 * y las envía de forma segura (HTTPS Outbound) hacia la API de la nube (puerto 3306).
 *
 * Ejecución:
 *   php sync_daemon.php          (Modo demonio continuo cada 12 segundos)
 *   php sync_daemon.php --once   (Modo ejecución única para Cron o Tarea Programada)
 * ============================================================================
 */

date_default_timezone_set('America/Caracas');

// Cargar configuración centralizada desde la BD mijar_sistema (puerto 3307)
require_once __DIR__ . '/funciones/config_helper.php';
$datos_conexion = obtener_config_sistema();

// Parámetros de bases locales (Único puerto 3307 para ambas bases)
$host_local = $datos_conexion['host'] ?? '127.0.0.1';
$user_local = $datos_conexion['user'] ?? 'root';
$pass_local = $datos_conexion['pw'] ?? 'root';
$port_local = (int) ($datos_conexion['port'] ?? 3307);
$bd_pos     = $datos_conexion['bd_pos'] ?? ($datos_conexion['bd'] ?? 'adminbahiap');
$bd_sistema = $datos_conexion['bd_sistema'] ?? 'mijar_sistema';

// Endpoint Cloud seguro HTTPS (Outbound hacia el servidor dedicado / VPS)
$url_api_cloud = "https://mijardinrestaurant.test/api/sincronizar-mesas";
if (!empty($datos_conexion['url_hosting'])) {
    $base = rtrim($datos_conexion['url_hosting'], '/');
    $url_api_cloud = $base . '/api/sincronizar-mesas';
} elseif (!empty($datos_conexion['url_web_mesa'])) {
    $base = preg_replace('#/mesa/?$#i', '', $datos_conexion['url_web_mesa']);
    $url_api_cloud = $base . '/api/sincronizar-mesas';
}

$modo_once = in_array('--once', $argv);
$intervalo_segundos = 12;

echo "========================================================" . PHP_EOL;
echo "  SINCRONIZADOR CLOUD - MI JARDIN RESTAURANT" . PHP_EOL;
echo "========================================================" . PHP_EOL;
echo " Servidor Local: MySQL {$host_local}:{$port_local}" . PHP_EOL;
echo " BD POS:         {$bd_pos}" . PHP_EOL;
echo " BD Satélite:    {$bd_sistema}" . PHP_EOL;
echo " Endpoint Nube:  {$url_api_cloud}" . PHP_EOL;
echo " Modo:           " . ($modo_once ? "Ejecución única (--once)" : "Continuo (cada {$intervalo_segundos}s)") . PHP_EOL;
echo "========================================================" . PHP_EOL . PHP_EOL;

function sincronizar_ciclo($host_local, $user_local, $pass_local, $port_local, $bd_pos, $bd_sistema, $url_api_cloud, $datos_conexion)
{
    $timestamp = date('H:i:s');

    // 1. Conexión a bases locales (puerto 3307)
    $mysqli_pos = @new mysqli($host_local, $user_local, $pass_local, $bd_pos, $port_local);
    if ($mysqli_pos->connect_error) {
        echo "[{$timestamp}] [ERROR LOCAL] No se pudo conectar a {$bd_pos} (3307): " . $mysqli_pos->connect_error . PHP_EOL;
        return false;
    }

    $mysqli_sistema = @new mysqli($host_local, $user_local, $pass_local, $bd_sistema, $port_local);
    if ($mysqli_sistema->connect_error) {
        echo "[{$timestamp}] [ERROR LOCAL] No se pudo conectar a {$bd_sistema} (3307): " . $mysqli_sistema->connect_error . PHP_EOL;
        $mysqli_pos->close();
        return false;
    }

    // Tasa del día
    $tasa = (float) ($datos_conexion['monedafactor'] ?? 1);
    if ($tasa <= 0) $tasa = 1;

    // 2. Consultar mesas abiertas en operti (tipodoc = 'ESP')
    $sql_mesas = "SELECT documento, nombrecli, totalfinal, fechayhora 
                  FROM operti 
                  WHERE tipodoc = 'ESP' 
                  ORDER BY documento ASC";
    $res_mesas = $mysqli_pos->query($sql_mesas);

    $mesas_para_nube = [];
    $total_mesas_encontradas = 0;

    if ($res_mesas && $res_mesas->num_rows > 0) {
        while ($m = $res_mesas->fetch_assoc()) {
            $total_mesas_encontradas++;
            $documento = trim($m['documento']);
            $mesa_clean = preg_replace('/^E/i', '', $documento);

            // Buscar si ya tiene PIN en z_status_pago
            $sql_pin = "SELECT token_pin, estado FROM z_status_pago WHERE documento = '" . $mysqli_sistema->real_escape_string($documento) . "' LIMIT 1";
            $res_pin = $mysqli_sistema->query($sql_pin);
            $pin_row = $res_pin ? $res_pin->fetch_assoc() : null;

            $pin = '';
            if ($pin_row && !empty($pin_row['token_pin'])) {
                $pin = $pin_row['token_pin'];
            } else {
                // Generar PIN de 6 dígitos automático
                $pin = (string) rand(100000, 999999);
                $f_now = date('Y-m-d H:i:s');
                $mysqli_sistema->query("INSERT INTO z_status_pago (documento, token_pin, estado, created_at, updated_at) 
                                        VALUES ('" . $mysqli_sistema->real_escape_string($documento) . "', '{$pin}', '0', '{$f_now}', '{$f_now}')
                                        ON DUPLICATE KEY UPDATE token_pin = '{$pin}', updated_at = '{$f_now}';");
            }

            // Consultar ítems de la mesa en opermv
            $items = [];
            $total_bs = 0;
            $sql_items = "SELECT fechayhora, cantidad, nombre, preciounit, montoneto 
                          FROM opermv 
                          WHERE documento = '" . $mysqli_pos->real_escape_string($documento) . "' 
                          ORDER BY fechayhora ASC";
            $res_items = $mysqli_pos->query($sql_items);

            if ($res_items && $res_items->num_rows > 0) {
                while ($it = $res_items->fetch_assoc()) {
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
            } else {
                $total_bs = (float) $m['totalfinal'];
            }

            $total_usd = $total_bs / $tasa;
            $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
            $f_act = date('Y-m-d H:i:s');

            // Actualizar tabla local mijar_sistema.z_mesas_activas (3307)
            $mysqli_sistema->query("INSERT INTO z_mesas_activas (documento, mesa, token_pin, total_bs, total_usd, tasa_dia, items_json, estado, created_at, updated_at)
                                    VALUES ('" . $mysqli_sistema->real_escape_string($documento) . "', 'MESA {$mesa_clean}', '{$pin}', " . round($total_bs, 2) . ", " . round($total_usd, 2) . ", " . round($tasa, 4) . ", '" . $mysqli_sistema->real_escape_string($items_json) . "', 'ACTIVA', '{$f_act}', '{$f_act}')
                                    ON DUPLICATE KEY UPDATE 
                                        token_pin = '{$pin}',
                                        total_bs = " . round($total_bs, 2) . ",
                                        total_usd = " . round($total_usd, 2) . ",
                                        tasa_dia = " . round($tasa, 4) . ",
                                        items_json = '" . $mysqli_sistema->real_escape_string($items_json) . "',
                                        estado = 'ACTIVA',
                                        updated_at = '{$f_act}';");

            $mesas_para_nube[] = [
                'documento' => $documento,
                'mesa' => "MESA " . $mesa_clean,
                'token_pin' => $pin,
                'total_bs' => round($total_bs, 2),
                'total_usd' => round($total_usd, 2),
                'tasa_dia' => round($tasa, 4),
                'items' => $items,
                'estado_pago' => $pin_row['estado'] ?? '0',
                'referencia' => $pin_row['referencia'] ?? null,
            ];
        }
    }

    $mysqli_pos->close();
    $mysqli_sistema->close();

    // 3. Enviar a la nube vía HTTPS POST Outbound
    if (empty($mesas_para_nube)) {
        echo "[{$timestamp}] Sin mesas activas con pedidos para sincronizar." . PHP_EOL;
        return true;
    }

    $payload = json_encode(['mesas' => $mesas_para_nube], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url_api_cloud);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $respuesta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200) {
        $json_resp = json_decode($respuesta, true);
        $cant_mesas = count($mesas_para_nube);
        $resumen = [];
        foreach ($mesas_para_nube as $m) {
            $resumen[] = "{$m['mesa']} (PIN: {$m['token_pin']}, Bs. " . number_format($m['total_bs'], 2, ',', '.') . " / $" . number_format($m['total_usd'], 2) . ")";
        }
        echo "[{$timestamp}] [OK NUBE 200] Sincronizada(s) {$cant_mesas} mesa(s): " . implode(' | ', $resumen) . PHP_EOL;

        // Si la nube envió pagos pendientes reportados, registrarlos en local
        if (!empty($json_resp['pagos_pendientes']) && is_array($json_resp['pagos_pendientes'])) {
            $c_rep = count($json_resp['pagos_pendientes']);
            echo "[{$timestamp}] [AVISO] Se detectaron {$c_rep} pago(s) reportado(s) por clientes en la nube." . PHP_EOL;
        }
    } else {
        echo "[{$timestamp}] [ERROR NUBE HTTP {$http_code}] {$curl_err} " . substr($respuesta, 0, 150) . PHP_EOL;
    }

    return true;
}

// Bucle principal del sincronizador
if ($modo_once) {
    sincronizar_ciclo($host_local, $user_local, $pass_local, $port_local, $bd_pos, $bd_sistema, $url_api_cloud, $datos_conexion);
} else {
    while (true) {
        sincronizar_ciclo($host_local, $user_local, $pass_local, $port_local, $bd_pos, $bd_sistema, $url_api_cloud, $datos_conexion);
        sleep($intervalo_segundos);
    }
}
