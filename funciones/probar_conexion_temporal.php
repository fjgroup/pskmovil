<?php

    $path = "../datos_conexion.json";
	$file = array();
	if( file_exists($path) ){
		$file = file_get_contents($path);
		$file = json_decode($file,true);
	}

	try{
		$host = $_POST['host'];
		$port = $_POST['port'];
		$db = $_POST['bd'];
		$user = $_POST['user'];
		$pw = $_POST['pw'];
		$conexion = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8;",$user,$pw);
		
		$file['host'] = $host;
		$file['user'] = $user;
		$file['pw'] = $pw;
		$file['bd'] = $db;
		$file['port'] = $port; // no se estaba pasando el puerto - GDM
		$file_put = file_put_contents($path,json_encode($file));
		if( $file_put ){
			echo "<i class='fa fa-check'></i> Conexion exitosa";
		}else{
			echo "<i class='fa fa-times'></i> Error al actualizar archivo JSON de conexion";
		}

	}catch(PDOException $e){
		echo "Error de conexion<br>";
		echo $e->getMessage();
	}

?>