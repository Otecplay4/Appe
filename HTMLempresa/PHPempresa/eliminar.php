<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

// Hallazgo 1: operación administrativa protegida por sesión de entrenador.
if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo json_encode(["exito" => false, "error" => "No autorizado"]);
    exit;
}

$id = $_POST['id'] ?? null;
if (!ctype_digit((string) $id)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "ID inválido"]);
    exit;
}

// Consulta SQL para eliminar
$sql = "DELETE FROM cliente WHERE id = ?";
$sentencia = $conexion->prepare($sql);
$sentencia->execute([$id]);

// Hallazgo 25: se informa éxito real según si existía el objetivo.
if ($sentencia->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(["exito" => false, "error" => "Cliente no encontrado"]);
    exit;
}

echo json_encode(["exito" => true]);
?>
