-- ============================================================================
-- ACTUALIZAR CODIGOS DE CUENTAS / MESAS A MAYUSCULAS CORTAS (M01, J01, T01)
-- Mi Jardín Restaurant - Base de Datos: adminbahiap
-- 
-- En vez de códigos largos (MESA01, JARD01) o minúsculas:
-- Códigos estándar de Premium Soft en MAYÚSCULAS:
--   - Tasca:  T01
--   - Llevar: M00
--   - Salón:  M01 a M22
--   - Jardín: J01 a J05
-- ============================================================================

USE adminbahiap;

-- 1. Respaldo de seguridad previo
CREATE TABLE IF NOT EXISTS `idconsumos_bak_original` AS SELECT * FROM idconsumos;

-- 2. Tasca: T01
UPDATE idconsumos 
SET id_consumo = 'T01', nombre_corto = 'T01', descripcion = 'TASCA 01', ubicacion = 'TASCA', codarea = '003', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS.JPG', tipo_idconsumo = '001', estatus = 1, estatus2 = 1, aplica_touch = 1, mostrarenmapa = 1
WHERE id_consumo IN ('TASC01', 'tasc01', 'T01', 't01', 'TASCA 1', 'TASCA 01') OR descripcion = 'TASCA 01';

-- 3. Llevar: M00
UPDATE idconsumos 
SET id_consumo = 'M00', nombre_corto = 'M00', descripcion = 'LLEVAR', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001', estatus = 1, estatus2 = 1, aplica_touch = 1, mostrarenmapa = 1
WHERE id_consumo IN ('M00', 'm00', 'LLEVAR', 'llevar') OR descripcion = 'LLEVAR';

INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M00', 'LLEVAR', 'M00', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M00', 'm00'));

-- 4. Salón: M01 a M16 (Actualizar existentes a mayúsculas M01..M16)
UPDATE idconsumos SET id_consumo = 'M01', nombre_corto = 'M01', descripcion = 'MESA 01', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA01', 'mesa01', 'M01', 'm01', 'MESA 1', 'mesa 1', 'MESA 01') OR descripcion = 'MESA 01';
UPDATE idconsumos SET id_consumo = 'M02', nombre_corto = 'M02', descripcion = 'MESA 02', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA02', 'mesa02', 'M02', 'm02', 'MESA 2', 'mesa 2', 'MESA 02') OR descripcion = 'MESA 02';
UPDATE idconsumos SET id_consumo = 'M03', nombre_corto = 'M03', descripcion = 'MESA 03', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA03', 'mesa03', 'M03', 'm03', 'MESA 3', 'mesa 3', 'MESA 03') OR descripcion = 'MESA 03';
UPDATE idconsumos SET id_consumo = 'M04', nombre_corto = 'M04', descripcion = 'MESA 04', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA04', 'mesa04', 'M04', 'm04', 'MESA 4', 'mesa 4', 'MESA 04') OR descripcion = 'MESA 04';
UPDATE idconsumos SET id_consumo = 'M05', nombre_corto = 'M05', descripcion = 'MESA 05', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA05', 'mesa05', 'M05', 'm05', 'MESA 5', 'mesa 5', 'MESA 05') OR descripcion = 'MESA 05';
UPDATE idconsumos SET id_consumo = 'M06', nombre_corto = 'M06', descripcion = 'MESA 06', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA06', 'mesa06', 'M06', 'm06', 'MESA 6', 'mesa 6', 'MESA 06') OR descripcion = 'MESA 06';
UPDATE idconsumos SET id_consumo = 'M07', nombre_corto = 'M07', descripcion = 'MESA 07', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA07', 'mesa07', 'M07', 'm07', 'MESA 7', 'mesa 7', 'MESA 07') OR descripcion = 'MESA 07';
UPDATE idconsumos SET id_consumo = 'M08', nombre_corto = 'M08', descripcion = 'MESA 08', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA08', 'mesa08', 'M08', 'm08', 'MESA 8', 'mesa 8', 'MESA 08') OR descripcion = 'MESA 08';
UPDATE idconsumos SET id_consumo = 'M09', nombre_corto = 'M09', descripcion = 'MESA 09', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA09', 'mesa09', 'M09', 'm09', 'MESA 9', 'mesa 9', 'MESA 09') OR descripcion = 'MESA 09';
UPDATE idconsumos SET id_consumo = 'M10', nombre_corto = 'M10', descripcion = 'MESA 10', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA10', 'mesa10', 'M10', 'm10', 'MESA 10') OR descripcion = 'MESA 10';
UPDATE idconsumos SET id_consumo = 'M11', nombre_corto = 'M11', descripcion = 'MESA 11', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA11', 'mesa11', 'M11', 'm11', 'MESA 11') OR descripcion = 'MESA 11';
UPDATE idconsumos SET id_consumo = 'M12', nombre_corto = 'M12', descripcion = 'MESA 12', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA12', 'mesa12', 'M12', 'm12', 'MESA 12') OR descripcion = 'MESA 12';
UPDATE idconsumos SET id_consumo = 'M13', nombre_corto = 'M13', descripcion = 'MESA 13', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA13', 'mesa13', 'M13', 'm13', 'MESA 13') OR descripcion = 'MESA 13';
UPDATE idconsumos SET id_consumo = 'M14', nombre_corto = 'M14', descripcion = 'MESA 14', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA14', 'mesa14', 'M14', 'm14', 'MESA 14') OR descripcion = 'MESA 14';
UPDATE idconsumos SET id_consumo = 'M15', nombre_corto = 'M15', descripcion = 'MESA 15', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA15', 'mesa15', 'M15', 'm15', 'MESA 15') OR descripcion = 'MESA 15';
UPDATE idconsumos SET id_consumo = 'M16', nombre_corto = 'M16', descripcion = 'MESA 16', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('MESA16', 'mesa16', 'M16', 'm16', 'MESA 16') OR descripcion = 'MESA 16';

