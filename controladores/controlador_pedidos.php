<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
clearstatcache();

include ("../modelos/modelo_crud.php");
include ("../funciones/funciones.php");

require_once dirname(__DIR__) . '/funciones/config_helper.php';
$datos_conexion = obtener_config_sistema();
$file = $datos_conexion;

$objeto_pedido = new crud();
$bd_conexion = "cliente";

if ($_GET['accion'] == "procesar_pedido") {

	if (isset($_POST['productos_array']) and $_POST['productos_array'] != "") {

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

		$query_datos_config2 = $objeto_pedido->general_query($bd_conexion, $query);
		$datos_config2 = mysqli_fetch_array($query_datos_config2);


		$mesa = "E" . $_POST['mesa'];

		$select = "idopermv,codigo";
		$from = "comandasmv";
		$where = "documento = '" . $mesa . "' ";
		$consulta_comandasmv = $objeto_pedido->select($bd_conexion, $select, $from, $where, '', '');

		$comandasmv_ya_registrados = array();
		while ($comandasmv = mysqli_fetch_array($consulta_comandasmv)) {
			$comandasmv_ya_registrados[] = trim($comandasmv['idopermv']);
		}

		$operti_ya_existe = false;
		$select_operti = "documento";
		$from_operti = "operti";
		$where_operti = " documento = '" . $mesa . "' ";
		$consulta_operti = $objeto_pedido->select($bd_conexion, $select_operti, $from_operti, $where_operti, '', '');
		$operti = mysqli_fetch_array($consulta_operti);
		if (!empty($operti['documento'])) {
			$operti_ya_existe = true;
		}

		$select = "pid,codhijo,se_imprimio";
		$from = "opermv";
		$where = " documento = '" . $mesa . "' ";
		$consulta = $objeto_pedido->select($bd_conexion, $select, $from, $where, '', '');

		$productos_ya_registrados = array();

		while ($producto = mysqli_fetch_array($consulta)) {
			//if( !empty($operti['documento']) ){	$operti_ya_existe = true; }

			$productos_ya_registrados[] = trim($producto['pid']); // . trim($producto['codhijo']);
		}

		$codigos_articulos = array();
		foreach ($_POST['productos_array'] as $p) {
			$datos = explode('|', $p);
			$codigo = $datos[0];
			$codigos_articulos[] = $codigo;
		}

		$query = "
			SELECT 
			*
			FROM articulo 
			WHERE codigo IN (
		";

		$x = 0;
		$total_codigos = count($codigos_articulos);
		foreach ($codigos_articulos as $cod) {
			$query .= "'" . $cod . "'";
			$x++;
			if ($x < $total_codigos) {
				$query .= ",";
			}
		}

		$query .= ")";

		$resultado_articulos = $objeto_pedido->general_query($bd_conexion, $query);

		$datos_articulos = array();
		while ($array_articulos = mysqli_fetch_array($resultado_articulos)) {
			$datos_articulos[] = $array_articulos;
		}
		$fecha_hoy = date("Y-m-d");
		$hora_actual = date("H:i", strtotime($_GET['hora']));
		$hour = date("H:i:s", strtotime($_GET['hora']));
		$fechayhora = $fecha_hoy . " " . $hour;
		$dia_semana = date('w');

		$totdescuen = 0;
		$baseimpo = "";
		$baseimpo_total = 0;
		$ali_normal = 0;
		$ali_reducida = 0;
		$ali_extendida = 0;
		$totalfinal = 0;
		$monto_neto = 0;
		$monto_impuesto = 0;
		$totbruto = 0;
		$monto_recargo = 0;
		$total_impuesto = 0;
		$totcosto = 0;

		$array_impuestos = array();

		$registro_opmvimpumv = true;
		$registro_comandasmv = true;
		$consecutivo_comandasmv_generado = false;

		foreach ($_POST['productos_array'] as $p) {

			$datos = explode('|', $p);
			if ($datos_conexion['no_usar_impuestos'] == "si" or $_POST['cliente_exento'] == 3) {
				//SI NO USA IMPUESTOS, EL PRC DE IMPUESTO ES 0 CERO
				$datos[6] = 0;
			}

			$codigo = $datos[0];
			$cantidad = $datos[12];
			$compuesto = 1;
			$codhijo = '';

			if (trim($datos[14]) != '') {
				$codigo = $datos[14];
				$codhijo = $datos[0];
				$compuesto = empty($codhijo) ? 2 : 0; //2;
			}

			//dias_semana = 7 caracteres, el 1ro es Domingo y el Ultimo es Sabado. (1=Si/0=No).
			/*
																 date('w', $fecha_hoy);
																 case 0: echo "Domingo"; break;
																 case 1: echo "Lunes"; break;
																 case 2: echo "Martes"; break;
																 case 3: echo "Miercoles"; break;
																 case 4: echo "Jueves"; break;
																 case 5: echo "Viernes"; break;
																 case 6: echo "Sabado"; break; 
																 */

			$query_oferta = "
						SELECT 
						ofertasti.idoferta, 
						ofertasti.fechadesde, 
						ofertasti.fechahasta, 
						ofertasti.dias_semana, 
						ofertasti.horadesde, 
						ofertasti.horahasta, 
						ofertasmv.cantidadminima,
						ofertasmv.canitdadmaxima AS cantidadmaxima,
						ofertasmv.precio_oferta_neto, 
						ofertasmv.grp_precio_oferta 
						FROM ofertasti 
						INNER JOIN ofertasmv ON ofertasti.idoferta = ofertasmv.idoferta 
						WHERE 
						ofertasmv.codigoart = '" . $codigo . "' AND 
						ofertasmv.tipoprecio = 1 AND 
						ofertasti.fechadesde <= '" . $fecha_hoy . "' AND 
						ofertasti.fechahasta >= '" . $fecha_hoy . "' AND 
						TIME(ofertasti.horadesde) <= '" . $hour . "' AND 
						TIME(ofertasti.horahasta) >= '" . $hour . "'
						LIMIT 1
					";

			$resultado_oferta = $objeto_pedido->general_query($bd_conexion, $query_oferta);
			$datos_oferta = mysqli_fetch_array($resultado_oferta);

			if (!empty($datos_oferta)) {
				if ($datos_oferta['dias_semana'][$dia_semana] == 1) {
					if (
						(
							$cantidad >= $datos_oferta['cantidadminima'] and
							$cantidad <= $datos_oferta['cantidadmaxima']
						) or
						(
							$datos_oferta['cantidadminima'] == 0 and
							$datos_oferta['cantidadmaxima'] == 0
						)
					) {
						$datos[4] = $datos_oferta['precio_oferta_neto'];
						if ($datos_oferta['grp_precio_oferta'] <= 0) {
							$datos[10] = '';
						} else {
							$datos[10] = $datos_oferta['grp_precio_oferta'];
						}
					}
				}
			}

			$prc_recargo = $datos_conexion['recargo'] / 100;
			$pid = trim($datos[13]);

			$pidhijo = trim($datos[0]); // GDM - el codhijo para validar cuando pid viene de fox, ya que viene con el mismo valor
			$cantidad_agrupada = trim($datos[11]);
			$compuesto = $cantidad_agrupada > 0 ? 2 : $compuesto;

			//	$precio_unitario_agrupado = trim($datos[10]);
			$precio_unitario_agrupado = empty($codhijo) ? trim($datos[10]) : 0;
			$nombre_unidad_agrupado = $datos[9];
			//
			if (empty($datos[7])) {
				$pidpadre = $datos[13]; //$datos[7];

			} else {
				$pidpadre = empty($codhijo) ? $datos[13] : ((int) $datos[7]); //$datos[7];
			}

			$se_guardo = $datos[8];
			//$pid = empty($codhijo) ? $datos[13]: $pidpadre;
			if (strpos($datos[6], '-') > -1) {
				$imp = explode('-', $datos[6]);
				$codigo_impuesto = $imp[0];
				$impuesto_prc = $imp[1];
				$impuesto = $imp[1] / 100;
			} else {
				$impuesto_prc = $datos[6];
				$impuesto = $datos[6] / 100;
			}

			$dsctounit = 0;
			$prc_descuento = 0;
			if (isset($datos[15])) {
				$prc_descuento = $datos[15];
				$dsctounit = 0;
			}

			$codgrupo = $datos[1];
			$codsubgrupo = $datos[2];
			$nombre = $datos[3];
			//$preciounit = $datos[4];
			$preciounit = empty($codhijo) ? round($datos[4], 2) : 0;
			//$precio_bruto = $datos[4];
			$precio_bruto = empty($codhijo) ? round($datos[4], 2) : 0;


			if ($prc_descuento > 0) {
				$dsctounit = $preciounit * ($prc_descuento / 100);
				$precioneto = ($preciounit - $dsctounit) * $cantidad;
				$totdescuen += $dsctounit;
			} else {
				$precioneto = $preciounit * $cantidad;
			}

			$empaque = empty($codhijo) ? 2 : 0;

			if ($nombre_unidad_agrupado != "") {
				if ($se_guardo == 0) {
					$nombre = $nombre . " (" . $nombre_unidad_agrupado . ")";
				}
			}

			$se_guardo = 0;

			if ($precio_unitario_agrupado != "" and $cantidad_agrupada > 0) {
				$preciounit = $precio_unitario_agrupado;
				$precioneto = $precio_unitario_agrupado * $cantidad;
				$empaque = 1;
			} else {
				$cantidad_agrupada = 0;
			}

			$recargo_unit = $preciounit * $prc_recargo;
			// Si el artículo es exento (exislr == 1), los impuestos deben ser 0
			// Nota: exislr se obtiene más adelante, pero necesitamos verificar aquí también
			// Por ahora calculamos normalmente, luego se ajustará si es exento
			$impuesto_total = $precioneto * $impuesto;
			$preciofin = ($precioneto * $impuesto) + $precioneto;

			if (isset($datos[16]) and $datos[16] != 'undefined') {
				$notas = $datos[16];
			} else {
				$notas = "";
			}


			if (isset($datos[17]) and $datos[17] != 'undefined') {
				$estacion = $datos[17];
			} else {
				$estacion = "PSK";
			}

			$se_imprimio = 0;

			$totbruto += $precio_bruto * $cantidad;
			// Los impuestos se calcularán después de obtener exislr
			// Por ahora los calculamos normalmente, luego se ajustarán si es exento
			$monto_impuesto = $precioneto * $impuesto;
			$total_impuesto += $monto_impuesto;
			$monto_neto += $precioneto;
			//$totalfinal += $preciofin + ($precioneto * $prc_recargo);		
			$totalfinal += $preciofin + ($preciofin * $prc_recargo);
			//$totalfinal += round($preciofin,2) +( round($preciofin,2) * round($prc_recargo,2));	

			$costounit = 0;
			$unidad = '';
			$usaexist = 1;
			$origen_articulo = 1; // Valor por defecto
			$impuesto1_articulo = 0; // Impuesto1 del artículo
			$usatasapercibido = 0; // Por defecto no usa tasa percibido
			$exislr = 0; // Por defecto no es exento

			foreach ($datos_articulos as $art) {
				if (empty($codhijo)) {
					if ($art['codigo'] == $codigo) {
						$costounit = $art['costo'];
						$unidad = $art['unidad'];
						$usaexist = empty($codhijo) ? $art['usaexist'] : 1; //$art['usaexist'];
						$origen_articulo = isset($art['origen']) ? $art['origen'] : 1;
						$impuesto1_articulo = isset($art['impuesto1']) ? floatval($art['impuesto1']) : 0;
						$usatasapercibido = isset($art['usatasapercibido']) ? intval($art['usatasapercibido']) : 0;
						$exislr = isset($art['exislr']) ? intval($art['exislr']) : 0;

						break;
					}
				} else {
					if ($art['codigo'] == $codhijo) {
						$costounit = $art['costo'];
						$unidad = $art['unidad'];
						$usaexist = empty($codhijo) ? $art['usaexist'] : 1; //$art['usaexist'];
						$origen_articulo = isset($art['origen']) ? $art['origen'] : 1;
						$impuesto1_articulo = isset($art['impuesto1']) ? floatval($art['impuesto1']) : 0;
						$usatasapercibido = isset($art['usatasapercibido']) ? intval($art['usatasapercibido']) : 0;
						$exislr = isset($art['exislr']) ? intval($art['exislr']) : 0;

						break;
					}
				}
			}

			// Inicializar baseimpo según exislr y usatasapercibido del artículo
			$baseimpo = '';
			$baseimponible = 0;
			$baseimpo1_monto = 0; // Monto para baseimpo1 cuando impuesto1 > 0
			
			// Si el artículo es exento (exislr == 1), todos los baseimpo e impuestos deben ser 0
			if ($exislr == 1) {
				// Artículo exento: no se asignan baseimpo, todos quedan en 0
				$baseimpo = '';
				$baseimponible = 0;
				$baseimpo1_monto = 0;
				// Los impuestos también deben ser 0 para artículos exentos
				// Ajustar los valores de impuestos
				$impuesto_total = 0;
				$monto_impuesto_temp = $precioneto * $impuesto; // Impuesto que ya se sumó
				$monto_impuesto = 0;
				$preciofin = $precioneto; // Sin impuestos
				// Ajustar totales que ya se calcularon antes de obtener exislr
				$total_impuesto -= $monto_impuesto_temp; // Restar el impuesto que ya se sumó
				// Ajustar totalfinal: restar el impuesto y el recargo sobre el impuesto que ya se sumaron
				$impuesto_con_recargo = $monto_impuesto_temp + ($monto_impuesto_temp * $prc_recargo);
				$totalfinal -= $impuesto_con_recargo;
				// Sumar el precio sin impuestos pero con recargo sobre el precio neto
				$totalfinal += $precioneto + ($precioneto * $prc_recargo);
			} else {
				// Si el artículo tiene impuesto1 > 0, calcular baseimpo1
				if ($impuesto1_articulo > 0) {
					$baseimpo1_monto = $precioneto * ($impuesto1_articulo / 100);
				}
				
				// Determinar si es percibido
				// usatasapercibido = 1 → percibido (usa baseimpo5)
				if ($usatasapercibido == 1) {
					// Artículo percibido: baseimpo = baseimpo5, los demás baseimpo deben estar en 0
					$baseimpo = 'baseimpo5';
					$baseimponible = $precioneto;
				} else {
					// No es exento ni percibido: usar baseimpo1
					$baseimpo = 'baseimpo1';
					$baseimponible = $precioneto;
				}
			}

			$aux1 = $usaexist == 1 ? $cantidad : 0;
			if (!empty($nombre_unidad_agrupado)) {
				$unidad = $nombre_unidad_agrupado;
			}

			$totcosto += $costounit * $cantidad;

			// Si es percibido, asegurar que baseimpo1, baseimpo2, etc. estén en 0
			// Si es exento, todos los baseimpo deben estar en 0
			$columnas_baseimpo = "";
			if ($exislr == 1) {
				// Exento: todos los baseimpo deben estar en 0
				$columnas_baseimpo = ",
						baseimpo1,
						baseimpo2,
						baseimpo3,
						baseimpo4,
						baseimpo5";
			} else if ($usatasapercibido == 1) {
				// Percibido: solo baseimpo5, los demás deben estar en 0
				$columnas_baseimpo = ",
						baseimpo5,
						baseimpo1,
						baseimpo2,
						baseimpo3,
						baseimpo4";
			} else {
				// No percibido ni exento: usar baseimpo1
				$columnas_baseimpo = ",
						baseimpo1";
			}
			
			$columnas = "
						id_empresa,
						agencia,
						tipodoc,
						documento,
						grupo,
						subgrupo,
						origen,
						codigo,
						codhijo,
						pid,
						pidpadre,
						nombre,
						preciounit,
						dsctounit,
						dsctoprc,
						preciofin,
						preciooriginal,
						cantidad,
						montoneto,
						montototal,
						almacen,
						proveedor,
						fechadoc,
						impuesto1,
						timpueprc,
						vendedor,
						emisor,
						usaserial,
						tipoprecio,
						unidad,
						agrupado,
						cntagrupada,
						notas,
						compuesto,
						usaexist,
						aux1, 
						estacion" . $columnas_baseimpo . ",
						se_imprimio,
						se_guardo,
						enviado_kmonitor,
						fechadocant,
						fechayhora,
						costounit
					";
			$pid_art = !empty($codhijo) ? $pidpadre : $pid;
			// Si el artículo es exento, los impuestos deben ser 0
			$impuesto_prc1 = ($exislr == 1) ? 0 : $preciounit*($impuesto_prc/100);
			
			// Asegurarse de que el origen esté definido
			if (!isset($origen_articulo)) {
				$origen_articulo = 1;
			}
			
			// Valores para baseimpo según si es exento, percibido o normal
			$values_baseimpo = "";
			if ($exislr == 1) {
				// Exento: todos los baseimpo en 0
				$values_baseimpo = ",
						'0.00',
						'0.00',
						'0.00',
						'0.00',
						'0.00'";
			} else if ($usatasapercibido == 1) {
				// Percibido: baseimpo5 con el valor, los demás en 0
				$values_baseimpo = ",
						'" . number_format($baseimponible, 2, '.', '') . "',
						'0.00',
						'0.00',
						'0.00',
						'0.00'";
			} else {
				// No percibido ni exento: baseimpo1 con el valor de la base imponible
				$values_baseimpo = ",
						'" . number_format($baseimponible, 2, '.', '') . "'";
			}
			
			$values = "
						'" . $datos_conexion['cod_empresa'] . "',
						'" . $datos_conexion['cod_agencia'] . "',
						'" . $datos_conexion['tipo_doc'] . "',
						'$mesa',
						'$codgrupo',
						'$codsubgrupo',
						'" . $origen_articulo . "',
						'$codigo',
						'$codhijo',
						'$pid_art',
						'$pid',
						'$nombre',
						'" . number_format($preciounit, 2, '.', '') . "',						
						'" . number_format($dsctounit, 2, '.', '') . "',
						'" . number_format($prc_descuento, 2, '.', '') . "',
						'" . number_format($preciounit, 2, '.', '') . "',
						'" . $preciounit . "',
						'" . number_format($cantidad, 5, '.', '') . "',
						'" . number_format($precioneto, 2, '.', '') . "',
						'" . number_format($precioneto, 2, '.', '') . "',
						'" . $datos_conexion['cod_deposito'] . "',
						'" . $_POST['cod_cliente'] . "',
						'$fecha_hoy',
						'" . number_format($impuesto_prc1, 2, '.', '') . "',
						'" . number_format($impuesto_prc, 2, '.', '') . "',
						'" . $_POST['vendedor'] . "',
						'" . $_POST['vendedor'] . "',
						'2',
						'1',
						'$unidad',
						'$empaque',
						'$cantidad_agrupada',
						'$notas',
						'$compuesto',
						" . $usaexist . ",
						'" . number_format($aux1, 5, '.', '') . "',
						'PSK'" . $values_baseimpo . ",
						" . $se_imprimio . ",
						'$se_guardo',
						'2',
						'" . $fecha_hoy . "',
						'" . $fechayhora . "',
						 '" . $costounit . "'
					";
			//print_r($columnas);
			// GDM - Al código de la estación se esta enviando directamente PSK, anteriormente se enviaba: ".substr(trim($_POST['vendedor']),-3)."
			// Solo procesar impuestos si el artículo NO es exento
			if (strpos($datos[6], '-') > -1 && $exislr != 1) {

				if (empty($array_impuestos[$codigo_impuesto]['base_imponible'])) {
					$array_impuestos[$codigo_impuesto]['base_imponible'] = 0;
					$array_impuestos[$codigo_impuesto]['totimpuest'] = 0;
				}

				$array_impuestos[$codigo_impuesto]['base_imponible'] += $precioneto;
				$array_impuestos[$codigo_impuesto]['prc'] = $imp[1];

				$totimpuest = 0;
				if ($imp[1] > 0) {
					$totimpuest = ($imp[1] / 100);
					$totimpuest = $precioneto * $totimpuest;
				}

				$array_impuestos[$codigo_impuesto]['totimpuest'] += $totimpuest;
			}
			$pid_codhijo = $pid . $pidhijo;

			if (in_array($pid, $productos_ya_registrados)) {
				$query_update_baseimpo = "";
				if ($exislr == 1) {
					// Exento: todos los baseimpo en 0
					$query_update_baseimpo = ",
						baseimpo1 = '0.00',
						baseimpo2 = '0.00',
						baseimpo3 = '0.00',
						baseimpo4 = '0.00',
						baseimpo5 = '0.00'";
				} else if ($usatasapercibido == 1) {
					// Percibido: actualizar baseimpo5 con el valor, los demás en 0
					$query_update_baseimpo = ",
						baseimpo5 = '" . number_format($baseimponible, 2, '.', '') . "',
						baseimpo1 = '0.00',
						baseimpo2 = '0.00',
						baseimpo3 = '0.00',
						baseimpo4 = '0.00'";
				} else {
					// No percibido ni exento: actualizar baseimpo1 con el valor de la base imponible
					$query_update_baseimpo = ",
						baseimpo1 = '" . number_format($baseimponible, 2, '.', '') . "'";
				}
				
				$query = " 
							UPDATE opermv SET
							preciounit = '" . number_format($preciounit, 2, '.', '') . "',
							dsctounit = '" . number_format($dsctounit, 2, '.', '') . "',
							dsctoprc = '" . number_format($prc_descuento, 2, '.', '') . "',
							preciofin = '" . number_format($preciounit, 2, '.', '') . "',
							preciooriginal = '" . $preciounit . "',
							costounit = '" . $costounit . "',
							cantidad = '" . number_format($cantidad, 5, '.', '') . "',
							cntagrupada = '" . number_format($cantidad_agrupada, 5, '.', '') . "',
							montoneto = '" . number_format($precioneto, 2, '.', '') . "',
							montototal = '" . number_format($precioneto, 2, '.', '') . "',
							aux1 = '" . number_format($aux1, 5, '.', '') . "',
							notas = '$notas'" . $query_update_baseimpo . "
							WHERE pidpadre= '$pidpadre' AND documento = '$mesa'
						";

				$registro_opermv = $objeto_pedido->general_query($bd_conexion, $query);
				$productos_ya_registrados[] = $pid; //.$pidhijo;
			} else {

				$registro_opermv = $objeto_pedido->insert($bd_conexion, 'opermv', $columnas, $values);
				$productos_ya_registrados[] = $pid; //.$pidhijo;

				if (strpos($datos[6], '-') > -1) {

					//MULTIPLES IMPUESTOS
					$columnas = "
								id_empresa,
								agencia,
								tipodoc,
								documento,
								codigo,
								pid,
								impu_codigo,
								impu_prc,
								impu_monto,
								impu_base,
								totimpuest
							";

					$values = "
								'" . $datos_conexion['cod_empresa'] . "',
								'" . $datos_conexion['cod_agencia'] . "',
								'" . $datos_conexion['tipo_doc'] . "',
								'" . $mesa . "',
								'" . $codigo . "',
								'" . $pid . "',
								'" . $imp[0] . "',
								'" . $imp[1] . "',
								0,
								'" . number_format($precioneto, 2, '.', '') . "',
								'" . number_format($totimpuest, 2, '.', '') . "'
							";
							

					$registro_opmvimpumv = $objeto_pedido->insert($bd_conexion, 'opmvimpumv', $columnas, $values);
				}
			}

			//LOS COMPONENTES NO DEBEN REGISTRARSE EN COMANDASMV
			if ($_POST['imprimir'] == 2 and empty($codhijo)) {
				$pid_codhijo = $pid . $codigo;
				if (!in_array($pid, $comandasmv_ya_registrados)) {

					if (!$consecutivo_comandasmv_generado) {
						$query_agencia = " SELECT despacho FROM adminmatriz.agencias WHERE agencia = '" . $datos_conexion['cod_agencia'] . "' AND id_empresa = '" . $datos_conexion['cod_empresa'] . "' LIMIT 1";
						$resultado_agencia = $objeto_pedido->general_query($bd_conexion, $query_agencia);
						$datos_agencia = mysqli_fetch_array($resultado_agencia);

						$consecutivo_comandasmv = 0;
						if (!empty($datos_agencia['despacho'])) {
							$consecutivo_comandasmv = (int) $datos_agencia['despacho'];
						}
						$consecutivo_comandasmv++;
						$consecutivo_comandasmv = str_pad($consecutivo_comandasmv, 8, "0", STR_PAD_LEFT);
						$consecutivo_comandasmv_generado = true;
					}

					$query = "
								INSERT INTO comandasmv
								(
									id_empresa,
									agencia,
									estacion,
									pid,
									documento,
									tipodoc,
									idopermv,
									fecha,
									codigo,
									nombre,
									cantidad,
									notas,
									prioridad,
									comenzalnumero,
									estadocomanda,
									fechaestado,
									impresion
								)
								VALUES
								(
									'" . $datos_conexion['cod_empresa'] . "',
									'" . $datos_conexion['cod_agencia'] . "',
									'PSK',
									'" . $consecutivo_comandasmv . "',
									'" . $mesa . "',
									'" . $datos_conexion['tipo_doc'] . "',
									'" . $pid . "',
									'" . $fechayhora . "',
									'" . $codigo . "',
									'" . $nombre . "',
									'" . $cantidad . "',
									'" . $notas . "',
									0,
									0,
									1,
									'" . $fecha_hoy . "',
									1
								);
							";

					$from = "adminmatriz.agencias";
					$values = " despacho = LPAD(despacho+1, 8, '0')";
					$where = " agencia = '" . $datos_conexion['cod_agencia'] . "' AND id_empresa = '" . $datos_conexion['cod_empresa'] . "'";
					$resultado_agencia = $objeto_pedido->update($bd_conexion, $from, $values, $where);


					$queryopermv = " 
					UPDATE opermv SET
					se_guardo = '1'
					WHERE concat(pid) = '$pid' AND documento = '$mesa'
				;";

					

					// GDM - Al código de la estación se esta enviando directamente PSK, anteriormente se enviaba: ".substr(trim($_POST['vendedor']),-3)."

				} else {
					$query = "
								UPDATE comandasmv SET
								notas = '" . $notas . "'
								WHERE idopermv = '" . $pid . "' AND documento = '" . $mesa . "'
							;";
				}

				$registro_comandasmv = $objeto_pedido->general_query($bd_conexion, $query);
			}
		}

		if ($registro_opermv) {
			if ($registro_comandasmv) {
				if ($registro_opmvimpumv) {

					$consulta_hora = $objeto_pedido->select($bd_conexion, 'horadocum', 'operti', "documento='" . $mesa . "'", '', '');
					$horadocum = mysqli_fetch_array($consulta_hora);

					if (!empty($horadocum['horadocum'])) {
						$hora_actual = $horadocum['horadocum'];
					}
					//$recargo = $datos_conexion['recargo'];
					//$totalfinal = $monto_neto + $total_impuesto;
					
					// Consultar dirección del cliente desde cliempre
					$direccion_cliente = 'DIRECCION'; // Valor por defecto
					$query_direccion = "SELECT direccion FROM cliempre WHERE codigo = '" . $_POST['cod_cliente'] . "' LIMIT 1";
					$resultado_direccion = $objeto_pedido->general_query($bd_conexion, $query_direccion);
					$datos_direccion = mysqli_fetch_array($resultado_direccion);
					if (!empty($datos_direccion['direccion'])) {
						$direccion_cliente = addslashes($datos_direccion['direccion']);
					}
					
					// Consultar RIF del cliente desde cliempre si no viene en POST
					$rif_cliente = '';
					if (!empty($_POST['nrorif'])) {
						$rif_cliente = addslashes(trim($_POST['nrorif']));
					} else {
						// Consultar nrorif y cedula desde cliempre
						$query_rif = "SELECT nrorif, cedula FROM cliempre WHERE codigo = '" . $_POST['cod_cliente'] . "' LIMIT 1";
						$resultado_rif = $objeto_pedido->general_query($bd_conexion, $query_rif);
						$datos_rif = mysqli_fetch_array($resultado_rif);
						if (!empty($datos_rif['nrorif']) && trim($datos_rif['nrorif']) != '') {
							$rif_cliente = addslashes(trim($datos_rif['nrorif']));
						} elseif (!empty($datos_rif['cedula']) && trim($datos_rif['cedula']) != '') {
							$rif_cliente = addslashes(trim($datos_rif['cedula']));
						}
					}
					
					if (!$operti_ya_existe) {
						$query = "
								INSERT INTO operti
								(
									id_empresa,
									agencia,
									tipodoc,
									documento,
									codcliente,
									nombrecli,
									contacto,
									rif,
									nit,
									direccion,
									telefonos,
									tipoprecio,
									emision,
									recepcion,
									vence,
									fechacrea,
									totbruto,
									totneto,
									totalfinal,
									totimpuest,
									impuesto1,
									notas,
									vendedor,
									factorcamb,
									uemisor,
									estacion,
									almacen,
									sector,
									formafis,
									al_libro,
									dbcr,
									horadocum,
									comanda_movil,
									comanda_kmonitor,
									fechayhora,
									ult_interes,
									ultimopag,
									fechanul,
									emisionant,
									fchyhrant,
									ordentrab,
									totcosto,
									usertipofac
								)
								VALUES
								(
									'" . $datos_conexion['cod_empresa'] . "',
									'" . $datos_conexion['cod_agencia'] . "',
									'" . $datos_conexion['tipo_doc'] . "',
									'" . $mesa . "',
									'" . $_POST['cod_cliente'] . "',
									'" . strtoupper($_POST['nombre_cliente']) . "',
									'" . strtoupper($_POST['nombre_cliente']) . "',
									'" . strtoupper($rif_cliente) . "',
									'00',
									'" . $direccion_cliente . "',
									'000000',
									'1',
									'" . $fecha_hoy . "',
									'" . $fecha_hoy . "',
									'" . $fecha_hoy . "',
									'" . $fecha_hoy . "',
									'" . number_format($totbruto, 2, '.', '') . "',
									'" . number_format($monto_neto, 2, '.', '') . "',
									'" . number_format($totalfinal, 2, '.', '') . "',
									'" . number_format($total_impuesto, 2, '.', '') . "',
									'" . number_format($total_impuesto, 2, '.', '') . "',
									'',
									'" . $_POST['vendedor'] . "',
									'1',
									'" . $_POST['vendedor'] . "',
									'PSK',
									'" . $datos_conexion['cod_deposito'] . "',
									'" . $datos_conexion['cod_zona'] . "',
									'1',
									'1',
									'1',
									'" . $hora_actual . "',
									'" . $_POST['imprimir'] . "',
									'" . $_POST['imprimir'] . "',
									'" . $fechayhora . "',
									'" . $fecha_hoy . "',
									'" . $fecha_hoy . "',
									'" . $fecha_hoy . "',
									'" . $fecha_hoy . "',
									'" . $fechayhora . "',
									'" . $mesa . "',
									'" . number_format($totcosto, 2, '.', '') . "',
									'2'
								)
							";
						// GDM - Al código de la estación se esta enviando directamente PSK, anteriormente se enviaba: ".substr(trim($_POST['vendedor']),-3)."

					} else {
						$query = "
								UPDATE operti
								SET 						
								codcliente = '" . $_POST['cod_cliente'] . "',
								nombrecli = '" . strtoupper($_POST['nombre_cliente']) . "',
								rif = '" . strtoupper($rif_cliente) . "',
								contacto = '" . strtoupper($_POST['nombre_cliente']) . "',
								emision = '" . $fecha_hoy . "',
								recepcion = '" . $fecha_hoy . "',
								vence = '" . $fecha_hoy . "',
								fechacrea = '" . $fecha_hoy . "',
								totbruto = '" . number_format($totbruto, 2, '.', '') . "',
								totneto = '" . number_format($monto_neto, 2, '.', '') . "',
								totalfinal = '" . number_format($totalfinal, 2, '.', '') . "',
								totimpuest = '" . number_format($total_impuesto, 2, '.', '') . "',
								impuesto1 = 0,
								vendedor = '" . $_POST['vendedor'] . "',
								horadocum = '" . $hora_actual . "',
								comanda_movil = '" . $_POST['imprimir'] . "',
								comanda_kmonitor = '" . $_POST['imprimir'] . "',
								fechayhora = '" . $fechayhora . "',
								totcosto = '" . number_format($totcosto, 2, '.', '') . "',
								usertipofac = '2'
								WHERE 
								documento = '" . $mesa . "' AND 
								tipodoc = '" . $datos_conexion['tipo_doc'] . "'
							";
					}
					if (!empty($queryopermv)){
					$registro_opermv = $objeto_pedido->general_query($bd_conexion, $queryopermv);
					}
					$registro_operti = $objeto_pedido->general_query($bd_conexion, $query);
					if ($registro_operti) {

						$registro_optiimpumv = true;
						if (!empty($array_impuestos)) {
							$query = "
									INSERT INTO optiimpumv
									(
										id_empresa,
										agencia,
										tipodoc,
										documento,
										impu_codigo,
										impu_prc,
										impu_monto,
										impu_base,
										totimpuest
									)
									VALUES
								";

							$x_i = 0;
							$total_array_imp = count($array_impuestos);
							foreach ($array_impuestos as $codigo_imp => $array_imp) {
								$query .= "
										(
											'" . $datos_conexion['cod_empresa'] . "',
											'" . $datos_conexion['cod_agencia'] . "',
											'" . $datos_conexion['tipo_doc'] . "',
											'" . $mesa . "',
											'" . $codigo_imp . "',
											'" . $array_imp['prc'] . "',
											0,
											'" . number_format($array_imp['base_imponible'], 2, '.', '') . "',
											'" . number_format($array_imp['totimpuest'], 2, '.', '') . "'
										)
									";
								$x_i++;
								if ($x_i < $total_array_imp) {
									$query .= ",";
								}
							}

							$registro_optiimpumv = $objeto_pedido->general_query($bd_conexion, $query);
						}

						if ($registro_optiimpumv) {
							echo "
									<i class='fa fa-check'></i><br>Pedido procesado
									<script>
										limpiar_productos_agregados();
										$('#input_cliente').val('');
										ConsultarCliente(codcliente_grabado);
										MesaAutomatica();
									</script>
								";
						} else {
							echo "<i class='fa fa-times'></i><br>Error al registrar multiples impuestos TI<br>
								<b>Detalle:</b> " . $GLOBALS['error_mysql'];
						}
					} else {
						echo "<i class='fa fa-times'></i><br>Error al procesar pedido<br>
							<b>Detalle:</b> " . $GLOBALS['error_mysql'];
					}
				} else {
					echo "<i class='fa fa-times'></i><br>Error al registrar multiples impuestos MV<br>
							<b>Detalle:</b> " . $GLOBALS['error_mysql'];
				}
			} else {
				echo "<i class='fa fa-times'></i><br>Error al registrar Comandas MV<br>
						<b>Detalle:</b> " . $GLOBALS['error_mysql'];
			}
		} else {
			echo "<i class='fa fa-times'></i><br>Error al procesar productos<br>
				<b>Detalle:</b> " . $GLOBALS['error_mysql'];
		}
	} else {
		echo "<i class='fa fa-times'></i><br>Indique los productos a procesar";
	}
}

if ($_GET['accion'] == "ver_pedidos") {

	$query = "
		SELECT 
		operti.documento,
		operti.totalfinal,
		operti.codcliente,
		operti.nombrecli,
		operti.rif as nrorif,
		listvend.nombre as nombre_vendedor
		FROM operti
		INNER JOIN listvend ON listvend.codigo = operti.vendedor 
		WHERE 
		operti.tipodoc='" . $datos_conexion['tipo_doc'] . "' 
	";

	if (!empty($_POST['vendedor'])) {
		$query .= " AND operti.vendedor = '" . $_POST['vendedor'] . "' ";
	}

	$consulta = $objeto_pedido->general_query($bd_conexion, $query);

	// Consultar estados de pago en satélite local (mijar26273_sistema:3307)
	$pagos_mesas = [];
	$mysqli_sist = @new mysqli($datos_conexion['host'] ?? '127.0.0.1', $datos_conexion['user'] ?? 'root', $datos_conexion['pw'] ?? 'root', $datos_conexion['bd_sistema'] ?? 'mijar26273_sistema', (int)($datos_conexion['port'] ?? 3307));
	if ($mysqli_sist && !$mysqli_sist->connect_errno) {
		$res_p = $mysqli_sist->query("SELECT documento, token_pin, estado, metodo, banco_origen, referencia, monto, fecha_reporte FROM z_status_pago");
		if ($res_p) {
			while ($r_p = $res_p->fetch_assoc()) {
				$pagos_mesas[$r_p['documento']] = $r_p;
			}
		}
		$mysqli_sist->close();
	}

	echo "
		<table class='table table-striped' style='font-size:12px;'>
			<thead>
				<tr>
					<th style='width:50px;text-align:center;'>Editar</th>
					<th>Mesa</th>
					<th>Cliente</th>
					<th class='text-right'>Total</th>
				</tr>
			</thead>
			<tbody>	
	";

	$recargo = $datos_conexion['recargo'] / 100;

	// Registro de mesas reales (idconsumos y mapamesas de Premium + config) para separar pedidos en caja
	require_once dirname(__DIR__) . '/funciones/mesas_helper.php';
	$coditems_mesas = [];
	// 1. Cuentas oficiales de Premium Soft
	$consulta_idcons = $objeto_pedido->general_query($bd_conexion, "SELECT id_consumo, descripcion, nombre_corto FROM idconsumos WHERE id_consumo <> ''");
	if ($consulta_idcons) {
		while ($ri = mysqli_fetch_array($consulta_idcons)) {
			if (!empty($ri['id_consumo'])) $coditems_mesas[] = $ri['id_consumo'];
			if (!empty($ri['descripcion'])) $coditems_mesas[] = $ri['descripcion'];
			if (!empty($ri['nombre_corto'])) $coditems_mesas[] = $ri['nombre_corto'];
		}
	}
	// 2. Mapa 2D de mesas si existiera
	$consulta_mapa = $objeto_pedido->general_query($bd_conexion, "SELECT DISTINCT coditem FROM mapamesas WHERE coditem <> ''");
	if ($consulta_mapa) {
		while ($rm = mysqli_fetch_array($consulta_mapa)) {
			if (!empty($rm['coditem'])) $coditems_mesas[] = $rm['coditem'];
		}
	}
	$registro_mesas = construir_registro_mesas($coditems_mesas, $datos_conexion);
	$filas_mesas = '';
	$filas_caja = '';

	while ($pedido = mysqli_fetch_array($consulta)) {
		ob_start();
		$es_caja = es_pedido_caja($pedido['documento'], $registro_mesas);
		$pedido['totalfinal'] = $pedido['totalfinal'] + ($pedido['totalfinal'] * $recargo);

		$doc_original = $pedido['documento'];
		$doc_clean = substr($pedido['documento'], 1, strlen($pedido['documento']));
		$doc_full = "E" . $doc_clean;
		$codigo_vendedor = "";

		// Verificar información de pago
		$pago = $pagos_mesas[$doc_full] ?? ($pagos_mesas[$doc_original] ?? null);
		$estado_pago = $pago['estado'] ?? '0';

		$btn_class = "btn-warning"; // Botón de editar siempre amarillo
		$badge_pago = "";
		$fila_style = "";

		if ($estado_pago == '2') {
			// AZUL - Pago Móvil / Transferencia reportado por cliente (Por confirmar)
			$btn_class = "btn-info";
			$fila_style = "background-color: #ebf5fb;";
			$badge_pago = "<br><span class='label label-info' style='background:#0880C5;font-size:10px;display:inline-block;margin-top:3px;'><i class='fa fa-clock-o'></i> PAGO POR CONFIRMAR</span>";
		} elseif ($estado_pago == '1') {
			// VERDE - Pago Confirmado
			$btn_class = "btn-success";
			$fila_style = "background-color: #eafaf1;";
			$badge_pago = "<br><span class='label label-success' style='background:#5CB85C;font-size:10px;display:inline-block;margin-top:3px;'><i class='fa fa-check'></i> PAGO CONFIRMADO</span>";
		} elseif ($estado_pago == '4') {
			// ROJO - Pago Rechazado
			$btn_class = "btn-danger";
			$fila_style = "background-color: #fdedec;";
			$badge_pago = "<br><span class='label label-danger' style='background:#D9534F;font-size:10px;display:inline-block;margin-top:3px;'><i class='fa fa-times'></i> PAGO RECHAZADO</span>";
		}

		// Pedido en caja (comida para llevar): fondo verde suave y borde verde, botón editar amarillo
		if ($es_caja && $estado_pago == '0') {
			$fila_style = "background-color: #f0fdf4; border-left: 4px solid #5CB85C;";
		}

		echo "		
			<tr style='{$fila_style}'>
				<td style='text-align:center;'>
					<div class='btn {$btn_class}' style='width:40px;padding:5px;font-size:13px;' onclick='seleccionar_mesa(" . json_encode($doc_clean) . "," . json_encode(trim($pedido['codcliente'])) . "," . json_encode($pedido['nombrecli']) . ",".'"'.$codigo_vendedor.'"'."," . json_encode($pedido['nrorif']) . ")' title='Editar pedido'>
						<i class='fa fa-edit'></i>
					</div>
				</td>				
				<td>
					<strong>" . ($es_caja
						? "<span class='label label-success' style='background:#5CB85C;font-size:10px;padding:2px 6px;'><i class='fa fa-shopping-bag'></i> CAJA</span><br><small class='text-muted' style='font-size:11px;'>Cuenta: " . $doc_clean . "</small>"
						: $doc_clean) . "</strong>";

		if ($pago && in_array($estado_pago, ['1', '2', '4'])) {
			echo "<br><button type='button' class='btn btn-xs btn-primary' style='padding:1px 5px;font-size:10px;margin-top:3px;' onclick='toggle_detalle_pago(\"" . $doc_clean . "\")'><i class='fa fa-eye'></i> Ver Pago</button>";
		}

		echo "
				</td>
				<td>" . strtoupper($pedido['nombrecli']) . " {$badge_pago}</td>
				<td class='text-right'><strong>" . number_format($pedido['totalfinal'], 2, ',', '.') . "</strong></td>
			</tr>";

		// Fila desplegable con detalle del pago y botones Confirmar / Rechazar
		if ($pago && in_array($estado_pago, ['1', '2', '4'])) {
			$borde_color = ($estado_pago == '1') ? '#5CB85C' : (($estado_pago == '4') ? '#D9534F' : '#0880C5');
			$monto_rep = isset($pago['monto']) && (float)$pago['monto'] > 0 ? number_format($pago['monto'], 2, ',', '.') : number_format($pedido['totalfinal'], 2, ',', '.');
			$fec_rep = !empty($pago['fecha_reporte']) ? $pago['fecha_reporte'] : date('Y-m-d H:i');

			echo "
			<tr id='detalle_pago_{$doc_clean}' style='display:none; {$fila_style}'>
				<td colspan='4' style='padding:10px 15px; border-left: 5px solid {$borde_color}; background-color: #fafbfc;'>
					<div style='display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;'>
						<div style='font-size: 11px; line-height: 1.6;'>
							<div><strong>Método:</strong> <span class='text-primary'>{$pago['metodo']}</span> | <strong>Banco:</strong> {$pago['banco_origen']}</div>
							<div><strong>Referencia:</strong> <code style='font-size:13px; font-weight:bold; color:#0880C5;'>{$pago['referencia']}</code> | <strong>Monto Reportado:</strong> <b style='font-size:12px;'>Bs. {$monto_rep}</b></div>
							<div class='text-muted' style='font-size:10px;'><i class='fa fa-calendar'></i> Reportado el {$fec_rep}</div>
						</div>
						<div style='display: flex; gap: 10px; align-items: center;'>
							<button type='button' class='btn btn-danger' style='width: auto !important; padding: 7px 18px !important; font-size: 13px !important; font-weight: bold; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;' onclick='cambiar_estado_pago(\"{$doc_full}\", \"4\", this)' title='Rechazar pago si no se encontró en el banco para que el cliente corrija la referencia'>
								<i class='fa fa-times-circle'></i> Rechazar Pago
							</button>
						</div>
					</div>
				</td>
			</tr>";
		}

		// Guardar la fila en su grupo: mesa registrada o pedido en caja
		$fila_html = ob_get_clean();
		if ($es_caja) {
			$filas_caja .= $fila_html;
		} else {
			$filas_mesas .= $fila_html;
		}
	}

	echo $filas_mesas;
	if ($filas_caja !== '') {
		echo "
			<tr>
				<td colspan='4' style='background:#5CB85C; color:#fff; font-weight:bold; padding:7px 12px; letter-spacing:.5px; font-size:12px;'>
					<i class='fa fa-shopping-bag'></i> PEDIDOS EN CAJA (comida para llevar)
				</td>
			</tr>" . $filas_caja;
	}

	echo "
				</tbody>
			</table>";
}

