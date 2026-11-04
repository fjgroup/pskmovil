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

### 2.3 Tamaño de Papel, Supresión de Papel Blanco y Corte Automático (Crucial)

> [!IMPORTANT]
> **¿Por qué no aparece "Ticket" en Windows?**  
> El controlador `Generic / Text Only` es universal y sólo lista formatos de oficina (`A4`, `Carta`, `Sobres`...). **NO contiene la opción "Ticket" ni "80mm"**. Esto es completamente normal.  
> **NO seleccionar formatos pequeños como "Sobre B6"**: Si se elige un sobre (ej. 125 mm), FoxPro creerá que se acabó la hoja tras imprimir el encabezado, provocando un corte de papel a la mitad y separando el encabezado de los platos.

1. Abrir **Propiedades de la impresora** `cocina`:
2. Pestaña **`Configuración del dispositivo`** *(Formato a asignación de bandeja)*:
   * **Primera línea** (`Alimentación continua:` con salto): Seleccionar **`No disponible`**.
   * **Segunda línea** (`Hojas sueltas`): Dejar en **`A4`**.
   * **Tercera línea** (`Alimentación continua:` sin salto): Seleccionar **`A4`**.
   > *(Este orden es fundamental: al poner la primera en `No disponible` y la tercera en `A4`, Windows trata el rollo térmico como continuo infinito y **evita que expulse los 20 cm de papel blanco** al terminar los platos).*

3. Pestaña **`Comandos de impresora`**:
   * En el campo **`Empezar trabajo de impresión`**, escribir:  
     `<1B>@`  
     *(Inicializa y limpia la memoria interna de la ticketera antes de cada comanda).*
   * En el campo **`Finalizar trabajo de impresión`**, escribir:  
     `<1B>d<03><1D>V<01>`  
     *(Avanza 3 líneas de papel para librar la cuchilla y ejecuta el corte ESC/POS).*  
     > [!NOTE]  
     > **Comportamiento normal de Windows:** Al presionar **Aplicar**, Windows automáticamente fusionará `<03><1D>` transformándolo visualmente en **`<1B>d<031D>V<01>`**. Esto es totalmente normal y esperado: Windows agrupa bytes hexadecimales contiguos dentro de los mismos corchetes y los interpreta exactamente como los bytes `03` y `1D`.

4. Clic en **Aplicar** y **Aceptar**.

---

## 3. Reportes de FoxPro (`cocina.frx`, `bebidas.frx`, `barra.frx`)

### 3.1 Configuración de Hoja Continua (`PAPERSIZE=9`)
* En FoxPro, al imprimir comandas en rollo continuo, el reporte debe estar configurado como **`PAPERSIZE=9` (A4)**.  
* **¿Por qué A4 en el reporte?** Porque le indica a FoxPro que la hoja tiene 29,7 cm de largo disponible. FoxPro nunca cortará la comanda en dos páginas; colocará el encabezado y todos los platos juntos en un solo ticket continuo. El corte físico lo da el comando ESC/POS al final del ticket.

### 3.2 Corrección del Error `cocina.dbf no existe`
* Se extrajo el DataEnvironment dañado y se reconstruyó el bytecode binario FoxPro (`TAG2`) con `InitSet()`, depuración de ítems y sin referencias a tablas externas.

### 3.3 Corrección del Campo Cantidad (Salía en 0)
* En el archivo binario `cocina.frx` (Registro 9), el campo `cantidad` venía de fábrica configurado con `TOTALTYPE = 2` (Suma acumulada) apuntando a un grupo inexistente.
* Se reescribió binariamente el registro con `TOTALTYPE = 0` (Ninguno), permitiendo que imprima directamente el valor real del cursor (`1`, `2`, `3`...).

### 3.4 Diseño Ancho de los Artículos
* Se colocaron `cantidad` y `alltrim(MvNombre)+chr(10)+ alltrim(NOTASMV)` dentro de la **Banda Detalle** (altura 4.000 unidades).
* Se aplicó el ancho expandido `Width = 29.479` para aprovechar el ancho completo del papel de 80 mm y evitar que nombres largos se corten a la mitad.

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
