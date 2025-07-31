	
	<div id="busqueda">				
		Buscar por 
		<select id="buscar_por"><option>Licencia</option><option>Empresa</option><option>Email</option></select>
		<input type="text" class="form-control" id="palabra" placeholder="Buscar..."/>
		<button class="btn btn-primary" onclick="ver_empresas()">Buscar</button>
	</div>

	<div style="text-align:center">
		PremiumSoft © <?php echo date('d/m/Y'); ?> | Privacy Policy | <a onclick="launchIntoFullscreen(document.documentElement)">Pantalla Completa</a>
	</div>	

	<br><br>
	<div id="resultados"></div>	