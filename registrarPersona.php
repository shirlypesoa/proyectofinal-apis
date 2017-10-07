<?php
	$con = mysqli_init();
	$db_name = "localdb";
	$mysql_user = "azure";//PaquinBorowicz
	$mysql_pass = "6#vWHD_$";//micaIERU
	$server_name = "127.0.0.1:52110";
	$con = mysqli_real_connect($server_name, $mysql_user, $mysql_pass, $db_name);
	
	$nombre = $POST_["nombre"];
	$apellido = $POST_["apellido"];
	$dni = $POST_["dni"];
	$email = $POST_["email"];
	$usu = $POST_["usu"];
	$contrasena = $POST_["contrasena"];

	$sql_query = "INSERT INTO personas (nombre, apellido, dni, email, usu, contrasena) VALUES ($nombre, $apellido, $dni, $email, $usu, $contrasena)";
	mysqli_query($con, $sql_query);
	$result = mysqli_query($con, $sql_query);
	if (mysqli_num_rows($result)>0)
	{
	  echo "Todo salió bien.";
	}
	else
	{
	  echo "Algo salió mal.";
	}
	mysqli_close($con);
?>