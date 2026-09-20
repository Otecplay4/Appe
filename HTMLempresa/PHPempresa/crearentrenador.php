<?php
include "conexion2.php";
$correo = $_POST['correo'];
$nombre = $_POST['nombre'];
$telefono = $_POST['telefono'];
$cedula = $_POST['cedula'];
$c = $_POST['contraseña'];
//encriptamos contraseña
$contraseña= password_hash($c, PASSWORD_DEFAULT);



$sen=$conexion->prepare("INSERT INTO entrenador (telefono, nombre, correo, contraseña, cedula) values (?, ?, ?, ?, ?)");
$sen->execute([$telefono, $nombre, $correo, $contraseña, $cedula]);

echo json_encode(["exito" => true]);

?>