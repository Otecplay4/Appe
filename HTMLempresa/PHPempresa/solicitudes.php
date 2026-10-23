<?php
/**
 * A.P.P.E. — Solicitudes de cambio de rutina.
 *
 * Requisito funcional RF-14 · Caso de uso CU-06 (el entrenador aprueba o
 * rechaza la solicitud del cliente) y CU-04.
 *
 * Operaciones:
 *   - listar     : solicitudes con filtros por estado.
 *   - aprobar    : acepta la solicitud.
 *   - rechazar   : rechaza la solicitud con un motivo.
 *   - resolver   : alias de aprobar/rechazar según el estado enviado.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    // La comprobación de sesión y de rol se hace ANTES de resolver la
    // operación: de lo contrario un usuario anónimo recibiría un 422 en lugar
    // del 401 que corresponde (hallazgo 1).
    requiere_entrenador();

    $accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

    switch ($accion) {
        case 'listar':
            listar_solicitudes();
            break;
        case 'aprobar':
            resolver_solicitud('aprobada');
            break;
        case 'rechazar':
            resolver_solicitud('rechazada');
            break;
        default:
            throw new ValidacionException('La operación solicitada no es válida.');
    }
}, 'solicitudes.php');

/**
 * Listado de solicitudes de cambio de rutina.
 */
function listar_solicitudes(): void
{
    exigir_metodo('GET');
    requiere_entrenador();

    $estado = isset($_GET['estado']) && $_GET['estado'] !== ''
        ? enum_requerido($_GET['estado'], ['pendiente', 'aprobada', 'rechazada', 'todas'], 'Estado')
        : 'pendiente';

    $sql = 'SELECT s.id_solicitud, s.id_cliente, s.id_rutina, s.mensaje, s.estado, s.respuesta,
                   s.fecha, s.fecha_respuesta,
                   c.nombre, c.apellido, c.correo,
                   e.nombre AS entrenador_nombre,
                   r.nombre AS rutina_nombre
              FROM solicitudcambio s
              JOIN cliente c ON c.id_cliente = s.id_cliente
              LEFT JOIN entrenador e ON e.id_entrenador = s.id_entrenador
              LEFT JOIN rutina r ON r.id_rutina = s.id_rutina
             WHERE 1 = 1';
    $parametros = [];

    if ($estado !== 'todas') {
        $sql .= ' AND s.estado = ?';
        $parametros[] = $estado;
    }

    $sql .= ' ORDER BY s.fecha DESC LIMIT 200';

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    $solicitudes = array_map(static function (array $fila): array {
        return [
            'id_solicitud'       => (int) $fila['id_solicitud'],
            'id_cliente'         => (int) $fila['id_cliente'],
            'cliente'            => trim($fila['nombre'] . ' ' . $fila['apellido']),
            'correo'             => (string) $fila['correo'],
            'id_rutina'          => $fila['id_rutina'] === null ? null : (int) $fila['id_rutina'],
            'rutina_nombre'      => $fila['rutina_nombre'],
            'entrenador_nombre'  => $fila['entrenador_nombre'],
            'mensaje'            => (string) $fila['mensaje'],
            'estado'             => (string) $fila['estado'],
            'respuesta'          => $fila['respuesta'],
            'fecha'              => (string) $fila['fecha'],
            'fecha_respuesta'    => $fila['fecha_respuesta'],
        ];
    }, $filas);

    responder_exito('Solicitudes de cambio de rutina.', [
        'solicitudes' => $solicitudes,
    ]);
}

/**
 * Aprueba o rechaza una solicitud.
 */
function resolver_solicitud(string $estado): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $id = id_requerido($_POST['id'] ?? null, 'Solicitud');
    $respuesta = texto_opcional($_POST['respuesta'] ?? '', 'Respuesta', 500);

    if ($estado === 'rechazada' && $respuesta === '') {
        throw new ValidacionException('Debe indicar el motivo del rechazo.');
    }

    $stmt = appe_conexion()->prepare('SELECT id_solicitud, estado FROM solicitudcambio WHERE id_solicitud = ?');
    $stmt->execute([$id]);
    $solicitud = $stmt->fetch();

    if ($solicitud === false) {
        throw new NoEncontradoException('La solicitud no existe.');
    }

    if ($solicitud['estado'] !== 'pendiente') {
        throw new ConflictoException('La solicitud ya fue resuelta.');
    }

    $stmt = appe_conexion()->prepare(
        'UPDATE solicitudcambio SET estado = ?, respuesta = ?, fecha_respuesta = NOW() WHERE id_solicitud = ?'
    );
    $stmt->execute([$estado, $respuesta === '' ? null : $respuesta, $id]);

    auditar('resolucion_solicitud', 'solicitudcambio', $id, $estado);

    responder_exito(
        $estado === 'aprobada' ? 'Solicitud aprobada.' : 'Solicitud rechazada.',
        [
            'id_solicitud' => $id,
            'estado'       => $estado,
        ]
    );
}
