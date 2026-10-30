<?php
/**
 * A.P.P.E. — Guardado de la rutina de un cliente.
 *
 * Requisito funcional RF-13 / RF-14 · Caso de uso CU-06 «Consultar y Modificar
 * Rutina de Entrenamiento» (actor Entrenador).

 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    $idEntrenador = requiere_entrenador();

    $idCliente    = id_requerido($_POST['id_cliente'] ?? null, 'Cliente');
    $nombreRutina = texto_requerido($_POST['nombre_rutina'] ?? '', 'Nombre de la rutina', 50);

    // --- El cliente debe existir --------------------------------------------
    $stmt = appe_conexion()->prepare('SELECT id FROM cliente WHERE id = ?');
    $stmt->execute([$idCliente]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('El cliente no existe.');
    }

    // --- Validación de los arrays -------------------------------------------
    $musculos     = $_POST['musculo'] ?? [];
    $ejercicios   = $_POST['ejercicio'] ?? [];
    $series       = $_POST['series'] ?? [];
    $repeticiones = $_POST['repeticiones'] ?? [];
    $dias         = $_POST['dia'] ?? [];

    foreach ([$musculos, $ejercicios, $series, $repeticiones, $dias] as $lista) {
        if (!is_array($lista)) {
            throw new ValidacionException('Los datos de los ejercicios no tienen el formato esperado.');
        }
    }

    $cantidad = count($ejercicios);

    if ($cantidad === 0) {
        throw new ValidacionException('La rutina debe tener al menos un ejercicio.');
    }

    if (
        count($musculos) !== $cantidad
        || count($series) !== $cantidad
        || count($repeticiones) !== $cantidad
        || count($dias) !== $cantidad
    ) {
        throw new ValidacionException('La cantidad de ejercicios, músculos, series, repeticiones y días no coincide.');
    }

    if ($cantidad > 100) {
        throw new ValidacionException('Una rutina no puede tener más de 100 ejercicios.');
    }

    // --- Normalización y validación de cada ejercicio ------------------------
    $filas = [];

    for ($i = 0; $i < $cantidad; $i++) {
        $ejercicio = texto_requerido($ejercicios[$i] ?? '', 'Ejercicio', 200);
        $musculo   = texto_requerido($musculos[$i] ?? '', 'Músculo', 200);
        $serie     = entero_requerido($series[$i] ?? null, 'Series', 1, 99);
        $repeticion = entero_requerido($repeticiones[$i] ?? null, 'Repeticiones', 1, 999);
        $dia       = entero_requerido($dias[$i] ?? null, 'Día', 1, 7);

        $filas[] = [
            'ejercicio'        => $ejercicio,
            'musculo'          => $musculo,
            'serie_repeticion' => $serie . 'x' . $repeticion,
            'dia'              => $dia,
            'orden'            => $i + 1,
        ];
    }

    $pdo = appe_conexion();

    try {
        
        $pdo->beginTransaction();

        //    Se localiza la rutina activa del cliente con ese nombre; si no
        //    existe, se crea una nueva. La identidad es el id_rutina, no el
        //    nombre
        $stmt = $pdo->prepare(
            "SELECT id_rutina
               FROM rutina
              WHERE id_cliente = ? AND nombre = ? AND estado = 'activa'
              ORDER BY id_rutina DESC
              LIMIT 1"
        );
        $stmt->execute([$idCliente, $nombreRutina]);
        $fila = $stmt->fetch();

        if ($fila !== false) {
            $idRutina = (int) $fila['id_rutina'];

            // Se reemplaza el contenido de la rutina.
            $stmt = $pdo->prepare('DELETE FROM rutinatieneejercicio WHERE id_rutina = ?');
            $stmt->execute([$idRutina]);

            $stmt = $pdo->prepare("UPDATE rutina SET id_entrenador = ?, estado = 'activa' WHERE id_rutina = ?");
            $stmt->execute([$idEntrenador, $idRutina]);
        } else {
            // `fecha_inicio` y `fecha_fin` son obligatorias en el esquema: la
            // rutina nueva rige desde hoy y por un mes.
            $stmt = $pdo->prepare(
                "INSERT INTO rutina (nombre, id_cliente, id_entrenador, fecha_inicio, fecha_fin, estado)
                 VALUES (?, ?, ?, ?, ?, 'activa')"
            );
            $stmt->execute([
                $nombreRutina,
                $idCliente,
                $idEntrenador,
                date('Y-m-d'),
                date('Y-m-d', strtotime('+1 month')),
            ]);
            $idRutina = (int) $pdo->lastInsertId();
        }

        //    Cada ejercicio del formulario se resuelve contra el catálogo y se
        //    enlaza con la rutina indicando día y prescripción.
        $stmtBuscar = $pdo->prepare('SELECT id_ejercicio FROM ejercicio WHERE nombre = ? AND musculo = ? LIMIT 1');
        $stmtCrear  = $pdo->prepare('INSERT INTO ejercicio (nombre, musculo) VALUES (?, ?)');
        $stmtEnlazar = $pdo->prepare(
            'INSERT INTO rutinatieneejercicio (id_rutina, id_ejercicio, serie_repeticion, dia, orden)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($filas as $filaEjercicio) {
            $stmtBuscar->execute([$filaEjercicio['ejercicio'], $filaEjercicio['musculo']]);
            $idEjercicio = $stmtBuscar->fetchColumn();

            if ($idEjercicio === false) {
                $stmtCrear->execute([$filaEjercicio['ejercicio'], $filaEjercicio['musculo']]);
                $idEjercicio = (int) $pdo->lastInsertId();
            }

            $stmtEnlazar->execute([
                $idRutina,
                (int) $idEjercicio,
                $filaEjercicio['serie_repeticion'],
                $filaEjercicio['dia'],
                $filaEjercicio['orden'],
            ]);
        }

        
        $stmt = $pdo->prepare('UPDATE cliente SET id_rutina = ?, id_entrenador = ? WHERE id = ?');
        $stmt->execute([$idRutina, $idEntrenador, $idCliente]);

        $pdo->commit();

        auditar('alta_rutina', 'rutina', $idRutina, $nombreRutina . ' (' . $cantidad . ' ejercicios)');

        responder_exito('Rutina guardada correctamente (' . $cantidad . ' ejercicio(s)).', [
            'id_rutina' => $idRutina,
            'id_cliente' => $idCliente,
            'ejercicios' => $cantidad,
        ], 201);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}, 'agregarrutina.php');
