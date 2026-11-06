<?php
date_default_timezone_set('America/Caracas');

if (!function_exists('quitar_acentos')) {
    function quitar_acentos($string) {
        if (!is_string($string) || $string === '') return $string;
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
        }
        $tabla = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u',
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'à'=>'a', 'è'=>'e', 'ì'=>'i', 'ò'=>'o', 'ù'=>'u',
            'À'=>'A', 'È'=>'E', 'Ì'=>'I', 'Ò'=>'O', 'Ù'=>'U',
            'ä'=>'a', 'ë'=>'e', 'ï'=>'i', 'ö'=>'o', 'ü'=>'u',
            'Ä'=>'A', 'Ë'=>'E', 'Ï'=>'I', 'Ö'=>'O', 'Ü'=>'U',
            'â'=>'a', 'ê'=>'e', 'î'=>'i', 'ô'=>'o', 'û'=>'u',
            'Â'=>'A', 'Ê'=>'E', 'Î'=>'I', 'Ô'=>'O', 'Û'=>'U',
            'ñ'=>'n', 'Ñ'=>'N', 'ç'=>'c', 'Ç'=>'C'
        ];
        return strtr($string, $tabla);
    }
}

function limpiarCaracteresEspeciales($string) {
    return quitar_acentos($string);
}

function calcular_tiempo_trasnc($hora1, $hora2) {
    if (isset($hora1) and isset($hora2)) {
        $separar[1] = explode(':', $hora1);
        $separar[2] = explode(':', $hora2);
        $total_minutos_trasncurridos[1] = ($separar[1][0] * 60) + $separar[1][1];
        $total_minutos_trasncurridos[2] = ($separar[2][0] * 60) + $separar[2][1];
        $total_minutos_trasncurridos = $total_minutos_trasncurridos[1] - $total_minutos_trasncurridos[2];
        if ($total_minutos_trasncurridos <= 59) return ($total_minutos_trasncurridos . ' Minutos');
        elseif ($total_minutos_trasncurridos > 59) {
            $HORA_TRANSCURRIDA = round($total_minutos_trasncurridos / 60);
            if ($HORA_TRANSCURRIDA <= 9) $HORA_TRANSCURRIDA = '0' . $HORA_TRANSCURRIDA;
            $MINUITOS_TRANSCURRIDOS = $total_minutos_trasncurridos % 60;
            if ($MINUITOS_TRANSCURRIDOS <= 9) $MINUITOS_TRANSCURRIDOS = '0' . $MINUITOS_TRANSCURRIDOS;
            return ($HORA_TRANSCURRIDA . ':' . $MINUITOS_TRANSCURRIDOS . ' Horas');
        }
    }
}

function LimpiarArreglo($array){
    foreach($array as $index => $value){
        if ($value !== null) {
            $array[$index] = is_string($value) ? quitar_acentos(trim($value)) : $value;
        } else {
            $array[$index] = '';
        }
    }
    return $array;
}

if (!function_exists('resolver_ruta_imagen')) {
    function resolver_ruta_imagen($rutafoto, $fallback = 'imagenes/MESAS.jpg') {
        $rutafoto = trim((string)$rutafoto);
        if ($rutafoto === '') return $fallback;

        $partes = preg_split('/[\/\\\\]+/', $rutafoto);
        $nombre = end($partes);

        // Si por falta de escape en BD se guardo sin barras (ej: "c:premiumimagenesjardin.jpg")
        $nombre = preg_replace('/^.*(?:imagenes|premium|c:)/i', '', $nombre);

        static $archivos_en_disco = null;
        if ($archivos_en_disco === null) {
            $dir = dirname(__DIR__) . '/imagenes/';
            $archivos_en_disco = [];
            if (is_dir($dir)) {
                $scanned = scandir($dir);
                foreach ($scanned as $f) {
                    if ($f !== '.' && $f !== '..') {
                        $archivos_en_disco[strtolower($f)] = $f;
                    }
                }
            }
        }

        $nombre_lower = strtolower($nombre);
        if (isset($archivos_en_disco[$nombre_lower])) {
            return 'imagenes/' . $archivos_en_disco[$nombre_lower];
        }

        return 'imagenes/' . $nombre;
    }
}

?>