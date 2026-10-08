<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

// Hallazgo 14: límite simple de intentos por sesión.
$ahora = time();
$intentos = $_SESSION['login_intentos_entrenador'] ?? 0;
$bloqueadoHasta = $_SESSION['login_bloqueo_entrenador'] ?? 0;

if ($ahora < $bloqueadoHasta) {
    echo json_encode([
        "exito" => false,
        "mensaje" => "Demasiados intentos. Intentá nuevamente en unos minutos.",
    ]);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';

$sen = $conexion->prepare("SELECT * FROM entrenador WHERE correo = ?");
$sen->execute([$correo]);
$usuario = $sen->fetch(PDO::FETCH_ASSOC);

// Hallazgo 13: respuesta genérica y consistente ante credenciales inválidas,
// sin distinguir "correo inexistente" de "contraseña incorrecta".
if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
    // Hallazgo 10: regeneramos el identificador de sesión al autenticar.
    session_regenerate_id(true);

    // Hallazgo 12: reconstruimos el estado de sesión y NO guardamos el hash
    // de la contraseña en $_SESSION.
    $_SESSION = [];
    $_SESSION["rol"] = "entrenador";
    $_SESSION["entrenador_correo"] = $usuario["correo"];
    $_SESSION["entrenador_id"] = $usuario["id_entrenador"];

    unset($_SESSION['login_intentos_entrenador'], $_SESSION['login_bloqueo_entrenador']);

    echo json_encode([
        "exito" => true,
        "mensaje" => "Login correcto",
    ]);
} else {
    $_SESSION['login_intentos_entrenador'] = $intentos + 1;
    if ($_SESSION['login_intentos_entrenador'] >= 5) {
        $_SESSION['login_bloqueo_entrenador'] = $ahora + 60;
        $_SESSION['login_intentos_entrenador'] = 0;
    }

    echo json_encode([
        "exito" => false,
        "mensaje" => "Correo o contraseña incorrectos",
    ]);
}
?>
