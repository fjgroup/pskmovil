<?php

$path = "../datos_conexion.json";
$file = array();
if (file_exists($path)) {
	$file = file_get_contents($path);
	$file = json_decode($file, true);
}

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