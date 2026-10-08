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

// Recibimos los datos del formulario
$id = $_POST['id'] ?? null;
if (!ctype_digit((string) $id)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "ID inválido"]);
    exit;
}

// Hallazgo 19: contrato parcial. Solo se actualizan los campos realmente
// enviados; los campos ausentes conservan su valor anterior (COALESCE).
$nombre = isset($_POST['nombre']) && $_POST['nombre'] !== '' ? $_POST['nombre'] : null;
$apellido = isset($_POST['apellido']) && $_POST['apellido'] !== '' ? $_POST['apellido'] : null;
$correo = isset($_POST['correo']) && $_POST['correo'] !== '' ? $_POST['correo'] : null;

$sql = "UPDATE cliente SET
            nombre = COALESCE(?, nombre),
            apellido = COALESCE(?, apellido),
            correo = COALESCE(?, correo)
        WHERE id = ?";
$sentencia = $conexion->prepare($sql);
$sentencia->execute([$nombre, $apellido, $correo, $id]);

// Hallazgo 25: se informa éxito real, distinguiendo "sin cambios" de "no existe".
if ($sentencia->rowCount() === 0) {
    $existe = $conexion->prepare("SELECT 1 FROM cliente WHERE id = ?");
    $existe->execute([$id]);
    if (!$existe->fetch()) {
        http_response_code(404);
        echo json_encode(["exito" => false, "error" => "Cliente no encontrado"]);
        exit;
    }
    // Existe pero no hubo cambios (los valores enviados eran iguales a los actuales).
}

echo json_encode(["exito" => true]);
?>
