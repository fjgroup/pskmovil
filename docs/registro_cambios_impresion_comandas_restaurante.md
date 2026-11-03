# Registro Técnico de Cambios: Impresión de Comandas y Base de Datos

**Fecha de intervención:** 03 de Noviembre de 2026  
**Entorno actual:** PC de Desarrollo  
**Objetivo:** Diagnóstico definitivo, reparación binaria de reportes FoxPro, puesta a punto de impresora de comandas y sincronización de base de datos para réplica segura en producción (Restaurante).

---

## 1. Base de Datos (`adminbahiap`)

### 1.1 Verificación de Estructura
* **Origen:** Volcado del restaurante (`bd/02112026 20261102 1118.sql`, ~379 MB).
* **Hallazgo:** El archivo dump contiene volcados de 3 bases de datos:
  1. `adminbahiap` (272 tablas)
  2. `mijar26273_sistema` (6 tablas - tablas satélite `z_*`)
  3. `adminmatriz` (262 tablas)
  *(Contiene además tablas temporales de `information_schema` entre líneas 595.805 y 655.187 que deben excluirse para evitar errores de permisos al importar).*
* **Resultado de comparación:**  
  `adminbahiap` es **100% IDÉNTICA** a la base de datos de desarrollo (272 tablas exactas, 0 diferencias en columnas).

### 1.2 Respaldo y Sustitución
* Se renombró la base de datos previa de desarrollo a **`adminbahiap_old`** (272 tablas resguardadas intactas).
* Se extrajo el bloque limpio de `adminbahiap` (líneas 1 a 595.557) en `bd/adminbahiap_02112026.sql` y se importó en MySQL 5.5 (puerto 3307).
* Verificación de datos:
  * `operti`: 50.240 registros (última fecha: 01/11/2026).
  * `opermv`: 61.438 registros.
  * `kardex`: 60.727 registros.
  * `comandasmv`: 241 registros.
  * `articulo`: 161 artículos.
  * `idconsumos`: 22 mesas físicas.

---

## 2. Configuración de Red e Impresora en Windows

### 2.1 Datos de la Impresora de Comandas
* **Hardware:** Impresora térmica ESC/POS VCEST Modelo VOE-6120 / POS-80.
* **Velocidad de red:** 100 Mbps (el LED de la tarjeta de red de la PC enciende en **amarillo/ámbar**, lo cual es 100% normal por ser enlace 100M y no Gigabit).
* **IP en el Restaurante:** `192.168.1.100` (Gateway `192.168.1.1`).
* **IP adaptada en Desarrollo:** `192.168.0.150` (misma subred que el router Wi-Fi `192.168.0.x`).
* **Puerto de comunicación:** TCP **9100** (puerto estándar RAW para impresoras térmicas ESC/POS).

### 2.2 Impresora en Windows (`cocina`)
* **Nombre de impresora:** `cocina`
* **Controlador:** `Generic / Text Only`
* **Puerto:** Puerto TCP/IP estándar `192.168.0.150` (en producción en el restaurante debe ser `192.168.1.100`).
* **Configuración crítica de Windows:**
  * En `Configuración > Bluetooth y dispositivos > Impresoras y escáneres`:
  * **"Permitir que Windows administre mi impresora predeterminada"** debe estar en **DESACTIVADO**.
  * La impresora **`cocina`** debe estar configurada como **PREDETERMINADA**.
  * Se eliminó de Windows la impresora virtual **`OneNote (Desktop)`** para evitar que intercepte reportes.

---

## 3. Diagnóstico Profundo y Corrección de Reportes FoxPro (`FRX` / `FRT`)

### 3.1 Causa Raíz del Error `El archivo bebidas.dbf / cocina.dbf no existe`
1. Los reportes de Visual FoxPro (`.FRX` y `.FRT`) almacenan en su Registro 29 / Registro 28 (DataEnvironment, `ObjType=25`):
   * El memo `TAG`: contiene el código fuente en texto claro.
   * El memo `TAG2`: contiene el **código binario compilado (p-code/bytecode)**.
2. Al ejecutar un reporte (`REPORT FORM ...`), Visual FoxPro ejecuta directamente el **código binario compilado en `TAG2`**, ignorando cualquier edición de texto si no se recompila el bytecode.
3. En el reporte `cocina.FRT`, un desarrollador anterior había insertado la instrucción:
   `SELECT * FROM BEBIDAS INTO CURSOR BEBIDAS ORDER BY CODIGO READWRITE`
   Cuando Touch Screen imprime una comanda, los datos del pedido ya están en la memoria RAM en el cursor activo (`qgridcta`). Al encontrar `FROM BEBIDAS` (o `FROM COCINA`), FoxPro buscaba un archivo físico de tabla `.dbf` en el disco que no existía, arrojando el error de inmediato en la línea 1.
4. En contraste, en el reporte hermano `barra.frx` y en el reporte base `bebidas.frx`, `PROCEDURE Init` no busca tablas externas; únicamente invoca `InitSet()` y procesa el cursor activo en memoria.

