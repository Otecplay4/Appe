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

// Hallazgo 15: no se modifica estado con GET.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "error" => "Método no permitido"]);
    exit;
}

// Hallazgo 28: el endpoint ahora sí implementa la actualización, en vez de
// limitarse a leer un parámetro sin usarlo.
$id = $_POST["id"] ?? null;
if (!ctype_digit((string) $id)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "ID inválido"]);
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
if ($nombre === "") {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "El nombre es obligatorio"]);
    exit;
}

$sen = $conexion->prepare("UPDATE imagenes SET nombre = ? WHERE id = ?");
$sen->execute([$nombre, $id]);

if ($sen->rowCount() === 0) {
    $existe = $conexion->prepare("SELECT 1 FROM imagenes WHERE id = ?");
    $existe->execute([$id]);
    if (!$existe->fetch()) {
        http_response_code(404);
        echo json_encode(["exito" => false, "error" => "Producto no encontrado"]);
        exit;
    }
}

echo json_encode(["exito" => true]);
?>
