<?php

echo "
	<small>
	===========================
	<br>
	Fecha: 03/May/2007 
	<br>
	Hora: 15:07:45
	<br><br>
	Cliente: Cliente Contado
	<br>
	Vendedor: Norbert
	<br>
	-----------------------------------------------------
	</small>
	<br>
	<table class='table'>
		<thead>
			<th>Cant.</th><th>Articulo</th>
		</thead>
		<tbody>
";

$array_productos = array();
if (!empty($_POST['resumir_lineas'])) {
	$array_productos = array();
	foreach ($_POST['productos_array'] as $producto) {
		$datos = explode('|', $producto);
		$codigo = $datos[0];
		if (!isset($array_productos[$codigo])) {
			$array_productos[$codigo]['datos'] = $producto;
			$array_productos[$codigo]['cantidad'] = 1;
		} else {
			$array_productos[$codigo]['cantidad'] += 1;
		}
	}
} else {
	$x = 0;
	foreach ($_POST['productos_array'] as $producto) {
		$array_productos[$x]['datos'] = $producto;
		$array_productos[$x]['cantidad'] = 1;
		$x++;
	}
}




foreach ($array_productos as $p) {

	$datos = explode('|', $p);

	if (isset($datos[7])) {


		$cantidad = $datos[7];
		$codigo = $datos[0];
		$nombre = $datos[3];

		if (isset($datos[8])) {
			$notas = $datos[8];
		} else {
			$notas = "";
		}

		echo "		
				<tr>
					<td>" . $cantidad . "</td>
					<td>
					" . $nombre . "<br>
					<small>" . $notas . "</small>
					</td>
				</tr>
			";

	}

}

echo "
			</tbody>
		</table>
	";

?>