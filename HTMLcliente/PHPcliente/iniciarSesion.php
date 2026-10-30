<?php
/**
 * A.P.P.E. — Inicio de sesión del cliente.
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
        'SELECT id AS id_cliente, nombre, apellido, correo, `contraseña`, activo
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
        // Respuesta genérica: no revela si la cuenta existe.
        responder_error('Correo o contraseña incorrectos.', 401, 'credenciales');
    }

    // Un acceso correcto borra los fallos acumulados de la cuenta: el usuario
    // legítimo que se equivocó un par de veces no debe quedar al borde del
    // bloqueo por su IP.
    limpiar_intentos_cuenta($correo);

    // Si el hash no está en el formato soportado, se vuelve a calcular.
    if (password_needs_rehash((string) $usuario['contraseña'], PASSWORD_DEFAULT)) {
        $nuevo = password_hash($clave, PASSWORD_DEFAULT);
        $stmtUpdate = appe_conexion()->prepare('UPDATE cliente SET `contraseña` = ? WHERE id = ?');
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
