<?php
// Hallazgo 28: este archivo era en realidad un documento OpenDocument (.odt)
// con extensión .php, y no implementaba ningún endpoint. No hay ninguna
// referencia a "rutina.php" desde el frontend (el endpoint real de rutinas es
// agregarrutina.php para guardarlas y PHPcliente/mirutina.php para leerlas),
// así que se normaliza a un archivo PHP válido mas no se le añade lógica que
// no estaba especificada en ningún otro lugar del proyecto.
http_response_code(404);
header("Content-Type: application/json; charset=utf-8");
echo json_encode(["error" => "Endpoint no implementado"]);
?>
