// Variable para almacenar el ID del temporizador
let inactivityTimer;

// Función para reiniciar el temporizador
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(logout, (minutos_inactividad * 60000)); // 60mil = 1 minuto. Cambia el tiempo en milisegundos que deseas esperar antes de cerrar sesión
}

// Función para cerrar sesión (llamar a una URL, etc.)
function logout() {
    // Redirigir a la página de cierre de sesión, por ejemplo
    window.location.href = 'logout_page.php';
}

if (minutos_inactividad > 0) {
    // Reiniciar el temporizador cuando se detecte actividad del usuario
    document.body.addEventListener('mousemove', resetInactivityTimer);
    document.body.addEventListener('keydown', resetInactivityTimer);

    // Iniciar el temporizador
    resetInactivityTimer();
}
document.addEventListener("DOMContentLoaded", function () {
    const prcDescuentoInput = document.getElementById("prc_descuento");

    prcDescuentoInput.addEventListener("input", function () {
        let value = parseInt(this.value);

        if (isNaN(value)) {
            this.value = ""; // Limpiar si no es un número
        } else if (value > 99) {
            this.value = "99"; // Establecer a 99 si es mayor
        } else if (value < 0) {
            this.value = "0"; // Establecer a 0 si es menor
        }
    });

    const codigoPaisGuardado = localStorage.getItem("codigo_pais");
    const recargoTotalElement = $("#recargo_total");
    const precuentaButton = $('#precuenta');

    if (recargoTotalElement.length) {
        // Verifica si el elemento existe en el DOM
        if (
            codigoPaisGuardado === "58"
        ) {
            // Ocultar el elemento de recargo
            recargoTotalElement.hide();
            recargoTotalElement.prev('label').hide();
            precuentaButton.hide();
        } else {
            // Mostrar el elemento de recargo
            recargoTotalElement.prev('label').show();
            recargoTotalElement.show();
            precuentaButton.show();
        }
    }

    const labelFiscal1 = $("#label-fiscal-1");
    const divFiscal2 = $("#div-fiscal-2");
    const inputFiscal1 = $('input[name="nrorif"]');

    if (
        codigoPaisGuardado === "58"
    ) {
        // Ocultar el segundo div
        divFiscal2.hide();

        // Modificar el texto del label
        labelFiscal1.text("RIF:");

        // Modificar el placeholder del input
        inputFiscal1.attr("placeholder", "RIF");
    }

    function actualizarEstadoBotonCancelar() {
        const consumoGuardado = localStorage.getItem('consumo');

        if (consumoGuardado) {
            cancelarButton.hide();
            console.log('El elemento "consumo" existe en localStorage. El botón "cancelarButton" está oculto.');
        } else {
            cancelarButton.show();
            console.log('El elemento "consumo" NO existe en localStorage. El botón "cancelarButton" está visible.');
        }
    }

    // Checkbox ocultar_campos
    const ocultarCamposCheckbox = document.getElementById('ocultar_campos_conf');
    const precioTotalInput = document.getElementById('precio_total');
    const precioTotalRefInput = document.getElementById('precio_total_ref');
    const precioTotalPrincipal = document.querySelector('.precio_total_principal');
    const textoTotalLabel = document.getElementById('texto_total');
    const detallesCuenta = document.querySelector('.detalles_cuenta');
    const logoDiv = document.getElementById('logo');



    function actualizarVisibilidadInputs() {
        if (localStorage.getItem('ocultar_campos') === 'true') {
            if (precioTotalInput) precioTotalInput.style.display = 'none';
            if (precioTotalRefInput) precioTotalRefInput.style.display = 'none';
            if (precioTotalPrincipal) precioTotalPrincipal.style.display = 'none';
            if (detallesCuenta) detallesCuenta.style.display = 'none';
            if (textoTotalLabel) textoTotalLabel.style.display = 'none';
            if (logoDiv) logoDiv.style.marginBottom = '20px'; 

        } else {
            if (precioTotalInput) precioTotalInput.style.display = '';
            if (precioTotalRefInput) precioTotalRefInput.style.display = '';
            if (precioTotalPrincipal) precioTotalPrincipal.style.display = '';
            if (detallesCuenta) detallesCuenta.style.display = '';
            if (textoTotalLabel) textoTotalLabel.style.display = '';
            if (logoDiv) logoDiv.style.marginBottom = ''; 
        }
    }

    if (ocultarCamposCheckbox) {
        // Inicializa el checkbox según el valor guardado
        ocultarCamposCheckbox.checked = localStorage.getItem('ocultar_campos') === 'true';

        // Actualiza la visibilidad al cargar
        actualizarVisibilidadInputs();

        ocultarCamposCheckbox.addEventListener('change', function () {
            if (this.checked) {
                localStorage.setItem('ocultar_campos', 'true');
            } else {
                localStorage.setItem('ocultar_campos', 'false');
            }
            actualizarVisibilidadInputs();
        });


    }

    // Checkbox ocultar_mesas_automaticas
    const ocultarMesasAutomaticasCheckbox = document.getElementById('ocultar_mesas_automaticas_conf');
    const contenedorMesaAutomatica = document.getElementById('contenedor_mesa_automatica');
    const inputGroupAddon = contenedorMesaAutomatica ? contenedorMesaAutomatica.querySelector('.input-group-addon') : null;
    const inputMesa = document.getElementById('input_mesa');

    function actualizarVisibilidadMesaAutomatica() {
        if (localStorage.getItem('ocultar_mesas_automaticas') === 'true') {
            if (inputGroupAddon) inputGroupAddon.style.display = 'none';
            if (inputMesa) inputMesa.style.width = '100%';
            if (contenedorMesaAutomatica) contenedorMesaAutomatica.style.width = '100%';
        } else {
            if (inputGroupAddon) inputGroupAddon.style.display = '';
            if (inputMesa && contenedorMesaAutomatica) inputMesa.style.width = '';
            if (contenedorMesaAutomatica) contenedorMesaAutomatica.style.width = '';
        }
    }

    if (ocultarMesasAutomaticasCheckbox) {
        // Inicializa el checkbox según el valor guardado
        ocultarMesasAutomaticasCheckbox.checked = localStorage.getItem('ocultar_mesas_automaticas') === 'true';

        // Actualiza la visibilidad al cargar
        actualizarVisibilidadMesaAutomatica();

        ocultarMesasAutomaticasCheckbox.addEventListener('change', function () {
            if (this.checked) {
                localStorage.setItem('ocultar_mesas_automaticas', 'true');
            } else {
                localStorage.setItem('ocultar_mesas_automaticas', 'false');
            }
            actualizarVisibilidadMesaAutomatica();
        });
    }
});

