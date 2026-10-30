<?php
/**
 * A.P.P.E. — Solicitud de alta de entrenadores.
 *
 * Requisito funcional RF-05 «gestionar el registro completo de entrenadores
 * (alta, baja, modificación y consulta)».
 *
 * Flujo de registro con aprobación:
 *   1. Cualquier persona puede ENVIAR una solicitud de registro (este endpoint).
 *   2. La solicitud NO crea la cuenta: se guarda como «pendiente» en
 *      `config/solicitudes_registro.json` (fuera de la base de datos), con la
 *      contraseña ya cifrada.
 *   3. Solo el Administrador la aprueba o rechaza desde
 *      `PHPempresa/solicitudesregistro.php`; recién al aprobar se inserta la
 *      fila en la tabla `entrenador`.
 *
 * Hallazgo 2 (severidad alta): el alta directa era pública. Por eso este
 * endpoint nunca inserta en `entrenador`.
 *
 * La validación que antes vivía aislada en `seguridadentrenador.php` se
 * aplica aquí, en el servidor (hallazgo 16).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

// Archivo JSON donde se guardan las solicitudes (NO en la base de datos).
// El directorio `config/` está protegido por su `.htaccess`.
define('ARCHIVO_SOLICITUDES', __DIR__ . '/../../config/solicitudes_registro.json');

/**
 * @return array<int, array<string, mixed>>
 */
function leer_solicitudes(): array
{
    if (!file_exists(ARCHIVO_SOLICITUDES)) {
        return [];
    }

    $contenido = file_get_contents(ARCHIVO_SOLICITUDES);
    $datos = json_decode($contenido === false ? '' : $contenido, true);

    return is_array($datos) ? $datos : [];
}

/**
 * @param array<int, array<string, mixed>> $solicitudes
 */
function guardar_solicitudes(array $solicitudes): void
{
    $escrito = file_put_contents(
        ARCHIVO_SOLICITUDES,
        json_encode($solicitudes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    // Si el servidor web no puede escribir en `config/`, no se debe responder
    // «enviada» cuando la solicitud se perdió.
    if ($escrito === false) {
        throw new RuntimeException('No se pudo escribir ' . ARCHIVO_SOLICITUDES . ' (revisar permisos de config/).');
    }
}

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

    // Cédula y correo únicos entre los entrenadores ya existentes.
    $stmt = appe_conexion()->prepare('SELECT id_entrenador FROM entrenador WHERE cedula = ? OR correo = ?');
    $stmt->execute([$cedula, $correo]);

    if ($stmt->fetch() !== false) {
        throw new ConflictoException('Ya existe un entrenador con esa cédula o ese correo.');
    }

    // No puede haber dos solicitudes pendientes con el mismo correo.
    $solicitudes = leer_solicitudes();

    foreach ($solicitudes as $s) {
        if (($s['correo'] ?? '') === $correo && ($s['estado'] ?? '') === 'pendiente') {
            throw new ConflictoException('Ya existe una solicitud de registro pendiente con ese correo.');
        }
    }

    $nuevoId = 1;

    foreach ($solicitudes as $s) {
        if ((int) ($s['id'] ?? 0) >= $nuevoId) {
            $nuevoId = (int) $s['id'] + 1;
        }
    }

    $solicitudes[] = [
        'id'                 => $nuevoId,
        'cedula'             => $cedula,
        'nombre'             => $nombre,
        'apellido'           => $apellido === '' ? null : $apellido,
        'telefono'           => $telefono,
        'correo'             => $correo,
        'contraseña'         => password_hash($clave, PASSWORD_DEFAULT),
        'rol'                => $rol,
        'estado'             => 'pendiente',
        'motivo_rechazo'     => null,
        'fecha_solicitud'    => date('Y-m-d H:i:s'),
        'fecha_resolucion'   => null,
        'id_admin_resolutor' => null,
    ];

    guardar_solicitudes($solicitudes);

    auditar('solicitud_registro', 'solicitudregistro', $nuevoId, $correo . ' / rol ' . $rol);

    responder_exito('Solicitud de registro enviada. El administrador la revisará y te avisará por correo.', [
        'id_solicitud' => $nuevoId,
    ], 201);
}, 'crearentrenador.php');
