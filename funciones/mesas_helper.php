<?php
/**
 * Clasificación Universal de Documentos ESP de Premium Soft:
 * "Mesa Real" vs "Pedido en Caja" (comida para llevar / caja inventada).
 *
 * Lógica Universal y Dinámica:
 * - Toda mesa física real del restaurante está registrada en `adminbahiap.idconsumos`
 *   (MESA01..16, JARD01..05, TASC01, y expansiones futuras hasta MESA22+).
 * - Si el código ingresado o documento ESP existe en `idconsumos`, es una MESA REAL:
 *   puede generar código QR, botón "Compartir", y se sincroniza al hosting cloud.
 * - Si el código NO existe en `idconsumos` (ej. 001, 099, 01, 020, 0101010, o cualquier número
 *   inventado por el cajero para pedidos para llevar), se clasifica como PEDIDO EN CAJA:
 *   NUNCA genera QR/PIN, NUNCA se sube a la nube y se procesa solo de forma interna.
 */

if (!function_exists('normalizar_codigo_mesa')) {
    function normalizar_codigo_mesa($documento)
    {
        $c = strtoupper(trim((string) $documento));
        // Quitar prefijo 'E' o 'ESP' típico de documentos operti
        $c = preg_replace('/^E/i', '', $c);
        // Quitar espacios, guiones y caracteres no alfanuméricos
        $c = preg_replace('/[^A-Z0-9]/', '', $c);
        // Tolerar falta de cero a la izquierda en nombres de mesa (MESA1 -> MESA01, JARD3 -> JARD03)
        $c = preg_replace('/^([A-Z]+)(\d)$/', '${1}0$2', $c);
        return $c;
    }
}

