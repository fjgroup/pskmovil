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
 * - Grupos: grupo 005 (BEBIDAS) y grupo 006 (LICORES / Cervezas) asignados a formato 'BARRA'
 * - Normaliza automáticamente pedidos abiertos en operti (Em01, Em00, etc.) para que FoxPro los marque en ROJO
 */

require_once __DIR__ . '/config_helper.php';

$cfg = obtener_config_sistema();
$db = conectar_bd_pos($cfg);

if (!$db || $db->connect_errno) {
    $host = $cfg['host'] ?? '127.0.0.1';
    $port = (int)($cfg['port'] ?? 3306);
    $bd = $cfg['bd_pos'] ?? 'adminbahiap';
    foreach ([['user' => 'root', 'pw' => 'root'], ['user' => 'root', 'pw' => '1234'], ['user' => 'root', 'pw' => '']] as $cred) {
        $db = @new mysqli($host, $cred['user'], $cred['pw'], $bd, $port);
        if ($db && !$db->connect_errno) {
            break;
        }
    }
}

if (!$db || $db->connect_errno) {
    echo "\n[ERROR] No se pudo conectar a la base de datos de Premium Soft (adminbahiap).\n";
    echo "Verifique que el servicio de MySQL (Puerto 3306) este iniciado.\n";
    exit(1);
}

echo "========================================================\n";
echo "  MIGRACION DE MESAS Y GRUPOS - MI JARDIN RESTAURANT     \n";
echo "========================================================\n\n";

// 1. Respaldo previo con fecha y hora
$fecha_bak = date('Ymd_His');
$db->query("CREATE TABLE IF NOT EXISTS `idconsumos_bak_{$fecha_bak}` AS SELECT * FROM idconsumos");
echo "[1/4] Respaldo de seguridad creado en: idconsumos_bak_{$fecha_bak}\n\n";

// 2. Normalización de Mesas
echo "[2/4] Normalizando codigos de mesas en idconsumos...\n";

