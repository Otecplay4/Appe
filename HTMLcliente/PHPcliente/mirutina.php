<?php
// Hallazgo 27: no debe haber ninguna salida antes de session_start().
session_start();

header("Content-Type: application/json; charset=utf-8");

include "conexion.php";

try {
    // Hallazgo 4 (IDOR): la identidad del cliente se toma SIEMPRE de la sesión
    // autenticada. Ya no se acepta un id_cliente llegado por $_GET.
    if (!isset($_SESSION['cliente_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No se especificó un cliente']);
        exit;
    }
    $id_cliente = $_SESSION['cliente_id'];

    $sql = "SELECT nombre_rutina, musculo, ejercicio, serie_repeticion 
            FROM rutina
            WHERE id_cliente = ? 
              AND nombre_rutina = (
                  SELECT nombre_rutina 
                  FROM rutina 
                  WHERE id_rutina = (SELECT id_rutina FROM cliente WHERE id = ?)
              )
            ORDER BY id_rutina ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id_cliente, $id_cliente]);

    $rutina = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rutina);

} catch (Exception $e) {
    // Hallazgo 17: no se devuelve el mensaje interno de la excepción al cliente.
    error_log("mirutina.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo obtener la rutina.']);
}
?>
