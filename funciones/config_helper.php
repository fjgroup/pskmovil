<?php
/**
 * Helper central de configuracion para Comandas Moviles.
 * Lee prioritariamente la configuracion desde la base de datos satelite local "mijar26273_sistema",
 * protegiendo los datos confidenciales y eliminando la dependencia de archivos planos JSON inseguros.
 * 
 * Soporte Dual Automático de Credenciales:
 *  - DESARROLLO (Casa): Puerto 3307, Usuario root, Contraseña root
 *  - PRODUCCIÓN (Restaurante): Puerto 3306, Usuario root, Contraseña 1234
 */

if (!function_exists("credenciales_mysql_candidatas")) {
    function credenciales_mysql_candidatas()
    {
        return [
            // 1. Prioridad: DESARROLLO (PC Casa: MySQL 5.5 de Premium Soft)
            [
                'port' => 3307,
                'user' => 'root',
                'pw'   => 'root',
                'entorno' => 'desarrollo'
            ],
            // 2. PRODUCCIÓN (PC Restaurante: MySQL 5.5/8 con clave del restaurante)
            [
                'port' => 3306,
                'user' => 'root',
                'pw'   => '1234',
                'entorno' => 'produccion'
            ],
            // 3. Fallbacks de tolerancia a fallos
            [
                'port' => 3306,
                'user' => 'root',
                'pw'   => 'root',
                'entorno' => 'produccion'
            ],
            [
                'port' => 3307,
                'user' => 'root',
                'pw'   => '1234',
                'entorno' => 'desarrollo'
            ]
        ];
    }
}

if (!function_exists("puertos_mysql_candidatos")) {
    function puertos_mysql_candidatos()
    {
        return [3307 => "desarrollo", 3306 => "produccion"];
    }
}

if (!function_exists("obtener_config_sistema")) {
    function obtener_config_sistema()
    {
        static $config_cache = null;
        if ($config_cache !== null) {
            return $config_cache;
        }

        $host_default = "127.0.0.1";
        $bd_candidatas = ["mijar26273_sistema", "mijardin_sistema", "mijar_sistema"];

        // 1. Intentar consultar z_config_sistema con la matriz de credenciales (3307 root/root primero, luego 3306 root/1234)
        foreach (credenciales_mysql_candidatas() as $cred) {
            $port_c = $cred['port'];
            $user_c = $cred['user'];
            $pass_c = $cred['pw'];
            $entorno_c = $cred['entorno'];

            foreach ($bd_candidatas as $bd_sistema) {
                try {
                    $mysqli = @new mysqli($host_default, $user_c, $pass_c, $bd_sistema, $port_c);
                    if (!$mysqli->connect_errno) {
                        $res = $mysqli->query("SELECT clave, valor FROM z_config_sistema");
                        if ($res && $res->num_rows > 0) {
                            $config = [];
                            while ($row = $res->fetch_assoc()) {
                                $k = $row["clave"];
                                $v = $row["valor"];
                                if ($k === "config_completa") {
                                    $decoded = json_decode($v, true);
                                    if (is_array($decoded)) {
                                        $config = array_merge($config, $decoded);
                                    }
                                } else {
                                    if ($k === "impuestos") {
                                        $config[$k] = json_decode($v, true) ?: $v;
                                    } else {
                                        $config[$k] = $v;
                                    }
                                }
                            }
                            $mysqli->close();
                            if (!empty($config)) {
                                $config['bd_sistema'] = $bd_sistema;
                                // Credenciales y entorno que realmente conectaron
                                $config['port'] = $port_c;
                                $config['user'] = $user_c;
                                $config['pw']   = $pass_c;
                                $config['modo_entorno'] = $entorno_c;

                                // La sincronización cloud va DIRECTAMENTE al hosting en internet
                                if (empty($config['url_hosting']) || strpos($config['url_hosting'], '.test') !== false) {
                                    $config['url_hosting'] = 'https://mijardinrestaurant.com';
                                }
                                if (empty($config['url_web_mesa']) || strpos($config['url_web_mesa'], '.test') !== false) {
                                    $config['url_web_mesa'] = 'https://mijardinrestaurant.com/mesa';
                                }

                                $config_cache = $config;
                                return $config;
                            }
                        } else {
                            $mysqli->close();
                        }
                    }
                } catch (\Throwable $e) {
                    // Silencioso para fallback
                }
            }
        }

        // 2. Fallback de seguridad al archivo JSON si la BD local aún no estuviera creada
        $json_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . "datos_conexion.json";
        if (file_exists($json_file)) {
            $data = json_decode(file_get_contents($json_file), true);
            if (is_array($data)) {
                $config_cache = $data;
                return $data;
            }
        }

        return [];
    }
}

