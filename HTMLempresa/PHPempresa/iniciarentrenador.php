<?php
/**
 * A.P.P.E. — Inicio de sesión del entrenador / administrador.
 *
 * Caso de uso CU-01 «Iniciar Sesión» (actor Entrenador).
 *
 * Correcciones respecto de la versión anterior:
 *   - El correo se valida en el servidor (antes no se validaba).
 *   - Regeneración del identificador de sesión y reconstrucción del estado
 *     (hallazgos 10 y 12).
 *   - Mensaje genérico ante credenciales inválidas (hallazgo 13).
 *   - Límite de intentos por cuenta y por origen, persistido en la base de
 *     datos (hallazgo 14).
 *   - Token CSRF obligatorio (hallazgo 15).
 *   - Se comprueba que la cuenta esté activa.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();

    $correo = correo_requerido($_POST['correo'] ?? '');
    $clave  = (string) ($_POST['contraseña'] ?? '');

    if ($clave === '') {
        throw new ValidacionException('La contraseña es obligatoria.');
    }

    if (strlen($clave) > 200) {
        throw new ValidacionException('La contraseña es demasiado larga.');
    }

    $ip = ip_cliente();

    $bloqueo = segundos_de_bloqueo($correo, $ip);
    if ($bloqueo !== null) {
        responder_error(
            'Demasiados intentos fallidos. Intente nuevamente en ' . max(1, (int) ceil($bloqueo / 60)) . ' minuto(s).',
            429,
            'demasiados_intentos'
        );
    }

    $stmt = appe_conexion()->prepare(
        'SELECT id_entrenador, nombre, apellido, telefono, correo, `contraseña`, rol, activo
           FROM entrenador
          WHERE correo = ?'
    );
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    $autenticado = $usuario !== false
        && (int) $usuario['activo'] === 1
        && password_verify($clave, (string) $usuario['contraseña']);

    registrar_intento($correo, $ip, $autenticado);

    if (!$autenticado) {
        responder_error('Correo o contraseña incorrectos.', 401, 'credenciales');
    }

    // Un acceso correcto borra los fallos acumulados de la cuenta (hallazgo 14).
    limpiar_intentos_cuenta($correo);

    if (password_needs_rehash((string) $usuario['contraseña'], PASSWORD_DEFAULT)) {
        $nuevo = password_hash($clave, PASSWORD_DEFAULT);
        $stmtUpdate = appe_conexion()->prepare('UPDATE entrenador SET `contraseña` = ? WHERE id_entrenador = ?');
        $stmtUpdate->execute([$nuevo, (int) $usuario['id_entrenador']]);
    }

    iniciar_sesion_usuario([
        'id'       => (int) $usuario['id_entrenador'],
        'rol'      => (string) $usuario['rol'],
        'nombre'   => (string) $usuario['nombre'],
        'apellido' => (string) $usuario['apellido'],
        'correo'   => (string) $usuario['correo'],
    ]);

    auditar('inicio_sesion', 'entrenador', (int) $usuario['id_entrenador'], 'Acceso correcto');

    responder_exito('Sesión iniciada correctamente.', [
        'id'   => (int) $usuario['id_entrenador'],
        'rol'  => (string) $usuario['rol'],
        'csrf' => csrf_token(),
    ]);
}, 'iniciarentrenador.php');
