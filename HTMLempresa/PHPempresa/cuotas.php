<?php
/**
 * A.P.P.E. — Gestión de cuotas y estado de cuenta.
 *
 * Requisito funcional RF-10 «consultar el estado de pago de las cuotas y
 * visualizar los vencimientos próximos» · Caso de uso CU-04.
 *
 * Operaciones:
 *   - listar   : cuotas con filtros (cliente, estado, vencimientos).
 *   - crear    : emite una cuota para un cliente.
 *   - actualizar : modifica importe, fechas o estado de pago.
 *   - eliminar : anula/elimina una cuota.
 *   - resumen  : indicadores de facturación para el panel.
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
            listar_cuotas();
            break;
        case 'crear':
            crear_cuota();
            break;
        case 'actualizar':
            actualizar_cuota();
            break;
        case 'eliminar':
            eliminar_cuota();
            break;
        case 'resumen':
            resumen_cuotas();
            break;
        default:
            throw new ValidacionException('La operación solicitada no es válida.');
    }
}, 'cuotas.php');

/**
 * Listado de cuotas con filtros dinámicos.
 */
function listar_cuotas(): void
{
    exigir_metodo('GET');
    requiere_entrenador();

    $paginacion = parametros_paginacion(200);

    $sql = 'SELECT q.id_cuota, q.id_cliente, q.id_plan, q.concepto, q.monto,
                   q.fecha_emision, q.fecha_vencimiento, q.fecha_pago, q.estado, q.forma_pago,
                   c.nombre, c.apellido, c.correo
              FROM cuota q
              JOIN cliente c ON c.id = q.id_cliente
             WHERE 1 = 1';
    $parametros = [];

    $estado = isset($_GET['estado']) && $_GET['estado'] !== ''
        ? enum_requerido($_GET['estado'], ['pendiente', 'pagada', 'vencida', 'anulada'], 'Estado')
        : null;

    if ($estado !== null) {
        $sql .= ' AND q.estado = ?';
        $parametros[] = $estado;
    }

    if (isset($_GET['id_cliente']) && $_GET['id_cliente'] !== '') {
        $sql .= ' AND q.id_cliente = ?';
        $parametros[] = entero_requerido($_GET['id_cliente'], 'Cliente', 1);
    }

    // Solo las cuotas que vencen dentro del horizonte indicado.
    if (isset($_GET['proximos']) && $_GET['proximos'] !== '') {
        $dias = entero_requerido($_GET['proximos'], 'Días', 1, 365);
        $sql .= ' AND q.estado <> \'anulada\' AND q.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)';
        $parametros[] = $dias;
    }

    $sql .= ' ORDER BY q.estado = \'pagada\', q.fecha_vencimiento ASC, q.id_cuota DESC'
          . ' LIMIT ' . $paginacion['limite'] . ' OFFSET ' . $paginacion['desplazamiento'];

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    $cuotas = array_map(static function (array $fila): array {
        return [
            'id_cuota'          => (int) $fila['id_cuota'],
            'id_cliente'        => (int) $fila['id_cliente'],
            'cliente'           => trim($fila['nombre'] . ' ' . $fila['apellido']),
            'correo'            => (string) $fila['correo'],
            'id_plan'           => $fila['id_plan'] === null ? null : (int) $fila['id_plan'],
            'concepto'          => (string) $fila['concepto'],
            'monto'             => (float) $fila['monto'],
            'fecha_emision'     => (string) $fila['fecha_emision'],
            'fecha_vencimiento' => (string) $fila['fecha_vencimiento'],
            'fecha_pago'        => $fila['fecha_pago'],
            'estado'            => (string) $fila['estado'],
            'forma_pago'        => $fila['forma_pago'],
        ];
    }, $filas);

    responder_exito('Listado de cuotas.', [
        'cuotas'        => $cuotas,
        'limite'        => $paginacion['limite'],
        'desplazamiento' => $paginacion['desplazamiento'],
    ]);
}

/**
 * Emisión de una cuota.
 */
function crear_cuota(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idCliente = id_requerido($_POST['id_cliente'] ?? null, 'Cliente');
    $concepto  = texto_requerido($_POST['concepto'] ?? '', 'Concepto', 200);
    $monto     = decimal_requerido($_POST['monto'] ?? null, 'Monto', 0.01, 99999999.99);
    $emision   = fecha_requerida($_POST['fecha_emision'] ?? date('Y-m-d'), 'Fecha de emisión');
    $vencimiento = fecha_requerida($_POST['fecha_vencimiento'] ?? null, 'Fecha de vencimiento');
    $formaPago = texto_opcional($_POST['forma_pago'] ?? '', 'Forma de pago', 30);
    $estado    = enum_requerido($_POST['estado'] ?? 'pendiente', ['pendiente', 'pagada', 'anulada'], 'Estado');
    $idPlan    = isset($_POST['id_plan']) && $_POST['id_plan'] !== ''
        ? entero_requerido($_POST['id_plan'], 'Plan', 1)
        : null;

    if (strtotime($vencimiento) < strtotime($emision)) {
        throw new ValidacionException('La fecha de vencimiento no puede ser anterior a la de emisión.');
    }

    $stmt = appe_conexion()->prepare('SELECT id FROM cliente WHERE id = ?');
    $stmt->execute([$idCliente]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('El cliente no existe.');
    }

    $fechaPago = $estado === 'pagada' ? date('Y-m-d') : null;

    $stmt = appe_conexion()->prepare(
        'INSERT INTO cuota (id_cliente, id_plan, concepto, monto, fecha_emision, fecha_vencimiento,
                            fecha_pago, estado, forma_pago)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $idCliente,
        $idPlan,
        $concepto,
        $monto,
        $emision,
        $vencimiento,
        $fechaPago,
        $estado,
        $formaPago === '' ? null : $formaPago,
    ]);

    $id = (int) appe_conexion()->lastInsertId();

    auditar('alta_cuota', 'cuota', $id, $concepto);

    responder_exito('Cuota registrada correctamente.', [
        'id_cuota' => $id,
    ], 201);
}

