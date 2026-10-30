<?php
/**
 * A.P.P.E. — Panel centralizado de administración.
 *
 * Requisito funcional RF-11 «ofrecer un panel centralizado de administración
 * para la gestión del sistema» · Caso de uso CU-04 «Consultar Información y
 * Estado de Cuenta» (actor Administrador/Entrenador).
 *
 * Devuelve indicadores y listados de resumen. Todos los cálculos se hacen en
 * el servidor con consultas preparadas.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $pdo = appe_conexion();

    // --- Indicadores de clientes ---------------------------------------------
    $stmt = $pdo->query(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos,
                SUM(CASE WHEN id_plan IS NULL THEN 1 ELSE 0 END) AS sin_plan,
                SUM(CASE WHEN id_rutina IS NULL THEN 1 ELSE 0 END) AS sin_rutina
           FROM cliente'
    );
    $clientes = (array) $stmt->fetch();

    // --- Indicadores de rutinas ----------------------------------------------
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) AS activas
           FROM rutina"
    );
    $rutinas = (array) $stmt->fetch();

    // --- Indicadores de catálogo ---------------------------------------------
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                COALESCE(SUM(stock), 0) AS unidades
           FROM producto"
    );
    $productos = (array) $stmt->fetch();

    $stmt = $pdo->query(
        'SELECT COUNT(*) AS total, SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos FROM plan'
    );
    $planes = (array) $stmt->fetch();

    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM proveedor');
    $proveedores = (array) $stmt->fetch();

    // --- Indicadores de facturación ------------------------------------------
    $stmt = $pdo->query(
        "SELECT
            COALESCE(SUM(CASE WHEN estado = 'pagada' THEN monto END), 0)   AS cobrado,
            COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN monto END), 0) AS pendiente,
            COALESCE(SUM(CASE WHEN estado = 'vencida' THEN monto END), 0)    AS vencido,
            SUM(CASE WHEN estado <> 'anulada'
                      AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                     THEN 1 ELSE 0 END) AS vencen_pronto
           FROM cuota"
    );
    $facturacion = (array) $stmt->fetch();

    // --- Pendientes de atención ------------------------------------------------
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM solicitudcambio WHERE estado = 'pendiente'");
    $solicitudes = (array) $stmt->fetch();

    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM mensajecontacto WHERE leido = 0');
    $mensajes = (array) $stmt->fetch();

    // --- Últimos clientes dados de alta ---------------------------------------
    $stmt = $pdo->query(
        'SELECT id AS id_cliente, nombre, apellido, correo, fecha_alta
           FROM cliente
          ORDER BY id DESC
          LIMIT 5'
    );
    $ultimosClientes = $stmt->fetchAll();

    // --- Próximos vencimientos -------------------------------------------------
    $stmt = $pdo->query(
        "SELECT q.id_cuota, q.concepto, q.monto, q.fecha_vencimiento, q.estado,
                c.nombre, c.apellido
           FROM cuota q
           JOIN cliente c ON c.id = q.id_cliente
          WHERE q.estado IN ('pendiente', 'vencida')
          ORDER BY q.fecha_vencimiento ASC
          LIMIT 8"
    );
    $vencimientos = $stmt->fetchAll();

    // --- Solicitudes de cambio de rutina --------------------------------------
    $stmt = $pdo->query(
        "SELECT s.id_solicitud, s.mensaje, s.fecha, c.nombre, c.apellido
           FROM solicitudcambio s
           JOIN cliente c ON c.id = s.id_cliente
          WHERE s.estado = 'pendiente'
          ORDER BY s.fecha ASC
          LIMIT 5"
    );
    $cambiosPendientes = $stmt->fetchAll();

    responder_exito('Panel de administración.', [
        'indicadores' => [
            'clientes' => [
                'total'      => (int) ($clientes['total'] ?? 0),
                'activos'    => (int) ($clientes['activos'] ?? 0),
                'inactivos'  => (int) ($clientes['inactivos'] ?? 0),
                'sin_plan'   => (int) ($clientes['sin_plan'] ?? 0),
                'sin_rutina' => (int) ($clientes['sin_rutina'] ?? 0),
            ],
            'rutinas' => [
                'total'  => (int) ($rutinas['total'] ?? 0),
                'activas' => (int) ($rutinas['activas'] ?? 0),
            ],
            'productos' => [
                'total'     => (int) ($productos['total'] ?? 0),
                'activos'   => (int) ($productos['activos'] ?? 0),
                'unidades'  => (int) ($productos['unidades'] ?? 0),
            ],
            'planes' => [
                'total'   => (int) ($planes['total'] ?? 0),
                'activos' => (int) ($planes['activos'] ?? 0),
            ],
            'proveedores' => [
                'total' => (int) ($proveedores['total'] ?? 0),
            ],
            'facturacion' => [
                'cobrado'        => (float) ($facturacion['cobrado'] ?? 0),
                'pendiente'      => (float) ($facturacion['pendiente'] ?? 0),
                'vencido'        => (float) ($facturacion['vencido'] ?? 0),
                'vencen_pronto'  => (int) ($facturacion['vencen_pronto'] ?? 0),
            ],
            'pendientes' => [
                'solicitudes' => (int) ($solicitudes['total'] ?? 0),
                'mensajes'    => (int) ($mensajes['total'] ?? 0),
            ],
        ],
        'ultimos_clientes'    => $ultimosClientes,
        'proximos_vencimientos' => $vencimientos,
        'cambios_pendientes'  => $cambiosPendientes,
    ]);
}, 'panel.php');
