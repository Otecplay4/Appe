<?php
/**
 * A.P.P.E. — Estado de la sesión de la empresa (entrenador / administrador).
 *
 * Fuente de verdad: la sesión de PHP del servidor. El navegador no decide si
 * el usuario está autenticado (hallazgo 11).
 *
 * Además entrega el token CSRF que el front-end adjunta a las operaciones que
 * modifican el estado (hallazgo 15). Es el endpoint que usa
 * `assets/js/api.js` (`API.obtenerCsrf()`) al resolver
 * `"./PHPempresa/sesion.php"`, por lo que sin él ningún POST de la
 * administración (incluido el inicio de sesión) puede validar su token.
 *
 * Es el equivalente de `HTMLcliente/PHPcliente/sesion.php` para el lado
 * empresa.
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
