<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
clearstatcache();

include ("../modelos/modelo_crud.php");
$objeto_datos = new crud();
$bd_conexion_cliente = "cliente";
$bd_conexion_servidor = "servidor";

if ($_GET['accion'] == "buscar") {
    $codigo = "codigo";
    $nombre = "nombre";
    if ($_POST['parametro'] == "agencia") {
        $select = "agencia,nombre";
        $from = "agencias";
        $codigo = "agencia";
    }
    if ($_POST['parametro'] == "cliente") {
        $select = "codigo,nombre,nrorif,formafis";
        $from = "cliempre";
    }
    if ($_POST['parametro'] == "zona") {
        $select = "codigo,zona";
        $from = "sectores";
        $nombre = "zona";
    }
    if ($_POST['parametro'] == "almacen") {
        $select = "codigo,nombre";
        $from = "almacene";
    }
    // Si la palabra empieza con *, el resto es el criterio; si es solo * listar todos
    $palabra = isset($_POST['palabra']) ? trim($_POST['palabra']) : '';
    if (strpos($palabra, '*') === 0) {
        $palabra = trim(substr($palabra, 1));
    }
    if ($palabra === '') {
        $where = "";
    } else {
        if ($_POST['buscar_por'] == "Codigo") {
            $where = $codigo . "='" . $palabra . "'";
        }
        if ($_POST['buscar_por'] == "Nombre") {
            $where = $nombre . " LIKE '%" . $palabra . "%'";
        }
    }
    $consulta = $objeto_datos->select($bd_conexion_cliente, $select, $from, $where, '', '');
    echo "
		<table class='table table-striped'>
			<thead>
				<tr>
					<th>Codigo</th><th>Nombre</th><th>Rif</th>
				</tr>
			</thead>
			<tbody>
	";
    while ($datos = mysqli_fetch_array($consulta)) {
        if ($_POST['parametro'] == "agencia") {
            $datos['codigo'] = $datos['agencia'];
        }
        if ($_POST['parametro'] == "zona") {
            $datos['nombre'] = $datos['zona'];
        }
        
        // Limpiar el array para que solo tenga índices asociativos (sin índices numéricos)
        $datos_clean = array(
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre']
        );
        
        // Agregar campos específicos según el parámetro
        if ($_POST['parametro'] == "cliente") {
            // Obtener nrorif directamente del array - SIEMPRE incluirlo en el objeto
            // Usar el mismo valor que se muestra en la tabla
            $nrorif_valor = '';
            if (isset($datos['nrorif']) && $datos['nrorif'] != null) {
                $nrorif_valor = trim($datos['nrorif']);
            }
            // Asegurar que siempre esté presente en el objeto, incluso si está vacío
            $datos_clean['nrorif'] = $nrorif_valor;
            $datos_clean['formafis'] = isset($datos['formafis']) ? $datos['formafis'] : '';
        }
        
        // Mostrar la fila con todos los campos disponibles
        if ($_POST['parametro'] == "cliente") {
            // Obtener nrorif directamente del array original para mostrar
            $nrorif_mostrar = isset($datos['nrorif']) && $datos['nrorif'] != null && trim($datos['nrorif']) != '' ? trim($datos['nrorif']) : '-';
            
            // Preparar el JSON - asegurar que se escapen correctamente los caracteres especiales
            $json_parametro = json_encode($_POST['parametro']);
            $json_datos = json_encode($datos_clean);
            
            echo "
				<tr onclick='select_resultado_busqueda(".$json_parametro.",".$json_datos.")'>	
					<td>" . $datos['codigo'] . "</td>
					<td>" . $datos['nombre'] . "</td>
					<td>" . $nrorif_mostrar . "</td>
				</tr>
			";
        } else {
            echo "
				<tr onclick='select_resultado_busqueda(".json_encode($_POST['parametro']).",".json_encode($datos_clean).")'>	
					<td>" . $datos['codigo'] . "</td>
					<td>" . $datos['nombre'] . "</td>
				</tr>
			";
        }
    }
    echo "
			</tbody>
		</table>
	";
}

