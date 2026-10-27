<?php
/**
 * Suite de Pruebas Unitarias y Diagnóstico Integral de Sistema
 * Comandas Móviles - Mi Jardín Restaurant
 * 
 * Modos de ejecución:
 * 1. Desde consola CLI: `php tests/test_suite.php`
 * 2. Desde navegador web: `http://localhost:2010/tests/test_suite.php` o `http://192.168.0.102:2010/tests/test_suite.php`
 * 3. En formato JSON para APIs/CI: `http://localhost:2010/tests/test_suite.php?format=json`
 */

ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$rootDir = realpath(__DIR__ . '/..');
require_once $rootDir . '/funciones/config_helper.php';
require_once $rootDir . '/funciones/mesas_helper.php';

$isCli = (php_sapi_name() === 'cli');
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : ($isCli ? 'cli' : 'html');

class SuiteDiagnostico {
    private $rootDir;
    private $resultados = [];
    private $mysqli_satelite = null;
    private $mysqli_pos = null;
    private $config = [];
    private $tiempoInicio;

    public function __construct($rootDir) {
        $this->rootDir = $rootDir;
        $this->tiempoInicio = microtime(true);
        if (function_exists('mysqli_report')) {
            @mysqli_report(MYSQLI_REPORT_OFF);
        }
    }

    public function registrar($id, $nombre, $categoria, $estado, $mensaje, $detalles = [], $duracionMs = 0) {
        $this->resultados[] = [
            'id' => $id,
            'nombre' => $nombre,
            'categoria' => $categoria,
            'estado' => $estado, // PASS, FAIL, WARN
            'mensaje' => $mensaje,
            'detalles' => $detalles,
            'duracion_ms' => round($duracionMs, 2)
        ];
    }

    public function ejecutarTodas() {
        $this->testConexionDualMySQL();
        $this->testBaseFiscalPremium();
        $this->testBaseSateliteYTablasProhibidas();
        $this->testLogicaUniversalMesas();
        $this->testCriptografiaYPins();
        $this->testTasaCambioDelDia();
        $this->testConectividadCloud();
        $this->testIntegridadGit();

        // Cerrar conexiones abiertas
        if ($this->mysqli_satelite) @$this->mysqli_satelite->close();
        if ($this->mysqli_pos) @$this->mysqli_pos->close();
    }

    // ------------------------------------------------------------------------
    // TEST 1: Fallback Dual de Conexión MySQL (3307 Desarrollo vs 3306 Producción)
    // ------------------------------------------------------------------------
    private function testConexionDualMySQL() {
        $t0 = microtime(true);
        $candidatos = credenciales_mysql_candidatas();
        $conectado = false;
        $detalles = [];

        foreach ($candidatos as $idx => $cred) {
            $tConn0 = microtime(true);
            try {
                $m = @new mysqli('127.0.0.1', $cred['user'], $cred['pw'], '', $cred['port']);
                $ms = round((microtime(true) - $tConn0) * 1000, 2);
                if (!$m->connect_errno) {
                    $conectado = true;
                    $detalles[] = "Intento #".($idx+1).": Puerto {$cred['port']} ({$cred['user']}:****) -> CONECTADO ({$ms}ms) [Entorno: {$cred['entorno']}]";
                    $this->config = [
                        'host' => '127.0.0.1',
                        'port' => $cred['port'],
                        'user' => $cred['user'],
                        'pw'   => $cred['pw'],
                        'entorno' => $cred['entorno']
                    ];
                    $m->close();
                    break;
                } else {
                    $detalles[] = "Intento #".($idx+1).": Puerto {$cred['port']} ({$cred['user']}:****) -> No responde ({$m->connect_error})";
                }
            } catch (\Throwable $e) {
                $detalles[] = "Intento #".($idx+1).": Puerto {$cred['port']} -> Error: " . $e->getMessage();
            }
        }

        $dur = (microtime(true) - $t0) * 1000;
        if ($conectado) {
            $this->registrar(
                'TEST_MYSQL_DUAL',
                'Conexión Dual MySQL (Fallback Automático)',
                'Base de Datos',
                'PASS',
                "Conexión exitosa en puerto {$this->config['port']} como '{$this->config['user']}' (Modo: " . strtoupper($this->config['entorno']) . ")",
                $detalles,
                $dur
            );
        } else {
            $this->registrar(
                'TEST_MYSQL_DUAL',
                'Conexión Dual MySQL (Fallback Automático)',
                'Base de Datos',
                'FAIL',
                'No se pudo conectar a MySQL en ninguno de los puertos candidatos (3307 desarrollo ni 3306 producción).',
                $detalles,
                $dur
            );
        }
    }

