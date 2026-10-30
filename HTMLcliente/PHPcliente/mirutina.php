<?php
/**
 * A.P.P.E. — Rutina asignada al cliente autenticado.
 *
 * Requisito funcional RF-13 «permitir al cliente consultar su rutina de
 * entrenamiento asignada» · Caso de uso CU-06.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    requiere_rol('cliente');

    $idCliente = cliente_id_actual();

    // 1. La rutina vigente: la que apunta `cliente.id_rutina` o,
    //   su respectiva rutina para el cliente que haya iniciaado sesion.
    $stmt = appe_conexion()->prepare(
        'SELECT COALESCE(c.id_rutina, (
                    SELECT r.id_rutina
                      FROM rutina r
                     WHERE r.id_cliente = c.id
                       AND r.estado = \'activa\'
                     ORDER BY r.id_rutina DESC
                     LIMIT 1
                )) AS id_rutina
           FROM cliente c
          WHERE c.id = ?'
    );
    $stmt->execute([$idCliente]);
    $fila = $stmt->fetch();

    $idRutina = $fila === false ? null : $fila['id_rutina'];

    if ($idRutina === null) {
        responder_exito('Todavía no tiene una rutina asignada.', [
            'rutina'   => null,
            'ejercicios' => [],
            'dias'     => [],
        ]);
    }

    // 2. Los ejercicios de la rutina, con su día y sus ejercicios/Mùsculo trabajado.
    $stmt = appe_conexion()->prepare(
        'SELECT ru.nombre           AS nombre_rutina,
                rt.dia              AS dia,
                rt.orden            AS orden,
                rt.serie_repeticion AS serie_repeticion,
                e.nombre            AS ejercicio,
                e.musculo           AS musculo
           FROM rutina ru
           JOIN rutinatieneejercicio rt ON rt.id_rutina = ru.id_rutina
           JOIN ejercicio e            ON e.id_ejercicio = rt.id_ejercicio
          WHERE ru.id_rutina = ?
          ORDER BY rt.dia ASC, rt.orden ASC, e.nombre ASC'
    );
    $stmt->execute([(int) $idRutina]);
    $filas = $stmt->fetchAll();

    $dias = [];
    foreach ($filas as $fila) {
        $dia = (int) $fila['dia'];
        if (!isset($dias[$dia])) {
            $dias[$dia] = [];
        }
        $dias[$dia][] = [
            'ejercicio'        => (string) $fila['ejercicio'],
            'musculo'          => (string) $fila['musculo'],
            'serie_repeticion' => (string) $fila['serie_repeticion'],
        ];
    }
    ksort($dias);

    responder_exito('Rutina asignada.', [
        'rutina'     => [
            'id_rutina' => (int) $idRutina,
            'nombre'    => (string) ($filas[0]['nombre_rutina'] ?? 'Rutina'),
        ],
        'ejercicios' => $filas,
        'dias'       => $dias,
    ]);
}, 'mirutina.php');