-- 5. Salón: M17 a M22 (Insertar si no existen)
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M17', 'MESA 17', 'M17', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M17', 'm17'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M18', 'MESA 18', 'M18', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M18', 'm18'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M19', 'MESA 19', 'M19', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M19', 'm19'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M20', 'MESA 20', 'M20', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M20', 'm20'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M21', 'MESA 21', 'M21', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M21', 'm21'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'M22', 'MESA 22', 'M22', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('M22', 'm22'));

-- 6. Jardín: J01 a J05 (Actualizar existentes a mayúsculas J01..J05)
UPDATE idconsumos SET id_consumo = 'J01', nombre_corto = 'J01', descripcion = 'JARDIN 01', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('JARD01', 'jard01', 'J01', 'j01', 'JARD 1', 'jard 1', 'JARDIN 1') OR descripcion = 'JARDIN 01';
UPDATE idconsumos SET id_consumo = 'J02', nombre_corto = 'J02', descripcion = 'JARDIN 02', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('JARD02', 'jard02', 'J02', 'j02', 'JARD 2', 'jard 2', 'JARDIN 2') OR descripcion = 'JARDIN 02';
UPDATE idconsumos SET id_consumo = 'J03', nombre_corto = 'J03', descripcion = 'JARDIN 03', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('JARD03', 'jard03', 'J03', 'j03', 'JARD 3', 'jard 3', 'JARDIN 3') OR descripcion = 'JARDIN 03';
UPDATE idconsumos SET id_consumo = 'J04', nombre_corto = 'J04', descripcion = 'JARDIN 04', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('JARD04', 'jard04', 'J04', 'j04', 'JARD 4', 'jard 4', 'JARDIN 4') OR descripcion = 'JARDIN 04';
UPDATE idconsumos SET id_consumo = 'J05', nombre_corto = 'J05', descripcion = 'JARDIN 05', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('JARD05', 'jard05', 'J05', 'j05', 'JARD 5', 'jard 5', 'JARDIN 5') OR descripcion = 'JARDIN 05';

-- 7. Actualizar Grupos 005 (Bebidas) y 006 (Licores / Cervezas) a formato BARRA
UPDATE grupos SET formato = 'BARRA' WHERE codigo = '005';
UPDATE grupos SET formato = 'BARRA' WHERE codigo = '006';

-- 8. Verificación de resultado
SELECT id_consumo, descripcion, ubicacion, codarea FROM idconsumos ORDER BY ubicacion, id_consumo;
