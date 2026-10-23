<?php
ini_set("default_charset", "UTF-8");
mb_internal_encoding("UTF-8");
$file = file_get_contents("../datos_conexion.json");
$datos_conexion = json_decode($file, true);
include ("../modelos/modelo_crud.php");
include ("../funciones/funciones.php");

$objeto_pedido = new crud();
$bd_conexion_cliente = "cliente";

$permitir_descuentos = isset($datos_conexion['permitir_descuentos']) ? $datos_conexion['permitir_descuentos'] : 'si';

echo "		
		<table class='table table-striped'>
			<thead>
				<tr>
					<th>Notas</th>";
if ($permitir_descuentos != 'no') {
	echo "<th>Dscto</th>";
}
echo "
					<th>Codigo</th>
					<th>Cant.</th>
					<th>Producto</th>
					<th>Total</th>
					<th class='text-center'>Eliminar</th>
				</tr>
			</thead>
			<tbody>	
";


if (isset($_POST['productos_array'])) {
	$array_productos = array();
	if (!empty($_GET['resumir_lineas'])) {
		$array_productos = array();
		foreach ($_POST['productos_array'] as $producto) {

			$datos = explode('|', $producto);
			$codigo = $datos[0];
			$unidad = $datos[9];
			$cod_hijo = $datos[14];
			$prc_descuento = 0;

			if (!empty($datos[15])) {
				$prc_descuento = $datos[15];
			}
			if (!isset($array_productos[$codigo . $unidad . $cod_hijo])) {
				$array_explo = explode('|', $producto);

				$array_productos[$codigo . $unidad . $cod_hijo]['datos'] = $producto;
				$array_productos[$codigo . $unidad . $cod_hijo]['cantidad'] = number_format($array_explo[12], 3, '.', '');
			} else {

				$dat = explode('|', $array_productos[$codigo . $unidad . $cod_hijo]['datos']);
				if (empty($dat[15])) {
					$dat[15] = 0;
				}
				$dat[15] += $prc_descuento;
				$dat = implode('|', $dat);
				$array_explo = explode('|', $producto);
				$array_productos[$codigo . $unidad . $cod_hijo]['datos'] = $dat;
				$array_productos[$codigo . $unidad . $cod_hijo]['cantidad'] += number_format($array_explo[12], 3, '.', '');
			}

		}
		foreach ($array_productos as $codigo => $value) {
			$datos = explode('|', $value['datos']);
			$prc_descuento = 0;
			if (!empty($datos[15])) {
				$prc_descuento = $datos[15];
				if ($value['cantidad'] > 0) {
					$prc_descuento = $prc_descuento / $value['cantidad'];
					$datos[15] = $prc_descuento;
					$dat = implode('|', $datos);
					$array_productos[$codigo . $unidad]['datos'] = $dat;
				}
			}
		}
	} else {
		$x = 0;
		foreach ($_POST['productos_array'] as $producto) {

			$dat = explode('|', $producto);
			if ($dat[12] == 1) {
				$array_productos[$x]['datos'] = $producto;
				$array_productos[$x]['cantidad'] = number_format($dat[12], 3, '.', '');
				$x++;
			} else {
				$cant = $dat[12];
				for ($i = 0; $i < $cant; $i++) {
					$array_productos[$x]['datos'] = $producto;
					$array_productos[$x]['cantidad'] = number_format(1, 3, '.', '');
					$x++;
				}
			}
		}
	}


	$array_impuestos = array();
	$monto_total = 0;
	$monto_neto = 0;
	$monto_impuesto = 0;
	$total_bruto = 0;
	$subtotal = 0;
	$monto_recargo = 0;
	$total_descuento = 0;
	//print_r($array_productos);
	foreach ($array_productos as $value) {

		$datos = explode('|', $value['datos']);

		if (isset($datos[8]) && isset($value['cantidad'])) {


			$prc_recargo = $datos_conexion['recargo'] / 100;
			$codpadre = $datos[14];
			//$pid = empty($codpadre) ? trim($datos[13]) : $codpadre;
			$pid = trim($datos[13]);
			$pidhijo = trim($datos[0]); // GDM - el codhijo para validar cuando pid viene de fox, ya que viene con el mismo valor
			$cantidad = $value['cantidad'];
			$cantidad_agrupada = $datos[11];

			$codhijo = $datos[0];
			//$precio_unitario_agrupado = $datos[10];
			$precio_unitario_agrupado = empty($codpadre) ? $datos[10] : 0;
			$nombre_unidad_agrupado = $datos[9];
			$se_guardo = $datos[8];
			//if (empty($codpadre)) {
				$query = " SELECT impresion FROM comandasmv WHERE idopermv = '" . $pid . "' AND tipodoc = 'ESP' LIMIT 1";
			//} else {
			//	$query = " SELECT se_imprimio FROM opermv WHERE concat(codhijo,pid) = '" . $codhijo . $pid . "' LIMIT 1";
			//}

			$consulta = $objeto_pedido->general_query($bd_conexion_cliente, $query);
			$resultado = mysqli_fetch_array($consulta);
			if (!empty($resultado)) {
				$se_comando = 1;
			} else {
				$se_comando = 0;
			}
			$se_guardo = $se_comando;
			if (strpos($datos[6], '-') > -1) {
				$imp = explode('-', $datos[6]);
				$impuesto = $imp[1] / 100;
				$impuesto_prc = $imp[1];
			} else {
				$impuesto = $datos[6] / 100;
				$impuesto_prc = $datos[6];
			}

			$prc_descuento = 0;
			if (isset($datos[15])) {
				$prc_descuento = $datos[15];
			}

			$codigo = $datos[0];
			$codgrupo = $datos[1];
			$codsubgrupo = $datos[2];
			$nombre = $datos[3];

			$precio_unit = empty($codpadre) ? round($datos[4], 2) : 0;
			if ($prc_descuento > 0) {
				//$dscto_unit = $datos[4] * ($prc_descuento / 100);
				//$precioneto = ($datos[4] - ($dscto_unit)) * $cantidad;
				$dscto_unit = $precio_unit * ($prc_descuento / 100);
				$precioneto = ($precio_unit - ($dscto_unit)) * $cantidad;
				$total_descuento += $dscto_unit * $cantidad;
			} else {
				//$precioneto = $datos[4] * $cantidad;
				$precioneto = $precio_unit * $cantidad;
			}

			//$total_bruto += $datos[4] * $cantidad;
			$total_bruto += $precio_unit * $cantidad;

			if ($nombre_unidad_agrupado != "") {
				if ($se_guardo == 0) {
					$nombre .= " (" . $nombre_unidad_agrupado . ")";
				}
			}

			if ($precio_unitario_agrupado != "" and $nombre_unidad_agrupado != '') {
				$precioneto = $precio_unitario_agrupado * $cantidad;
			}

			$preciofin = ($precioneto * $impuesto) + $precioneto;

			$notas = "";
			if (isset($datos[16])) {
				$notas = $datos[16];
			}

			$estacion = "PSK";
			if (isset($datos[17])) {
				$estacion = $datos[17];
			}
			foreach ($datos_conexion['impuestos'] as $imp) {
				if ($impuesto_prc == $imp['prc']) {
					if (empty($array_impuestos[$imp['codigo']])) {
						$array_impuestos[$imp['codigo']] = 0;
					}
					$array_impuestos[$imp['codigo']] += ($precioneto * $impuesto);
				}
			}

			$monto_recargo = $monto_recargo + ($precioneto * $prc_recargo);
			$subtotal = $subtotal + ($precioneto + ($precioneto * $impuesto));
			$monto_impuesto = $monto_impuesto + ($precioneto * $impuesto);
			$monto_neto = $monto_neto + $precioneto;
			$monto_total = $monto_total + $preciofin + ($precioneto * $prc_recargo);
			$monto_total_ext = $monto_total;

			if (!empty($datos_conexion['monedafactor'])) {
				if ($datos_conexion['dividemultiplica'] == 1) {
					$monto_total_ext = $monto_total_ext / $datos_conexion['monedafactor'];
				} else {
					$monto_total_ext = $monto_total_ext * $datos_conexion['monedafactor'];
				}
			}

			if (!empty($codpadre)) {
				echo "<tr class='es_componente' pid='" . $pid . "' pidhijo='" . $pidhijo . "'>";
			} else {
				echo "<tr pid='" . $pid . "' pidhijo='" . $pidhijo . "'>";
			}

			echo "							
						<td><i class='fa fa-edit'></i></td>";
			if ($permitir_descuentos != 'no') {
				echo "<td><i class='fa fa-percent'></i></td>";
			}
			echo "
						<td>" . $codigo . "</td>
						<td>" . $cantidad . "</td>
						<td>" . $nombre . "</td>
						<td>" . number_format($precioneto, 2, ',', '.') . "</td>
						<td class='text-center'><i class='fa fa-times clase-eliminar-producto' se_comando='" . $se_comando . "' se_guardo='" . $se_guardo . "' pid='" . $pid . "' pidhijo='" . $pidhijo . "'></i></td>
					</tr>		
				";
		}
	}

	echo "
				</tbody>
			</table>
	";

	$script = "<script>";

	$totalimpuesto = 0;
	$script .= "$('.alicuotas').val(" . json_encode(number_format(0, 2, ',', '.')) . ");";
	foreach ($array_impuestos as $codigo_imp => $totimp) {
		$totalimpuesto += $totimp;
		$script .= "$('#ali_reducida_" . $codigo_imp . "').val(" . json_encode(number_format($totimp, 2, ',', '.')) . ");";
	}
	$script .= "
            $('#recargo_total').val(" . json_encode(number_format($monto_recargo, 2, ',', '.')) . ");
            $('#total_descuento').val(" . json_encode(number_format($total_descuento, 2, ',', '.')) . ");
            $('#subtotal').val(" . json_encode(number_format($subtotal, 2, ',', '.')) . ");
            $('#precio_neto_total').val(" . json_encode(number_format($total_bruto, 2, ',', '.')) . ");
            $('.precio_total').val(" . json_encode(number_format($monto_total, 2, ',', '.')) . ");
            $('.precio_total_moneda_extranjera').val('" . number_format($monto_total_ext, 2, '.', '') . "');
            $('#total_impuesto_precuenta').val(" . $totalimpuesto . ");
            $('#precio_neto_total_precuenta').val(" . number_format($subtotal, 2, ',', '.') . ");
            $('#subtotal_precuenta').val(" . $monto_neto . ");
            $('#precio_total_precuenta').val(" . $monto_total . ");
            $('#total_moneda_extranjera_precuenta').val(" . $monto_total_ext . ");
            $('.precio_total_ref').val('REF.:" . number_format($monto_total_ext, 2, ',', '.') . "');
            ";

    $script .= "</script>";

    echo $script;
}
