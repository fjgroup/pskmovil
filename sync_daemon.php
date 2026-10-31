<?php
/**
 * ============================================================================
 * SINCRONIZADOR LOCAL A LA NUBE - MI JARDIN RESTAURANT
 * ============================================================================
 * Lee las mesas activas de Premium Soft, el Top 3 de productos más vendidos
 * de las últimas 100 ventas en Kardex, y la tabla satélite local.
 * Envía la información y fotos a la nube vía HTTPS Outbound.
 *
 * Ejecución:
 *   php sync_daemon.php          (Modo demonio continuo cada 12 segundos)
 *   php sync_daemon.php --once   (Modo ejecución única para Cron o Tarea Programada)
 * ============================================================================
 */

date_default_timezone_set('America/Caracas');

// Cargar configuración centralizada desde la BD mijar26273_sistema
require_once __DIR__ . '/funciones/config_helper.php';
require_once __DIR__ . '/funciones/mesas_helper.php';
require_once __DIR__ . '/funciones/verificador_bd.php';

// Auto-verificar y auto-reparar tablas satélite al iniciar el servicio
verificar_y_reparar_base_datos_satelite();

$datos_conexion = obtener_config_sistema();

// Entorno: 'desarrollo' o 'produccion' (gestionado en BD z_config_sistema)
$modo_entorno = strtolower(trim($datos_conexion['modo_entorno'] ?? 'desarrollo'));

// Parámetros de bases locales (3307 en desarrollo, 3306 en producción si no se especifica)
$host_local = $datos_conexion['host'] ?? '127.0.0.1';
$user_local = $datos_conexion['user'] ?? 'root';
$pass_local = $datos_conexion['pw'] ?? 'root';
$port_local = (int) ($datos_conexion['port'] ?? ($modo_entorno === 'produccion' ? 3306 : 3307));
$bd_pos     = $datos_conexion['bd_pos'] ?? ($datos_conexion['bd'] ?? 'adminbahiap');
$bd_sistema = $datos_conexion['bd_sistema'] ?? 'mijar26273_sistema';

// Endpoint Cloud seguro HTTPS (Outbound hacia el servidor dedicado / VPS)
if (!empty($datos_conexion['url_hosting'])) {
    $base = rtrim($datos_conexion['url_hosting'], '/');
    $url_api_cloud = $base . '/api/sincronizar-mesas';
} elseif (!empty($datos_conexion['url_web_mesa'])) {
    $base = preg_replace('#/mesa/?$#i', '', $datos_conexion['url_web_mesa']);
    $url_api_cloud = $base . '/api/sincronizar-mesas';
} elseif ($modo_entorno === 'produccion') {
    $url_api_cloud = "https://mijardinrestaurant.com/api/sincronizar-mesas";
} else {
    // La sincronización siempre va directamente al hosting web en producción
    $url_api_cloud = "https://mijardinrestaurant.com/api/sincronizar-mesas";
}

$modo_once = in_array('--once', $argv);
$intervalo_segundos = 12;

echo "========================================================" . PHP_EOL;
echo "  SINCRONIZADOR CLOUD - MI JARDIN RESTAURANT" . PHP_EOL;
echo "========================================================" . PHP_EOL;
echo " Entorno:        " . strtoupper($modo_entorno) . PHP_EOL;
echo " Servidor Local: MySQL {$host_local}:{$port_local}" . PHP_EOL;
echo " BD POS:         {$bd_pos}" . PHP_EOL;
echo " BD Satélite:    {$bd_sistema}" . PHP_EOL;
echo " Endpoint Nube:  {$url_api_cloud}" . PHP_EOL;
echo " Modo:           " . ($modo_once ? "Ejecución única (--once)" : "Continuo (cada {$intervalo_segundos}s)") . PHP_EOL;
echo "========================================================" . PHP_EOL . PHP_EOL;

/**
 * Calcula los 3 productos más vendidos de las últimas 100 ventas en kardex.
 * Se ejecuta automáticamente cada 1 hora (3600 segundos) o al iniciar el servicio.
 */