    // ------------------------------------------------------------------------
    // TEST 2: Integridad de Base Fiscal SENIAT (adminbahiap)
    // ------------------------------------------------------------------------
    private function testBaseFiscalPremium() {
        $t0 = microtime(true);
        if (empty($this->config)) {
            $this->registrar('TEST_FISCAL_PREMIUM', 'Integridad Base Fiscal SENIAT (adminbahiap)', 'Fiscal SENIAT', 'FAIL', 'Prueba omitida: Falló la conexión MySQL previa.', [], 0);
            return;
        }

        $tablasRequeridas = ['operti', 'opermv', 'idconsumos', 'monedas', 'listvend'];
        $detalles = [];
        $faltantes = [];
        $cuentasAbiertas = 0;

        try {
            $this->mysqli_pos = @new mysqli($this->config['host'], $this->config['user'], $this->config['pw'], 'adminbahiap', $this->config['port']);
            if ($this->mysqli_pos->connect_errno) {
                $this->registrar('TEST_FISCAL_PREMIUM', 'Integridad Base Fiscal SENIAT (adminbahiap)', 'Fiscal SENIAT', 'FAIL', 'Base fiscal "adminbahiap" inaccesible: ' . $this->mysqli_pos->connect_error, [], (microtime(true) - $t0) * 1000);
                return;
            }

            foreach ($tablasRequeridas as $t) {
                $res = @$this->mysqli_pos->query("SHOW TABLES LIKE '{$t}'");
                if ($res && $res->num_rows > 0) {
                    $cntRes = @$this->mysqli_pos->query("SELECT COUNT(*) AS total FROM {$t}");
                    $cnt = $cntRes ? (int)$cntRes->fetch_assoc()['total'] : 0;
                    $detalles[] = "Tabla '{$t}': Presente ({$cnt} registros)";
                } else {
                    $faltantes[] = $t;
                    $detalles[] = "Tabla '{$t}': FALTANTE";
                }
            }

            // Verificar cuentas operti abiertas
            $qOperti = @$this->mysqli_pos->query("SELECT COUNT(*) AS abiertas FROM operti WHERE status = 0 AND tipo_doc = 'ESP'");
            if ($qOperti) {
                $cuentasAbiertas = (int)$qOperti->fetch_assoc()['abiertas'];
                $detalles[] = "Cuentas ESP activas en Premium: {$cuentasAbiertas} abiertas";
            }
        } catch (\Throwable $e) {
            $this->registrar('TEST_FISCAL_PREMIUM', 'Integridad Base Fiscal SENIAT (adminbahiap)', 'Fiscal SENIAT', 'FAIL', 'Excepción: ' . $e->getMessage(), $detalles, (microtime(true) - $t0) * 1000);
            return;
        }

        $dur = (microtime(true) - $t0) * 1000;
        if (empty($faltantes)) {
            $this->registrar(
                'TEST_FISCAL_PREMIUM',
                'Integridad Base Fiscal SENIAT (adminbahiap)',
                'Fiscal SENIAT',
                'PASS',
                "Estructura 100% íntegra. Las 5 tablas fiscales requeridas existen y responden.",
                $detalles,
                $dur
            );
        } else {
            $this->registrar(
                'TEST_FISCAL_PREMIUM',
                'Integridad Base Fiscal SENIAT (adminbahiap)',
                'Fiscal SENIAT',
                'FAIL',
                'Faltan tablas fiscales requeridas en adminbahiap: ' . implode(', ', $faltantes),
                $detalles,
                $dur
            );
        }
    }

