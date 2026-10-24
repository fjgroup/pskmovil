
$(function () {
	$('[data-toggle="tooltip"]').tooltip();
	mostrar_login();
	codigo_empresa($('#bd').val());
	$('#detalle_cuenta input,#input_mesa').val('');
	$('.precio_total').val('0,00');
	$('.precio_total_ref').val('0,00');
	$('.precio_total_moneda_extranjera').val('0,00');
	
	// Inicializar variable global para el precio del cliente
	window.precio_cliente = null;
	// Bandera para indicar cuando se está actualizando programáticamente
	window.actualizando_cliente_programaticamente = false;
	
	$('#form_autorizar_admin').submit(function (event) {
		event.preventDefault();
	});


	$('input[name="codigo"]').on('input', function () {
		const valor = $(this).val();
		$('input[name="cedula"]').val(valor);
		$('input[name="nrorif"]').val(valor);
		$('input[name="nronit"]').val(valor);
	});




	$('#form_reg_cli').submit(function (event) {
		event.preventDefault();
		RegistrarCliente();
	});

	$('#texto_busqueda').val('');
	$('#form_busqueda_inventario').submit(function (event) {
		event.preventDefault();
		mostrar_articulos('');
	});

	$('#cod_cliente, #cod_cliente_ped, #input_cliente, #nrorif').on('input', function () {
		// Si se está actualizando programáticamente, no ejecutar la lógica
		if (window.actualizando_cliente_programaticamente) {
			return;
		}
		
		$('#input_mesa').val('');
		if ($('#mesa_automatica').prop('checked') == true) {
			$('#mesa_automatica').prop('checked', false);
			$('#input_mesa').val('');

			alert('No se puede modificar el nombre del cliente. Por favor, seleccione un cliente existente o cree uno nuevo.');
		}
	});

	$('#input_cliente').on('input', function () {
		$('#cod_cliente_ped').val('');
	});

});

function HoraActual() {
	var today = new Date();
	var h = today.getHours();
	var m = today.getMinutes();
	if (m < 10) {
		m = "0" + m;
	}
	var s = today.getSeconds();
	if (s < 10) {
		s = "0" + s;
	}
	return h + ':' + m + ':' + s;
}

function RegistrarCliente() {
	$.ajax({
		type: "POST",
		url: "controladores/controlador_clientes.php?accion=registrar",
		data: $('#form_reg_cli').serialize(),
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#msj_reg_cli').html(r);
			setTimeout(function () {
				$('#msj_reg_cli').html('');
			}, 3000);
		},
		error: function (r) {
			ocultar_cargando();
			console.log(r.responseText);
			setTimeout(function () {
				$('#msj_reg_cli').html('');
			}, 5000);
		}
	});
}



function MesaAutomatica() {
	if ($('#mesa_automatica').prop('checked') == true) {
		var number1 = Math.floor((Math.random() * 999) + 1);
		$('#input_mesa').val(number1);
		consultar_mesa();
	}
}

function justNumbers(e) {
	var keynum = window.event ? window.event.keyCode : e.which;
	if ((keynum == 8) || (keynum == 46))
		return true;

	return /\d/.test(String.fromCharCode(keynum));
}

$(window).resize(function () {
	if ($(window).width() > 800) {
		$('#div_derecha,#div_izquierda').show();
	}
});

function mostrar_pw_administrador(r) {
	$('#pw_administrador .titulos').html(r);
	$('#pw_administrador').show();
	$('#bloqueo').show();
	$('#pw_administrador input[name="password_sup"]').focus();
	$('#btn_cancelar_pw_admin').unbind();
	$('#btn_cancelar_pw_admin').click(function () {
		ocultar_pw_administrador();
	});
}

function ocultar_pw_administrador() {
	$('#pw_administrador').hide();
	$('#pw_administrador .btn-success').unbind();
	$('#bloqueo').hide();
	$('#pw_administrador i').html('');
	$('#pw_administrador input:password').val('');
}

function es_movil() {
	return window.innerWidth < 800;
}

function ver_div_derecha() {
	if (es_movil()) {
		// En movil el menu Mesas/Grupos va debajo: no ocultar, solo desplazar
		$('#div_izquierda, #div_derecha').css('display', '');
		$('html, body').animate({ scrollTop: $('#div_derecha').offset().top }, 300);
		return;
	}
	$('#div_derecha').show();
	$('#div_izquierda').hide();
}

function ver_div_izquierda() {
	if (es_movil()) {
		$('#div_izquierda, #div_derecha').css('display', '');
		$('html, body').animate({ scrollTop: 0 }, 300);
		return;
	}
	$('#div_derecha').hide();
	$('#div_izquierda').show();
}

function imprimir_comanda() {
	if (productos_array.length > 0) {
		datos = { "productos_array": productos_array };
		$.ajax({
			type: "POST",
			url: "formatos_comandas/formato1.php",
			data: datos,
			beforeSend: function () {
				mostrar_cargando();
			},
			success: function (r) {
				ocultar_cargando();
				$('#contenedor').hide();
				$('footer').hide();
				$('#print_comand').html(r);
				window.echo();
				$('#contenedor').show();
				$('footer').show();
				$('#print_comand').html('');
				procesar_pedido();
			}
		});
	} else {
		mostrar_msj("<i class='fa fa-times'></i><br>Seleccione un producto");
	}
}

function imprimir_precuenta() {
	if (productos_array.length > 0) {
		datos = { "productos_array": productos_array };
		$.ajax({
			type: "POST",
			url: "formatos_comandas/precuenta.php",
			data: datos,
			beforeSend: function () {
				mostrar_cargando();
			},
			success: function (r) {
				ocultar_cargando();
				$('#contenedor').hide();
				$('footer').hide();
				$('#print_comand').html(r);
				window.echo();
				$('#contenedor').show();
				$('footer').show();
				$('#print_comand').html('');
				procesar_pedido();
			}
		});
	} else {
		mostrar_msj("<i class='fa fa-times'></i><br>Seleccione un producto");
	}
}

function limpiar_parametros_adicionales() {
	$('#parametros_adicionales input:text').val('');
	var bd = $('#bd').val();
	codigo_empresa(bd);
	$('.msj_conexion').eq(0).html('');
}

function mostrar_cargando() {
	$('#cargando').show();
	$('#bloqueo').show();
	$('#bloqueo').css({ 'z-index': '9' });
}

function ocultar_cargando() {
	$('#cargando').hide();
	$('#bloqueo').hide();
	$('#bloqueo').css({ 'z-index': '2' });
}

function probar_conexion_registrada() {
	var id_empresa = $('#id_empresa').val();
	var datos = { "id_empresa": id_empresa };
	$.ajax({
		type: "POST",
		url: "funciones/probar_conexion_registrada.php",
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#bloqueo').show();
			$('.msj_conexion').eq(0).html(r);
		},
		error: function (error) {
			console.log(error.responseText);
		}
	});
}

function probar_conexion_temporal() {
	$.ajax({
		type: "POST",
		url: "funciones/probar_conexion_temporal.php",
		data: $('#form_conexion').serialize(),
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#bloqueo').show();
			$('.msj_conexion').eq(0).html(r);
		},
		error: function (error) {
			ocultar_cargando();
			$('.msj_conexion').eq(0).html(error.responseText);
		}
	});
}

// Find the right method, call on correct element
function launchIntoFullscreen(element) {
	if (element.requestFullscreen) {
		element.requestFullscreen();
	} else if (element.mozRequestFullScreen) {
		element.mozRequestFullScreen();
	} else if (element.webkitRequestFullscreen) {
		element.webkitRequestFullscreen();
	} else if (element.msRequestFullscreen) {
		element.msRequestFullscreen();
	}
}

// Launch fullscreen for browsers that support it!
//launchIntoFullscreen(document.documentElement); // the whole page

function codigo_empresa(val) {
	var cod_empresa = val.replace('admin', '');
	cod_empresa = cod_empresa.replace('ADMIN', '');
	$('#cod_empresa').val(cod_empresa.toUpperCase());
}

function ejecutar_busqueda(parametro) {
	var palabra = $.trim($('#palabra').val());
	if (palabra === "") {
		$('#palabra').val('*');
		palabra = '*';
	}
	var buscar_por = $('#buscar_por').val();
	var datos = { "buscar_por": buscar_por, "palabra": palabra, "parametro": parametro };
	$('#busquedas_resultados').html('Cargando...');
	$.ajax({
		type: "POST",
		url: "controladores/controlador_datos_conexion.php?accion=buscar",
		data: datos,
		cache: false,
		success: function (r) {
			$('#busquedas_resultados').html(r);
		}
	});
}

function mostrar_busqueda(parametro) {
	if (
		$('.msj_conexion').eq(0).html().indexOf('Conexion exitosa') > -1 ||
		!$('#conf_conexion').is(':visible')
	) {
		$('#busquedas_resultados').html('');
		$('#busquedas').show();
		$('#busquedas input:text').focus();
		$('#bloqueo').css({ 'z-index': '9' });
		$('#busquedas .input-group-addon').off('click').on('click', function () {
			ejecutar_busqueda(parametro);
		});
		$('#busquedas #palabra').off('keydown.busqueda').on('keydown.busqueda', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				ejecutar_busqueda(parametro);
			}
		});
		ejecutar_busqueda(parametro);
	} else {
		$('.msj_conexion').eq(1).html("<i class='fa fa-times'></i> Compruebe la conexion");
	}
}

function LimpiarFormulario(form) {
	$('#' + form + ' input[type="text"]').val('');
	$('#' + form + ' input[type="number"]').val(0);
	$('#' + form + ' input[type="password"]').val('');
	$('#' + form + ' input[type="email"]').val('');
	$('#' + form + ' input[type="checkbox"]').prop('checked', false);
	$('#' + form + ' textarea').val('');
}

