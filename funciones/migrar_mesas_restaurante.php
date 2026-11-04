<?php
/**
 * Script de Migración y Normalización de Mesas y Grupos
 * Mi Jardín Restaurant - Premium Soft
 *
 * Configura:
 * - Tasca: t01 (TASCA 01)
 * - Llevar: m00 (LLEVAR)
 * - Salón: m01 .. m22 (MESA 01 .. MESA 22)
 * - Jardín: j01 .. j05 (JARDIN 01 .. JARDIN 05)
 * - Grupos: grupo 006 (LICORES / Cervezas) asignado a formato 'BARRA'
 */

require_once __DIR__ . '/config_helper.php';

$cfg = obtener_config_sistema();
$db = @new mysqli('127.0.0.1', $cfg['user'] ?? 'root', $cfg['pw'] ?? 'root', $cfg['bd_pos'] ?? 'adminbahiap', (int)($cfg['port'] ?? 3306));

if ($db->connect_errno) {
    echo "\n[ERROR] No se pudo conectar a la base de datos: " . $db->connect_error . "\n";
    exit(1);
}

echo "========================================================\n";
echo "  MIGRACION DE MESAS Y GRUPOS - MI JARDIN RESTAURANT     \n";
echo "========================================================\n\n";

// 1. Respaldo previo
$fecha_bak = date('Ymd_His');
$db->query("CREATE TABLE IF NOT EXISTS `idconsumos_bak_{$fecha_bak}` AS SELECT * FROM idconsumos");
echo "[1/3] Respaldo creado en: idconsumos_bak_{$fecha_bak}\n\n";

// 2. Normalización de Mesas
echo "[2/3] Actualizando códigos de mesas en idconsumos...\n";

function migrar_mesa($db, $cod, $desc, $ubicacion, $codarea, $tipo, $foto) {
    $cod_ant = '';
    if (preg_match('/^m(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        if ($num === 0) {
            $cod_ant = "id_consumo IN ('m00', 'M00', 'LLEVAR', '001')";
        } else {
            $num_str = sprintf('%02d', $num);
            $cod_ant = "id_consumo IN ('m{$num_str}', 'M{$num_str}', 'MESA{$num_str}', 'mesa{$num_str}', 'MESA {$num}', 'mesa {$num}', 'MESA{$num}')";
        }
    } elseif (preg_match('/^t(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        $num_str = sprintf('%02d', $num);
        $cod_ant = "id_consumo IN ('t{$num_str}', 'T{$num_str}', 'TASC{$num_str}', 'tasc{$num_str}', 'TASCA {$num}')";
    } elseif (preg_match('/^j(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        $num_str = sprintf('%02d', $num);
        $cod_ant = "id_consumo IN ('j{$num_str}', 'J{$num_str}', 'JARD{$num_str}', 'jard{$num_str}', 'JARDIN {$num}', 'JARD {$num}')";
    }

    $existe = false;
    if ($cod_ant) {
        $res = $db->query("SELECT id_consumo FROM idconsumos WHERE {$cod_ant} LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $id_orig = $row['id_consumo'];
            $existe = true;
            $db->query("UPDATE idconsumos SET 
                id_consumo = '{$cod}',
                nombre_corto = '{$cod}',
                descripcion = '{$desc}',
                ubicacion = '{$ubicacion}',
                codarea = '{$codarea}',
                tipo_idconsumo = '{$tipo}',
                rutafoto = '{$foto}',
                estatus = 1,
                estatus2 = 1,
                aplica_touch = 1,
                mostrarenmapa = 1
                WHERE id_consumo = '{$id_orig}'
            ");
            echo "  * Actualizada: {$id_orig} --> {$cod} ({$desc})\n";
        }
    }

    if (!$existe) {
        $db->query("INSERT INTO idconsumos (
            id_empresa, agencia, id_consumo, descripcion, nombre_corto,
            capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo,
            tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac
        ) VALUES (
            'BAHIAP', '001', '{$cod}', '{$desc}', '{$cod}',
            '', 1, 1, '', '{$foto}', '',
            '{$tipo}', 1, '{$ubicacion}', '', '{$codarea}', 1, 0
        )");
        echo "  + Creada nueva: {$cod} ({$desc})\n";
    }
}

// Tasca
migrar_mesa($db, 't01', 'TASCA 01', 'TASCA', '003', '001', 'C:\\PREMIUM\\IMAGENES\\MESAS.JPG');

// Llevar
migrar_mesa($db, 'm00', 'LLEVAR', 'SALON', '002', '001', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG');

// Salón (m01 a m22)
for ($i = 1; $i <= 22; $i++) {
    $num_str = sprintf('%02d', $i);
    migrar_mesa($db, "m{$num_str}", "MESA {$num_str}", 'SALON', '002', '001', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG');
}

// Jardín (j01 a j05)
for ($j = 1; $j <= 5; $j++) {
    $num_str = sprintf('%02d', $j);
    migrar_mesa($db, "j{$num_str}", "JARDIN {$num_str}", 'JARDIN', '001', '002', 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG');
}

// 3. Grupos
echo "\n[3/3] Configurando destino de impresión de grupos...\n";
$db->query("UPDATE grupos SET formato = 'BARRA' WHERE codigo = '006'");
$db->query("UPDATE grupos SET formato = 'BARRA' WHERE codigo = '005'");
echo "  [OK] Grupo 005 (BEBIDAS) -> Formato BARRA\n";
echo "  [OK] Grupo 006 (LICORES / Cervezas) -> Formato BARRA\n";

echo "\n========================================================\n";
echo "  PROCESO COMPLETADO EXITOSAMENTE                       \n";
echo "========================================================\n";