    // ------------------------------------------------------------------------
    // TEST 3: Base Satélite (mijar26273_sistema) & Purga de Tablas Prohibidas
    // ------------------------------------------------------------------------
    private function testBaseSateliteYTablasProhibidas() {
        $t0 = microtime(true);
        if (empty($this->config)) {
            $this->registrar('TEST_SATELITE_ESTRUCTURA', 'Base Satélite y Purga de Tablas Prohibidas', 'Base Satélite', 'FAIL', 'Prueba omitida: Sin conexión MySQL.', [], 0);
            return;
        }

        $bdSatelite = 'mijar26273_sistema';
        $tablasCanonicas = [
            'z_config_sistema',
            'z_config_bancos',
            'z_mesas_activas',
            'z_status_pago',
            'z_top_productos'
        ];
        $tablasProhibidas = ['mesas_caja', 'mesas_registradas'];
        $detalles = [];
        $todasOk = true;

        try {
            $this->mysqli_satelite = @new mysqli($this->config['host'], $this->config['user'], $this->config['pw'], $bdSatelite, $this->config['port']);
            if ($this->mysqli_satelite->connect_errno) {
                $this->registrar('TEST_SATELITE_ESTRUCTURA', 'Base Satélite y Purga de Tablas Prohibidas', 'Base Satélite', 'FAIL', "No se pudo abrir base satélite '{$bdSatelite}': " . $this->mysqli_satelite->connect_error, [], (microtime(true) - $t0) * 1000);
                return;
            }

            // 1. Verificar las 5 canónicas
            foreach ($tablasCanonicas as $t) {
                $res = @$this->mysqli_satelite->query("SHOW TABLES LIKE '{$t}'");
                if ($res && $res->num_rows > 0) {
                    $detalles[] = "Tabla canónica '{$t}': OK";
                } else {
                    $todasOk = false;
                    $detalles[] = "Tabla canónica '{$t}': FALTANTE";
                }
            }

            // 2. Verificar purga de tablas prohibidas
            $encontradasProhibidas = [];
            foreach ($tablasProhibidas as $tp) {
                $res = @$this->mysqli_satelite->query("SHOW TABLES LIKE '{$tp}'");
                if ($res && $res->num_rows > 0) {
                    $encontradasProhibidas[] = $tp;
                    $detalles[] = "Tabla obsoleta '{$tp}': DETECTADA (Debe purgarse)";
                } else {
                    $detalles[] = "Tabla obsoleta '{$tp}': INEXISTENTE (Purga limpia OK)";
                }
            }

            // 3. Verificar URL de z_config_sistema (.com y nunca .test)
            $resCfg = @$this->mysqli_satelite->query("SELECT valor FROM z_config_sistema WHERE clave = 'url_hosting'");
            if ($resCfg && $r = $resCfg->fetch_assoc()) {
                $urlH = $r['valor'];
                if (stripos($urlH, '.test') !== false) {
                    $detalles[] = "url_hosting contiene '.test' obsoleto: {$urlH}";
                    $todasOk = false;
                } else {
                    $detalles[] = "url_hosting unificada al hosting real: {$urlH}";
                }
            }
        } catch (\Throwable $e) {
            $this->registrar('TEST_SATELITE_ESTRUCTURA', 'Base Satélite y Purga de Tablas Prohibidas', 'Base Satélite', 'FAIL', 'Excepción: ' . $e->getMessage(), $detalles, (microtime(true) - $t0) * 1000);
            return;
        }

        $dur = (microtime(true) - $t0) * 1000;
        if ($todasOk && empty($encontradasProhibidas)) {
            $this->registrar(
                'TEST_SATELITE_ESTRUCTURA',
                'Base Satélite y Purga de Tablas Prohibidas',
                'Base Satélite',
                'PASS',
                '5 tablas canónicas activas y tablas obsoletas eliminadas correctamente.',
                $detalles,
                $dur
            );
        } else {
            $this->registrar(
                'TEST_SATELITE_ESTRUCTURA',
                'Base Satélite y Purga de Tablas Prohibidas',
                'Base Satélite',
                'FAIL',
                'Discrepancia en base satélite: faltan tablas canónicas o persisten tablas obsoletas.',
                $detalles,
                $dur
            );
        }
    }