function select_resultado_busqueda(parametro, r) {
	if (r.nombre == undefined) { r.nombre = ''; }
	ocultar_busqueda();
	if (parametro == "agencia") {
		$('#cod_agencia').val(r.codigo);
	}

	if (parametro == "cliente") {
		// Activar bandera para indicar actualización programática
		window.actualizando_cliente_programaticamente = true;
		
		$('#cod_cliente').val(r.codigo);
		$('#cod_cliente_ped').val(r.codigo);
		$('#input_cliente').val(r.nombre);
		// Actualizar nrorif directamente si viene en el objeto de búsqueda
		var nrorif_busqueda = null;
		
		// Depuración temporal - ver qué viene en el objeto
		console.log('=== DEPURACIÓN NRORIF ===');
		console.log('Objeto r completo:', JSON.stringify(r));
		console.log('r.nrorif:', r.nrorif);
		console.log('typeof r.nrorif:', typeof r.nrorif);
		
		// Establecer nrorif SIEMPRE que exista en el objeto - sin validaciones estrictas
		if ('nrorif' in r) {
			var nrorif_valor = (r.nrorif !== null && r.nrorif !== undefined) ? String(r.nrorif).trim() : '';
			console.log('nrorif_valor procesado:', nrorif_valor);
			
			// Establecer el valor directamente - si está vacío o es 0000000, ConsultarCliente lo corregirá
			if (nrorif_valor && nrorif_valor !== '0000000') {
				// Como hay dos campos con el mismo ID, usar el placeholder para identificar el campo correcto
				// Campo principal tiene placeholder="Nro Identificación"
				// Campo del formulario tiene placeholder="Nro. Fiscal #1"
				var campo_nrorif;
				if ($('#reg_cli').is(':visible')) {
					// Si el formulario de registro está visible, usar el campo dentro de él
					campo_nrorif = $('#reg_cli input[name="nrorif"]');
				} else {
					// Usar el campo principal por su placeholder único
					campo_nrorif = $('input[placeholder="Nro Identificación"]');
					console.log('Campo encontrado por placeholder:', campo_nrorif.length);
				}
				
				if (campo_nrorif.length > 0) {
					console.log('📍 Campo encontrado - ID:', campo_nrorif.attr('id'), 'Placeholder:', campo_nrorif.attr('placeholder'));
					console.log('📍 Campo visible?:', campo_nrorif.is(':visible'));
					console.log('📍 Valor antes de establecer:', campo_nrorif.val());
					
					campo_nrorif.val(nrorif_valor);
					// Forzar el establecimiento también con attr y trigger para asegurar que se actualice
					campo_nrorif.attr('value', nrorif_valor);
					campo_nrorif.trigger('input').trigger('change');
					
					console.log('📍 Valor después de establecer (inmediato):', campo_nrorif.val());
					
					nrorif_busqueda = nrorif_valor;
					console.log('✓ nrorif establecido en campo desde búsqueda:', nrorif_valor);
					
					// Verificar inmediatamente después de establecerlo
					var verificacion = function() {
						var valor_en_campo = campo_nrorif.val();
						console.log('🔍 Valor en campo verificado:', valor_en_campo);
						if (valor_en_campo !== nrorif_valor) {
							console.log('⚠️ ADVERTENCIA: El valor fue sobrescrito! Esperado:', nrorif_valor, 'Actual:', valor_en_campo);
							// Reestablecer el valor con fuerza
							campo_nrorif.val(nrorif_valor).attr('value', nrorif_valor).trigger('input');
							console.log('🔧 Valor reestablecido forzadamente');
						} else {
							console.log('✓ Valor confirmado en campo');
						}
					};
					setTimeout(verificacion, 100);
					setTimeout(verificacion, 500);
				} else {
					console.log('⚠️ ERROR: Campo nrorif no encontrado en el DOM');
				}
			} else {
				console.log('⚠ nrorif vacío o 0000000, se esperará a ConsultarCliente');
			}
		} else {
			console.log('✗ nrorif NO existe en el objeto');
		}
		console.log('========================');
		$('#cliente_exento').val(r.formafis);
		
		// Guardar el nrorif antes de llamar a ConsultarCliente
		var nrorif_guardado = nrorif_busqueda;
		
		// Consultar cliente completo para asegurar todos los datos (incluido nrorif)
		// Pasamos el nrorif de la búsqueda para que no se pierda si la consulta no lo trae
		ConsultarCliente(r.codigo, nrorif_busqueda);
		
		// Verificación final después de que ConsultarCliente termine
		setTimeout(function() {
			if (nrorif_guardado && nrorif_guardado != '' && nrorif_guardado != '0000000') {
				// Usar selector por placeholder para el campo principal
				var campo_final = $('input[placeholder="Nro Identificación"]');
				if (campo_final.length === 0) {
					campo_final = $('#nrorif').last(); // Fallback
				}
				var valor_final = campo_final.val();
				if (!valor_final || valor_final == '' || valor_final == '0000000') {
					console.log('🔧 FORZANDO establecimiento de nrorif:', nrorif_guardado);
					campo_final.val(nrorif_guardado).attr('value', nrorif_guardado);
				} else {
					console.log('✓ nrorif ya tiene valor válido:', valor_final);
				}
			}
		}, 500);
		
		if ($('#conf_conexion').is('not:visible')) {
			mostrar_grupos();
		}
		
		// Desactivar bandera después de establecer los valores
		setTimeout(function() {
			window.actualizando_cliente_programaticamente = false;
		}, 100);
	}

	if (parametro == "zona") {
		$('#cod_zona').val(r.codigo);
	}

	if (parametro == "almacen") {
		$('#cod_deposito').val(r.codigo);
	}
}

function ocultar_busqueda() {
	$('#busquedas').hide();
	$('#bloqueo').css({ 'z-index': '2' });
}

function registrar_conf_conexion() {
	if ($('.msj_conexion').eq(0).html().indexOf('Conexion exitosa') > -1) {

		if ($('#cod_empresa').val() != "" && $('#cod_agencia').val() != "" && $('#cod_cliente').val() != "" &&
			$('#cod_zona').val() != "" && $('#cod_deposito').val() != "" && $('#recargo').val() != "" &&
			$('#pw_supervisor').val() != "" && $('#pw_admin') != "" && $('#conf_pw_admin').val() != "") {

			if ($('#conf_pw_admin').val() == $('#pw_admin').val()) {

				var datos = new FormData($('#conf_conexion form')[0]);
				datos.append("id_empresa", $('#id_empresa').val());

				$.ajax({
					type: "POST",
					url: "controladores/controlador_datos_conexion.php?accion=registrar_conexion",
					data: datos,
					cache: false,
					processData: false,
					contentType: false,
					beforeSend: function () {
						mostrar_cargando();
					},
					success: function (r) {
						ocultar_cargando();
						$('.msj_conexion').eq(1).html(r);
						$('#bloqueo').show();
					}
				});
			} else {
				$('.msj_conexion').eq(1).html("<i class='fa fa-times'></i> Password no coincide");
			}

		} else {
			$('.msj_conexion').eq(1).html("<i class='fa fa-times'></i> Complete la informacion");
		}
	} else {
		$('.msj_conexion').eq(1).html("<i class='fa fa-times'></i> Compruebe la conexion");
	}

}


function autorizar_conf_conexion() {
	$('#login').hide();
	mostrar_pw_administrador("Acceso a configurar conexion");

	$('#btn_cancelar_pw_admin').unbind();
	$('#btn_cancelar_pw_admin').click(function () {
		mostrar_login();
	});

	$('#pw_administrador .btn-success').unbind();
	$('#pw_administrador .btn-success').click(function () {
		var password_sup = $('#password_sup').val();
		var datos = { "password_sup": password_sup };
		$.ajax({
			type: "POST",
			url: "funciones/permisos_adm.php",
			data: datos,
			beforeSend: function () {
				mostrar_cargando();
			},
			success: function (r) {
				ocultar_cargando();
				if (r == "true") {
					ocultar_pw_administrador();
					mostrar_conf_conexion();
				} else if (r == "false") {
					$('#pw_administrador i').html("Contrase&ntildea incorrecta");
					$('#bloqueo').show();
				} else {
					$('#pw_administrador i').html(r);
					$('#bloqueo').show();
				}
			}
		});
	});
}

function mostrar_conf_conexion() {
	$('.msj_conexion').eq(0).html('');
	$('#conf_conexion').show();
	$('#login').hide();
	$.ajax({
		type: "GET",
		url: "controladores/controlador_datos_conexion.php?accion=mostrar_datos_conexion",
		dataType: "JSON",
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#host').val(r.host);
			$('#port').val(r.port);
			$('#user').val(r.user);
			$('#pw').val(r.pw);
			$('#nombre_empresa').val(r.nombre_empresa);
			$('#cod_empresa').val(r.cod_empresa);
			$('#cod_deposito').val(r.cod_deposito);
			$('#cod_cliente').val(r.cod_cliente);
			$('#cod_agencia').val(r.cod_agencia);
			$('#cod_zona').val(r.cod_zona);
			$('#cod_deposito').val(r.cod_deposito);
			$('#recargo').val(r.recargo);
			$('#pw_supervisor').val(r.pw_supervisor);
			$('#cantidad_por_pagina').val(r.cantidad_por_pagina);
			$('#cm_ancho_precuenta').val(r.cm_ancho_precuenta);
			$('#bd').val(r.bd_pos || r.bd || 'adminbahiap');
			$('#bd_sistema').val(r.bd_sistema || 'mijar_sistema');
			$('#url_hosting').val(r.url_hosting || (r.url_web_mesa ? r.url_web_mesa.replace(/\/mesa\/?$/i, '') : 'https://mijardinrestaurant.test'));
			$('#url_web_mesa').val(r.url_web_mesa || 'https://mijardinrestaurant.test/mesa');

			if (r.cuentas_compartidas_entre_vendedores == 'si') {
				$('#compartir_mesas').prop('checked', true);
			} else {
				$('#compartir_mesas').prop('checked', false);
			}

			if (r.usar_subgrupos == 'si') {
				$('#usar_subgrupos').prop('checked', true);
			} else {
				$('#usar_subgrupos').prop('checked', false);
			}

			if (r.agrupar_mesas_por_ubicacion == 'si') {
				$('#agrupar_mesas_conf').prop('checked', true);
			} else {
				$('#agrupar_mesas_conf').prop('checked', false);
			}

			if (r.no_usar_impuestos == 'si') {
				$('#no_usar_impuestos').prop('checked', true);
			} else {
				$('#no_usar_impuestos').prop('checked', false);
			}

			if (r.no_editar_integrados == 'si') {
				$('#no_editar_integrados_conf').prop('checked', true);
			} else {
				$('#no_editar_integrados_conf').prop('checked', false);
			}

			if (r.ocultar_integrados == 'si') {
				$('#ocultar_integrados_conf').prop('checked', true);
			} else {
				$('#ocultar_integrados_conf').prop('checked', false);
			}

			if (r.no_tiene_existencia == 'si') {
				$('#no_tiene_existencia_conf').prop('checked', true);
			} else {
				$('#no_tiene_existencia_conf').prop('checked', false);
			}

			if (r.permitir_descuentos == 'no') {
				$('#permitir_descuentos_conf').prop('checked', false);
			} else {
				$('#permitir_descuentos_conf').prop('checked', true);
			}

		},
		error: function (err) {
			ocultar_cargando();
			alert(err.responseText);
		}
	});
}

function ocultar_conf_conexion() {
	$('#conf_conexion').hide();
	mostrar_login();
}

function mostrar_detalle_cuenta() {
	var mesa = $('#input_mesa').val();

	var resumir_lineas = 0;
	if ($('#resumir_lineas').prop('checked')) {
		resumir_lineas = 1;
	}
	$('#detalle_cuenta').show();
	$('#bloqueo').show();
	$('#productos_precuenta').html('');
	$('#resumir_lineas_precuenta').val(resumir_lineas);
	$('#input_mesa_precuenta').val($('#input_mesa').val());
	$('#input_cliente_precuenta').val($('#input_cliente').val());
	$('#input_vendedor_precuenta').val($('#input_vendedor').val());
	var productos = "";
	for (i in productos_array) {
		productos += "<input type='hidden' name='productos[]' value='" + productos_array[i] + "'/>";
	}
	$('#productos_precuenta').html(productos);
}

function ocultar_detalle_cuenta() {
	$('#detalle_cuenta').hide();
	$('#bloqueo').hide();
}


function mostrar_filtros_mesas() {
	$('#inventario').html('');
	$('#filtros_mesa').show();
	$('#btn_mis_mesas').unbind();
	$('#btn_mesas_disp').unbind();
	$('#todas_mesas').unbind();
	var agrupar_mesas = $.trim($('#agrupar_mesas').val());
	if (agrupar_mesas == 'no') {
		$('#btn_mis_mesas').click(function () {
			mostrar_mesas('0', 'mias')
		});
		$('#btn_mesas_disp').click(function () {
			mostrar_mesas('0', 'disponibles')
		});
		$('#todas_mesas').click(function () {
			mostrar_mesas('0', 'todas')
		});
	} else if (agrupar_mesas == 'si') {
		$('#btn_mis_mesas').click(function () {
			mostrar_grupos_mesas('0', 'mias')
		});
		$('#btn_mesas_disp').click(function () {
			mostrar_grupos_mesas('0', 'disponibles')
		});
		$('#todas_mesas').click(function () {
			mostrar_grupos_mesas('0', 'todas')
		});
	}
}

var ocultarmsj;
function mostrar_msj(r) {
	ocultarmsj = setTimeout(ocultar_msj, 1000)
	$('#msj_php').show();
	$('#bloqueo').show();
	$('#msj_php .ventanas_contenido').html(r);
	$('#bloqueo').unbind();
	$('#bloqueo').click(function () {
		ocultar_msj();
	});
}

function ocultar_msj() {
	$('#bloqueo').unbind();
	$('#msj_php').hide();
	$('#bloqueo').hide();
	$('#bloqueo').unbind();
	clearTimeout(ocultarmsj);
	timeoutHandle = null;
}


function cliente_contado() {
	if ($('#input_cliente').val() != "") {
		$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
		$('#input_cliente').css({ 'border': '1px solid #CCC' });
	} else {
		$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
		$('#input_cliente').css({ 'border': '2px solid red' });
	}
}

function mostrar_login() {
	ocultar_pw_administrador();
	$('#login').show();
	$('#bloqueo').show();
	$('#login input').focus();
	probar_conexion_registrada();
}

