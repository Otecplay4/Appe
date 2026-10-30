<?php
/**
 * A.P.P.E. — Restablecimiento de contraseña con token.
 *
 * Requisito funcional RF-03. Complementa a `recuperarclave.php`.
 *
 * El token se recibe en el cuerpo de la petición y se compara con el hash
 * guardado en la base de datos (`hash_equals` evita la comparación por tiempo).
 * El token se marca como usado, de modo que sirve una sola vez, y la
 * contraseña nueva se guarda siempre con `password_hash`.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();

    $token = trim((string) ($_POST['token'] ?? ''));

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        throw new ValidacionException('El enlace de recuperación no es válido.');
    }

    $claveNueva = (string) ($_POST['contraseña'] ?? '');
    $repetida   = (string) ($_POST['confirmacion'] ?? '');

    if (strlen($claveNueva) < 8) {
        throw new ValidacionException('La contraseña debe tener al menos 8 caracteres.');
    }

    if (strlen($claveNueva) > 200) {
        throw new ValidacionException('La contraseña es demasiado larga.');
    }

    if (!hash_equals($claveNueva, $repetida)) {
        throw new ValidacionException('Las contraseñas no coinciden.');
    }

    $stmt = appe_conexion()->prepare(
        'SELECT id_token, tipo, id_cliente, id_entrenador, expira, usado
           FROM tokenrecuperacion
          WHERE token_hash = ?'
    );
    $stmt->execute([hash('sha256', $token)]);
    $registro = $stmt->fetch();

    if ($registro === false) {
        throw new NoEncontradoException('El enlace de recuperación no es válido.');
    }

    if ((int) $registro['usado'] === 1) {
        throw new ValidacionException('Este enlace ya fue utilizado. Solicite uno nuevo.');
    }

    if (strtotime((string) $registro['expira']) < time()) {
        throw new ValidacionException('El enlace expiró. Solicite uno nuevo.');
    }

    $hash = password_hash($claveNueva, PASSWORD_DEFAULT);

    if ($registro['tipo'] === 'entrenador') {
        $stmt = appe_conexion()->prepare('UPDATE entrenador SET `contraseña` = ?, activo = 1 WHERE id_entrenador = ?');
        $stmt->execute([$hash, (int) $registro['id_entrenador']]);
        $idActor = (int) $registro['id_entrenador'];
    } else {
        $stmt = appe_conexion()->prepare('UPDATE cliente SET `contraseña` = ? WHERE id = ?');
        $stmt->execute([$hash, (int) $registro['id_cliente']]);
        $idActor = (int) $registro['id_cliente'];
    }

    $stmt = appe_conexion()->prepare('UPDATE tokenrecuperacion SET usado = 1 WHERE id_token = ?');
    $stmt->execute([(int) $registro['id_token']]);

    auditar('restablecimiento_clave', (string) $registro['tipo'], $idActor, 'Contraseña restablecida');

    responder_exito('Su contraseña fue actualizada. Ya puede iniciar sesión.', [
        'csrf' => csrf_token(),
    ]);
}, 'reestablecerclave.php');
