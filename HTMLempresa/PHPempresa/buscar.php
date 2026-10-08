<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$query = $_GET["q"] ?? "";

// Hallazgo 30: se unifica el nombre de tabla a "cliente" (singular), igual que
// en el resto de los endpoints; no existe una tabla "clientes" en el esquema.
// Hallazgo 18/26: fetchALL -> fetchAll (PDO es sensible a mayúsculas en este método).
$sen = $conexion->prepare("SELECT nombre, correo FROM cliente WHERE nombre LIKE ? LIMIT 50");
$sen->execute(["%$query%"]);
$resultados = $sen->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($resultados);
?>
