<?php
/**
 * A.P.P.E. — Inicio de sesión del cliente.
 *
 * Caso de uso CU-01 «Iniciar Sesión» · Requisito funcional RF-02.
 *
 * Correcciones aplicadas respecto de la versión anterior:
 *   - Regeneración del identificador de sesión al autenticar (hallazgo 10).
 *   - Reconstrucción del estado de sesión: no queda información del rol
 *     anterior ni el hash de la contraseña (hallazgo 12).
 *   - Mensaje genérico ante credenciales inválidas: no se distingue entre
 *     «correo inexistente» y «contraseña incorrecta» (hallazgo 13).
 *   - Límite de intentos por cuenta y por origen, registrado en la base de
 *     datos y no solo en la sesión (hallazgo 14).
 *   - Token CSRF obligatorio (hallazgo 15).
 *   - Se valida el correo en el servidor (no basta con el `type="email"` del
 *     HTML, que se puede saltar con F12 — hallazgo 16).
 *   - Respuesta JSON con `Content-Type` declarado y estado HTTP coherente
 *     (hallazgo 26).
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
        'SELECT id_cliente, nombre, apellido, correo, `contraseña`, activo
           FROM cliente
          WHERE correo = ?'
    );
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    $autenticado = $usuario !== false
        && (int) $usuario['activo'] === 1
        && password_verify($clave, (string) $usuario['contraseña']);

    registrar_intento($correo, $ip, $autenticado);

    if (!$autenticado) {
        // Respuesta genérica: no revela si la cuenta existe (hallazgo 13).
        responder_error('Correo o contraseña incorrectos.', 401, 'credenciales');
    }

    // Un acceso correcto borra los fallos acumulados de la cuenta: el usuario
    // legítimo que se equivocó un par de veces no debe quedar al borde del
    // bloqueo por su IP compartida (hallazgo 14).
    limpiar_intentos_cuenta($correo);

    // Si el hash no está en el formato soportado, se vuelve a calcular.
    if (password_needs_rehash((string) $usuario['contraseña'], PASSWORD_DEFAULT)) {
        $nuevo = password_hash($clave, PASSWORD_DEFAULT);
        $stmtUpdate = appe_conexion()->prepare('UPDATE cliente SET `contraseña` = ? WHERE id_cliente = ?');
        $stmtUpdate->execute([$nuevo, (int) $usuario['id_cliente']]);
    }

    iniciar_sesion_usuario([
        'id'       => (int) $usuario['id_cliente'],
        'rol'      => 'cliente',
        'nombre'   => (string) $usuario['nombre'],
        'apellido' => (string) $usuario['apellido'],
        'correo'   => (string) $usuario['correo'],
    ]);

    auditar('inicio_sesion', 'cliente', (int) $usuario['id_cliente'], 'Acceso correcto');

    responder_exito('Sesión iniciada correctamente.', [
        'id'   => (int) $usuario['id_cliente'],
        'rol'  => 'cliente',
        'csrf' => csrf_token(),
    ]);
}, 'iniciarSesion.php');
