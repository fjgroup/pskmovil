<?php
/**
 * Helper de Impresión Directa de Comandas para Comandas Móviles
 * 
 * Elimina la necesidad de ejecutar C:\Premium\Administrativo9x\Monitor\cmonitor.exe en caja.
 * Cuando un mesonero o usuario presiona "Comanda" en el móvil, tablet o PC:
 *  1. Identifica el destino de cada ítem según grupos.formato (COCINA vs BARRA).
 *  2. Totaliza y consolida cantidades de ítems idénticos en una sola línea (ej. 4 COMBO ESPECIAL BRASA).
 *  3. Genera el ticket térmico con formato ESC/POS estándar (80 mm).
 *  4. Envía los comandos ESC/POS directamente vía Socket TCP puerto 9100 a la ticketera de red
 *     (o fallback a la cola de Windows "cocina" si el socket no respondiera).
 *  5. Envía el comando de corte automático al ras (\x1bd\x03\x1dV\x01).
 *  6. Actualiza comandasmv.impresion = 2 para reflejar el estado impreso.
 */

require_once __DIR__ . '/config_helper.php';

if (!function_exists('obtener_conexion_impresion')) {
    function obtener_conexion_impresion() {
        static $cached_conn = null;
        if ($cached_conn instanceof \mysqli && @$cached_conn->ping()) {
            return $cached_conn;
        }

        $cfg = obtener_config_sistema();
        $host = $cfg['host'] ?? '127.0.0.1';
        $user = $cfg['user'] ?? 'root';
        $pw   = $cfg['pw'] ?? 'root';
        $bd   = $cfg['bd_pos'] ?? ($cfg['bd'] ?? 'adminbahiap');
        $port = (int)($cfg['port'] ?? 3307);

        try {
            $conn = @new \mysqli($host, $user, $pw, $bd, $port);
            if ($conn && !$conn->connect_errno) {
                $cached_conn = $conn;
                return $conn;
            }
        } catch (\Throwable $e) {}

        // Fallback usando candidatos de credenciales
        if (function_exists('credenciales_mysql_candidatas')) {
            foreach (credenciales_mysql_candidatas() as $cred) {
                try {
                    $conn = @new \mysqli($host, $cred['user'], $cred['pw'], $bd, (int)$cred['port']);
                    if ($conn && !$conn->connect_errno) {
                        $cached_conn = $conn;
                        return $conn;
                    }
                } catch (\Throwable $e) {}
            }
        }
        return null;
    }
}

if (!function_exists('detectar_ip_impresora_windows')) {
    /**
     * Consulta el registro de Windows para detectar automáticamente
     * la IP a la que apunta el puerto de la impresora térmica (ej. 'cocina').
     */
    function detectar_ip_impresora_windows($nombre_impresora = 'cocina') {
        static $cache = [];
        if (isset($cache[$nombre_impresora])) {
            return $cache[$nombre_impresora];
        }

        $ip_detectada = null;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $salida = [];
            @exec('reg query "HKLM\\SYSTEM\\CurrentControlSet\\Control\\Print\\Printers\\' . $nombre_impresora . '" /v "Port"', $salida);
            foreach ($salida as $linea) {
                if (preg_match('/Port\s+REG_SZ\s+(?:IP_)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i', $linea, $m)) {
                    $ip_detectada = $m[1];
                    break;
                }
            }
        }

        $cache[$nombre_impresora] = $ip_detectada;
        return $ip_detectada;
    }
}

if (!function_exists('validar_conexion_impresora')) {
    /**
     * Valida si la impresora configurada en Windows está encendida y accesible por red.
     */
    function validar_conexion_impresora($nombre_impresora = 'cocina', $port = 9100) {
        $ip = detectar_ip_impresora_windows($nombre_impresora);
        if (!$ip) {
            return [
                'success' => false,
                'ip'      => null,
                'mensaje' => "No se detectó un puerto IP asignado a la impresora '$nombre_impresora' en Windows."
            ];
        }

        $t0 = microtime(true);
        $fp = @fsockopen($ip, (int)$port, $errno, $errstr, 1.2);
        if ($fp) {
            @fclose($fp);
            $ms = round((microtime(true) - $t0) * 1000, 1);
            return [
                'success' => true,
                'ip'      => $ip,
                'port'    => $port,
                'ms'      => $ms,
                'mensaje' => "Impresora '$nombre_impresora' ($ip:$port) respondiendo y en línea ({$ms} ms)."
            ];
        }

        return [
            'success' => false,
            'ip'      => $ip,
            'port'    => $port,
            'error'   => $errstr,
            'mensaje' => "La impresora '$nombre_impresora' apunta a $ip en Windows, pero no responde. Verifique que esté encendida o que su IP coincida."
        ];
    }
}