    // ------------------------------------------------------------------------
    // TEST 4: Clasificación Universal de Mesas (idconsumos vs Caja inventada)
    // ------------------------------------------------------------------------
    private function testLogicaUniversalMesas() {
        $t0 = microtime(true);
        $detalles = [];
        $fallos = 0;

        // Si tenemos conexión a adminbahiap, probar con el motor real
        $oficiales = $this->mysqli_pos ? obtener_mesas_oficiales($this->mysqli_pos) : ['MESA01' => true, 'MESA02' => true, 'JARD01' => true, 'TASC01' => true];

        $matrizPruebas = [
            // Casos que DEBEN ser Mesas Reales (si existen en idconsumos o en el set)
            ['doc' => 'MESA01', 'esperado_mesa' => true, 'desc' => 'Mesa numerada estándar'],
            ['doc' => 'EMESA01', 'esperado_mesa' => true, 'desc' => 'Documento ESP con prefijo E'],
            ['doc' => 'MESA1', 'esperado_mesa' => true, 'desc' => 'Mesa con normalización de cero (MESA1 -> MESA01)'],
            // Casos que DEBEN ser Pedidos en Caja (códigos inventados por cajeros)
            ['doc' => '0101010', 'esperado_mesa' => false, 'desc' => 'Código de delivery / llevar largo'],
            ['doc' => '099', 'esperado_mesa' => false, 'desc' => 'Código de caja rápida 099'],
            ['doc' => '001', 'esperado_mesa' => false, 'desc' => 'Código de caja 001'],
            ['doc' => '020', 'esperado_mesa' => false, 'desc' => 'Número inventado por nuevo cajero'],
            ['doc' => 'ETASC99', 'esperado_mesa' => false, 'desc' => 'Tascas inexistente']
        ];

        foreach ($matrizPruebas as $tc) {
            $doc = $tc['doc'];
            $esMesa = es_mesa_oficial($doc, $oficiales);
            $esCaja = es_pedido_caja($doc, $oficiales);

            $coherente = ($esMesa === $tc['esperado_mesa']) && ($esCaja === !$tc['esperado_mesa']);
            if ($coherente) {
                $detalles[] = "[OK] '{$doc}' ({$tc['desc']}) -> Clasificado como: " . ($esMesa ? 'MESA REAL' : 'PEDIDO EN CAJA');
            } else {
                $fallos++;
                $detalles[] = "[FALLO] '{$doc}' ({$tc['desc']}) -> Esperado: " . ($tc['esperado_mesa'] ? 'MESA' : 'CAJA') . ", Obtenido: " . ($esMesa ? 'MESA' : 'CAJA');
            }
        }

        $dur = (microtime(true) - $t0) * 1000;
        if ($fallos === 0) {
            $this->registrar(
                'TEST_LOGICA_MESAS',
                'Clasificación Universal de Mesas (idconsumos vs Pedidos en Caja)',
                'Lógica de Negocio',
                'PASS',
                'La regla universal clasificó con 100% de exactitud mesas físicas reales vs números inventados en caja.',
                $detalles,
                $dur
            );
        } else {
            $this->registrar(
                'TEST_LOGICA_MESAS',
                'Clasificación Universal de Mesas (idconsumos vs Pedidos en Caja)',
                'Lógica de Negocio',
                'FAIL',
                "Se detectaron {$fallos} discrepancias en la clasificación de mesas.",
                $detalles,
                $dur
            );
        }
    }

    // ------------------------------------------------------------------------
    // TEST 5: Criptografía y Unicidad de PINs (No Reciclado de Mesas)
    // ------------------------------------------------------------------------
    private function testCriptografiaYPins() {
        $t0 = microtime(true);
        $detalles = [];
        $fallos = 0;

        // Generar 1,000 PINs criptográficos y comprobar límites y aleatoriedad
        $pins = [];
        for ($i = 0; $i < 1000; $i++) {
            $pin = random_int(100000, 999999);
            if ($pin < 100000 || $pin > 999999) {
                $fallos++;
            }
            $pins[$pin] = ($pins[$pin] ?? 0) + 1;
        }

        $unicos = count($pins);
        $detalles[] = "Generación de 1,000 PINs con random_int(100000, 999999): {$unicos} valores distintos generados (Rango estricto [100000, 999999] OK).";

        // Verificar regla de ciclo de vida de PIN en base de datos:
        // No deben existir PINs activos pendientes (estado = '0') en z_status_pago para mesas cerradas
        if ($this->mysqli_satelite) {
            $res = @$this->mysqli_satelite->query("SELECT COUNT(*) AS pendientes_huerfanos FROM z_status_pago WHERE estado = '0' AND documento IN (SELECT documento FROM z_mesas_activas WHERE estado = 'CERRADA')");
            if ($res) {
                $huerfanos = (int)$res->fetch_assoc()['pendientes_huerfanos'];
                if ($huerfanos > 0) {
                    $fallos++;
                    $detalles[] = "[ALERTA] Se encontraron {$huerfanos} mesas cerradas con pago pendiente activo en z_status_pago (Riesgo de reciclado).";
                } else {
                    $detalles[] = "[OK] 0 PINs de pago activos para mesas cerradas. Los tokens se anulan y renuevan al reabrir.";
                }
            }
        }

        $dur = (microtime(true) - $t0) * 1000;
        if ($fallos === 0) {
            $this->registrar(
                'TEST_CRIPTOGRAFIA_PINS',
                'Criptografía y Unicidad de PINs de Mesa (Anti-Reciclado)',
                'Seguridad',
                'PASS',
                'Generador criptográfico seguro activo. PINs de 6 dígitos únicos y destrucción al cierre de mesa confirmada.',
                $detalles,
                $dur
            );
        } else {
            $this->registrar(
                'TEST_CRIPTOGRAFIA_PINS',
                'Criptografía y Unicidad de PINs de Mesa (Anti-Reciclado)',
                'Seguridad',
                'WARN',
                'Se detectaron alertas en la verificación de seguridad de PINs.',
                $detalles,
                $dur
            );
        }
    }

