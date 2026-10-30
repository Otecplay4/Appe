<?php
/**
 * A.P.P.E. — Imagen pública de un plan.
 *
 * La extensión se lee de la base de datos, nunca de la petición, y la ruta
 * final se comprueba con `realpath()` para impedir el path traversal.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');

    $idPlan = entero_requerido($_GET['id'] ?? null, 'Plan', 1);

    $stmt = appe_conexion()->prepare(
        'SELECT p.extension
           FROM plan p
           JOIN planprecio pp ON pp.id_plan = p.id_plan
          WHERE p.id_plan = ? AND p.activo = 1
          LIMIT 1'
    );
    $stmt->execute([$idPlan]);
    $fila = $stmt->fetch();

    if ($fila === false) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    servir_imagen(
        __DIR__ . '/../../HTMLempresa/PHPempresa/img_planes',
        (string) ($fila['extension'] ?? ''),
        $idPlan,
        __DIR__ . '/../imagenes/plan basico.jpg'
    );
}, 'imagenplan.php');