if (!function_exists('obtener_config_impresoras')) {
    function obtener_config_impresoras() {
        $cfg = obtener_config_sistema();
        $entorno = $cfg['modo_entorno'] ?? 'desarrollo';

        $windows_printer = $cfg['impresora_windows'] ?? 'cocina';
        $ip_windows = detectar_ip_impresora_windows($windows_printer);

        // Si Windows tiene una IP asignada a la impresora, esa tiene la máxima prioridad
        $ip_defecto = $ip_windows ?: (($entorno === 'produccion') ? '192.168.1.100' : '192.168.0.150');

        $cocina_ip = $cfg['impresora_cocina_ip'] ?? $ip_defecto;
        $cocina_port = (int)($cfg['impresora_cocina_port'] ?? 9100);

        $barra_ip = $cfg['impresora_barra_ip'] ?? $cocina_ip;
        $barra_port = (int)($cfg['impresora_barra_port'] ?? 9100);

        return [
            'cocina_ip'   => $cocina_ip,
            'cocina_port' => $cocina_port,
            'barra_ip'    => $barra_ip,
            'barra_port'  => $barra_port,
            'windows_printer' => $windows_printer,
            'entorno'     => $entorno
        ];
    }
}

if (!function_exists('formatear_ticket_comanda_espos')) {
    /**
     * Construye los bytes ESC/POS exactos para la ticketera de 80 mm
     */
    function formatear_ticket_comanda_espos($tipo, $nro_ticket, $mesa, $vendedor, array $items_consolidados, $fecha = null) {
        if (!$fecha) {
            $fecha = date('d/m/Y h:i:s A');
        }

        // Ancho estándar de línea en ticketera 80 mm = 42 caracteres
        $ancho = 42;
        $sep = str_repeat('-', $ancho);

        // Bytes de control ESC/POS
        $ESC = "\x1b";
        $GS  = "\x1d";
        $INIT = $ESC . "@";                     // Inicializar impresora
        $ALIGN_CENTER = $ESC . "a\x01";          // Centrado
        $ALIGN_LEFT   = $ESC . "a\x00";          // Alineado a la izquierda
        $BOLD_ON      = $ESC . "E\x01";          // Negrita activada
        $BOLD_OFF     = $ESC . "E\x00";          // Negrita desactivada
        $DOUBLE_ON    = $ESC . "!\x20";          // Doble ancho (título)
        $DOUBLE_OFF   = $ESC . "!\x00";          // Ancho normal
        $FEED_CUT     = $ESC . "d\x03" . $GS . "V\x01"; // Avance 3 líneas y corte parcial/completo

        $out = "";
        $out .= $INIT;

        // 1. Encabezado principal (COCINA o BARRA)
        $out .= $ALIGN_CENTER . $BOLD_ON . $DOUBLE_ON;
        $out .= strtoupper(trim($tipo)) . "\n";
        $out .= $DOUBLE_OFF . $BOLD_OFF;

        // 2. Metadatos de la orden (sin línea de ID/Nro de transacción)
        $out .= $ALIGN_LEFT;
        $out .= "Fecha:    " . $fecha . "\n";
        $out .= "Mesa:     " . strtoupper(trim($mesa)) . "\n";
        $out .= "Mesonero: " . strtoupper(trim($vendedor)) . "\n";
        $out .= $ALIGN_CENTER . "Documento Sin Credito Fiscal\n" . $ALIGN_LEFT;
        $out .= $sep . "\n";

        // 3. Encabezado de columnas
        $out .= sprintf("%-7s %-34s\n", "Cant.", "Descripcion");
        $out .= $sep . "\n";

        // 4. Detalle de productos consolidados y totalizados
        $out .= $BOLD_ON;
        foreach ($items_consolidados as $item) {
            $cant_formateada = number_format((float)$item['cantidad'], 2, ',', '');
            $nombre_art = mb_strtoupper(trim($item['nombre']), 'UTF-8');
            
            // Si el nombre es muy largo, cortarlo a 34 caracteres
            $desc_linea = mb_substr($nombre_art, 0, 34, 'UTF-8');
            $out .= sprintf("%-7s %-34s\n", $cant_formateada, $desc_linea);

            // Si tiene notas u observaciones (ej: "Sin cebolla", "Bien frias")
            if (!empty($item['notas'])) {
                $out .= $BOLD_OFF . "   * NOTA: " . trim($item['notas']) . "\n" . $BOLD_ON;
            }
        }
        $out .= $BOLD_OFF;
        $out .= $sep . "\n";

        // 5. Pie de ticket y corte de papel
        $out .= $ALIGN_CENTER . "Documento Sin Credito Fiscal\n\n";
        $out .= $FEED_CUT;

        return $out;
    }
}