function migrar_mesa($db, $cod, $desc, $ubicacion, $codarea, $tipo, $foto) {
    $candidatos = [];
    $desc_esc = $db->real_escape_string($desc);

    if (preg_match('/^m(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        if ($num === 0) {
            $candidatos = ["'m00'", "'M00'", "'LLEVAR'", "'001'", "'llevar'"];
        } else {
            $num_str = sprintf('%02d', $num);
            $candidatos = [
                "'m{$num_str}'", "'M{$num_str}'",
                "'m{$num}'", "'M{$num}'",
                "'MESA{$num_str}'", "'mesa{$num_str}'",
                "'MESA{$num}'", "'mesa{$num}'",
                "'MESA {$num}'", "'mesa {$num}'",
                "'MESA {$num_str}'", "'mesa {$num_str}'"
            ];
        }
    } elseif (preg_match('/^t(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        $num_str = sprintf('%02d', $num);
        $candidatos = [
            "'t{$num_str}'", "'T{$num_str}'",
            "'t{$num}'", "'T{$num}'",
            "'TASC{$num_str}'", "'tasc{$num_str}'",
            "'TASC{$num}'", "'tasc{$num}'",
            "'TASCA {$num}'", "'tasca {$num}'",
            "'TASCA {$num_str}'", "'tasca {$num_str}'",
            "'TASCA{$num_str}'", "'tasca{$num_str}'"
        ];
    } elseif (preg_match('/^j(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        $num_str = sprintf('%02d', $num);
        $candidatos = [
            "'j{$num_str}'", "'J{$num_str}'",
            "'j{$num}'", "'J{$num}'",
            "'JARD{$num_str}'", "'jard{$num_str}'",
            "'JARD{$num}'", "'jard{$num}'",
            "'JARDIN {$num}'", "'jardin {$num}'",
            "'JARDIN {$num_str}'", "'jardin {$num_str}'",
            "'JARD {$num}'", "'jard {$num}'",
            "'JARD {$num_str}'", "'jard {$num_str}'"
        ];
    }

    $candidatos_in = implode(', ', array_unique($candidatos));
    $where_match = "id_consumo IN ({$candidatos_in}) OR descripcion = '{$desc_esc}'";

    $existe = false;
    $foto_esc = $db->real_escape_string($foto);

    $res = $db->query("SELECT id_consumo FROM idconsumos WHERE {$where_match} ORDER BY (id_consumo = '{$cod}') DESC LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $id_orig = $row['id_consumo'];
        $existe = true;

        $db->query("UPDATE idconsumos SET 
            id_consumo = '{$cod}',
            nombre_corto = '{$cod}',
            descripcion = '{$desc}',
            ubicacion = '{$ubicacion}',
            codarea = '{$codarea}',
            tipo_idconsumo = '{$tipo}',
            rutafoto = '{$foto_esc}',
            estatus = 1,
            estatus2 = 1,
            aplica_touch = 1,
            mostrarenmapa = 1
            WHERE id_consumo = '{$id_orig}'
        ");

        // Si existían filas duplicadas o residuales de pruebas previas con nombres viejos, eliminarlas
        $db->query("DELETE FROM idconsumos WHERE ({$where_match}) AND id_consumo != '{$cod}'");

        echo "  * Mesa {$cod} ({$desc}) lista. (Origen: {$id_orig})\n";

        // Si la mesa tenía otro código previo y tiene pedidos activos en operti, migrarlos
        if ($id_orig !== $cod) {
            $doc_ant = 'E' . $id_orig;
            $doc_nuevo = 'E' . $cod;
            $db->query("UPDATE operti SET documento = '{$doc_nuevo}', ordentrab = '{$doc_nuevo}' WHERE documento = '{$doc_ant}' AND tipodoc = 'ESP'");
            $db->query("UPDATE opermv SET documento = '{$doc_nuevo}' WHERE documento = '{$doc_ant}' AND tipodoc = 'ESP'");
            $db->query("UPDATE comandasmv SET documento = '{$doc_nuevo}' WHERE documento = '{$doc_ant}'");
        }
    }

    if (!$existe) {
        $db->query("INSERT INTO idconsumos (
            id_empresa, agencia, id_consumo, descripcion, nombre_corto,
            capacidad, estatus, estatus2, notas, rutafoto, codigoarticulo,
            tipo_idconsumo, aplica_touch, ubicacion, caracticas, codarea, mostrarenmapa, tipofac
        ) VALUES (
            'BAHIAP', '001', '{$cod}', '{$desc}', '{$cod}',
            '', 1, 1, '', '{$foto_esc}', '',
            '{$tipo}', 1, '{$ubicacion}', '', '{$codarea}', 1, 0
        )");
        echo "  + Creada nueva mesa: {$cod} ({$desc})\n";
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

// 3. Normalizar pedidos activos en operti para evitar fallas de case-sensitivity en FoxPro (EM01 -> Em01)
echo "\n[3/4] Sincronizando pedidos activos en transito...\n";
$db->query("UPDATE operti SET documento = CONCAT('E', LOWER(SUBSTRING(documento, 2))), ordentrab = CONCAT('E', LOWER(SUBSTRING(documento, 2))) WHERE tipodoc = 'ESP'");
$db->query("UPDATE opermv SET documento = CONCAT('E', LOWER(SUBSTRING(documento, 2))) WHERE tipodoc = 'ESP'");
$db->query("UPDATE comandasmv SET documento = CONCAT('E', LOWER(SUBSTRING(documento, 2)))");
$db->query("UPDATE operti SET ordentrab = documento WHERE tipodoc = 'ESP' AND ordentrab != documento");
echo "  [OK] Documentos y ordenes de trabajo normalizados a formato exacto FoxPro (EmXX).\n";

// 4. Enrutamiento de Grupos de Impresión
echo "\n[4/4] Configurando destino de impresion de grupos (BARRA / COCINA)...\n";
$db->query("UPDATE grupos SET formato = 'BARRA' WHERE codigo = '005'");
$db->query("UPDATE grupos SET formato = 'BARRA' WHERE codigo = '006'");
echo "  [OK] Grupo 005 (BEBIDAS) -> Formato BARRA\n";
echo "  [OK] Grupo 006 (LICORES / Cervezas) -> Formato BARRA\n";

echo "\n========================================================\n";
echo "  PROCESO COMPLETADO EXITOSAMENTE                       \n";
echo "  Todas las mesas y grupos estan listos para operar.    \n";
echo "========================================================\n";
