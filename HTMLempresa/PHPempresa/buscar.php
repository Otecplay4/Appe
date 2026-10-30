<?php
/**
 * A.P.P.E. — Búsqueda y filtros dinámicos sobre todos los registros.
 *
 * Requisito funcional RF-12 «realizar búsquedas y filtros dinámicos sobre
 * todos los registros almacenados» · Caso de uso CU-04 (flujo principal,
 * paso 2: «aplica filtros o criterios de búsqueda»).
 *
 * Permite filtrar por tipo de registro y, dentro de cada tipo, por texto y
 * por atributos propios. Los comodines de LIKE se escapan.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $tipo = enum_requerido($_GET['tipo'] ?? 'clientes', ['clientes', 'entrenadores', 'planes', 'productos', 'proveedores', 'rutinas'], 'Tipo de registro');
    $texto = isset($_GET['q']) ? limpiar_texto($_GET['q']) : '';
    $limite = isset($_GET['limite']) ? entero_requerido($_GET['limite'], 'Límite', 1, 200) : 50;

    $like = $texto === '' ? null : '%' . escapar_like($texto) . '%';

    switch ($tipo) {
        case 'clientes':
            $sql = 'SELECT id AS id, CONCAT(nombre, \' \', apellido) AS etiqueta,
                           correo AS detalle, activo AS estado
                      FROM cliente';
            $parametros = [];

            if ($like !== null) {
                $sql .= ' WHERE nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?';
                $parametros = [$like, $like, $like];
            }

            $sql .= ' ORDER BY nombre ASC LIMIT ' . $limite;
            break;

        case 'entrenadores':
            $sql = 'SELECT id_entrenador AS id, CONCAT(nombre, \' \', apellido) AS etiqueta,
                           correo AS detalle, activo AS estado
                      FROM entrenador';
            $parametros = [];

            if ($like !== null) {
                $sql .= ' WHERE nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR cedula LIKE ?';
                $parametros = [$like, $like, $like, $like];
            }

            $sql .= ' ORDER BY nombre ASC LIMIT ' . $limite;
            break;

        case 'planes':
            $sql = 'SELECT id_plan AS id, nombre AS etiqueta, descripcion AS detalle, activo AS estado
                      FROM plan';
            $parametros = [];

            if ($like !== null) {
                $sql .= ' WHERE nombre LIKE ? OR descripcion LIKE ? OR beneficio LIKE ?';
                $parametros = [$like, $like, $like];
            }

            $sql .= ' ORDER BY nombre ASC LIMIT ' . $limite;
            break;

        case 'productos':
            $sql = 'SELECT id_producto AS id, nombre AS etiqueta,
                           CONCAT(tipo_de_producto, \' · \', FORMAT(precio, 2)) AS detalle, activo AS estado
                      FROM producto';
            $parametros = [];

            if ($like !== null) {
                $sql .= ' WHERE nombre LIKE ? OR descripcion LIKE ?';
                $parametros = [$like, $like];
            }

            $sql .= ' ORDER BY nombre ASC LIMIT ' . $limite;
            break;

        case 'proveedores':
            $sql = 'SELECT id_proveedor AS id, nombre AS etiqueta, correo AS detalle, activo AS estado
                      FROM proveedor';
            $parametros = [];

            if ($like !== null) {
                $sql .= ' WHERE nombre LIKE ? OR correo LIKE ?';
                $parametros = [$like, $like];
            }

            $sql .= ' ORDER BY nombre ASC LIMIT ' . $limite;
            break;

        default: // rutinas
            $sql = 'SELECT r.id_rutina AS id, r.nombre AS etiqueta,
                           CONCAT(COALESCE(c.nombre, \'\'), \' \', COALESCE(c.apellido, \'\')) AS detalle,
                           r.estado AS estado
                      FROM rutina r
                      LEFT JOIN cliente c ON c.id = r.id_cliente';
            $parametros = [];

            if ($like !== null) {
                $sql .= ' WHERE r.nombre LIKE ?';
                $parametros = [$like];
            }

            $sql .= ' ORDER BY r.id_rutina DESC LIMIT ' . $limite;
            break;
    }

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    $resultados = array_map(static function (array $fila): array {
        return [
            'id'      => (int) $fila['id'],
            'etiqueta' => (string) $fila['etiqueta'],
            'detalle' => (string) ($fila['detalle'] ?? ''),
            'estado'  => (string) ($fila['estado'] ?? ''),
        ];
    }, $filas);

    responder_exito('Resultados de la búsqueda.', [
        'tipo'       => $tipo,
        'consulta'   => $texto,
        'resultados' => $resultados,
        'total'      => count($resultados),
    ]);
}, 'buscar.php');
