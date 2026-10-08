<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

// Hallazgo 1 y 3: requiere sesión de entrenador autenticado.
if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

// Hallazgo 3: ya no se selecciona ni se devuelve el campo "contraseña" (hash).
$sql = "SELECT id, nombre, apellido, correo FROM cliente";
$sentencia = $conexion->prepare($sql);
$sentencia->execute();

$clientes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($clientes);
?>
