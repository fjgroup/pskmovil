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

## 2. Tratamiento Inteligente del Caso `MESA 1` vs `mesa01`

Debido a que en el restaurante hicieron pruebas manuales creando o eliminando la mesa:
1. **Si existen las dos (`MESA 1` y `mesa01`):** Se elimina la que crearon de prueba (`mesa01`) y se actualiza `MESA 1` convirtiéndola a `MESA01` con todos sus datos completos (foto, ubicación, área, touch, mapa).
2. **Si solo existe `MESA 1`:** Se actualiza completamente a `MESA01`.
3. **Si solo existe `mesa01`:** Se actualizan todos sus campos (no solo el código) asignándole imagen `C:\PREMIUM\IMAGENES\MESAS4.JPG`, ubicación `SALON`, área `002`, `mostrarenmapa = 1`, etc.
4. **Si no existiera ninguna:** Se inserta `MESA01` limpia con toda su configuración requerida.

---

## 3. Plan de Mapeo de Códigos

| Ubicación | Código Anterior | Código Nuevo | Descripción | Foto Asociada |
| :--- | :--- | :--- | :--- | :--- |
| **JARDIN** | `JARD 1` | `JARD01` | JARDIN 01 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 2` | `JARD02` | JARDIN 02 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 3` | `JARD03` | JARDIN 03 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 4` | `JARD04` | JARDIN 04 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **JARDIN** | `JARD 5` | `JARD05` | JARDIN 05 | `C:\PREMIUM\IMAGENES\JARDIN.JPG` |
| **SALON** | `MESA 1` *(o mesa01)* | `MESA01` | MESA 01 | `C:\PREMIUM\IMAGENES\MESAS4.JPG` |
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

## 4. Script SQL Inteligente para Ejecutar

El archivo [actualizar_mesas.txt](file:///e:/herd/pskmovil/docs/actualizar_mesas.txt) contiene este script listo para copiar y pegar en HeidiSQL:

```sql
USE adminbahiap;

DELIMITER $$

DROP PROCEDURE IF EXISTS NormalizarMesasBahiap$$

