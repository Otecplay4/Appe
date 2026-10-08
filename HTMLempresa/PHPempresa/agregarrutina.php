<?php
header("Content-Type: text/html; charset=utf-8");
session_start();
include "conexion2.php";

// Hallazgo 1: operación administrativa protegida por sesión de entrenador.
if (!isset($_SESSION['entrenador_id']) || ($_SESSION['rol'] ?? null) !== 'entrenador') {
    http_response_code(401);
    echo "<h2>No autorizado</h2>";
    exit;
}

$id_cliente = $_POST['id_cliente'] ?? null;
$nombre_rutina = trim($_POST['nombre_rutina'] ?? '');

if (!ctype_digit((string) $id_cliente) || $nombre_rutina === '') {
    echo "<h2>Faltan datos obligatorios</h2>";
    exit;
}

// Hallazgo 16: validación completa de los arrays recibidos: deben existir,
// tener la misma longitud entre sí y contener valores con sentido.
$musculos = $_POST['musculo'] ?? [];
$ejercicios = $_POST['ejercicio'] ?? [];
$series = $_POST['series'] ?? [];
$repeticiones = $_POST['repeticiones'] ?? [];
// Hallazgo 23: se recupera también el día de cada ejercicio.
$dias = $_POST['dia'] ?? [];

$cantidad = count($ejercicios);
if (
    $cantidad === 0 ||
    count($musculos) !== $cantidad ||
    count($series) !== $cantidad ||
    count($repeticiones) !== $cantidad ||
    count($dias) !== $cantidad
) {
    echo "<h2>Los ejercicios enviados son inconsistentes</h2>";
    exit;
}

for ($i = 0; $i < $cantidad; $i++) {
    $ejercicios[$i] = trim((string) $ejercicios[$i]);
    $musculos[$i] = trim((string) $musculos[$i]);
    if (
        $ejercicios[$i] === '' || $musculos[$i] === '' ||
        !ctype_digit((string) $series[$i]) || (int) $series[$i] < 1 ||
        !ctype_digit((string) $repeticiones[$i]) || (int) $repeticiones[$i] < 1 ||
        !ctype_digit((string) $dias[$i]) || (int) $dias[$i] < 1
    ) {
        echo "<h2>Datos inválidos en el ejercicio #" . ($i + 1) . "</h2>";
        exit;
    }
}

try {
    // Hallazgo 21: las escrituras relacionadas se ejecutan dentro de una
    // transacción, con rollback ante cualquier error.
    $conexion->beginTransaction();

    // Hallazgo 22 (mitigación dentro del modelo de datos existente): para
    // evitar que dos rutinas del mismo cliente con igual nombre se mezclen,
    // se reemplazan los ejercicios previos de una rutina con ese mismo
    // nombre para ese cliente antes de insertar la nueva versión. Una
    // solución completa requeriría separar "rutina" de "ejercicio" con un
    // identificador propio, lo cual implica un cambio de esquema que no
    // forma parte de los archivos entregados en este proyecto.
    $borrarAnterior = $conexion->prepare("DELETE FROM rutina WHERE id_cliente = ? AND nombre_rutina = ?");
    $borrarAnterior->execute([$id_cliente, $nombre_rutina]);

    // Hallazgo 23: se agrega la columna "dia" a la operación de guardado.
    $sql_rutina = "INSERT INTO rutina (id_cliente, nombre_rutina, ejercicio, musculo, serie_repeticion, dia) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql_rutina);

    $sql_update_cliente = "UPDATE cliente SET id_rutina = ? WHERE id = ?";
    $stmt_c = $conexion->prepare($sql_update_cliente);

    $primerIdRutina = null;
    for ($i = 0; $i < $cantidad; $i++) {
        $serie_rep = $series[$i] . "x" . $repeticiones[$i]; // Ej: "4x12"

        $stmt->execute([
            $id_cliente,
            $nombre_rutina,
            $ejercicios[$i],
            $musculos[$i],
            $serie_rep,
            $dias[$i],
        ]);

        if ($primerIdRutina === null) {
            $primerIdRutina = $conexion->lastInsertId();
        }
    }

    // Se asocia al cliente el identificador de la primera fila de la rutina.
    $stmt_c->execute([$primerIdRutina, $id_cliente]);

    $conexion->commit();

    // Hallazgo 25: solo se informa éxito si realmente se guardaron ejercicios.
    echo "<h2>Rutina guardada correctamente (" . $cantidad . " ejercicio(s))</h2>";
    echo "<a href='crearrutina.html'>Volver</a>";

} catch (PDOException $e) {
    $conexion->rollBack();
    // Hallazgo 17: no se devuelve el mensaje interno de la excepción al cliente.
    error_log("agregarrutina.php: " . $e->getMessage());
    http_response_code(500);
    echo "<h2>Error al guardar la rutina</h2>";
}
?>
