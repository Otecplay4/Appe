<?php
header("Content-Type: application/json; charset=utf-8");
session_start();

// Hallazgo 11: se verifica identidad Y rol de entrenador, no solo la presencia
// de un correo en sesión (una sesión de cliente usaba el mismo campo).
if (isset($_SESSION["entrenador_id"]) && ($_SESSION["rol"] ?? null) === "entrenador") {
    echo json_encode(["exito" => true]);
} else {
    echo json_encode(["exito" => false]);
}
?>