function iniciar_sesion() {

	var id_empresa = $('#id_empresa').val();
	var vendedor = $('#login select').val();
	if ($('#login select option').length > 0) {
		var pw = $('#login input:password').val();
		var datos = { "vendedor": vendedor, "pw": pw, "id_empresa": id_empresa };
		$.ajax({
			type: "POST",
			url: "controladores/controlador_vendedores.php?accion=login",
			data: datos,
			beforeSend: function () {
				mostrar_cargando();
			},
			success: function (r) {
				ocultar_cargando();
				$('#bloqueo').show();
				$('#msj_login').html(r);
			}
		});
	}
}

function ocultar_login() {
	$('#login').hide();
}

function lista_vendedores() {
	var id_empresa = $('#id_empresa').val();
	var datos = { "id_empresa": id_empresa };
	$.ajax({
		type: "POST",
		url: "controladores/controlador_vendedores.php?accion=lista_vendedores",
		data: datos,
		success: function (r) {
			ocultar_cargando();
			$('#bloqueo').show();
			$('#lista_vendedores').html(r);
			$('#login input:password').val('');
		},
		error: function (r) {
			ocultar_cargando();
			console.log(r.responseText);
		}
	});
}

function BuscarInventario() {
}

function mostrar_grupos(pagina) {
	var datos = { "pagina": pagina };
	$.ajax({
		type: "POST",
		url: "controladores/controlador_inventario.php?accion=mostrar_grupos",
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#filtros_mesa').hide();
			$('#inventario').html(r);
		}
	});
}


function mostrar_articulos(grupo, subgrupo, pagina) {
	if (subgrupo == undefined) {
		subgrupo = '';
	}
	if (pagina == undefined) {
		pagina = '0';
	}
	var cliente_exento = $('#cliente_exento').val();
	var texto_busqueda = $('#texto_busqueda').val();
	var datos = { "grupo": grupo, "subgrupo": subgrupo, "pagina": pagina, "texto_busqueda": texto_busqueda };
	$.ajax({
		url: "controladores/controlador_inventario.php?accion=mostrar_articulos&cliente_exento=" + cliente_exento,
		data: datos,
		type: "POST",
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#inventario').html(r);
		}
	});
}

function mostrar_subgrupos(grupo, pagina) {
	if (pagina == undefined) {
		pagina = '0';
	}
	var datos = { "grupo": grupo, "pagina": pagina };
	$.ajax({
		url: "controladores/controlador_inventario.php?accion=mostrar_subgrupos",
		data: datos,
		type: "POST",
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#inventario').html(r);
		}
	});
}

function ejecutar_agregar_con_cantidad(productos, compuesto, fraccionado, existencia, maneja_exist, cantidad) {
	ocultar_div_agrupado();
	$.ajax({
		url: "controladores/controlador_inventario.php?accion=calcular_pid",
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			var pid;
			var pidpadre;

			if (productos_array.length == 0) {
				pid = Number(r);
			} else {
				var dat = (productos_array[productos_array.length - 1]).split('|');
				pid = Number(dat[13]) + 1;
				pidpadre = Number(dat[7]);
			}

			ocultar_cargando();
			ver_operacion_actual();
			$('#input_mesa').css('border', '1px solid #CCC');

			if (fraccionado != 1 && compuesto != 2) {
				$('#cantidad input:text').val(cantidad);
				ObtenerIntegrados(productos, pid, fraccionado, pidpadre, compuesto);
			} else {
				var productos_add = productos + "|" + Number(cantidad) + "|" + pid + "|";
				productos_array.push(productos_add);
				VerProductosAgregados();
				ResumirLineas();
			}
		},
		error: function (error) {
			ocultar_cargando();
			console.log(error.responseText);
		}
	});
}

function AmpliarImagen(posicion, nombre) {
	var li = $('#inventario li').eq(posicion);
	var rutafoto = li.find('img').attr('src');
	$('#imagen_amplia img').attr('src', rutafoto);
	$('#imagen_amplia .modal-title').html(nombre);

	// Extraer y mostrar precios formateados
	var precioTexto = li.find('div b').map(function () { return $(this).text(); }).get().join(' | ');
	$('#modal_producto_precio').html(precioTexto);

	// Reiniciar cantidad a 1
	$('#modal_cantidad').val(1);

	// Botones de incremento y decremento de cantidad
	$('#modal_btn_cant_menos').off('click').on('click', function () {
		var c = parseInt($('#modal_cantidad').val(), 10);
		if (isNaN(c) || c <= 1) c = 1;
		else c--;
		$('#modal_cantidad').val(c);
	});
	$('#modal_btn_cant_mas').off('click').on('click', function () {
		var c = parseInt($('#modal_cantidad').val(), 10);
		if (isNaN(c) || c < 1) c = 1;
		c++;
		$('#modal_cantidad').val(c);
	});

	// Obtener datos del producto desde atributos data o fallback
	var datos = li.attr('data-datos');
	var empaque = li.attr('data-empaque') || '0';
	var datos_agrupados = li.attr('data-agrupados') || '';
	var compuesto = li.attr('data-compuesto') || '0';
	var fraccionado = li.attr('data-decimales') || '0';
	var existencia = li.attr('data-existencia') || '0';
	var maneja_exist = li.attr('data-usaexist') || '0';
	var codigo_articulo = li.attr('data-codigo');

	if (!datos && li.attr('onclick')) {
		var m = li.attr('onclick').match(/seleccionar_producto\(([^)]+)\)/);
		if (m && m[1]) {
			try {
				var params = JSON.parse('[' + m[1] + ']');
				datos = params[0];
				empaque = params[1] || '0';
				datos_agrupados = params[2] || '';
				compuesto = params[3] || '0';
				fraccionado = params[4] || '0';
				existencia = params[5] || '0';
				maneja_exist = params[6] || '0';
			} catch (e) {
				console.log('Error parsing onclick params:', e);
			}
		}
	}

	if (!codigo_articulo && datos) {
		var dp = datos.split('|');
		codigo_articulo = dp[0];
	}

	// Cargar precios en el select si existen precios múltiples
	$('#modal_select_precio').html('<option value="">Precio por defecto</option>');
	$('#modal_precio_wrapper').hide();
	if (codigo_articulo && codigo_articulo != '') {
		$.ajax({
			url: "controladores/controlador_inventario.php?accion=obtener_precios&codigo=" + encodeURIComponent(codigo_articulo),
			type: "GET",
			dataType: "json",
			success: function (precios) {
				if (precios && precios.length > 1) {
					$('#modal_select_precio').empty();
					precios.forEach(function (precio) {
						var valor_preciofin = precio.preciofin || precio.valor;
						$('#modal_select_precio').append('<option value="' + precio.precio + '|' + precio.valor + '|' + valor_preciofin + '">' + precio.nombre + ' - ' + parseFloat(precio.valor).toFixed(2) + '</option>');
					});
					$('#modal_precio_wrapper').show();
				}
			}
		});
	}

	// Botón "Agregar al pedido"
	var btn = $('#btn_agregar_desde_imagen');
	btn.show().off('click').on('click', function () {
		if ($('#input_mesa').val() == "") {
			$('#imagen_amplia').modal('hide');
			$('#input_mesa').css('border', '2px solid red');
			mostrar_msj("<i class='fa fa-exclamation-triangle'></i><br>Primero seleccione una mesa");
			mostrar_filtros_mesas();
			if ($.trim($('#agrupar_mesas').val()) == 'si') {
				mostrar_grupos_mesas('0', 'disponibles');
			} else {
				mostrar_mesas('0', 'disponibles');
			}
			if (es_movil()) { ver_div_derecha(); }
			return;
		}

		var cantidad = parseInt($('#modal_cantidad').val(), 10);
		if (isNaN(cantidad) || cantidad <= 0) {
			cantidad = 1;
		}

		var productos_actualizado = datos;
		var precio_seleccionado = $('#modal_select_precio').val();
		if (precio_seleccionado && precio_seleccionado != "") {
			var datos_precio = precio_seleccionado.split('|');
			var parts = productos_actualizado.split('|');
			parts[4] = datos_precio[1];
			parts[5] = datos_precio.length > 2 ? datos_precio[2] : datos_precio[1];
			productos_actualizado = parts.join('|');
		}

		$('#imagen_amplia').modal('hide');

		if (empaque == '1') {
			var producto_agrupado = datos_agrupados.split("|");
			$('#unidad span').html(producto_agrupado[0]);
			$('#agrupado span').html(producto_agrupado[1]);
			mostrar_div_agrupado();
			$('#div_agrupado #unidad').off('click').on('click', function () {
				if (producto_agrupado[2] > 0) {
					existencia = producto_agrupado[2] * existencia;
				}
				ejecutar_agregar_con_cantidad(productos_actualizado + "|" + producto_agrupado[0] + "||", compuesto, fraccionado, existencia, maneja_exist, cantidad);
			});
			$('#div_agrupado #agrupado').off('click').on('click', function () {
				ejecutar_agregar_con_cantidad(productos_actualizado + "|" + producto_agrupado[1] + "|" + producto_agrupado[3] + "|" + producto_agrupado[2], compuesto, fraccionado, existencia, maneja_exist, cantidad);
			});
		} else {
			ejecutar_agregar_con_cantidad(productos_actualizado + "|||", compuesto, fraccionado, existencia, maneja_exist, cantidad);
		}
	});

	$('#imagen_amplia').modal('show');
}

function mostrar_div_agrupado() {
	$('#bloqueo').show();
	$('#div_agrupado').show();
}

function ocultar_div_agrupado() {
	$('#div_agrupado #unidad').unbind();
	$('#div_agrupado #agrupado').unbind();
	$('#bloqueo').hide();
	$('#div_agrupado').hide();
}

var productos_array = [];
function seleccionar_producto(productos, empaque, datos_agrupados, compuesto, fraccionado, existencia, maneja_exist) {
	var producto_agrupado = datos_agrupados.split("|");
	$('#unidad span').html(producto_agrupado[0]);
	$('#agrupado span').html(producto_agrupado[1]);
	if ($('#input_mesa').val() != "") {

		if (empaque == '1') {
			mostrar_div_agrupado();
			//producto_agrupado[0] = nombre unidad
			//producto_agrupado[1] = nombre agrupado
			//producto_agrupado[2] = cantidad agrupada
			//producto_agrupado[3] = precio unitario agrupado	

			$('#div_agrupado #unidad').click(function () {
				if (producto_agrupado[2] > 0) {
					existencia = producto_agrupado[2] * existencia;
				}
				agregar_producto(productos + "|" + producto_agrupado[0] + "|" + '' + "|" + '', compuesto, fraccionado, existencia, maneja_exist);
			});
			$('#div_agrupado #agrupado').click(function () {
				agregar_producto(productos + "|" + producto_agrupado[1] + "|" + producto_agrupado[3] + "|" + producto_agrupado[2], compuesto, fraccionado, existencia, maneja_exist);
			});
		} else {
			agregar_producto(productos + "|" + '' + "|" + '' + "|" + '', compuesto, fraccionado, existencia, maneja_exist);
		}
	} else {
		// Sin mesa: recordar al mesonero que primero seleccione la mesa
		$('#input_mesa').css('border', '2px solid red');
		mostrar_msj("<i class='fa fa-exclamation-triangle'></i><br>Primero seleccione una mesa");
		mostrar_filtros_mesas();
		if ($.trim($('#agrupar_mesas').val()) == 'si') {
			mostrar_grupos_mesas('0', 'disponibles'); // Salon, Jardin, Tasca...
		} else {
			mostrar_mesas('0', 'disponibles');
		}
		if (es_movil()) { ver_div_derecha(); }
	}
}

// function agregar_producto(productos, compuesto = 0, fraccionado, existencia, maneja_exist) {
// 	ocultar_div_agrupado();
// 	$.ajax({
// 		url: "controladores/controlador_inventario.php?accion=calcular_pid",

// 		beforeSend: function () {
// 			mostrar_cargando();
// 		},
// 		success: function (r) {
// 			if (productos_array.length == 0) {
// 				var pid = Number(r);
// 			} else {
// 				var dat = (productos_array[productos_array.length - 1]).split('|');
// 				//var pid = Number(dat[13]) + Number(1);
// 				var pid = Number(dat[13]) + Number(1);
// 				var pidpadre = Number(dat[7]);
// 			}