if (!function_exists('enviar_bytes_impresora')) {
    /**
     * Envía bytes directamente a la impresora:
     *  Intento 1: Socket TCP directo al puerto 9100 (instantáneo, sin cola de Windows).
     *  Intento 2: Fallback a la cola de impresión de Windows.
     */
    function enviar_bytes_impresora($raw_data, $ip, $port = 9100, $windows_printer = 'cocina') {
        $resultado = [
            'success' => false,
            'metodo'  => null,
            'detalle' => ''
        ];

        // 1. Intento por Socket TCP directo a la impresora física en red (RAW 9100)
        $ip_win = detectar_ip_impresora_windows($windows_printer);
        $ips_candidatas = array_filter(array_unique([
            $ip_win,
            $ip,
            '192.168.0.150',
            '192.168.1.150',
            '192.168.1.100'
        ]));

        $t0 = microtime(true);
        foreach ($ips_candidatas as $ip_try) {
            $fp = @fsockopen($ip_try, (int)$port, $errno, $errstr, 0.8);
            if ($fp) {
                @stream_set_timeout($fp, 3);
                @fwrite($fp, $raw_data);
                @fflush($fp);
                @fclose($fp);
                $duracion = round((microtime(true) - $t0) * 1000, 1);
                $resultado['success'] = true;
                $resultado['metodo'] = "socket_tcp ($ip_try:$port, {$duracion}ms)";
                $resultado['detalle'] = "Enviado con éxito directamente por socket TCP";
                return $resultado;
            }
        }

        // 2. Fallback a cola de Windows si el socket directo no respondió
        if (!empty($windows_printer) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $temp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ticket_' . uniqid() . '.prn';
            file_put_contents($temp_file, $raw_data);

            $cmd = 'powershell -NoProfile -Command "Get-Content -Path ' . escapeshellarg($temp_file) . ' -Raw | Out-Printer -Name ' . escapeshellarg($windows_printer) . '"';
            @exec($cmd, $out, $ret);
            @unlink($temp_file);

            if ($ret === 0) {
                $resultado['success'] = true;
                $resultado['metodo'] = "windows_spooler ($windows_printer)";
                $resultado['detalle'] = "Enviado mediante cola de impresión de Windows ($windows_printer)";
                return $resultado;
            }
        }

        $resultado['detalle'] = "Fallo socket ($ip:$port - $errstr) y spooler de Windows";
        return $resultado;
    }
}