// ==========================================
// MÓDULO DE ACTUALIZACIÓN DEL SISTEMA (GIT)
// ==========================================
function verificar_actualizaciones_git() {
    var $btn = $('#btn_verificar_actualizacion');
    var $box = $('#contenedor_actualizacion');
    var $res = $('#resultado_actualizacion');

    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Consultando GitHub...');
    $box.slideDown(200);
    $res.html('<div style="text-align:center; color:#666; padding:10px;"><i class="fa fa-spinner fa-spin"></i> Verificando actualizaciones en el repositorio remoto...</div>');

    $.ajax({
        type: "POST",
        url: "controladores/controlador_actualizacion.php?accion=verificar",
        dataType: "json",
        timeout: 25000,
        success: function (r) {
            $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Verificar Actualizaciones');
            if (r.status === 'al_dia') {
                $res.html(
                    '<div class="alert alert-success" style="margin-bottom:0;">' +
                    '<i class="fa fa-check-circle" style="font-size:16px;"></i> <b>El sistema está al día</b><br>' +
                    '<span style="font-size:12px; color:#2b542c;">Versión instalada: <code>' + r.commit_actual + '</code> (' + r.detalle_actual + ')</span>' +
                    '</div>'
                );
            } else if (r.status === 'actualizacion_disponible') {
                var htmlCommits = '';
                if (r.commits && r.commits.length > 0) {
                    htmlCommits += '<ul style="margin:5px 0 10px 18px; padding:0; font-size:12px; max-height:120px; overflow-y:auto;">';
                    r.commits.forEach(function(c) {
                        htmlCommits += '<li><code>' + c.hash + '</code> ' + c.mensaje + ' <small class="text-muted">(' + c.tiempo + ')</small></li>';
                    });
                    htmlCommits += '</ul>';
                }

                $res.html(
                    '<div class="alert alert-warning" style="margin-bottom:0;">' +
                    '<div style="font-size:14px; font-weight:bold; margin-bottom:5px;">' +
                    '<i class="fa fa-cloud-download"></i> ¡Nueva actualización disponible! (' + r.num_commits + ' cambio(s))' +
                    '</div>' +
                    '<div><strong>Cambios en esta versión:</strong></div>' +
                    htmlCommits +
                    '<div style="font-size:11px; color:#66512c; margin-bottom:10px; background:rgba(255,255,255,0.7); padding:6px; border-radius:3px;">' +
                    '<i class="fa fa-shield"></i> <b>Nota segura:</b> Tu configuración local (conexión de BD y script de inicio) se mantendrá intacta.' +
                    '</div>' +
                    '<button type="button" class="btn btn-success" id="btn_descargar_actualizacion" onclick="ejecutar_actualizacion_git()">' +
                    '<i class="fa fa-download"></i> Descargar e Instalar Ahora' +
                    '</button>' +
                    '</div>'
                );
            } else {
                $res.html(
                    '<div class="alert alert-danger" style="margin-bottom:0;">' +
                    '<i class="fa fa-exclamation-triangle"></i> ' + (r.mensaje || 'Error al verificar actualizaciones.') +
                    '</div>'
                );
            }
        },
        error: function (xhr, status, error) {
            $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Verificar Actualizaciones');
            $res.html(
                '<div class="alert alert-danger" style="margin-bottom:0;">' +
                '<i class="fa fa-exclamation-triangle"></i> No se pudo comunicar con el servidor o GitHub. Verifique su conexión a Internet.<br><small class="text-muted">Error: ' + error + '</small>' +
                '</div>'
            );
        }
    });
}

