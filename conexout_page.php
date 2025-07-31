<!DOCTYPE html>
<?php
	session_start();
	session_destroy();
?>
<html lang="en">
	<head>
		<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1"/>
		<link rel="stylesheet" type="text/css" href="css/bootstrap.css" />
	</head>
<body>
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<a class="navbar-brand" href="https://premium-soft.com/">Premium Soft</a>
		</div>
	</nav>
	<div class="col-md-3"></div>
	<div class="col-md-6 well">
		<h3 class="text-primary">Error de Conexión...</h3>
		<hr style="border-top:1px dotted #ccc;"/>
		<center><h3>Se ha perdido la conexión con la Base de Datos</h3></center>
		<a href="index.php">Volver a acceder</a>
	</div>
</body>
</html>