// 			ocultar_cargando();
// 			ver_operacion_actual();
// 			$('#input_mesa').css('border', '1px solid #CCC');
// 			mostrar_cantidad();
// 			$('#cantidad input').focus();
// 			$('#cantidad .btn-success').unbind();

// 			$('#cantidad .btn-success').click(function () {
// 				var cantidad = $('#cantidad input:text').val();
// 				if (cantidad == "") { cantidad = 1; }
// 				validar_existencia = true;
// 				if ((maneja_exist == 1)) {
// 					if (parseInt(cantidad) > parseInt(existencia)) {
// 						validar_existencia = false;
// 					}
// 				}
// 				if (validar_existencia) {
// 					if (fraccionado != 1) {
// 						if (compuesto == 2) {
// 							var cantidad = $('#cantidad input:text').val();
// 							if (cantidad == "") { cantidad = 1; }

// 							// var x = 1;
// 							// while (x <= parseInt(cantidad)) {
// 							// 	var productos_add = productos + "|" + 1 + "|" + (Number(pid) + Number(x)) + "|";
// 							// 	//var productos_add = productos + "|" + 1 + "|" + (Number(pid) ) + "|";
// 							// 	productos_array.push(productos_add);
// 							// 	x++;
// 							// }
// 							var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid)) + "|";
// 							productos_array.push(productos_add);
// 							VerProductosAgregados();

// 							ResumirLineas();
// 							ocultar_cantidad();
// 						} else {

// 							ObtenerIntegrados(productos, pid, fraccionado, pidpadre, compuesto);
// 						}
// 					} else {
// 						console.log("Es Fraccionado v");
// 						if (compuesto == 2) {

// 							var cantidad = $('#cantidad input:text').val();
// 							if (cantidad == "") { cantidad = 1; }
// 							var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid)) + "|";
// 							productos_array.push(productos_add);

// 							VerProductosAgregados();
// 						} else {

// 							ObtenerIntegrados(productos, pid, fraccionado, pidpadre, compuesto);
// 						}
// 					}
// 				} else {
// 					ocultar_cantidad()
// 					mostrar_msj("<i class='fa fa-times'></i><br>El producto no posee suficiente existencia, la cantidad no puede ser mayor a: " + existencia + " Verifique y vuelva a intentarlo");
// 				}

// 			});
// 		},
// 		error: function (error) {
// 			ocultar_cargando();
// 			console.log(error.responseText);
// 		}
// 	});
// }

function agregar_producto(productos, compuesto = 0, fraccionado, existencia, maneja_exist) {
	ocultar_div_agrupado();
	$.ajax({
		url: "controladores/controlador_inventario.php?accion=calcular_pid",
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			var pid;
			var pidpadre;

			if (productos_array.length == 0) {
				pid = Number(r);
			} else {
				var dat = (productos_array[productos_array.length - 1]).split('|');
				pid = Number(dat[13]) + 1; // Incrementamos el PID del último elemento
				pidpadre = Number(dat[7]);
			}

			ocultar_cargando();
			ver_operacion_actual();
			$('#input_mesa').css('border', '1px solid #CCC');
			// Extraer el código del artículo del string productos (índice 0)
			// El string productos puede venir con comillas JSON, necesitamos limpiarlo
			var productos_limpio = productos;
			if (typeof productos === 'string' && productos.startsWith('"') && productos.endsWith('"')) {
				productos_limpio = productos.replace(/^"|"$/g, '');
			}
			var datos_producto = productos_limpio.split('|');
			var codigo_articulo = datos_producto[0];
			// Limpiar el código de posibles comillas o espacios
			codigo_articulo = codigo_articulo.replace(/^["']|["']$/g, '').trim();
			console.log('String productos completo:', productos);
			console.log('String productos limpio:', productos_limpio);
			console.log('Datos producto split:', datos_producto);
			console.log('Código artículo extraído:', codigo_articulo);
			mostrar_cantidad(codigo_articulo);
			$('#cantidad input').focus();
			$('#cantidad .btn-success').unbind();
			
			// Variable para almacenar el string productos actualizado con el precio seleccionado
			var productos_actualizado = productos;
			
			// Evento para actualizar el precio cuando se selecciona una opción del select
			$('#select_precio').off('change').on('change', function() {
				var precio_seleccionado = $(this).val();
				if (precio_seleccionado != "") {
					var datos_precio = precio_seleccionado.split('|');
					var tipo_precio = datos_precio[0]; // precio1 o precio2
					var valor_precio = datos_precio[1]; // valor del precio
					var valor_preciofin = datos_precio.length > 2 ? datos_precio[2] : valor_precio; // preciofin (si existe)
					
					// Actualizar el string productos con el nuevo precio
					datos_producto = productos.split('|');
					datos_producto[4] = valor_precio; // precio1 está en el índice 4
					datos_producto[5] = valor_preciofin; // preciofin1 está en el índice 5
					productos_actualizado = datos_producto.join('|');
				} else {
					productos_actualizado = productos; // Si no hay selección, usar el precio original
				}
			});

			$('#cantidad .btn-success').click(function () {
				var cantidad = $('#cantidad input:text').val();
				if (cantidad == "") { cantidad = 1; }
				validar_existencia = true;
				if ((maneja_exist == 1)) {
					if (parseInt(cantidad) > parseInt(existencia)) {
						validar_existencia = false;
					}
				}
				// if (validar_existencia) {
					if (fraccionado != 1) {
						if (compuesto == 2) {
							var cantidad = $('#cantidad input:text').val();
							if (cantidad == "") { cantidad = 1; }

							var productos_add = productos_actualizado + "|" + Number(cantidad) + "|" + pid + "|";
							productos_array.push(productos_add);
							VerProductosAgregados();
							ResumirLineas();
							ocultar_cantidad();
						} else {
							ObtenerIntegrados(productos_actualizado, pid, fraccionado, pidpadre, compuesto);
						}
					} else {
						console.log("Es Fraccionado");
						if (compuesto == 2) {
							var cantidad = $('#cantidad input:text').val();
							if (cantidad == "") { cantidad = 1; }
							var productos_add = productos_actualizado + "|" + Number(cantidad) + "|" + pid + "|";
							productos_array.push(productos_add);
							VerProductosAgregados();
						} else {
							ObtenerIntegrados(productos_actualizado, pid, fraccionado, pidpadre, compuesto);
						}
					}
				// } else {
				// 	ocultar_cantidad();
				// 	mostrar_msj("<i class='fa fa-times'></i><br>El producto no posee suficiente existencia, la cantidad no puede ser mayor a: " + existencia + " Verifique y vuelva a intentarlo");
				// }
			});
		},
		error: function (error) {
			ocultar_cargando();
			console.log(error.responseText);
		}
	});
}

function str_replace(search, replace, subject) {
	var new_string = '';
	subject = subject.toString();
	array_old = subject.split(search);
	total = array_old.length;
	for (k in array_old) {
		new_string += array_old[k];
		if (k < total - 1) {
			new_string += replace;
		}
	}
	return new_string;
}

function ObtenerIntegrados(productos, pid, fraccionado, pidpadre, compuesto = 0) {
	var no_editar_integrados = $('#no_editar_integrados').val();
	var datos_art = productos.split('|');
	var codigo = datos_art[0];
	var cliente_exento = $('#cliente_exento').val();

	$.ajax({
		url: "controladores/controlador_inventario.php?accion=obtener_integrados&codigo=" + codigo + "&cliente_exento=" + cliente_exento,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			if (no_editar_integrados == 'no') {
				$('#modal_compuestos').modal('show');
			}
			$('#lista_compuestos').html(r);
			$('#lista_compuestos .btn_delete_art_comp').click(function () {
				$(this).parents('tr').hide();
				$(this).parents('tr').html('');
			});
			$('#lista_compuestos .btn_art_comp_minus').click(function () {
				var cantidad = $(this).parents('.input-group').find('.art_comp_cantidad').val();
				if (cantidad == 0) { cantidad = 1; }
				$(this).parents('.input-group').find('.art_comp_cantidad').val(Number(cantidad) - 1);
			});
			$('#lista_compuestos .btn_art_comp_plus').click(function () {
				var cantidad = $(this).parents('.input-group').find('.art_comp_cantidad').val();
				$(this).parents('.input-group').find('.art_comp_cantidad').val(Number(cantidad) + 1);
			});
			$('#btn_adicionar_componentes').unbind();
			$('#btn_adicionar_componentes').click(function () {

				var cantidad = $('#cantidad input:text').val();
				if (cantidad == "") { cantidad = 1; }

				if (fraccionado != 1) {
					var x = 1;
					//while (x <= parseInt(cantidad)) {
					//var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid) + Number(x)) + "|";
					var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid)) + "|";
					productos_array.push(productos_add);
					//x++;
					//}
					var cantidad_padre = cantidad;

					VerProductosAgregados();
					//for (var c = 0; c < cantidad_padre; c++) {
					//var pidpadre = Number(pid) + Number(c + 1);
					var pidpadre = Number(pid);
					$('#lista_compuestos table tbody tr').each(function () {

						var productos = $(this).attr('datos_art_comp') + "|||";
						var cantidad = Number($('#cantidad input:text').val());
						if (cantidad == "") { cantidad = 1; }
						var cantidad_integrado = Number($(this).find('.art_comp_cantidad').val());
						//cantidad = cantidad_integrado * cantidad;
						cantidad = cantidad_integrado;

						return $.ajax({
							url: "controladores/controlador_inventario.php?accion=calcular_pid&pidpadre=" + pidpadre,
							beforeSend: function () {
								mostrar_cargando();
							},
							success: function (r) {
								var r = r.split('|');
								var pidhijo = r[0];
								var pidpadre = r[1];
								ver_operacion_actual();
								$('#input_mesa').css('border', '1px solid #CCC');
								var x = 1;
								var cantidad_hijo = cantidad;
								if (parseFloat(cantidad) > 0 && parseFloat(cantidad) < 1) {
									cantidad = 1;
								}
								//while (x <= parseInt(cantidad)) {
								//CODIGO = ES EL CODIGO DEL PADRE		

								//var productos_add = productos + "|" + cantidad_hijo + "|" + (Number(pidhijo) + Number(x)) + "|" + codigo;
								var productos_add = productos + "|" + cantidad_hijo + "|" + (Number(pidhijo)) + "|" + codigo;
								productos_add = str_replace('"', '', productos_add);
								var datos = productos_add.split('|');
								productos_add = datos[0] + '|' + datos[1] + '|' + datos[2] + '|' + datos[3] + '|' + datos[4] + '|' + datos[5] + '|' + datos[6] + '|' + pidpadre + '|' + datos[8] + '|' + datos[9] + '|' + datos[10] + '|' + datos[11] + '|' + datos[12] + '|' + datos[13] + '|' + datos[14];
								productos_array.push(productos_add);
								//	x++;
								//}

								ocultar_cargando();
								VerProductosAgregados();

							},
							error: function (error) {
								ocultar_cargando();
								console.log(error.responseText);
							}
						});
					});
					//}
				} else {
					console.log("ES Fraccionado a");
					if (compuesto != 2) {
						var x = 1;
						//while (x <= parseInt(cantidad)) {
						//var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid) + Number(x)) + "|";
						var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid)) + "|";
						//var productos_add = productos + "|" + Number(cantidad) + "|" + (Number(pid)) + "|";
						productos_array.push(productos_add);
						//	x++;
						//}
						var cantidad_padre = cantidad;

						VerProductosAgregados();
						//	for (var c = 0; c < cantidad_padre; c++) {
						//var pidpadre = Number(pid) + Number(c + 1);
						var pidpadre = Number(pid);
						$('#lista_compuestos table tbody tr').each(function () {

							var productos = $(this).attr('datos_art_comp') + "|||";
							var cantidad = Number($('#cantidad input:text').val());
							if (cantidad == "") { cantidad = 1; }
							var cantidad_integrado = Number($(this).find('.art_comp_cantidad').val());
							//cantidad = cantidad_integrado * cantidad;
							cantidad = cantidad_integrado;

							return $.ajax({
								url: "controladores/controlador_inventario.php?accion=calcular_pid&pidpadre=" + pidpadre,
								beforeSend: function () {
									mostrar_cargando();
								},
								success: function (r) {
									var r = r.split('|');
									var pidhijo = r[0];
									var pidpadre = r[1];
									ver_operacion_actual();
									$('#input_mesa').css('border', '1px solid #CCC');
									var x = 1;
									var cantidad_hijo = cantidad;
									if (parseFloat(cantidad) > 0 && parseFloat(cantidad) < 1) {
										cantidad = 1;
									}
									//while (x <= parseInt(cantidad)) {
									//CODIGO = ES EL CODIGO DEL PADRE		

									//var productos_add = productos + "|" + cantidad_hijo + "|" + (Number(pidhijo) + Number(x)) + "|" + codigo;
									var productos_add = productos + "|" + cantidad_hijo + "|" + (Number(pidhijo)) + "|" + codigo;
									productos_add = str_replace('"', '', productos_add);
									var datos = productos_add.split('|');
									productos_add = datos[0] + '|' + datos[1] + '|' + datos[2] + '|' + datos[3] + '|' + datos[4] + '|' + datos[5] + '|' + datos[6] + '|' + pidpadre + '|' + datos[8] + '|' + datos[9] + '|' + datos[10] + '|' + datos[11] + '|' + datos[12] + '|' + datos[13] + '|' + datos[14];
									productos_array.push(productos_add);
									//	x++;
									//}

									ocultar_cargando();
									VerProductosAgregados();

								},
								error: function (error) {
									ocultar_cargando();
									console.log(error.responseText);
								}
							});
						});
						//}
					} else {

						var productos_add = productos + "|" + cantidad + "|" + (Number(pid)) + "|";
						productos_array.push(productos_add);
						//var cantidad_padre = cantidad;
						VerProductosAgregados();
					}
				}
			});
			if (no_editar_integrados == 'si') {
				$('#btn_adicionar_componentes').click();
			}
		},
		error: function (error) {
			ocultar_cargando();
			console.log(error.responseText);
		}
	});
}