if (!function_exists('imprimir_comandas_pedido')) {
    /**
     * Procesa la lista de ítems de una comanda, clasifica por formato (COCINA vs BARRA),
     * totaliza cantidades idénticas y envía los tickets de forma directa e independiente.
     */
    function imprimir_comandas_pedido($mesa_doc, $consecutivo, $vendedor_nombre, array $items_nuevos, $bd_conexion = 'cliente') {
        try {
            if (empty($items_nuevos)) {
                return [
                    'success' => false,
                    'mensaje' => 'No hay ítems para imprimir en comanda'
                ];
            }

            $config_imp = obtener_config_impresoras();
            $conn = obtener_conexion_impresion();

            // 1. Obtener los formatos de destino (COCINA o BARRA) consultando articulo + grupos
            $formatos_por_codigo = [];
            if ($conn) {
                $codigos = array_unique(array_map(function($it) use ($conn) {
                    return "'" . $conn->real_escape_string(trim($it['codigo'])) . "'";
                }, $items_nuevos));

                if (!empty($codigos)) {
                    $sql_formatos = "
                        SELECT a.codigo, IFNULL(NULLIF(TRIM(g.formato), ''), 'COCINA') as formato
                        FROM articulo a
                        LEFT JOIN grupos g ON g.codigo = a.grupo
                        WHERE a.codigo IN (" . implode(',', $codigos) . ")
                    ";
                    $res_f = @$conn->query($sql_formatos);
                    if ($res_f) {
                        while ($rf = $res_f->fetch_assoc()) {
                            $formatos_por_codigo[trim($rf['codigo'])] = strtoupper(trim($rf['formato']));
                        }
                    }
                }
            }

            // 2. Separar los ítems por destino y consolidar/totalizar ítems idénticos
            $grupos_tickets = [
                'COCINA' => [],
                'BARRA'  => []
            ];

            foreach ($items_nuevos as $item) {
                $cod = trim($item['codigo']);
                $destino = $formatos_por_codigo[$cod] ?? 'COCINA';
                if ($destino !== 'BARRA') {
                    $destino = 'COCINA';
                }

                // Agrupar por clave única (código + notas) para sumar cantidades
                $clave = $cod . '|' . trim($item['notas'] ?? '');
                if (!isset($grupos_tickets[$destino][$clave])) {
                    $grupos_tickets[$destino][$clave] = [
                        'codigo'   => $cod,
                        'nombre'   => $item['nombre'],
                        'cantidad' => (float)$item['cantidad'],
                        'notas'    => trim($item['notas'] ?? '')
                    ];
                } else {
                    $grupos_tickets[$destino][$clave]['cantidad'] += (float)$item['cantidad'];
                }
            }

            $tickets_enviados = [];
            $mesa_limpia = ltrim(trim($mesa_doc), 'E');

            // 3. Imprimir ticket de COCINA si tiene ítems
            if (!empty($grupos_tickets['COCINA'])) {
                $items_cocina = array_values($grupos_tickets['COCINA']);
                $raw_cocina = formatear_ticket_comanda_espos('COCINA', $consecutivo, $mesa_limpia, $vendedor_nombre, $items_cocina);
                $env_cocina = enviar_bytes_impresora($raw_cocina, $config_imp['cocina_ip'], $config_imp['cocina_port'], $config_imp['windows_printer']);
                $tickets_enviados[] = [
                    'tipo'      => 'COCINA',
                    'items'     => count($items_cocina),
                    'resultado' => $env_cocina
                ];
            }

            // 4. Imprimir ticket de BARRA si tiene ítems
            if (!empty($grupos_tickets['BARRA'])) {
                $items_barra = array_values($grupos_tickets['BARRA']);
                $raw_barra = formatear_ticket_comanda_espos('BARRA', $consecutivo, $mesa_limpia, $vendedor_nombre, $items_barra);
                $env_barra = enviar_bytes_impresora($raw_barra, $config_imp['barra_ip'], $config_imp['barra_port'], $config_imp['windows_printer']);
                $tickets_enviados[] = [
                    'tipo'      => 'BARRA',
                    'items'     => count($items_barra),
                    'resultado' => $env_barra
                ];
            }

            // 5. Actualizar comandasmv.impresion = 2 para los ítems de este pedido
            if ($conn) {
                $mesa_esc = $conn->real_escape_string($mesa_doc);
                $pid_esc  = $conn->real_escape_string($consecutivo);
                @$conn->query("UPDATE comandasmv SET impresion = 2 WHERE documento = '{$mesa_esc}' AND (pid = '{$pid_esc}' OR impresion = 1)");
            }

            return [
                'success'          => true,
                'consecutivo'      => $consecutivo,
                'tickets_enviados' => $tickets_enviados
            ];
        } catch (\Throwable $e) {
            // Log de error de contingencia para diagnóstico
            @file_put_contents(
                dirname(__DIR__) . '/scratch/impresion_comandas.log',
                "[" . date('Y-m-d H:i:s') . "] Error en imprimir_comandas_pedido: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
}
