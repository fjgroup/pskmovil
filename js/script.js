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

});
