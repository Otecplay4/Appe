<?php
/**
 * A.P.P.E. — Estado de la sesión de la empresa (entrenador / administrador).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    $usuario = usuario_actual();

    responder_exito('Estado de la sesión.', [
        'logueado' => $usuario !== null,
        'rol'      => $usuario['rol'] ?? null,
        'usuario'  => $usuario !== null
            ? [
                'id'       => (int) $usuario['id'],
                'nombre'   => (string) $usuario['nombre'],
                'apellido' => (string) $usuario['apellido'],
                'correo'   => (string) $usuario['correo'],
            ]
            : null,
        'csrf'     => csrf_token(),
    ]);
}, 'sesion.php');
