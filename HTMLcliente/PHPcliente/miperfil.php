<?php
/**
 * A.P.P.E. — Perfil del cliente: datos personales, plan, rutina y estado de
 * cuenta.
 *
 * Caso de uso CU-04 «Consultar Información y Estado de Cuenta»
 * (actor Cliente) · Requisito funcional RF-10 (estado de pago y vencimientos)
 * y RF-13 (rutina asignada).
 *
 * Solo devuelve los datos de la cuenta propia: el identificador se toma de la
 * sesión (hallazgo 4). Nunca se devuelven hashes de contraseña (hallazgo 3).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    requiere_rol('cliente');

    $idCliente = cliente_id_actual();

    $stmt = appe_conexion()->prepare(
        'SELECT c.id_cliente, c.nombre, c.apellido, c.telefono, c.correo, c.fecha_alta,
                p.id_plan, p.nombre AS plan_nombre, p.beneficio, p.precio, p.duracion,
                p.tipo_de_plan, p.fecha_inicio, p.fecha_fin,
                r.id_rutina, r.nombre AS rutina_nombre, r.estado AS rutina_estado
           FROM cliente c
           LEFT JOIN plan p   ON p.id_plan = c.id_plan
           LEFT JOIN rutina r ON r.id_rutina = c.id_rutina
          WHERE c.id_cliente = ?'
    );
    $stmt->execute([$idCliente]);
    $fila = $stmt->fetch();

    if ($fila === false) {
        throw new NoEncontradoException('No se encontró la cuenta del cliente.');
    }

    // Estado de cuenta: cuotas del cliente (RF-10).
    $stmt = appe_conexion()->prepare(
        'SELECT id_cuota, concepto, monto, fecha_emision, fecha_vencimiento, fecha_pago, estado, forma_pago
           FROM cuota
          WHERE id_cliente = ?
          ORDER BY fecha_vencimiento DESC, id_cuota DESC
          LIMIT 50'
    );
    $stmt->execute([$idCliente]);
    $cuotas = $stmt->fetchAll();

    $pendientes = 0;
    $proximoVencimiento = null;
    foreach ($cuotas as $cuota) {
        if ($cuota['estado'] === 'pendiente' || $cuota['estado'] === 'vencida') {
            $pendientes += (float) $cuota['monto'];
            if ($proximoVencimiento === null || $cuota['fecha_vencimiento'] < $proximoVencimiento) {
                $proximoVencimiento = $cuota['fecha_vencimiento'];
            }
        }
    }

    // Solicitudes de cambio de rutina (RF-14).
    $stmt = appe_conexion()->prepare(
        'SELECT id_solicitud, mensaje, estado, respuesta, fecha, fecha_respuesta
           FROM solicitudcambio
          WHERE id_cliente = ?
          ORDER BY fecha DESC
          LIMIT 20'
    );
    $stmt->execute([$idCliente]);
    $solicitudes = $stmt->fetchAll();

    responder_exito('Datos del perfil.', [
        'datos' => [
            'id'        => (int) $fila['id_cliente'],
            'nombre'    => (string) $fila['nombre'],
            'apellido'  => (string) $fila['apellido'],
            'telefono'  => $fila['telefono'],
            'correo'    => (string) $fila['correo'],
            'fecha_alta' => (string) $fila['fecha_alta'],
        ],
        'plan' => $fila['id_plan'] === null ? null : [
            'id_plan'      => (int) $fila['id_plan'],
            'nombre'       => (string) $fila['plan_nombre'],
            'beneficio'    => $fila['beneficio'],
            'precio'       => (float) $fila['precio'],
            'duracion'     => $fila['duracion'],
            'tipo_de_plan' => $fila['tipo_de_plan'],
            'fecha_inicio' => $fila['fecha_inicio'],
            'fecha_fin'    => $fila['fecha_fin'],
        ],
        'rutina' => $fila['id_rutina'] === null ? null : [
            'id_rutina' => (int) $fila['id_rutina'],
            'nombre'    => (string) $fila['rutina_nombre'],
            'estado'    => (string) $fila['rutina_estado'],
        ],
        'estado_cuenta' => [
            'cuotas'              => $cuotas,
            'total_pendiente'     => round($pendientes, 2),
            'proximo_vencimiento' => $proximoVencimiento,
        ],
        'solicitudes' => $solicitudes,
    ]);
}, 'miperfil.php');
