<?php

require_once __DIR__ . '/config_helper.php';
$file = obtener_config_sistema();

try {
	$host = $_POST['host'];
	$port = $_POST['port'];
	$db = $_POST['bd'];
	$user = $_POST['user'];
	$pw = $_POST['pw'];
	$conexion = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8;", $user, $pw);
	
	$file['host'] = $host;
	$file['user'] = $user;
	$file['pw'] = $pw;
	$file['bd'] = $db;
	$file['bd_pos'] = $db;
	$file['port'] = $port;
	$guardado = guardar_config_sistema($file);
	if ($guardado) {
		echo "<i class='fa fa-check'></i> Conexion exitosa";
	} else {
		echo "<i class='fa fa-times'></i> Error al guardar configuración en la base de datos";
	}

	}catch(PDOException $e){
		echo "Error de conexion<br>";
		echo $e->getMessage();
	}

?>