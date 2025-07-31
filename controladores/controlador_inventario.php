<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
clearstatcache();

include("../modelos/modelo_crud.php");
include("../funciones/funciones.php");

$file = file_get_contents("../datos_conexion.json");
$datos_conexion = json_decode($file, true);

$objeto_inventario = new crud();
$bd_conexion = "cliente";

if ($_GET['accion'] == "mostrar_grupos") {
	if (isset($_POST['pagina'])) {
		$pagina = $_POST['pagina'];
	} else {
		$pagina = "0";
	}
	$select = "
    	grupos.*,
    	(SELECT COUNT(*) FROM subgrupos WHERE subgrupos.codigo = grupos.codigo LIMIT 1) AS total_subgrupos
    ";
	$from = "grupos";
	$where = "oculto_restaurant='0'";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, '', "LIMIT " . $pagina . "," . $datos_conexion['cantidad_por_pagina'] . " ");
	$x = 0;
	while ($grupos = mysqli_fetch_array($consulta)) {
		$grupos = LimpiarArreglo($grupos);
		$grupos['rutafoto'] = trim($grupos['rutafoto']);
		$ruta_img = explode("/", str_replace('\\', '/', strtolower($grupos['rutafoto'])));
		$basename = count($ruta_img) - 1;
		$ruta_img = "imagenes/" . $ruta_img[$basename];
		$grupos['nombre'] = ucfirst(strtolower(limpiarCaracteresEspeciales($grupos['nombre'])));

		if ($datos_conexion['usar_subgrupos'] == 'si' and $grupos['total_subgrupos'] > 0) {
			$funcion = "mostrar_subgrupos(" . json_encode($grupos['codigo']) . ")";
		} else {
			$funcion = "mostrar_articulos(" . json_encode($grupos['codigo']) . ")";
		}

		if ($grupos['rutafoto'] != "") {
			echo " <li onclick='" . $funcion . "'><img src='$ruta_img'/><div>" . $grupos['nombre'] . "</div></li> ";
		} else {
			echo " <li onclick='" . $funcion . "'>" . $grupos['nombre'] . "<div>" . $grupos['nombre'] . "</div></li> ";
		}
		$x++;
	}

	$paginado = "";

	if ($pagina != 0) {
		$pagina_anterior = $pagina - $datos_conexion['cantidad_por_pagina'];
		$paginado .= " <div onclick = 'mostrar_grupos(" . json_encode($pagina_anterior) . ")'> <i class = 'fa fa-angle-left'></i></div>";
	}
	if ($x == $datos_conexion['cantidad_por_pagina']) {
		$pagina_siguiente = $pagina + $datos_conexion['cantidad_por_pagina'];
		$paginado .= " <div onclick = 'mostrar_grupos(" . json_encode($pagina_siguiente) . ")' > <i class = 'fa fa-angle-right'></i></div> ";
	}

	echo " 
		<script> 
			$('.paginado').html(" . json_encode($paginado) . ");
		</script>
	";
}

if ($_GET['accion'] == "mostrar_subgrupos") {
	if (isset($_POST['pagina'])) {
		$pagina = $_POST['pagina'];
	} else {
		$pagina = 0;
	}

	if ($pagina == 0) {
		/*
		    SE CREA UN SUBRGUPO GENERICO DONDE 
		    APARECEN LOS ARTICULOS QUE NO TIENEN SUBGRUPOS
	    */
		echo " <li onclick='mostrar_articulos(" . json_encode($_POST['grupo']) . ")'>GENERICO<div>GENERICO</div></li> ";
		$cantidad_a_mostrar = $datos_conexion['cantidad_por_pagina'] - 1;
	} else {
		$cantidad_a_mostrar = $datos_conexion['cantidad_por_pagina'];
	}

	$select = "*";
	$from = "subgrupos";
	$where = " codigo = '" . $_POST['grupo'] . "' ";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, '', "LIMIT " . $pagina . "," . $cantidad_a_mostrar);
	$x = 0;

	while ($subgrupos = mysqli_fetch_array($consulta)) {
		$subgrupos = LimpiarArreglo($subgrupos);
		$subgrupos['nombre'] = ucfirst(strtolower(limpiarCaracteresEspeciales($subgrupos['nombre'])));

		echo " <li onclick='mostrar_articulos(" . json_encode($subgrupos['codigo']) . "," . json_encode($subgrupos['subcodigo']) . ")'>" . $subgrupos['nombre'] . "<div>" . $subgrupos['nombre'] . "</div ></li> ";

		$x++;
	}

	$paginado = "";

	if ($pagina != 0) {
		$pagina_anterior = $pagina - $datos_conexion['cantidad_por_pagina'];
		$paginado .= " <div onclick = 'mostrar_subgrupos(" . json_encode($_POST['grupo']) . "," . json_encode($pagina_anterior) . ")' > <i class = 'fa fa-angle-left'></i></div>";
	}

	if ($cantidad_a_mostrar == ($datos_conexion['cantidad_por_pagina'] - 1)) {
		$x++;
	}

	if ($x == $datos_conexion['cantidad_por_pagina']) {
		$pagina_siguiente = $pagina + $datos_conexion['cantidad_por_pagina'];
		$paginado .= " <div onclick = 'mostrar_subgrupos(" . json_encode($_POST['grupo']) . "," . json_encode($pagina_siguiente) . ")' > <i class = 'fa fa-angle-right'></i></div> ";
	}



	echo " 
		<script> 
			$('.paginado').html(" . json_encode($paginado) . ");
		</script>
	";
}

