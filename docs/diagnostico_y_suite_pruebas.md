# Manual de Pruebas Unitarias, Diagnóstico y Blindaje Git

**Sistema:** Comandas Móviles - Mi Jardín Restaurant  
**Fecha de Implementación:** Octubre 2026  
**Rama Oficial:** `main` (https://github.com/fjgroup/pskmovil.git)

---

## 1. Causa Raíz del Error de Actualización en GitHub y Solución Definitiva

### El Problema Observado
Al actualizar desde una instalación desactualizada mediante el botón de actualización web, la interfaz reportaba:
```text
Falló la solicitud de actualización.
Error: SyntaxError: Unexpected token '<', "
"... is not valid JSON
```
**Sin embargo, al verificar en disco, el sistema sí se había actualizado e incorporado los 8 commits pendientes.**

### Causa Raíz Técnica
1. **Reemplazo en Disco en Caliente:** Al ejecutarse `git reset --hard origin/main`, los archivos del repositorio son sobrescritos en el disco duro mientras PHP se encuentra atendiendo la solicitud HTTP.
2. **Advertencias PHP (PHP Warnings/Notices):** Cuando el script ejecutaba la restauración de configuración (`file_get_contents($conexionFile)`), si el archivo estaba en uso o arrojaba un Notice/Warning con `display_errors = On`, PHP emitía al flujo de salida HTML del tipo `<br /><b>Warning: ...</b>`.
3. **Falla del Parser JSON en jQuery:** El cliente web esperaba una respuesta estricta `dataType: 'json'`. Al llegar una etiqueta `<` como primer byte, el método `JSON.parse()` arrojaba `SyntaxError: Unexpected token '<'`. La solicitud caía en el callback `error:` de AJAX, aunque subyacentemente Git ya había completado el reset e instalado los commits con éxito.

### Solución Implementada (Doble Blindaje PHP + JS)
1. **En Backend ([controladores/controlador_actualizacion.php](file:///e:/herd/comandasmoviles/controladores/controlador_actualizacion.php)):**
   - Se activó buffer de salida `ob_start()`.
   - Se silenciaron advertencias visuales: `ini_set('display_errors', '0'); error_reporting(0);`.
   - Se implementó la función central `responder_json($datos, $httpCode)` que ejecuta `ob_clean()` antes de enviar el encabezado `Content-Type: application/json`, garantizando un payload 100% puro sin etiquetas HTML residuales.
   - Verificación previa con `file_exists()` antes de leer cualquier archivo respaldado.
2. **En Frontend ([js/script.js](file:///e:/herd/comandasmoviles/js/script.js)):**
   - En ambos manejadores de error AJAX (`ejecutar_actualizacion_git` y `ejecutar_actualizacion_git_footer`), se agregó rescate automático mediante expresión regular:
     ```javascript
     var jsonMatch = xhr.responseText.match(/\{[\s\S]*\}/);
     if (jsonMatch) {
         var r = JSON.parse(jsonMatch[0]);
         if (r && r.status === 'exito') {
             // Renderizar éxito real y evitar falsa alarma
         }
     }
     ```
   - Si cualquier salida de consola o aviso se cuela antes del JSON, el frontend rescata el JSON válido y presenta la pantalla de éxito.

---

## 2. Suite de Pruebas Unitarias y Diagnóstico Integral (`tests/test_suite.php`)

Para certificar que todo el ecosistema (bases de datos local y satélite, reglas fiscales, tokens y conectividad cloud) funcione al 100%, se construyó una suite de pruebas automatizadas con 8 baterías de verificación.

### Las 8 Pruebas Implementadas

| # | Prueba | Subsistema | Objetivo / Criterio de Aprobación |
|---|--------|------------|-----------------------------------|
| **1** | `TEST_MYSQL_DUAL` | Base de Datos | Verifica conexión automática mediante fallback: primero puerto `3307` (`root`/`root`, Casa/Desarrollo) y luego puerto `3306` (`root`/`1234`, Restaurante/Producción). Mide latencia de conexión en milisegundos. |
| **2** | `TEST_FISCAL_PREMIUM` | Fiscal SENIAT (`adminbahiap`) | Comprueba la existencia y disponibilidad de lectura de las 5 tablas maestras del sistema fiscal: `operti`, `opermv`, `idconsumos`, `monedas`, `listvend`. Verifica integridad de transacciones abiertas. |
| **3** | `TEST_SATELITE_ESTRUCTURA` | Base Satélite (`mijar26273_sistema`) | Certifica la presencia de las 5 tablas canónicas satélite (`z_config_sistema`, `z_config_bancos`, `z_mesas_activas`, `z_status_pago`, `z_top_productos`). **Certifica la purga total** de tablas/columnas prohibidas (`mesas_caja`, `mesas_registradas`) y que `url_hosting` apunte a `https://mijardinrestaurant.com`. |
| **4** | `TEST_LOGICA_MESAS` | Lógica de Negocio | Evalúa la regla universal dinámica: si un código existe en `adminbahiap.idconsumos` es `MESA REAL`; si no existe (códigos inventados por cajeros: `001`, `099`, `020`, `0101010`) es `PEDIDO EN CAJA`. Valida normalización de ceros (`MESA1` -> `MESA01`). |
| **5** | `TEST_CRIPTOGRAFIA_PINS` | Seguridad & QR | Genera 1,000 PINs con `random_int(100000, 999999)` comprobando estricto rango numérico de 6 dígitos. Verifica que mesas cerradas (`estado = 'CERRADA'`) tengan `token_pin = NULL` para garantizar cero reciclado de tokens entre clientes. |
| **6** | `TEST_TASA_CAMBIO` | Finanzas & SENIAT | Consulta en vivo la tabla `monedas` de `adminbahiap` extrayendo la cotización activa de la divisa (ej. Dólar `$`). Valida que la tasa sea mayor a cero (ej. `887,30 Bs.`). |
| **7** | `TEST_CONECTIVIDAD_CLOUD` | Red & Cloud | Realiza una petición HTTPS real con validación SSL contra `https://mijardinrestaurant.com`. Mide código de respuesta HTTP (200 OK) y latencia de red. |
| **8** | `TEST_GIT_INTEGRIDAD` | Sistema & Git | Comprueba la presencia del motor Git, rama `main`, commit local activo y URL remota oficial en GitHub. |

---

## 3. Formas de Ejecución

### Opción A: Desde Consola CLI (Rápido y Automatizado)
```powershell
php tests/test_suite.php
```
O haciendo doble clic en el archivo directo:
[ejecutar_tests.bat](file:///e:/herd/comandasmoviles/ejecutar_tests.bat)

### Opción B: Desde el Navegador Web (Dashboard Visual)
Acceda desde el navegador a:
- Local: `http://localhost:2010/tests/test_suite.php`
- En red WiFi: `http://192.168.0.102:2010/tests/test_suite.php`

El dashboard incluye:
- Tarjetas de salud visual (Total Pruebas, Aprobadas, Fallidas, Índice de Salud %).
- Desglose con badges de color (`PASS`, `WARN`, `FAIL`).
- Tiempos de ejecución exactos por cada prueba.
- Botón **"Re-ejecutar Tests"** para auditoría en tiempo real.

### Opción C: Formato JSON (Para Monitoreo y APIs)
```http
GET http://localhost:2010/tests/test_suite.php?format=json
```
Retorna el reporte completo estructurado en JSON estándar.
