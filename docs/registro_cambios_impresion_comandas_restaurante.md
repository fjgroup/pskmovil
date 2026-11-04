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

### 3.3 Corrección del Campo Cantidad (Impresión Garantizada)
* Se configuró el campo `cantidad` con:
  * `TOTALTYPE = 0` (Ninguno, imprime directamente el valor real de la comanda).
  * `SUPALWAYS = T` (Imprimir siempre de forma incondicional).
  * `SUPEXPR = 0` (Sin expresiones de supresión que oculten la cantidad).
* Esto garantiza que los números (`1`, `2`, `3`...) se impriman siempre debajo de la columna `Cant.` al igual que en bebidas y barra.

### 3.4 Reducción del Margen Superior y Eliminación de Espacios entre Platos
* **Cabecera compacta:** Se redujo el espacio en blanco superior desplazando todos los elementos de la cabecera hacia arriba en 2.300 unidades. La etiqueta `"COCINA "` ahora comienza inmediatamente en la parte superior (`VPos = 304`), reduciendo la altura del encabezado de 18.500 a 16.200 unidades.
* **Eliminación de saltos entre platos:** Se ajustó la altura de la **Banda Detalle** a **1.771 unidades** (idéntica a `bebidas.frx`). Esto elimina cualquier espacio en blanco innecesario entre platos del pedido.

### 3.5 Comparativa Definitiva: ¿Por qué Bebidas ("Licores") imprimía perfecto y Cocina omitía productos?

Al analizar minuciosamente los reportes originales de fábrica (`C:\Users\cjmfi\Downloads\DescargaExpressAdm_2026_09_03\DescargaExpresAdm\Matrices\reports`):

| Característica | `bebidas.frx` (Licores / Bebidas) | `cocina.frx` (Cocina Original / Modificada) |
| :--- | :--- | :--- |
| **Estructura de Bandas** | **4 Bandas Directas:** Encabezado (1), Detalle (4), Pie (7), Resumen (8). | **6 Bandas con Agrupación:** Encabezado (1), Encabezado de Grupo (3: `CODIGO`), Detalle (4), Pie de Grupo (5), Pie (7), Resumen (8). |
| **Ubicación de Ítems** | **Banda Detalle (4):** Itera fila por fila sobre cada ítem pedido. | **Pie de Grupo (5):** Dependiente del cambio de `CODIGO` y totalización. |
| **Filtrado de Sub-ítems** | `SUPEXPR = empty(codhijo)` en la propia banda detalle (imprime platos/combos directos, oculta guarniciones hijas). | En el `Init` original ejecutaba: `SELECT * FROM BEBIDAS INTO CURSOR BEBIDAS` y `delete FOR !EMPTY(codhijo)`, generando el error de `cocina.dbf no existe` y corrompiendo cursores. |
| **Causa de la Pérdida de Platos** | **Ninguna:** Todo el archivo `.FRT` estaba intacto (9.728 bytes). | **Desincronización binaria:** El archivo `cocina.FRT` en el sistema medía solo 4.158 bytes (truncado), por lo que los punteros de memo del FRX apuntaban fuera de rango a bloques corruptos. Al evaluar el Grupo por `CODIGO`, FoxPro abortaba tras el primer ítem e ignoraba los demás. |

#### Solución Implementada:
Se reconstruyó `cocina.frx` y `cocina.FRT` adoptando la arquitectura probada y robusta de `bebidas.frx`:
1. **Misma estructura de 4 bandas directas:** La impresión corre sobre la **Banda Detalle** (altura 1.771), iterando sobre todos y cada uno de los productos sin saltos prematuros.
2. **Título `COCINA` destacado:** En tamaño 18pt negrita centrado en el inicio del ticket (`VPOS = 304.2`).
3. **Ancho de descripción ampliado:** De 19.375 a **29.479 unidades**, permitiendo que platos largos o nombres de combos quepan holgadamente.
4. **Soporte de Combos e Ítems Directos:** Expresión `SUPEXPR = empty(codhijo)` idéntica a bebidas, permitiendo imprimir todos los combos (`Combo Familiar`, `Combo Jardinero Broaster`, `Combo Jardinero Brasa`, `Combo Duo Muslo`, `1 Pollo Brasa`, etc.).
5. **Integridad Binaria Garantizada:** FRX (9.338 bytes) y FRT (10.176 bytes) con punteros 100% verificados y cero referencias externas a tablas DBF temporales.

---

### 3.6 Consolidación y Totalización Definitiva (Platos, Bebidas y Combos Múltiples)

