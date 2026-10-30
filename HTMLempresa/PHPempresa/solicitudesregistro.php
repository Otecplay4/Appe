<?php
/**
 * A.P.P.E. — Revisión de solicitudes de registro de entrenadores.
 *
 * Solo el Administrador. Lista las solicitudes enviadas desde
 * `crearentrenador.php` y las aprueba (se inserta la cuenta en `entrenador`)
 * o las rechaza (con motivo obligatorio).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

// Archivo JSON donde se guardan las solicitudes.
define('ARCHIVO_SOLICITUDES', __DIR__ . '/../../config/solicitudes_registro.json');

function leer_solicitudes(): array
{
    if (!file_exists(ARCHIVO_SOLICITUDES)) {
        return [];
    }
    $contenido = file_get_contents(ARCHIVO_SOLICITUDES);
    $datos = json_decode($contenido, true);
    return is_array($datos) ? $datos : [];
}

function guardar_solicitudes(array $solicitudes): void
{
    $escrito = file_put_contents(ARCHIVO_SOLICITUDES, json_encode($solicitudes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    if ($escrito === false) {
        throw new RuntimeException('No se pudo escribir ' . ARCHIVO_SOLICITUDES . ' (revisar permisos de config/).');
    }
}

ejecutar_endpoint(static function (): void {
    requiere_admin();

    $accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

    switch ($accion) {
        case 'listar':
            listar_solicitudes_registro();
            break;
        case 'aprobar':
            resolver_solicitud_registro('aprobada');
            break;
        case 'rechazar':
            resolver_solicitud_registro('rechazada');
            break;
        default:
            throw new ValidacionException('La operación solicitada no es válida.');
    }
}, 'solicitudesregistro.php');

function listar_solicitudes_registro(): void
{
    exigir_metodo('GET');
    requiere_admin();

    $estado = isset($_GET['estado']) && $_GET['estado'] !== ''
        ? enum_requerido($_GET['estado'], ['pendiente', 'aprobada', 'rechazada', 'todas'], 'Estado')
        : 'pendiente';

    $solicitudes = leer_solicitudes();

    if ($estado !== 'todas') {
        $solicitudes = array_values(array_filter($solicitudes, static function (array $s) use ($estado): bool {
            return ($s['estado'] ?? '') === $estado;
        }));
    }

    // Ordenar por fecha de solicitud descendente
    usort($solicitudes, static function (array $a, array $b): int {
        return strcmp($b['fecha_solicitud'] ?? '', $a['fecha_solicitud'] ?? '');
    });

    $resultado = array_map(static function (array $s): array {
        return [
            'id_solicitud'       => (int) ($s['id'] ?? 0),
            'cedula'             => (string) ($s['cedula'] ?? ''),
            'nombre'             => (string) ($s['nombre'] ?? ''),
            'apellido'           => $s['apellido'] ?? null,
            'telefono'           => (string) ($s['telefono'] ?? ''),
            'correo'             => (string) ($s['correo'] ?? ''),
            'rol'                => (string) ($s['rol'] ?? 'entrenador'),
            'estado'             => (string) ($s['estado'] ?? 'pendiente'),
            'motivo_rechazo'     => $s['motivo_rechazo'] ?? null,
            'fecha_solicitud'    => (string) ($s['fecha_solicitud'] ?? ''),
            'fecha_resolucion'   => $s['fecha_resolucion'] ?? null,
            'id_admin_resolutor' => $s['id_admin_resolutor'] ?? null,
        ];
    }, $solicitudes);

    responder_exito('Solicitudes de registro.', [
        'solicitudes' => $resultado,
    ]);
}

function resolver_solicitud_registro(string $estado): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_admin();

    $id = id_requerido($_POST['id'] ?? null, 'Solicitud');
    $respuesta = texto_opcional($_POST['respuesta'] ?? '', 'Respuesta', 500);

    if ($estado === 'rechazada' && $respuesta === '') {
        throw new ValidacionException('Debe indicar el motivo del rechazo.');
    }

    $solicitudes = leer_solicitudes();
    $encontrada = null;
    $indice = null;

    foreach ($solicitudes as $i => $s) {
        if ((int) ($s['id'] ?? 0) === $id) {
            $encontrada = $s;
            $indice = $i;
            break;
        }
    }

    if ($encontrada === null) {
        throw new NoEncontradoException('La solicitud no existe.');
    }

    if (($encontrada['estado'] ?? '') !== 'pendiente') {
        throw new ConflictoException('La solicitud ya fue resuelta.');
    }

    $idAdmin = entrenador_id_actual();

    if ($estado === 'aprobada') {
        $pdo = appe_conexion();
        $stmt = $pdo->prepare('SELECT id_entrenador FROM entrenador WHERE cedula = ? OR correo = ?');
        $stmt->execute([(string) $encontrada['cedula'], (string) $encontrada['correo']]);

        if ($stmt->fetch() !== false) {
            throw new ConflictoException('Ya existe un entrenador con esa cédula o ese correo.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO entrenador (cedula, nombre, apellido, telefono, correo, `contraseña`, rol, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            (string) $encontrada['cedula'],
            (string) $encontrada['nombre'],
            $encontrada['apellido'],
            (string) $encontrada['telefono'],
            (string) $encontrada['correo'],
            (string) $encontrada['contraseña'],
            (string) $encontrada['rol'],
        ]);

        $idEntrenador = (int) $pdo->lastInsertId();
    }

    // Marcar la solicitud como resuelta
    $solicitudes[$indice]['estado'] = $estado;
    $solicitudes[$indice]['motivo_rechazo'] = $estado === 'rechazada' ? $respuesta : null;
    $solicitudes[$indice]['fecha_resolucion'] = date('Y-m-d H:i:s');
    $solicitudes[$indice]['id_admin_resolutor'] = $idAdmin;

    guardar_solicitudes($solicitudes);

    auditar(
        $estado === 'aprobada' ? 'aprobacion_registro' : 'rechazo_registro',
        'solicitudregistro',
        $id,
        (string) $encontrada['correo'] . ' / rol ' . $encontrada['rol']
    );

    $mensaje = $estado === 'aprobada'
        ? 'Solicitud aprobada. La cuenta fue creada correctamente.'
        : 'Solicitud rechazada.';

    responder_exito($mensaje, [
        'id_solicitud' => $id,
        'estado'       => $estado,
    ]);
}
