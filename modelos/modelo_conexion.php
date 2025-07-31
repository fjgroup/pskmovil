<?php


class conexion{
	function __construct(){		
	}
	function query($bd_conexion,$query){	

	$file = file_get_contents("../datos_conexion.json");
	$datos_conexion = json_decode($file,true);		

	if($bd_conexion == "cliente"){
		if(isset($datos_conexion['host'])){
			$host = $datos_conexion['host'];
			$user = $datos_conexion['user'];
			$pw = $datos_conexion['pw'];
			$bd = $datos_conexion['bd'];
			if( !empty($datos_conexion['port']) ){
				$port = ':'.$datos_conexion['port'];
			}else{
				$port = ':3306';
			}
		}
	}
		
	@$conexion = mysqli_connect($host.$port,$user,$pw);
		if($conexion){
			@$select_bd = mysqli_select_db($conexion,$bd);
			if($select_bd){

				$resultado = mysqli_query($conexion,$query);
				if($resultado == true){
					$GLOBALS['error_mysql'] = '';
					return $resultado;
				}else{
					$GLOBALS['error_mysql'] = mysqli_error($conexion);
					return false;
				}
			}
		}

		@mysqli_close();

	}
	
}

?>