<?php
date_default_timezone_set('America/Caracas');
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
        $port = (int)($cfg['port'] ?? 3306);

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

if (!function_exists('detectar_info_impresora_windows')) {
    /**
     * Detecta dinámicamente la impresora térmica instalada en Windows y su IP real de red.
     * Busca candidatos prioritarios (POS-80C, POS-80, cocina, etc.) y lee la IP real desde
     * el monitor de puertos Standard TCP/IP (HostName), evitando confusiones con el nombre del puerto.
     */
    /**
     * Obtiene el puerto e IP de la impresora térmica única (POS-80C) configurada en Windows.
     */
    function detectar_info_impresora_windows($nombre_impresora = null) {
        static $cache = [];
        $nombre = $nombre_impresora ?: 'POS-80C';
        if (isset($cache[$nombre])) return $cache[$nombre];
        $info = ['nombre' => $nombre, 'puerto' => null, 'ip' => null];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $salida = [];
            $regKey = 'HKLM\\SYSTEM\\CurrentControlSet\\Control\\Print\\Printers\\' . $nombre;
            @exec("reg query \"{$regKey}\" /v \"Port\" 2>nul", $salida);
            foreach ($salida as $l) {
                if (preg_match('/Port\s+REG_SZ\s+(.+)$/i', trim($l), $m)) {
                    $info['puerto'] = trim($m[1]);
                    break;
                }
            }
            if ($info['puerto']) {
                $salida_host = [];
                $portKey = 'HKLM\\SYSTEM\\CurrentControlSet\\Control\\Print\\Monitors\\Standard TCP/IP Port\\Ports\\' . $info['puerto'];
                @exec("reg query \"{$portKey}\" /v \"HostName\" 2>nul", $salida_host);
                foreach ($salida_host as $lh) {
                    if (preg_match('/HostName\s+REG_SZ\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i', $lh, $mh)) {
                        $info['ip'] = $mh[1];
                        break;
                    }
                }
                if (!$info['ip'] && preg_match('/(?:IP_)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i', $info['puerto'], $mpu)) {
                    $info['ip'] = $mpu[1];
                }
            }
        }
        $cache[$nombre] = $info;
        return $info;
    }
}

    function detectar_ip_impresora_windows($nombre_impresora = null) {
        $info = detectar_info_impresora_windows($nombre_impresora);
        return $info['ip'] ?? null;
    }

if (!function_exists('validar_conexion_impresora')) {
    /**
     * Valida si la impresora configurada en Windows está encendida y accesible por red.
     */
    function validar_conexion_impresora($nombre_impresora = null, $port = 9100) {
        $info = detectar_info_impresora_windows($nombre_impresora);
        $nombre_real = $info['nombre'] ?: ($nombre_impresora ?: 'Impresora');
        $ip = $info['ip'];

        if (!$ip) {
            return [
                'success' => false,
                'ip'      => null,
                'mensaje' => "No se detectó un puerto IP asignado a la impresora '$nombre_real' en Windows."
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
                'nombre'  => $nombre_real,
                'ms'      => $ms,
                'mensaje' => "Impresora '$nombre_real' ($ip:$port) respondiendo y en línea ({$ms} ms)."
            ];
        }

        return [
            'success' => false,
            'ip'      => $ip,
            'port'    => $port,
            'nombre'  => $nombre_real,
            'error'   => $errstr,
            'mensaje' => "La impresora '$nombre_real' apunta a $ip en Windows, pero no responde. Verifique que esté encendida o que su IP coincida."
        ];
    }
}