if (!function_exists('obtener_mesas_oficiales')) {
    /**
     * Consulta y almacena en caché las mesas reales registradas en Premium Soft (idconsumos y mapamesas).
     * @param mysqli|null $mysqli_pos Conexión a adminbahiap.
     * @return array [CODIGO_NORMALIZADO => true]
     */
    function obtener_mesas_oficiales($mysqli_pos)
    {
        static $cache_oficiales = null;
        if ($cache_oficiales !== null) {
            return $cache_oficiales;
        }

        $oficiales = [];
        if ($mysqli_pos && !$mysqli_pos->connect_errno) {
            // 1. Cuentas oficiales de idconsumos (tabla principal de mesas de Premium)
            try {
                $res = @$mysqli_pos->query("SELECT id_consumo, descripcion, nombre_corto FROM idconsumos WHERE id_consumo <> ''");
                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        foreach (['id_consumo', 'descripcion', 'nombre_corto'] as $col) {
                            if (!empty($r[$col])) {
                                $norm = normalizar_codigo_mesa($r[$col]);
                                if ($norm !== '') {
                                    $oficiales[$norm] = true;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}

            // 2. Mapa 2D táctil mapamesas (si se usa en el restaurante)
            try {
                $res_m = @$mysqli_pos->query("SELECT DISTINCT coditem FROM mapamesas WHERE coditem <> ''");
                if ($res_m) {
                    while ($rm = $res_m->fetch_assoc()) {
                        if (!empty($rm['coditem'])) {
                            $norm = normalizar_codigo_mesa($rm['coditem']);
                            if ($norm !== '') {
                                $oficiales[$norm] = true;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        $cache_oficiales = $oficiales;
        return $oficiales;
    }
}

if (!function_exists('es_mesa_oficial')) {
    /**
     * Determina si un código o documento corresponde a una mesa física registrada en Premium Soft.
     * @param string $documento
     * @param mysqli|array|null $pos_o_registro Conexión mysqli a adminbahiap o array de oficiales.
     * @return bool
     */
    function es_mesa_oficial($documento, $pos_o_registro)
    {
        $norm = normalizar_codigo_mesa($documento);
        if ($norm === '') {
            return false;
        }

        if (is_array($pos_o_registro)) {
            $oficiales = $pos_o_registro['registradas'] ?? ($pos_o_registro['oficiales'] ?? $pos_o_registro);
            return isset($oficiales[$norm]);
        }

        if ($pos_o_registro instanceof mysqli) {
            $oficiales = obtener_mesas_oficiales($pos_o_registro);
            return isset($oficiales[$norm]);
        }

        return false;
    }
}

if (!function_exists('es_pedido_caja')) {
    /**
     * Determina si un documento es un pedido de caja / comida para llevar / cuenta inventada.
     * Regla universal: Si NO es mesa oficial de idconsumos, ES PEDIDO DE CAJA.
     */
    function es_pedido_caja($documento, $pos_o_registro)
    {
        return !es_mesa_oficial($documento, $pos_o_registro);
    }
}

if (!function_exists('cargar_registro_mesas')) {
    /**
     * Compatibilidad con llamadas legacy: retorna array con 'oficiales' y 'registradas'.
     */
    function cargar_registro_mesas($mysqli_pos, array $config = [])
    {
        $oficiales = obtener_mesas_oficiales($mysqli_pos);
        return [
            'caja'        => [],
            'registradas' => $oficiales,
            'oficiales'   => $oficiales
        ];
    }
}

if (!function_exists('construir_registro_mesas')) {
    /**
     * Compatibilidad legacy con listas fijas de coditems.
     */
    function construir_registro_mesas(array $coditems, array $config = [])
    {
        $oficiales = [];
        foreach ($coditems as $ci) {
            $c = normalizar_codigo_mesa($ci);
            if ($c !== '') {
                $oficiales[$c] = true;
            }
        }
        return [
            'caja'        => [],
            'registradas' => $oficiales,
            'oficiales'   => $oficiales
        ];
    }
}

if (!function_exists('resolver_documento_mesa')) {
    /**
     * Resuelve el nombre canónico de documento para Premium Soft (ej. "Em01", "Em00", "Ej02").
     * En FoxPro, la grilla de CUENTAS compara 'Em01' == ALLTRIM(documento) de forma case-sensitive.
     * Si documento es 'EM01' (con M mayúscula), FoxPro no lo reconoce y no pinta la mesa en ROJO.
     *
     * @param string $mesa_input Valor recibido (ej: "M01", "m01", "01", "1", "Em01", "EM01", "j02")
     * @param mysqli|null $mysqli_pos Conexión a la base de datos de Premium Soft (adminbahiap)
     * @return string Documento canónico (ej: "Em01", "E001")
     */
    function resolver_documento_mesa($mesa_input, $mysqli_pos = null)
    {
        $input = trim((string)$mesa_input);
        if ($input === '') {
            return '';
        }

        // Quitar prefijo E o ESP inicial si ya lo trae (ej. "Em01" -> "m01", "EM01" -> "M01")
        $sin_e = preg_replace('/^E/i', '', $input);
        $sin_e_trim = trim($sin_e);

        if (!$mysqli_pos || !($mysqli_pos instanceof mysqli)) {
            if (function_exists('conectar_bd_pos')) {
                $mysqli_pos = conectar_bd_pos();
            }
        }

        if ($mysqli_pos instanceof mysqli && !$mysqli_pos->connect_errno) {
            $esc = $mysqli_pos->real_escape_string($sin_e_trim);
            // Buscar coincidencia en idconsumos por id_consumo, nombre_corto o descripcion
            $sql = "SELECT id_consumo FROM idconsumos 
                    WHERE id_consumo = '{$esc}' 
                       OR nombre_corto = '{$esc}' 
                       OR descripcion = '{$esc}'";

            // Si es numérico (ej: "1" o "01"), buscar coincidencia como m01
            if (ctype_digit($sin_e_trim)) {
                $num_pad = str_pad($sin_e_trim, 2, '0', STR_PAD_LEFT);
                $sql .= " OR id_consumo = 'm{$num_pad}' OR nombre_corto = 'm{$num_pad}'";
            }

            $sql .= " LIMIT 1";

            $res = @$mysqli_pos->query($sql);
            if ($res && ($row = $res->fetch_assoc())) {
                $id_consumo = trim($row['id_consumo']);
                if (!empty($id_consumo)) {
                    return 'E' . $id_consumo;
                }
            }
        }

        // Reglas de respaldo sin conexión o si no está en idconsumos:
        if (preg_match('/^m\d+/i', $sin_e_trim)) {
            return 'E' . strtolower($sin_e_trim);
        } elseif (preg_match('/^j\d+/i', $sin_e_trim)) {
            return 'E' . strtolower($sin_e_trim);
        } elseif (preg_match('/^t\d+/i', $sin_e_trim)) {
            return 'E' . strtolower($sin_e_trim);
        } elseif (ctype_digit($sin_e_trim) && strlen($sin_e_trim) <= 2) {
            return 'Em' . str_pad($sin_e_trim, 2, '0', STR_PAD_LEFT);
        }

        return 'E' . $sin_e_trim;
    }
}

