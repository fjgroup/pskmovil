<?php
/**
 * Genera un reporte PDF con el total recibido por cada método de pago
 * en los últimos 7 días desde la BD de Premium Soft (adminbahiap).
 */

declare(strict_types=1);

date_default_timezone_set('America/Caracas');

function conectarBahiap(): array
{
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'root';
    $passwords = ['1234', 'root'];

    $pdo = null;
    $lastError = '';
    $entorno = 'desconocido';

    foreach ($passwords as $idx => $pw) {
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pw, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $entorno = ($pw === '1234') ? 'produccion' : 'desarrollo';
            break;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
        }
    }

    if (!$pdo) {
        fwrite(STDERR, "ERROR: No se pudo conectar a MySQL ({$host}:{$port}) con usuario '{$user}'.\nDetalle: {$lastError}\n");
        exit(1);
    }

    $bdPos = 'adminbahiap';
    $nombreEmpresa = 'INVERSIONES BAHIA PLAZA, C.A';
    $codEmpresa = 'BAHIAP';

    try {
        $stmt = $pdo->query("SELECT valor FROM mijar26273_sistema.z_config_sistema WHERE clave = 'config_completa' LIMIT 1");
        $row = $stmt ? $stmt->fetch() : false;
        if ($row && !empty($row['valor'])) {
            $cfg = json_decode($row['valor'], true);
            if (is_array($cfg)) {
                $bdPos = $cfg['bd_pos'] ?? ($cfg['bd'] ?? $bdPos);
                $nombreEmpresa = $cfg['nombre_empresa'] ?? $nombreEmpresa;
                $codEmpresa = $cfg['cod_empresa'] ?? $codEmpresa;
            }
        }
    } catch (PDOException $e) {
        // Si no existe mijar26273_sistema, continuar con adminbahiap por defecto
    }

    $pdo->exec("USE `{$bdPos}`");

    return [
        'pdo' => $pdo,
        'bd' => $bdPos,
        'empresa' => $nombreEmpresa,
        'cod_empresa' => $codEmpresa,
        'entorno' => $entorno,
    ];
}

function obtenerRangoFechas(PDO $pdo, int $dias, ?string $hastaManual): array
{
    $hasta = $hastaManual ?: date('Y-m-d');
    $desde = date('Y-m-d', strtotime($hasta . ' -' . ($dias - 1) . ' days'));
    $esFallback = false;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM operclit c
        WHERE c.fecha BETWEEN :desde AND :hasta
          AND c.estatusrec <> '3'
    ");
    $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
    $cnt = (int)$stmt->fetchColumn();

    if ($cnt === 0 && $hastaManual === null) {
        $maxFecha = $pdo->query("SELECT MAX(fecha) FROM operclit WHERE estatusrec <> '3'")->fetchColumn();
        if ($maxFecha) {
            $hasta = $maxFecha;
            $desde = date('Y-m-d', strtotime($hasta . ' -' . ($dias - 1) . ' days'));
            $esFallback = true;
        }
    }

    return [
        'desde' => $desde,
        'hasta' => $hasta,
        'es_fallback' => $esFallback,
    ];
}

function consultarResumenMetodos(PDO $pdo, string $desde, string $hasta): array
{
    $sql = "
        SELECT
            COALESCE(NULLIF(TRIM(e.nomforma), ''), UPPER(e.codigo)) AS metodo,
            e.codigo,
            e.tipopago,
            COALESCE(NULLIF(TRIM(e.codigoorigen), ''), NULLIF(TRIM(e.codigodestino), ''), '-') AS cod_detalle,
            COUNT(*) AS operaciones,
            COUNT(DISTINCT c.documento) AS facturas,
            SUM(e.monto) AS total_monto
        FROM operclit c
        INNER JOIN operclit_ext e
            ON e.id_empresa = c.id_empresa
           AND e.agencia = c.agencia
           AND e.recibo = c.recibo
        WHERE c.fecha BETWEEN :desde AND :hasta
          AND c.estatusrec <> '3'
        GROUP BY metodo, e.codigo, e.tipopago, cod_detalle
        ORDER BY total_monto DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
    return $stmt->fetchAll();
}

function consultarDesgloseDiario(PDO $pdo, string $desde, string $hasta): array
{
    $sql = "
        SELECT
            c.fecha,
            COALESCE(NULLIF(TRIM(e.nomforma), ''), UPPER(e.codigo)) AS metodo,
            COUNT(*) AS operaciones,
            SUM(e.monto) AS total_monto
        FROM operclit c
        INNER JOIN operclit_ext e
            ON e.id_empresa = c.id_empresa
           AND e.agencia = c.agencia
           AND e.recibo = c.recibo
        WHERE c.fecha BETWEEN :desde AND :hasta
          AND c.estatusrec <> '3'
        GROUP BY c.fecha, metodo
        ORDER BY c.fecha DESC, total_monto DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
    return $stmt->fetchAll();
}

