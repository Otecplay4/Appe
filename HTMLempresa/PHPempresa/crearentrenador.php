<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

// Hallazgo 2: el alta de entrenadores ya no es pública. Solo un entrenador
// ya autenticado puede dar de alta a otro (no existe en el proyecto original
// un rol de "administrador" separado para implementar invitaciones verificadas;
// esta es la restricción mínima disponible con el modelo de datos entregado).
if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$c = $_POST['contraseña'] ?? '';

// Hallazgo 16: la validación de seguridadentrenador.php se integra realmente
// en el alta, en vez de existir como un endpoint suelto sin usar.
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["exito" => false, "mensaje" => "Formato de correo incorrecto"]);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_ ]+$/', $nombre) || $nombre === '') {
    echo json_encode(["exito" => false, "mensaje" => "El nombre de usuario no es válido. Solo se permiten letras, números y guiones bajos."]);
    exit;
}
if ($telefono === '' || $cedula === '') {
    echo json_encode(["exito" => false, "mensaje" => "Teléfono y cédula son obligatorios."]);
    exit;
}
if (strlen($c) < 8) {
    echo json_encode(["exito" => false, "mensaje" => "La contraseña debe tener al menos 8 caracteres."]);
    exit;
}

// encriptamos contraseña
$contraseña = password_hash($c, PASSWORD_DEFAULT);

try {
    $sen = $conexion->prepare("INSERT INTO entrenador (telefono, nombre, correo, contraseña, cedula) VALUES (?, ?, ?, ?, ?)");
    $sen->execute([$telefono, $nombre, $correo, $contraseña, $cedula]);
    echo json_encode(["exito" => true]);
} catch (PDOException $e) {
    // Hallazgo 17: no se devuelve el detalle interno de la excepción.
    error_log("crearentrenador.php: " . $e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "No se pudo crear la cuenta."]);
}
?>
