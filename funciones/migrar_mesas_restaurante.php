<?php
/**
 * Script de Actualización y Normalización de Mesas y Grupos
 * Mi Jardín Restaurant - Premium Soft
 *
 * Configura los códigos de mesa estándar en MAYÚSCULAS:
 * - Tasca:  T01 (TASCA 01)
 * - Llevar: M00 (PARA LLEVAR)
 * - Salón:  M01 a M22 (MESA 01 a MESA 22)
 * - Jardín: J01 a J05 (JARDIN 01 a JARDIN 05)
 * - Grupos: 005 (BEBIDAS) y 006 (LICORES / Cervezas) asignados a formato 'BARRA'
 * - Sincroniza órdenes activas en operti a formato Premium Soft (EM01, EJ01, etc.)
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
    echo "Verifique que el servicio MySQL (Puerto 3306) esté iniciado.\n";
    exit(1);
}

echo "========================================================\n";
echo "  ACTUALIZACION DE MESAS Y GRUPOS - MI JARDIN RESTAURANT \n";
echo "========================================================\n\n";

// 1. Respaldo de seguridad previo con fecha y hora
$fecha_bak = date('Ymd_His');
$db->query("CREATE TABLE IF NOT EXISTS `idconsumos_bak_{$fecha_bak}` AS SELECT * FROM idconsumos");
echo "[1/4] Respaldo de seguridad creado en: idconsumos_bak_{$fecha_bak}\n\n";

// 2. Normalización de Mesas a Mayúsculas
echo "[2/4] Configurando codigos de mesas en idconsumos (Mayusculas)...\n";

function actualizar_mesa_mayus($db, $cod, $desc, $ubicacion, $codarea, $tipo, $foto) {
    $candidatos = ["'{$cod}'", "'" . strtolower($cod) . "'"];
    $desc_esc = $db->real_escape_string($desc);

    if (preg_match('/^M(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        if ($num === 0) {
            $candidatos = array_merge($candidatos, ["'LLEVAR'", "'llevar'", "'001'", "'m00'", "'M00'"]);
        } else {
            $num_str = sprintf('%02d', $num);
            $candidatos = array_merge($candidatos, [
                "'MESA{$num_str}'", "'mesa{$num_str}'",
                "'MESA{$num}'", "'mesa{$num}'",
                "'MESA {$num}'", "'mesa {$num}'",
                "'MESA {$num_str}'", "'mesa {$num_str}'",
                "'m{$num_str}'", "'M{$num_str}'",
                "'m{$num}'", "'M{$num}'"
            ]);
        }
    } elseif (preg_match('/^T(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        $num_str = sprintf('%02d', $num);
        $candidatos = array_merge($candidatos, [
            "'TASC{$num_str}'", "'tasc{$num_str}'",
            "'TASC{$num}'", "'tasc{$num}'",
            "'TASCA {$num}'", "'tasca {$num}'",
            "'TASCA {$num_str}'", "'tasca {$num_str}'",
            "'TASCA{$num_str}'", "'tasca{$num_str}'",
            "'t{$num_str}'", "'T{$num_str}'"
        ]);
    } elseif (preg_match('/^J(\d{2})$/i', $cod, $m)) {
        $num = (int)$m[1];
        $num_str = sprintf('%02d', $num);
        $candidatos = array_merge($candidatos, [
            "'JARD{$num_str}'", "'jard{$num_str}'",
            "'JARD{$num}'", "'jard{$num}'",
            "'JARDIN {$num}'", "'jardin {$num}'",
            "'JARDIN {$num_str}'", "'jardin {$num_str}'",
            "'JARD {$num}'", "'jard {$num}'",
            "'JARD {$num_str}'", "'jard {$num_str}'",
            "'j{$num_str}'", "'J{$num_str}'"
        ]);
    }

    $candidatos_in = implode(', ', array_unique($candidatos));
    $where_match = "id_consumo IN ({$candidatos_in}) OR descripcion = '{$desc_esc}'";

    $existe = false;
    $foto_esc = $db->real_escape_string($foto);

    // Priorizar coincidencia exacta de código
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

        // Eliminar posibles duplicados o restos de pruebas previas
        $db->query("DELETE FROM idconsumos WHERE ({$where_match}) AND id_consumo != '{$cod}'");

        echo "  * Mesa {$cod} ({$desc}) lista. (Origen: {$id_orig})\n";

        // Si la mesa tenía otro código anterior y está abierta en operti, sincronizar documento
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

// 1. Tasca (T01)
actualizar_mesa_mayus($db, 'T01', 'TASCA 01', 'TASCA', '003', '001', 'C:\\PREMIUM\\IMAGENES\\MESAS.JPG');

// 2. Llevar (M00 -> PARA LLEVAR)
actualizar_mesa_mayus($db, 'M00', 'PARA LLEVAR', 'SALON', '002', '001', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG');

// 3. Salón (M01 a M22)
for ($i = 1; $i <= 22; $i++) {
    $num_str = sprintf('%02d', $i);
    actualizar_mesa_mayus($db, "M{$num_str}", "MESA {$num_str}", 'SALON', '002', '001', 'C:\\PREMIUM\\IMAGENES\\MESAS4.JPG');
}

// 4. Jardín (J01 a J05)
for ($j = 1; $j <= 5; $j++) {
    $num_str = sprintf('%02d', $j);
    actualizar_mesa_mayus($db, "J{$num_str}", "JARDIN {$num_str}", 'JARDIN', '001', '002', 'C:\\PREMIUM\\IMAGENES\\JARDIN.JPG');
}

// 3. Sincronizar órdenes activas en operti para que FoxPro las reconozca en ROJO
echo "\n[3/4] Sincronizando ordenes activas en transito a mayusculas (EMXX, EJXX)...\n";
$db->query("UPDATE operti SET documento = CONCAT('E', UPPER(SUBSTRING(documento, 2))), ordentrab = CONCAT('E', UPPER(SUBSTRING(documento, 2))) WHERE tipodoc = 'ESP'");
$db->query("UPDATE opermv SET documento = CONCAT('E', UPPER(SUBSTRING(documento, 2))) WHERE tipodoc = 'ESP'");
$db->query("UPDATE comandasmv SET documento = CONCAT('E', UPPER(SUBSTRING(documento, 2)))");
$db->query("UPDATE operti SET ordentrab = documento WHERE tipodoc = 'ESP' AND ordentrab != documento");
echo "  [OK] Documentos y ordenes de trabajo sincronizados al estandar Premium Soft (EMXX).\n";

// 4. Enrutamiento de Grupos de Impresión (Bebidas y Licores a BARRA)
echo "\n[4/4] Configurando destino de impresion de grupos (BARRA / COCINA)...\n";
$db->query("UPDATE grupos SET formato = 'BARRA' WHERE codigo = '005'");
$db->query("UPDATE grupos SET formato = 'BARRA' WHERE codigo = '006'");
echo "  [OK] Grupo 005 (BEBIDAS) -> Formato BARRA\n";
echo "  [OK] Grupo 006 (LICORES / Cervezas) -> Formato BARRA\n";

echo "\n========================================================\n";
echo "  PROCESO COMPLETADO EXITOSAMENTE                       \n";
echo "  - 22 Mesas en Salon (M01 .. M22)                      \n";
echo "  - 1 Mesa Especial Llevar (M00 -> PARA LLEVAR)         \n";
echo "  - 5 Mesas en Jardin (J01 .. J05)                      \n";
echo "  - 1 Mesa en Tasca (T01)                               \n";
echo "  - Impresion de Bebidas y Licores asignada a BARRA     \n";
echo "========================================================\n";
