<?php
include "conexion2.php";
$query =  $_GET["q"] ?? "";
$sen = $conexion->prepare("SELECT nombre, correo FROM clientes WHERE nombre LIKE ? LIMIT 50");
$sen->execute(["%$query%"]);
$resultados = $sen->fetchALL(PDO::FETCH_ASSOC);
echo json_encode($resultados);

?>