### 3.2 Problema de Impresoras Fantasma (`OneNote`, `PDF24`, `POS-80C`)
* En FoxPro, si un reporte se edita en un equipo donde estaba configurado `OneNote`, `PDF24` o `POS-80C`, los campos binarios `TAG` y `TAG2` del Registro 1 quedan bloqueados a los metadatos de ese controlador específico de Windows.
* **Solución Estándar Microsoft (Clearing Report Printer Environment):**
  * Se limpiaron a longitud 0 los bloques binarios `TAG` y `TAG2` del Registro 1.
  * Se normalizó el campo `EXPR` para definir `DEVICE=cocina` sobre el spooler estándar de Windows (`winspool`).
  * Con esto, el reporte se envía a la impresora `cocina` o a la impresora predeterminada sin abrir ventanas de diálogo ni OneNote.

### 3.3 Reparación Binaria Aplicada
Se reescribieron los bloques binarios de los tres formatos de comanda:
1. **`cocina.frx` / `cocina.FRT`:**
   * Se sustituyó el DataEnvironment dañado por el DataEnvironment limpio y compilado de FoxPro (`InitSet()`, depuración de componentes hijos `codhijo`, sin consultas SQL a archivos DBF).
   * Se configuró el dispositivo hacia `DEVICE=cocina`.
2. **`bebidas.frx` / `bebidas.FRT`:**
   * Se eliminó el amarre a `DEVICE=PDF24` y se configuró hacia `DEVICE=cocina`.
3. **`barra.frx` / `barra.FRT`:**
   * Se eliminó el amarre a `DEVICE=POS-80C` y se configuró hacia `DEVICE=cocina`.

---

## 4. Respuestas a Preguntas Clave del Diagnóstico

### 4.1 ¿Por qué apareció el error `Código de excepción OLE IDispatch 0 de msxml3.dll`?
* `msxml3.dll` es el componente de Windows que gestiona peticiones HTTP y lectura de archivos XML (ej. consulta del dólar en línea, actualización automática de tasas, validación web de licencias PSKloud).
* Al estar trabajando en desarrollo en una red WiFi **sin conexión a internet**, las peticiones HTTP remotas de `msxml3.dll` devuelven el error normal: *"El sistema no puede encontrar el recurso especificado"*. Esto es totalmente inofensivo y desaparece cuando hay internet.

### 4.2 ¿Para qué sirve `cmonitor.exe` y por qué decía `adminadminbahiap`?
* **¿Es obligatorio `cmonitor.exe`?**
  * **Para la Pantalla Táctil de Premium Soft:** **NO es obligatorio**. La pantalla táctil imprime directamente mediante el reporte `cocina.frx`.
  * **Para Comandas Móviles (Mesoneros en celulares):** **SÍ es obligatorio**. Comandas Móviles inserta los pedidos en la tabla MySQL `comandasmv`. `cmonitor.exe` vigila esa tabla cada 3.000 ms (3 segundos), detecta las comandas entrantes y las envía a imprimir físicamente.
* **¿Por qué decía `adminadminbahiap`?**
  * En `configMC.cfg`, `cmonitor.exe` le agrega automáticamente el prefijo `admin` al nombre ingresado (ej: si escribes `bahiap`, el programa conecta a `admin` + `bahiap` = `adminbahiap`).
  * Si en la casilla se escribió `adminbahiap`, el monitor intentó conectar a `adminadminbahiap`, haciendo saltar la ventana de configuración del conector ODBC de MySQL. La base de datos en el monitor debe ser simplemente: `bahiap`.

---

## 5. Despliegue Automático hacia Producción (`fix_premium/`)

Para evitar tener que aplicar estas correcciones manualmente en el restaurante:
1. Se resguardaron los archivos reparados en el repositorio:
   `e:\herd\comandasmoviles\fix_premium\reports\`
   * `cocina.frx` / `cocina.FRT`
   * `bebidas.frx` / `bebidas.frt`
   * `barra.frx` / `barra.FRT`
2. El script maestro **`iniciar_sistema.bat`** ya tiene incorporada la rutina de copia automática:
   ```cmd
   if exist "fix_premium\reports" (
       copy /y "fix_premium\reports\*.*" "C:\PREMIUM\ADMINISTRATIVO9X\bahiap\reports\" >nul 2>&1
       copy /y "fix_premium\reports\*.*" "C:\PREMIUM\ADMINISTRATIVO9X\Matrices\reports\" >nul 2>&1
   )
   ```
   
**Flujo seguro hacia el restaurante:**  
1. Subir cambios aquí: `git add .`, `git commit -m "fix(comandas): repair frx/frt bytecode and printer environment"`, `git push`.
2. En la PC del restaurante: `git pull`.
3. Ejecutar `iniciar_sistema.bat`: Aplicará automáticamente los parches a Premium Soft sin tocar nada a mano.