function VerProductosAgregados() {
	var resumir_lineas = 0;


	if ($('#resumir_lineas').prop('checked')) { resumir_lineas = 1; }
	var ocultar_integrados = $('#ocultar_integrados').val();
	var codcliente = $('#cod_cliente_ped').val();
	var nrorif = $('#nrorif').val();
	var datos = { "productos_array": productos_array, "cod_cliente": codcliente, "nrorif": nrorif };
	$.ajax({
		type: "POST",
		url: "vistas/productos_agregados.php?resumir_lineas=" + resumir_lineas,
		data: datos,
		success: function (r) {
			$('#productos_agregados').html(r);
			if (ocultar_integrados == 'si') {
				$('#productos_agregados .es_componente').hide();
			}
			ocultar_cantidad();
			eliminar_producto();
			notas_productos();
			descuentos_productos();
			$('#modal_compuestos').modal('hide');
			$('#modal_compuestos #lista_compuestos').html('');
			actualizar_estado_boton_compartir();
		}
	});
}

function cantidad(a) {

	var cantidad = parseFloat($('#cantidad input:text').val());
	if (a < 0) {
		cantidad = cantidad - 1;
	} else {
		cantidad = cantidad + 1;
	}
	if (cantidad <= 0 || $('#cantidad input:text').val() == "") {
		cantidad = 1;
	}
	$('#cantidad input:text').val(cantidad);
}

function eliminar_producto() {
	var id_empresa = $('#id_empresa').val();
	var mesa = $('#input_mesa').val();
	var resumir = 0;

	$('#productos_agregados table tbody tr .fa-times, #productos_agregados table tbody tr .clase-eliminar-producto').off('click').on('click', function (e) {
		e.stopPropagation();
		var currentRow = $(this).closest('tr');
		var posicion = currentRow.index();

		var cantidad = currentRow.find('td:eq(3)').html();
		var pidhijo = $(this).attr('pidhijo');
		var pid = $(this).attr('pid');
		// Nuevas variables para los atributos se_comando y se_guardado
		var se_comando_attr = $(this).attr('se_comando');
		var se_guardado_attr = $(this).attr('se_guardo');

		let indiceEnProductosArray = -1;

		if (cantidad == 1) {
			for (let i = 0; i < productos_array.length; i++) {
				let datos_producto_array = productos_array[i].split('|');
				if (datos_producto_array[13] == pid && datos_producto_array[0] == pidhijo) {
					indiceEnProductosArray = i;
					break;
				}
			}
		} else {
			for (let i = productos_array.length - 1; i >= 0; i--) {
				let datos_producto_array = productos_array[i].split('|');
				let se_comando = datos_producto_array[8];
				if (datos_producto_array[0] == pidhijo && se_comando == 0) {
					indiceEnProductosArray = i;
					break;
				}
			}

			if (indiceEnProductosArray === -1) {
				for (let i = productos_array.length - 1; i >= 0; i--) {
					let datos_producto_array = productos_array[i].split('|');
					if (datos_producto_array[13] == pid && datos_producto_array[0] == pidhijo) {
						indiceEnProductosArray = i;
						break;
					}
				}
			}
		}

		if (indiceEnProductosArray === -1) {
			console.error("Producto no encontrado en productos_array para eliminar.");
			MostrarMensaje('error|No se pudo encontrar el producto a eliminar.');
			return;
		}

		var datos_producto_a_eliminar = productos_array[indiceEnProductosArray].split('|');
		var codigo_producto_a_eliminar = datos_producto_a_eliminar[0];
		var estacion = datos_producto_a_eliminar[17];
		if (estacion == undefined) {
			estacion = 'PSK';
		}

		var datos_ajax = { "codigo": codigo_producto_a_eliminar, "pid": pid, "pidhijo": pidhijo, "mesa": mesa, "id_empresa": id_empresa };

		if (se_comando_attr === "1" || se_guardado_attr === "1") {
			mostrar_pw_administrador("Eliminar producto");
			$('#pw_administrador .btn-success').unbind('click').click(function () {
				var password_sup = $('#password_sup').val();
				datos_ajax.password_sup = password_sup;

				$.ajax({
					type: "POST",
					url: "funciones/permisos_adm.php",
					data: datos_ajax,
					beforeSend: function () {
						mostrar_cargando();
					},
					success: function (r) {
						ocultar_cargando();
						if (r.trim() == "true") {
							$.ajax({
								type: "POST",
								url: "controladores/controlador_inventario.php?accion=eliminar_producto",
								data: datos_ajax,
								beforeSend: function () {
									mostrar_cargando();
								},
								success: function (r_eliminar) {
									ocultar_cargando();
									ocultar_pw_administrador();
									$('#msj_php .ventanas_contenido').html(r_eliminar);
									currentRow.remove();
									productos_array.splice(indiceEnProductosArray, 1);
								}
							});
						} else {
							$('#pw_administrador i').html("Contrase&ntildea incorrecta");
							$('#bloqueo').show();
						}
					},
					error: function () {
						ocultar_cargando();
						$('#pw_administrador i').html("Error de comunicación con el servidor.");
						$('#bloqueo').show();
					}
				});
			});
		} else {
			// Si no se necesita contraseña, ejecuta directamente la eliminación
			$.ajax({
				type: "POST",
				url: "controladores/controlador_inventario.php?accion=eliminar_producto",
				data: datos_ajax,
				beforeSend: function () {
					mostrar_cargando();
				},
				success: function (r_eliminar) {
					ocultar_cargando();
					// No necesitas ocultar la contraseña si no la mostraste
					$('#msj_php .ventanas_contenido').html(r_eliminar);
					currentRow.remove();
					productos_array.splice(indiceEnProductosArray, 1);
				}
			});
		}
	});

	if (resumir == 1) {
		$('#resumir_lineas').prop('checked', true);
	}
}

// function eliminar_producto() {
// 	var id_empresa = $('#id_empresa').val();
// 	var mesa = $('#input_mesa').val();
// 	var resumir = 0;

// 	$('#productos_agregados table tbody tr').mouseenter(function () {

// 		var posicion = $(this).index();
// 		$('#productos_agregados table tbody tr').eq(posicion).find('.fa-times').unbind();
// 		$('#productos_agregados table tbody tr').eq(posicion).find('.fa-times').click(function () {

// 		// debugger
// 			var cantidad = $('#productos_agregados table tbody tr').eq(0).find('td:eq(3)').html();
// 			var pidhijo = $(this).attr('pidhijo');
// 			var sin_comandar = 0;
// 			if (cantidad == 1) {
// 				var pid = $(this).attr('pid');
// 				for (i in productos_array) {
// 					var datos_producto = productos_array[i].split('|');
// 					var pid_delete = datos_producto[13];
// 					var pidhijo_delete = datos_producto[0];
// 					var se_comando = datos_producto[8];
// 					if (pid == pid_delete && pidhijo == pidhijo_delete && se_comando == 0) {
// 						posicion = i;
// 					}
// 				}
// 			} else {
// 				k = 0;
// 				//for (i  in productos_array) {
// 				for (var i = productos_array.length - 1; i >= 0; i--) {
// 					var datos_producto = productos_array[i].split('|');
// 					var pid_delete = datos_producto[13];
// 					var pidhijo_delete = datos_producto[0];
// 					var se_guardo = $(this).attr('se_comando');
// 					var se_comando = datos_producto[8];

// 					if (pidhijo == pidhijo_delete && se_comando == 0) {
// 						posicion = i;
// 						pid = pid_delete;
// 						k = 1;
// 						break;
// 					}
// 				}
// 				if (k == 0) {
// 					var pid = $(this).attr('pid');
// 					//for (i in productos_array) {
// 					for (var i = productos_array.length - 1; i >= 0; i--) {
// 						var datos_producto = productos_array[i].split('|');
// 						var pid_delete = datos_producto[13];
// 						var pidhijo_delete = datos_producto[0];
// 						var se_comando = datos_producto[8];
// 						if (pid == pid_delete && pidhijo == pidhijo_delete && se_comando == 0) {

// 							posicion = i;
// 						}
// 					}
// 				}
// 				// $('#productos_agregados table tbody tr td').each(function(){
// 				// 	var contenido = $(this).attr('pidhijo');
// 				// 	alert(contenido);
// 				// });



// 			}


// 			var datos_producto = productos_array[posicion].split('|');
// 			var codigo = datos_producto[0];
// 			var estacion = datos_producto[17];
// 			if (estacion == undefined) {
// 				estacion = 'PSK';
// 			}

// 			var datos = { "codigo": codigo, "pid": pid, "pidhijo": pidhijo, "mesa": mesa, "id_empresa": id_empresa };
// 			var se_guardo = $(this).attr('se_comando');
// 			//var se_comando = $(this).attr('se_comando');
// 			var se_comando = datos_producto[8];
// 			if (se_comando == 0 && (estacion == 'PSK' || estacion == '')) {
// 				$.ajax({
// 					type: "POST",
// 					url: "controladores/controlador_inventario.php?accion=eliminar_producto",
// 					data: datos,
// 					beforeSend: function () {
// 						mostrar_cargando();
// 					},
// 					success: function (r) {
// 						ocultar_cargando();
// 						ocultar_pw_administrador();
// 						$('#msj_php .ventanas_contenido').html(r);
// 					}
// 				});
// 			} else {
// 				mostrar_pw_administrador("Eliminar producto");
// 				$('#pw_administrador .btn-success').unbind();
// 				$('#pw_administrador .btn-success').click(function () {
// 					var password_sup = $('#password_sup').val();
// 					datos.password_sup = password_sup;
// 					$.ajax({
// 						type: "POST",
// 						url: "funciones/permisos_adm.php",
// 						data: datos,
// 						beforeSend: function () {
// 							mostrar_cargando();
// 						},
// 						success: function (r) {
// 							ocultar_cargando();
// 							if (r == "true") {
// 								$.ajax({
// 									type: "POST",
// 									url: "controladores/controlador_inventario.php?accion=eliminar_producto",
// 									data: datos,
// 									beforeSend: function () {
// 										mostrar_cargando();
// 									},
// 									success: function (r) {
// 										ocultar_cargando();
// 										ocultar_pw_administrador();
// 										$('#msj_php .ventanas_contenido').html(r);
// 									}
// 								});
// 							} else {
// 								$('#pw_administrador i').html("Contrase&ntildea incorrecta");
// 								$('#bloqueo').show();
// 							}
// 						}
// 					});
// 				});
// 			}
// 		});
// 	});
// 	if (resumir == 1) {
// 		$('#resumir_lineas').prop('checked', true);
// 	}
// }

