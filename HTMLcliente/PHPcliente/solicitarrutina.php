<?php
/**
 * A.P.P.E. — Solicitud de cambio de rutina.
 *
 * Requisito funcional RF-14 «permitir solicitar el cambio de rutina desde el
 * perfil del cliente» · Caso de uso CU-06 (flujo alternativo del cliente).
 *
 * El cliente no puede modificar la rutina directamente: deja una solicitud
 * que el entrenador atiende desde el módulo de solicitudes.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_rol('cliente');

    $idCliente = cliente_id_actual();

    // El motivo se valida en el servidor con el mismo mínimo que exige la
    // interfaz (5 caracteres), para que la regla no dependa del navegador.
    $mensaje = texto_requerido($_POST['mensaje'] ?? '', 'Motivo', 500, 5);

    // Solo una solicitud pendiente por cliente y rutina.
    $stmt = appe_conexion()->prepare(
        'SELECT id_solicitud
           FROM solicitudcambio
          WHERE id_cliente = ? AND estado = \'pendiente\''
    );
    $stmt->execute([$idCliente]);

    if ($stmt->fetch() !== false) {
        throw new ConflictoException('Ya tiene una solicitud pendiente de respuesta.');
    }

    $stmt = appe_conexion()->prepare('SELECT id_rutina, id_entrenador FROM cliente WHERE id = ?');
    $stmt->execute([$idCliente]);
    $cliente = $stmt->fetch();

    $stmt = appe_conexion()->prepare(
        'INSERT INTO solicitudcambio (id_cliente, id_rutina, id_entrenador, mensaje, estado)
         VALUES (?, ?, ?, ?, \'pendiente\')'
    );
    $stmt->execute([
        $idCliente,
        $cliente === false ? null : $cliente['id_rutina'],
        $cliente === false ? null : $cliente['id_entrenador'],
        $mensaje,
    ]);

    $idSolicitud = (int) appe_conexion()->lastInsertId();

    auditar('solicitud_cambio_rutina', 'solicitudcambio', $idSolicitud, 'Solicitud registrada');

    responder_exito('Su solicitud fue registrada. El entrenador la revisará.', [
        'id_solicitud' => $idSolicitud,
    ], 201);
}, 'solicitarrutina.php');
