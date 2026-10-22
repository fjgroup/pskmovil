// Variable para almacenar el ID del temporizador
let verificaTimer;

// Función para reiniciar el temporizador
function conexVerifyTimer() {
   // clearTimeout(verificaTimer);
    
   // verificaTimer = setTimeout(probar_conexion_activa, 60000); // 60mil = 1 minuto. Cambia el tiempo en milisegundos que deseas esperar antes de cerrar sesión
    verificaTimer=setInterval(probar_conexion_activa, 60000); 
    
}

function probar_conexion_activa() {
    $.ajax({
        type: "POST",
        url: "funciones/probar_conexion_activa.php",
        data: "",
        beforeSend: function () {
            mostrar_cargando();
        },
        success: function (r) {
            ocultar_cargando();
            var resp = (r || '').trim();
            if (resp == "Error") {
                window.location.href = 'conexout_page.php';
                console.log(resp);
            }else{
                console.log(resp);
            }
        },
        error: function (error) {
            // Redirigir a la página de cierre de sesión, por ejemplo
            window.location.href = 'conexout_page.php';
            console.log(error.responseText);
        }
    });
}

// Verificar Conexion
conexVerifyTimer();

