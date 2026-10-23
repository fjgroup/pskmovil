<?php
$file = file_get_contents('datos_conexion.json');
$file = json_decode($file, true);
$titulo_web = !empty($file['nombre_empresa']) ? htmlspecialchars($file['nombre_empresa']) : 'Comandas Móviles';
?>
<!DOCTYPE html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	<title><?php echo $titulo_web; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta charset="UTF-8" />
	<link rel="shortcut icon" href="imagenes/favicon.png" />
	<link rel="stylesheet" href="css/bootstrap.css" />
	<link rel="stylesheet" href="css/font-awesome.css" />
	<link rel="stylesheet" href="css/pedidos.css" />
	<script src="js/jquery-1.11.3.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/pedidos.js"></script>
	<script type="text/javascript">
		var codcliente_grabado = '<?php echo $file['cod_cliente']; ?>';
		var minutos_inactividad = '<?php echo $file['minutos_inactividad']; ?>';
	</script>

</head>

<body>
	<input type="text" id="id_empresa" value="<?php echo @$_GET['id']; ?>" readonly />

	<div id="cargando"><img src="imagenes_internas/loading.gif" /></div>

	<div class="ventanas" id="msj_php">
		<div class="titulos">Mensaje del sistema</div>
		<div class="ventanas_contenido"></div>
	</div>

	<div id="bloqueo" class="ventanas"></div>

	<div class="modal fade" id="imagen_amplia" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
							aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Detalle del Art&iacute;culo</h4>
				</div>
				<div class="modal-body text-center">
					<div id="img"><img src="" style="max-height: 250px; max-width: 100%; object-fit: contain; margin: 0 auto 10px; display: block;" /></div>
					
					<div id="modal_producto_precio" style="font-size: 19px; color: #0880C5; font-weight: bold; margin-bottom: 8px;"></div>
					
					<div id="modal_precio_wrapper" style="max-width: 280px; margin: 0 auto 10px; display: none;">
						<label style="font-size: 13px; color: #555; margin-bottom: 4px; font-weight: normal;">Precio:</label>
						<select id="modal_select_precio" class="form-control" style="font-size: 15px; height: 38px;"></select>
					</div>

					<div style="max-width: 220px; margin: 0 auto 10px;">
						<label style="font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px;">Cantidad a agregar:</label>
						<div class="input-group" style="margin: 0 auto;">
							<span class="input-group-btn">
								<button type="button" class="btn btn-default" id="modal_btn_cant_menos" style="height: 42px; width: 44px; font-size: 18px;"><i class="fa fa-minus"></i></button>
							</span>
							<input type="text" id="modal_cantidad" class="form-control text-center" value="1" onkeypress="return justNumbers(event);" style="height: 42px; font-size: 20px; font-weight: bold;" />
							<span class="input-group-btn">
								<button type="button" class="btn btn-default" id="modal_btn_cant_mas" style="height: 42px; width: 44px; font-size: 18px;"><i class="fa fa-plus"></i></button>
							</span>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="btn_agregar_desde_imagen"><i class="fa fa-plus"></i>&nbsp;Agregar al pedido</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>

	<div id="print_comand"></div>

	<div class="ventanas" id="div_agrupado">
		<div class="titulos">Seleccione unidad</div>
		<div class="ventanas_contenido">
			<div id="unidad">
				<i class="fa fa-file-o"></i><br>
				<span></span>
			</div>
			<div id="agrupado">
				<i class="fa fa-files-o"></i><br>
				<span></span>
			</div>
		</div>
	</div>


	<div class="modal fade" id="modal_compuestos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
							aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">Componentes</h4>
				</div>
				<div class="modal-body">
					<div id="lista_compuestos"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success" id="btn_adicionar_componentes">Adicionar</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				</div>
			</div>
		</div>
	</div>

	<div class="ventanas" id="busquedas">
		<div class="titulos">Ventana de busqueda</div>
		<div class="ventanas_contenido">
			Buscar por <select id="buscar_por">
				<option>Codigo</option>
				<option>Nombre</option>
			</select>
			<div class="input-group">
				<input type="text" class="form-control" id="palabra" placeholder="Buscar..." />
				<div class="input-group-addon"><i class="fa fa-search"></i></div>
			</div>
			<div id="busquedas_resultados"></div>
			<div class="btn btn-danger" onclick="ocultar_busqueda()">Cancelar</div>
		</div>
	</div>

	<div id="cantidad" class="ventanas">
		<div class="titulos">Cantidad</div>
		<div class="ventanas_contenido">
			<select id="select_precio" class="form-control">
				<option value="">Seleccione un precio</option>
			</select>
			<div class="input-group">
				<div class="input-group-addon" onclick="cantidad(-1)"><i class="fa fa-minus"></i></div>
				<input type="text" class="form-control" value="1" onkeypress="return justNumbers(event);" />
				<div class="input-group-addon" onclick="cantidad(1)"><i class="fa fa-plus"></i></div>
			</div>
			<div style="display: flex; gap: 10px; margin-top: 10px; width: 100%; box-sizing: border-box;">
				<div class="btn btn-success" style="flex: 1; box-sizing: border-box;">Procesar</div>
				<div class="btn btn-danger" onclick="ocultar_cantidad()" style="flex: 1; box-sizing: border-box;">Cancelar</div>
			</div>
		</div>
	</div>

	<form id="form_autorizar_admin">
		<div class="ventanas" id="pw_administrador">
			<div class="titulos">Mensaje del sistema</div>
			<div class="ventanas_contenido">
				Ingrese contrase&ntilde;a de Supervisor:
				<input type="password" class="form-control" name="password_sup" id="password_sup" />
				<button type="submit" class="btn btn-success">Aceptar</button>
				<div id="btn_cancelar_pw_admin" class="btn btn-danger" onclick="ocultar_pw_administrador()">Cancelar
				</div>
				<br><i></i>
			</div>
		</div>
	</form>

	<div class="ventanas" id="notas">
		<div class="titulos">Notas del producto</div>
		<div class="ventanas_contenido">
			<textarea class="form-control" placeholder="Notas..."></textarea>
			<div class="btn btn-success">Procesar</div>
			<div class="btn btn-danger" onclick="ocultar_notas()">Cancelar</div>
		</div>
	</div>

	<div class="ventanas" id="descuentos">
		<div class="titulos">Aplicar Descuento</div>
		<div class="ventanas_contenido">
			Descuento por Porcentaje
			<div class="input-group">
				<input type="text" class="form-control text-right" name="prc_descuento" id="prc_descuento"
					onclick="$(this).val('')" />
				<div class="input-group-addon"><i class="fa fa-percent"></i></div>
			</div>
			Descuento por Monto
			<div class="input-group">
				<div class="input-group-addon"><i class="fas fa-dollar-sign"></i></div>
				<input type="text" class="form-control text-right" name="monto_descuento" id="monto_descuento"
					onclick="$(this).val('')" />
			</div>
			<div class="btn btn-success">Procesar</div>
			<div class="btn btn-danger" onclick="ocultar_descuentos()">Cancelar</div>
		</div>
	</div>

	<div class="ventanas" id="login">
		<div class="titulos">Inicie Sesion</div>
		<div class="ventanas_contenido">
			Vendedor
			<div id="lista_vendedores">
				<select class="form-control">
					<option></option>
				</select>
			</div>
			Contrase&ntilde;a
			<input type="password" class="form-control" onkeydown="if(event.keyCode == 13)iniciar_sesion()" />
			<div class="btn btn-success" onclick="iniciar_sesion()">Entrar</div>
			<div class="btn btn-primary btn-xs" onclick="autorizar_conf_conexion()">Configurar Conexion</div>
			<i id="msj_login"></i>
		</div>
	</div>

	<div class="ventanas" id="conf_conexion">
		<form id="form_conexion">
			<div class="titulos">Configuracion de conexion</div>
			<div class="ventanas_contenido">
				<div class="titulos_puntos">Parametros de conexion</div><br>
				<div class="row">
					<div class="col-md-8">
						Host/Dns
						<input type="text" class="form-control" name="host" id="host" placeholder="Host/Dns"
							onkeyup="limpiar_parametros_adicionales()" />
					</div>
					<div class="col-md-4">
						Puerto
						<input type="text" class="form-control" name="port" id="port" placeholder="Puerto"
							onkeyup="limpiar_parametros_adicionales()" />
					</div>
					<div class="col-md-6">
						Usuario Mysql
						<input type="text" class="form-control" name="user" id="user" placeholder="Usuario Mysql"
							onkeyup="limpiar_parametros_adicionales()" />
					</div>
					<div class="col-md-6">
						Password Mysql
						<input type="password" class="form-control" name="pw" id="pw" placeholder="Password Mysql"
							onkeyup="limpiar_parametros_adicionales()" />
					</div>
					<div class="col-md-12">
						Nombre de BD
						<input type="text" class="form-control" name="bd" id="bd" placeholder="Nombre de BD"
							onkeyup="limpiar_parametros_adicionales()" />
					</div>
				</div>
				<div class="btn btn-primary" onclick="probar_conexion_temporal()">Probar conexion</div>
				<i class="msj_conexion"></i>
				<br>
				<div class="titulos_puntos">Parametros adicionales</div><br>
				<div class="row" id="parametros_adicionales">
					<div class="col-md-12" style="margin-bottom:10px;">
						<b>Logo de Empresa:</b><br>
						<small>
							<i class="fa fa-info-circle"></i> Para colocar el logo de la empresa solo debe reemplazar la
							imagen <b>logo_empresa.png</b> que se encuentra en la raiz del sistema. <b>Dimensiones
								Recomendadas: (60px X 200px)</b>
							<small>
					</div>
					<div class="col-md-8">
						Nombre de Empresa
						<input type="text" class="form-control" name="nombre_empresa" id="nombre_empresa"
							placeholder="Nombre de Empresa" />
					</div>
					<div class="col-md-4">
						Cod. Empresa
						<input type="text" class="form-control" name="cod_empresa" id="cod_empresa"
							placeholder="Codigo de Empresa" readonly />
					</div>
					<div class="col-md-6">
						Codigo Agencia
						<div class="input-group">
							<input type="text" class="form-control" name="cod_agencia" id="cod_agencia"
								placeholder="Codigo Agencia" readonly />
							<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('agencia')"><i
									class="fa fa-search"></i></div>
						</div>
					</div>
					<div class="col-md-6">
						Codigo Cliente
						<div class="input-group">
							<input type="text" class="form-control" name="cod_cliente" id="cod_cliente"
								placeholder="Codigo Cliente" readonly />
							<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('cliente')"><i
									class="fa fa-search"></i></div>
						</div>
					</div>
					<div class="col-md-6">
						Codigo Sector/Zona
						<div class="input-group">
							<input type="text" class="form-control" name="cod_zona" id="cod_zona"
								placeholder="Codigo Sector/Zona" readonly />
							<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('zona')"><i
									class="fa fa-search"></i></div>
						</div>
					</div>
					<div class="col-md-6">
						Codigo Almacen
						<div class="input-group">
							<input type="text" class="form-control" name="cod_deposito" id="cod_deposito"
								placeholder="Codigo Almacen/Deposito" readonly />
							<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('almacen')"><i
									class="fa fa-search"></i></div>
						</div>
					</div>
				</div>
				<div class="col-md-12">
					Porcentaje de Recargo/Servicio
					<input type="text" class="form-control" name="recargo" id="recargo"
						placeholder="Porcentaje de Recargo/Servicio" />
					Password Supervisor <i class="fa fa-lock"></i>
					<input type="password" class="form-control" placeholder="Password supervisor" name="pw_supervisor"
						id="pw_supervisor" />
					Cantidad Art&iacute;culos Por P&aacute;gina
					<input type="number" class="form-control" name="cantidad_por_pagina" id="cantidad_por_pagina"
						placeholder="Cantidad Art&iacute;culos Por P&aacute;gina" />
					Ancho de la Pre-Cuenta (Centimetros)
					<input type="text" class="form-control" name="cm_ancho_precuenta" id="cm_ancho_precuenta"
						placeholder="Centimetros" />
				</div>
				<div class="col-md-12">
					&#191;Que tipo de documento desea generar&#63;
					<select class="form-control" name="tipo_doc" id="tipo_doc">
						<option value="">Tipo de Documento</option>
						<option value="ESP">Operaciones en Espera</option>
						<option value="PED">Pedidos</option>
					</select>
				</div>
				Minutos de Inactividad
				<input type="text" class="form-control" name="minutos_inactividad" id="minutos_inactividad"
					placeholder="Minutos" />
				<div class="col-md-12">
					<label>
						<input type="checkbox" name="compartir_mesas" id="compartir_mesas" />&nbsp;
						Compartir Mesas
					</label><br>
					<label>
						<input type="checkbox" name="usar_subgrupos" id="usar_subgrupos" />&nbsp;
						Usar Subgrupos
					</label><br>
					<label>
						<input type="checkbox" name="agrupar_mesas" id="agrupar_mesas_conf" />&nbsp;
						Agrupar mesas por ubicaci&oacute;n
					</label><br>
					<label>
						<input type="checkbox" name="no_usar_impuestos" id="no_usar_impuestos" />&nbsp;
						No Cobrar Impuestos
					</label><br>
					<label>
						<input type="checkbox" name="no_editar_integrados" id="no_editar_integrados_conf" />&nbsp;
						No Editar Art&iacute;culos Integrados
					</label><br>
					<label>
						<input type="checkbox" name="ocultar_integrados" id="ocultar_integrados_conf" />&nbsp;
						Ocultar Art&iacute;culos Integrados
					</label><br>
					<label>
						<input type="checkbox" name="no_tiene_existencia" id="no_tiene_existencia_conf" />&nbsp;
						No permitir Art&iacute;culos sin existencias
					</label><br>
					<label>
						<input type="checkbox" name="permitir_descuentos" id="permitir_descuentos_conf" />&nbsp;
						Permitir Descuentos a las Comidas
					</label><br>
				<label>
					<input type="checkbox" name="ocultar_campos" id="ocultar_campos_conf" />&nbsp;
					Ocultar campos
				</label><br>
				<label>
					<input type="checkbox" name="ocultar_mesas_automaticas" id="ocultar_mesas_automaticas_conf" />&nbsp;
					Ocultar mesas automaticas
				</label><br>
			</div>
				<div class="btn btn-success" onclick="registrar_conf_conexion();">Procesar</div>
				<div class="btn btn-danger" onclick="ocultar_conf_conexion()">Cancelar</div>
				<br>
				<i class="msj_conexion"></i>
				<br><br>
			</div>
		</form>
	</div>

	<form id="form_precuenta" action="vistas/precuenta.php" method="POST" target="_blank">
		<div class="ventanas" id="detalle_cuenta">
			<div>
				<div class="titulos">Detalle de la Cuenta</div>
				<div class="ventanas_contenido">
					<input type="hidden" id="precio_neto_total_precuenta" name="precio_neto_total_precuenta" />
					<input type="hidden" id="subtotal_precuenta" name="subtotal_precuenta" />
					<input type="hidden" id="precio_total_precuenta" name="precio_total_precuenta" />
					<input type="hidden" id="total_impuesto_precuenta" name="total_impuesto_precuenta" />
					<input type="hidden" id="resumir_lineas_precuenta" name="resumir_lineas_precuenta" />
					<input type="hidden" id="input_mesa_precuenta" name="input_mesa_precuenta" />
					<input type="hidden" id="input_cliente_precuenta" name="input_cliente_precuenta" />
					<input type="hidden" id="input_vendedor_precuenta" name="input_vendedor_precuenta" />
					<input type="hidden" id="total_moneda_extranjera_precuenta"
						name="total_moneda_extranjera_precuenta" />
					<div id="productos_precuenta"></div>
					Precio Neto
					<input type="text" class="form-control" id="precio_neto_total" readonly />
					<div id="lista_impuestos_detalle"></div>
					Descuento
					<input type="text" class="form-control" id="total_descuento" readonly />
					SubTotal
					<input type="text" class="form-control" id="subtotal" readonly />
					<label for="">Recargo</label>
					<input type="text" class="form-control" id="recargo_total" readonly />
					<label for="" id="texto_total">Total <span class="simbolo_moneda_nacional"></span></label>
					<input type="text" class="form-control precio_total" id="precio_total" readonly value="0,00" />
					<div id="div_total_moneda_extranjera" style="display: none;">
						<input type="hidden" id="dividemultiplica" />
						<input type="hidden" id="monedafactor" />
						Total <span class="simbolo_moneda_extranjera"></span>
						<input type="text" class="form-control precio_total_moneda_extranjera"
							id="precio_total_moneda_extranjera" readonly value="0,00" />
					</div>
					<button type="button" class="btn btn-danger" onclick="ocultar_detalle_cuenta()">Cerrar</button>
					<button type="submit" id="precuenta" class="btn btn-primary"><i class="fa fa-echo"></i>&nbsp;Pre-Cuenta</button>
				</div>
			</div>
		</div>
	</form>

	<form id="form_reg_cli">
		<div class="ventanas" id="reg_cli">
			<div class="titulos">Registro de Cliente</div>

			<div class="ventanas_contenido">

				<div class="row">
					<div class="col-md-4 col-sm-6 col-xs-6">
						<label for="codigo">Código</label>
						<input type="text" class="form-control" name="codigo" id="codigo" placeholder="Codigo"
							onchange="ConsultarCliente($(this).val())" required />
					</div>
					<div class="col-md-4 col-sm-6 col-xs-6">
						<label for="nombre">Nombre</label>
						<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre" required />
					</div>
					<div class="col-md-4 col-sm-6 col-xs-6">
						<label for="perscont">Persona Contacto</label>
						<input type="text" class="form-control" name="perscont" id="perscont" placeholder="Persona Contacto" />
					</div>

					<div class="col-md-4 col-sm-6 col-xs-6">
						<label for="cedula">Cedula</label>
						<input type="text" class="form-control" name="cedula" id="cedula" placeholder="Cedula" />
					</div>
					<div class="col-md-4 col-sm-6 col-xs-6">
						<label id="label-fiscal-1" for="nrorif">Nro. Fiscal #1</label>
						<input type="text" class="form-control" name="nrorif" id="nrorif" placeholder="Nro. Fiscal #1" />
					</div>
					<div class="col-md-4 col-sm-6 col-xs-6" id="div-fiscal-2">
						<label for="nronit">Nro. Fiscal #2</label>
						<input type="text" class="form-control" name="nronit" id="nronit" placeholder="Nro. Fiscal #2" />
					</div>

					<div class="col-md-4 col-sm-6 col-xs-6">
						<label for="telefonos">Teléfono</label>
						<input type="text" class="form-control" name="telefonos" id="telefonos" placeholder="Teléfono" />
					</div>
					<div class="col-md-4 col-sm-6 col-xs-6">
						<label for="email">Email</label>
						<input type="text" class="form-control" name="email" id="email" placeholder="Email" />
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<label for="direccion">Dirección</label>
						<textarea class="form-control" name="direccion" id="direccion" placeholder="Dirección"></textarea>
					</div>

					<div class="col-md-12">
						<label>
							<input type="checkbox" name="formafis" />
							Es Exento
						</label>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 d-flex justify-content-center" style="margin-top:10px;">
						<button type="submit" class="btn btn-success" style="margin-right: 10px;">Guardar</button>
						<button type="button" class="btn btn-danger" onclick="$('#reg_cli').hide();">Cancelar</button>
						<span id="msj_reg_cli"></span>
					</div>
				</div>

			</div>
		</div>
	</form>

	<div id="contenedor">
		<div id="div_izquierda">
			<div id="logo" class="logo-hero">
				<img src="imagenes/mi jardin.webp" alt="Mi Jardín" />
			</div>
			<div class="col-md-12 detalles_cuenta">
				<div class="input-group">
					<input type="text" class="form-control precio_total precio_total_principal" id="precio_total" readonly value="0,00" />
					<input style="float:right;background-color:#000;color:#FFF;text-align:right;font-size:18px;"
						type="text" class="form-control precio_total_ref" id="precio_total_ref" readonly value="0,00" />
					<div class="input-group-addon" onclick="mostrar_detalle_cuenta()"><i class="fa fa-ellipsis-h"></i>
					</div>
				</div>
				<div class="text-end" style="position: relative; margin-bottom:19.5px">
					<small class="form-text text-muted" style="font-size: 1.3em; position:absolute; right:0; ">
						TASA DEL DIA: <strong id="tasa_del_dia"><?php echo $file['monedafactor']; ?></strong>
					</small>
				</div>
			</div>
			<div>
				<div id="pestanas">
					<li onclick="ver_operacion_actual()">Cuenta</li>
					<li onclick="ver_transacciones_transito()">Transito</li>
					<li onclick="ver_div_derecha()">Procesos</li>
				</div>
				<div id="operacion_actual">
					<input type="text" readonly id="cod_vend">
					<input type="hidden" id="cuentas_compartidas" name="cuentas_compartidas" />
					<input type="hidden" id="agrupar_mesas" />
					<input type="hidden" id="no_editar_integrados" />
					<input type="hidden" id="ocultar_integrados" />
					<input type="hidden" id="cliente_exento" />
					<input type="hidden" id="no_tiene_existencia" />
					<input type="hidden" id="permitir_descuentos" value="<?php echo isset($file['permitir_descuentos']) ? $file['permitir_descuentos'] : 'si'; ?>" />
					<div class="input-group">
						<input type="text" class="form-control" id="input_vendedor" placeholder="Vendedor" readonly />
						<div class="input-group-addon" onclick="window.location.href=''"
							style="background-color:#D9534F;color:#FFF;"><i class="fa fa-power-off"></i></div>
					</div>

					<div class="input-group">
						<div class="input-group-addon" onclick="mostrar_busqueda('cliente')"><i
								class="fa fa-search"></i></div>
						<input type="text" class="form-control" name="cod_cliente" id="cod_cliente_ped"
							placeholder="Codigo Cliente" value="<?php echo $file['cod_cliente']; ?>"
							onchange="ConsultarCliente($(this).val())" />
						<div class="input-group-addon" onclick="mostrar_modal_reg_cli()">Nuevo</div>
					</div>

					<input type="text" class="form-control" id="input_cliente" placeholder="Cliente"
						value="Cliente contado" onchange="cliente_contado()" onclick="$(this).val('');" />
					<input type="text" class="form-control" id="nrorif" placeholder="Nro Identificación"
						value="" />

					<div class="input-group" id="contenedor_mesa_automatica">
						<input type="text" class="form-control" id="input_mesa" placeholder="Mesa"
							onchange="consultar_mesa()" />
						<div class="input-group-addon">
							Autom&aacute;tico&nbsp;<input type="checkbox" id="mesa_automatica"
								onclick="MesaAutomatica()" />
						</div>
					</div>
					<div style="text-align:center">
						<div class="btn btn-success" onclick="procesar_pedido('1')"><i
								class="fa fa-check"></i>&nbsp;Procesar</div>
						<div class="btn btn-warning" onclick="procesar_pedido('2')"><i
								class="fa fa-echo"></i>&nbsp;Comanda</div>
						<div class="btn btn-danger" id="cancelarButton" onclick="cancelar_operacion()"><i
								class="fa fa-trash"></i>&nbsp;Cancelar</div>
					</div>
					<div id="productos_agregados"></div>
					<div style="margin-top:5px;margin-bottom:40px;">
						<label>
							<input type='checkbox' id='resumir_lineas' onclick="ResumirLineas()" checked /> Resumir
							Lineas
						</label>
					</div>
				</div>
				<div id="en_transito"></div>
			</div>
		</div>
		<div id="div_derecha">
			<div class="btn-opt" onclick="mostrar_filtros_mesas()">Mesas</div>
			<div class="btn-opt" onclick="mostrar_grupos()">Grupos</div>
			<div class="btn-opt" onclick="ver_div_izquierda()">Operacion</div>

			<div id="filtros_mesa">
				<div class="btn-opt" id="btn_mis_mesas">Mis Mesas</div>
				<div class="btn-opt" id="btn_mesas_disp">Mesas Disponibles</div>
				<div class="btn-opt" id="todas_mesas">Todas las Mesas</div>
			</div>

			<form id="form_busqueda_inventario" action="" method="POST">
				<div style="max-width: 500px;margin:auto;">
					<div class="input-group">
						<div class="input-group-addon" style="padding:0px;">
							<button type="submit" class="btn"><i class="fa fa-search"></i></button>
						</div>
						<input type="text" class="form-control" name="texto_busqueda" id="texto_busqueda"
							placeholder="Buscar..." />
					</div>
				</div>
			</form>

			<div id="inventario"></div>

			<div class="paginado"></div>
		</div>
	</div>
	<footer>
		<strong>Comandas Móviles v2.0</strong> &copy; <?php echo date('Y'); ?> | 
		<a href="https://wa.me/584128172337?text=Hola%20C%C3%A9sar,%20te%20contacto%20desde%20el%20sistema%20Comandas%20M%C3%B3viles" target="_blank" title="Contactar por WhatsApp">
			<i class="fa fa-whatsapp" style="color: #25D366; font-size: 14px;"></i> Actualizado por César Figueroa
		</a>
		| <a onclick="launchIntoFullscreen(document.documentElement)" style="cursor: pointer;">Pantalla Completa</a>
	</footer>
</body>

</html>

<script src="js/script.js"></script>
<script src="js/conect.js"></script>