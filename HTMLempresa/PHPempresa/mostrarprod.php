<?php
header("Content-Type: application/json; charset=utf-8");
session_start();
include "conexion2.php";

if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

$sen = $conexion->prepare("SELECT * FROM imagenes");
$sen->execute();

// Hallazgo 26: fetchALL -> fetchAll.
$r = $sen->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($r);
?>
