<?php
date_default_timezone_set('America/Caracas');

function limpiarCaracteresEspeciales($string) {
    //$string = htmlentities($string);
    //$string = preg_replace('/\&(.)[^;]*;/', '', $string);
    return $string;
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
        $array[$index] = ($value !== null) ? trim($value) : '';
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