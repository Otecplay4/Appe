<?php
/**
 * A.P.P.E. — Alta de un plan de suscripción.
 *
 * Requisito funcional RF-07 «gestionar los planes y suscripciones del
 * gimnasio con su duración, beneficios y precio» · Caso de uso CU-03.
 *
 * El precio se desglosa en `planprecio` (semanal / mensual / trimestral /
 * anual) y `plan.precio` guarda el precio de referencia.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/planes_comun.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $nombre      = texto_requerido($_POST['nombre_plan'] ?? $_POST['nombre'] ?? '', 'Nombre del plan', 100);
    $descripcion = texto_opcional($_POST['descripcion'] ?? '', 'Descripción', 200);
    $beneficio   = texto_opcional($_POST['beneficio'] ?? '', 'Beneficio', 50);
    $duracion    = texto_opcional($_POST['duracion'] ?? '', 'Duración', 50);
    $tipoPlan    = texto_opcional($_POST['tipo_de_plan'] ?? '', 'Tipo de plan', 60);
    $fechaInicio = texto_opcional($_POST['fecha_inicio'] ?? '', 'Fecha de inicio', 50);
    $fechaFin    = texto_opcional($_POST['fecha_fin'] ?? '', 'Fecha de fin', 50);

    $errores = [];
    $precios = leer_precios_plan($_POST, $errores);

    if ($errores !== []) {
        throw new ValidacionException(implode(' ', $errores));
    }

    if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
        throw new ValidacionException('Debe seleccionar una imagen del plan.');
    }

    $directorio = directorio_imagenes_plan();

    if (!is_dir($directorio) && !@mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new ValidacionException('No se pudo preparar el directorio de imágenes.');
    }

    $pdo = appe_conexion();

    $precioReferencia = (float) reset($precios);

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO plan (id_gimnasio, nombre, descripcion, beneficio, precio, duracion,
                               tipo_de_plan, fecha_inicio, fecha_fin, extension, activo)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, \'jpg\', 1)'
        );
        $stmt->execute([
            $nombre,
            $descripcion === '' ? null : $descripcion,
            $beneficio === '' ? null : $beneficio,
            $precioReferencia,
            $duracion === '' ? null : $duracion,
            $tipoPlan === '' ? null : $tipoPlan,
            $fechaInicio === '' ? null : $fechaInicio,
            $fechaFin === '' ? null : $fechaFin,
        ]);

        $idPlan = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO planprecio (id_plan, periodo, precio) VALUES (?, ?, ?)');

        foreach ($precios as $periodo => $precio) {
            $stmt->execute([$idPlan, $periodo, $precio]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    // La imagen se guarda después de la confirmación de la base de datos; si
    // falla, se deshace el alta (hallazgo 24).
    try {
        $extension = guardar_imagen($_FILES['archivo'], $directorio, (string) $idPlan);

        $stmt = $pdo->prepare('UPDATE plan SET extension = ? WHERE id_plan = ?');
        $stmt->execute([$extension, $idPlan]);
    } catch (Throwable $e) {
        $pdo->prepare('DELETE FROM plan WHERE id_plan = ?')->execute([$idPlan]);
        throw $e;
    }

    auditar('alta_plan', 'plan', $idPlan, $nombre);

    responder_exito('Plan creado correctamente.', [
        'id_plan' => $idPlan,
        'nombre'  => $nombre,
        'precios' => $precios,
    ], 201);
}, 'agregarplan.php');
