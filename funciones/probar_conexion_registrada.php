<?php
include ("../modelos/modelo_crud.php");
$objeto_conexion = new crud();
$bd_conexion_servidor = "servidor";
$bd_conexion_cliente = "cliente";

$hoy = date("Y-m-d");

$path = "../datos_conexion.json";

if (file_exists($path)) {

	$file = file_get_contents($path);
	$datos_conexion = json_decode($file, true);

	$host = trim($datos_conexion['host']);
	$user = trim($datos_conexion['user']);
	$pw = trim($datos_conexion['pw']);
	$bd = trim($datos_conexion['bd']);
	$port = trim($datos_conexion['port']);
	if (empty($port)) {
		$port = '3306';
	}
	$cod_empresa = strtoupper($datos_conexion['cod_empresa']);
	$cod_agencia = strtoupper($datos_conexion['cod_agencia']);
	$cod_cliente = strtoupper($datos_conexion['cod_cliente']);
	$cod_zona = strtoupper($datos_conexion['cod_zona']);
	$cod_deposito = strtoupper($datos_conexion['cod_deposito']);
	$recargo = (!isset($datos_conexion['recargo']) ? 0 : $datos_conexion['recargo']);
	$pw_supervisor = $datos_conexion['pw_supervisor'];
	$cuentas_compartidas_entre_vendedores = $datos_conexion['cuentas_compartidas_entre_vendedores'];
	$agrupar_mesas_por_ubicacion = $datos_conexion['agrupar_mesas_por_ubicacion'];
	$usar_subgrupos = $datos_conexion['usar_subgrupos'];
	$no_usar_impuestos = $datos_conexion['no_usar_impuestos'];
	$no_editar_integrados = $datos_conexion['no_editar_integrados'];
	$ocultar_integrados = $datos_conexion['ocultar_integrados'];
	$cantidad_por_pagina = $datos_conexion['cantidad_por_pagina'];
	$no_tiene_existencia = !isset($datos_conexion['no_tiene_existencia']) ? 'no' : $datos_conexion['no_tiene_existencia'];
	if (empty($datos_conexion['tipo_doc'])) {
		$datos_conexion['tipo_doc'] = 'ESP';
	}
	$tipo_doc = $datos_conexion['tipo_doc'];
	$datos_conexion['impuestos'] = array();

	if (!empty($host)) {
		@$conexion = mysqli_connect($host . ':' . $port, $user, $pw);

		if ($conexion) {
			@$select_bd = mysqli_select_db($conexion, $bd);
			if ($select_bd) {

				echo "<i class='fa fa-check'></i> Conexion exitosa";
				if ($no_usar_impuestos == "no" or empty($no_usar_impuestos)) {

					/*
									   VZLA=58 Y PANAMA=507 NO USA IMPUESTOS MULTIPLES (LOS DEMÁS SI).
									   HONDURAS=504 USA MULTIPLES IMPUESTOS PERO ES CONDICIONAL
								   */
					$query = "
						SELECT
						codpaisemp,
						activaimpuestomultiple
						FROM config2
						LIMIT 1
					";

					$query_datos_config2 = $objeto_conexion->general_query($bd_conexion_cliente, $query);
					$datos_config2 = mysqli_fetch_array($query_datos_config2);

					$query = "
						SELECT
						ali_normal,
						ali_reducida,
						ali_extendida,
						monnacsimb,
						monexsimb,
						dividemultiplica,
						monedafactor
						FROM config
						LIMIT 1
					";

					$query_impuestos_adm = $objeto_conexion->general_query($bd_conexion_cliente, $query);
					$datos_config = mysqli_fetch_array($query_impuestos_adm);

					$datos_conexion['dividemultiplica'] = $datos_config['dividemultiplica'];
					$datos_conexion['monedafactor'] = $datos_config['monedafactor'];
					$codigo_a_guardar = null;
					$codpaisemp = trim($datos_config2['codpaisemp']);
					$codigo_a_guardar = $codpaisemp;

					if (
						(trim($datos_config2['codpaisemp']) == '58') or
						(trim($datos_config2['codpaisemp']) == '507') or
						(trim($datos_config2['codpaisemp']) == '504' and $datos_config2['activaimpuestomultiple'] == 0)
					) {
						$codigo_a_guardar = $codpaisemp;

						array("codigo" => "02", "nombre" => "Alicuota Reducida", "prc" => $datos_config['ali_reducida']);
						$datos_conexion['impuestos'][] = array("codigo" => "03", "nombre" => "Alicuota Adicional", "prc" => $datos_config['ali_extendida']);
						echo "<script>console.log('Condición PHP cumplida, codigo a guardar: " . $codigo_a_guardar . "'); localStorage.setItem('codigo_pais', '" . $codigo_a_guardar . "');</script>";


					} else {
						echo "<script>console.log('Condición PHP NO cumplida.'); if (localStorage.getItem('codigo_pais')) { localStorage.removeItem('codigo_pais'); }</script>";
						$query = "
							SELECT
							impu_codigo,
							impu_nombre,
							impu_prc,
							impu_monto
							FROM impuestos
						";

						$query_impuestos_adm = $objeto_conexion->general_query($bd_conexion_cliente, $query);

						$impuestos_adicionados = array();
						while ($imp_adm = mysqli_fetch_array($query_impuestos_adm)) {
							if (!in_array(trim($imp_adm['impu_codigo']), $impuestos_adicionados)) {
								$impuestos_adicionados[] = trim($imp_adm['impu_codigo']);
								$datos_conexion['impuestos'][] = array("codigo" => trim($imp_adm['impu_codigo']), "nombre" => trim($imp_adm['impu_nombre']), "prc" => $imp_adm['impu_prc']);
							}
						}

					}


				} else {
					echo "<script>console.log('Condición PHP NO cumplida.'); if (localStorage.getItem('codigo_pais')) { localStorage.removeItem('codigo_pais'); }</script>";

					$datos_conexion['impuestos'][] = array("codigo" => "01", "nombre" => "Alicuota Normal", "prc" => 0);
					$datos_conexion['impuestos'][] = array("codigo" => "02", "nombre" => "Alicuota Reducida", "prc" => 0);
					$datos_conexion['impuestos'][] = array("codigo" => "03", "nombre" => "Alicuota Extendida", "prc" => 0);
				}

				file_put_contents('../datos_conexion.json', json_encode($datos_conexion));

				$resultado_pidpadre = true;
				try {
					$query = " SELECT pidpadre FROM opermv LIMIT 1 ";
					$resultado_consulta_pidpadre = $objeto_conexion->general_query($bd_conexion_cliente, $query);
				} catch (Exception $e) {
					$query = " ALTER TABLE opermv ADD pidpadre varchar(12) AFTER pid ";
					$resultado_pidpadre = $objeto_conexion->general_query($bd_conexion_cliente, $query);
				}

				if ($resultado_pidpadre) {
					$resultado_imprimircuenta = true;
					try {
						$query = " SELECT ImprimirCuenta FROM operti LIMIT 1 ";
						$resultado_consulta_imprimircuenta = $objeto_conexion->general_query($bd_conexion_cliente, $query);
					} catch (Exception $e) {
						$query = " ALTER TABLE operti ADD ImprimirCuenta TINYINT(4) NOT NULL DEFAULT '0' ";
						$resultado_imprimircuenta = $objeto_conexion->general_query($bd_conexion_cliente, $query);
					}

					if ($resultado_imprimircuenta) {
						$script = "<script>";

						foreach ($datos_conexion['impuestos'] as $imp) {
							$prc = number_format($imp['prc'], 0);
							$nombre = str_ireplace(array(1, 2, 3, 4, 5, 6, 7, 8, 9, '%', '&'), '', $imp['nombre']);
							$script .= "$('#lista_impuestos_detalle').append(`<div>" . $nombre . " " . $prc . "%<input type='text' class='form-control alicuotas' id='ali_reducida_" . $imp['codigo'] . "' readonly/></div>`);";
						}

						if ($datos_config['monedafactor'] > 0) {
							$script .= "
								$('#div_total_moneda_extranjera').show();
							";
						}

						$script .= "
							$('#dividemultiplica').val(" . $datos_config['dividemultiplica'] . ");
							$('#monedafactor').val(" . $datos_config['monedafactor'] . ");
							$('.simbolo_moneda_nacional').html('" . $datos_config['monnacsimb'] . "');
							$('.simbolo_moneda_extranjera').html('" . $datos_config['monexsimb'] . "');
							$('#ocultar_integrados').val('" . $ocultar_integrados . "');
							$('#no_editar_integrados').val('" . $no_editar_integrados . "');
							$('#agrupar_mesas').val('" . $agrupar_mesas_por_ubicacion . "');
							$('#cuentas_compartidas').val('" . $cuentas_compartidas_entre_vendedores . "');
							$('#cod_empresa').val('" . $cod_empresa . "');
							$('#cod_agencia').val('" . $cod_agencia . "');
							$('#cod_cliente').val('" . $cod_cliente . "');
							$('#cod_zona').val('" . $cod_zona . "');
							$('#cod_deposito').val('" . $cod_deposito . "');
							$('#recargo').val('" . $recargo . "');
							$('#vence').html(" . json_encode($datos['vencimiento']) . ");
							$('#pw_supervisor').val('" . $datos['pw_supervisor'] . "');
							$('#tipo_doc').val('" . $tipo_doc . "');
							$('#no_tiene_existencia').val('" . $no_tiene_existencia . "');
						</script>
					";

						echo $script;

					} else {
						echo "<i class='fa fa-times'></i> No se pudo crear el campo ImprimirCuenta en OPERTI";
					}
				} else {
					echo "<i class='fa fa-times'></i> No se pudo crear el campo PIDPADRE en OPERMV";
				}
			} else {
				echo "<i class='fa fa-times'></i> No se encuentra BD";
			}
		} else {
			echo "<i class='fa fa-times'></i> Error de conexion";
		}

	}

	echo "
			<script>
				$('#host').val('" . $host . "');
				$('#user').val('" . $user . "');
				$('#pw').val('" . $pw . "');
				$('#bd').val('" . $bd . "');
				lista_vendedores();
			</script>
		";

	@mysqli_close($conexion);

} else {
	echo "
	<script>
		$('#msj_login').html('Archivo de conexion no existe');
	</script>";
}


?>