<?php
/**
 * A.P.P.E. — Listado de productos del catálogo (gestión de la empresa).
 *
 * Requisito funcional RF-08 · Caso de uso CU-05.
 *
 * A diferencia del catálogo público, aquí se muestran TODOS los productos,
 * incluidos los inactivos, porque es la vista de administración.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $tipo = isset($_GET['tipo']) && $_GET['tipo'] !== ''
        ? enum_requerido($_GET['tipo'], ['consumible', 'accesorio', 'atuendo'], 'Tipo de producto')
        : null;

    $sql = 'SELECT id_producto, nombre, descripcion, tipo_de_producto, precio, stock, extension, activo
              FROM producto
             WHERE 1 = 1';
    $parametros = [];

    if ($tipo !== null) {
        $sql .= ' AND tipo_de_producto = ?';
        $parametros[] = $tipo;
    }

    $sql .= ' ORDER BY activo DESC, id_producto ASC';

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    $productos = array_map(static function (array $fila): array {
        return [
            'id_producto'      => (int) $fila['id_producto'],
            'nombre'           => (string) $fila['nombre'],
            'descripcion'      => $fila['descripcion'],
            'tipo_de_producto' => (string) $fila['tipo_de_producto'],
            'precio'           => (float) $fila['precio'],
            'stock'            => (int) $fila['stock'],
            'extension'        => (string) $fila['extension'],
            'activo'           => (int) $fila['activo'] === 1,
        ];
    }, $filas);

    responder_exito('Listado de productos.', [
        'productos' => $productos,
    ]);
}, 'mostrarprod.php');