/**
 * Modificación de una cuota.
 */
function actualizar_cuota(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idCuota = id_requerido($_POST['id'] ?? null, 'Cuota');

    $stmt = appe_conexion()->prepare('SELECT id_cuota FROM cuota WHERE id_cuota = ?');
    $stmt->execute([$idCuota]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('La cuota no existe.');
    }

    $campos = [];
    $parametros = [];

    if (isset($_POST['concepto'])) {
        $campos[] = 'concepto = ?';
        $parametros[] = texto_requerido($_POST['concepto'], 'Concepto', 200);
    }

    if (isset($_POST['monto'])) {
        $campos[] = 'monto = ?';
        $parametros[] = decimal_requerido($_POST['monto'], 'Monto', 0.01, 99999999.99);
    }

    if (isset($_POST['fecha_vencimiento'])) {
        $campos[] = 'fecha_vencimiento = ?';
        $parametros[] = fecha_requerida($_POST['fecha_vencimiento'], 'Fecha de vencimiento');
    }

    if (isset($_POST['estado'])) {
        $estado = enum_requerido($_POST['estado'], ['pendiente', 'pagada', 'vencida', 'anulada'], 'Estado');
        $campos[] = 'estado = ?';
        $parametros[] = $estado;

        if ($estado === 'pagada') {
            $campos[] = 'fecha_pago = COALESCE(fecha_pago, CURDATE())';
        }
    }

    if (isset($_POST['forma_pago'])) {
        $forma = texto_opcional($_POST['forma_pago'], 'Forma de pago', 30);
        $campos[] = 'forma_pago = ?';
        $parametros[] = $forma === '' ? null : $forma;
    }

    if ($campos === []) {
        throw new ValidacionException('No se envió ningún dato para modificar.');
    }

    $parametros[] = $idCuota;

    $stmt = appe_conexion()->prepare('UPDATE cuota SET ' . implode(', ', $campos) . ' WHERE id_cuota = ?');
    $stmt->execute($parametros);

    auditar('modificacion_cuota', 'cuota', $idCuota, implode(', ', $campos));

    responder_exito('Cuota actualizada correctamente.', [
        'id_cuota' => $idCuota,
    ]);
}

/**
 * Eliminación de una cuota.
 */
function eliminar_cuota(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idCuota = id_requerido($_POST['id'] ?? null, 'Cuota');

    $stmt = appe_conexion()->prepare('SELECT id_cuota FROM cuota WHERE id_cuota = ?');
    $stmt->execute([$idCuota]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('La cuota no existe.');
    }

    $stmt = appe_conexion()->prepare('DELETE FROM cuota WHERE id_cuota = ?');
    $stmt->execute([$idCuota]);

    auditar('baja_cuota', 'cuota', $idCuota, 'Cuota eliminada');

    responder_exito('La cuota fue eliminada correctamente.', [
        'id_cuota' => $idCuota,
    ]);
}

/**
 * Indicadores de facturación para el panel de administración.
 */
function resumen_cuotas(): void
{
    exigir_metodo('GET');
    requiere_entrenador();

    $stmt = appe_conexion()->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN estado = 'pagada' THEN monto END), 0)        AS cobrado,
            COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN monto END), 0)      AS pendiente,
            COALESCE(SUM(CASE WHEN estado = 'vencida' THEN monto END), 0)         AS vencido,
            COUNT(*)                                                            AS total,
            SUM(CASE WHEN estado <> 'anulada' AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS proximos
           FROM cuota"
    );
    $stmt->execute();
    $resumen = $stmt->fetch() ?: [];

    responder_exito('Resumen de cuotas.', [
        'resumen' => [
            'cobrado'   => (float) ($resumen['cobrado'] ?? 0),
            'pendiente' => (float) ($resumen['pendiente'] ?? 0),
            'vencido'   => (float) ($resumen['vencido'] ?? 0),
            'total'     => (int) ($resumen['total'] ?? 0),
            'proximos'  => (int) ($resumen['proximos'] ?? 0),
        ],
    ]);
}