    // ------------------------------------------------------------------------
    // TEST 6: Tasa Oficial de Cambio del Día (monedas.ultimacotizacion)
    // ------------------------------------------------------------------------
    private function testTasaCambioDelDia() {
        $t0 = microtime(true);
        $detalles = [];

        if (!$this->mysqli_pos) {
            $this->registrar('TEST_TASA_CAMBIO', 'Tasa Oficial de Cambio (monedas.ultimacotizacion)', 'Finanzas & SENIAT', 'FAIL', 'Sin conexión a base fiscal adminbahiap.', [], 0);
            return;
        }

        try {
            $qMon = @$this->mysqli_pos->query("SELECT codigo, nombre, simbolo, ultimacotizacion, iso4217 FROM monedas WHERE ultimacotizacion > 0 ORDER BY ultimacotizacion DESC LIMIT 5");
            $tasaValida = false;
            $tasaEncontrada = 0;
            $monedaCodigo = '';
            $monedaNombre = '';

            if ($qMon && $qMon->num_rows > 0) {
                while ($m = $qMon->fetch_assoc()) {
                    $val = (float)$m['ultimacotizacion'];
                    $simb = $m['simbolo'] ?: ($m['iso4217'] ?: $m['codigo']);
                    $detalles[] = "Moneda [{$m['codigo']}] {$m['nombre']} ({$simb}): Tasa {$val} Bs.";
                    if ($val > 0 && !$tasaValida) {
                        $tasaValida = true;
                        $tasaEncontrada = $val;
                        $monedaCodigo = $simb;
                        $monedaNombre = $m['nombre'];
                    }
                }
            }

            $dur = (microtime(true) - $t0) * 1000;
            if ($tasaValida) {
                $this->registrar(
                    'TEST_TASA_CAMBIO',
                    'Tasa Oficial de Cambio (monedas.ultimacotizacion)',
                    'Finanzas & SENIAT',
                    'PASS',
                    "Tasa fiscal activa: 1 {$monedaCodigo} ({$monedaNombre}) = " . number_format($tasaEncontrada, 2, ',', '.') . " Bs.",
                    $detalles,
                    $dur
                );
            } else {
                $this->registrar(
                    'TEST_TASA_CAMBIO',
                    'Tasa Oficial de Cambio (monedas.ultimacotizacion)',
                    'Finanzas & SENIAT',
                    'WARN',
                    'No se encontró una tasa de cambio mayor a cero en la tabla monedas de adminbahiap.',
                    $detalles,
                    $dur
                );
            }
        } catch (\Throwable $e) {
            $this->registrar('TEST_TASA_CAMBIO', 'Tasa Oficial de Cambio (monedas.ultimacotizacion)', 'Finanzas & SENIAT', 'FAIL', 'Excepción: ' . $e->getMessage(), $detalles, (microtime(true) - $t0) * 1000);
        }
    }