**Problema Resuelto:**  
Anteriormente, al pedir múltiples platos o combos idénticos (ej. 4 Combos Especial Brasa, 2 Combos Duo o varias maltas/refrescos), el sistema generaba registros unitarios separados e imprimía múltiples líneas de cantidad `1`. Si se agrupaba ingenuamente por código, las guarniciones intermedias rompían el grupo o sumaban cantidades erróneas.

**Arquitectura Técnica Implementada:**
1. **Agrupación Limpia por Código:**  
   * Encabezado de Grupo (Banda 3): `ALLTRIM(codigo)` con altura `0`.
   * Detalle (Banda 4): Altura `0`.
   * Pie de Grupo (Banda 5): Altura `1.771`.
2. **Variable de Reporte Nativa (`OBJTYPE = 18: cPlatoNombre`):**  
   * Almacena y preserva el nombre del plato principal evaluando:  
     `IIF(EMPTY(codhijo), alltrim(MvNombre)+chr(10)+ alltrim(notas), cPlatoNombre)`  
     con reseteo al quiebre de grupo (`RESETTOTAL = 6`). Esto blinda al combo de ser sobreescrito por el nombre de sus guarniciones.
3. **Suma Exclusiva de Platos Principales (`TOTALTYPE = 2`):**  
   * El campo cantidad evalúa: `IIF(EMPTY(codhijo), cantidad, 00000.00)`.  
   * Las guarniciones suman 0 y los combos suman su cantidad exacta (ej. 1 + 1 + 1 = 3).
4. **Homologación de Formatos:**  
   * Se reconstruyó con esta misma arquitectura binaria robusta (`block size: 64`) tanto `cocina.frx/FRT`, `barra.frx/FRT` y `bebidas.frx/FRT`.

**Pruebas Confirmadas en Tiempo Real:**
* **Ticket 00000256 (BARRA):**  
  `2 YUKY PAK` consolidado en una sola línea.
* **Ticket 00000257 (COCINA):**  
  * `4 COMBO ESPECIAL BRASA`  
  * `2 COMBO DUO MUSLO BRASA`  
  * `2 COMBO JARDINERO BRASA`  
  * `2 COMBO FAMILIAR BROASTER`  
  * `2 CERVEZA ZULIA`  
  Consolidados al 100% en líneas únicas con su respectiva cantidad. Cero Error 2005 y cero Error 41.

---
* **Espacio al final:** El único avance de papel es al final del pedido, controlado por `<1B>d<031D>V<01>` (3 líneas antes de la cuchilla).
* Se mantuvo el ancho expandido `Width = 29.479` para que nombres largos de platos quepan sin cortarse.

---

## 4. Despliegue en Producción (Checklist Rápido)

1. En la PC de Desarrollo:
   ```cmd
   git push origin main
   ```
2. En la PC del Restaurante:
   ```cmd
   git pull origin main
   iniciar_sistema_segundo_plano.vbs
   ```
   *(Aparecerá la ventana indicando la IP exacta `http://192.168.1.102:2010` para los mesoneros y los servicios quedarán corriendo en segundo plano).*
3. Configurar la impresora Windows `cocina` con las opciones de la Sección 2.3.
4. Probar desde celulares, tablets y desde la pantalla táctil de ventas con F12 (Comandas).

---

## 5. Impresión Directa desde Móvil/Tablet y Eliminación de `cmonitor.exe`

### 5.1 Diagnóstico de Sobrecarga en Caja
Anteriormente, la PC de caja debía mantener abiertas múltiples ventanas que saturaban la pantalla:
1. `cmonitor.exe` (Monitor de comandas de FoxPro que sondeaba `comandasmv`).
2. Múltiples ventanas de consola de `iniciar_sistema.bat`, `sync_daemon.php` y el servidor web PHP.
3. Ventanas de Cashea, calculadora y Premium Soft POS.