if (isset($_GET['accion']) && $_GET['accion'] == "cambiar_estado_pago") {
	header('Content-Type: application/json');
	$documento = trim($_POST['documento'] ?? '');
	$nuevo_estado = trim($_POST['estado'] ?? '1'); // '1' = Confirmado, '4' = Rechazado

	if (empty($documento)) {
		echo json_encode(['success' => false, 'error' => 'Documento requerido']);
		exit;
	}

	$mysqli_sist = @new mysqli($datos_conexion['host'] ?? '127.0.0.1', $datos_conexion['user'] ?? 'root', $datos_conexion['pw'] ?? 'root', $datos_conexion['bd_sistema'] ?? 'mijar26273_sistema', (int)($datos_conexion['port'] ?? 3307));
	if (!$mysqli_sist || $mysqli_sist->connect_errno) {
		echo json_encode(['success' => false, 'error' => 'No se pudo conectar a la base de datos satélite']);
		exit;
	}

	$doc_esc = $mysqli_sist->real_escape_string($documento);
	$est_esc = $mysqli_sist->real_escape_string($nuevo_estado);

	$mysqli_sist->query("UPDATE z_status_pago SET estado = '{$est_esc}', updated_at = NOW() WHERE documento = '{$doc_esc}'");
	$mysqli_sist->close();

	// Notificar inmediatamente a la API Cloud de Laravel
	$url_hosting = rtrim($datos_conexion['url_hosting'] ?? 'https://mijardinrestaurant.com', '/');
	if (strpos($url_hosting, '.test') !== false) {
		$url_hosting = 'https://mijardinrestaurant.com';
	}
	$url_api_cloud = $url_hosting . '/api/confirmar-pago';

	$ch = curl_init($url_api_cloud);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
		'documento' => $documento,
		'estado' => $nuevo_estado,
		'usuario' => 'Mesonero / Comandas Móviles',
	]));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	@curl_exec($ch);
	@curl_close($ch);

	echo json_encode([
		'success' => true,
		'documento' => $documento,
		'estado' => $nuevo_estado,
		'mensaje' => ($nuevo_estado == '1' ? '¡Pago Confirmado y Aprobado!' : 'Pago Rechazado.')
	]);
	exit;
}
if ($_GET['accion'] == "cancelar_pedido") {

	if ($_POST['mesa'] != "") {

		$mesa = 'E' . $_POST['mesa'];

		$where = " documento='" . $mesa . "' ";

		$from = "operti";
		$delete_operti = $objeto_pedido->delete($bd_conexion, $from, $where);

		$from = "opermv";
		$delete_opermv = $objeto_pedido->delete($bd_conexion, $from, $where);

		$from = "comandasmv";
		$delete_comandasmv = $objeto_pedido->delete($bd_conexion, $from, $where);

		$from = "optiimpumv";
		$delete_optiimpumv = $objeto_pedido->delete($bd_conexion, $from, $where);

		if ($delete_opermv == true and $delete_operti == true and $delete_comandasmv == true) {
			echo "
				<i class='fa fa-check'></i><br>Pedido Cancelado
				<script>
					limpiar_productos_agregados();	
					$('#input_cliente').val('');
					ConsultarCliente(codcliente_grabado);
					MesaAutomatica();
				</script>
			";
		} else {
			echo "Ha ocurrido un error";
		}
	}
}