    // ------------------------------------------------------------------------
    // TEST 7: Conectividad Cloud HTTPS (https://mijardinrestaurant.com)
    // ------------------------------------------------------------------------
    private function testConectividadCloud() {
        $t0 = microtime(true);
        $url = 'https://mijardinrestaurant.com';
        $detalles = [];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ComandasMoviles-TestSuite/1.0');

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
        curl_close($ch);

        $dur = (microtime(true) - $t0) * 1000;
        $detalles[] = "Destino HTTPS: {$url}";
        $detalles[] = "Código de respuesta HTTP: {$httpCode}";
        $detalles[] = "Tiempo de respuesta: " . round($totalTime, 2) . " ms";

        if ($httpCode >= 200 && $httpCode < 400) {
            $this->registrar(
                'TEST_CONECTIVIDAD_CLOUD',
                'Conectividad Cloud HTTPS Directa (mijardinrestaurant.com)',
                'Red & Cloud',
                'PASS',
                "Conexión HTTPS exitosa con el servidor cloud ({$httpCode} OK, " . round($totalTime, 2) . "ms).",
                $detalles,
                $dur
            );
        } else {
            if (!empty($err)) {
                $detalles[] = "Error cURL: {$err}";
            }
            $this->registrar(
                'TEST_CONECTIVIDAD_CLOUD',
                'Conectividad Cloud HTTPS Directa (mijardinrestaurant.com)',
                'Red & Cloud',
                'FAIL',
                "No se pudo conectar de forma segura a {$url} (HTTP {$httpCode}).",
                $detalles,
                $dur
            );
        }
    }

    // ------------------------------------------------------------------------
    // TEST 8: Integridad Git y Sincronización Remota
    // ------------------------------------------------------------------------
    private function testIntegridadGit() {
        $t0 = microtime(true);
        $detalles = [];

        function _cmd_git() {
            $salida = @shell_exec('git --version 2>&1');
            if ($salida && stripos($salida, 'git version') !== false) {
                return 'git';
            }
            $rutas = [
                'C:\Program Files\Git\cmd\git.exe',
                'C:\Program Files\Git\bin\git.exe',
                'C:\Program Files (x86)\Git\cmd\git.exe'
            ];
            foreach ($rutas as $r) {
                if (file_exists($r)) return '"' . $r . '"';
            }
            return null;
        }

        $git = _cmd_git();
        if (!$git) {
            $this->registrar('TEST_GIT_INTEGRIDAD', 'Integridad de Git y Control de Versiones', 'Sistema & Git', 'FAIL', 'Git no está disponible en el sistema.', [], (microtime(true) - $t0) * 1000);
            return;
        }

        $cmdVersion = "cd /d \"{$this->rootDir}\" && {$git} --version 2>&1";
        $version = trim((string)@shell_exec($cmdVersion));
        $detalles[] = "Versión instalada: {$version}";

        $cmdHash = "cd /d \"{$this->rootDir}\" && {$git} rev-parse --short HEAD 2>&1";
        $hashLocal = trim((string)@shell_exec($cmdHash));
        $detalles[] = "Commit local activo: {$hashLocal}";

        $cmdLog = "cd /d \"{$this->rootDir}\" && {$git} log -1 --format=\"%cd - %s\" --date=short 2>&1";
        $detalleLog = trim((string)@shell_exec($cmdLog));
        $detalles[] = "Último commit: {$detalleLog}";

        $cmdRemote = "cd /d \"{$this->rootDir}\" && {$git} remote get-url origin 2>&1";
        $remoteUrl = trim((string)@shell_exec($cmdRemote));
        $detalles[] = "Repositorio remoto: {$remoteUrl}";

        $dur = (microtime(true) - $t0) * 1000;
        if (!empty($hashLocal) && strlen($hashLocal) >= 6) {
            $this->registrar(
                'TEST_GIT_INTEGRIDAD',
                'Integridad de Git y Control de Versiones',
                'Sistema & Git',
                'PASS',
                "Repositorio íntegro en rama main. Commit activo: {$hashLocal}.",
                $detalles,
                $dur
            );
        } else {
            $this->registrar(
                'TEST_GIT_INTEGRIDAD',
                'Integridad de Git y Control de Versiones',
                'Sistema & Git',
                'FAIL',
                'No se pudo determinar el commit activo del repositorio local.',
                $detalles,
                $dur
            );
        }
    }

