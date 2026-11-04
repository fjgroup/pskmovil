-- ============================================================================
-- SCRIPT SQL: NORMALIZACIÓN DE CÓDIGOS DE MESAS Y GRUPOS DE IMPRESIÓN
-- Mi Jardín Restaurant - Base de Datos: adminbahiap
-- ============================================================================

USE adminbahiap;

-- 1. Respaldo de seguridad previo
CREATE TABLE IF NOT EXISTS `idconsumos_bak_20261104` AS SELECT * FROM idconsumos;

-- 2. Actualizar Grupo 006 (LICORES / Cervezas) para que imprima en BARRA
UPDATE grupos SET formato = 'BARRA' WHERE codigo = '006';
UPDATE grupos SET formato = 'BARRA' WHERE codigo = '005';

-- 3. Normalizar Tasca: t01 (TASCA 01)
UPDATE idconsumos 
SET id_consumo = 't01', nombre_corto = 't01', descripcion = 'TASCA 01', ubicacion = 'TASCA', codarea = '003', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS.JPG', tipo_idconsumo = '001', estatus = 1, estatus2 = 1, aplica_touch = 1, mostrarenmapa = 1
WHERE id_consumo IN ('t01', 'T01', 'TASC01', 'tasc01', 'TASCA 1');

-- 4. Mesa para Llevar: m00 (LLEVAR)
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm00', 'LLEVAR', 'm00', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m00', 'M00'));

-- 5. Salón: m01 a m16 (Actualizar existentes)
UPDATE idconsumos SET id_consumo = 'm01', nombre_corto = 'm01', descripcion = 'MESA 01', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m01', 'M01', 'MESA01', 'mesa01', 'MESA 1', 'mesa 1');
UPDATE idconsumos SET id_consumo = 'm02', nombre_corto = 'm02', descripcion = 'MESA 02', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m02', 'M02', 'MESA02', 'mesa02', 'MESA 2', 'mesa 2');
UPDATE idconsumos SET id_consumo = 'm03', nombre_corto = 'm03', descripcion = 'MESA 03', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m03', 'M03', 'MESA03', 'mesa03', 'MESA 3', 'mesa 3');
UPDATE idconsumos SET id_consumo = 'm04', nombre_corto = 'm04', descripcion = 'MESA 04', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m04', 'M04', 'MESA04', 'mesa04', 'MESA 4', 'mesa 4');
UPDATE idconsumos SET id_consumo = 'm05', nombre_corto = 'm05', descripcion = 'MESA 05', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m05', 'M05', 'MESA05', 'mesa05', 'MESA 5', 'mesa 5');
UPDATE idconsumos SET id_consumo = 'm06', nombre_corto = 'm06', descripcion = 'MESA 06', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m06', 'M06', 'MESA06', 'mesa06', 'MESA 6', 'mesa 6');
UPDATE idconsumos SET id_consumo = 'm07', nombre_corto = 'm07', descripcion = 'MESA 07', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m07', 'M07', 'MESA07', 'mesa07', 'MESA 7', 'mesa 7');
UPDATE idconsumos SET id_consumo = 'm08', nombre_corto = 'm08', descripcion = 'MESA 08', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m08', 'M08', 'MESA08', 'mesa08', 'MESA 8', 'mesa 8');
UPDATE idconsumos SET id_consumo = 'm09', nombre_corto = 'm09', descripcion = 'MESA 09', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m09', 'M09', 'MESA09', 'mesa09', 'MESA 9', 'mesa 9');
UPDATE idconsumos SET id_consumo = 'm10', nombre_corto = 'm10', descripcion = 'MESA 10', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m10', 'M10', 'MESA10', 'mesa10', 'MESA 10');
UPDATE idconsumos SET id_consumo = 'm11', nombre_corto = 'm11', descripcion = 'MESA 11', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m11', 'M11', 'MESA11', 'mesa11', 'MESA 11');
UPDATE idconsumos SET id_consumo = 'm12', nombre_corto = 'm12', descripcion = 'MESA 12', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m12', 'M12', 'MESA12', 'mesa12', 'MESA 12');
UPDATE idconsumos SET id_consumo = 'm13', nombre_corto = 'm13', descripcion = 'MESA 13', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m13', 'M13', 'MESA13', 'mesa13', 'MESA 13');
UPDATE idconsumos SET id_consumo = 'm14', nombre_corto = 'm14', descripcion = 'MESA 14', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m14', 'M14', 'MESA14', 'mesa14', 'MESA 14');
UPDATE idconsumos SET id_consumo = 'm15', nombre_corto = 'm15', descripcion = 'MESA 15', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m15', 'M15', 'MESA15', 'mesa15', 'MESA 15');
UPDATE idconsumos SET id_consumo = 'm16', nombre_corto = 'm16', descripcion = 'MESA 16', ubicacion = 'SALON', codarea = '002', rutafoto = 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', tipo_idconsumo = '001' WHERE id_consumo IN ('m16', 'M16', 'MESA16', 'mesa16', 'MESA 16');

-- 6. Salón: m17 a m22 (Insertar nuevas mesas para completar hasta la 22)
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm17', 'MESA 17', 'm17', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m17', 'M17'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm18', 'MESA 18', 'm18', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m18', 'M18'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm19', 'MESA 19', 'm19', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m19', 'M19'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm20', 'MESA 20', 'm20', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m20', 'M20'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm21', 'MESA 21', 'm21', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m21', 'M21'));
INSERT INTO idconsumos (id_empresa, agencia, id_consumo, descripcion, nombre_corto, capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo, tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac)
SELECT 'BAHIAP', '001', 'm22', 'MESA 22', 'm22', '', 1, 1, '', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG', '', '001', 1, 'SALON', '', '002', 1, 0 WHERE NOT EXISTS (SELECT 1 FROM idconsumos WHERE id_consumo IN ('m22', 'M22'));

-- 7. Jardín: j01 a j05 (Actualizar existentes)
UPDATE idconsumos SET id_consumo = 'j01', nombre_corto = 'j01', descripcion = 'JARDIN 01', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('j01', 'J01', 'JARD01', 'jard01', 'JARDIN 1', 'jard 1');
UPDATE idconsumos SET id_consumo = 'j02', nombre_corto = 'j02', descripcion = 'JARDIN 02', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('j02', 'J02', 'JARD02', 'jard02', 'JARDIN 2', 'jard 2');
UPDATE idconsumos SET id_consumo = 'j03', nombre_corto = 'j03', descripcion = 'JARDIN 03', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('j03', 'J03', 'JARD03', 'jard03', 'JARDIN 3', 'jard 3');
UPDATE idconsumos SET id_consumo = 'j04', nombre_corto = 'j04', descripcion = 'JARDIN 04', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('j04', 'J04', 'JARD04', 'jard04', 'JARDIN 4', 'jard 4');
UPDATE idconsumos SET id_consumo = 'j05', nombre_corto = 'j05', descripcion = 'JARDIN 05', ubicacion = 'JARDIN', codarea = '001', rutafoto = 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG', tipo_idconsumo = '002' WHERE id_consumo IN ('j05', 'J05', 'JARD05', 'jard05', 'JARDIN 5', 'jard 5');

-- 8. Verificación de resultado
SELECT id_consumo, descripcion, ubicacion, codarea FROM idconsumos ORDER BY ubicacion, id_consumo;
