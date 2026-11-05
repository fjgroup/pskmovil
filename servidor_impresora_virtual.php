<?php
/**
 * Servidor de Impresora Termica Virtual para Comandas Moviles y Premium Soft
 * Escucha en el puerto TCP 9100 (RAW ESC/POS).
 * Permite realizar pruebas completas sin tener una impresora fisica conectada.
 */

$port = 9100;
$address = '0.0.0.0';

$log_dir = __DIR__ . '/storage';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}
$log_file = $log_dir . '/tickets_virtuales.txt';
$last_ticket_file = $log_dir . '/ultimo_ticket.txt';

$server = @stream_socket_server("tcp://{$address}:{$port}", $errno, $errstr);
if (!$server) {
    echo "[ERROR] No se pudo iniciar la impresora virtual en {$address}:{$port} - {$errstr} ({$errno})\n";
    exit(1);
}

echo "========================================================\n";
echo "   IMPRESORA TERMICA VIRTUAL (Puerto {$port})\n";
echo "========================================================\n";
echo " Escuchando en tcp://{$address}:{$port}...\n";
echo " Los tickets se guardan en: storage/tickets_virtuales.txt\n";
echo " Presione Ctrl + C para detener.\n";
echo "========================================================\n\n";

while ($client = @stream_socket_accept($server, -1)) {
    $raw = '';
    while (!feof($client)) {
        $chunk = fread($client, 4096);
        if ($chunk === false || $chunk === '') break;
        $raw .= $chunk;
    }
    @fclose($client);

    if (empty($raw)) continue;

    // Decodificar ESC/POS a texto limpio legible
    $clean = $raw;
    // Remover cortes ESC/POS: ESC d <n> GS V <m>, etc.
    $clean = preg_replace('/\x1b@/s', '', $clean);
    $clean = preg_replace('/\x1ba\x01/s', '', $clean);
    $clean = preg_replace('/\x1ba\x00/s', '', $clean);
    $clean = preg_replace('/\x1bE\x01/s', '', $clean);
    $clean = preg_replace('/\x1bE\x00/s', '', $clean);
    $clean = preg_replace('/\x1b!\x20/s', '', $clean);
    $clean = preg_replace('/\x1b!\x00/s', '', $clean);
    $clean = preg_replace('/\x1bd[\x00-\x09]/s', '', $clean);
    $clean = preg_replace('/\x1dV[\x00-\x02]/s', '', $clean);
    // Remover otros bytes de control binarios
    $clean = preg_replace('/[\x00-\x09\x0b\x0c\x0e-\x1f\x7f]/s', '', $clean);

    // Normalizar líneas sobreimpresas con \r de FoxPro (ej. "<mesa>\r   Mesa:" -> "   Mesa:      <mesa>")
    $clean = preg_replace('/^[ \t]*([^\r\n]+)\r[ \t]*(Mesa:)[ \t]*/m', '   $2      $1', $clean);
    // Convertir retornos de carro residuales a espacio
    $clean = str_replace("\r", " ", $clean);
    // Limpiar espacios sobrantes al final de cada línea
    $clean = preg_replace('/[ \t]+$/m', '', $clean);

    // Enriquecer la mesa con su descripción de idconsumos (ej. "j02" -> "j02 - JARDIN 02")
    if (preg_match('/Mesa:\s+([a-zA-Z0-9]+)/i', $clean, $mMesa)) {
        $cod_m = strtolower(trim($mMesa[1]));
        try {
            $pdo_m = new PDO('mysql:host=127.0.0.1;port=3306;dbname=adminbahiap', 'root', 'root');
            $stm_m = $pdo_m->prepare("SELECT descripcion FROM idconsumos WHERE id_consumo = ? OR nombre_corto = ? LIMIT 1");
            $stm_m->execute([$cod_m, $cod_m]);
            if ($row_m = $stm_m->fetch(PDO::FETCH_ASSOC)) {
                $desc_m = trim($row_m['descripcion']);
                if (!empty($desc_m) && strtoupper($desc_m) !== strtoupper($cod_m)) {
                    $clean = preg_replace('/(Mesa:\s+[a-zA-Z0-9]+)(?:\s*-\s*)?/i', "$1 - {$desc_m}", $clean, 1);
                }
            }
        } catch (\Throwable $e) {}
    }

    $clean = trim($clean);

    $fecha_actual = date('Y-m-d H:i:s');
    $separador = str_repeat('=', 46);
    $banner = "\n{$separador}\n[TICKET RECIBIDO] - {$fecha_actual}\n{$separador}\n";

    echo $banner . $clean . "\n\n";

    @file_put_contents($log_file, $banner . $clean . "\n", FILE_APPEND);
    @file_put_contents($last_ticket_file, $clean);

    // Mantener únicamente los últimos 4 tickets en el historial
    $content = @file_get_contents($log_file);
    if ($content && preg_match_all('/={30,}\r?\n\[TICKET[^=]+={30,}\r?\n.*?(?=(?:={30,}\r?\n\[TICKET)|\z)/s', $content, $m)) {
        if (count($m[0]) > 4) {
            @file_put_contents($log_file, trim(implode("\n\n", array_slice($m[0], -4))) . "\n");
        }
    }
}

@fclose($server);

