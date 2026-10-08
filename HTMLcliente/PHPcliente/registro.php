<?php
header("Content-Type: application/json; charset=utf-8");
include "conexion.php";

// Hallazgo 16: validación en el servidor de campos obligatorios, formato y longitud.
$correo = trim($_POST['correo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';

$errores = [];
if ($nombre === '' || mb_strlen($nombre) > 100) {
    $errores[] = "El nombre es obligatorio.";
}
if ($apellido === '' || mb_strlen($apellido) > 100) {
    $errores[] = "El apellido es obligatorio.";
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo no es válido.";
}
if (strlen($contraseña) < 8) {
    $errores[] = "La contraseña debe tener al menos 8 caracteres.";
}

if (!empty($errores)) {
    echo json_encode(["exito" => false, "mensaje" => implode(" ", $errores)]);
    exit;
}

// encriptamos contraseña
$contraseñaHash = password_hash($contraseña, PASSWORD_DEFAULT);

try {
    $sen = $conexion->prepare("INSERT INTO cliente (apellido, nombre, correo, contraseña) VALUES (?, ?, ?, ?)");
    $sen->execute([$apellido, $nombre, $correo, $contraseñaHash]);
    echo json_encode(["exito" => true]);
} catch (PDOException $e) {
    // Hallazgo 17: no se devuelve el detalle interno de la excepción al cliente.
    error_log("registro.php: " . $e->getMessage());
    echo json_encode(["exito" => false, "mensaje" => "No se pudo completar el registro."]);
}
?>
