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

## 2. Configuración de Windows en el Restaurante (Paso a Paso)

Todo esto debe realizarse en la PC del Restaurante cuando se monte la ticketera:

### 2.1 Datos de Red de la Ticketera
* **Hardware:** Impresora térmica ESC/POS VCEST Modelo VOE-6120 / POS-80.
* **IP en el Restaurante:** `192.168.1.100` (Gateway `192.168.1.1`).
* **Puerto de comunicación:** TCP **9100** (puerto RAW estándar).

### 2.2 Agregar la Impresora en Windows
1. Ir a `Panel de Control > Dispositivos e Impresoras` (o `Configuración > Bluetooth y dispositivos > Impresoras y escáneres`).
2. Agregar impresora manualmente > **Agregar una impresora por medio de una dirección IP o un nombre de host**.
3. Tipo de dispositivo: **Dispositivo TCP/IP**.
4. Dirección IP: `192.168.1.100` (en Desarrollo usamos `192.168.0.150`). Puerto: `9100`.
5. Controlador: Seleccionar fabricante **Generic** y modelo **Generic / Text Only**.
6. Nombre de impresora: **`cocina`** (en minúsculas).
7. Compartir impresora: Compartir con el nombre de recurso compartido: **`cocina`**.
8. Establecer como **Impresora predeterminada**.
9. Desactivar la opción de Windows: *"Permitir que Windows administre mi impresora predeterminada"*.

### 2.3 Evitar el Papel Blanco y Activar Corte Automático (Crucial)
1. Abrir **Propiedades de la impresora** `cocina`:
2. Pestaña **`Configuración del dispositivo`**:
   * En `Asignación de formulario a bandeja` > `Alimentación continua:`:
   * Cambiar de `A4` a: **`Alimentación continua: Sin salto de página`** *(Cont. Feed - No Break)*.
   * *Propósito:* Evita que Windows expulse una página A4 (29.7 cm) completa al terminar de imprimir.
3. Pestaña **`Comandos de impresora`**:
   * En el campo **`Final del trabajo de impresión`**, escribir:  
     `<1B>d<03><1D>V<01>`  
     *(Avanza 3 líneas de papel para librar la cuchilla y ejecuta el corte automático ESC/POS).*
   * En el campo **`Salto de página`**: Dejarlo **totalmente vacío** (borrar `<0C>` si existiera).
4. Clic en **Aplicar** y **Aceptar**.

---

## 3. Reportes de FoxPro (`cocina.frx`, `bebidas.frx`, `barra.frx`)

### 3.1 Dónde se guardan los cambios de `PAPERLENGTH` y `PAPERWIDTH`
* Estos valores **NO son de Windows**: están codificados directamente en la cabecera binaria del reporte FoxPro (`.frx` y `.frt`).
* Ya fueron configurados para rollo de 80 mm (`PAPERLENGTH=1000` = 10 cm, `PAPERWIDTH=800` = 80 mm).
* En el restaurante **NO es necesario editar el reporte a mano**. Al hacer `git pull` y ejecutar `iniciar_sistema.bat`, los archivos corregidos se copian solos a:
  * `C:\PREMIUM\Administrativo9x\bahiap\reports\`
  * `C:\PREMIUM\Administrativo9x\Matrices\reports\`

### 3.2 Corrección del Error `cocina.dbf no existe`
* Se extrajo el DataEnvironment dañado y se reconstruyó el bytecode binario FoxPro (`TAG2`) con `InitSet()`, depuración de ítems y sin referencias a tablas externas.

### 3.3 Diseño Ancho de los Artículos
* Se colocaron `cantidad` y `alltrim(MvNombre)+chr(10)+ alltrim(NOTASMV)` dentro de la **Banda Detalle** (altura 4.000 unidades).
* Se aplicó el ancho expandido `Width = 29.479` para aprovechar el ancho completo del papel de 80 mm.
* Se eliminaron los encabezados y pies de grupo vacíos que generaban separaciones en blanco.

---

## 4. Despliegue en Producción (Checklist Rápido)

1. En la PC de Desarrollo:
   ```cmd
   git push origin main
   ```
2. En la PC del Restaurante:
   ```cmd
   git pull origin main
   iniciar_sistema.bat
   ```
   *(El script copia automáticamente los parches desde `fix_premium/reports/`).*
3. Configurar la impresora Windows `cocina` con las opciones de la Sección 2.3.
4. Probar desde la pantalla táctil de ventas con F12 (Comandas).
