<?php
date_default_timezone_set('America/Panama');

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

?>