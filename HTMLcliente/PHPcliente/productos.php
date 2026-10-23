<?php
/**
 * A.P.P.E. — Catálogo público de productos.
 *
 * Requisito funcional RF-08 (gestionar el catálogo de productos) en su cara
 * pública y RF-12 (filtros).
 *
 * Las categorías de la interfaz (ropa, suplementos, accesorios) se traducen al
 * enumerado `tipo_de_producto` del MER (atuendo, consumible, accesorio).
 * Solo se publican los productos activos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');

    $idProducto = isset($_GET['id']) ? entero_requerido($_GET['id'], 'Producto', 1) : null;
    $categoria  = isset($_GET['cat']) ? trim((string) $_GET['cat']) : null;

    $sql = 'SELECT id_producto, nombre, descripcion, tipo_de_producto, precio, stock, extension
              FROM producto
             WHERE activo = 1';

    $parametros = [];

    if ($categoria !== null && $categoria !== '') {
        $tipo = tipo_producto_desde_categoria($categoria);

        if ($tipo === null) {
            throw new ValidacionException('La categoría solicitada no es válida.');
        }

        $sql .= ' AND tipo_de_producto = ?';
        $parametros[] = $tipo;
    }

    if ($idProducto !== null) {
        $sql .= ' AND id_producto = ?';
        $parametros[] = $idProducto;
    }

    $sql .= ' ORDER BY id_producto ASC';

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
        ];
    }, $filas);

    if ($idProducto !== null && $productos === []) {
        throw new NoEncontradoException('El producto solicitado no existe.');
    }

    responder_exito('Catálogo de productos.', [
        'productos' => $idProducto !== null ? $productos[0] : $productos,
    ]);
}, 'productos.php');
