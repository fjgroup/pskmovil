# Guía de Actualización de Códigos de Mesas (`idconsumos`)

> **Nota:** Este archivo con extensión `.md` (Markdown) es texto plano estándar. Se puede abrir, leer y editar con el **Bloc de notas** (Notepad), VS Code, Notepad++ o cualquier editor de texto en cualquier computadora con Windows.

---

## 1. Análisis de Impacto y Estabilidad

### ¿Afecta la estabilidad del software cambiar los códigos en la base de datos?
**No genera problemas de estabilidad ni rompe el sistema**, siempre y cuando:
1. **No existan mesas abiertas actualmente** al momento de ejecutar el script (sin cuentas pendientes con `tipodoc = 'ESP'`).
2. El código nuevo tenga una longitud máxima de **7 caracteres** (el campo `id_consumo` es `VARCHAR(7)`).

### ¿Por qué?
- En el sistema móvil (`pskmovil`), las mesas se consultan directamente por `idconsumos.id_consumo`. Al abrir o cargar una cuenta, se crea el documento como `E` + `id_consumo` (ejemplo: `EMESA01`).
- En el sistema de escritorio (Premium Soft Restaurante), los consumos activos se almacenan en `operti` con `tipodoc = 'ESP'` y documento `E[codigo]`. Los históricos cerrados se facturan y no dependen de una llave foránea rígida a `idconsumos`.
- Tener códigos estandarizados sin espacios (`JARD01`, `MESA01`, `TASC01`) previene errores de ordenamiento alfabético (`MESA1`, `MESA10`, `MESA11` ... `MESA2`) y discrepancias en búsquedas.

---

## 2. Caso Especial: Mesa Creada por Error (`mesa01` o `MESA01`)
Si en el restaurante se intentó crear la mesa desde la interfaz de Premium y se creó una mesa adicional con código `mesa01` pero quedó incompleta o sin mostrarse en el mapa:
- El script primero **elimina** esa fila residual incompleta.
- Luego **actualiza** la existente `MESA 1` convirtiéndola a `MESA01` con toda la configuración completa (imagen `MESAS4.JPG`, ubicación `SALON`, área `002`, `mostrarenmapa = 1`).

---

## 3. Plan de Mapeo de Códigos

| Ubicación | Código Anterior | Código Nuevo | Descripción | Foto Asociada |
| :--- | :--- | :--- | :--- | :--- |
| **JARDIN** | `JARD 1` | `JARD01` | JARDIN 01 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 2` | `JARD02` | JARDIN 02 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 3` | `JARD03` | JARDIN 03 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 4` | `JARD04` | JARDIN 04 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 5` | `JARD05` | JARDIN 05 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **SALON** | `MESA 1` *(o mesa01 incompleta)* | `MESA01` | MESA 01 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 2` | `MESA02` | MESA 02 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 3` | `MESA03` | MESA 03 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 4` | `MESA04` | MESA 04 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 5` | `MESA05` | MESA 05 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 6` | `MESA06` | MESA 06 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 7` | `MESA07` | MESA 07 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 8` | `MESA08` | MESA 08 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA 9` | `MESA09` | MESA 09 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
| **SALON** | `MESA10` | `MESA10` | MESA 10 | *(sin cambios)* |
| **SALON** | `MESA11` | `MESA11` | MESA 11 | *(sin cambios)* |
| **SALON** | `MESA12` | `MESA12` | MESA 12 | *(sin cambios)* |
| **SALON** | `MESA13` | `MESA13` | MESA 13 | *(sin cambios)* |
| **SALON** | `MESA14` | `MESA14` | MESA 14 | *(sin cambios)* |
| **SALON** | `MESA15` | `MESA15` | MESA 15 | *(sin cambios)* |
| **SALON** | `MESA16` | `MESA16` | MESA 16 | *(sin cambios)* |
| **TASCA** | `TASC01` | `TASC01` | MESA 01 | *(sin cambios)* |

---

## 4. Script SQL para Ejecutar en HeidiSQL / MySQL

El script también está disponible en archivo de texto simple: [actualizar_mesas.txt](file:///e:/herd/pskmovil/docs/actualizar_mesas.txt).

```sql
USE adminbahiap;

-- 1. Eliminar cualquier mesa residual incompleta 'mesa01' o 'MESA01'
DELETE FROM idconsumos WHERE id_consumo IN ('mesa01', 'MESA01');

-- 2. Convertir MESA 1 a MESA01 con configuración estándar
UPDATE idconsumos 
SET id_consumo      = 'MESA01',
    nombre_corto    = 'MESA01',
    descripcion     = 'MESA 01',
    rutafoto        = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG',
    tipo_idconsumo  = '001',
    aplica_touch    = 1,
    ubicacion       = 'SALON',
    codarea         = '002',
    mostrarenmapa   = 1,
    estatus         = 1,
    estatus2        = 1
WHERE id_consumo = 'MESA 1';

-- En caso de que MESA 1 hubiese sido borrada previamente, insertar MESA01:
INSERT IGNORE INTO idconsumos (
    id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad,
    estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo,
    aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa
) VALUES (
    'BAHIAP', '001', 'MESA01', 'MESA 01', 'MESA01', '',
    1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001',
    1, 'SALON', '', '002', 1
);

-- 3. Actualizar el resto de las mesas de SALON (MESA 2 a MESA 9)
UPDATE idconsumos SET id_consumo = 'MESA02', nombre_corto = 'MESA02' WHERE id_consumo = 'MESA 2';
UPDATE idconsumos SET id_consumo = 'MESA03', nombre_corto = 'MESA03' WHERE id_consumo = 'MESA 3';
UPDATE idconsumos SET id_consumo = 'MESA04', nombre_corto = 'MESA04' WHERE id_consumo = 'MESA 4';
UPDATE idconsumos SET id_consumo = 'MESA05', nombre_corto = 'MESA05' WHERE id_consumo = 'MESA 5';
UPDATE idconsumos SET id_consumo = 'MESA06', nombre_corto = 'MESA06' WHERE id_consumo = 'MESA 6';
UPDATE idconsumos SET id_consumo = 'MESA07', nombre_corto = 'MESA07' WHERE id_consumo = 'MESA 7';
UPDATE idconsumos SET id_consumo = 'MESA08', nombre_corto = 'MESA08' WHERE id_consumo = 'MESA 8';
UPDATE idconsumos SET id_consumo = 'MESA09', nombre_corto = 'MESA09' WHERE id_consumo = 'MESA 9';

-- 4. Actualizar las mesas de JARDIN (JARD 1 a JARD 5)
UPDATE idconsumos SET id_consumo = 'JARD01', nombre_corto = 'JARD01' WHERE id_consumo = 'JARD 1';
UPDATE idconsumos SET id_consumo = 'JARD02', nombre_corto = 'JARD02' WHERE id_consumo = 'JARD 2';
UPDATE idconsumos SET id_consumo = 'JARD03', nombre_corto = 'JARD03' WHERE id_consumo = 'JARD 3';
UPDATE idconsumos SET id_consumo = 'JARD04', nombre_corto = 'JARD04' WHERE id_consumo = 'JARD 4';
UPDATE idconsumos SET id_consumo = 'JARD05', nombre_corto = 'JARD05' WHERE id_consumo = 'JARD 5';

-- 5. Consulta de verificación
SELECT id_consumo, descripcion, nombre_corto, ubicacion, codarea, mostrarenmapa, rutafoto 
FROM idconsumos 
ORDER BY ubicacion, id_consumo;
```
