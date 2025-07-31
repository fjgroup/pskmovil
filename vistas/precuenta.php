<?php
/*
	require "./modelos/modelo_conexion.php";
	$conexion = new conexion();
	$query = "SELECT * FROM config2";
	$resultado = $conexion->query('cliente',$query);
	$datos_config = mysqli_fetch_array($resultado);
*/

$file = file_get_contents('../datos_conexion.json');
$file = json_decode($file, true);

include ("../modelos/modelo_crud.php");
$objeto_conexion = new crud();
$bd_conexion = "cliente";

if (empty($file['nombre_empresa'])) {
	$file['nombre_empresa'] = "NOMBRE DE EMPRESA";
}
if (empty($file['cm_ancho_precuenta'])) {
	$file['cm_ancho_precuenta'] = 8;
}

$resultado_imprimircuenta = true;
try {
	$query = " SELECT ImprimirCuenta FROM operti LIMIT 1 ";
	$resultado_consulta_imprimircuenta = $objeto_conexion->general_query($bd_conexion, $query);
} catch (Exception $e) {
	$query = " ALTER TABLE operti ADD ImprimirCuenta TINYINT(4) NOT NULL DEFAULT '0' ";
	$resultado_imprimircuenta = $objeto_conexion->general_query($bd_conexion, $query);
}


$mesa = "E" . $_POST['input_mesa_precuenta'];
//if ($resultado_imprimircuenta) {
$query = " UPDATE operti SET ImprimirCuenta = '1' WHERE 
		documento = '" . $mesa . "' AND 
		tipodoc = 'ESP'
	";

$registro_operti = $objeto_conexion->general_query($bd_conexion, $query);
if ($registro_operti) {
} else {
	echo "<i class='fa fa-times'></i><br>ERROR al actualizar el campo de enviar a imprimir la pre-cuenta<br>
			<b>Detalle:</b> " . $GLOBALS['error_mysql'];
}
?>

<!DOCTYPE html>
<html>

<head>
	<title>PRE-CUENTA</title>
	<style>
		* {
			font-size: 14px;
		}

		html,
		body {
			margin: 0px;
			padding: 0px;
		}

		p {
			margin: 0px;
			margin-bottom: 5px;
		}
	</style>
</head>