if (!function_exists('obtener_config_impresoras')) {
    function obtener_config_impresoras() {
        $cfg = obtener_config_sistema();
        $entorno = $cfg['modo_entorno'] ?? 'desarrollo';

        $info_win = detectar_info_impresora_windows($cfg['impresora_windows'] ?? null);
        $windows_printer = $cfg['impresora_windows'] ?? ($info_win['nombre'] ?: 'POS-80C');
        $ip_windows = $info_win['ip'] ?? null;

        // La IP configurada en la impresora de Windows (POS-80C) siempre tiene la máxima prioridad
        $ip_defecto = $ip_windows ?: ($cfg['impresora_ip'] ?? '192.168.1.150');

        $cocina_ip = $ip_windows ?: ($cfg['impresora_cocina_ip'] ?? $ip_defecto);
        $cocina_port = (int)($cfg['impresora_cocina_port'] ?? 9100);

        $barra_ip = $ip_windows ?: ($cfg['impresora_barra_ip'] ?? $cocina_ip);
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
     * Utiliza tipografía ampliada (Doble Alto y Doble Ancho) para lectura clara
     * desde cualquier distancia en cocina y barra.
     */
    function formatear_ticket_comanda_espos($tipo, $nro_ticket, $mesa, $vendedor, array $items_consolidados, $fecha = null, $cliente = null) {
        if (!$fecha) {
            $fecha = date('d/m/Y h:i:s A');
        }

        // Ancho estándar de línea en ticketera 80 mm = 42 caracteres (Font A)
        $ancho = 42;
        $sep = str_repeat('-', $ancho);

        // Bytes de control ESC/POS
        $ESC = "\x1b";
        $GS  = "\x1d";
        $INIT         = $ESC . "@";                     // Inicializar impresora
        $ALIGN_CENTER = $ESC . "a\x01";                  // Centrado
        $ALIGN_LEFT   = $ESC . "a\x00";                  // Alineado a la izquierda
        $BOLD_ON      = $ESC . "E\x01";                  // Negrita activada
        $BOLD_OFF     = $ESC . "E\x00";                  // Negrita desactivada

        // Tamaños de fuente con GS ! n
        $TXT_NORMAL   = $GS . "!\x00";                  // Tamaño estándar 1x1
        $TXT_2H       = $GS . "!\x01";                  // Doble Alto (ideal para platillos en 80 mm)
        $TXT_2W       = $GS . "!\x10";                  // Doble Ancho
        $TXT_2W2H     = $GS . "!\x11";                  // Doble Alto y Doble Ancho (Gigante)

        $FEED_CUT     = $ESC . "d\x04" . $GS . "V\x01"; // Avance 4 líneas y corte de papel

        $out = "";
        $out .= $INIT;

        // 1. Encabezado principal (COCINA o BARRA) en Doble Alto y Doble Ancho
        $out .= $ALIGN_CENTER . $BOLD_ON . $TXT_2W2H;
        $out .= "*** " . strtoupper(trim($tipo)) . " ***\n";
        $out .= $TXT_NORMAL . $BOLD_OFF;

        // 2. MESA EN TAMAÑO GIGANTE (Doble Alto + Doble Ancho)
        $out .= $ALIGN_CENTER . $BOLD_ON . $TXT_2W2H;
        $out .= "MESA: " . strtoupper(trim($mesa)) . "\n";
        $out .= $TXT_NORMAL . $BOLD_OFF;

        // 3. Metadatos de la orden (Fecha, Mesonero, Cliente) en tamaño normal
        $out .= $ALIGN_LEFT;
        $out .= "Fecha:    " . $fecha . "\n";
        $out .= "Mesonero: " . strtoupper(trim($vendedor)) . "\n";
        if (!empty($cliente) && strtoupper(trim($cliente)) !== 'CLIENTE CONTADO') {
            $out .= "Cliente:  " . strtoupper(trim($cliente)) . "\n";
        }
        $out .= $sep . "\n";

        // 4. Encabezado de columnas
        $out .= sprintf("%-6s %-35s\n", "CANT.", "DESCRIPCION");
        $out .= $sep . "\n";

        // 5. Detalle de productos en DOBLE ALTO y NEGRITA
        foreach ($items_consolidados as $item) {
            $cant_num = (float)$item['cantidad'];
            $cant_formateada = ($cant_num == (int)$cant_num) 
                ? (string)(int)$cant_num 
                : number_format($cant_num, 2, ',', '');
            $nombre_art = mb_strtoupper(trim($item['nombre']), 'UTF-8');

            $out .= $BOLD_ON . $TXT_2H;
            if (mb_strlen($nombre_art, 'UTF-8') <= 35) {
                $out .= sprintf("%-6s %-35s\n", $cant_formateada, $nombre_art);
            } else {
                $linea1 = mb_substr($nombre_art, 0, 35, 'UTF-8');
                $linea2 = mb_substr($nombre_art, 35, 35, 'UTF-8');
                $out .= sprintf("%-6s %-35s\n", $cant_formateada, $linea1);
                $out .= sprintf("%-6s %-35s\n", "", $linea2);
            }
            $out .= $TXT_NORMAL . $BOLD_OFF;

            // Si tiene notas u observaciones
            if (!empty($item['notas'])) {
                $out .= $BOLD_ON . "   >> NOTA: " . trim($item['notas']) . "\n" . $BOLD_OFF;
            }
        }

        $out .= $sep . "\n";

        // 6. Pie de ticket y corte de papel
        $out .= $ALIGN_CENTER . "Documento Sin Credito Fiscal\n";
        $out .= $FEED_CUT;

        return $out;
    }
}

if (!function_exists('registrar_ticket_en_archivo')) {
    /**
     * Guarda una copia en texto legible de cada comanda impresa en storage/tickets_virtuales.txt
     * Permite inspeccionar en cualquier momento lo que se envio a imprimir, incluso sin impresora fisica.
     */
    function registrar_ticket_en_archivo($tipo, $raw_ticket, $consecutivo = '') {
        try {
            $dir = dirname(__DIR__) . '/storage';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $clean = $raw_ticket;
            $clean = preg_replace('/\x1b@/s', '', $clean);
            $clean = preg_replace('/\x1ba[\x00-\x02]/s', '', $clean);
            $clean = preg_replace('/\x1bE[\x00-\x01]/s', '', $clean);
            $clean = preg_replace('/\x1b![\x00-\xFF]/s', '', $clean);
            $clean = preg_replace('/\x1d![\x00-\xFF]/s', '', $clean);
            $clean = preg_replace('/\x1bd[\x00-\x09]/s', '', $clean);
            $clean = preg_replace('/\x1dV[\x00-\x02]/s', '', $clean);
            $clean = preg_replace('/[\x00-\x09\x0b\x0c\x0e-\x1f\x7f]/s', '', $clean);
            $clean = preg_replace('/^[ \t]*([^\r\n]+)\r[ \t]*(Mesa:)[ \t]*/m', '   $2      $1', $clean);
            $clean = str_replace("\r", " ", $clean);
            $clean = preg_replace('/[ \t]+$/m', '', $clean);
            $clean = trim($clean);

            $fecha = date('d/m/Y h:i:s A');
            $sep = str_repeat('=', 44);
            $banner = "\n{$sep}\n[TICKET {$tipo}] - {$fecha} (#{$consecutivo})\n{$sep}\n";

            @file_put_contents($dir . '/tickets_virtuales.txt', $banner . $clean . "\n", FILE_APPEND);
            @file_put_contents($dir . '/ultimo_ticket.txt', $banner . $clean . "\n");

            // Mantener unicamente los ultimos 4 tickets en el historial
            $cHist = @file_get_contents($dir . '/tickets_virtuales.txt');
            if ($cHist && preg_match_all('/={30,}\r?\n\[TICKET[^=]+={30,}\r?\n.*?(?=(?:={30,}\r?\n\[TICKET)|\z)/s', $cHist, $m)) {
                if (count($m[0]) > 4) {
                    @file_put_contents($dir . '/tickets_virtuales.txt', trim(implode("\n\n", array_slice($m[0], -4))) . "\n");
                }
            }
        } catch (\Throwable $e) {}
    }
}

if (!function_exists('enviar_bytes_impresora')) {
    /**
     * Envía bytes directamente a la impresora:
     *  Intento 1: Socket TCP directo al puerto 9100 (instantáneo, sin intermediarios ni drivers GDI).
     *  Intento 2: Fallback RAW binario a la cola de Windows evitando Out-Printer/GDI.
     */
    function enviar_bytes_impresora($raw_data, $ip = null, $port = 9100, $windows_printer = 'POS-80C') {
        $info_win = detectar_info_impresora_windows($windows_printer);
        $ip_destino = $info_win['ip'] ?: ($ip ?: '192.168.1.150');
        $es_local = in_array($ip_destino, ['127.0.0.1', 'localhost', '::1'], true);
        $resultado = [
            'success'  => false,
            'metodo'   => null,
            'detalle'  => '',
            'ip'       => $ip_destino,
            'es_local' => $es_local
        ];
        // 1. Envío directo por Socket TCP
        $fp = @fsockopen($ip_destino, (int)$port, $errno, $errstr, 1.2);
        if ($fp) {
            @fwrite($fp, $raw_data);
            @fclose($fp);
            $resultado['success'] = true;
            $resultado['metodo'] = "socket_tcp ($ip_destino:$port)";
            return $resultado;
        }
        // 2. Fallback por Spooler de Windows en modo RAW (sin GDI ni Out-Printer)
        if (!$es_local && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $temp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ticket_' . uniqid() . '.prn';
            file_put_contents($temp_file, $raw_data);
            $cmd = 'cmd.exe /c copy /b ' . escapeshellarg($temp_file) . ' "\\\\127.0.0.1\\' . escapeshellarg($windows_printer) . '" >nul 2>nul';
            @exec($cmd, $out, $ret);
            if ($ret !== 0) {
                $cmd = 'cmd.exe /c copy /b ' . escapeshellarg($temp_file) . ' ' . escapeshellarg($windows_printer) . ' >nul 2>nul';
                @exec($cmd, $out, $ret);
            }
            @unlink($temp_file);
            if ($ret === 0) {
                $resultado['success'] = true;
                $resultado['metodo'] = "windows_spooler_raw ($windows_printer)";
                return $resultado;
            }
        }
        return $resultado;
    }
}

    function imprimir_comandas_pedido($mesa_doc, $consecutivo, $vendedor_nombre, array $items_nuevos, $bd_conexion = 'cliente', $cliente_nombre = '') {
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
            $mesa_etiqueta = $mesa_limpia;
            if ($conn) {
                $mesa_esc = $conn->real_escape_string($mesa_limpia);
                $res_m = @$conn->query("SELECT descripcion FROM idconsumos WHERE id_consumo = '{$mesa_esc}' OR nombre_corto = '{$mesa_esc}' LIMIT 1");
                if ($res_m && ($row_m = $res_m->fetch_assoc())) {
                    $desc = trim($row_m['descripcion']);
                    if (!empty($desc)) {
                        $mesa_etiqueta = $desc;
                    }
                }
            }

            // 3. Imprimir ticket de COCINA si tiene ítems
            if (!empty($grupos_tickets['COCINA'])) {
                $items_cocina = array_values($grupos_tickets['COCINA']);
                $raw_cocina = formatear_ticket_comanda_espos('COCINA', $consecutivo, $mesa_etiqueta, $vendedor_nombre, $items_cocina, null, $cliente_nombre);
                $env_cocina = enviar_bytes_impresora($raw_cocina, $config_imp['cocina_ip'], $config_imp['cocina_port'], $config_imp['windows_printer']);
                // Si el simulador local en 127.0.0.1:9100 ya recibió el ticket por socket, él mismo lo guarda en tickets_virtuales.txt
                if (empty($env_cocina['success']) || empty($env_cocina['es_local'])) {
                    registrar_ticket_en_archivo('COCINA', $raw_cocina, $consecutivo);
                }
                $tickets_enviados[] = [
                    'tipo'      => 'COCINA',
                    'items'     => count($items_cocina),
                    'resultado' => $env_cocina
                ];
            }

            // 4. Imprimir ticket de BARRA si tiene ítems
            if (!empty($grupos_tickets['BARRA'])) {
                $items_barra = array_values($grupos_tickets['BARRA']);
                $raw_barra = formatear_ticket_comanda_espos('BARRA', $consecutivo, $mesa_etiqueta, $vendedor_nombre, $items_barra, null, $cliente_nombre);
                $env_barra = enviar_bytes_impresora($raw_barra, $config_imp['barra_ip'], $config_imp['barra_port'], $config_imp['windows_printer']);
                if (empty($env_barra['success']) || empty($env_barra['es_local'])) {
                    registrar_ticket_en_archivo('BARRA', $raw_barra, $consecutivo);
                }
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