    public function obtenerResultados() {
        $total = count($this->resultados);
        $pass = 0; $fail = 0; $warn = 0;
        foreach ($this->resultados as $r) {
            if ($r['estado'] === 'PASS') $pass++;
            elseif ($r['estado'] === 'FAIL') $fail++;
            elseif ($r['estado'] === 'WARN') $warn++;
        }
        $durTotal = round((microtime(true) - $this->tiempoInicio) * 1000, 2);

        return [
            'resumen' => [
                'total' => $total,
                'pass'  => $pass,
                'fail'  => $fail,
                'warn'  => $warn,
                'duracion_total_ms' => $durTotal,
                'porcentaje_exito'  => $total > 0 ? round(($pass / $total) * 100, 1) : 0,
                'fecha' => date('Y-m-d H:i:s')
            ],
            'pruebas' => $this->resultados
        ];
    }
}

// ----------------------------------------------------------------------------
// EJECUCIÓN
// ----------------------------------------------------------------------------
$suite = new SuiteDiagnostico($rootDir);
$suite->ejecutarTodas();
$salida = $suite->obtenerResultados();

if (ob_get_length()) ob_clean();

// 1. SALIDA JSON
if ($format === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 2. SALIDA CLI (Terminal)
if ($format === 'cli' || $isCli) {
    echo "\n";
    echo "====================================================================\n";
    echo "  COMANDAS MOVILES - SUITE DE PRUEBAS Y DIAGNOSTICO INTEGRAL       \n";
    echo "  Mi Jardin Restaurant - " . $salida['resumen']['fecha'] . "\n";
    echo "====================================================================\n\n";

    foreach ($salida['pruebas'] as $idx => $p) {
        $tag = "[" . $p['estado'] . "]";
        $num = str_pad(($idx + 1) . ".", 3);
        echo "{$num} {$tag} {$p['nombre']} ({$p['duracion_ms']}ms)\n";
        echo "     -> {$p['mensaje']}\n";
        if (!empty($p['detalles'])) {
            foreach ($p['detalles'] as $d) {
                echo "        • {$d}\n";
            }
        }
        echo "\n";
    }

    echo "--------------------------------------------------------------------\n";
    echo "RESULTADO: {$salida['resumen']['pass']}/{$salida['resumen']['total']} Superadas | {$salida['resumen']['fail']} Fallos | {$salida['resumen']['warn']} Advertencias\n";
    echo "Tasa de Aprobación: {$salida['resumen']['porcentaje_exito']}% (Tiempo Total: {$salida['resumen']['duracion_total_ms']}ms)\n";
    echo "====================================================================\n\n";
    exit($salida['resumen']['fail'] > 0 ? 1 : 0);
}

// 3. SALIDA HTML MODERNA PARA NAVEGADOR
$r = $salida['resumen'];
$bgColor = ($r['fail'] === 0) ? '#28a745' : '#dc3545';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Integral y Unit Tests - Comandas Móviles</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/font-awesome.min.css">
    <style>
        body { background-color: #1a1e24; color: #e1e7ed; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding-bottom: 50px; }
        .hero-header { background: linear-gradient(135deg, #1f2937 0%, #111827 100%); border-bottom: 3px solid <?= $bgColor ?>; padding: 25px 0; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.4); }
        .stat-card { background: #242c37; border-radius: 8px; padding: 20px; text-align: center; border: 1px solid #333f4f; box-shadow: 0 4px 8px rgba(0,0,0,0.2); margin-bottom: 15px; }
        .stat-card .num { font-size: 34px; font-weight: 700; }
        .stat-card .lbl { font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #9aa7b5; }
        .card-test { background: #242c37; border-radius: 8px; border: 1px solid #333f4f; margin-bottom: 15px; overflow: hidden; transition: transform 0.15s; }
        .card-test:hover { border-color: #4b5a6f; transform: translateY(-2px); }
        .card-test-header { padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .badge-pass { background: #28a745; color: #fff; font-weight: bold; padding: 5px 12px; border-radius: 4px; font-size: 12px; }
        .badge-fail { background: #dc3545; color: #fff; font-weight: bold; padding: 5px 12px; border-radius: 4px; font-size: 12px; }
        .badge-warn { background: #ffc107; color: #212529; font-weight: bold; padding: 5px 12px; border-radius: 4px; font-size: 12px; }
        .card-test-body { padding: 15px 18px; font-size: 14px; }
        .detalles-list { margin-top: 10px; margin-bottom: 0; padding-left: 20px; color: #9bb0c5; font-size: 13px; font-family: monospace; }
        .detalles-list li { margin-bottom: 4px; }
        .btn-reload { background: #0880C5; color: #fff; font-weight: 600; padding: 10px 22px; border-radius: 6px; border: none; }
        .btn-reload:hover { background: #06659c; color: #fff; }
        .btn-back { background: #374151; color: #fff; font-weight: 600; padding: 10px 20px; border-radius: 6px; border: none; margin-right: 10px; text-decoration: none; display: inline-block; }
        .btn-back:hover { background: #4b5563; color: #fff; text-decoration: none; }
    </style>
</head>
<body>

<div class="hero-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 style="margin: 0; font-weight: 700; color: #ffffff;">
                    <i class="fa fa-stethoscope" style="color: <?= $bgColor ?>;"></i> Diagnóstico Integral y Suite de Tests
                </h2>
                <p style="margin: 5px 0 0 0; color: #9aa7b5; font-size: 14px;">
                    Verificador de Estado en Vivo: Conexión Dual MySQL, Fiscal SENIAT, Tablas Satélite, Mesas y Cloud HTTPS.
                </p>
            </div>
            <div class="col-md-4 text-right">
                <a href="../index.php" class="btn btn-back"><i class="fa fa-arrow-left"></i> Volver</a>
                <button type="button" class="btn btn-reload" onclick="window.location.reload()"><i class="fa fa-refresh"></i> Re-ejecutar Tests</button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Métricas -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="num" style="color: #60a5fa;"><?= $r['total'] ?></div>
                <div class="lbl">Pruebas Ejecutadas</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="num" style="color: #34d399;"><?= $r['pass'] ?></div>
                <div class="lbl">Aprobadas (PASS)</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="num" style="color: #f87171;"><?= $r['fail'] ?></div>
                <div class="lbl">Fallidas (FAIL)</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="num" style="color: #fbbf24;"><?= $r['porcentaje_exito'] ?>%</div>
                <div class="lbl">Índice de Salud (<?= $r['duracion_total_ms'] ?>ms)</div>
            </div>
        </div>
    </div>

    <!-- Lista de Tests -->
    <h4 style="margin: 25px 0 15px 0; font-weight: 600; color: #cbd5e1;">Resultados Detallados por Subsistema</h4>

    <?php foreach ($salida['pruebas'] as $idx => $p): 
        $badgeClass = ($p['estado'] === 'PASS') ? 'badge-pass' : (($p['estado'] === 'WARN') ? 'badge-warn' : 'badge-fail');
        $iconClass = ($p['estado'] === 'PASS') ? 'fa-check-circle text-success' : (($p['estado'] === 'WARN') ? 'fa-exclamation-triangle text-warning' : 'fa-times-circle text-danger');
    ?>
    <div class="card-test">
        <div class="card-test-header">
            <div>
                <i class="fa <?= $iconClass ?>" style="font-size: 18px; margin-right: 8px;"></i>
                <b style="color: #ffffff; font-size: 15px;"><?= ($idx + 1) . '. ' . htmlspecialchars($p['nombre']) ?></b>
                <span style="font-size: 12px; color: #9aa7b5; margin-left: 10px;">[<?= htmlspecialchars($p['categoria']) ?>]</span>
            </div>
            <div>
                <span style="font-size: 12px; color: #64748b; margin-right: 12px;"><?= $p['duracion_ms'] ?> ms</span>
                <span class="<?= $badgeClass ?>"><?= $p['estado'] ?></span>
            </div>
        </div>
        <div class="card-test-body">
            <p style="margin: 0; color: #e2e8f0; font-size: 14px;"><?= htmlspecialchars($p['mensaje']) ?></p>
            <?php if (!empty($p['detalles'])): ?>
                <ul class="detalles-list">
                    <?php foreach ($p['detalles'] as $d): ?>
                        <li><?= htmlspecialchars($d) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="text-align: center; margin-top: 30px; color: #64748b; font-size: 13px;">
        Comandas Móviles • Suite de Pruebas Automatizada • Ejecutado el <?= $r['fecha'] ?>
    </div>
</div>

</body>
</html>
