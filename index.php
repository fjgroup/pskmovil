<?php
require_once __DIR__ . '/funciones/config_helper.php';
$file = obtener_config_sistema();
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
	<link rel="stylesheet" href="css/pedidos.css?v=<?php echo time(); ?>" />
	<script src="js/jquery-1.11.3.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/pedidos.js?v=<?php echo time(); ?>"></script>
	<script src="js/qrcode.min.js?v=<?php echo time(); ?>"></script>
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
			<div class="btn btn-primary btn-xs" onclick="autorizar_conf_conexion()"><i class="fa fa-cog"></i> Configuración</div>
			<i id="msj_login"></i>
		</div>
	</div>

	<div class="ventanas" id="conf_conexion">
		<form id="form_conexion">
			<div class="titulos" style="display:flex; justify-content:space-between; align-items:center; padding: 10px 20px; margin-bottom: 0;">
				<span><i class="fa fa-cogs"></i> Configuración del Sistema</span>
				<i class="fa fa-times" style="cursor:pointer; font-size:18px;" onclick="ocultar_conf_conexion()" title="Cerrar"></i>
			</div>
			<div class="ventanas_contenido" style="padding: 15px 20px; max-height: calc(90vh - 60px); overflow-y: auto;">
				
				<!-- SECCIÓN 1: BASES DE DATOS LOCALES (PUERTO 3307) -->
				<div class="seccion_config_box">
					<div class="seccion_config_titulo">
						<i class="fa fa-database"></i> 1. Bases de Datos Locales (Restaurante - Puerto 3307)
					</div>
					<div class="row">
						<div class="col-md-6 col-sm-6">
							<label>Host / IP Local MySQL:</label>
							<input type="text" class="form-control" name="host" id="host" placeholder="127.0.0.1 o 192.168.0.102"
								onkeyup="limpiar_parametros_adicionales()" />
						</div>
						<div class="col-md-6 col-sm-6">
							<label>Puerto Local MySQL:</label>
							<input type="text" class="form-control" name="port" id="port" value="3307" placeholder="3307"
								onkeyup="limpiar_parametros_adicionales()" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Usuario MySQL:</label>
							<input type="text" class="form-control" name="user" id="user" placeholder="root"
								onkeyup="limpiar_parametros_adicionales()" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Contraseña MySQL:</label>
							<input type="password" class="form-control" name="pw" id="pw" placeholder="Password MySQL"
								onkeyup="limpiar_parametros_adicionales()" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>BD 1: Premium Soft (Fiscal / SENIAT):</label>
							<input type="text" class="form-control" name="bd" id="bd" value="adminbahiap" placeholder="adminbahiap"
								onkeyup="limpiar_parametros_adicionales()" />
							<small class="text-muted" style="font-size:11px; display:block; margin-top:2px;">
								<i class="fa fa-shield"></i> Base fiscal homologada por el SENIAT (Esquema intocable).
							</small>
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>BD 2: Satélite Local (Mesas & Config):</label>
							<input type="text" class="form-control" name="bd_sistema" id="bd_sistema" value="mijar_sistema" placeholder="mijar_sistema" />
							<small class="text-muted" style="font-size:11px; display:block; margin-top:2px;">
								<i class="fa fa-check-circle"></i> Guarda tokens PIN, estados y configuración sin tocar Premium Soft.
							</small>
						</div>
					</div>
					<div style="margin-top: 12px;">
						<div class="btn btn-primary" onclick="probar_conexion_temporal()">
							<i class="fa fa-plug"></i> Probar Conexión Local
						</div>
						<i class="msj_conexion"></i>
					</div>
				</div>

				<!-- SECCIÓN 2: HOSTING Y NUBE (SERVIDOR DEDICADO) -->
				<div class="seccion_config_box">
					<div class="seccion_config_titulo">
						<i class="fa fa-cloud-upload"></i> 2. Conexión con el Hosting Cloud (Servidor Dedicado / VPS)
					</div>
					<div class="row">
						<div class="col-md-6 col-sm-6">
							<label>URL Base del Hosting Web:</label>
							<input type="text" class="form-control" name="url_hosting" id="url_hosting" placeholder="https://mijardinrestaurant.com" />
							<small class="text-muted" style="font-size:11px;">Ej: https://mijardinrestaurant.com o https://mijardinrestaurant.test</small>
						</div>
						<div class="col-md-6 col-sm-6">
							<label>URL Consulta Mesa para Clientes (QR):</label>
							<input type="text" class="form-control" name="url_web_mesa" id="url_web_mesa" placeholder="https://mijardinrestaurant.com/mesa" />
							<small class="text-muted" style="font-size:11px;">Enlace que escanean los comensales con el PIN de la mesa.</small>
						</div>
						<div class="col-md-12" style="margin-top: 6px;">
							<small class="text-info" style="font-size:11px;">
								<i class="fa fa-lock"></i> La sincronización se realiza mediante peticiones salientes seguras HTTPS POST hacia <b>/api/sincronizar-mesas</b>.
							</small>
						</div>
					</div>
				</div>

				<!-- SECCIÓN 3: PARÁMETROS OPERATIVOS DEL RESTAURANTE -->
				<div class="seccion_config_box">
					<div class="seccion_config_titulo">
						<i class="fa fa-sliders"></i> 3. Parámetros Operativos del Restaurante
					</div>
					<div class="row" id="parametros_adicionales">
						<div class="col-md-12" style="margin-bottom:10px;">
							<b>Logo de Empresa:</b><br>
							<small>
								<i class="fa fa-info-circle"></i> Para colocar el logo de la empresa solo debe reemplazar la
								imagen <b>logo_empresa.png</b> que se encuentra en la raiz del sistema. <b>Dimensiones
									Recomendadas: (60px X 200px)</b>
							</small>
						</div>
						<div class="col-md-8 col-sm-8">
							<label>Nombre de Empresa:</label>
							<input type="text" class="form-control" name="nombre_empresa" id="nombre_empresa"
								placeholder="Nombre de Empresa" />
						</div>
						<div class="col-md-4 col-sm-4">
							<label>Cod. Empresa:</label>
							<input type="text" class="form-control" name="cod_empresa" id="cod_empresa"
								placeholder="Codigo de Empresa" readonly />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Codigo Agencia:</label>
							<div class="input-group">
								<input type="text" class="form-control" name="cod_agencia" id="cod_agencia"
									placeholder="Codigo Agencia" readonly />
								<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('agencia')"><i
										class="fa fa-search"></i></div>
							</div>
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Codigo Cliente:</label>
							<div class="input-group">
								<input type="text" class="form-control" name="cod_cliente" id="cod_cliente"
									placeholder="Codigo Cliente" readonly />
								<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('cliente')"><i
										class="fa fa-search"></i></div>
							</div>
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Codigo Sector/Zona:</label>
							<div class="input-group">
								<input type="text" class="form-control" name="cod_zona" id="cod_zona"
									placeholder="Codigo Sector/Zona" readonly />
								<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('zona')"><i
										class="fa fa-search"></i></div>
							</div>
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Codigo Almacen / Depósito:</label>
							<div class="input-group">
								<input type="text" class="form-control" name="cod_deposito" id="cod_deposito"
									placeholder="Codigo Almacen/Deposito" readonly />
								<div class="input-group-addon btn-primary-addon" onclick="mostrar_busqueda('almacen')"><i
										class="fa fa-search"></i></div>
							</div>
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Porcentaje de Recargo/Servicio (%):</label>
							<input type="text" class="form-control" name="recargo" id="recargo"
								placeholder="Porcentaje de Recargo/Servicio" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Password Supervisor <i class="fa fa-lock"></i>:</label>
							<input type="password" class="form-control" placeholder="Password supervisor" name="pw_supervisor"
								id="pw_supervisor" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Cantidad Artículos Por Página:</label>
							<input type="number" class="form-control" name="cantidad_por_pagina" id="cantidad_por_pagina"
								placeholder="Cantidad Artículos Por Página" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Ancho Pre-Cuenta (cm):</label>
							<input type="text" class="form-control" name="cm_ancho_precuenta" id="cm_ancho_precuenta"
								placeholder="Centimetros" />
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Tipo de Documento a Generar:</label>
							<select class="form-control" name="tipo_doc" id="tipo_doc">
								<option value="">Tipo de Documento</option>
								<option value="ESP">Operaciones en Espera</option>
								<option value="PED">Pedidos</option>
							</select>
						</div>
						<div class="col-md-6 col-sm-6" style="margin-top:8px;">
							<label>Minutos de Inactividad:</label>
							<input type="text" class="form-control" name="minutos_inactividad" id="minutos_inactividad"
								placeholder="Minutos" />
						</div>
						<div class="col-md-12" style="margin-top:12px;">
							<label style="margin-right:15px;">
								<input type="checkbox" name="compartir_mesas" id="compartir_mesas" />&nbsp;
								Compartir Mesas
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="usar_subgrupos" id="usar_subgrupos" />&nbsp;
								Usar Subgrupos
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="agrupar_mesas" id="agrupar_mesas_conf" />&nbsp;
								Agrupar mesas por ubicación
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="no_usar_impuestos" id="no_usar_impuestos" />&nbsp;
								No Cobrar Impuestos
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="no_editar_integrados" id="no_editar_integrados_conf" />&nbsp;
								No Editar Artículos Integrados
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="ocultar_integrados" id="ocultar_integrados_conf" />&nbsp;
								Ocultar Artículos Integrados
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="no_tiene_existencia" id="no_tiene_existencia_conf" />&nbsp;
								No permitir Artículos sin existencias
							</label>
							<label style="margin-right:15px;">
								<input type="checkbox" name="permitir_descuentos" id="permitir_descuentos_conf" />&nbsp;
								Permitir Descuentos a las Comidas
							</label>
						</div>
					</div>
				</div>

				<!-- BOTONES DE ACCIÓN PRINCIPALES -->
				<div style="display:flex; gap:10px; align-items:center; margin-top:15px; margin-bottom:10px;">
					<button type="button" class="btn btn-success btn-lg" onclick="registrar_conf_conexion();" style="font-weight:bold; font-size:14px; padding: 10px 22px;">
						<i class="fa fa-save"></i> Actualizar BD remota
					</button>
					<button type="button" class="btn btn-danger btn-lg" onclick="ocultar_conf_conexion()" style="font-size:14px; padding: 10px 20px;">
						<i class="fa fa-times"></i> Cancelar
					</button>
					<i class="msj_conexion" style="font-weight:bold; margin-left:10px;"></i>
				</div>

				<hr style="border-top: 1px solid #ccc; margin: 20px 0 15px 0;">
				<div class="titulos_puntos"><i class="fa fa-refresh"></i> Actualización del Sistema (Git)</div>
				<p style="font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 10px;">
					Comprueba si hay nuevas versiones en GitHub y actualiza automáticamente los archivos del sistema sin modificar tus datos de conexión local.
				</p>
				<button type="button" class="btn btn-info" id="btn_verificar_actualizacion" onclick="verificar_actualizaciones_git()">
					<i class="fa fa-refresh"></i> Verificar Actualizaciones
				</button>
				<div id="contenedor_actualizacion" style="display:none; margin-top: 12px; text-align: left;">
					<div id="resultado_actualizacion"></div>
				</div>
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

					<input type="text" class="form-control" id="input_mesa" placeholder="Mesa"
						onchange="consultar_mesa()" />
					<div style="text-align:center">
						<div class="btn btn-success" onclick="procesar_pedido('1')"><i
								class="fa fa-check"></i>&nbsp;Procesar</div>
						<div class="btn btn-warning" onclick="procesar_pedido('2')"><i
								class="fa fa-cutlery"></i>&nbsp;Comanda</div>
						<div class="btn btn-primary" id="btn_compartir_qr" onclick="compartir_cuenta_qr()" style="background-color: #0880C5; border-color: #0880C5; display: none;"><i
								class="fa fa-qrcode"></i>&nbsp;Compartir</div>
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
	<!-- MODAL COMPARTIR CUENTA POR QR Y PIN -->
	<div class="modal fade" id="modal_compartir_qr" tabindex="-1" role="dialog" aria-labelledby="modalQrLabel" style="z-index: 99999;">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content" style="border-radius: 16px; overflow: hidden; border: 1px solid #DCE4EC; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
				<div class="modal-header" style="background-color: #0880C5; color: #FFF; padding: 12px 15px; border-bottom: none;">
					<button type="button" class="close" data-dismiss="modal" style="color: #FFF; opacity: 0.9; font-size: 24px;">&times;</button>
					<h4 class="modal-title" id="modalQrLabel" style="font-weight: bold; font-size: 16px;"><i class="fa fa-qrcode"></i> Compartir Cuenta</h4>
				</div>
				<div class="modal-body text-center" style="padding: 20px 15px; background-color: #F4F6F9;">
					<p id="qr_mesa_nombre" style="font-weight: 800; font-size: 20px; margin-bottom: 2px; color: #2B303A;">MESA</p>
					<p style="font-size: 12px; color: #6C757D; margin-bottom: 15px;">Muestre este código al cliente para consultar la cuenta o pagar.</p>
					
					<div style="background: #FFF; padding: 15px; border-radius: 12px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #DCE4EC; margin-bottom: 15px;">
						<div id="contenedor_qrcode" style="width: 180px; height: 180px; margin: 0 auto;"></div>
					</div>

					<div style="background: #000; color: #FFF; border-radius: 10px; padding: 10px; margin-bottom: 12px;">
						<span style="font-size: 10px; text-transform: uppercase; color: #AAA; display: block; letter-spacing: 1px;">PIN de Consulta</span>
						<strong id="qr_pin_numero" style="font-size: 26px; font-family: monospace; letter-spacing: 4px; color: #FFF;">------</strong>
					</div>

					<button type="button" class="btn btn-default btn-block" id="btn_copiar_url_qr" onclick="copiar_enlace_qr()" style="font-size: 12px; font-weight: 600; padding: 8px;">
						<i class="fa fa-copy"></i> Copiar Enlace Directo
					</button>
				</div>
				<div class="modal-footer" style="padding: 10px 15px; background: #FFF; border-top: 1px solid #E9ECEF;">
					<button type="button" class="btn btn-default" data-dismiss="modal" style="width: 100%; font-weight: bold;">Cerrar</button>
				</div>
			</div>
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