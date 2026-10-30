<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    requiere_rol('cliente');

    $idCliente = cliente_id_actual();

    // 1. Seleccionamos las fechas de vigencia desde la tabla 'cliente' (c.fecha_inicio y c.fecha_fin)
    $stmt = appe_conexion()->prepare(
        'SELECT c.id AS id_cliente, c.nombre, c.apellido, c.telefono, c.correo, c.fecha_alta,
                c.fecha_inicio AS cliente_fecha_inicio, c.fecha_fin AS cliente_fecha_fin,
                p.id_plan, p.nombre AS plan_nombre, p.beneficio, p.precio, p.duracion,
                p.tipo_de_plan,
                r.id_rutina, r.nombre AS rutina_nombre, r.estado AS rutina_estado
           FROM cliente c
           LEFT JOIN plan p   ON p.id_plan = c.id_plan
           LEFT JOIN rutina r ON r.id_rutina = c.id_rutina
          WHERE c.id = ?'
    );
    $stmt->execute([$idCliente]);
    $fila = $stmt->fetch();

    if ($fila === false) {
        throw new NoEncontradoException('No se encontró la cuenta del cliente.');
    }

    // Período y precio que el cliente eligió al contratar. `plan.precio` es solo
    // el precio de referencia del plan; lo que se cobró depende del período.
    $periodoElegido = null;
    $precioElegido = $fila['id_plan'] === null ? null : (float) $fila['precio'];

    if ($fila['id_plan'] !== null) {
        $inicio = $fila['cliente_fecha_inicio'] ? strtotime((string) $fila['cliente_fecha_inicio']) : false;
        $fin = $fila['cliente_fecha_fin'] ? strtotime((string) $fila['cliente_fecha_fin']) : false;

        if ($inicio !== false && $fin !== false) {
            // comprarplan.php fija la vigencia en 7 días, 1, 3 o 12 meses.
            $dias = (int) round(($fin - $inicio) / 86400);
            $periodoElegido = match (true) {
                $dias <= 10  => 'semanal',
                $dias <= 45  => 'mensual',
                $dias <= 120 => 'trimestral',
                default      => 'anual',
            };

            // Lo cobrado: la cuota emitida al contratar; si no está, el precio
            // vigente de ese período.
            $stmt = appe_conexion()->prepare(
                'SELECT monto FROM cuota
                  WHERE id_cliente = ? AND id_plan = ? AND fecha_emision = ?
                  ORDER BY id_cuota DESC LIMIT 1'
            );
            $stmt->execute([$idCliente, (int) $fila['id_plan'], date('Y-m-d', $inicio)]);
            $monto = $stmt->fetchColumn();

            if ($monto !== false) {
                $precioElegido = (float) $monto;
            } else {
                $stmt = appe_conexion()->prepare(
                    'SELECT precio FROM planprecio WHERE id_plan = ? AND periodo = ?'
                );
                $stmt->execute([(int) $fila['id_plan'], $periodoElegido]);
                $precioPeriodo = $stmt->fetchColumn();

                if ($precioPeriodo !== false) {
                    $precioElegido = (float) $precioPeriodo;
                }
            }
        }
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
        // 2. Mapeamos las fechas del cliente para que 'perfil.js' las pinte correctamente
        'plan' => $fila['id_plan'] === null ? null : [
            'id_plan'      => (int) $fila['id_plan'],
            'nombre'       => (string) $fila['plan_nombre'],
            'beneficio'    => $fila['beneficio'],
            'precio'       => $precioElegido,
            'periodo'      => $periodoElegido,
            'duracion'     => $fila['duracion'],
            'tipo_de_plan' => $fila['tipo_de_plan'],
            'fecha_inicio' => $fila['cliente_fecha_inicio'],
            'fecha_fin'    => $fila['cliente_fecha_fin'],
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