### 5.2 Arquitectura de Impresión Directa Implementada
Se implementó el módulo [`funciones/impresion_comandas_helper.php`](file:///e:/herd/comandasmoviles/funciones/impresion_comandas_helper.php):
* **Cero dependencia de `cmonitor.exe`:** Cuando un mesonero o usuario presiona **"Comanda"** en el móvil o tablet:
  1. `controladores/controlador_pedidos.php` registra la orden en `operti`, `opermv` y `comandasmv` para que Premium Soft POS mantenga su auditoría intacta.
  2. Determina el destino de cada plato consultando `grupos.formato` (`COCINA` vs `BARRA`).
  3. Totaliza y consolida cantidades de platos idénticos (ej. 4 COMBO ESPECIAL BRASA, 2 CERVEZA ZULIA).
  4. Genera los bytes ESC/POS de 80 mm e imprime de forma instantánea mediante **Socket TCP directo al puerto 9100** de la impresora térmica en red (`192.168.1.100` en producción, `127.0.0.1` en desarrollo).
  5. Si el socket estuviese ocupado o fuera de línea, conmuta automáticamente a la cola de Windows `cocina`.
  6. Envía el corte de papel al ras (`\x1bd\x03\x1dV\x01`) tras cada ticket.
  7. Marca `comandasmv.impresion = 2`.

### 5.3 Indicadores Visuales y Colores en "En Tránsito"
En la pestaña **"En Tránsito"** (`controladores/controlador_pedidos.php?accion=ver_pedidos`), cada mesa ahora refleja visualmente su estado de comanda en tiempo real:
* **`[COMANDA ENVIADA]` (Borde y fondo verde suave `#28a745`):**  
  Todos los platos de la mesa ya fueron impresos y enviados a la cocina/barra.
* **`[SIN COMANDA]` (Borde y fondo ámbar suave `#f39c12`):**  
  Mesa guardada en borrador; aún no se ha enviado comanda.
* **`[ÍTEMS PENDIENTES]` (Borde y fondo naranja suave `#e67e22`):**  
  Mesa existente a la que se le agregaron platos nuevos que aún no se han enviado a comanda.
* **Mapa de Mesas (`mostrar_mesas`):** Las mesas ocupadas con comanda enviada muestran el icono de cubiertos `fa-cutlery`.

### 5.4 Limpieza de Pantalla en Caja y Detección Automática de IP para Mesoneros
* Se creó el iniciador silencioso [`iniciar_sistema_segundo_plano.vbs`](file:///e:/herd/comandasmoviles/iniciar_sistema_segundo_plano.vbs). Al hacer doble clic:
  1. Detecta automáticamente mediante WMI la **dirección IPv4 activa** de la PC en la red local (`192.168.1.102` en el restaurante con gateway `192.168.1.1`).
  2. Inicia el servidor web local en el puerto **2010** y el sincronizador Cloud en segundo plano sin mostrar ninguna ventana de consola negra (`style 0`).
  3. Muestra una ventana emergente informativa para guiar al cajero y mesoneros:
     ```text
     =========================================
        MI JARDIN - COMANDAS MOVILES ACTIVAS   
     =========================================
     DIRECCION PARA LOS MESONEROS (CELULARES Y TABLETS):
       >>>   http://192.168.1.102:2010   <<<
     -----------------------------------------
       * IP detectada de esta PC: 192.168.1.102
       * Puerto Web: 2010
       * Impresora Termica: 192.168.1.100 (Puerto 9100)
     -----------------------------------------
     Estado del Sistema:
       [OK] Servidor Web Comandas: ACTIVO
       [OK] Sincronizador Nube Cloud: ACTIVO
       [OK] Pantalla de caja: 100% LIMPIA (Sin ventanas negras)
     Al presionar [Aceptar], ambos servicios continuaran corriendo
     en segundo plano sin ocupar espacio en la pantalla de caja.
     ```
* **Sustitución de `iniciar_sistema.bat`:** El archivo `.bat` fue redirigido internamente para ejecutar de forma silenciosa el iniciador `.vbs`. Así, si alguien hace doble clic por costumbre en el archivo `.bat` o en un acceso directo anterior del escritorio, jamás quedará una ventana negra abierta en la pantalla de caja.
* Se creó [`detener_sistema.bat`](file:///e:/herd/comandasmoviles/detener_sistema.bat) para detener los procesos de forma limpia cuando sea requerido.
* `cmonitor.exe` **ya no necesita ejecutarse**, liberando memoria y espacio en pantalla.

### 5.5 Supresión de Línea de Transacción Innecesaria (`Nro.:`) en Tickets
Para maximizar la legibilidad y evitar datos confusos para el personal de cocina y barra:
* **En Comandas Móviles (PHP):** En [`funciones/impresion_comandas_helper.php`](file:///e:/herd/comandasmoviles/funciones/impresion_comandas_helper.php) se removió la línea `$out .= "Nro.:     " . str_pad($nro_ticket, 8, '0', STR_PAD_LEFT) . "\n";`.
* **En Premium Soft (Visual FoxPro):** En `cocina.frx` y `barra.frx` se marcaron como eliminados (`0x2A`) los registros binarios 19 y 20 (`Nro.:` y `numerodespacho`), logrando que ambos sistemas impriman tickets limpios sin esa línea.


---

## 6. Mejoras de Accesibilidad Móvil y Resolución de Error en Premium Soft

### 6.1 Corrección del Error FoxPro `BARRA.FRT no existe o no es válido`
* **Causa del Error:** Al presionar "Comanda" en el Touch Screen de Premium Soft para productos asignados a la barra (`grupos.formato = 'BARRA'`), FoxPro buscaba `C:\PREMIUM\ADMINISTRATIVO9X\BAHIAP\REPORTS\BARRA.FRT` y `BARRA.QRY`.
* **Solución Aplicada:**
  1. Se generaron las duplas completas `barra.frx` y `barra.FRT` sincronizadas con `cocina.*` tanto en `bahiap\reports` como en `Matrices\reports`.
  2. Se duplicó la consulta FoxPro `cocina.qry` a `barra.qry` en `bahiap\querys` y `Matrices\querys`.
  3. Resultado: Tanto comandas de Cocina como de Barra imprimen perfectamente desde Premium Soft y desde Comandas Móviles.

### 6.2 Rediseño de Encabezado y Campos de Identificación
* **Orden de Campos:** Se unificaron `Cédula / RIF` y `Mesa / Tasca` en una sola fila horizontal tipo flex (`[ Cédula / RIF ] [ Mesa / Tasca ]`) debajo de `Cliente` y `Código Cliente`.
* **Agrupación Predeterminada:** Se ocultó visualmente el checkbox *"Agrupar cantidades por productos"* manteniéndolo marcado por defecto (`checked`), asegurando que todos los ítems idénticos se consoliden automáticamente sin intervención del mesonero.

### 6.3 Capacidad de 6 a 7 Ítems en Pantalla
* Se ajustó `#productos_agregados` a una altura cómoda de `330px` (min 260px, max 380px) con `overflow-y: auto`.
* Se compactó el padding de celdas (`4px 6px`) y el tamaño de fuentes (`13px`), permitiendo que el mesonero visualice de 6 a 8 ítems en pantalla simultáneamente sin desplazamiento vertical excesivo.

### 6.4 Total Flotante Sticky con Referencia en Tiempo Real
* Se incorporó `#barra_total_flotante` fijo en la parte superior (`position: fixed; top: 0; z-index: 99999;`).
* Cuando el usuario se desplaza hacia abajo para explorar productos en grupos o inventario, la barra flotante aparece automáticamente mostrando:
  * **Total Cuenta en Bs** (verde destacado, ej. `62.270,76 Bs`).
  * **Referencia en USD** (etiqueta oscura con borde, ej. `REF 12,50`).
  * Botón **[ 🛒 Ver Pedido ]** que realiza scroll suave de retorno al tope.
* Se implementó un hook global sobre jQuery `.val()` que sincroniza instantáneamente el monto flotante en cuanto se agrega o modifica cualquier producto.

### 6.5 Ícono de Edición de Notas Compacto y Elegante
* Se sustituyó el ícono grande por un lápiz minimalista de 13px (`fa fa-pencil fa-edit`) idéntico al estilo gráfico de Premium Soft.

### 6.6 Visualización de Combos Unificada en una Sola Oración (Azul Claro)
* **Problema Resuelto:** Anteriormente, en modo borrador (sin procesar), al hacer clic en el botón `[ ℹ Incluye ]` se desplegaba tanto la oración resumen como las 5 filas individuales de componentes, duplicando la información y ocupando excesiva altura vertical en móviles. Además, algunos nombres presentaban secuencias unicode sin decodificar (`RAC\u00d3N` en vez de `RACIÓN`).
* **Solución Implementada:**
  1. **Filas de componentes permanentemente suprimidas:** En `css/pedidos.css` se definió `#productos_agregados tr.es_componente { display: none !important; }`, garantizando que las líneas hijas individuales no saturen la pantalla ni antes ni después de procesar.
  2. **Oración resumen en azul claro (`tr.fila_info_combo`):** Al tocar **`[ ℹ Incluye ]`**, se despliega exclusivamente un contenedor estilizado (`background-color: #f0f7fb; border-left: 3px solid #0880C5; color: #2c3e50;`) que resume todos los ítems incluidos en una sola línea elegante separada por viñetas (`2.5 RACION AREPITAS FRITAS • 1 RACIÓN ENSALADA RALLADA • 2 GUASACACA • 1 REFRESCO 1,5 LITROS • 1 POLLO POR UNIDAD`).
  3. **Corrección de acentos UTF-8:** Se aplicó reemplazo y decodificación UTF-8 tanto en la respuesta JSON (`JSON_UNESCAPED_UNICODE`) como en el renderizador de vistas, visualizando `Ó`, `ó`, `á`, `é`, `í`, `ú`, `ñ` con total nitidez.
  4. **Comportamiento idéntico:** Ahora la experiencia visual es 100% coherente y compacta tanto en la orden preliminar como cuando la comanda ya está en tránsito.