<body>
	<div id="contenedor" style="max-width: <?php echo $file['cm_ancho_precuenta']; ?>cm;margin:0.1cm;">
		<p style="text-align: center;margin-bottom:7px;"><b>
				<?php echo strtoupper($file['nombre_empresa']); ?>
			</b></p>
		<p>Mesa: <b>
				<?php echo strtoupper($_POST['input_mesa_precuenta']); ?>
			</b></p>
		<p>Fecha:
			<?php echo date('d/m/Y'); ?>
		</p>
		<p>Cliente:
			<?php echo strtoupper($_POST['input_cliente_precuenta']); ?>
		</p>
		<p>
			<?php echo strtoupper($_POST['input_vendedor_precuenta']); ?>
		</p>
		<hr>
		<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
			<tbody>
				<?php

				$array_productos = array();
				if (!empty($_POST['resumir_lineas_precuenta'])) {
					$array_productos = array();
					foreach ($_POST['productos'] as $producto) {
						$datos = explode('|', $producto);
						$codigo = $datos[0];
						if (!isset($array_productos[$codigo])) {
							$array_productos[$codigo]['datos'] = $producto;
							$array_productos[$codigo]['cantidad'] = $datos[12];
						} else {
							$array_productos[$codigo]['cantidad'] += $datos[12];
						}
					}
				} else {
					$x = 0;
					foreach ($_POST['productos'] as $producto) {
						$datos = explode('|', $producto);
						$cant = $datos[12];
						if ($cant == 1) {
							$array_productos[$x]['datos'] = $producto;
							$array_productos[$x]['cantidad'] = 1;
							$x++;
						} else {
							for ($i = 0; $i < $cant; $i++) {
								$array_productos[$x]['datos'] = $producto;
								$array_productos[$x]['cantidad'] = 1;
								$x++;
							}
						}
					}
				}

				$x = 0;
				$total_descuento = 0;
				$total_bruto = 0;
				foreach ($array_productos as $producto) {
					$cantidad = $producto['cantidad'];
					$datos = explode('|', $producto['datos']);
					$codigo = $datos[0];
					$nombre = $datos[3];
					$precioneto = $datos[4];
					$preciofin = $datos[5];
					$impuesto = $datos[6];

					$codpadre = '';
					if (trim($datos[14]) != '') {
						$codpadre = $datos[14];
					}

					if (empty($codpadre)) {

						$prc_descuento = 0;
						$dsctounit = 0;
						if (!empty($datos[15])) {
							$prc_descuento = $datos[15];
							$dsctounit = $precioneto * ($prc_descuento / 100) * $cantidad;
							$total_descuento += $dsctounit;
						}

						$notas = '';
						//if( !empty($datos[16]) ){
						//$notas = $datos[16];
						//}
						if (isset($datos[16]) and $datos[16] != 'undefined') {
							$notas = $datos[16];
						} else {
							$notas = "";
						}
						$total_bruto += $precioneto * $cantidad;
						?>
						<tr>
							<td style="vertical-align: top;padding-bottom:5px;">
								<?php echo $nombre; ?>
								<!-- <?php //if( !empty($notas) ){ echo "<b>Notas: </b>".$notas; } ?> -->
							</td>
							<td style="vertical-align: top;padding-bottom:5px;text-align: right;">
								<?php echo number_format($precioneto, 2, ',', '.'); ?>&nbsp;x&nbsp;
								<?php echo number_format($cantidad, 3, ',', '.'); ?>
							</td>
							<td style="vertical-align: top;padding-bottom:5px;padding-left:6px;text-align: right;">
								<?php echo number_format(($precioneto * $cantidad), 2, ',', '.'); ?>
							</td>
						</tr>
						<?php if ($prc_descuento > 0) { ?>
							<tr>
								<td style="text-align: right;padding-right:2px;"><b>Dscto:</b></td>
								<td style="text-align: right;">
									<?php echo number_format($prc_descuento, 2, ',', '.') . '%'; ?>
								</td>
								<td style="text-align: right;">
									<?php echo '-' . number_format($dsctounit, 2, ',', '.'); ?>
								</td>
							</tr>
						<?php } ?>
						<?php
						$x++;
					}
				}
				?>
			</tbody>
		</table>
		<hr>
		<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
			<tbody>
				<tr>
					<td style="vertical-align: top;padding-bottom:5px;">SUBTOTAL:</td>
					<td style="text-align: right;vertical-align: top;padding-bottom:5px;">
						<?php echo number_format($total_bruto, 2, ',', '.'); ?>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top;padding-bottom:5px;">DESCUENTO:</td>
					<td style="text-align: right;vertical-align: top;padding-bottom:5px;">
						<?php echo number_format($total_descuento, 2, ',', '.'); ?>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top;padding-bottom:5px;">TOTAL NETO:</td>
					<td style="text-align: right;vertical-align: top;padding-bottom:5px;">
						<?php echo number_format($_POST['subtotal_precuenta'], 2, ',', '.'); ?>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top;padding-bottom:5px;">IMPUESTO:</td>
					<td style="text-align: right;vertical-align: top;padding-bottom:5px;">
						<?php echo number_format($_POST['total_impuesto_precuenta'], 2, ',', '.'); ?>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top;padding-bottom:5px;font-size:16px;">TOTAL FINAL:</td>
					<th style="text-align: right;vertical-align: top;padding-bottom:5px;font-size:16px;">
						<?php echo number_format($_POST['precio_total_precuenta'], 2, ',', '.'); ?>
					</th>
				</tr>
				<tr>
					<td style="vertical-align: top;padding-bottom:5px;font-size:16px;">TOTAL REF:</td>
					<th style="text-align: right;vertical-align: top;padding-bottom:5px;font-size:16px;">
						<?php echo number_format($_POST['total_moneda_extranjera_precuenta'], 2, ',', '.'); ?>
					</th>
				</tr>
			</tbody>
		</table>
		<hr>
		<div style="text-align: center;">
			<p>Si desea agregar propina a esta cuenta favor marcar el porcentaje deseado</p>
			<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
				<tr>
					<td style="text-align: center;">
						<b>10%</b>&nbsp;
						<?php echo number_format(($_POST['subtotal_precuenta'] * 0.10), 2, ',', '.'); ?><br>
						<b style="font-size: 16px;">
							<?php echo number_format($_POST['subtotal_precuenta'] + ($_POST['subtotal_precuenta'] * 0.10), 2, ',', '.'); ?>
						</b>
					</td>
					<td style="text-align: center;">
						<b>15%</b>&nbsp;
						<?php echo number_format(($_POST['subtotal_precuenta'] * 0.15), 2, ',', '.'); ?><br>
						<b style="font-size: 16px;">
							<?php echo number_format($_POST['subtotal_precuenta'] + ($_POST['subtotal_precuenta'] * 0.15), 2, ',', '.'); ?>
						</b>
					</td>
					<td style="text-align: center;">
						<b>20%</b>&nbsp;
						<?php echo number_format(($_POST['subtotal_precuenta'] * 0.20), 2, ',', '.'); ?><br>
						<b style="font-size: 16px;">
							<?php echo number_format($_POST['subtotal_precuenta'] + ($_POST['subtotal_precuenta'] * 0.20), 2, ',', '.'); ?>
						</b>
					</td>
				</tr>
			</table>
			<div style="margin-top:10px;">"El servicio es Voluntario"</div>
			<h4 style="margin-top:5px;"><u>*GRACIAS POR PREFERIRNOS*</u></h4>
		</div>
	</div>
</body>

</html>

<script>
	window.echo();
</script>