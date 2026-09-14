<?php
include "conexion.php";
$correo = $_POST['correo'];
$nombre = $_POST['nombre'];
$c = $_POST['contraseña'];
//encriptamos contraseña
$contraseña= password_hash($c, PASSWORD_DEFAULT);



$sen=$conexion->prepare("INSERT INTO clientes (nombre, correo, contraseña) values (?, ?, ?)");
$sen->execute([$nombre, $correo, $contraseña]);

echo json_encode(["exito" => true]);

?>