function procesar_top_productos($mysqli_pos, $mysqli_sistema, $tasa)
{
    static $cache_top = [];
    static $ultimo_tiempo_top = 0;

    $ahora = time();
    // Ejecutar cada 1 hora (3600 segundos) o al iniciar
    if (!empty($cache_top) && ($ahora - $ultimo_tiempo_top < 3600)) {
        return $cache_top;
    }

    $sql = "
        SELECT 
            k.codigo,
            a.nombre,
            a.precio1,
            a.impuesto1,
            a.preciofin1,
            a.rutafoto,
            SUM(k.cantidad) AS total_vendido
        FROM kardex k
        INNER JOIN articulo a ON a.codigo = k.codigo
        WHERE k.origen = 'FAC'
          AND CAST(a.grupo AS UNSIGNED) NOT BETWEEN 10 AND 17
          AND k.documento IN (
              SELECT doc FROM (
                  SELECT DISTINCT documento AS doc
                  FROM kardex
                  WHERE origen = 'FAC'
                  ORDER BY documento DESC
                  LIMIT 200
              ) ultimas_ventas
          )
        GROUP BY k.codigo, a.nombre, a.precio1, a.impuesto1, a.preciofin1, a.rutafoto
        ORDER BY total_vendido DESC
        LIMIT 5
    ";

    $res = $mysqli_pos->query($sql);
    if (!$res || $res->num_rows === 0) {
        return $cache_top;
    }

    $top = [];
    $pos = 1;
    $now = date('Y-m-d H:i:s');

    while ($r = $res->fetch_assoc()) {
        $codigo = trim($r['codigo']);
        $nombre = trim(mb_convert_encoding($r['nombre'], 'UTF-8', 'ISO-8859-1'));
        $precio1 = (float)$r['precio1'];
        $impuesto1 = (float)$r['impuesto1'];
        $precio_bs = round($precio1 * (1 + ($impuesto1 / 100)), 2);
        $precio_usd = $tasa > 0 ? round($precio_bs / $tasa, 2) : 0;
        $total_vendido = round((float)$r['total_vendido'], 2);

        $rutafoto = trim($r['rutafoto'] ?? '');
        $img_name = !empty($rutafoto) ? basename(str_replace('\\', '/', $rutafoto)) : '';
        $img_base64 = null;

        // Buscar imagen física en disco para transferirla a la nube si existe
        $posibles_rutas = [
            $rutafoto,
            'C:\\Premium\\Imagenes\\' . $img_name,
            __DIR__ . '/imagenes/' . $img_name,
        ];

        foreach ($posibles_rutas as $pr) {
            if (!empty($pr) && file_exists($pr) && is_file($pr)) {
                $img_base64 = base64_encode(file_get_contents($pr));
                break;
            }
        }

        // Actualizar tabla z_top_productos en BD satélite local (puerto 3307)
        $mysqli_sistema->query("
            INSERT INTO z_top_productos (posicion, codigo, nombre, precio_bs, precio_usd, imagen, total_vendido, updated_at)
            VALUES ({$pos}, '{$mysqli_sistema->real_escape_string($codigo)}', '{$mysqli_sistema->real_escape_string($nombre)}', {$precio_bs}, {$precio_usd}, '{$mysqli_sistema->real_escape_string($img_name)}', {$total_vendido}, '{$now}')
            ON DUPLICATE KEY UPDATE
                codigo = '{$mysqli_sistema->real_escape_string($codigo)}',
                nombre = '{$mysqli_sistema->real_escape_string($nombre)}',
                precio_bs = {$precio_bs},
                precio_usd = {$precio_usd},
                imagen = '{$mysqli_sistema->real_escape_string($img_name)}',
                total_vendido = {$total_vendido},
                updated_at = '{$now}';
        ");

        $top[] = [
            'posicion' => $pos,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'precio_bs' => $precio_bs,
            'precio_usd' => $precio_usd,
            'total_vendido' => $total_vendido,
            'imagen_nombre' => $img_name,
            'imagen_base64' => $img_base64,
        ];

        $pos++;
    }

    $mysqli_sistema->query("DELETE FROM z_top_productos WHERE posicion > 5");

    $cache_top = $top;
    $ultimo_tiempo_top = $ahora;
    echo "[" . date('H:i:s') . "] [TOP 5 KARDEX] Calculados 5 artículos más vendidos (200 ult. ventas, excl. grupos 10-17) y guardados en satélite local." . PHP_EOL;

    return $top;
}

function sincronizar_ciclo($host_local, $user_local, $pass_local, $port_local, $bd_pos, $bd_sistema, $url_api_cloud, $datos_conexion)
{
    $timestamp = date('H:i:s');

    // 1. Conexión a bases locales (puerto 3307)
    $mysqli_pos = @new mysqli($host_local, $user_local, $pass_local, $bd_pos, $port_local);
    if ($mysqli_pos->connect_error) {
        echo "[{$timestamp}] [ERROR LOCAL] No se pudo conectar a {$bd_pos} ({$port_local}): " . $mysqli_pos->connect_error . PHP_EOL;
        return false;
    }

    $mysqli_sistema = @new mysqli($host_local, $user_local, $pass_local, $bd_sistema, $port_local);
    if ($mysqli_sistema->connect_error) {
        $bd_alt = ($bd_sistema === 'mijar26273_sistema') ? 'mijar_sistema' : 'mijar26273_sistema';
        $mysqli_sistema = @new mysqli($host_local, $user_local, $pass_local, $bd_alt, $port_local);
        if (!$mysqli_sistema->connect_error) {
            $bd_sistema = $bd_alt;
        } else {
            echo "[{$timestamp}] [ERROR LOCAL] No se pudo conectar a {$bd_sistema} ({$port_local}): " . $mysqli_sistema->connect_error . PHP_EOL;
            $mysqli_pos->close();
            return false;
        }
    }

    // Tasa del día en tiempo real: consultar directamente tabla monedas de Premium Soft
    $tasa = 0;
    $res_tasa = $mysqli_pos->query("SELECT ultimacotizacion FROM monedas WHERE (tipo = 2 OR codigo = '001') AND ultimacotizacion > 0 ORDER BY codigo ASC LIMIT 1");
    if ($res_tasa && $res_tasa->num_rows > 0) {
        $row_tasa = $res_tasa->fetch_assoc();
        $tasa = (float) $row_tasa['ultimacotizacion'];
    }

    if ($tasa <= 0) {
        $tasa = (float) ($datos_conexion['monedafactor'] ?? 1);
    }
    if ($tasa <= 0) $tasa = 1;

    // Mantener sincronizada la tasa en z_config_sistema de mijar26273_sistema
    $mysqli_sistema->query("INSERT INTO z_config_sistema (clave, valor, updated_at) 
        VALUES ('monedafactor', '{$tasa}', NOW()) 
        ON DUPLICATE KEY UPDATE valor = '{$tasa}', updated_at = NOW()");

    // 2. Consultar mesas abiertas en operti (tipodoc = 'ESP')
    $sql_mesas = "SELECT documento, nombrecli, totneto, totimpuest, totalfinal, fechayhora 
                  FROM operti 
                  WHERE tipodoc = 'ESP' 
                  ORDER BY documento ASC";
    $res_mesas = $mysqli_pos->query($sql_mesas);

    // Registro de mesas reales vs. pedidos en caja (001 para llevar, 099, nombres, etc.)
    $registro_mesas = cargar_registro_mesas($mysqli_pos, $datos_conexion);

    $mesas_para_nube = [];
    $mesas_cerradas_nube = [];
    $total_mesas_encontradas = 0;
    $documentos_abiertos = [];

    if ($res_mesas && $res_mesas->num_rows > 0) {
        while ($m = $res_mesas->fetch_assoc()) {
            $documento = trim($m['documento']);
            // Pedidos en caja: no son mesas, no se sincronizan ni generan QR/PIN
            if (es_pedido_caja($documento, $registro_mesas)) {
                continue;
            }
            $total_mesas_encontradas++;
            $documentos_abiertos[] = $documento;
            $mesa_clean = preg_replace('/^E/i', '', $documento);

            // Mesa reabierta: si estaba CERRADA en z_mesas_activas, reiniciar pago/PIN del cliente anterior
            $q_prev = $mysqli_sistema->query("SELECT estado FROM z_mesas_activas WHERE documento = '" . $mysqli_sistema->real_escape_string($documento) . "' LIMIT 1");
            if ($q_prev && ($r_prev = $q_prev->fetch_assoc()) && $r_prev['estado'] === 'CERRADA') {
                $mysqli_sistema->query("DELETE FROM z_status_pago WHERE documento = '" . $mysqli_sistema->real_escape_string($documento) . "'");
            }

            // Buscar si ya tiene PIN en z_status_pago
            $sql_pin = "SELECT token_pin, estado, referencia, metodo, banco_origen, monto FROM z_status_pago WHERE documento = '" . $mysqli_sistema->real_escape_string($documento) . "' LIMIT 1";
            $res_pin = $mysqli_sistema->query($sql_pin);
            $pin_row = $res_pin ? $res_pin->fetch_assoc() : null;

            $pin = '';
            if ($pin_row && !empty($pin_row['token_pin']) && (string)$pin_row['estado'] === '0') {
                $pin = $pin_row['token_pin'];
            } else {
                do {
                    $pin = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $chk_pin = $mysqli_sistema->query("SELECT id FROM z_status_pago WHERE token_pin = '{$pin}' AND estado = '0' LIMIT 1");
                } while ($chk_pin && $chk_pin->num_rows > 0);

                $f_now = date('Y-m-d H:i:s');
                $mysqli_sistema->query("INSERT INTO z_status_pago (documento, token_pin, estado, created_at, updated_at) 
                                        VALUES ('" . $mysqli_sistema->real_escape_string($documento) . "', '{$pin}', '0', '{$f_now}', '{$f_now}')
                                        ON DUPLICATE KEY UPDATE token_pin = '{$pin}', estado = '0', metodo = NULL, banco_origen = NULL, referencia = NULL, monto = NULL, fecha_reporte = NULL, aprobado_por = NULL, fecha_confirmacion = NULL, updated_at = '{$f_now}';");
            }

            // Consultar ítems reales de la mesa en opermv (fuente de la verdad)
            $items = [];
            $subtotal_items = 0;
            $impuesto_items = 0;
            $sql_items = "SELECT fechayhora, cantidad, nombre, preciounit, montoneto, timpueprc, impuesto1 
                          FROM opermv 
                          WHERE documento = '" . $mysqli_pos->real_escape_string($documento) . "' 
                          ORDER BY fechayhora ASC";
            $res_items = $mysqli_pos->query($sql_items);

            if ($res_items && $res_items->num_rows > 0) {
                while ($it = $res_items->fetch_assoc()) {
                    $cant = (float) $it['cantidad'];
                    $monto = (float) $it['montoneto'];
                    if ($monto <= 0 && $cant > 0 && (float) $it['preciounit'] > 0) {
                        $monto = $cant * (float) $it['preciounit'];
                    }
                    $subtotal_items += $monto;

                    $imp = isset($it['impuesto1']) && (float) $it['impuesto1'] > 0 
                        ? (float) $it['impuesto1'] 
                        : ($monto * ((float) ($it['timpueprc'] ?? 0) / 100));
                    $impuesto_items += $imp;

                    $items[] = [
                        'hora' => date('h:i A', strtotime($it['fechayhora'] ?? 'now')),
                        'cant' => $cant,
                        'nombre' => mb_convert_encoding($it['nombre'], 'UTF-8', 'ISO-8859-1'),
                        'preciounit' => (float) $it['preciounit'],
                        'total' => $monto,
                    ];
                }

                $subtotal_bs = $subtotal_items;
                $impuesto_bs = $impuesto_items;
                $total_final_bs = $subtotal_bs + $impuesto_bs;

                if (abs((float)($m['totneto'] ?? 0) - $subtotal_bs) > 0.05 || abs((float)($m['totalfinal'] ?? 0) - $total_final_bs) > 0.05) {
                    $mysqli_pos->query("UPDATE operti SET 
                        totbruto = " . round($subtotal_bs, 2) . ",
                        totneto = " . round($subtotal_bs, 2) . ",
                        totimpuest = " . round($impuesto_bs, 2) . ",
                        totalfinal = " . round($total_final_bs, 2) . "
                        WHERE documento = '" . $mysqli_pos->real_escape_string($documento) . "' AND tipodoc = 'ESP'");
                }
            } else {
                $subtotal_bs = isset($m['totneto']) && (float)$m['totneto'] > 0 ? (float)$m['totneto'] : 0.00;
                $impuesto_bs = isset($m['totimpuest']) ? (float)$m['totimpuest'] : 0.00;
                $total_final_bs = isset($m['totalfinal']) && (float)$m['totalfinal'] > 0 ? (float)$m['totalfinal'] : ($subtotal_bs + $impuesto_bs);
            }

            $subtotal_usd   = round($subtotal_bs / $tasa, 2);
            $impuesto_usd   = round($impuesto_bs / $tasa, 2);
            $total_final_usd = round($total_final_bs / $tasa, 2);

            $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
            $f_act = date('Y-m-d H:i:s');

            // Actualizar tabla local mijar26273_sistema.z_mesas_activas
            $mysqli_sistema->query("INSERT INTO z_mesas_activas (documento, mesa, token_pin, subtotal_bs, impuesto_bs, total_bs, subtotal_usd, impuesto_usd, total_usd, tasa_dia, items_json, estado, created_at, updated_at)
                                    VALUES ('" . $mysqli_sistema->real_escape_string($documento) . "', 'MESA {$mesa_clean}', '{$pin}', " . round($subtotal_bs, 2) . ", " . round($impuesto_bs, 2) . ", " . round($total_final_bs, 2) . ", " . round($subtotal_usd, 2) . ", " . round($impuesto_usd, 2) . ", " . round($total_final_usd, 2) . ", " . round($tasa, 4) . ", '" . $mysqli_sistema->real_escape_string($items_json) . "', 'ACTIVA', '{$f_act}', '{$f_act}')
                                    ON DUPLICATE KEY UPDATE 
                                        token_pin = '{$pin}',
                                        subtotal_bs = " . round($subtotal_bs, 2) . ",
                                        impuesto_bs = " . round($impuesto_bs, 2) . ",
                                        total_bs = " . round($total_final_bs, 2) . ",
                                        subtotal_usd = " . round($subtotal_usd, 2) . ",
                                        impuesto_usd = " . round($impuesto_usd, 2) . ",
                                        total_usd = " . round($total_final_usd, 2) . ",
                                        tasa_dia = " . round($tasa, 4) . ",
                                        items_json = '" . $mysqli_sistema->real_escape_string($items_json) . "',
                                        estado = 'ACTIVA',
                                        updated_at = '{$f_act}';");

            $mesas_para_nube[] = [
                'documento' => $documento,
                'mesa' => "MESA " . $mesa_clean,
                'token_pin' => $pin,
                'subtotal_bs' => round($subtotal_bs, 2),
                'impuesto_bs' => round($impuesto_bs, 2),
                'total_bs' => round($total_final_bs, 2),
                'subtotal_usd' => round($subtotal_usd, 2),
                'impuesto_usd' => round($impuesto_usd, 2),
                'total_usd' => round($total_final_usd, 2),
                'tasa_dia' => round($tasa, 4),
                'items' => $items,
                'estado_pago' => $pin_row['estado'] ?? '0',
                'referencia' => $pin_row['referencia'] ?? null,
            ];
        }
    }

    // Reconciliación: Detectar mesas facturadas y cerradas en Premium Soft
    $docs_abiertos_escaped = array_map(function($d) use ($mysqli_sistema) {
        return "'" . $mysqli_sistema->real_escape_string($d) . "'";
    }, $documentos_abiertos);

    $where_cerradas = !empty($docs_abiertos_escaped) 
        ? "WHERE estado = 'ACTIVA' AND documento NOT IN (" . implode(',', $docs_abiertos_escaped) . ")"
        : "WHERE estado = 'ACTIVA'";

    $q_cerradas = $mysqli_sistema->query("SELECT documento, mesa, token_pin, total_bs, created_at FROM z_mesas_activas {$where_cerradas}");
    if ($q_cerradas && $q_cerradas->num_rows > 0) {
        // Consultar los ultimos 50 documentos tipo FAC emitidos en Premium Soft (aprovecha indice tipodoc+documento en <1ms)
        $res_facs = $mysqli_pos->query("
            SELECT documento, ordentrab, totalfinal, totpagos, fechayhora, estatusdoc 
            FROM operti 
            WHERE tipodoc = 'FAC' AND ordentrab != '' 
            ORDER BY documento DESC 
            LIMIT 50
        ");
        $ultimas_facs = [];
        if ($res_facs) {
            while ($rf = $res_facs->fetch_assoc()) {
                $ultimas_facs[] = $rf;
            }
        }

        while ($mc = $q_cerradas->fetch_assoc()) {
            $doc_c = $mc['documento'];
            $doc_c_esc = $mysqli_sistema->real_escape_string($doc_c);
            $pin_c = $mc['token_pin'] ?? '';
            $total_c = (float)($mc['total_bs'] ?? 0);
            $f_crea = !empty($mc['created_at']) ? strtotime($mc['created_at']) : (time() - 7200);
            $mesa_norm = normalizar_codigo_mesa($doc_c);

            // Consultar estado previo de pago en z_status_pago
            $q_prev_pago = $mysqli_sistema->query("SELECT estado, referencia, metodo, banco_origen FROM z_status_pago WHERE documento = '{$doc_c_esc}' LIMIT 1");
            $prev_pago = $q_prev_pago ? $q_prev_pago->fetch_assoc() : null;
            $tenia_reporte_movil = ($prev_pago && ($prev_pago['estado'] === '2' || (!empty($prev_pago['referencia']) && !in_array($prev_pago['estado'], ['1', '3', '4']))));

            // Buscar si esta comanda tiene una Factura (FAC) correspondiente en Premium Soft
            $factura_encontrada = null;
            foreach ($ultimas_facs as $fac) {
                // 1. Coincidencia de mesa normalizada (ej. 'E*099' == '099', 'EJARD 1' == 'JARD01')
                $fac_ordentrab_norm = normalizar_codigo_mesa($fac['ordentrab']);
                if ($fac_ordentrab_norm !== $mesa_norm) {
                    continue;
                }

                // 2. Coincidencia exacta de monto (el monto total en Premium Soft es identico al centavo)
                $monto_fac = round((float)$fac['totalfinal'], 2);
                $monto_mesa = round($total_c, 2);
                if ($monto_mesa > 0 && $monto_fac !== $monto_mesa) {
                    continue;
                }

                // 3. Coincidencia temporal: la factura debe haberse emitido despues de abrir la mesa
                $f_fac = strtotime($fac['fechayhora']);
                if ($f_fac < $f_crea) {
                    continue;
                }

                // 4. PASO CRUCIAL ANTI-REUTILIZACION: verificar si este numero de factura ya fue asociado a otro PIN
                $num_fac_candidata = trim($fac['documento']);
                $chk_usada = $mysqli_sistema->query("SELECT id FROM z_facturas_mesas WHERE num_factura = '" . $mysqli_sistema->real_escape_string($num_fac_candidata) . "' LIMIT 1");
                if ($chk_usada && $chk_usada->num_rows > 0) {
                    // Esta factura ya pertenece a una venta previa. No puede ser reutilizada!
                    continue;
                }

                // Factura legitima, virgen y no usada encontrada
                $factura_encontrada = $fac;
                break;
            }

            if ($factura_encontrada !== null) {
                $num_fac = trim($factura_encontrada['documento']);
                $num_fac_esc = $mysqli_sistema->real_escape_string($num_fac);
                $monto_fac = (float)$factura_encontrada['totalfinal'];
                $fch_fac = $factura_encontrada['fechayhora'];
                $ref_fac = 'FAC-' . $num_fac;

                // Determinar el metodo de pago registrado en Premium Soft consultando operclit
                $metodo_pago = 'TARJETA DE DÉBITO (PUNTO)';
                $banco_pago = 'Punto de Venta';

                $q_clit = $mysqli_pos->query("
                    SELECT efectivo, tardebmto, tardebnom, tardebnum, mtotarjeta, bancotar, otrotipo 
                    FROM operclit 
                    WHERE tipodoc = 'FAC' AND documento = '" . $mysqli_pos->real_escape_string($num_fac) . "' 
                    LIMIT 1
                ");

                if ($q_clit && ($rclit = $q_clit->fetch_assoc())) {
                    $t_deb = (float)$rclit['tardebmto'];
                    $t_efe = (float)$rclit['efectivo'];
                    $t_tar = (float)$rclit['mtotarjeta'];
                    $t_otr = (float)$rclit['otrotipo'];

                    if ($t_deb > 0 && $t_efe <= 0 && $t_tar <= 0) {
                        $metodo_pago = 'TARJETA DE DÉBITO (PUNTO)';
                        $banco_pago = !empty($rclit['tardebnom']) ? trim($rclit['tardebnom']) : 'Punto de Venta';
                    } elseif ($t_efe > 0 && $t_deb <= 0 && $t_tar <= 0) {
                        $metodo_pago = 'EFECTIVO (CAJA)';
                        $banco_pago = 'Caja Restaurante';
                    } elseif ($t_tar > 0 && $t_deb <= 0 && $t_efe <= 0) {
                        $metodo_pago = 'TARJETA DE CRÉDITO';
                        $banco_pago = !empty($rclit['bancotar']) ? trim($rclit['bancotar']) : 'Punto de Venta';
                    } elseif ($t_deb > 0 && $t_efe > 0) {
                        $metodo_pago = 'MIXTO (DÉBITO + EFECTIVO)';
                        $banco_pago = 'Caja y Punto';
                    } elseif ($t_otr > 0) {
                        $metodo_pago = 'PAGO MÓVIL EN CAJA';
                        $banco_pago = 'Caja Restaurante';
                    }
                }

                // Si el cliente habia reportado pago movil por la app, conservar su referencia bancaria
                if ($tenia_reporte_movil && !empty($prev_pago['metodo'])) {
                    $metodo_pago = $prev_pago['metodo'];
                    if (!empty($prev_pago['banco_origen'])) {
                        $banco_pago = $prev_pago['banco_origen'];
                    }
                }

                // 1. Blindar factura en z_facturas_mesas para que ningun otro PIN pueda reclamarla
                $mysqli_sistema->query("
                    INSERT INTO z_facturas_mesas (num_factura, documento_mesa, token_pin, monto, metodo, banco, fechayhora_factura, created_at)
                    VALUES (
                        '{$num_fac_esc}',
                        '{$doc_c_esc}',
                        '" . $mysqli_sistema->real_escape_string($pin_c) . "',
                        {$monto_fac},
                        '" . $mysqli_sistema->real_escape_string($metodo_pago) . "',
                        '" . $mysqli_sistema->real_escape_string($banco_pago) . "',
                        '{$fch_fac}',
                        NOW()
                    )
                    ON DUPLICATE KEY UPDATE token_pin = VALUES(token_pin);
                ");

                // 2. Actualizar z_status_pago como CONFIRMADO (estado 1 - Pantalla Verde)
                $mysqli_sistema->query("
                    UPDATE z_status_pago 
                    SET estado = '1',
                        metodo = '" . $mysqli_sistema->real_escape_string($metodo_pago) . "',
                        banco_origen = '" . $mysqli_sistema->real_escape_string($banco_pago) . "',
                        referencia = '{$ref_fac}',
                        num_factura = '{$num_fac_esc}',
                        monto = {$monto_fac},
                        aprobado_por = 'Caja Premium Soft (Facturado)',
                        fecha_confirmacion = '{$fch_fac}',
                        updated_at = NOW() 
                    WHERE documento = '{$doc_c_esc}'
                ");

                // 3. Actualizar z_mesas_activas
                $mysqli_sistema->query("
                    UPDATE z_mesas_activas 
                    SET estado = 'CERRADA',
                        num_factura = '{$num_fac_esc}',
                        updated_at = NOW() 
                    WHERE documento = '{$doc_c_esc}'
                ");

                $mesas_cerradas_nube[] = [
                    'documento' => $mc['documento'],
                    'token_pin' => $mc['token_pin'],
                    'estado_pago' => '1',
                    'metodo' => $metodo_pago,
                    'banco_origen' => $banco_pago,
                    'referencia' => $ref_fac,
                    'num_factura' => $num_fac,
                    'monto' => $monto_fac,
                    'fecha_confirmacion' => $fch_fac,
                ];

                echo "[{$timestamp}] [FACTURADA] Mesa {$mc['documento']} pagada en Premium Soft (FAC #{$num_fac}, {$metodo_pago}, Bs. " . number_format($monto_fac, 2, ',', '.') . "). Estado: 1 (VERDE)." . PHP_EOL;

            } else {
                // NO se encontro factura valida para este PIN
                // Si el cliente tenia reporte movil previo, se aprueba si caja la cerro; si no, pasa a cerrada (3)
                $mysqli_sistema->query("UPDATE z_mesas_activas SET estado = 'CERRADA', updated_at = NOW() WHERE documento = '{$doc_c_esc}'");

                if ($tenia_reporte_movil) {
                    $mysqli_sistema->query("
                        UPDATE z_status_pago 
                        SET estado = '1', aprobado_por = 'Caja Premium', fecha_confirmacion = NOW(), updated_at = NOW() 
                        WHERE documento = '{$doc_c_esc}'
                    ");
                    $est_final = '1';
                } else {
                    // Sin factura y sin pago reportado: el cliente no compro o se fue -> MESA CERRADA (3 - Pantalla Blanca)
                    $mysqli_sistema->query("
                        UPDATE z_status_pago 
                        SET estado = '3', aprobado_por = 'Cierre sin facturar', fecha_confirmacion = NOW(), updated_at = NOW() 
                        WHERE documento = '{$doc_c_esc}' AND estado IN ('0','4')
                    ");
                    $est_final = '3';
                }

                $mesas_cerradas_nube[] = [
                    'documento' => $mc['documento'],
                    'token_pin' => $mc['token_pin'],
                    'estado_pago' => $est_final,
                ];

                echo "[{$timestamp}] [CERRADA] Mesa {$mc['documento']} cerrada sin factura nueva. Estado final: {$est_final} (3=mesa cerrada sin compra)." . PHP_EOL;
            }
        }
    }

    // 3. Procesar Top 3 artículos más vendidos de las últimas 100 ventas en Kardex
    $top_productos = procesar_top_productos($mysqli_pos, $mysqli_sistema, $tasa);

    $mysqli_pos->close();
    $mysqli_sistema->close();

    // 4. Enviar a la nube vía HTTPS POST Outbound
    if (empty($mesas_para_nube) && empty($top_productos) && empty($mesas_cerradas_nube)) {
        echo "[{$timestamp}] Sin mesas activas ni productos destacados para sincronizar." . PHP_EOL;
        return true;
    }

    $payload_arr = [
        'mesas' => $mesas_para_nube,
        'mesas_cerradas' => $mesas_cerradas_nube,
        'top_productos' => $top_productos,
    ];
    $payload = json_encode($payload_arr, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url_api_cloud);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
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
        $info_top = !empty($top_productos) ? " + Top 5 Productos" : "";
        echo "[{$timestamp}] [OK NUBE 200] Sincronizada(s) {$cant_mesas} mesa(s){$info_top}: " . (empty($resumen) ? "Sin mesas abiertas" : implode(' | ', $resumen)) . PHP_EOL;

        // Si la nube envió pagos pendientes reportados, registrarlos en local (mijar26273_sistema:3307)
        if (!empty($json_resp['pagos_pendientes']) && is_array($json_resp['pagos_pendientes'])) {
            $c_rep = count($json_resp['pagos_pendientes']);
            echo "[{$timestamp}] [AVISO] Se detectaron {$c_rep} pago(s) reportado(s) por clientes en la nube. Sincronizando con z_status_pago local..." . PHP_EOL;

            $mysqli_sync_pago = @new mysqli($host_local, $user_local, $pass_local, $bd_sistema, (int)$port_local);
            if ($mysqli_sync_pago && !$mysqli_sync_pago->connect_errno) {
                foreach ($json_resp['pagos_pendientes'] as $p) {
                    $doc_p = $mysqli_sync_pago->real_escape_string($p['documento'] ?? '');
                    $pin_p = $mysqli_sync_pago->real_escape_string($p['token_pin'] ?? '');
                    $est_p = $mysqli_sync_pago->real_escape_string($p['estado'] ?? '2');
                    $met_p = $mysqli_sync_pago->real_escape_string($p['metodo'] ?? '');
                    $ban_p = $mysqli_sync_pago->real_escape_string($p['banco_origen'] ?? '');
                    $ref_p = $mysqli_sync_pago->real_escape_string($p['referencia'] ?? '');
                    $mon_p = (float)($p['monto'] ?? 0);
                    $fec_p = $mysqli_sync_pago->real_escape_string($p['fecha_reporte'] ?? date('Y-m-d H:i:s'));

                    if (!empty($doc_p)) {
                        $mysqli_sync_pago->query("INSERT INTO z_status_pago (documento, token_pin, estado, metodo, banco_origen, referencia, monto, fecha_reporte, created_at, updated_at)
                            VALUES ('{$doc_p}', '{$pin_p}', '{$est_p}', '{$met_p}', '{$ban_p}', '{$ref_p}', {$mon_p}, '{$fec_p}', NOW(), NOW())
                            ON DUPLICATE KEY UPDATE 
                                estado = '{$est_p}',
                                metodo = '{$met_p}',
                                banco_origen = '{$ban_p}',
                                referencia = '{$ref_p}',
                                monto = {$mon_p},
                                fecha_reporte = '{$fec_p}',
                                updated_at = NOW()");
                    }
                }
                $mysqli_sync_pago->close();
                echo "[{$timestamp}] [OK] z_status_pago local actualizado correctamente en puerto {$port_local}." . PHP_EOL;
            } else {
                echo "[{$timestamp}] [ERROR] No se pudo conectar a {$bd_sistema} para actualizar pagos." . PHP_EOL;
            }
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
