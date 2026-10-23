<?php
/**
 * A.P.P.E. — Baja de un plan de suscripción.
 *
 * Requisito funcional RF-07 · Caso de uso CU-03.
 *
 * Se borran el registro, sus precios y su imagen. Los clientes que lo tuvieran
 * asignado quedan con `id_plan` en NULL (ON DELETE SET NULL del esquema).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/planes_comun.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idPlan = id_requerido($_POST['id'] ?? null, 'Plan');

    $stmt = appe_conexion()->prepare('SELECT id_plan, nombre FROM plan WHERE id_plan = ?');
    $stmt->execute([$idPlan]);
    $plan = $stmt->fetch();

    if ($plan === false) {
        throw new NoEncontradoException('El plan no existe.');
    }

    $archivoBorrado = borrar_imagen_plan($idPlan);

    $stmt = appe_conexion()->prepare('DELETE FROM plan WHERE id_plan = ?');
    $stmt->execute([$idPlan]);

    auditar('baja_plan', 'plan', $idPlan, (string) $plan['nombre']);

    responder_exito('El plan fue eliminado correctamente.', [
        'id_plan'        => $idPlan,
        'archivo_borrado' => $archivoBorrado,
    ]);
}, 'eliminarplan.php');
