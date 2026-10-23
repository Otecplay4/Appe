<?php
/**
 * A.P.P.E. — Alta de entrenadores.
 *
 * Requisito funcional RF-05 «gestionar el registro completo de entrenadores
 * (alta, baja, modificación y consulta)».
 *
 * Hallazgo 2 (severidad alta): el alta era pública, de modo que cualquier
 * visitante obtenía una cuenta de entrenador. Ahora SOLO el Administrador
 * (rol 'admin' del PDF) puede crear cuentas de entrenador o de administrador.
 *
 * La validación que antes vivía aislada en `seguridadentrenador.php` se
 * aplica aquí, en el servidor (hallazgo 16).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();

    $cedula   = texto_requerido($_POST['cedula'] ?? '', 'Cédula', 50);
    $nombre   = texto_requerido($_POST['nombre'] ?? '', 'Nombre', 100);
    $apellido = texto_opcional($_POST['apellido'] ?? '', 'Apellido', 70);
    $correo   = correo_requerido($_POST['correo'] ?? '');
    $telefono = texto_requerido($_POST['telefono'] ?? '', 'Teléfono', 20);
    $clave    = (string) ($_POST['contraseña'] ?? '');
    $rol      = enum_requerido($_POST['rol'] ?? 'entrenador', ['entrenador', 'admin'], 'Rol');

    if (!preg_match('/^\d{6,10}$/', $cedula)) {
        throw new ValidacionException('La cédula debe tener entre 6 y 10 dígitos.');
    }

    if (!preg_match('/^[0-9+ ]{7,20}$/', $telefono)) {
        throw new ValidacionException('El teléfono no es válido.');
    }

    if (strlen($clave) < 8) {
        throw new ValidacionException('La contraseña debe tener al menos 8 caracteres.');
    }

    if (strlen($clave) > 200) {
        throw new ValidacionException('La contraseña es demasiado larga.');
    }

    // Cédula y correo únicos.
    $stmt = appe_conexion()->prepare('SELECT id_entrenador FROM entrenador WHERE cedula = ? OR correo = ?');
    $stmt->execute([$cedula, $correo]);

    if ($stmt->fetch() !== false) {
        throw new ConflictoException('Ya existe un entrenador con esa cédula o ese correo.');
    }

    $stmt = appe_conexion()->prepare(
        'INSERT INTO entrenador (cedula, nombre, apellido, telefono, correo, `contraseña`, rol, activo)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([
        $cedula,
        $nombre,
        $apellido,
        $telefono,
        $correo,
        password_hash($clave, PASSWORD_DEFAULT),
        $rol,
    ]);

    $id = (int) appe_conexion()->lastInsertId();

    auditar('alta_entrenador', 'entrenador', $id, $correo . ' / rol ' . $rol);

    responder_exito('Entrenador creado correctamente.', [
        'id_entrenador' => $id,
    ], 201);
}, 'crearentrenador.php');