function ejecutar_actualizacion_git() {
    if (!confirm("¿Está seguro de que desea descargar e instalar las actualizaciones ahora?")) {
        return;
    }

    var $btn = $('#btn_descargar_actualizacion');
    var $res = $('#resultado_actualizacion');

    if ($btn.length) {
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Descargando e instalando...');
    }

    $res.html(
        '<div style="text-align:center; padding:15px; color:#333;">' +
        '<i class="fa fa-spinner fa-spin" style="font-size:24px; color:#337ab7;"></i><br>' +
        '<b style="display:inline-block; margin-top:8px;">Descargando archivos y aplicando actualización...</b><br>' +
        '<span style="font-size:12px; color:#777;">Por favor espere, no cierre esta ventana.</span>' +
        '</div>'
    );

    $.ajax({
        type: "POST",
        url: "controladores/controlador_actualizacion.php?accion=actualizar",
        dataType: "json",
        timeout: 60000,
        success: function (r) {
            if (r.status === 'exito') {
                $res.html(
                    '<div class="alert alert-success" style="margin-bottom:0;">' +
                    '<h4 style="margin-top:0;"><i class="fa fa-check-circle"></i> ¡Actualización Exitosa!</h4>' +
                    '<p style="font-size:13px;">' + r.mensaje + '</p>' +
                    '<p style="font-size:12px; color:#2b542c;">Nueva versión activa: <code>' + r.commit_instalado + '</code> (' + r.detalle_instalado + ')</p>' +
                    '<button type="button" class="btn btn-primary" onclick="window.location.reload()">' +
                    '<i class="fa fa-refresh"></i> Recargar Sistema Ahora' +
                    '</button>' +
                    '</div>'
                );
            } else {
                $res.html(
                    '<div class="alert alert-danger" style="margin-bottom:0;">' +
                    '<i class="fa fa-exclamation-triangle"></i> Error al actualizar: ' + (r.mensaje || 'Ocurrió un error inesperado.') +
                    '</div>'
                );
            }
        },
        error: function (xhr, status, error) {
            $res.html(
                '<div class="alert alert-danger" style="margin-bottom:0;">' +
                '<i class="fa fa-exclamation-triangle"></i> Falló la solicitud de actualización.<br><small class="text-muted">Error: ' + error + '</small>' +
                '</div>'
            );
        }
    });
}
