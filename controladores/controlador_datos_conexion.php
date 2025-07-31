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
    if ($_POST['palabra'] == "*") {
        $where = "";
    } else {
        if ($_POST['buscar_por'] == "Codigo") {
            $where = $codigo . "='" . $_POST['palabra'] . "'";
        }
        if ($_POST['buscar_por'] == "Nombre") {
            $where = $nombre . " LIKE '%" . $_POST['palabra'] . "%'";
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
        if (isset($datos['nrorif'])){
        echo "
				<tr onclick='select_resultado_busqueda(".json_encode($_POST['parametro']).",".json_encode($datos).")'>	
					<td>" . $datos['codigo'] . "</td>
					<td>" . $datos['nombre'] . "</td>
					<td>" . $datos['nrorif'] . "</td>
				</tr>
			";
        }else{
            echo "
				<tr onclick='select_resultado_busqueda(".json_encode($_POST['parametro']).",".json_encode($datos).")'>	
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
            formafis
            FROM cliempre
            WHERE codigo = '".$_POST['codcliente']."'
            LIMIT 1
        ";

        $resultado = $objeto_datos->general_query($bd_conexion_cliente,$query);
        $datos = mysqli_fetch_array($resultado);
        echo json_encode($datos);

    }else{
        echo "<i class='fa fa-times'></i> Indique el codigo del cliente";
    }

}

if ($_GET['accion'] == "registrar_conexion") {

    foreach($_POST as $index => $value){
        $_POST[$index] = trim($value);
    }

    if( !isset($_POST['compartir_mesas']) ){
        $_POST['compartir_mesas'] = 'no';
    }else{
        $_POST['compartir_mesas'] = 'si';
    }

    if( !isset($_POST['usar_subgrupos']) ){
        $_POST['usar_subgrupos'] = 'no';
    }else{
        $_POST['usar_subgrupos'] = 'si';
    }

    if( !isset($_POST['agrupar_mesas']) ){
        $_POST['agrupar_mesas'] = 'no';
    }else{
        $_POST['agrupar_mesas'] = 'si';
    }

    if( !isset($_POST['no_usar_impuestos']) ){
        $_POST['no_usar_impuestos'] = 'no';
    }else{
        $_POST['no_usar_impuestos'] = 'si';
    }

    if( !isset($_POST['no_editar_integrados']) ){
        $_POST['no_editar_integrados'] = 'no';
    }else{
        $_POST['no_editar_integrados'] = 'si';
    }

    if( !isset($_POST['ocultar_integrados']) ){
        $_POST['ocultar_integrados'] = 'no';
    }else{
        $_POST['ocultar_integrados'] = 'si';
    }

    if( !isset($_POST['no_tiene_existencia']) ){
        $_POST['no_tiene_existencia'] = 'no';
    }else{
        $_POST['no_tiene_existencia'] = 'si';
    }

    if( empty($_POST['cantidad_por_pagina']) ){
        $_POST['cantidad_por_pagina'] = 9;
    }

    if( empty($_POST['cm_ancho_precuenta']) ){
        $_POST['cm_ancho_precuenta'] = 8;
    }

    if( empty($_POST['tipo_doc']) ){
        $_POST['tipo_doc'] = 'ESP';
    }

    if( empty($_POST['minutos_inactividad']) ){
        $_POST['minutos_inactividad'] = 0;
    }

    $array = array(
        "host"=>$_POST['host'],
        "user"=>$_POST['user'],
        "pw"=>$_POST['pw'],
        "bd"=>$_POST['bd'],
        "port"=>$_POST['port'],
        "nombre_empresa"=>$_POST['nombre_empresa'],
        "cod_empresa"=>$_POST['cod_empresa'],
        "cod_agencia"=>$_POST['cod_agencia'],
        "cod_cliente"=>$_POST['cod_cliente'],
        "nrorif"=>$_POST['nrorif'],
        "cod_zona"=>$_POST['cod_zona'],
        "cod_deposito"=>$_POST['cod_deposito'],
        "recargo"=>($_POST['recargo']=='' ? 0 : $_POST['recargo']),
        "pw_supervisor"=>$_POST['pw_supervisor'],
        "cantidad_por_pagina"=>$_POST['cantidad_por_pagina'],
        "cuentas_compartidas_entre_vendedores"=>$_POST['compartir_mesas'],
        "agrupar_mesas_por_ubicacion"=>$_POST['agrupar_mesas'],
        "usar_subgrupos"=>$_POST['usar_subgrupos'],
        "no_usar_impuestos"=>$_POST['no_usar_impuestos'],
        "no_editar_integrados"=>$_POST['no_editar_integrados'],
        "ocultar_integrados"=>$_POST['ocultar_integrados'],        
        "cm_ancho_precuenta"=>$_POST['cm_ancho_precuenta'],
        "tipo_doc"=>$_POST['tipo_doc'],
        "no_tiene_existencia"=>$_POST['no_tiene_existencia'],
        "minutos_inactividad"=>$_POST['minutos_inactividad']
    );
    
    $file = file_put_contents('../datos_conexion.json', json_encode($array));
    if($file){
        echo "
            <i class='fa fa-check'></i> Datos registrados
            <script>
                window.location.reload();
            </script>
        ";
    }else{
        echo "<i class='fa fa-times'></i> Ha ocurrido un error";
    }

}

if ($_GET['accion'] == "mostrar_datos_conexion") {

    $file = file_get_contents('../datos_conexion.json');
    echo $file;

}