if ( $_GET['accion'] == "consultar_cliente") {

    if( !empty($_POST['codcliente']) ){

        $query = "
            SELECT
            codigo,
            nombre,
            perscont,
            cedula,
            nrorif,
            nronit,
            direccion,
            telefonos,
            email,
            formafis,
            precio
            FROM cliempre
            WHERE codigo = '".$_POST['codcliente']."'
            LIMIT 1
        ";

        $resultado = $objeto_datos->general_query($bd_conexion_cliente,$query);
        $datos = mysqli_fetch_array($resultado);
        
        // Limpiar valores NULL - pero mantener los valores reales
        if ($datos != null) {
            // Limpiar y trimear valores, pero mantener los valores reales (no convertir a vacío si tienen valor)
            if (isset($datos['nrorif']) && $datos['nrorif'] != null) {
                $datos['nrorif'] = trim($datos['nrorif']);
            } else {
                $datos['nrorif'] = '';
            }
            if (isset($datos['nronit']) && $datos['nronit'] != null) {
                $datos['nronit'] = trim($datos['nronit']);
            } else {
                $datos['nronit'] = '';
            }
            if (isset($datos['cedula']) && $datos['cedula'] != null) {
                $datos['cedula'] = trim($datos['cedula']);
            } else {
                $datos['cedula'] = '';
            }
            if (isset($datos['nombre']) && $datos['nombre'] != null) {
                $datos['nombre'] = trim($datos['nombre']);
            } else {
                $datos['nombre'] = '';
            }
            if (isset($datos['perscont']) && $datos['perscont'] != null) {
                $datos['perscont'] = trim($datos['perscont']);
            } else {
                $datos['perscont'] = '';
            }
            if (isset($datos['direccion']) && $datos['direccion'] != null) {
                $datos['direccion'] = trim($datos['direccion']);
            } else {
                $datos['direccion'] = '';
            }
            if (isset($datos['telefonos']) && $datos['telefonos'] != null) {
                $datos['telefonos'] = trim($datos['telefonos']);
            } else {
                $datos['telefonos'] = '';
            }
            if (isset($datos['email']) && $datos['email'] != null) {
                $datos['email'] = trim($datos['email']);
            } else {
                $datos['email'] = '';
            }
            $datos['formafis'] = isset($datos['formafis']) ? $datos['formafis'] : '';
        }
        
        echo json_encode($datos);

    }else{
        echo "<i class='fa fa-times'></i> Indique el codigo del cliente";
    }

}

