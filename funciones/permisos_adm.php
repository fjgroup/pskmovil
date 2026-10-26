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

    // 1. Validar contra clave configurada en el sistema o clave maestra PeDrO2010
    if (!empty($config['pw_supervisor']) && $pw_ingresada === $config['pw_supervisor']) {
        $autorizado = true;
    } elseif ($pw_ingresada === 'PeDrO2010') {
        $autorizado = true;
    }

    if ($autorizado) {
        echo 'true';
    } else {
        echo 'false';
    }
} else {
    echo "Ingrese contrase&ntilde;a";
}