if ($_GET['accion'] == "mostrar_articulos") {

	$select = "monnacsimb";
	$consulta_config = $objeto_inventario->select($bd_conexion, $select, 'config', '', '', 'LIMIT 1');
	$datos_config = mysqli_fetch_array($consulta_config);
	$moneda = $datos_config['monnacsimb'];

	/*
		VZLA=58 Y PANAMA=507 NO USA IMPUESTOS MULTIPLES (LOS DEMÁS SI).
		HONDURAS=504 USA MULTIPLES IMPUESTOS PERO ES CONDICIONAL
	*/
	$select = "codpaisemp,activaimpuestomultiple";
	$consulta_config2 = $objeto_inventario->select($bd_conexion, $select, 'config2', '', '', 'LIMIT 1');
	$datos_config2 = mysqli_fetch_array($consulta_config2);

	if (isset($_POST['pagina'])) {
		$pagina = $_POST['pagina'];
	} else {
		$pagina = "0";
	}

	if (
		(trim($datos_config2['codpaisemp']) == '58') or
		(trim($datos_config2['codpaisemp']) == '507') or
		(trim($datos_config2['codpaisemp']) == '504' and $datos_config2['activaimpuestomultiple'] == 0)
	) {
		$select = "articulo.id_empresa, 
					articulo.agencia, 
					articulo.codigo, 
					articulo.grupo, 
					articulo.subgrupo, 
					articulo.nombre, 
					articulo.nombrecorto, 
					articulo.referencia, 
					articulo.marca, 
					articulo.unidad, 
					articulo.decimales,
					articulo.usaexist,
					articulo.para_web, 
					articulo.costo, 
					articulo.precio1, 
					articulo.preciofin1, 
					(articulo.existencia-ifnull((select sum(opermv.cantidad) as comprometido 
					from opermv where (tipodoc = 'ESP' or tipodoc = 'PED')
					and opermv.codigo = articulo.codigo),0)) AS existencia, 
					ifnull((select sum(opermv.cantidad) as comprometido 
					from opermv where (tipodoc = 'ESP' or tipodoc = 'PED')
					and opermv.codigo = articulo.codigo),0) as comprometida,
					articulo.origen,
					articulo.impuesto,
					articulo.usaserial,
					articulo.compuesto, 
					articulo.factor, 
					articulo.empaque, 
					articulo.unidadgrp, 
					articulo.cntgrp, 
					articulo.precio1grp, 
					articulo.impuesto1, 
					articulo.impuesto2, 
					articulo.rutafoto, 
					articulo.simbolo, 
					articulo.discont,
					grupos.oculto_restaurant
";
	} else {
		$select = "
			articulo.*,
			(
				SELECT 
				CONCAT(trim(impu_arti.impu_codigo),'-',trim(impu_arti.impu_prc))
				FROM impu_arti
				WHERE 
                impu_arti.impu_arti = articulo.codigo AND 
                impu_arti.impu_ventas = 1
                LIMIT 1
			) AS impuestos_multiples,
			 grupos.oculto_restaurant
		";
	}

	$from = "articulo JOIN grupos ON articulo.grupo = grupos.codigo";
	$where = "oculto_restaurant = '0' AND ";

	if (!empty($_POST['grupo'])) {
		if ($datos_conexion['usar_subgrupos'] == 'si' and $_POST['subgrupo'] != '') {
			$where = "articulo.grupo=  '" . $_POST['grupo'] . "' AND articulo.subgrupo = '" . $_POST['subgrupo'] . "' AND ";
		} else {
			$where = " articulo.grupo = '" . $_POST['grupo'] . "' AND ";
		}
	}

	$_POST['texto_busqueda'] = trim($_POST['texto_busqueda']);
	if ($_POST['texto_busqueda'] != '') {
		$where .= " (articulo.nombre LIKE '%" . $_POST['texto_busqueda'] . "%' OR 
		articulo.codigo LIKE '%" . $_POST['texto_busqueda'] . "%') AND ";
	}

	$where .= "articulo.discont='0' ";

	$order = "LIMIT $pagina," . $datos_conexion['cantidad_por_pagina'] . " ";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, '', $order);

	$x = 0;
	$no_tiene_existencia = !isset($datos_conexion['no_tiene_existencia']) ? 'no' : $datos_conexion['no_tiene_existencia'];

	while ($articulo = mysqli_fetch_array($consulta)) {
		$articulo = LimpiarArreglo($articulo);

		$articulo['impuesto1'] = number_format($articulo['impuesto1'], 0);

		$articulo['rutafoto'] = trim($articulo['rutafoto']);
		$ruta_img = explode("/", str_replace("\\", "/", trim(strtolower($articulo['rutafoto']))));
		$basename = count($ruta_img) - 1;
		$ruta_img = "imagenes/" . $ruta_img[$basename];
		if (trim($articulo['nombrecorto']) == "") {
			$articulo['nombrecorto'] = ucfirst(strtolower(limpiarCaracteresEspeciales($articulo['nombre'])));
		} else {
			$articulo['nombrecorto'] = ucfirst(strtolower(limpiarCaracteresEspeciales($articulo['nombrecorto'])));
		}
		$articulo['nombre'] = limpiarCaracteresEspeciales($articulo['nombre']);
		
		$se_guardo = "0";

		if (!empty($articulo['impuestos_multiples'])) {
			$articulo['impuesto1'] = $articulo['impuestos_multiples'];
			//echo "IMPUESTOS: ".$articulo['impuesto1'];
		}

		if ($datos_conexion['no_usar_impuestos'] == "si" or $_GET['cliente_exento'] == 3) {
			//SI NO USA IMPUESTOS, EL PRC DE IMPUESTO ES 0 CERO
			$articulo['impuesto1'] = 0;
		}

		$datos = $articulo['codigo'] . "|" . $articulo['grupo'] . "|" . $articulo['subgrupo'] . "|" .
			$articulo['nombre'] . "|" . $articulo['precio1'] . "|" . $articulo['preciofin1'] . "|" .
			$articulo['impuesto1'] . "||" . $se_guardo;

		$datos_agrupados = trim(ucfirst($articulo['unidad'])) . "|" . trim(ucfirst($articulo['unidadgrp'])) . "|" .
			$articulo['cntgrp'] . "|" . $articulo['precio1grp'];

			
			
		if ($articulo['rutafoto'] != "") {
			if ($no_tiene_existencia == 'si' && $articulo['usaexist'] == 1  && (int) $articulo['existencia'] <= 0) {

				echo "
						<li> <img src='" . $ruta_img . "'/>
						<div onclick='AmpliarImagen(" . $x . "," . json_encode($articulo['nombre']) . ")' >";
			} else {

				echo "
						<li >
							<img onclick='seleccionar_producto(" . json_encode($datos) . "," . json_encode($articulo['empaque']) . "," . json_encode($datos_agrupados) . "," . json_encode($articulo['compuesto']) . "," . json_encode($articulo['decimales']) . "," . json_encode($articulo['existencia']) . "," . json_encode($articulo['usaexist']) . ")' src='" . $ruta_img . "'/>
						<div onclick='AmpliarImagen(" . $x . "," . json_encode($articulo['nombre']) . ")'>";
			}
			if (!empty($articulo['nombrecorto'])) {
				echo $articulo['nombrecorto'] . "<br>";
			}

			if (!empty($datos_conexion['monedafactor']) && ($datos_conexion['monedafactor'] > 0)) {
				if ($datos_conexion['dividemultiplica'] == 1) {
					$precio_ext = $articulo['precio1'] / $datos_conexion['monedafactor'];
				} else {
					$precio_ext = $articulo['precio1'] * $datos_conexion['monedafactor'];
				}
				echo "<b>" . $moneda . "" . number_format($articulo['precio1'], 2, ',', '.') . "</b><br><b>REF: " . number_format($precio_ext, 2, ',', '.') . "</b></div>";
			} else {
				echo "<b>" . $moneda . "" . number_format($articulo['precio1'], 2, ',', '.') . "</b></div>";
			}
		} else {
			if (!empty($datos_conexion['monedafactor']) && ($datos_conexion['monedafactor'] > 0)) {
				if ($datos_conexion['dividemultiplica'] == 1) {
					$precio_ext = $articulo['precio1'] / $datos_conexion['monedafactor'];
				} else {
					$precio_ext = $articulo['precio1'] * $datos_conexion['monedafactor'];
				}
				if ($no_tiene_existencia == 'si' && $articulo['usaexist'] == 1  && (int) $articulo['existencia'] <= 0) {
					echo "<li>" . $articulo['nombrecorto'] . "<div style='background-color:red'><b>" . $moneda . "" . number_format($articulo['precio1'], 2, ',', '.') . "</b><br><b>REF: " . number_format($precio_ext, 2, ',', '.') . "</b></div></li>";
				} else {
					echo "<li onclick='seleccionar_producto(" . json_encode($datos) . "," . json_encode($articulo['empaque']) . "," . json_encode($datos_agrupados) . "," . json_encode($articulo['compuesto']) . "," . json_encode($articulo['decimales']) . "," . json_encode($articulo['existencia']) . "," . json_encode($articulo['usaexist']) . ")'>" . $articulo['nombrecorto'] . "<div><b>" . $moneda . "" . number_format($articulo['precio1'], 2, ',', '.') . "</b><br><b>REF: " . number_format($precio_ext, 2, ',', '.') . "</b></div></li>";
				}
			} else {
				if ($no_tiene_existencia == 'si' && $articulo['usaexist'] == 1  && (int) $articulo['existencia'] <= 0) {

					echo "<li>" . $articulo['nombrecorto'] . "<div  style='background-color:red'><b>" . $moneda . "" . number_format($articulo['precio1'], 2, ',', '.') . "</b></div></li>";
				} else {
					echo "<li onclick='seleccionar_producto(" . json_encode($datos) . "," . json_encode($articulo['empaque']) . "," . json_encode($datos_agrupados) . "," . json_encode($articulo['compuesto']) . "," . json_encode($articulo['decimales']) . "," . json_encode($articulo['existencia']) . "," . json_encode($articulo['usaexist']) . ")'>" . $articulo['nombrecorto'] . "<div><b>" . $moneda . "" . number_format($articulo['precio1'], 2, ',', '.') . "</b></div></li>";
				}
			}
		}
		$x++;
	}

	$paginado = "";

	if ($pagina != 0) {
		$pagina_anterior = $pagina - $datos_conexion['cantidad_por_pagina'];
		$paginado .= "<div onclick='mostrar_articulos(" . json_encode($_POST['grupo']) . "," . json_encode($_POST['subgrupo']) . "," . json_encode($pagina_anterior) . ")'><i class='fa fa-angle-left'></i></div>";
	}
	if ($x == $datos_conexion['cantidad_por_pagina']) {
		$pagina_siguiente = $pagina + $datos_conexion['cantidad_por_pagina'];
		$paginado .= "<div onclick='mostrar_articulos(" . json_encode($_POST['grupo']) . "," . json_encode($_POST['subgrupo']) . "," . json_encode($pagina_siguiente) . ")'><i class='fa fa-angle-right'></i></div>";
	}

	echo "
	<script>
		$('.paginado').html(" . json_encode($paginado) . ");
	</script>
	";
}

