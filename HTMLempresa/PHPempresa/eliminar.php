<?php
include "conexion2.php";
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];


$sen=$conexion->prepare("DELETE FROM clientes WHERE nombre = ? AND correo = ?");
$sen->execute([$nombre, $correo]);


?>