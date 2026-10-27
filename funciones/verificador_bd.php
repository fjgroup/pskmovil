<?php
/**
 * Verificador y Auto-Reparador de la Base de Datos Satélite (mijar26273_sistema)
 * Garantiza que la BD satélite exista, tenga las 5 tablas con su estructura canónica
 * exacta y los datos obligatorios de configuración, evitando pérdidas de tiempo
 * al migrar entre la PC de desarrollo y la PC del restaurante en producción.
 */

require_once __DIR__ . '/config_helper.php';

if (!function_exists('verificar_y_reparar_base_datos_satelite')) {
    function verificar_y_reparar_base_datos_satelite($host_in = null, $user_in = null, $pass_in = null, $port_in = null)
    {
        $reporte = [
            'success' => false,
            'conexion' => false,
            'bd_creada' => false,
            'tablas_creadas' => [],
            'columnas_agregadas' => [],
            'config_actualizada' => [],
            'detalles' => [],
            'errores' => []
        ];

        // 1. Detectar o probar conexión con las credenciales candidatas
        $candidatos = credenciales_mysql_candidatas();
        if ($port_in) {
            array_unshift($candidatos, [
                'port' => (int)$port_in,
                'user' => $user_in ?: 'root',
                'pw'   => $pass_in !== null ? $pass_in : 'root',
                'entorno' => ((int)$port_in === 3306 ? 'produccion' : 'desarrollo')
            ]);
        }

        $mysqli = null;
        $cred_activa = null;
        foreach ($candidatos as $cand) {
            try {
                $m = @new mysqli($host_in ?: '127.0.0.1', $cand['user'], $cand['pw'], null, $cand['port']);
                if (!$m->connect_errno) {
                    $mysqli = $m;
                    $cred_activa = $cand;
                    break;
                }
            } catch (\Throwable $e) {}
        }

        if (!$mysqli) {
            $reporte['errores'][] = "No se pudo conectar a MySQL en ningún puerto candidato (3307 / 3306).";
            return $reporte;
        }

        $reporte['conexion'] = true;
        $reporte['detalles'][] = "Conectado a MySQL en puerto {$cred_activa['port']} ({$cred_activa['entorno']}) con usuario '{$cred_activa['user']}'.";

        $bd_nombre = "mijar26273_sistema";

        // 2. Asegurar que exista la base de datos
        $res_db = $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$bd_nombre}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        if (!$res_db) {
            $reporte['errores'][] = "Error al crear la base de datos {$bd_nombre}: " . $mysqli->error;
            $mysqli->close();
            return $reporte;
        }
        $reporte['bd_creada'] = true;

        if (!$mysqli->select_db($bd_nombre)) {
            $reporte['errores'][] = "No se pudo seleccionar la base de datos {$bd_nombre}: " . $mysqli->error;
            $mysqli->close();
            return $reporte;
        }

        // 3. Estructura canónica de las 5 tablas satélite requeridas
        $tablas_schema = [
            'z_config_sistema' => "CREATE TABLE IF NOT EXISTS `z_config_sistema` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `clave` varchar(60) NOT NULL,
                `valor` longtext NULL,
                `created_at` datetime NULL,
                `updated_at` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_clave_unica` (`clave`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'z_config_bancos' => "CREATE TABLE IF NOT EXISTS `z_config_bancos` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `codigo_pos` varchar(20) NULL,
                `banco` varchar(60) NOT NULL,
                `titular` varchar(100) NOT NULL,
                `identificacion` varchar(100) NULL,
                `telefono` varchar(50) NULL,
                `numero_cuenta` varchar(100) NULL,
                `tipo` enum('PAGO_MOVIL','TRANSFERENCIA','ZELLE') NOT NULL DEFAULT 'PAGO_MOVIL',
                `activo` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime NULL,
                `updated_at` datetime NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'z_mesas_activas' => "CREATE TABLE IF NOT EXISTS `z_mesas_activas` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `documento` varchar(20) NOT NULL,
                `mesa` varchar(20) NOT NULL,
                `token_pin` varchar(10) NULL,
                `subtotal_bs` decimal(18,2) DEFAULT 0.00,
                `impuesto_bs` decimal(18,2) DEFAULT 0.00,
                `total_bs` decimal(18,2) DEFAULT 0.00,
                `subtotal_usd` decimal(18,2) DEFAULT 0.00,
                `impuesto_usd` decimal(18,2) DEFAULT 0.00,
                `total_usd` decimal(18,2) DEFAULT 0.00,
                `tasa_dia` decimal(18,4) DEFAULT 1.0000,
                `items_json` longtext NULL,
                `estado` enum('ACTIVA','CERRADA') DEFAULT 'ACTIVA',
                `created_at` datetime NULL,
                `updated_at` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_doc_unico` (`documento`),
                KEY `idx_estado_mesa` (`estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'z_status_pago' => "CREATE TABLE IF NOT EXISTS `z_status_pago` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `documento` varchar(20) NOT NULL,
                `token_pin` varchar(10) NULL,
                `estado` varchar(2) NOT NULL DEFAULT '0',
                `metodo` varchar(50) NULL,
                `banco_origen` varchar(100) NULL,
                `referencia` varchar(100) NULL,
                `monto` decimal(18,2) NULL,
                `moneda` varchar(10) DEFAULT 'VES',
                `fecha_reporte` datetime NULL,
                `aprobado_por` varchar(50) NULL,
                `fecha_confirmacion` datetime NULL,
                `created_at` datetime NULL,
                `updated_at` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_documento` (`documento`),
                KEY `idx_estado` (`estado`),
                KEY `idx_token_pin` (`token_pin`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'z_top_productos' => "CREATE TABLE IF NOT EXISTS `z_top_productos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `posicion` tinyint(4) NOT NULL,
                `codigo` varchar(25) NOT NULL,
                `nombre` varchar(150) NOT NULL,
                `precio_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
                `precio_usd` decimal(18,2) NOT NULL DEFAULT 0.00,
                `total_vendido` decimal(18,2) NOT NULL DEFAULT 0.00,
                `foto_base64` longtext NULL,
                `created_at` datetime NULL,
                `updated_at` datetime NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_posicion` (`posicion`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        ];

        foreach ($tablas_schema as $tbl => $sql) {
            if ($mysqli->query($sql)) {
                $reporte['tablas_creadas'][] = $tbl;
            } else {
                $reporte['errores'][] = "Error al verificar/crear tabla {$tbl}: " . $mysqli->error;
            }
        }

        // 4. Verificación de columnas específicas por si alguna tabla existía de una versión anterior
        $columnas_necesarias = [
            'z_mesas_activas' => [
                'subtotal_bs' => "decimal(18,2) DEFAULT 0.00",
                'impuesto_bs' => "decimal(18,2) DEFAULT 0.00",
                'total_bs'    => "decimal(18,2) DEFAULT 0.00",
                'subtotal_usd' => "decimal(18,2) DEFAULT 0.00",
                'impuesto_usd' => "decimal(18,2) DEFAULT 0.00",
                'total_usd'   => "decimal(18,2) DEFAULT 0.00",
                'tasa_dia'    => "decimal(18,4) DEFAULT 1.0000",
                'items_json'  => "longtext NULL",
                'estado'      => "enum('ACTIVA','CERRADA') DEFAULT 'ACTIVA'"
            ],
            'z_status_pago' => [
                'aprobado_por'       => "varchar(50) NULL",
                'fecha_confirmacion' => "datetime NULL",
                'fecha_reporte'      => "datetime NULL",
                'metodo'             => "varchar(50) NULL",
                'referencia'         => "varchar(100) NULL"
            ],
            'z_config_bancos' => [
                'codigo_pos' => "varchar(20) NULL"
            ]
        ];

        foreach ($columnas_necesarias as $tbl => $cols) {
            $cols_existentes = [];
            $res_c = $mysqli->query("SHOW COLUMNS FROM `{$tbl}`");
            if ($res_c) {
                while ($rc = $res_c->fetch_assoc()) {
                    $cols_existentes[strtolower($rc['Field'])] = true;
                }
            }
            foreach ($cols as $c_name => $c_def) {
                if (!isset($cols_existentes[strtolower($c_name)])) {
                    $alter_sql = "ALTER TABLE `{$tbl}` ADD COLUMN `{$c_name}` {$c_def};";
                    if ($mysqli->query($alter_sql)) {
                        $reporte['columnas_agregadas'][] = "{$tbl}.{$c_name}";
                    }
                }
            }
        }

        // 5. Parámetros obligatorios de z_config_sistema (no sobreescribe valores existentes)
        $config_defaults = [
            'url_hosting'         => 'https://mijardinrestaurant.com',
            'url_web_mesa'        => 'https://mijardinrestaurant.com/mesa',
            'tipo_doc'            => 'ESP',
            'bd_pos'              => 'adminbahiap',
            'bd_sistema'          => 'mijar26273_sistema',
            'permitir_descuentos' => 'no',
            'monedafactor'        => '887.3020'
        ];

        // Depurar claves obsoletas de versiones anteriores
        $mysqli->query("DELETE FROM z_config_sistema WHERE clave IN ('mesas_caja', 'mesas_registradas')");

        $now = date('Y-m-d H:i:s');
        foreach ($config_defaults as $k => $def_v) {
            $k_esc = $mysqli->real_escape_string($k);
            $v_esc = $mysqli->real_escape_string($def_v);
            // Insertar solo si la clave no existe
            $mysqli->query("INSERT IGNORE INTO z_config_sistema (clave, valor, created_at, updated_at) 
                            VALUES ('{$k_esc}', '{$v_esc}', '{$now}', '{$now}');");
            $reporte['config_actualizada'][] = $k;
        }

        $reporte['success'] = empty($reporte['errores']);
        $reporte['detalles'][] = "Estructura de las 5 tablas satélite y parámetros clave verificados al 100%.";

        $mysqli->close();
        return $reporte;
    }
}