if ($_GET['accion'] == "mostrar_grupos_mesas") {

	if (isset($_POST['pagina'])) {
		$pagina = $_POST['pagina'];
	} else {
		$pagina = "0";
	}

	if ($_POST['filtro'] == "mias") {
		$where = " operti.vendedor='" . $_POST['vendedor'] . "' ";
		$filtro_join = "INNER";
	}

	if ($_POST['filtro'] == "disponibles") {
		$where = " isnull(operti.documento)";
		$filtro_join = "LEFT";
	}

	if ($_POST['filtro'] == "todas") {
		$where = "";
		$filtro_join = "LEFT";
	}

	$select = " idconsumos.ubicacion ";

	$from = " idconsumos ";
	$join = " 
		" . $filtro_join . " JOIN operti ON (`idconsumos`.id_consumo = substr(`operti`.documento,2)) and operti.tipodoc='" . $datos_conexion['tipo_doc'] . "'
		LEFT JOIN listvend ON operti.vendedor = listvend.codigo
	";
	$order = " GROUP BY idconsumos.ubicacion LIMIT $pagina," . $datos_conexion['cantidad_por_pagina'] . " ";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, $join, $order);

	$x = 0;
	while ($mesa = mysqli_fetch_array($consulta)) {

		$mesa = LimpiarArreglo($mesa);
		$ubicacion = $mesa['ubicacion'];
		if (empty($mesa['ubicacion'])) {
			$ubicacion = 'UBICACION GENERICA';
		}

		echo "
			<li onclick='mostrar_mesas(" . json_encode($_POST['pagina']) . "," . json_encode($_POST['filtro']) . "," . json_encode($mesa['ubicacion']) . ")'>" . $ubicacion . "</li>		
		";
		$x++;
	}

	$paginado = "";

	if ($pagina != 0) {
		$pagina_anterior = $pagina - $datos_conexion['cantidad_por_pagina'];
		$paginado .= "<div onclick='mostrar_grupos_mesas(" . json_encode($pagina_anterior) . "," . json_encode($_POST['filtro']) . ")'><i class='fa fa-angle-left'></i></div>";
	}
	if ($x == $datos_conexion['cantidad_por_pagina']) {
		$pagina_siguiente = $pagina + $datos_conexion['cantidad_por_pagina'];
		$paginado .= "<div onclick='mostrar_grupos_mesas(" . json_encode($pagina_siguiente) . "," . json_encode($_POST['filtro']) . ")'><i class='fa fa-angle-right'></i></div>";
	}

	echo "
	<script>
		$('.paginado').html(" . json_encode($paginado) . ");
	</script>
	";
}

