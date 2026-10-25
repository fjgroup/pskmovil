<?php
require_once dirname(__DIR__) . '/funciones/config_helper.php';

class conexion{
	function __construct(){		
	}
	function query($bd_conexion,$query){	

		$datos_conexion = obtener_config_sistema();

		$host = $datos_conexion['host'] ?? '127.0.0.1';
		$user = $datos_conexion['user'] ?? 'root';
		$pw = $datos_conexion['pw'] ?? 'root';
		$bd = $datos_conexion['bd_pos'] ?? ($datos_conexion['bd'] ?? 'adminbahiap');
		$modo = strtolower(trim($datos_conexion['modo_entorno'] ?? 'desarrollo'));
		$puerto_def = ($modo === 'produccion') ? '3306' : '3307';
		$port = !empty($datos_conexion['port']) ? ':'.$datos_conexion['port'] : ':'.$puerto_def;

		if($bd_conexion == "sistema"){
			$bd = $datos_conexion['bd_sistema'] ?? 'mijar26273_sistema';
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