function notas_productos() {
	var mesa = $('#input_mesa').val();
	$('#productos_agregados table tbody tr').mouseenter(function () {
		var posicion = $(this).index();
		let hijito = $('#productos_agregados table tbody tr').eq(posicion).attr('pidhijo');
		var pid = $('#productos_agregados table tbody tr').eq(posicion).attr('pid');
		$('#productos_agregados table tbody tr').eq(posicion).find('.fa-edit').unbind();
		$('#productos_agregados table tbody tr').eq(posicion).find('.fa-edit').click(function () {
			mostrar_notas();
			if (posicion > (productos_array.length - 1)) {
				for (var i = 0; i < productos_array.length; i++) {
					var prod = productos_array[i].split("|");
					if (hijito == prod[0] && pid == prod[13]) {
						posicion = i;
						break;
					}
				}
			}
			var split = productos_array[posicion].split("|");
			var prc_descuento = split[15];
			if (split[15] == 'undefined' || split[15] == undefined) {
				prc_descuento = 0;
			}
			var notas_agregadas = split[16];
			if (split[16] == 'undefined' || split[16] == undefined) {
				notas_agregadas = "";
			}
			$('#notas textarea').val(notas_agregadas);
			$('#notas .btn-success').unbind();

			$('#notas .btn-success').click(function () {
				var notas = $('#notas textarea').val();

				if ($('#resumir_lineas').prop('checked')) {

					//for (i in productos_array) {
					for (var i = 0; i < productos_array.length; i++) {
						var split = productos_array[i].split("|");
						var prc_descuento = split[15];
						if (split[15] == 'undefined' || split[15] == undefined) {
							prc_descuento = 0;
						}
						var notas_agregadas = split[16];
						if (split[16] == 'undefined' || split[16] == undefined) {
							notas_agregadas = "";
						}
						var impreso = split[8];
						var notas = $('#notas textarea').val();
						var datos_productos = productos_array[i].split('|');
						var codigo = datos_productos[0];
						var pid = datos_productos[13];
						var codpadre = datos_productos[14];
						var estacion = (datos_productos[17] == '') ? datos_productos[17] : 'PSK';

						if (codigo == hijito && impreso == 0) {
							var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + pid + "|" + codpadre + "|" + prc_descuento + "|" + notas + "|" + estacion;
							productos_array.splice(i, 1, producto);
							console.log(producto);
						}
					}
				} else {


					var split = productos_array[posicion].split("|");
					var prc_descuento = split[15];
					if (split[15] == 'undefined' || split[15] == undefined) {
						prc_descuento = 0;
					}
					var notas_agregadas = split[16];
					if (split[16] == 'undefined' || split[16] == undefined) {
						notas_agregadas = "";
					}
					var estacion = (split[17] == '') ? split[17] : 'PSK';
					var notas = $('#notas textarea').val();
					//var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
					//productos_array.splice(posicion, 1, producto);

					if (split[12] == 1) {
						var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
						productos_array.splice(posicion, 1, producto);
					} else if (split[12] > 1) {
						var cant = split[12] - 1;
						var pid_new = Number(split[13]) + Number(productos_array.length)
						var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|1|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
						productos_array.splice(posicion, 1, producto);
						var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + cant + "|" + pid_new + "|" + split[14] + "|" + prc_descuento + "||" + estacion;
						productos_array.push(producto);
						//procesar_pedido(0);
					}

				}

				ver_productos_agregados();
				ocultar_notas();
			});
		});
	});
}


function GuardarNota() {
	var mesa = $('#input_mesa').val();
	var posicion = $(this).index();
	var cod_hijo = $(this).attr('pidhijo');
	var split = productos_array[posicion].split("|");
	var prc_descuento = split[15];
	if (split[15] == 'undefined' || split[15] == undefined) {
		prc_descuento = 0;
	}

	var notas_agregadas = split[16];
	if (split[16] == 'undefined' || split[16] == undefined) {
		notas_agregadas = "";
	}

	$('#notas textarea').val(notas_agregadas);
	$('#notas .btn-success').unbind();
	$('#notas .btn-success').click(function () {
		var notas = $('#notas textarea').val();
		if ($('#resumir_lineas').prop('checked')) {
			//for (i in productos_array) {
			for (var i = 0; i < productos_array.length; i++) {
				var datos_productos = productos_array[i].split('|');
				var codigo = datos_productos[0];
				var pid = datos_productos[13];
				var codpadre = datos_productos[14];
				var estacion = (datos_productos[17] == '') ? datos_productos[17] : 'PSK';

				if (codigo == cod_hijo) {
					var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + pid + "|" + codpadre + "|" + prc_descuento + "|" + notas + "|" + estacion;
					productos_array.splice(i, 1, producto);
				}
			}
		} else {

			if (split[12] == 1) {
				var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
				productos_array.splice(posicion, 1, producto);
			} else if (split[12] > 1) {
				var cant = split[12] - 1;
				var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|1|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
				productos_array.splice(posicion, 1, producto);
				var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + cant + "|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
				productos_array.push(producto);
			}

		}



		ver_productos_agregados();
		ocultar_notas();
	});
}





function mostrar_notas() {
	$('#bloqueo').show();
	$('#notas').show();
	$('#notas textarea').focus();
}

function ocultar_notas() {
	$('#bloqueo').hide();
	$('#notas').hide();
	$('#notas textarea').val('');
}

function descuentos_productos() {
	var permitir_descuentos = $('#permitir_descuentos').val();
	if (permitir_descuentos == 'no') {
		return;
	}
	$('#productos_agregados table tbody tr').mouseenter(function () {
		var pid = $(this).attr('pid');
		$('#productos_agregados table tbody tr[pid="' + pid + '"]').find('.fa-percent').unbind();
		$('#productos_agregados table tbody tr[pid="' + pid + '"]').find('.fa-percent').click(function () {
			mostrar_descuentos();

			var posicion = 0;
			for (i in productos_array) {
				var datos_producto = productos_array[i].split("|");
				if (datos_producto[13] == pid) {
					posicion = i;
					var split = datos_producto;
				}
			}

			var precio_unit_bruto = split[4];
			var prc_descuento = split[15];
			if (split[15] == 'undefined' || split[15] == undefined) {
				prc_descuento = 0;
			}

			var notas = split[16];
			if (split[16] == 'undefined' || split[16] == undefined) {
				notas = '';
			}

			var estacion = split[17];
			if (split[17] == 'undefined' || split[17] == undefined) {
				estacion = 'PSK';
			}

			$('#descuentos #prc_descuento').val(parseFloat(prc_descuento).toFixed(2));

			// $('#descuentos #monto_descuento').unbind();
			// $('#descuentos #monto_descuento').change(function () {
			// 	var monto_descuento = $(this).val();
			// 	prc_descuento = (parseFloat(monto_descuento) * 100) / precio_unit_bruto;
			// 	$('#descuentos #prc_descuento').val(parseFloat(prc_descuento).toFixed(6));
			// });

			$('#descuentos #monto_descuento').change(function () {
				var monto_descuento = parseFloat($(this).val());
				var precio_unit_bruto_num = parseFloat(precio_unit_bruto);

				if (monto_descuento >= precio_unit_bruto_num) {
					prc_descuento = 99;
				} else {
					prc_descuento = (monto_descuento * 100) / precio_unit_bruto_num;
				}

				$('#descuentos #prc_descuento').val(parseFloat(prc_descuento).toFixed(6));
			});

			$('#descuentos .btn-success').unbind();
			$('#descuentos .btn-success').click(function () {
				$('#descuentos').hide();
				mostrar_pw_administrador("Autorizar Descuento");
				$('#pw_administrador .btn-success').unbind();
				$('#pw_administrador .btn-success').click(function () {
					var password_sup = $('#password_sup').val();
					var datos = new Object();
					datos.password_sup = password_sup;
					$.ajax({
						type: "POST",
						url: "funciones/permisos_adm.php",
						data: datos,
						beforeSend: function () {
							mostrar_cargando();
						},
						success: function (r) {
							ocultar_cargando();
							if (r == "true") {
								ocultar_pw_administrador();
								var prc_descuento = $('#descuentos #prc_descuento').val();
								if (prc_descuento == '') { prc_descuento = 0; }
								if ($('#resumir_lineas').prop('checked')) {
									for (i in productos_array) {
										var datos_productos = productos_array[i].split('|');
										var codigo = datos_productos[0];
										var pid = datos_productos[13];
										var codpadre = datos_productos[14];
										var estacion = (datos_productos[17] == '') ? datos_productos[17] : 'PSK';
										if (codigo == split[0]) {
											var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + pid + "|" + codpadre + "|" + prc_descuento + "|" + notas + "|" + estacion;
											productos_array.splice(i, 1, producto);
										}
									}
								} else {
									var producto = split[0] + "|" + split[1] + "|" + split[2] + "|" + split[3] + "|" + split[4] + "|" + split[5] + "|" + split[6] + "|" + split[7] + "|" + split[8] + "|" + split[9] + "|" + split[10] + "|" + split[11] + "|" + split[12] + "|" + split[13] + "|" + split[14] + "|" + prc_descuento + "|" + notas + "|" + estacion;
									productos_array.splice(posicion, 1, producto);
								}
								ver_productos_agregados();
								ocultar_descuentos();
							} else {
								$('#pw_administrador i').html("Contrase&ntildea incorrecta");
								$('#bloqueo').show();
							}
						}
					});
				});

			});
		});
	});
}

function mostrar_descuentos() {
	$('#bloqueo').show();
	$('#descuentos').show();
	$('#descuentos #prc_descuento').focus();
}

function ocultar_descuentos() {
	$('#bloqueo').hide();
	$('#descuentos').hide();
	$('#descuentos input').val('');
}

function cancelar_operacion() {
	$('#input_mesa').css('border', '1px solid #CCC');
	var mesa = $('#input_mesa').val();
	if (mesa != "") {
		$('#input_mesa').css('border', '1px solid #CCC');
		mostrar_pw_administrador("Cancelar operacion");
		$('#pw_administrador .btn-success').unbind();
		$('#pw_administrador .btn-success').click(function () {
			var password_sup = $('#password_sup').val();
			var id_empresa = $('#id_empresa').val();
			var datos = { "mesa": mesa, "id_empresa": id_empresa, "password_sup": password_sup };
			$.ajax({
				type: "POST",
				url: "funciones/permisos_adm.php",
				data: datos,
				beforeSend: function () {
					mostrar_cargando();
				},
				success: function (r) {
					ocultar_cargando();
					if (r == "true") {
						$.ajax({
							type: "POST",
							url: "controladores/controlador_pedidos.php?accion=cancelar_pedido",
							data: datos,
							beforeSend: function () {
								mostrar_cargando();
							},
							success: function (r) {
								ocultar_pw_administrador();
								ocultar_cargando();
								mostrar_msj(r);
							}
						});
					} else {
						$('#pw_administrador i').html("Contrase&ntildea incorrecta");
						$('#bloqueo').show();
					}
				}
			});
		});
		$('#msj_php .ventanas_contenido .btn-danger').unbind();
		$('#msj_php .ventanas_contenido .btn-danger').click(function () {
			ocultar_msj();
		});
	} else {
		$('#input_mesa').css('border', '2px solid red');
	}

}

function limpiar_productos_agregados() {
	productos_array = [];
	var r = '<table class="table table-striped"><thead><tr><th>Notas</th><th>Codigo</th><th>Cant.</th><th>Producto</th><th>Total</th><th>Eliminar</th></tr></thead></table>';
	$('#productos_agregados').html(r);
	$('.precio_total').val('0,00');
	$('.precio_total_ref').val('0,00');
	$('.precio_total_moneda_extranjera').val('0,00');
	var monedafactor = $('#monedafactor').val();
	var dividemultiplica = $('#dividemultiplica').val();
	$('#detalle_cuenta input').val('');
	$('#monedafactor').val(monedafactor);
	$('#dividemultiplica').val(dividemultiplica);
	$('#input_mesa').val('');
	$('#btn_compartir_qr').hide();
}

