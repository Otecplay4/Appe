<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion.php";

// Hallazgo 14: límite simple de intentos por sesión (sin infraestructura externa
// de rate-limiting no es una defensa completa, pero evita la adivinación rápida).
$ahora = time();
$intentos = $_SESSION['login_intentos_cliente'] ?? 0;
$bloqueadoHasta = $_SESSION['login_bloqueo_cliente'] ?? 0;

if ($ahora < $bloqueadoHasta) {
    echo json_encode([
        "exito" => false,
        "mensaje" => "Demasiados intentos. Intentá nuevamente en unos minutos."
    ]);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';

if ($correo === '' || $contraseña === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["exito" => false, "mensaje" => "Correo o contraseña incorrectos"]);
    exit;
}

// 1. Buscamos al usuario SOLO por su correo
$sen = $conexion->prepare("SELECT * FROM cliente WHERE correo = ?");
$sen->execute([$correo]);
$usuario = $sen->fetch(PDO::FETCH_ASSOC);

// 2. Verificamos si el usuario existe Y si la contraseña coincide con el hash
if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
    // Hallazgo 10: regeneramos el identificador de sesión al autenticar.
    session_regenerate_id(true);

    // Hallazgo 12: limpiamos cualquier estado previo (por ejemplo de una sesión
    // de entrenador) antes de establecer la identidad de cliente.
    $_SESSION = [];
    $_SESSION["rol"] = "cliente";
    $_SESSION["cliente_id"] = $usuario["id"];
    $_SESSION["cliente_correo"] = $usuario["correo"];

    unset($_SESSION['login_intentos_cliente'], $_SESSION['login_bloqueo_cliente']);

    echo json_encode([
        "exito" => true,
        "mensaje" => "Login correcto",
        "id" => $usuario["id"]
    ]);
} else {
    // Hallazgo 13: mensaje genérico, sin distinguir correo inexistente de contraseña incorrecta
    $_SESSION['login_intentos_cliente'] = $intentos + 1;
    if ($_SESSION['login_intentos_cliente'] >= 5) {
        $_SESSION['login_bloqueo_cliente'] = $ahora + 60; // 1 minuto de espera
        $_SESSION['login_intentos_cliente'] = 0;
    }

    echo json_encode([
        "exito" => false,
        "mensaje" => "Correo o contraseña incorrectos",
    ]);
}
?>