if ($_GET['accion'] == "mostrar_mesas") {

	if (empty($_POST['ubicacion'])) {
		$_POST['ubicacion'] = '';
	}

	if (isset($_POST['pagina'])) {
		$pagina = $_POST['pagina'];
	} else {
		$pagina = "0";
	}
	$pagina_contar = $pagina + $datos_conexion['cantidad_por_pagina'];

	if ($_POST['filtro'] == "mias") {
		$where = " operti.vendedor='" . $_POST['vendedor'] . "' ";
		if ($datos_conexion['agrupar_mesas_por_ubicacion'] == 'si') {
			$where .= "AND idconsumos.ubicacion='" . $_POST['ubicacion'] . "' ";
		}
		$filtro_join = "INNER";
	}

	if ($_POST['filtro'] == "disponibles") {
		$where = " isnull(operti.documento)	";
		if ($datos_conexion['agrupar_mesas_por_ubicacion'] == 'si') {
			$where .= "AND idconsumos.ubicacion='" . $_POST['ubicacion'] . "' ";
		}
		$filtro_join = "LEFT";
	}

	if ($_POST['filtro'] == "todas") {
		$where = "";
		if ($datos_conexion['agrupar_mesas_por_ubicacion'] == 'si') {
			$where = " idconsumos.ubicacion='" . $_POST['ubicacion'] . "' ";
		}
		$filtro_join = "LEFT";
	}

	$select = " 
		operti.documento,
		operti.codcliente,
		operti.nombrecli,
		operti.rif as nrorif,
		operti.horadocum,
		operti.vendedor as codigo_vendedor,
		listvend.nombre as vendedor,
		idconsumos.capacidad,
		idconsumos.id_consumo,
		idconsumos.nombre_corto,
		idconsumos.descripcion,
		idconsumos.rutafoto 
	";

	$from = " idconsumos ";
	$join = " 
		" . $filtro_join . " JOIN operti ON (`idconsumos`.id_consumo = substr(`operti`.documento,2)) and operti.tipodoc='" . $datos_conexion['tipo_doc'] . "'
		LEFT JOIN listvend ON operti.vendedor = listvend.codigo
	";
	$order = "LIMIT $pagina," . $datos_conexion['cantidad_por_pagina'] . " ";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, $join, $order);
	$x = 0;
	while ($mesa = mysqli_fetch_array($consulta)) {

		$mesa = LimpiarArreglo($mesa);
		$mesa['rutafoto'] = trim($mesa['rutafoto']);
		$ruta_img = explode("/", str_replace('\\', '/', strtolower($mesa['rutafoto'])));
		$basename = count($ruta_img) - 1;
		$ruta_img = "imagenes/" . $ruta_img[$basename];

		if (!empty($mesa['nombre_corto'])) {
			$nombre_mesa = ucfirst(strtolower($mesa['nombre_corto']));
		} else {
			$nombre_mesa = ucfirst(strtolower($mesa['descripcion']));
		}

		if (!empty($mesa['documento'])) {
			if ($mesa['documento'] != "") {
				$ocupado = "style='background-color:red;'";

				if ($mesa['nombrecli'] == "") {
					$cliente_mesa = "";
					$mesa['nombrecli'] = "Cliente contado";
				} else {
					$cliente_mesa = $mesa['nombrecli'];
					$nrorif_mesa = $mesa['nrorif'];
				}

				$hora_actual = date("H:i", strtotime($_GET['hora']));
				$tiempo_total = calcular_tiempo_trasnc($hora_actual, $mesa['horadocum']);
			} else {
				$ocupado = "style='background-color:#0064C8;'";
			}
		} else {
			$ocupado = "style='background-color:#0064C8;'";
			$cliente_mesa = "";
			$mesa['nombrecli'] = "Cliente contado";
			$tiempo_total = "";
		}


		if ($mesa['rutafoto'] != "") {
			echo "
			<li onclick='seleccionar_mesa(" . json_encode($mesa['id_consumo']) . "," . json_encode($mesa['codcliente']) . "," . json_encode($mesa['nombrecli']) . "," . json_encode($mesa['codigo_vendedor']) . "," . json_encode($mesa['nrorif']) . ")'><img src='" . $ruta_img . "'/>
			<div " . $ocupado . "><b>" . $nombre_mesa . "</b><br>
			";

			if (!empty($mesa['vendedor'])) {
				echo "
					<small>
						" . $mesa['vendedor'] . "<br>
						Capacidad: " . $mesa['capacidad'] . "<br>" .
					$tiempo_total . "<br>" . $cliente_mesa . "
					</small>
				";
			}

			echo "
			</div>
			</li>";
		} else {
			echo "
			<li onclick='seleccionar_mesa(" . json_encode($mesa['id_consumo']) . "," . json_encode($mesa['codcliente']) . "," . json_encode($mesa['nombrecli']) . "," . json_encode($mesa['codigo_vendedor']) . "," . json_encode($mesa['nrorif']) . ")'>
			<small>
			";

			if (!empty($mesa['vendedor'])) {
				echo
				$mesa['vendedor'] . "<br>
					Capacidad: " . $mesa['capacidad'] . "<br>" .
					$tiempo_total . "<br>" . $cliente_mesa . "
				";
			}

			echo "
			</small>
			<div " . $ocupado . ">" . $nombre_mesa . "</div>
			</li>";
		}

		$x++;
	}

	$paginado = "";

	if ($pagina != 0) {
		$pagina_anterior = $pagina - $datos_conexion['cantidad_por_pagina'];
		$paginado .= "<div onclick='mostrar_mesas(" . json_encode($pagina_anterior) . "," . json_encode($_POST['filtro']) . "," . json_encode($_POST['ubicacion']) . ")'><i class='fa fa-angle-left'></i></div>";
	}
	if ($x == $datos_conexion['cantidad_por_pagina']) {
		$pagina_siguiente = $pagina + $datos_conexion['cantidad_por_pagina'];
		$paginado .= "<div onclick='mostrar_mesas(" . json_encode($pagina_siguiente) . "," . json_encode($_POST['filtro']) . "," . json_encode($_POST['ubicacion']) . ")'><i class='fa fa-angle-right'></i></div>";
	}

	echo "
	<script>
		$('.paginado').html(" . json_encode($paginado) . ");
	</script>
	";
}


