<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/funciones/verificador_bd.php';

$accion = $_GET['accion'] ?? 'verificar_reparar';

if ($accion === 'verificar_reparar') {
    $host = !empty($_POST['host']) ? trim($_POST['host']) : null;
    $user = !empty($_POST['user']) ? trim($_POST['user']) : null;
    $pass = isset($_POST['pw']) ? $_POST['pw'] : null;
    $port = !empty($_POST['port']) ? (int)$_POST['port'] : null;

    $res = verificar_y_reparar_base_datos_satelite($host, $user, $pass, $port);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
