<?php
include "conexion2.php";
$correo = $_POST['correo'];
$cedula = $_POST['cedula'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$c = $_POST['contraseña'];
//encriptamos contraseña
$contraseña= password_hash($c, PASSWORD_DEFAULT);
$nombre = $_POST['nombre'];




$sen=$conexion->prepare("INSERT INTO entrenador (cedula, telefono, direccion, nombre, correo, contraseña) values (?, ?, ?, ?, ?, ?)");
$sen->execute([$cedula, $telefono, $direccion, $nombre, $correo, $contraseña]);

echo json_encode(["exito" => true]);

?>