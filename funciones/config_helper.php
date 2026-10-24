<?php
/**
 * Helper central de configuracion para Comandas Moviles.
 * Lee prioritariamente la configuracion desde la base de datos satelite local "mijar_sistema" (puerto 3307),
 * protegiendo los datos confidenciales y eliminando la dependencia de archivos planos JSON inseguros.
 */

if (!function_exists("obtener_config_sistema")) {
    function obtener_config_sistema()
    {
        static $config_cache = null;
        if ($config_cache !== null) {
            return $config_cache;
        }

        $host_default = "127.0.0.1";
        $port_default = 3307;
        $user_default = "root";
        $pass_default = "root";
        $bd_sistema   = "mijar_sistema";

        // 1. Intentar consultar z_config_sistema en mijar_sistema (puerto 3307)
        try {
            $mysqli = @new mysqli($host_default, $user_default, $pass_default, $bd_sistema, $port_default);
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
                        $config_cache = $config;
                        return $config;
                    }
                }
                $mysqli->close();
            }
        } catch (\Throwable $e) {
            // Silencioso para fallback
        }

        // 2. Fallback de seguridad al archivo JSON si la BD local aun no estuviera iniciada
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
        $port_default = (int) ($datos["port"] ?? 3307);
        $user_default = $datos["user"] ?? "root";
        $pass_default = $datos["pw"] ?? "root";
        $bd_sistema   = "mijar_sistema";

        $guardado_bd = false;

        try {
            $mysqli = @new mysqli($host_default, $user_default, $pass_default, $bd_sistema, $port_default);
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
            }
        } catch (\Throwable $e) {
            // Error en BD
        }

        // Mantener archivo local respaldado
        $json_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . "datos_conexion.json";
        @file_put_contents($json_file, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $guardado_bd;
    }
}
