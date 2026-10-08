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

$nombre = trim($_POST["nombre"] ?? "");
if ($nombre === "") {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "Falta el nombre del producto"]);
    exit;
}

// Hallazgo 8: se valida el código de error de la carga, el tamaño y el
// contenido real del archivo (no solo su nombre/extensión).
if (!isset($_FILES["archivo"]) || $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "Error al subir el archivo"]);
    exit;
}

$archivoTmp = $_FILES["archivo"]["tmp_name"];
$limiteBytes = 5 * 1024 * 1024; // 5 MB, límite propio de la aplicación
if ($_FILES["archivo"]["size"] > $limiteBytes) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "El archivo supera el tamaño máximo permitido"]);
    exit;
}

$info = @getimagesize($archivoTmp);
$tiposPermitidos = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
if ($info === false || !in_array($info[2], $tiposPermitidos, true)) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "El archivo no es una imagen JPG/PNG válida"]);
    exit;
}

// Hallazgo 8: se recodifica la imagen en vez de mover el archivo subido tal cual,
// para no almacenar/servir contenido arbitrario disfrazado de imagen.
$imagen = ($info[2] === IMAGETYPE_PNG) ? imagecreatefrompng($archivoTmp) : imagecreatefromjpeg($archivoTmp);
if ($imagen === false) {
    http_response_code(400);
    echo json_encode(["exito" => false, "error" => "No se pudo procesar la imagen"]);
    exit;
}

$directorio = __DIR__ . "/img";
if (!is_dir($directorio)) {
    mkdir($directorio, 0755, true);
}

// Hallazgo 24: primero se guarda el archivo y se confirma el movimiento;
// recién si eso tiene éxito se inserta el registro en la base. Si el registro
// fallara después, se revierte el archivo como acción compensatoria.
$nombreTemporalUnico = uniqid('img_', true) . ".jpg";
$rutaTemporal = $directorio . "/" . $nombreTemporalUnico;

if (!imagejpeg($imagen, $rutaTemporal, 85)) {
    imagedestroy($imagen);
    http_response_code(500);
    echo json_encode(["exito" => false, "error" => "No se pudo guardar la imagen"]);
    exit;
}
imagedestroy($imagen);

try {
    $sen = $conexion->prepare("INSERT INTO imagenes (nombre) VALUES (?)");
    $sen->execute([$nombre]);
    $id = $conexion->lastInsertId();

    $rutaFinal = $directorio . "/" . $id . ".jpg";
    if (!rename($rutaTemporal, $rutaFinal)) {
        throw new RuntimeException("No se pudo renombrar la imagen al id final");
    }

    echo json_encode(["exito" => true, "id" => $id]);
} catch (Throwable $e) {
    // Acción compensatoria: si falla la base o el renombrado, no dejamos archivos huérfanos.
    if (file_exists($rutaTemporal)) {
        unlink($rutaTemporal);
    }
    error_log("img.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["exito" => false, "error" => "No se pudo registrar la imagen"]);
}
?>
