<?php
/**
 * A.P.P.E. — Listado de planes (vista de administración).
 *
 * Requisito funcional RF-07 · Caso de uso CU-04.
 *
 * A diferencia del catálogo público, aquí se muestran también los planes
 * inactivos y los que todavía no tienen precio cargado.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $stmt = appe_conexion()->prepare(
        'SELECT p.id_plan, p.nombre, p.descripcion, p.beneficio, p.precio, p.duracion,
                p.tipo_de_plan, p.fecha_inicio, p.fecha_fin, p.extension, p.activo,
                pp.periodo, pp.precio AS precio_periodo
           FROM plan p
           LEFT JOIN planprecio pp ON pp.id_plan = p.id_plan
          ORDER BY p.activo DESC, p.id_plan ASC, pp.periodo ASC'
    );
    $stmt->execute();
    $filas = $stmt->fetchAll();

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
                'fecha_inicio' => $fila['fecha_inicio'],
                'fecha_fin'    => $fila['fecha_fin'],
                'extension'    => $fila['extension'],
                'activo'       => (int) $fila['activo'] === 1,
                'precios'      => [],
            ];
        }

        if ($fila['periodo'] !== null) {
            $planes[$id]['precios'][(string) $fila['periodo']] = (float) $fila['precio_periodo'];
        }
    }

    responder_exito('Listado de planes.', [
        'planes' => array_values($planes),
    ]);
}, 'mostrarplanes.php');