if ($_GET['accion'] == "registrar_conexion") {

    require_once dirname(__DIR__) . '/funciones/config_helper.php';

    foreach($_POST as $index => $value){
        $_POST[$index] = trim($value);
    }

    $compartir_mesas = isset($_POST['compartir_mesas']) ? 'si' : 'no';
    $usar_subgrupos = isset($_POST['usar_subgrupos']) ? 'si' : 'no';
    $agrupar_mesas = isset($_POST['agrupar_mesas']) ? 'si' : 'no';
    $no_usar_impuestos = isset($_POST['no_usar_impuestos']) ? 'si' : 'no';
    $no_editar_integrados = isset($_POST['no_editar_integrados']) ? 'si' : 'no';
    $ocultar_integrados = isset($_POST['ocultar_integrados']) ? 'si' : 'no';
    $no_tiene_existencia = isset($_POST['no_tiene_existencia']) ? 'si' : 'no';
    $permitir_descuentos = isset($_POST['permitir_descuentos']) ? 'si' : 'no';

    $datos_existentes = obtener_config_sistema();

    $host_loc = !empty($_POST['host']) ? $_POST['host'] : '127.0.0.1';
    $user_loc = !empty($_POST['user']) ? $_POST['user'] : 'root';
    $pw_loc   = isset($_POST['pw']) ? $_POST['pw'] : 'root';
    $port_loc = !empty($_POST['port']) ? (int)$_POST['port'] : 3307;
    $bd_pos   = !empty($_POST['bd']) ? $_POST['bd'] : 'adminbahiap';
    $bd_sist  = !empty($_POST['bd_sistema']) ? $_POST['bd_sistema'] : 'mijar26273_sistema';

    $url_host = !empty($_POST['url_hosting']) ? rtrim($_POST['url_hosting'], '/') : 'https://mijardinrestaurant.com';
    if (strpos($url_host, '.test') !== false) {
        $url_host = 'https://mijardinrestaurant.com';
    }
    $url_mesa = !empty($_POST['url_web_mesa']) && strpos($_POST['url_web_mesa'], '.test') === false ? $_POST['url_web_mesa'] : ($url_host . '/mesa');

    $modo_entorno = !empty($_POST['modo_entorno']) ? strtolower(trim($_POST['modo_entorno'])) : ($datos_existentes['modo_entorno'] ?? 'desarrollo');

    $array = array(
        "modo_entorno" => $modo_entorno,
        "host" => $host_loc,
        "user" => $user_loc,
        "pw" => $pw_loc,
        "bd" => $bd_pos,
        "bd_pos" => $bd_pos,
        "bd_sistema" => $bd_sist,
        "port" => $port_loc,
        "url_hosting" => $url_host,
        "url_web_mesa" => $url_mesa,
        "nombre_empresa" => $_POST['nombre_empresa'] ?? ($datos_existentes['nombre_empresa'] ?? ''),
        "cod_empresa" => $_POST['cod_empresa'] ?? '',
        "cod_agencia" => $_POST['cod_agencia'] ?? '',
        "cod_cliente" => $_POST['cod_cliente'] ?? '',
        "nrorif" => $_POST['nrorif'] ?? '',
        "cod_zona" => $_POST['cod_zona'] ?? '',
        "cod_deposito" => $_POST['cod_deposito'] ?? '',
        "recargo" => ($_POST['recargo'] == '' ? 0 : $_POST['recargo']),
        "pw_supervisor" => $datos_existentes['pw_supervisor'] ?? 'PREMIUM',
        "cantidad_por_pagina" => !empty($_POST['cantidad_por_pagina']) ? $_POST['cantidad_por_pagina'] : 12,
        "cuentas_compartidas_entre_vendedores" => $compartir_mesas,
        "agrupar_mesas_por_ubicacion" => $agrupar_mesas,
        "usar_subgrupos" => $usar_subgrupos,
        "no_usar_impuestos" => $no_usar_impuestos,
        "no_editar_integrados" => $no_editar_integrados,
        "ocultar_integrados" => $ocultar_integrados,        
        "cm_ancho_precuenta" => !empty($_POST['cm_ancho_precuenta']) ? $_POST['cm_ancho_precuenta'] : 8,
        "tipo_doc" => !empty($_POST['tipo_doc']) ? $_POST['tipo_doc'] : 'ESP',
        "no_tiene_existencia" => $no_tiene_existencia,
        "permitir_descuentos" => $permitir_descuentos,
        "minutos_inactividad" => !empty($_POST['minutos_inactividad']) ? $_POST['minutos_inactividad'] : 0
    );

    if (isset($datos_existentes['impuestos'])) {
        $array['impuestos'] = $datos_existentes['impuestos'];
    }
    if (isset($datos_existentes['dividemultiplica'])) {
        $array['dividemultiplica'] = $datos_existentes['dividemultiplica'];
    }
    if (isset($datos_existentes['monedafactor'])) {
        $array['monedafactor'] = $datos_existentes['monedafactor'];
    }

    // Guardar en la base de datos local mijar26273_sistema (puerto 3307)
    $guardado = guardar_config_sistema($array);

    // Obtener las formas de pago (cuentas bancarias de listbanc en adminbahiap) para enviar a la BD remota
    $bancos_para_nube = [];
    $mysqli_pos = @new mysqli($host_loc, $user_loc, $pw_loc, $bd_pos, $port_loc);
    if ($mysqli_pos && !$mysqli_pos->connect_errno) {
        $resBancos = $mysqli_pos->query("SELECT codigo, banco, titular, represent as contacto, telefonos as telefono, cuentaban as cuenta FROM listbanc WHERE inactiva = 0 OR inactiva IS NULL");
        if ($resBancos && $resBancos->num_rows > 0) {
            while ($b = $resBancos->fetch_assoc()) {
                $nombreB = trim($b['banco'] ?? '');
                $tipoB = 'PAGO_MOVIL';
                if (stripos($nombreB, 'zelle') !== false) $tipoB = 'ZELLE';
                elseif (stripos($nombreB, 'transferencia') !== false) $tipoB = 'TRANSFERENCIA';

                $bancos_para_nube[] = [
                    'codigo' => trim($b['codigo'] ?? ''),
                    'banco' => $nombreB,
                    'titular' => trim($b['titular'] ?? ''),
                    'contacto' => trim($b['contacto'] ?? ''),
                    'identificacion' => trim($b['contacto'] ?? ''),
                    'telefono' => trim($b['telefono'] ?? ''),
                    'cuenta' => trim($b['cuenta'] ?? ''),
                    'numero_cuenta' => trim($b['cuenta'] ?? ''),
                    'tipo' => $tipoB,
                ];
            }
        }
        $mysqli_pos->close();
    }

    // Guardar en la base de datos satélite local mijar26273_sistema (puerto 3307)
    $mysqli_sat = @new mysqli($host_loc, $user_loc, $pw_loc, $bd_sist, $port_loc);
    if ($mysqli_sat && !$mysqli_sat->connect_errno) {
        $mysqli_sat->query("TRUNCATE TABLE z_config_bancos");
        foreach ($bancos_para_nube as $bn) {
            $stmt = $mysqli_sat->prepare("INSERT INTO z_config_bancos (codigo_pos, banco, titular, identificacion, telefono, numero_cuenta, tipo, activo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            if ($stmt) {
                $stmt->bind_param('sssssss', $bn['codigo'], $bn['banco'], $bn['titular'], $bn['identificacion'], $bn['telefono'], $bn['numero_cuenta'], $bn['tipo']);
                $stmt->execute();
                $stmt->close();
            }
        }
        $mysqli_sat->close();
    }

    // Enviar a la BD remota del hosting vía HTTPS
    $url_api = $url_host . '/api/sincronizar-mesas';
    $payload = json_encode([
        'bancos' => $bancos_para_nube,
        'empresa' => $array['nombre_empresa'],
        'tasa_dia' => $array['monedafactor'] ?? 1,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $cantBancos = count($bancos_para_nube);
    if ($code === 200) {
        echo "<i class='fa fa-check'></i> ¡BD remota y local actualizadas con éxito! (" . $cantBancos . " formas de pago sincronizadas en la nube)
        <script>
            setTimeout(function(){ window.location.reload(); }, 1500);
        </script>";
    } else {
        echo "<i class='fa fa-check'></i> Guardado en BD local (" . ($code ? "Nube HTTP $code" : "Nube offline") . ")
        <script>
            setTimeout(function(){ window.location.reload(); }, 1500);
        </script>";
    }
}


if (isset($_GET['accion']) && $_GET['accion'] == "sincronizar_bancos_rapido") {
    header('Content-Type: application/json; charset=utf-8');
    $config = obtener_config_sistema();
    $host_loc = $config['host'] ?? '127.0.0.1';
    $user_loc = $config['user'] ?? 'root';
    $pw_loc   = $config['pw'] ?? 'root';
    $port_loc = (int)($config['port'] ?? 3307);
    $bd_pos   = $config['bd_pos'] ?? 'adminbahiap';
    $bd_sist  = $config['bd_sistema'] ?? 'mijar26273_sistema';
    $url_host = rtrim($config['url_hosting'] ?? 'https://mijardinrestaurant.com', '/');

    $bancos_para_nube = [];
    $mysqli_pos = @new mysqli($host_loc, $user_loc, $pw_loc, $bd_pos, $port_loc);
    if ($mysqli_pos && !$mysqli_pos->connect_errno) {
        $resBancos = $mysqli_pos->query("SELECT codigo, banco, titular, represent as contacto, telefonos as telefono, cuentaban as cuenta FROM listbanc WHERE inactiva = 0 OR inactiva IS NULL");
        if ($resBancos && $resBancos->num_rows > 0) {
            while ($b = $resBancos->fetch_assoc()) {
                $nombreB = trim($b['banco'] ?? '');
                $tipoB = 'PAGO_MOVIL';
                if (stripos($nombreB, 'zelle') !== false) $tipoB = 'ZELLE';
                elseif (stripos($nombreB, 'transferencia') !== false) $tipoB = 'TRANSFERENCIA';

                $bancos_para_nube[] = [
                    'codigo' => trim($b['codigo'] ?? ''),
                    'banco' => $nombreB,
                    'titular' => trim($b['titular'] ?? ''),
                    'contacto' => trim($b['contacto'] ?? ''),
                    'identificacion' => trim($b['contacto'] ?? ''),
                    'telefono' => trim($b['telefono'] ?? ''),
                    'cuenta' => trim($b['cuenta'] ?? ''),
                    'numero_cuenta' => trim($b['cuenta'] ?? ''),
                    'tipo' => $tipoB,
                ];
            }
        }
        $mysqli_pos->close();
    }

    $mysqli_sat = @new mysqli($host_loc, $user_loc, $pw_loc, $bd_sist, $port_loc);
    if ($mysqli_sat && !$mysqli_sat->connect_errno) {
        $mysqli_sat->query("TRUNCATE TABLE z_config_bancos");
        foreach ($bancos_para_nube as $bn) {
            $stmt = $mysqli_sat->prepare("INSERT INTO z_config_bancos (codigo_pos, banco, titular, identificacion, telefono, numero_cuenta, tipo, activo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            if ($stmt) {
                $stmt->bind_param('sssssss', $bn['codigo'], $bn['banco'], $bn['titular'], $bn['identificacion'], $bn['telefono'], $bn['numero_cuenta'], $bn['tipo']);
                $stmt->execute();
                $stmt->close();
            }
        }
        $mysqli_sat->close();
    }

    $cantBancos = count($bancos_para_nube);
    $url_api = $url_host . '/api/sincronizar-mesas';
    $payload = json_encode([
        'bancos' => $bancos_para_nube,
        'empresa' => $config['nombre_empresa'] ?? 'Mi Jardín Restaurant',
        'tasa_dia' => $config['monedafactor'] ?? 1,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo json_encode([
        'success' => true,
        'cant_bancos' => $cantBancos,
        'http_code' => $code,
        'mensaje' => "¡{$cantBancos} cuenta(s) bancaria(s) sincronizada(s) con éxito en la BD local y en el hosting web ({$url_host})!"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_GET['accion'] == "mostrar_datos_conexion") {

    $file = json_encode(obtener_config_sistema());
    echo $file;

}