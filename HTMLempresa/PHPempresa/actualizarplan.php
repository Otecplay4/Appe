<?php
/**
 * A.P.P.E. — Modificación de un plan de suscripción.
 *
 * Requisito funcional RF-07 · Caso de uso CU-03.
 *
 * La imagen es opcional: si no se envía una nueva, se conserva la actual.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/planes_comun.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idPlan = id_requerido($_POST['id'] ?? null, 'Plan');

    $stmt = appe_conexion()->prepare('SELECT id_plan, extension FROM plan WHERE id_plan = ?');
    $stmt->execute([$idPlan]);
    $plan = $stmt->fetch();

    if ($plan === false) {
        throw new NoEncontradoException('El plan no existe.');
    }

    $campos = [];
    $parametros = [];

    if (isset($_POST['nombre_plan']) || isset($_POST['nombre'])) {
        $campos[] = 'nombre = ?';
        $parametros[] = texto_requerido($_POST['nombre_plan'] ?? $_POST['nombre'], 'Nombre del plan', 100);
    }

    if (isset($_POST['descripcion'])) {
        $descripcion = texto_opcional($_POST['descripcion'], 'Descripción', 200);
        $campos[] = 'descripcion = ?';
        $parametros[] = $descripcion === '' ? null : $descripcion;
    }

    if (isset($_POST['beneficio'])) {
        $beneficio = texto_opcional($_POST['beneficio'], 'Beneficio', 50);
        $campos[] = 'beneficio = ?';
        $parametros[] = $beneficio === '' ? null : $beneficio;
    }

    if (isset($_POST['duracion'])) {
        $duracion = texto_opcional($_POST['duracion'], 'Duración', 50);
        $campos[] = 'duracion = ?';
        $parametros[] = $duracion === '' ? null : $duracion;
    }

    if (isset($_POST['tipo_de_plan'])) {
        $tipo = texto_opcional($_POST['tipo_de_plan'], 'Tipo de plan', 60);
        $campos[] = 'tipo_de_plan = ?';
        $parametros[] = $tipo === '' ? null : $tipo;
    }

    if (isset($_POST['activo'])) {
        $campos[] = 'activo = ?';
        $parametros[] = (int) enum_requerido($_POST['activo'], ['0', '1'], 'Estado');
    }

    // Precios
    $errores = [];
    $precios = leer_precios_plan($_POST, $errores);

    if ($errores !== []) {
        throw new ValidacionException(implode(' ', $errores));
    }

    $pdo = appe_conexion();

    $pdo->beginTransaction();

    try {
        if ($campos !== []) {
            $parametros[] = $idPlan;
            $stmt = $pdo->prepare('UPDATE plan SET ' . implode(', ', $campos) . ' WHERE id_plan = ?');
            $stmt->execute($parametros);
        }

        $stmt = $pdo->prepare('DELETE FROM planprecio WHERE id_plan = ?');
        $stmt->execute([$idPlan]);

        $stmt = $pdo->prepare('INSERT INTO planprecio (id_plan, periodo, precio) VALUES (?, ?, ?)');
        $precioReferencia = (float) reset($precios);

        foreach ($precios as $periodo => $precio) {
            $stmt->execute([$idPlan, $periodo, $precio]);
        }

        $stmt = $pdo->prepare('UPDATE plan SET precio = ? WHERE id_plan = ?');
        $stmt->execute([$precioReferencia, $idPlan]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    // Imagen opcional.
    $extension = (string) $plan['extension'];

    if (isset($_FILES['archivo']) && is_array($_FILES['archivo'])
        && isset($_FILES['archivo']['error'])
        && (int) $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
        borrar_imagen_plan($idPlan);
        $extension = guardar_imagen($_FILES['archivo'], directorio_imagenes_plan(), (string) $idPlan);

        $stmt = $pdo->prepare('UPDATE plan SET extension = ? WHERE id_plan = ?');
        $stmt->execute([$extension, $idPlan]);
    }

    auditar('modificacion_plan', 'plan', $idPlan, implode(', ', $campos));

    responder_exito('Plan actualizado correctamente.', [
        'id_plan' => $idPlan,
        'precios' => $precios,
    ]);
}, 'actualizarplan.php');
