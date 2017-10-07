<?php
$servername = "127.0.0.1:50147";
$username = "root";
$password = "root";
$dbname = "donaciones";

try {
	$DBH = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$query = "SELECT nombre, apellido, dni, email, usu, contrasena FROM personas";
	$STH = $DBH->prepare($query);
	$STH->setFetchMode(PDO::FETCH_ASSOC);
	$STH->execute();
	
	$resultados = $STH->fetchAll();
	echo json_encode($resultados);
	
} catch (PDOException $e) {
	echo "algo salió mal";
}

$DBH = null;

?>