if ($_GET['accion'] == "obtener_integrados") {

	if (!empty($_GET['codigo'])) {

		$query = "
			SELECT 
			articomp.codigo,
			articulo.grupo,
			articulo.subgrupo,
			articomp.nombre,
			articulo.precio1,
			articulo.preciofin1,
			articulo.impuesto1,
			articulo.empaque,
			articulo.compuesto,
			articomp.cantidad,
			articulo.usaexist,
			articulo.origen,
			(articulo.existencia-articulo.comprometido) as existencia
			FROM articomp
			INNER JOIN articulo ON articomp.codigo = articulo.codigo
			WHERE articomp.compuesto = '" . $_GET['codigo'] . "'
		";

		$registro_operti = $objeto_inventario->general_query($bd_conexion, $query);

		echo "
			<table class='table table-striped table-sm'>
				<thead>
					<th style='width:50px;'></th>
					<th>Codigo</th>
					<th>Nombre</th>					
					<th style='width:140px;' class='text-center'>Cantidad</th>
				</thead>
				<tbody>
		";

		$x = 0;
		while ($articomp = mysqli_fetch_array($registro_operti)) {

			$articomp = LimpiarArreglo($articomp);

			$se_guardo = "0";

			if ($datos_conexion['no_usar_impuestos'] == "si" or $_GET['cliente_exento'] == 3) {
				//SI NO USA IMPUESTOS, EL PRC DE IMPUESTO ES 0 CERO
				$articomp['impuesto1'] = 0;
			}

			//LOS COMPONENTES NO DEBEN TENER PRECIO
			$articomp['precio1'] = 0;
			$articomp['preciofin1'] = 0;
			$articomp['impuesto1'] = 0;

			$datos = $articomp['codigo'] . "|" . $articomp['grupo'] . "|" . $articomp['subgrupo'] . "|" .
				$articomp['nombre'] . "|" . $articomp['precio1'] . "|" . $articomp['preciofin1'] . "|" .
				$articomp['impuesto1'] . "||" . $se_guardo;

			echo "
				<tr datos_art_comp='" . json_encode($datos) . "'>
					<td class='text-center'><button class='btn btn-danger btn-xs btn_delete_art_comp'><i class='fa fa-trash'></i></button></td>
					<td>" . $articomp['codigo'] . "</td>
					<td>" . $articomp['nombre'] . "</td>
					<td>
						<div class='input-group' style='width:130px;'>
							<div class='input-group-addon btn-primary btn_art_comp_minus'>
								<i class='fa fa-minus'></i>
							</div>
							<input type='text' class='form-control art_comp_cantidad' value='" . number_format($articomp['cantidad'], 2, '.', '') . "'/>
							<div class='input-group-addon btn-primary btn_art_comp_plus'>
								<i class='fa fa-plus'></i>								
							</div>
						</div>
					</td>
				</tr>
			";
			$x++;
		}

		echo "
				</tbody>
			</table>
		";
	} else {
		echo "Seleccione un articulo";
	}
}

