<?php
/**
 * A.P.P.E. — Solicitud de recuperación de contraseña.
 *
 * Requisito funcional RF-03 «permitir recuperar las credenciales de acceso en
 * caso de olvido».
 *
 * Flujo:
 *   1. El cliente indica su correo.
 *   2. Se genera un token aleatorio; en la base de datos solo se guarda su
 *      hash (SHA-256). El valor en claro viaja únicamente en el enlace.
 *   3. Se envía el enlace de restablecimiento por correo.
 *   4. `reestablecerclave.php` valida el token y permite fijar una contraseña
 *      nueva (que se guarda con `password_hash`).
 *
 * La respuesta es SIEMPRE la misma, exista o no la cuenta: así no se puede
 * enumerar qué correos están registrados (hallazgo 13).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/correo.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();

    $correo = correo_requerido($_POST['correo'] ?? '');

    $stmt = appe_conexion()->prepare('SELECT id_cliente, nombre, correo FROM cliente WHERE correo = ? AND activo = 1');
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    $enlace = null;

    if ($usuario !== false) {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);

        $vigencia = (int) appe_config()['seguridad']['vigencia_token_min'];

        // Se invalidan los tokens anteriores de esta cuenta.
        $stmt = appe_conexion()->prepare(
            'UPDATE tokenrecuperacion SET usado = 1
              WHERE tipo = \'cliente\' AND id_cliente = ? AND usado = 0'
        );
        $stmt->execute([(int) $usuario['id_cliente']]);

        $stmt = appe_conexion()->prepare(
            'INSERT INTO tokenrecuperacion (tipo, id_cliente, token_hash, expira, usado)
             VALUES (\'cliente\', ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0)'
        );
        $stmt->execute([(int) $usuario['id_cliente'], $hash, $vigencia]);

        // El enlace es relativo a este endpoint (`PHPcliente/`), de modo que
        // funciona con cualquier carpeta de publicación.
        $enlace = '../reestablecer.html?token=' . $token;

        enviar_correo(
            (string) $usuario['correo'],
            'Recuperación de contraseña — A.P.P.E.',
            "Hola " . $usuario['nombre'] . ",\n\n"
            . "Recibimos una solicitud para restablecer la contraseña de tu cuenta de A.P.P.E.\n\n"
            . "Abre el siguiente enlace (vigente por {$vigencia} minutos):\n"
            . $enlace . "\n\n"
            . "Si no solicitaste el cambio, podés ignorar este mensaje: el enlace solo funciona una vez.\n"
        );

        auditar('solicitud_recuperacion', 'cliente', (int) $usuario['id_cliente'], $correo);
    }

    // Respuesta genérica, idéntica exista o no la cuenta.
    $respuesta = [
        'mensaje' => 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.',
    ];

    // En un entorno sin servidor de correo el enlace se devuelve para que la
    // funcionalidad sea verificable. Nunca se hace en producción.
    if (!correo_disponible() && $enlace !== null && (bool) appe_config()['aplicacion']['depurar']) {
        $respuesta['enlace'] = $enlace;
    }

    responder_exito($respuesta['mensaje'], $respuesta);
}, 'recuperarclave.php');
