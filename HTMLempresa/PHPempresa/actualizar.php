<?php
include "conexion2.php";

// Recibimos los datos del formulario
$id = $_POST['id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];

// Consulta SQL para actualizar los datos
$sql = "UPDATE cliente SET nombre = ?, apellido = ?, correo = ? WHERE id = ?";
$sentencia = $conexion->prepare($sql);
$sentencia->execute([$nombre, $apellido, $correo, $id]);

echo json_encode(["exito" => true]);
?>