function etiquetaTipo(string $codigo): string
{
    return match (strtolower($codigo)) {
        'efe' => 'Efectivo',
        'tdb' => 'Tarjeta Débito',
        'tcr' => 'Tarjeta Crédito / Cashea',
        'trs' => 'Transferencia / Banco',
        'chq' => 'Cheque / Banco',
        default => strtoupper($codigo),
    };
}

function fmtMonto(float $monto): string
{
    return number_format($monto, 2, ',', '.');
}

function construirHtml(array $ctx, array $rango, array $resumen, array $diario): string
{
    $totalGeneral = array_sum(array_column($resumen, 'total_monto'));
    $totalOps = array_sum(array_column($resumen, 'operaciones'));
    $desdeFmt = date('d/m/Y', strtotime($rango['desde']));
    $hastaFmt = date('d/m/Y', strtotime($rango['hasta']));
    $generado = date('d/m/Y h:i A');

    $avisoFallback = '';
    if ($rango['es_fallback']) {
        $avisoFallback = '<div class="aviso">No hubo movimientos en los últimos 7 días calendario hasta hoy; se muestran los últimos 7 días con actividad registrada.</div>';
    }

    $filasResumen = '';
    foreach ($resumen as $r) {
        $mto = (float)$r['total_monto'];
        $pct = $totalGeneral > 0 ? ($mto / $totalGeneral) * 100 : 0;
        $metodo = htmlspecialchars((string)$r['metodo'], ENT_QUOTES, 'UTF-8');
        $grupo = htmlspecialchars(etiquetaTipo((string)$r['codigo']), ENT_QUOTES, 'UTF-8');
        $cod = htmlspecialchars((string)$r['cod_detalle'], ENT_QUOTES, 'UTF-8');
        $ops = (int)$r['operaciones'];
        $facs = (int)$r['facturas'];

        $filasResumen .= "
            <tr>
                <td><strong>{$metodo}</strong></td>
                <td>{$grupo}</td>
                <td class=\"center\">{$cod}</td>
                <td class=\"num\">{$ops}</td>
                <td class=\"num\">{$facs}</td>
                <td class=\"num\">" . number_format($pct, 1, ',', '.') . "%</td>
                <td class=\"num\"><strong>" . fmtMonto($mto) . "</strong></td>
            </tr>";
    }

    if ($filasResumen === '') {
        $filasResumen = '<tr><td colspan="7" class="center">Sin movimientos registrados en el período.</td></tr>';
    }

    $filasDiarias = '';
    $fechaActual = '';
    foreach ($diario as $d) {
        $f = date('d/m/Y', strtotime($d['fecha']));
        $fMostrar = ($f !== $fechaActual) ? "<strong>{$f}</strong>" : '';
        $fechaActual = $f;
        $metodo = htmlspecialchars((string)$d['metodo'], ENT_QUOTES, 'UTF-8');
        $ops = (int)$d['operaciones'];
        $mto = fmtMonto((float)$d['total_monto']);
        $filasDiarias .= "
            <tr>
                <td>{$fMostrar}</td>
                <td>{$metodo}</td>
                <td class=\"num\">{$ops}</td>
                <td class=\"num\">{$mto}</td>
            </tr>";
    }

    $empresa = htmlspecialchars($ctx['empresa'], ENT_QUOTES, 'UTF-8');
    $bd = htmlspecialchars($ctx['bd'], ENT_QUOTES, 'UTF-8');
    $entorno = htmlspecialchars($ctx['entorno'], ENT_QUOTES, 'UTF-8');
    $totalGeneralFmt = fmtMonto($totalGeneral);

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Pagos - Últimos 7 días</title>
<style>
    @page { size: letter; margin: 14mm 14mm; }
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; }
    .header { border-bottom: 2px solid #0f4c81; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
    .header h1 { margin: 0 0 4px 0; font-size: 18px; color: #0f4c81; }
    .header .sub { font-size: 12px; color: #475569; }
    .meta { text-align: right; font-size: 11px; color: #64748b; }
    .aviso { background: #fef3c7; border-left: 4px solid #d97706; color: #92400e; padding: 8px 10px; margin-bottom: 14px; font-size: 11px; }
    .kpi-box { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; }
    .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; }
    .kpi-val { font-size: 18px; font-weight: bold; color: #0f4c81; }
    h2 { font-size: 14px; color: #0f4c81; margin: 16px 0 8px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th { background: #0f4c81; color: #ffffff; text-align: left; padding: 7px 8px; font-size: 11px; }
    td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; font-size: 11.5px; }
    tr:nth-child(even) td { background: #f8fafc; }
    tfoot td { background: #e2e8f0 !important; font-weight: bold; border-top: 2px solid #94a3b8; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .center { text-align: center; }
    .footer { margin-top: 18px; font-size: 10px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{$empresa}</h1>
            <div class="sub">Ingresos por Método de Pago &bull; Período: <strong>{$desdeFmt} al {$hastaFmt}</strong></div>
        </div>
        <div class="meta">
            Generado: {$generado}<br>
            BD: {$bd} ({$entorno})
        </div>
    </div>

    {$avisoFallback}

    <div class="kpi-box">
        <div>
            <div class="kpi-label">Total Recibido (7 días)</div>
            <div class="kpi-val">{$totalGeneralFmt}</div>
        </div>
        <div style="text-align:right;">
            <div class="kpi-label">Total Operaciones</div>
            <div class="kpi-val">{$totalOps}</div>
        </div>
    </div>

    <h2>Resumen por Método de Pago</h2>
    <table>
        <thead>
            <tr>
                <th>Método de Pago</th>
                <th>Grupo</th>
                <th class="center">Cód.</th>
                <th class="num">Operaciones</th>
                <th class="num">Facturas</th>
                <th class="num">%</th>
                <th class="num">Monto Total</th>
            </tr>
        </thead>
        <tbody>
            {$filasResumen}
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">TOTAL GENERAL</td>
                <td class="num">{$totalOps}</td>
                <td></td>
                <td class="num">100,0%</td>
                <td class="num">{$totalGeneralFmt}</td>
            </tr>
        </tfoot>
    </table>

    <h2>Desglose por Día</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Método de Pago</th>
                <th class="num">Operaciones</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            {$filasDiarias}
        </tbody>
    </table>

    <div class="footer">
        Fuente: operclit + operclit_ext (excluye recibos anulados estatusrec = '3')
    </div>
</body>
</html>
HTML;
}

function encontrarNavegadorPdf(): ?string
{
    $rutas = [
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
    ];
    foreach ($rutas as $r) {
        if (file_exists($r)) {
            return $r;
        }
    }
    return null;
}

// --- Ejecución principal ---
$dias = 7;
$hastaManual = null;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--dias=(\d+)$/', $arg, $m)) {
        $dias = max(1, (int)$m[1]);
    } elseif (preg_match('/^--hasta=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
        $hastaManual = $m[1];
    }
}

$ctx = conectarBahiap();
$rango = obtenerRangoFechas($ctx['pdo'], $dias, $hastaManual);
$resumen = consultarResumenMetodos($ctx['pdo'], $rango['desde'], $rango['hasta']);
$diario = consultarDesgloseDiario($ctx['pdo'], $rango['desde'], $rango['hasta']);

$dirSalida = __DIR__;
if (!is_dir($dirSalida)) {
    mkdir($dirSalida, 0777, true);
}

$stamp = date('Ymd_His');
$htmlPath = $dirSalida . DIRECTORY_SEPARATOR . "reporte_pagos_7dias_{$stamp}.html";
$pdfPath = $dirSalida . DIRECTORY_SEPARATOR . "reporte_pagos_7dias_{$stamp}.pdf";

file_put_contents($htmlPath, construirHtml($ctx, $rango, $resumen, $diario));

$browser = encontrarNavegadorPdf();
if (!$browser) {
    fwrite(STDERR, "ERROR: No se encontró Chrome ni Edge para generar el PDF.\nHTML guardado en: {$htmlPath}\n");
    exit(1);
}

$fileUrl = 'file:///' . str_replace('\\', '/', $htmlPath);
$cmd = '"' . $browser . '" --headless --disable-gpu --no-pdf-header-footer --print-to-pdf="' . $pdfPath . '" "' . $fileUrl . '"';
exec($cmd . ' 2>&1', $out, $code);

if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
    fwrite(STDERR, "ERROR al generar PDF (código {$code}):\n" . implode("\n", $out) . "\n");
    exit(1);
}

@unlink($htmlPath);

echo "PDF_GENERADO={$pdfPath}\n";
echo "PERIODO={$rango['desde']}..{$rango['hasta']}\n";
echo "ENTORNO={$ctx['entorno']}\n";

