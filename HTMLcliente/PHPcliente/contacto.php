<?php
/**
 * A.P.P.E. — Formulario de contacto con el gimnasio.
 *
 * Requisito funcional RF-15 «mostrar la información de contacto y formulario
 * de comunicación con el gimnasio» · Caso de uso CU-07.
 *
 * Disponible sin inicio de sesión (así lo define el caso de uso), pero con
 * token CSRF para evitar que terceros envíen mensajes en nombre de la
 * aplicación. La validación de los datos se hace en el servidor (hallazgo 16).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();

    $nombre   = texto_requerido($_POST['nombre'] ?? '', 'Nombre', 70);
    $correo   = correo_requerido($_POST['correo'] ?? '');
    $telefono = texto_opcional($_POST['telefono'] ?? '', 'Teléfono', 20);
    $mensaje  = texto_requerido($_POST['mensaje'] ?? '', 'Mensaje', 1000);

    $stmt = appe_conexion()->prepare(
        'INSERT INTO mensajecontacto (nombre, correo, telefono, mensaje, leido)
         VALUES (?, ?, ?, ?, 0)'
    );
    $stmt->execute([$nombre, $correo, $telefono ?: null, $mensaje]);

    $id = (int) appe_conexion()->lastInsertId();

    auditar('mensaje_contacto', 'mensajecontacto', $id, 'Mensaje recibido');

    responder_exito('¡Gracias por escribirnos! Te responderemos a la brevedad.', [
        'id_mensaje' => $id,
    ], 201);
}, 'contacto.php');
