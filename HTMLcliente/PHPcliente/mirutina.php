
<?php
session_start();

include "conexion.php";

try {
    // Obtener el ID del cliente por sesión o por GET
    $id_cliente = $_SESSION['id_cliente'] ?? $_SESSION['id'] ?? $_GET['id_cliente'] ?? null;

    if (!$id_cliente) {
        echo json_encode(['error' => 'No se especificó un cliente']);
        exit;
    }

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

    // Faltaba imprimir el resultado en JSON para el frontend
    echo json_encode($rutina);

// Faltaba cerrar la llave del try
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>