CREATE PROCEDURE NormalizarMesasBahiap()
BEGIN
    DECLARE count_mesa_espacio INT DEFAULT 0;
    DECLARE count_mesa_cero INT DEFAULT 0;

    -- Validar si existen 'MESA 1' (con espacio) y 'MESA01' (o 'mesa01')
    SELECT COUNT(*) INTO count_mesa_espacio FROM idconsumos WHERE LOWER(TRIM(id_consumo)) = 'mesa 1';
    SELECT COUNT(*) INTO count_mesa_cero FROM idconsumos WHERE LOWER(TRIM(id_consumo)) = 'mesa01';

    -- CASO 1: Si existen AMBAS -> se borra la mesa01 incompleta y se actualiza mesa 1 completa a MESA01
    IF count_mesa_espacio > 0 AND count_mesa_cero > 0 THEN
        DELETE FROM idconsumos WHERE LOWER(TRIM(id_consumo)) = 'mesa01';
        UPDATE idconsumos 
        SET id_consumo      = 'MESA01',
            nombre_corto    = 'MESA01',
            descripcion     = 'MESA 01',
            capacidad       = '',
            estatus         = 1,
            estatus2        = 1,
            rutafoto        = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG',
            tipo_idconsumo  = '001',
            aplica_touch    = 1,
            ubicacion       = 'SALON',
            codarea         = '002',
            mostrarenmapa   = 1
        WHERE LOWER(TRIM(id_consumo)) = 'mesa 1';

    -- CASO 2: Si solo existe 'mesa 1' -> se actualizan todos los campos a MESA01
    ELSEIF count_mesa_espacio > 0 AND count_mesa_cero = 0 THEN
        UPDATE idconsumos 
        SET id_consumo      = 'MESA01',
            nombre_corto    = 'MESA01',
            descripcion     = 'MESA 01',
            capacidad       = '',
            estatus         = 1,
            estatus2        = 1,
            rutafoto        = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG',
            tipo_idconsumo  = '001',
            aplica_touch    = 1,
            ubicacion       = 'SALON',
            codarea         = '002',
            mostrarenmapa   = 1
        WHERE LOWER(TRIM(id_consumo)) = 'mesa 1';

    -- CASO 3: Si solo existe 'mesa01' -> se le colocan todos los campos e imagen correcta
    ELSEIF count_mesa_espacio = 0 AND count_mesa_cero > 0 THEN
        UPDATE idconsumos 
        SET id_consumo      = 'MESA01',
            nombre_corto    = 'MESA01',
            descripcion     = 'MESA 01',
            capacidad       = '',
            estatus         = 1,
            estatus2        = 1,
            rutafoto        = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG',
            tipo_idconsumo  = '001',
            aplica_touch    = 1,
            ubicacion       = 'SALON',
            codarea         = '002',
            mostrarenmapa   = 1
        WHERE LOWER(TRIM(id_consumo)) = 'mesa01';

    -- CASO 4: Si no existe ninguna -> se crea MESA01 completa desde cero
    ELSE
        INSERT INTO idconsumos (
            id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad,
            estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo,
            aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa
        ) VALUES (
            'BAHIAP', '001', 'MESA01', 'MESA 01', 'MESA01', '',
            1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001',
            1, 'SALON', '', '002', 1
        );
    END IF;

    -- Actualizar el resto de las mesas de SALON (MESA 2 a MESA 9) actualizando todos los datos esenciales
    UPDATE idconsumos SET id_consumo = 'MESA02', nombre_corto = 'MESA02', descripcion = 'MESA 02', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 2';
    UPDATE idconsumos SET id_consumo = 'MESA03', nombre_corto = 'MESA03', descripcion = 'MESA 03', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 3';
    UPDATE idconsumos SET id_consumo = 'MESA04', nombre_corto = 'MESA04', descripcion = 'MESA 04', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 4';
    UPDATE idconsumos SET id_consumo = 'MESA05', nombre_corto = 'MESA05', descripcion = 'MESA 05', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 5';
    UPDATE idconsumos SET id_consumo = 'MESA06', nombre_corto = 'MESA06', descripcion = 'MESA 06', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 6';
    UPDATE idconsumos SET id_consumo = 'MESA07', nombre_corto = 'MESA07', descripcion = 'MESA 07', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 7';
    UPDATE idconsumos SET id_consumo = 'MESA08', nombre_corto = 'MESA08', descripcion = 'MESA 08', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 8';
    UPDATE idconsumos SET id_consumo = 'MESA09', nombre_corto = 'MESA09', descripcion = 'MESA 09', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', ubicacion = 'SALON', codarea = '002', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'mesa 9';

    -- Actualizar mesas de JARDIN (JARD 1 a JARD 5)
    UPDATE idconsumos SET id_consumo = 'JARD01', nombre_corto = 'JARD01', descripcion = 'JARDIN 01', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', ubicacion = 'JARDIN', codarea = '001', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'jard 1';
    UPDATE idconsumos SET id_consumo = 'JARD02', nombre_corto = 'JARD02', descripcion = 'JARDIN 02', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', ubicacion = 'JARDIN', codarea = '001', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'jard 2';
    UPDATE idconsumos SET id_consumo = 'JARD03', nombre_corto = 'JARD03', descripcion = 'JARDIN 03', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', ubicacion = 'JARDIN', codarea = '001', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'jard 3';
    UPDATE idconsumos SET id_consumo = 'JARD04', nombre_corto = 'JARD04', descripcion = 'JARDIN 04', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', ubicacion = 'JARDIN', codarea = '001', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'jard 4';
    UPDATE idconsumos SET id_consumo = 'JARD05', nombre_corto = 'JARD05', descripcion = 'JARDIN 05', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', ubicacion = 'JARDIN', codarea = '001', mostrarenmapa = 1 WHERE LOWER(TRIM(id_consumo)) = 'jard 5';

END$$

DELIMITER ;

CALL NormalizarMesasBahiap();

DROP PROCEDURE IF EXISTS NormalizarMesasBahiap;

SELECT id_consumo, descripcion, nombre_corto, ubicacion, codarea, mostrarenmapa, rutafoto 
FROM idconsumos 
ORDER BY ubicacion, id_consumo;
```