function ResumirLineas() {
	var vendedor = $('#cod_vend').val();
	var mesa = $('#input_mesa').val();
	$.when(procesar_pedido(0))
		.done(function () {
			$('#cod_vend').val(vendedor);
			$('#input_mesa').val(mesa);
			consultar_mesa();
		});
	//ver_productos_agregados();
}

function procesar_pedido(imprimir) {
	var vendedor = $('#cod_vend').val();
	var nombre_cliente = $('#input_cliente').val();
	var nrorif = $('#nrorif').val();
	var mesa = $('#input_mesa').val();
	var cod_cliente = $('#cod_cliente_ped').val();
	var cliente_exento = $('#cliente_exento').val();
	var datos = { "productos_array": productos_array, "vendedor": vendedor, "cod_cliente": cod_cliente, "nombre_cliente": nombre_cliente, "nrorif": nrorif, "mesa": mesa, "imprimir": imprimir, "cliente_exento": cliente_exento };
	if (cod_cliente != "") {
		if (nombre_cliente != "") {
			$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
			$('#input_cliente').css({ 'border': '1px solid #CCC' });
			return $.ajax({
				type: "POST",
				url: "controladores/controlador_pedidos.php?accion=procesar_pedido&hora=" + HoraActual(),
				data: datos,
				beforeSend: function () {
					mostrar_cargando();
				},
				success: function (r) {
					ocultar_cargando();
					mostrar_msj(r);
					mostrar_grupos();
					const consumoExistenteEnSuccess = localStorage.getItem('consumo');
					if (consumoExistenteEnSuccess) {
						localStorage.removeItem('consumo');
						console.log('El elemento "consumo" ha sido eliminado de localStorage tras procesar el pedido con éxito.');
						// Y lo más importante: ¡actualiza la visibilidad del botón de cancelar!
						actualizarEstadoBotonCancelar();
					} else {
						console.log('El elemento "consumo" no existía en localStorage al procesar el pedido.');
					}
				}
			});


		} else {
			$('#input_cliente').css({ 'border': '2px solid red' });
		}
	} else {
		$('#cod_cliente_ped').css({ 'border': '2px solid red' });
	}
}

// function ConsultarCliente(codcliente) {
// 	if (codcliente != '') {
// 		var datos = { "codcliente": codcliente };
// 		$.ajax({
// 			dataType: 'JSON',
// 			type: "POST",
// 			data: datos,
// 			url: "controladores/controlador_datos_conexion.php?accion=consultar_cliente",
// 			beforeSend: function () {
// 				mostrar_cargando();
// 			},
// 			success: function (r) {
// 				ocultar_cargando();
// 				if ($('#reg_cli').is(':visible')) {
// 					var codigo = $('#reg_cli input[name="codigo"]').val();
// 					LimpiarFormulario('form_reg_cli');
// 					$('#reg_cli input[name="codigo"]').val(codigo);
// 					if (r != null) {
// 						$('#reg_cli input[name="nombre"]').val(r.nombre);
// 						$('#reg_cli input[name="perscont"]').val(r.perscont);
// 						$('#reg_cli input[name="cedula"]').val(r.cedula);
// 						$('#reg_cli input[name="nrorif"]').val(r.nrorif);
// 						$('#reg_cli input[name="nronit"]').val(r.nronit);
// 						$('#reg_cli input[name="telefonos"]').val(r.telefonos);
// 						$('#reg_cli input[name="email"]').val(r.email);
// 						$('#reg_cli textarea[name="direccion"]').val(r.direccion);
// 						if (r.formafis == 3) {
// 							$('#reg_cli input[name="formafis"]').prop('checked', true);
// 						} else {
// 							$('#reg_cli input[name="formafis"]').prop('checked', false);
// 						}
// 					}
// 				} else {
// 					// debugger
// 					if (r != null) {
// 						$('input[name="codigo"]').on('input', function () {
// 							const valor = $(this).val();
// 							$('input[name="cedula"]').val(valor);
// 							$('input[name="nrorif"]').val(valor);
// 							$('input[name="nronit"]').val(valor);
// 						});
// 						$('#cod_cliente_ped').val(r.codigo);
// 						$('#input_cliente').val(r.nombre);
// 						$('#nrorif').val(r.nrorif);
// 					} else {
// 						$('#cod_cliente_ped').val(codcliente_grabado);
// 						ConsultarCliente(codcliente_grabado);
// 					}
// 				}
// 			},
// 			error: function (err) {
// 				ocultar_cargando();
// 				console.log(err.responseText);
// 			}
// 		});
// 	}
// }

function ConsultarCliente(codcliente, nrorif_backup) {
	if (codcliente != '') {
		var datos = { "codcliente": codcliente };
		$.ajax({
			dataType: 'JSON',
			type: "POST",
			data: datos,
			url: "controladores/controlador_datos_conexion.php?accion=consultar_cliente",
			beforeSend: function () {
				mostrar_cargando();
			},
			success: function (r) {
				ocultar_cargando();

				if ($('#reg_cli').is(':visible')) {
					// ⏺️ Guardamos datos antes de limpiar
					var codigo = $('#reg_cli input[name="codigo"]').val();
					var cedula = $('#reg_cli input[name="cedula"]').val();
					var nrorif = $('#reg_cli input[name="nrorif"]').val();
					var nronit = $('#reg_cli input[name="nronit"]').val();

					// 🔁 Limpiamos formulario
					LimpiarFormulario('form_reg_cli');

					// 🔁 Restauramos código
					$('#reg_cli input[name="codigo"]').val(codigo);

					// ✅ Si encontró datos, los usamos
					if (r != null) {
						$('#reg_cli input[name="nombre"]').val(r.nombre);
						$('#reg_cli input[name="perscont"]').val(r.perscont);
						$('#reg_cli input[name="cedula"]').val(r.cedula);
						$('#reg_cli input[name="nrorif"]').val(r.nrorif);
						$('#reg_cli input[name="nronit"]').val(r.nronit);
						$('#reg_cli input[name="telefonos"]').val(r.telefonos);
						$('#reg_cli input[name="email"]').val(r.email);
						$('#reg_cli textarea[name="direccion"]').val(r.direccion);
						$('#reg_cli input[name="formafis"]').prop('checked', r.formafis == 3);
					} else {
						// 🚫 No encontró cliente: restaurar lo que el usuario ya había escrito
						$('#reg_cli input[name="cedula"]').val(cedula);
						$('#reg_cli input[name="nrorif"]').val(nrorif);
						$('#reg_cli input[name="nronit"]').val(nronit);
					}
				} else {
					// 🔍 En vista resumen (no formulario de registro)
					if (r != null) {
						// Activar bandera para indicar actualización programática
						window.actualizando_cliente_programaticamente = true;
						
						$('#cod_cliente_ped').val(r.codigo);
						$('#input_cliente').val(r.nombre);
						
						// Guardar el precio del cliente en una variable global
						window.precio_cliente = r.precio || null;
						console.log('Precio del cliente guardado:', window.precio_cliente);
						
						console.log('=== ConsultarCliente - Actualizando nrorif ===');
						console.log('r.nrorif de consulta:', r.nrorif);
						console.log('nrorif_backup:', nrorif_backup);
						// Obtener el campo correcto por placeholder
						var campo_actual_nrorif = $('input[placeholder="Nro Identificación"]');
						if (campo_actual_nrorif.length === 0) {
							campo_actual_nrorif = $('#nrorif').last();
						}
						var valor_actual_campo = campo_actual_nrorif.val();
						console.log('Valor actual en campo:', valor_actual_campo);
						
						// Determinar qué valor usar - priorizar el que sea más válido
						var nrorif_final = null;
						
						// 1. Si la consulta completa tiene un valor válido, usarlo
						if (r.nrorif && r.nrorif != '' && r.nrorif != '0000000' && String(r.nrorif).trim() != '') {
							nrorif_final = String(r.nrorif).trim();
							console.log('✓ Usando nrorif de consulta completa:', nrorif_final);
						}
						// 2. Si no, pero hay backup válido, usar el backup
						else if (nrorif_backup && nrorif_backup != '' && nrorif_backup != '0000000') {
							nrorif_final = nrorif_backup;
							console.log('✓ Usando nrorif de backup (búsqueda):', nrorif_final);
						}
						// 3. Si no, mantener el valor actual del campo (ya establecido)
						else if (valor_actual_campo && valor_actual_campo != '' && valor_actual_campo != '0000000') {
							nrorif_final = null; // No cambiar, mantener el actual
							console.log('✓ Manteniendo valor actual en campo (no sobrescribir):', valor_actual_campo);
						} else {
							console.log('⚠ No hay valor válido disponible, campo permanece vacío');
						}
						
						// Solo actualizar si hay un valor válido nuevo para establecer
						if (nrorif_final) {
							// Usar el mismo selector que en select_resultado_busqueda - por placeholder
							var campo_nrorif_final;
							if ($('#reg_cli').is(':visible')) {
								campo_nrorif_final = $('#reg_cli input[name="nrorif"]');
							} else {
								// Campo principal por placeholder (único para este campo)
								campo_nrorif_final = $('input[placeholder="Nro Identificación"]');
							}
							
							if (campo_nrorif_final.length > 0) {
								campo_nrorif_final.val(nrorif_final).attr('value', nrorif_final);
								console.log('✓ nrorif actualizado en campo:', nrorif_final);
								
								// Verificar que se estableció correctamente
								setTimeout(function() {
									var valor_verificado = campo_nrorif_final.val();
									console.log('🔍 Valor verificado en campo después de actualizar:', valor_verificado);
									if (valor_verificado !== nrorif_final) {
										console.log('⚠️ ERROR: El valor no se estableció correctamente. Reintentando...');
										campo_nrorif_final.val(nrorif_final).attr('value', nrorif_final).trigger('input');
									}
								}, 50);
							} else {
								console.log('⚠️ ERROR: No se encontró el campo nrorif principal');
							}
						}
						console.log('==============================================');
						
						$('#cliente_exento').val(r.formafis);
					} else {
						// Si no hay datos, dejar como está o manejar según lógica propia
						$('#cod_cliente_ped').val(codcliente);
					}
					
					// Desactivar bandera después de establecer los valores
					setTimeout(function() {
						window.actualizando_cliente_programaticamente = false;
					}, 100);
				}
			},
			error: function (err) {
				ocultar_cargando();
				console.log(err.responseText);
				
				// Desactivar bandera incluso si hay error
				window.actualizando_cliente_programaticamente = false;
			}
		});
	}
}


function mostrar_cantidad(codigo_articulo) {
	$('#bloqueo').show();
	$('#cantidad').show();
	
	// Limpiar el select
	$('#select_precio').html('<option value="">Seleccione un precio</option>');
	
	// Si se proporciona el código del artículo, cargar los precios
	if (codigo_articulo && codigo_articulo != '') {
		console.log('Cargando precios para artículo:', codigo_articulo);
		$.ajax({
			url: "controladores/controlador_inventario.php?accion=obtener_precios&codigo=" + encodeURIComponent(codigo_articulo),
			type: "GET",
			success: function(r) {
				console.log('Respuesta del servidor:', r);
				var precios = r;
				console.log('Precios parseados:', precios);
				if (precios && precios.length > 0) {
					precios.forEach(function(precio) {
						var valor_preciofin = precio.preciofin || precio.valor;
						$('#select_precio').append('<option value="' + precio.precio + '|' + precio.valor + '|' + valor_preciofin + '">' + precio.nombre + ' - ' + parseFloat(precio.valor).toFixed(2) + '</option>');
					});
					
					// Seleccionar automáticamente el precio según el cliente
					if (window.precio_cliente && window.precio_cliente != null && window.precio_cliente != '') {
						var precio_cliente_num = parseInt(window.precio_cliente);
						if (precio_cliente_num >= 1 && precio_cliente_num <= 7) {
							var precio_a_seleccionar = 'precio' + precio_cliente_num;
							console.log('Seleccionando precio automáticamente según cliente:', precio_a_seleccionar);
							
							// Buscar la opción que corresponde al precio del cliente
							$('#select_precio option').each(function() {
								var valor_opcion = $(this).val();
								if (valor_opcion && valor_opcion.startsWith(precio_a_seleccionar + '|')) {
									$('#select_precio').val(valor_opcion);
									// Disparar el evento change para actualizar el precio en productos_actualizado
									$('#select_precio').trigger('change');
									console.log('Precio seleccionado automáticamente:', valor_opcion);
									return false; // Salir del each
								}
							});
						}
					}
				} else {
					console.log('No se encontraron precios para el artículo');
				}
			},
			error: function(error) {
				console.error('Error al obtener precios:', error);
				console.error('Response text:', error.responseText);
			}
		});
	} else {
		console.log('No se proporcionó código de artículo o está vacío');
	}
}

