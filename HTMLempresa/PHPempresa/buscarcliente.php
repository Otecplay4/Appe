<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

// Hallazgo 3: requiere sesión de entrenador autenticado (antes era anónimo).
if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$query = $_GET["q"] ?? "";

// Se seleccionan id, nombre, apellido y correo (sin contraseña)
$sen = $conexion->prepare("SELECT id, nombre, apellido, correo FROM cliente WHERE nombre LIKE ? OR apellido LIKE ? LIMIT 50");
$sen->execute(["%$query%", "%$query%"]);
$resultados = $sen->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resultados);
?>
