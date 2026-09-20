<?php
include "conexion2.php";

// Consulta para traer los datos solicitados
$sql = "SELECT id, nombre, apellido, correo, contraseña FROM cliente";
$sentencia = $conexion->prepare($sql);
$sentencia->execute();

// Guardamos los resultados en un array
$clientes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Lo enviamos a JavaScript en formato JSON
echo json_encode($clientes);
?>