if (isset($_GET['accion']) && $_GET['accion'] == "obtener_impuesto_mesa") {
	header('Content-Type: application/json');
	$mesa_raw = trim($_GET['mesa'] ?? '');
	$doc = 'E' . ltrim($mesa_raw, 'E');
	$impuesto = 0.00;

	$mysqli_sist = @new mysqli($datos_conexion['host'] ?? '127.0.0.1', $datos_conexion['user'] ?? 'root', $datos_conexion['pw'] ?? 'root', $datos_conexion['bd_sistema'] ?? 'mijar26273_sistema', (int)($datos_conexion['port'] ?? 3307));
	if ($mysqli_sist && !$mysqli_sist->connect_errno) {
		$sql = "SELECT impuesto_bs FROM z_mesas_activas WHERE documento = '" . $mysqli_sist->real_escape_string($doc) . "' OR mesa = '" . $mysqli_sist->real_escape_string($mesa_raw) . "' OR mesa = 'MESA " . $mysqli_sist->real_escape_string($mesa_raw) . "' LIMIT 1";
		$res = $mysqli_sist->query($sql);
		if ($res && $row = $res->fetch_assoc()) {
			$impuesto = (float)($row['impuesto_bs'] ?? 0);
		}
		$mysqli_sist->close();
	}

	echo json_encode([
		'impuesto_bs' => $impuesto,
		'impuesto_formateado' => number_format($impuesto, 2, ',', '.')
	]);
	exit;
}
