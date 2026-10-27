<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json; charset=UTF-8");

function responder_json($datos, $httpCode = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($httpCode);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Función para localizar el ejecutable de Git en el sistema
function obtener_comando_git() {
    $salida = @shell_exec('git --version 2>&1');
    if ($salida && stripos($salida, 'git version') !== false) {
        return 'git';
    }

    $rutas = array(
        'C:\Program Files\Git\cmd\git.exe',
        'C:\Program Files\Git\bin\git.exe',
        'C:\Program Files (x86)\Git\cmd\git.exe',
        'C:\Users\Principal\AppData\Local\Programs\Git\cmd\git.exe'
    );
    
    $localAppData = getenv('LOCALAPPDATA');
    if ($localAppData) {
        $rutas[] = $localAppData . '\Programs\Git\cmd\git.exe';
    }

    foreach ($rutas as $ruta) {
        if (!empty($ruta) && file_exists($ruta)) {
            return '"' . $ruta . '"';
        }
    }

    return null;
}

$git = obtener_comando_git();
if (!$git) {
    responder_json(array(
        'status' => 'error',
        'mensaje' => 'Git no está instalado o no se encuentra en el PATH del sistema. Por favor instale Git para Windows.'
    ), 500);
}

$rootDir = realpath(__DIR__ . '/..');
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// 1. VERIFICAR ACTUALIZACIONES EN GITHUB
if ($accion == 'verificar') {
    // Fetch desde el repositorio remoto
    $cmdFetch = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* fetch origin main 2>&1";
    $salidaFetch = shell_exec($cmdFetch);

    // Obtener hashes locales y remotos
    $cmdLocal = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* rev-parse --short HEAD 2>&1";
    $localHash = trim(shell_exec($cmdLocal));

    $cmdRemote = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* rev-parse --short origin/main 2>&1";
    $remoteHash = trim(shell_exec($cmdRemote));

    $cmdLocalDetalle = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* log -1 --format=\"%cd - %s\" --date=short 2>&1";
    $localDetalle = trim(shell_exec($cmdLocalDetalle));

    if (empty($localHash) || empty($remoteHash)) {
        responder_json(array(
            'status' => 'error',
            'mensaje' => 'No se pudo conectar con el repositorio Git remoto. Verifique su conexión a Internet.',
            'debug' => $salidaFetch
        ), 500);
    }

    if ($localHash === $remoteHash) {
        responder_json(array(
            'status' => 'al_dia',
            'commit_actual' => $localHash,
            'detalle_actual' => $localDetalle,
            'mensaje' => 'El sistema está actualizado a la última versión disponible.'
        ));
    } else {
        // Hay actualizaciones pendientes
        $cmdCount = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* rev-list --count HEAD..origin/main 2>&1";
        $conteo = intval(trim(shell_exec($cmdCount)));

        $cmdLog = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* log HEAD..origin/main --format=\"%h|%s|%cr\" 2>&1";
        $commitsRaw = trim(shell_exec($cmdLog));
        $commits = array();
        if (!empty($commitsRaw)) {
            $lineas = explode("\n", $commitsRaw);
            foreach ($lineas as $l) {
                $partes = explode("|", trim($l));
                if (count($partes) >= 2) {
                    $commits[] = array(
                        'hash' => $partes[0],
                        'mensaje' => $partes[1],
                        'tiempo' => isset($partes[2]) ? $partes[2] : ''
                    );
                }
            }
        }

        $cmdFiles = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* diff --name-only HEAD origin/main 2>&1";
        $filesRaw = trim(shell_exec($cmdFiles));
        $archivos = array_filter(explode("\n", str_replace("\r", "", $filesRaw)));

        responder_json(array(
            'status' => 'actualizacion_disponible',
            'commit_local' => $localHash,
            'detalle_local' => $localDetalle,
            'commit_remoto' => $remoteHash,
            'num_commits' => $conteo > 0 ? $conteo : count($commits),
            'commits' => $commits,
            'archivos_modificados' => array_values($archivos)
        ));
    }
}

// 2. EJECUTAR ACTUALIZACIÓN
if ($accion == 'actualizar') {
    // Paso A: Respaldar configuración local y archivo .bat para no perderlos
    $conexionFile = $rootDir . '/datos_conexion.json';
    $batFile = $rootDir . '/iniciar_sistema.bat';
    $conexionBakFile = $rootDir . '/datos_conexion.local.bak';
    $batBakFile = $rootDir . '/iniciar_sistema.local.bak';

    $conexionBackup = file_exists($conexionFile) ? @file_get_contents($conexionFile) : null;
    $batBackup = file_exists($batFile) ? @file_get_contents($batFile) : null;

    // Respaldo de seguridad físico en disco
    if ($conexionBackup !== null) {
        @file_put_contents($conexionBakFile, $conexionBackup);
    }
    if ($batBackup !== null) {
        @file_put_contents($batBakFile, $batBackup);
    }

    // Paso B: Asegurar fetch y sincronizar exactamente con origin/main
    $cmdFetch = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* fetch origin main 2>&1";
    @shell_exec($cmdFetch);

    // Resetear a origin/main (garantiza cero conflictos de merge)
    $cmdReset = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* reset --hard origin/main 2>&1";
    $salidaReset = @shell_exec($cmdReset);

    // Paso C: Restaurar configuración local intacta (usando memoria o archivo de respaldo físico)
    if ($conexionBackup === null && file_exists($conexionBakFile)) {
        $conexionBackup = @file_get_contents($conexionBakFile);
    }
    if ($batBackup === null && file_exists($batBakFile)) {
        $batBackup = @file_get_contents($batBakFile);
    }

    if ($conexionBackup !== null) {
        $localCfg = json_decode($conexionBackup, true);
        $repoCfg = file_exists($conexionFile) ? json_decode(@file_get_contents($conexionFile), true) : null;
        if (is_array($localCfg) && is_array($repoCfg)) {
            // Preservar valores locales, pero agregando nuevas claves si el repo incorporó alguna
            $merged = array_merge($repoCfg, $localCfg);
            @file_put_contents($conexionFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            @file_put_contents($conexionFile, $conexionBackup);
        }
    }

    if ($batBackup !== null) {
        @file_put_contents($batFile, $batBackup);
    }

    // Obtener nuevo commit instalado
    $cmdNuevoHash = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* rev-parse --short HEAD 2>&1";
    $nuevoHash = trim((string)@shell_exec($cmdNuevoHash));

    $cmdNuevoDetalle = "cd /d \"{$rootDir}\" && {$git} -c safe.directory=* log -1 --format=\"%cd - %s\" --date=short 2>&1";
    $nuevoDetalle = trim((string)@shell_exec($cmdNuevoDetalle));

    responder_json(array(
        'status' => 'exito',
        'commit_instalado' => $nuevoHash,
        'detalle_instalado' => $nuevoDetalle,
        'mensaje' => 'Sistema actualizado con éxito a la versión ' . $nuevoHash . '. Su configuración local de base de datos e inicio fue preservada.',
        'debug' => $salidaReset
    ));
}

responder_json(array('status' => 'error', 'mensaje' => 'Acción no válida'), 400);
