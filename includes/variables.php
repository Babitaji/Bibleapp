<?php

	//database configuration
	$host 		= "localhost";
	$user 		= "root";
	$pass 		= "";
	$database 	= "material_wallpaper";
	
	$connect = new mysqli($host, $user, $pass,$database) or die("Error : ".mysql_error());
	
?>