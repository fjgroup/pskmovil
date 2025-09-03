<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
clearstatcache();

include ("../modelos/modelo_crud.php");
include ("../funciones/funciones.php");

$file = file_get_contents("../datos_conexion.json");
$datos_conexion = json_decode($file, true);

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

			$baseimpo = 'baseimpo1';
			$baseimponible = 0;

			if (
				(trim($datos_config2['codpaisemp']) == '58') or
				(trim($datos_config2['codpaisemp']) == '507') or
				(trim($datos_config2['codpaisemp']) == '504' and $datos_config2['activaimpuestomultiple'] == 0)
			) {
				foreach ($datos_conexion['impuestos'] as $impu) {
					if ($impuesto_prc == $impu['prc']) {
						$baseimpo = "baseimpo" . (int) $impu['codigo'];
						$baseimponible = $precioneto;
					}
				}
			}

			$se_imprimio = 0;

			$totbruto += $precio_bruto * $cantidad;
			$monto_impuesto = $precioneto * $impuesto;
			$total_impuesto += $monto_impuesto;
			$monto_neto += $precioneto;
			//$totalfinal += $preciofin + ($precioneto * $prc_recargo);		
			$totalfinal += $preciofin + ($preciofin * $prc_recargo);
			//$totalfinal += round($preciofin,2) +( round($preciofin,2) * round($prc_recargo,2));	

			$costounit = 0;
			$unidad = '';
			$usaexist = 1;

			foreach ($datos_articulos as $art) {
				if (empty($codhijo)) {
					if ($art['codigo'] == $codigo) {
						$costounit = $art['costo'];
						$unidad = $art['unidad'];
						$usaexist = empty($codhijo) ? $art['usaexist'] : 1; //$art['usaexist'];

						break;
					}
				} else {
					if ($art['codigo'] == $codhijo) {
						$costounit = $art['costo'];
						$unidad = $art['unidad'];
						$usaexist = empty($codhijo) ? $art['usaexist'] : 1; //$art['usaexist'];

						break;
					}
				}
			}

			$aux1 = $usaexist == 1 ? $cantidad : 0;
			if (!empty($nombre_unidad_agrupado)) {
				$unidad = $nombre_unidad_agrupado;
			}

			$totcosto += $costounit * $cantidad;

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
						estacion,
						" . $baseimpo . ",
						se_imprimio,
						se_guardo,
						enviado_kmonitor,
						fechadocant,
						fechayhora,
						costounit
					";
			$pid_art = !empty($codhijo) ? $pidpadre : $pid;
			$impuesto_prc1 = $preciounit*($impuesto_prc/100);
			$values = "
						'" . $datos_conexion['cod_empresa'] . "',
						'" . $datos_conexion['cod_agencia'] . "',
						'" . $datos_conexion['tipo_doc'] . "',
						'$mesa',
						'$codgrupo',
						'$codsubgrupo',
						'1',
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
						'PSK',
						'" . number_format($baseimponible, 2, '.', '') . "',
						" . $se_imprimio . ",
						'$se_guardo',
						'2',
						'" . $fecha_hoy . "',
						'" . $fechayhora . "',
						 '" . $costounit . "'
					";
			//print_r($columnas);
			// GDM - Al código de la estación se esta enviando directamente PSK, anteriormente se enviaba: ".substr(trim($_POST['vendedor']),-3)."
			if (strpos($datos[6], '-') > -1) {

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
							notas = '$notas',							
						" . $baseimpo . " = '" . number_format($precioneto, 2, '.', '') . "'
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
									'" . strtoupper($_POST['nrorif']) . "',
									'00',
									'DIRECCION',
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
								rif = '" . strtoupper($_POST['nrorif']) . "',
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
	while ($pedido = mysqli_fetch_array($consulta)) {

		$pedido['totalfinal'] = $pedido['totalfinal'] + ($pedido['totalfinal'] * $recargo);

		//$pedido['documento'] = str_replace('E','',$pedido['documento']);
		$pedido['documento'] = substr($pedido['documento'], 1, strlen($pedido['documento']));
		$codigo_vendedor = "";
		echo "		
			<tr>
				<td style='text-align:center;'>
					<div class='btn btn-warning' style='width:40px;padding:5px;font-size:13px;' onclick='seleccionar_mesa(" . json_encode($pedido['documento']) . "," . json_encode(trim($pedido['codcliente'])) . "," . json_encode($pedido['nombrecli']) . ",".'"'.$codigo_vendedor.'"'."," . json_encode($pedido['nrorif']) . ")'>
						<i class='fa fa-edit'></i>
					</div>
				</td>				
				<td>" . $pedido['documento'] . "</td>
				<td>" . strtoupper($pedido['nombrecli']) . "</td>
				<td class='text-right'>" . number_format($pedido['totalfinal'], 2, ',', '.') . "</td>
			</tr>		
		";
	}

	echo "
				</tbody>
			</table>
	";
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