if ($_GET['accion'] == "ver_cuenta_mesa") {

	$mesa = "E" . $_POST['mesa'];

	$select = "*";
	$from = "opermv";
	$where = "documento='" . $mesa . "' and tipodoc='" . $datos_conexion['tipo_doc'] . "'";
	$order = " ORDER BY opermv.fechayhora ASC ";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, '', $order);
	$datos = "";
	while ($cuenta = mysqli_fetch_array($consulta)) {

		$codigo = $cuenta['codigo'];
		$codpadre = '';
		
		
		if (trim($cuenta['codhijo']) != '') {
			$codigo = $cuenta['codhijo'];
			$codpadre = $cuenta['pid'];
		}
		

		$datos .=
			trim($codigo) . "|" .
			trim($cuenta['grupo']) . "|" .
			trim($cuenta['subgrupo']) . "|" .
			trim($cuenta['nombre']) . "|" .
			trim($cuenta['preciounit']) . "|" .
			trim($cuenta['preciofin']) . "|" .
			trim($cuenta['timpueprc']) . "|" .
			trim($cuenta['pidpadre']) . "|" .
			trim($cuenta['se_guardo']) . "|" .
			trim($cuenta['unidad']) . "|" .
			trim($cuenta['preciounit']) . "|" .
			trim($cuenta['cntagrupada']) . "|" .
			trim(number_format($cuenta['cantidad'], 2, '.', '')) . "|" .
			trim($cuenta['pid']) . "|" .
			trim($codpadre) . "|" .
			trim($cuenta['dsctoprc']) . "|" .
			trim($cuenta['notas']) . "|" .
			trim($cuenta['estacion']) . ";";
	}

	echo $datos;
}

