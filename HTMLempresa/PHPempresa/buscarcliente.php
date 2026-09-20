<?php
include "conexion2.php";

$query = $_GET["q"] ?? "";

// Se seleccionan id, nombre y apellido
$sen = $conexion->prepare("SELECT id, nombre, apellido, correo FROM cliente WHERE nombre LIKE ? OR apellido LIKE ? LIMIT 50");
$sen->execute(["%$query%", "%$query%"]);
$resultados = $sen->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resultados);
?>