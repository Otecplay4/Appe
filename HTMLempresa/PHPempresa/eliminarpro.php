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

// Hallazgo 15: la eliminación ya no se realiza por GET.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["exito" => false, "error" => "Método no permitido"]);
    exit;
}

$idCrudo = $_POST["id"] ?? "";

// Hallazgo 7 (path traversal): se exige un identificador puramente numérico.
// Nunca se concatena la entrada del usuario directamente en la ruta del archivo.
if (!ctype_digit((string) $idCrudo)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "ID inválido"]);
    exit;
}
$id = (int) $idCrudo;

// El identificador se resuelve contra un registro autorizado, no se confía en texto libre.
$sen = $conexion->prepare("SELECT id FROM imagenes WHERE id = ?");
$sen->execute([$id]);
if (!$sen->fetch()) {
    http_response_code(404);
    echo json_encode(["exito" => false, "error" => "Producto no encontrado"]);
    exit;
}

$directorioPermitido = realpath(__DIR__ . "/img");
$ruta = $directorioPermitido . DIRECTORY_SEPARATOR . $id . ".jpg";

// Comprobación final de que la ruta resuelta sigue dentro del directorio permitido.
$rutaReal = realpath($ruta);
$archivoBorrado = true;
if ($rutaReal !== false) {
    if (strpos($rutaReal, $directorioPermitido . DIRECTORY_SEPARATOR) !== 0) {
        http_response_code(400);
        echo json_encode(["exito" => false, "error" => "Ruta inválida"]);
        exit;
    }
    $archivoBorrado = unlink($rutaReal);
}

// Hallazgo 24: se elimina también el registro de la base, para no dejar
// listados obsoletos ni enlaces a imágenes inexistentes.
$conexion->prepare("DELETE FROM imagenes WHERE id = ?")->execute([$id]);

echo json_encode(["exito" => true, "archivo_borrado" => $archivoBorrado]);
?>
