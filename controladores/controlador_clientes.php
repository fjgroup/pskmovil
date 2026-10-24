<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
clearstatcache();

include("../modelos/modelo_crud.php");

require_once dirname(__DIR__) . '/funciones/config_helper.php';
$datos_conexion = obtener_config_sistema();
$file = $datos_conexion;

$objeto_cliente = new crud();
$bd_conexion_cliente = "cliente";
$bd_conexion_servidor = "servidor";

if($_GET['accion'] == "registrar"){

	if( !empty($_POST['codigo']) ){

		$_POST['codigo'] = strtoupper($_POST['codigo']);

		if( isset($_POST['formafis']) ){
			$_POST['formafis'] = 3;
		}else{
			$_POST['formafis'] = 1;
		}

		$query = " SELECT codigo FROM tipocli LIMIT 1";
		$consulta = $objeto_cliente->general_query($bd_conexion_cliente,$query);
		$datos_tipocli = mysqli_fetch_array($consulta);

		$query = " SELECT COUNT(*) AS total FROM cliempre WHERE codigo = '".$_POST['codigo']."' LIMIT 1";
		$consulta = $objeto_cliente->general_query($bd_conexion_cliente,$query);
		$datos_cli = mysqli_fetch_array($consulta);
		if( empty($datos_cli['total']) ){

			$query = " 
				INSERT INTO cliempre
				(
					id_empresa,
					agencia,
					codigo,
					nombre,
					cedula,
					nrorif,
					nronit,
					direccion,
					telefonos,
					email,
					perscont,
					sector,
					precio,
					credito,
					tipo,
					fecha,
					status,
					formafis
				)
				VALUES
				(
					'".$datos_conexion['cod_empresa']."',
					'".$datos_conexion['cod_agencia']."',
					'".$_POST['codigo']."',
					'".strtoupper($_POST['nombre'])."',
					'".$_POST['cedula']."',
					'".$_POST['nrorif']."',
					'".$_POST['nronit']."',
					'".$_POST['direccion']."',
					'".$_POST['telefonos']."',
					'".$_POST['email']."',
					'".strtoupper($_POST['perscont'])."',
					'".$datos_conexion['cod_zona']."',
					1,
					'N',
					'".$datos_tipocli['codigo']."',
					'".date('Y-m-d')."',
					1,
					".$_POST['formafis']."
				)
			";

		}else{

			$query = " 
				UPDATE cliempre
				SET 
				id_empresa = '".$datos_conexion['cod_empresa']."',
				agencia = '".$datos_conexion['cod_agencia']."',
				codigo = '".$_POST['codigo']."',
				nombre = '".strtoupper($_POST['nombre'])."',
				cedula = '".$_POST['cedula']."',
				nrorif = '".$_POST['nrorif']."',
				nronit = '".$_POST['nronit']."',
				direccion = '".$_POST['direccion']."',
				telefonos = '".$_POST['telefonos']."',
				email = '".$_POST['email']."',
				perscont = '".strtoupper($_POST['perscont'])."',
				sector = '".$datos_conexion['cod_zona']."',
				precio = 1,
				credito = 'N',
				tipo = '".$datos_tipocli['codigo']."',
				status = 1,
				formafis = ".$_POST['formafis']."
				WHERE codigo = '".$_POST['codigo']."'
			";

		}

		$resultado = $objeto_cliente->general_query($bd_conexion_cliente,$query);
		if( $resultado ){
			echo "
				<i class='fa fa-check'></i> Datos guardados
				<script>
					$('#reg_cli').hide();
					LimpiarFormulario('form_reg_cli');
				</script>
			";	
		}else{
			echo "<i class='fa fa-times'></i> Error al guardar datos del cliente ".$GLOBALS['error_mysql'];	
		}

	}else{
		echo "<i class='fa fa-times'></i> Ingrese el codigo del cliente";
	}
}

?>