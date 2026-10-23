<?php
/**
 * A.P.P.E. — Comprobación de sesión del entrenador/administrador.
 *
 * Verifica identidad Y rol (hallazgo 11: antes bastaba con que la sesión
 * tuviera un correo, y la sesión de un cliente satisfacía el chequeo).
 *
 * Además entrega el token CSRF que el front-end adjunta a las operaciones que
 * modifican el estado (hallazgo 15).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');

    $usuario = usuario_actual();
    $permitido = $usuario !== null && in_array($usuario['rol'], ['entrenador', 'admin'], true);

    responder_exito($permitido ? 'Sesión de empresa activa.' : 'No hay sesión de empresa.', [
        'exito'   => $permitido,
        'rol'     => $permitido ? (string) $usuario['rol'] : null,
        'usuario' => $permitido
            ? [
                'id'       => (int) $usuario['id'],
                'nombre'   => (string) $usuario['nombre'],
                'apellido' => (string) $usuario['apellido'],
                'correo'   => (string) $usuario['correo'],
            ]
            : null,
        'csrf'    => csrf_token(),
    ]);
}, 'check.php');