function ocultar_cantidad() {
	$('#bloqueo').hide();
	$('#cantidad').hide();
	$('#cantidad input:text').val('1');
	$('#select_precio').html('<option value="">Seleccione un precio</option>');
}

function mostrar_grupos_mesas(pagina, filtro) {
	var vendedor = $('#cod_vend').val();
	var datos = { "pagina": pagina, "vendedor": vendedor, "filtro": filtro };
	$.ajax({
		type: "POST",
		url: "controladores/controlador_inventario.php?accion=mostrar_grupos_mesas",
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#inventario').html(r);
		}
	});
}

function mostrar_mesas(pagina, filtro, ubicacion) {
	var vendedor = $('#cod_vend').val();
	var datos = { "pagina": pagina, "vendedor": vendedor, "filtro": filtro, "ubicacion": ubicacion };
	$.ajax({
		type: "POST",
		url: "controladores/controlador_inventario.php?accion=mostrar_mesas&hora=" + HoraActual(),
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#inventario').html(r);
		}
	});
}


function actualizarEstadoBotonCancelar() {
	const cancelarButton = $('#cancelarButton');
	const consumoGuardado = localStorage.getItem('consumo');
	if (consumoGuardado) {
		cancelarButton.hide();

		console.log('El elemento "consumo" existe en localStorage. El botón "cancelarButton" está oculto.');
	} else {
		cancelarButton.show();
	
		console.log('El elemento "consumo" NO existe en localStorage. El botón "cancelarButton" está visible.');
	}
}


function seleccionar_mesa(id_consumo, codcliente, nombrecli, codigo_vendedor, nrorif) {
	var cuentas_compartidas = $.trim($('input[name="cuentas_compartidas"]').val());
	var codigo_vendedor = $.trim(codigo_vendedor);
	var cod_vend = $.trim($('#cod_vend').val());
	if (
		(cuentas_compartidas == 'si') ||
		(cuentas_compartidas == 'no' && codigo_vendedor == cod_vend) ||
		(codigo_vendedor == null || codigo_vendedor == '')
	) {
		localStorage.setItem('consumo', id_consumo);
		actualizarEstadoBotonCancelar();
		
		$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
		$('#input_cliente').css({ 'border': '1px solid #CCC' });
		$('#input_mesa').css('border', '1px solid #CCC');
		$('#input_mesa').val(id_consumo);
		
		// Si la mesa tiene un cliente asociado, usarlo
		if (codcliente != '' && codcliente != null) {
			// Activar bandera para indicar actualización programática
			window.actualizando_cliente_programaticamente = true;
			$('#cod_cliente_ped').val(codcliente);
			$('#input_cliente').val(nombrecli);
			if (nrorif != '' && nrorif != null) {
				$('#nrorif').val(nrorif);
			}
			// Consultar el cliente para obtener el precio
			ConsultarCliente(codcliente);
		} else {
			// Si la mesa no tiene cliente asociado, mantener el cliente actual si existe
			var cliente_actual = $('#cod_cliente_ped').val();
			if (cliente_actual && cliente_actual != '' && cliente_actual != codcliente_grabado) {
				// Ya hay un cliente seleccionado, mantenerlo
				// No cambiar los valores del cliente
			} else {
				// No hay cliente seleccionado, usar el cliente por defecto
				// Activar bandera para indicar actualización programática
				window.actualizando_cliente_programaticamente = true;
				$('#cod_cliente_ped').val(codcliente_grabado);
				ConsultarCliente(codcliente_grabado);
			}
		}
		
		ver_cuenta_mesa();
		mostrar_grupos();
	} else {
		mostrar_msj("<i class='fa fa-times'></i><br>Esta cuenta es de otro vendedor");
	}
}

function ver_cuenta_mesa() {
	$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
	$('#input_cliente').css({ 'border': '1px solid #CCC' });
	$('.precio_total').val('0,00');
	$('.precio_total_ref').val('0,00');
	$('.precio_total_moneda_extranjera').val('0,00');
	productos_array = [];
	var mesa = $('#input_mesa').val();
	var datos = { "mesa": mesa };
	$.ajax({
		type: "POST",
		url: "controladores/controlador_inventario.php?accion=ver_cuenta_mesa",
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			var datos = r.split(";");
			for (i in datos) {
				if (datos[i] != "") {
					productos_array.push(datos[i])
				}
			}
			ver_productos_agregados();
			ver_operacion_actual();
		}
	});
}

function str_replace(search, replace, subject) {
	var new_string = '';
	subject = subject.toString();
	array_old = subject.split(search);
	total = array_old.length;
	for (k in array_old) {
		new_string += array_old[k];
		if (k < total - 1) {
			new_string += replace;
		}
	}
	return new_string;
}

function consultar_mesa() {


	$('#input_mesa').val(str_replace(' ', '', $('#input_mesa').val()));
	$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
	$('#cod_cliente_ped').css({ 'border': '1px solid #CCC' });
	$('#input_cliente').css({ 'border': '1px solid #CCC' });
	$('.precio_total').val('0,00');
	$('.precio_total_ref').val('0,00');
	$('.precio_total_moneda_extranjera').val('0,00');
	productos_array = [];
	var vendedor = $('#cod_vend').val();
	var mesa = $('#input_mesa').val();
	var nrorif = $('#nrorif').val();
	var datos = { "mesa": mesa, "vendedor": vendedor, "nrorif": nrorif };
	$.ajax({
		type: "POST",
		url: "controladores/controlador_inventario.php?accion=consultar_mesa",
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			var echo = r.split("//");
			var datos = echo[0].split(";");

			if ($.trim(echo[1]) != '') {
				// Si la mesa tiene un cliente asociado, usarlo
				// Activar bandera para indicar actualización programática
				window.actualizando_cliente_programaticamente = true;
				$('#input_cliente').val($.trim(echo[1]));
				// debugger
				ConsultarCliente($.trim(echo[3]));
			} else {
				// Si la mesa no tiene cliente asociado, mantener el cliente actual si existe
				// Solo usar el cliente por defecto si no hay ninguno seleccionado
				var cliente_actual = $('#cod_cliente_ped').val();
				if (cliente_actual && cliente_actual != '' && cliente_actual != codcliente_grabado) {
					// Ya hay un cliente seleccionado, mantenerlo
					// No hacer nada, solo mantener el cliente actual
				} else {
					// No hay cliente seleccionado, usar el cliente por defecto
					// Activar bandera para indicar actualización programática
					window.actualizando_cliente_programaticamente = true;
					ConsultarCliente(codcliente_grabado);
				}
			}

			var codigo_vendedor = $.trim(echo[2]);
			var cuentas_compartidas = $.trim($('input[name="cuentas_compartidas"]').val());
			var cod_vend = $.trim($('#cod_vend').val());
			if (
				(cuentas_compartidas == 'si') ||
				(cuentas_compartidas == 'no' && codigo_vendedor == cod_vend) ||
				(codigo_vendedor == null || codigo_vendedor == '')
			) {

				for (i in datos) {
					if (datos[i] != "") {
						productos_array.push(datos[i]);
					}
				}

				ver_productos_agregados();
				ver_operacion_actual();

			} else {
				mostrar_msj("<i class='fa fa-times'></i><br>Esta cuenta es de otro vendedor");
				$('#input_mesa').val('');
			}

		}
	});
}

function ver_productos_agregados() {
	var resumir_lineas = 0;
	if ($('#resumir_lineas').prop('checked')) {
		resumir_lineas = 1;
	}
	var ocultar_integrados = $('#ocultar_integrados').val();
	var datos = { "productos_array": productos_array };
	$.ajax({
		type: "POST",
		url: "vistas/productos_agregados.php?resumir_lineas=" + resumir_lineas,
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#productos_agregados').html(r);
			if (ocultar_integrados == 'si') {
				$('#productos_agregados .es_componente').hide();
			}
			eliminar_producto();
			notas_productos();
			descuentos_productos();
			actualizar_estado_boton_compartir();
		}
	});
}

function ver_operacion_actual() {
	$('#operacion_actual').show();
	$('#en_transito').hide();
	$('#en_transito').html('');
}

function ver_transacciones_transito() {

	var vendedor = $('#cod_vend').val();
	var datos = {};
	if (!$('#compartir_mesas').prop('checked')) {
		var datos = { "vendedor": vendedor };
	}
	$.ajax({
		type: "POST",
		url: "controladores/controlador_pedidos.php?accion=ver_pedidos",
		data: datos,
		beforeSend: function () {
			mostrar_cargando();
		},
		success: function (r) {
			ocultar_cargando();
			$('#operacion_actual').hide();
			$('#en_transito').show();
			$('#en_transito').html(r);
		}
	});
}

function mostrar_modal_reg_cli() {
	$('#reg_cli').show();
	$('#reg_cli input[type="text"]').val('');
}

var url_qr_actual = '';

function actualizar_estado_boton_compartir() {
	var mesa = $('#input_mesa').val() ? $('#input_mesa').val().trim() : '';
	if (typeof productos_array !== 'undefined' && productos_array.length > 0 && mesa !== '') {
		$('#btn_compartir_qr').css('display', 'inline-block');
	} else {
		$('#btn_compartir_qr').hide();
	}
}

function compartir_cuenta_qr() {
	var mesa = $('#input_mesa').val() ? $('#input_mesa').val().trim() : '';
	
	if (mesa === '') {
		alert('Por favor ingrese o seleccione una mesa para compartir.');
		$('#input_mesa').focus();
		return;
	}

	if (typeof productos_array === 'undefined' || productos_array.length === 0) {
		alert('La mesa no tiene productos cargados para compartir.');
		return;
	}

	mostrar_cargando();

	var datos_envio = {
		mesa: mesa,
		productos_array: productos_array
	};

	$.ajax({
		type: "POST",
		url: "controladores/controlador_qr.php?accion=obtener_pin",
		data: datos_envio,
		dataType: "json",
		success: function(r) {
			ocultar_cargando();
			if (r && r.success) {
				url_qr_actual = r.url;
				$('#qr_mesa_nombre').text('MESA ' + r.mesa);
				$('#qr_pin_numero').text(r.pin);
				
				// Limpiar contenedor previo
				$('#contenedor_qrcode').empty();

				try {
					if (typeof QRCode !== 'undefined') {
						new QRCode(document.getElementById("contenedor_qrcode"), {
							text: r.url,
							width: 180,
							height: 180,
							colorDark: "#2B303A",
							colorLight: "#FFFFFF",
							correctLevel: QRCode.CorrectLevel.M
						});
					} else {
						// Alternativa de fallback con SVG/IMG si la libreria fallara
						$('#contenedor_qrcode').html('<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(r.url) + '" style="width:180px;height:180px;" alt="QR Code" />');
					}
				} catch (e) {
					console.error("Error generando QR:", e);
					$('#contenedor_qrcode').html('<p style="font-size:12px;color:red;">No se pudo renderizar el QR gráfico.</p>');
				}

				$('#modal_compartir_qr').modal('show');
			} else {
				alert((r && r.error) ? r.error : 'No se pudo generar el código QR.');
			}
		},
		error: function(xhr, status, error) {
			ocultar_cargando();
			console.error("Error al generar QR:", error, xhr.responseText);
			alert('Error de comunicación al generar el QR de la mesa.');
		}
	});
}

function copiar_enlace_qr() {
	if (!url_qr_actual) return;
	
	if (navigator.clipboard) {
		navigator.clipboard.writeText(url_qr_actual).then(function() {
			var btn = $('#btn_copiar_url_qr');
			var originalText = btn.html();
			btn.html('<i class="fa fa-check"></i> ¡Enlace Copiado!');
			setTimeout(function() { btn.html(originalText); }, 2000);
		});
	} else {
		prompt('Copie este enlace:', url_qr_actual);
	}
}



