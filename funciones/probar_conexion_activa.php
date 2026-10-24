<?php

require_once __DIR__ . '/config_helper.php';
$file = obtener_config_sistema();

try {
	$host = trim($file['host']);
	$user = trim($file['user']);
	$pw = trim($file['pw']);
	$db = trim($file['bd']);
	$port = trim($file['port']);
	if (empty($port)) {
		$port = '3306';
	}
	$conexion = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8;", $user, $pw);


	if ($conexion) {
		echo "Success";
	}else{
		echo "Error";
	}

} catch (PDOException $e) {
	echo "Error";
	//echo $e->getMessage();
}

?>