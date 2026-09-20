<?php
include "conexion2.php";

$id = $_POST['id'];

// Consulta SQL para eliminar
$sql = "DELETE FROM cliente WHERE id = ?";
$sentencia = $conexion->prepare($sql);
$sentencia->execute([$id]);

echo json_encode(["exito" => true]);
?>