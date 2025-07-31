<?php
include("../modelos/modelo_crud.php");
$bd_conexion = "servidor";

$objeto_adm = new crud();

if( !empty($_POST['password_sup']) ){

	$path = "../datos_conexion.json";
	$file = file_get_contents($path);
	$file = json_decode($file,true);
	$pw_supervisor = $file['pw_supervisor'];

	if($_POST['password_sup'] == $pw_supervisor){
		echo 'true';		
	}else{
		echo 'false';
	}

}else{
	echo "Ingrese contrase&ntilde;a";
}

?>