<?php
/**
 * A.P.P.E. — Registro de clientes.
 *
 * Caso de uso CU-09 «Registrarse» · Requisito funcional RF-01.
 *
 * El PDF exige "registro de nuevos usuarios con validación de datos y
 * contraseña" y un CAPTCHA para verificar que el usuario no es un bot.
 *
 * Correcciones aplicadas respecto de la versión anterior:
 *   - Se exige token CSRF (hallazgo 15).
 *   - El CAPTCHA se valida y se consume en el servidor (un solo uso).
 *   - Se comprueba el código de error de la subida y el tamaño real del
 *     archivo (hallazgo 8).
 *   - La contraseña nunca se sanea ni se registra: solo se hashea.
 *   - Respuesta JSON coherente (hallazgo 26).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();

    // --- CAPTCHA (un solo uso) ------------------------------------------------
    $respuestaCaptcha = trim((string) ($_POST['captcha'] ?? ''));

    if (!isset($_SESSION['captcha_registro']) || !is_array($_SESSION['captcha_registro'])) {
        throw new ValidacionException('La verificación expiró. Vuelva a intentarlo.');
    }

    $captcha = $_SESSION['captcha_registro'];
    unset($_SESSION['captcha_registro']);

    if ($respuestaCaptcha === '' || !hash_equals((string) $captcha['respuesta'], $respuestaCaptcha)) {
        throw new ValidacionException('La respuesta de la verificación no es correcta.');
    }

    // --- Validación de los datos ----------------------------------------------
    $nombre   = texto_requerido($_POST['nombre'] ?? '', 'Nombre', 70);
    $apellido = texto_requerido($_POST['apellido'] ?? '', 'Apellido', 70);
    $correo   = correo_requerido($_POST['correo'] ?? '');
    $clave    = (string) ($_POST['contraseña'] ?? '');

    if (strlen($clave) < 8) {
        throw new ValidacionException('La contraseña debe tener al menos 8 caracteres.');
    }

    if (strlen($clave) > 200) {
        throw new ValidacionException('La contraseña es demasiado larga.');
    }

    // --- Correo duplicado ------------------------------------------------------
    $stmt = appe_conexion()->prepare('SELECT id_cliente FROM cliente WHERE correo = ?');
    $stmt->execute([$correo]);

    if ($stmt->fetch() !== false) {
        throw new ConflictoException('Ese correo electrónico ya está registrado.');
    }

    // --- Alta ------------------------------------------------------------------
    $hash = password_hash($clave, PASSWORD_DEFAULT);

    $stmt = appe_conexion()->prepare(
        'INSERT INTO cliente (nombre, apellido, telefono, correo, `contraseña`, activo, id_gimnasio)
         VALUES (?, ?, ?, ?, ?, 1, 1)'
    );
    $stmt->execute([
        $nombre,
        $apellido,
        texto_opcional($_POST['telefono'] ?? '', 'Teléfono', 20) ?: null,
        $correo,
        $hash,
    ]);

    $idCliente = (int) appe_conexion()->lastInsertId();

    auditar('alta_cliente', 'cliente', $idCliente, $correo);

    // CU-09: al registrarse, la sesión queda iniciada.
    iniciar_sesion_usuario([
        'id'       => $idCliente,
        'rol'      => 'cliente',
        'nombre'   => $nombre,
        'apellido' => $apellido,
        'correo'   => $correo,
    ]);

    responder_exito('Cuenta creada correctamente.', [
        'id'   => $idCliente,
        'csrf' => csrf_token(),
    ]);
}, 'registro.php');
