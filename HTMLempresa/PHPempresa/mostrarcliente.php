<?php
/**
 * A.P.P.E. — Listado de clientes con filtros dinámicos.
 *
 * Requisito funcional RF-06 «gestionar el registro completo de clientes» y
 * RF-12 «realizar búsquedas y filtros dinámicos sobre todos los registros».
 *
 * Hallazgo 3 (severidad alta): la versión anterior devolvía la columna
 * `contraseña` de todos los clientes sin exigir sesión. Ahora:
 *   - exige sesión de entrenador o administrador;
 *   - la contraseña NUNCA se incluye en la respuesta;
 *   - solo se devuelven los campos necesarios.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $texto = isset($_GET['q']) ? limpiar_texto($_GET['q']) : '';
    $idPlan = isset($_GET['id_plan']) && $_GET['id_plan'] !== ''
        ? entero_requerido($_GET['id_plan'], 'Plan', 1)
        : null;
    $idEntrenador = isset($_GET['id_entrenador']) && $_GET['id_entrenador'] !== ''
        ? entero_requerido($_GET['id_entrenador'], 'Entrenador', 1)
        : null;
    $estado = isset($_GET['estado']) && $_GET['estado'] !== ''
        ? enum_requerido($_GET['estado'], ['activos', 'inactivos', 'todos'], 'Estado')
        : 'activos';

    $paginacion = parametros_paginacion(200);

    $sql = 'SELECT c.id_cliente, c.nombre, c.apellido, c.correo, c.telefono, c.activo,
                   c.fecha_alta, c.id_plan, c.id_entrenador, c.id_rutina,
                   p.nombre AS plan_nombre,
                   e.nombre AS entrenador_nombre
              FROM cliente c
              LEFT JOIN plan p       ON p.id_plan = c.id_plan
              LEFT JOIN entrenador e ON e.id_entrenador = c.id_entrenador
             WHERE 1 = 1';

    $parametros = [];

    if ($texto !== '') {
        $like = '%' . escapar_like($texto) . '%';
        $sql .= ' AND (c.nombre LIKE ? OR c.apellido LIKE ? OR c.correo LIKE ?'
              . ' OR CONCAT(c.nombre, \' \', c.apellido) LIKE ?)';
        $parametros = array_merge($parametros, [$like, $like, $like, $like]);
    }

    if ($idPlan !== null) {
        $sql .= ' AND c.id_plan = ?';
        $parametros[] = $idPlan;
    }

    if ($idEntrenador !== null) {
        $sql .= ' AND c.id_entrenador = ?';
        $parametros[] = $idEntrenador;
    }

    if ($estado === 'activos') {
        $sql .= ' AND c.activo = 1';
    } elseif ($estado === 'inactivos') {
        $sql .= ' AND c.activo = 0';
    }

    // Recuento total para la paginación.
    $sqlCounta = preg_replace('/SELECT .*?FROM/s', 'SELECT COUNT(*) AS total FROM', $sql, 1);
    $stmtCounta = appe_conexion()->prepare((string) $sqlCounta);
    $stmtCounta->execute($parametros);
    $total = (int) $stmtCounta->fetchColumn();

    $sql .= ' ORDER BY c.nombre ASC, c.apellido ASC LIMIT ' . $paginacion['limite']
          . ' OFFSET ' . $paginacion['desplazamiento'];

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    $clientes = array_map(static function (array $fila): array {
        return [
            'id_cliente'        => (int) $fila['id_cliente'],
            'nombre'            => (string) $fila['nombre'],
            'apellido'          => (string) $fila['apellido'],
            'correo'            => (string) $fila['correo'],
            'telefono'          => $fila['telefono'],
            'activo'            => (int) $fila['activo'] === 1,
            'fecha_alta'        => (string) $fila['fecha_alta'],
            'id_plan'           => $fila['id_plan'] === null ? null : (int) $fila['id_plan'],
            'plan_nombre'       => $fila['plan_nombre'],
            'id_entrenador'     => $fila['id_entrenador'] === null ? null : (int) $fila['id_entrenador'],
            'entrenador_nombre' => $fila['entrenador_nombre'],
            'id_rutina'         => $fila['id_rutina'] === null ? null : (int) $fila['id_rutina'],
        ];
    }, $filas);

    responder_exito('Listado de clientes.', [
        'clientes'      => $clientes,
        'total'         => $total,
        'limite'        => $paginacion['limite'],
        'desplazamiento' => $paginacion['desplazamiento'],
    ]);
}, 'mostrarcliente.php');
