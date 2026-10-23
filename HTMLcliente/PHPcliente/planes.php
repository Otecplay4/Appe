<?php
/**
 * A.P.P.E. — Catálogo público de planes.
 *
 * Requisito funcional RF-07 (gestionar los planes y suscripciones) y RF-12
 * (búsquedas y filtros) en su cara pública.
 *
 * Solo se publican los planes activos que tienen al menos un precio
 * cargado: un plan sin precio no puede contratarse.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');

    $idPlan = isset($_GET['id']) ? entero_requerido($_GET['id'], 'Plan', 1) : null;

    $sql = 'SELECT p.id_plan, p.nombre, p.descripcion, p.beneficio, p.precio,
                   p.duracion, p.tipo_de_plan, p.extension,
                   pp.periodo, pp.precio AS precio_periodo
              FROM plan p
              JOIN planprecio pp ON pp.id_plan = p.id_plan
             WHERE p.activo = 1';

    $parametros = [];

    if ($idPlan !== null) {
        $sql .= ' AND p.id_plan = ?';
        $parametros[] = $idPlan;
    }

    $sql .= ' ORDER BY p.id_plan ASC, pp.periodo ASC';

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    // Se agrupan los precios por plan.
    $planes = [];
    foreach ($filas as $fila) {
        $id = (int) $fila['id_plan'];

        if (!isset($planes[$id])) {
            $planes[$id] = [
                'id_plan'      => $id,
                'nombre'       => (string) $fila['nombre'],
                'descripcion'  => $fila['descripcion'],
                'beneficio'    => $fila['beneficio'],
                'precio'       => (float) $fila['precio'],
                'duracion'     => $fila['duracion'],
                'tipo_de_plan' => $fila['tipo_de_plan'],
                'extension'    => $fila['extension'],
                'precios'      => [],
            ];
        }

        $planes[$id]['precios'][(string) $fila['periodo']] = (float) $fila['precio_periodo'];
    }

    $lista = array_values($planes);

    if ($idPlan !== null && $lista === []) {
        throw new NoEncontradoException('El plan solicitado no existe.');
    }

    responder_exito('Catálogo de planes.', [
        'planes' => $idPlan !== null ? $lista[0] : $lista,
    ]);
}, 'planes.php');
