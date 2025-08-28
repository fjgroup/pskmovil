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
});
