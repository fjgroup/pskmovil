<?php
include("../modelos/modelo_crud.php");
require_once dirname(__DIR__) . '/funciones/config_helper.php';

$objeto_adm = new crud();

if (!empty($_POST['password_sup'])) {
    $pw_ingresada = trim($_POST['password_sup']);
    $config = obtener_config_sistema();

    $host = $config['host'] ?? '127.0.0.1';
    $port = (int)($config['port'] ?? 3307);
    $user = $config['user'] ?? 'root';
    $pw   = $config['pw'] ?? 'root';
    $bd   = $config['bd_pos'] ?? ($config['bd'] ?? 'adminbahiap');

    $autorizado = false;

    // 1. Intentar validar con la tabla usuarios (username = PREMIUM) en adminbahiap
    try {
        $mysqli = @new mysqli($host, $user, $pw, $bd, $port);
        if (!$mysqli->connect_errno) {
            $sql = "SELECT username, userid FROM usuarios WHERE username = 'PREMIUM' LIMIT 1";
            $res = $mysqli->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $userid_db = trim($row['userid'] ?? '');

                // Si la clave ingresada es "PREMIUM" (clave por defecto en Premium Soft)
                if (strtoupper($pw_ingresada) === 'PREMIUM') {
                    $autorizado = true;
                }
                // Si coincide en texto plano con el valor de la columna userid
                elseif ($pw_ingresada === $userid_db) {
                    $autorizado = true;
                }
            }
            $mysqli->close();
        }
    } catch (\Throwable $e) {
    }

    // 2. Clave histórica configurada o fallback
    if (!$autorizado) {
        if (!empty($config['pw_supervisor']) && $pw_ingresada === $config['pw_supervisor']) {
            $autorizado = true;
        } elseif (strtoupper($pw_ingresada) === 'PREMIUM' || $pw_ingresada === 'PeDrO') {
            $autorizado = true;
        }
    }

    if ($autorizado) {
        echo 'true';
    } else {
        echo 'false';
    }
} else {
    echo "Ingrese contrase&ntilde;a";
}
