<?php
include("modelo_conexion.php");

class crud extends conexion{
	function __construct(){
	}
	function general_query($bd_conexion,$query){

		return parent::query($bd_conexion,$query);

	}
	function select($bd_conexion,$select,$from,$where,$join,$order){
		
		$query = "SELECT ".$select." FROM ".$from." ";

		if($join != ""){
			$query .= " ".$join." ";
		}		

		if($where != ""){
			$query .= " WHERE ".$where." ";
		}		

		if($order != ""){
			$query .= " ".$order." ";
		}	

		return parent::query($bd_conexion,$query);		

	}
	function insert($bd_conexion,$into,$columnas,$values){

		$query = "
			INSERT INTO $into ($columnas) VALUES ($values) 
		";

		return parent::query($bd_conexion,$query);

	}
	function replace($bd_conexion,$into,$columnas,$values){

		$query = "
			REPLACE INTO $into ($columnas) VALUES ($values) 
		";

		return parent::query($bd_conexion,$query);

	}	
	function delete($bd_conexion,$from,$where){

		$query = "
			DELETE FROM $from WHERE $where
		";

		return parent::query($bd_conexion,$query);

	}
	function update($bd_conexion,$from,$values,$where){

		$query = "
			UPDATE ".$from." SET ".$values." WHERE ".$where."
		";

		return parent::query($bd_conexion,$query);

	}	
	function add_field($bd_conexion,$table,$field){

		$query = "ALTER TABLE ".$table." ADD ".$field." ";

		return parent::query($bd_conexion,$query);

	}
}


?>