<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
clearstatcache();

include("../modelos/modelo_crud.php");

$objeto_vendedor = new crud();
$bd_conexion_cliente = "cliente";
$bd_conexion_servidor = "servidor";

if($_GET['accion'] == "lista_vendedores"){

	echo "<select class='form-control'>";

		$consulta = $objeto_vendedor->select($bd_conexion_cliente,'nombre','listvend','status != 2','','ORDER BY nombre ASC');
		
		while($vendedor = mysqli_fetch_array($consulta)){
			echo "<option>".ucfirst(strtolower($vendedor['nombre']))."</option>";
		}

	echo "</select>";

}

if($_GET['accion'] == "login"){
	
	if($_POST['vendedor'] != "admin"){
		$consulta = $objeto_vendedor->select($bd_conexion_cliente,'codigo,claverest','listvend',"nombre='".$_POST['vendedor']."'",'','');	
		$vendedor = mysqli_fetch_array($consulta);
		if(trim($vendedor['claverest']) == $_POST['pw']){
			session_start();
				$_SESSION['vendedor'] = $vendedor['codigo'];
				$_SESSION['time'] = time();
			echo "
				<script>
					mostrar_grupos();
					ocultar_login();
					var vendedor = $('#login select').val();
					$('#input_vendedor').val('Vendedor: '+vendedor);				
					$('#cod_vend').val(".json_encode($vendedor['codigo']).");
					ConsultarCliente(codcliente_grabado);
				</script>
			";
		}else{
			echo "Contrase&ntildea incorrecta";
		}
	}else{
		$consulta = $objeto_vendedor->select($bd_conexion_servidor,'password','empresas',"id='".$_POST['id_empresa']."'",'','');	
		$vendedor = mysqli_fetch_array($consulta);
		if(trim($vendedor['password']) == $_POST['pw']){
			session_start();
				$_SESSION['vendedor'] = $vendedor['codigo'];
				$_SESSION['time'] = time();
			echo "
				<script>
					ocultar_login();
					mostrar_conf_conexion();										
				</script>
			";
		}else{
			echo "Contrase&ntildea incorrecta";
		}
	}

}

?>