<?php
include "conexion.php";
$correo = $_POST['correo'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$c = $_POST['contraseña'];
//encriptamos contraseña
$contraseña= password_hash($c, PASSWORD_DEFAULT);



$sen=$conexion->prepare("INSERT INTO cliente (apellido, nombre, correo, contraseña) values (?, ?, ?, ?)");
$sen->execute([$apellido, $nombre, $correo, $contraseña]);

echo json_encode(["exito" => true]);

?>