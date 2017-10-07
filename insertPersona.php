<?php
$servername = "127.0.0.1:50147";
$username = "root";
$password = "root";
$dbname = "donaciones";

$nombre;
$apellido;
$dni;
$email;
$usu;
$contrasena;

try 
{
	$DBH = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$query = "INSERT INTO personas SET nombre =:nombre, apellido=:apellido, dni=:dni, email=:email, usu=:usu, contrasena=:contrasena";
	$STH = $DBH->prepare($query);
	$STH->setFetchMode(PDO::FETCH_ASSOC);

	$params = array(
	":nombre" => $nombre,
	":apellido" =>  $apellido,
	":dni" => $dni,
	":email" => $email, 
	":usu" => $usu,
	":contrasena" => $contrasena
	);
	
	$STH->execute($params);
	
	echo "todo bien";
	
} 
catch (PDOException $e) 
{
	echo "algo salió mal";
}

$DBH = null;

?>