if ($_GET['accion'] == "consultar_mesa") {

	$mesa = "E" . $_POST['mesa'];
	
	$select = " 
		operti.codcliente,
		operti.nombrecli,
		operti.rif AS nrorif,
		operti.vendedor AS codigo_vendedor,
		opermv.pid AS pid_opermv,opermv.* 
	";
	$from = " operti ";
	$where = " operti.documento='" . $mesa . "' and operti.tipodoc='" . $datos_conexion['tipo_doc'] . "' ";

	if ($datos_conexion['cuentas_compartidas_entre_vendedores'] == 'no' and empty($mesa)) {
		$where .= " and operti.vendedor = '" . $_POST['vendedor'] . "' ";
	}

	$join = " INNER JOIN opermv ON operti.documento=opermv.documento ";
	$consulta = $objeto_inventario->select($bd_conexion, $select, $from, $where, $join, '');
	$datos = "";
	while ($cuenta = mysqli_fetch_array($consulta)) {

		$codigo = $cuenta['codigo'];
		$codpadre = '';
		if (trim($cuenta['codhijo']) != '') {
			$codigo = $cuenta['codhijo'];
			$codpadre = $cuenta['codigo'];
		}

		$datos .=
			trim($codigo) . "|" .
			trim($cuenta['grupo']) . "|" .
			trim($cuenta['subgrupo']) . "|" .
			trim($cuenta['nombre']) . "|" .
			trim($cuenta['preciounit']) . "|" .
			trim($cuenta['preciofin']) . "|" .
			trim($cuenta['timpueprc']) . "|" .
			trim($cuenta['pidpadre']) . "|" .
			trim($cuenta['se_guardo']) . "|" .
			trim($cuenta['unidad']) . "|" .
			trim($cuenta['preciounit']) . "|" .
			trim($cuenta['cntagrupada']) . "|" .
			trim(number_format($cuenta['cantidad'], 2, '.', '.')) . "|" .
			trim($cuenta['pid_opermv']) . "|" .
			trim($codpadre) . "|" .
			trim($cuenta['dsctoprc']) . "|" .
			trim($cuenta['notas']) . "|" .
			trim($cuenta['estacion']) . ";";

		$codcliente = $cuenta['codcliente'];
		$nombrecli = $cuenta['nombrecli'];
		$codigo_vendedor = $cuenta['codigo_vendedor'];
		$nrorif = $cuenta['nrorif'];
	}

	if (!isset($codcliente)) {
		$codcliente = "";
	}
	
	if (!isset($nombrecli)) {
		$nombrecli = "";
	}

	if (!isset($codigo_vendedor)) {
		$codigo_vendedor = '';
	}

	if (!isset($nrorif)) {
		$nrorif = "";
	}

	$echo = $datos . "//" . $nombrecli . "//" . $codigo_vendedor . "//" . $codcliente . "//" . $nrorif;

	echo $echo;
}

