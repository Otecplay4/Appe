<?php
/**
 * A.P.P.E. — Baja de un cliente.
 *
 * Requisito funcional RF-06 · Caso de uso CU-02.
 *
 * Correcciones:
 *   - Exige sesión de entrenador y token CSRF (hallazgos 1 y 15).
 *   - Se exige POST: la eliminación no se puede ejecutar con GET (hallazgo 15).
 *   - Se comprueba que el cliente exista: se distingue «no existe» (404) de
 *     «eliminado» (200) (hallazgo 25).
 *   - Las rutinas, cuotas, solicitudes y teléfonos del cliente se eliminan en
 *     cascada gracias a las claves foráneas `ON DELETE CASCADE` del esquema.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idCliente = id_requerido($_POST['id'] ?? null, 'Cliente');

    $stmt = appe_conexion()->prepare('SELECT id AS id_cliente, nombre, apellido FROM cliente WHERE id = ?');
    $stmt->execute([$idCliente]);
    $cliente = $stmt->fetch();

    if ($cliente === false) {
        throw new NoEncontradoException('El cliente no existe.');
    }

    $stmt = appe_conexion()->prepare('DELETE FROM cliente WHERE id = ?');
    $stmt->execute([$idCliente]);

    auditar(
        'baja_cliente',
        'cliente',
        $idCliente,
        trim($cliente['nombre'] . ' ' . $cliente['apellido'])
    );

    responder_exito('El cliente fue eliminado correctamente.', [
        'id_cliente' => $idCliente,
    ]);
}, 'eliminar.php');
