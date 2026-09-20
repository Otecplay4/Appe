<?php
include "conexion2.php";

$id_cliente = $_POST['id_cliente'] ?? null;
$nombre_rutina = $_POST['nombre_rutina'] ?? null;

if (!$id_cliente || !$nombre_rutina) {
    echo "<h2>Faltan datos obligatorios</h2>";
    exit;
}

try {
    if (isset($_POST['ejercicio']) && is_array($_POST['ejercicio'])) {
        $musculos = $_POST['musculo'];
        $ejercicios = $_POST['ejercicio'];
        $series = $_POST['series'];
        $repeticiones = $_POST['repeticiones'];

        // Incluimos la columna 'ejercicio' en la consulta SQL
        $sql_rutina = "INSERT INTO rutina (id_cliente, nombre_rutina, ejercicio, musculo, serie_repeticion) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_rutina);

        $sql_update_cliente = "UPDATE cliente SET id_rutina = ? WHERE id = ?";
        $stmt_c = $conexion->prepare($sql_update_cliente);

        for ($i = 0; $i < count($ejercicios); $i++) {
            $nombre_ejercicio = $ejercicios[$i];
            $serie_rep = $series[$i] . "x" . $repeticiones[$i]; // Ej: "4x12"
            
            // Se insertan los 5 campos en la misma fila
            $stmt->execute([
                $id_cliente,
                $nombre_rutina,
                $nombre_ejercicio,
                $musculos[$i],
                $serie_rep
            ]);

            // Se asocia el ID de la fila recién insertada con el cliente
            $id_rutina = $conexion->lastInsertId();
            $stmt_c->execute([$id_rutina, $id_cliente]);
        }
    }

    echo "<h2>Rutina guardada correctamente</h2>";
    echo "<a href='crearrutina.html'>Volver</a>";

} catch (PDOException $e) {
    echo "<h2>Error al guardar la rutina: " . $e->getMessage() . "</h2>";
}
?>