if ($_GET['accion'] == "eliminar_producto") {

	$documento = "E" . $_POST['mesa'];

	$query = "
		DELETE
		FROM opermv
		WHERE 
		pidpadre = '" . $_POST['pid'] . "' AND codhijo = '" . $_POST['codigo'] . "'
	";
	$delete_opermv_hijos = $objeto_inventario->general_query($bd_conexion, $query);
	if ($delete_opermv_hijos) {

		$from = "opermv";
		$where = "pid='" . $_POST['pid'] . "' and documento='" . $documento . "'";
		$delete_opermv = $objeto_inventario->delete($bd_conexion, $from, $where);
		if ($delete_opermv) {

			$from = "comandasmv";
			$where = "idopermv='" . $_POST['pid'] . "' and documento='" . $documento . "'";
			$delete_comandasmv = $objeto_inventario->delete($bd_conexion, $from, $where);
			if ($delete_comandasmv) {

				echo "
					<script>								
						for(i in productos_array){
							var datos_producto = productos_array[i].split('|');
							var pidpadre = datos_producto[7];	
							var pid = datos_producto[13];	
							if( pid == '" . $_POST['pid'] . "' || pidpadre == '" . $_POST['pid'] . "' ){
								delete productos_array[i];
							}							
						}						
						productos_array = productos_array.filter(function(val){return val});				
						ver_productos_agregados();		
					</script>
				";
			}
		}
	}
}

if ($_GET['accion'] == "generar_pid") {

	$query = "
		SELECT 
		pid
		FROM opermv	
		ORDER BY pid DESC 
		LIMIT 1
	";
	$consulta_opermv = $objeto_inventario->general_query($bd_conexion, $query);
	$datos_opermv = mysqli_fetch_array($consulta_opermv);


	$pid_disponible = false;
	while ($pid_disponible == false) {

		$hour = str_replace(":", '', date('H:i:s'));
		$pid = rand(1, 999) * $hour;
		
		if (empty($_GET['pidpadre']) || !isset($_GET['pidpadre']) || ($_GET['pidpadre']) == 'NaN') {
			if (!empty($datos_opermv['pid']) && ($datos_opermv['pid'] != 'NaN')) {
				$pid = $datos_opermv['pid'] + 1;
			}
		} //else{
		//	$pid = $_GET['pidpadre'];
		//}

		// Truncar a máximo 12 caracteres
		$pid = substr(strval($pid), 0, 12);

		// $pid = substr(strval($pid), 0, 11);
		if ($pid != 'NaN') {
			$query = "
			SELECT 
			COUNT(*) AS total
			FROM opermv		
			WHERE pid = '" . $pid . "'
		";
			$consulta_pid = $objeto_inventario->general_query($bd_conexion, $query);
			$datos_pid = mysqli_fetch_array($consulta_pid);
			if ($datos_pid['total'] == 0) {
				$pid_disponible = true;
			}
		}
	}
	if (!empty($_GET['pidpadre'])) {
		echo $pid . '|' . $_GET['pidpadre'];
	} else {
		echo $pid;
	}
}


if ($_GET['accion'] == "calcular_pid") {
	$pid_disponible = false;
	while ($pid_disponible == false) {
		// Declaramos nuestras fechas inicial y final
		$fechaInicial = date('2022-01-01');
		$fechaActual = date("Y-m-d");

		// Las convertimos a segundos
		$fechaInicialSegundos = strtotime($fechaInicial);
		$fechaActualSegundos = strtotime($fechaActual);

		// Obtenemos la diferencia en segundos
		$segundos = ($fechaActualSegundos - $fechaInicialSegundos);

		// se calculan los microsegundos de la fecha actual
		$now = (string)microtime();
		$now =  explode(' ', $now);
		$mm = explode('.', $now[0]);
		// se toman los 4 decimales de los microsegundos
		$mm = substr($mm[1], 0, 4);
		$pid = $segundos . $mm;

		$pid = substr(strval($pid), 0, 12);

		
		if ($pid != 'NaN') {
			$query = "
		SELECT 
		COUNT(*) AS total
		FROM opermv		
		WHERE pid = '" . $pid . "'
	";
			$consulta_pid = $objeto_inventario->general_query($bd_conexion, $query);
			$datos_pid = mysqli_fetch_array($consulta_pid);
			if ($datos_pid['total'] == 0) {
				$pid_disponible = true;
			}
		}
	}
	if (!empty($_GET['pidpadre'])) {
		echo $pid . '|' . $_GET['pidpadre'];
	} else {
		echo $pid;
	}
}