if (!function_exists("guardar_config_sistema")) {
    function guardar_config_sistema(array $datos)
    {
        $host_default = $datos["host"] ?? "127.0.0.1";
        $bd_preferida = !empty($datos["bd_sistema"]) ? $datos["bd_sistema"] : "mijar26273_sistema";
        $bd_candidatas = array_unique([$bd_preferida, "mijar26273_sistema", "mijardin_sistema", "mijar_sistema"]);

        $guardado_bd = false;

        // Probar con cada credencial candidata hasta guardar exitosamente
        foreach (credenciales_mysql_candidatas() as $cred) {
            $port_c = !empty($datos["port"]) ? (int)$datos["port"] : $cred['port'];
            $user_c = !empty($datos["user"]) ? $datos["user"] : $cred['user'];
            $pass_c = isset($datos["pw"]) ? $datos["pw"] : $cred['pw'];

            foreach ($bd_candidatas as $bd_sistema) {
                try {
                    $mysqli = @new mysqli($host_default, $user_c, $pass_c, $bd_sistema, $port_c);
                    if (!$mysqli->connect_errno) {
                        // Crear tabla si no existe
                        $mysqli->query("CREATE TABLE IF NOT EXISTS `z_config_sistema` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `clave` varchar(60) NOT NULL UNIQUE,
                            `valor` longtext NULL,
                            `created_at` datetime NULL,
                            `updated_at` datetime NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

                        $now = date("Y-m-d H:i:s");
                        $completo_json = $mysqli->real_escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE));

                        $mysqli->query("INSERT INTO z_config_sistema (clave, valor, created_at, updated_at) 
                            VALUES ('config_completa', '{$completo_json}', '{$now}', '{$now}')
                            ON DUPLICATE KEY UPDATE valor = '{$completo_json}', updated_at = '{$now}';");

                        foreach ($datos as $k => $v) {
                            if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                            $k_esc = $mysqli->real_escape_string($k);
                            $v_esc = $mysqli->real_escape_string((string)$v);
                            $mysqli->query("INSERT INTO z_config_sistema (clave, valor, created_at, updated_at) 
                                VALUES ('{$k_esc}', '{$v_esc}', '{$now}', '{$now}')
                                ON DUPLICATE KEY UPDATE valor = '{$v_esc}', updated_at = '{$now}';");
                        }
                        $mysqli->close();
                        $guardado_bd = true;
                        break 2;
                    }
                } catch (\Throwable $e) {
                    // Error en BD
                }
            }
        }

        return $guardado_bd;
    }
}

if (!function_exists("conectar_bd_pos")) {
    function conectar_bd_pos($config = null)
    {
        if (!$config) $config = obtener_config_sistema();
        $bd = $config['bd_pos'] ?? 'adminbahiap';
        $host = $config['host'] ?? '127.0.0.1';
        
        // 1. Probar credenciales activas del config
        if (!empty($config['port'])) {
            $m = @new mysqli($host, $config['user'] ?? 'root', $config['pw'] ?? 'root', $bd, (int)$config['port']);
            if (!$m->connect_errno) return $m;
        }

        // 2. Probar matriz dual completa (3307 root/root -> 3306 root/1234)
        foreach (credenciales_mysql_candidatas() as $c) {
            $m = @new mysqli($host, $c['user'], $c['pw'], $bd, (int)$c['port']);
            if (!$m->connect_errno) return $m;
        }

        return false;
    }
}

if (!function_exists("conectar_bd_sistema")) {
    function conectar_bd_sistema($config = null)
    {
        if (!$config) $config = obtener_config_sistema();
        $bd_pref = $config['bd_sistema'] ?? 'mijar26273_sistema';
        $bds = array_unique([$bd_pref, 'mijar26273_sistema', 'mijar_sistema', 'mijardin_sistema']);
        $host = $config['host'] ?? '127.0.0.1';

        // 1. Probar credenciales activas del config
        if (!empty($config['port'])) {
            foreach ($bds as $bd) {
                $m = @new mysqli($host, $config['user'] ?? 'root', $config['pw'] ?? 'root', $bd, (int)$config['port']);
                if (!$m->connect_errno) return $m;
            }
        }

        // 2. Probar matriz dual completa (3307 root/root -> 3306 root/1234)
        foreach (credenciales_mysql_candidatas() as $c) {
            foreach ($bds as $bd) {
                $m = @new mysqli($host, $c['user'], $c['pw'], $bd, (int)$c['port']);
                if (!$m->connect_errno) return $m;
            }
        }

        return false;
    }
}

