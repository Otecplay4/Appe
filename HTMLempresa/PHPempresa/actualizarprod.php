<?php
/**
 * A.P.P.E. — Modificación de un producto del catálogo.
 *
 * Requisito funcional RF-08 · Caso de uso CU-05.
 *
 * Hallazgo 28: la versión anterior solo leía un parámetro y no actualizaba
 * nada. Ahora se modifican todos los atributos editables y se comprueba el
 * resultado real de la operación (hallazgo 25).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idProducto = id_requerido($_POST['id'] ?? null, 'Producto');

    $stmt = appe_conexion()->prepare('SELECT id_producto FROM producto WHERE id_producto = ?');
    $stmt->execute([$idProducto]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('El producto no existe.');
    }

    $campos = [];
    $parametros = [];

    if (isset($_POST['nombre'])) {
        $campos[] = 'nombre = ?';
        $parametros[] = texto_requerido($_POST['nombre'], 'Nombre', 50);
    }

    if (isset($_POST['descripcion'])) {
        $descripcion = texto_opcional($_POST['descripcion'], 'Descripción', 200);
        $campos[] = 'descripcion = ?';
        $parametros[] = $descripcion === '' ? null : $descripcion;
    }

    if (isset($_POST['precio'])) {
        $campos[] = 'precio = ?';
        $parametros[] = decimal_requerido($_POST['precio'], 'Precio', 0, 99999999.99);
    }

    if (isset($_POST['stock'])) {
        $campos[] = 'stock = ?';
        $parametros[] = entero_requerido($_POST['stock'], 'Stock', 0, 1000000);
    }

    if (isset($_POST['tipo_de_producto'])) {
        $campos[] = 'tipo_de_producto = ?';
        $parametros[] = enum_requerido(
            $_POST['tipo_de_producto'],
            ['consumible', 'accesorio', 'atuendo'],
            'Tipo de producto'
        );
    }

    if (isset($_POST['activo'])) {
        $campos[] = 'activo = ?';
        $parametros[] = (int) enum_requerido($_POST['activo'], ['0', '1'], 'Estado');
    }

    if ($campos === []) {
        throw new ValidacionException('No se envió ningún dato para modificar.');
    }

    $parametros[] = $idProducto;

    $stmt = appe_conexion()->prepare('UPDATE producto SET ' . implode(', ', $campos) . ' WHERE id_producto = ?');
    $stmt->execute($parametros);

    auditar('modificacion_producto', 'producto', $idProducto, implode(', ', $campos));

    $stmt = appe_conexion()->prepare(
        'SELECT id_producto, nombre, descripcion, tipo_de_producto, precio, stock, extension, activo
           FROM producto WHERE id_producto = ?'
    );
    $stmt->execute([$idProducto]);
    $fila = $stmt->fetch();

    responder_exito('Producto actualizado correctamente.', [
        'producto' => $fila === false ? null : [
            'id_producto'      => (int) $fila['id_producto'],
            'nombre'           => (string) $fila['nombre'],
            'descripcion'      => $fila['descripcion'],
            'tipo_de_producto' => (string) $fila['tipo_de_producto'],
            'precio'           => (float) $fila['precio'],
            'stock'            => (int) $fila['stock'],
            'extension'        => (string) $fila['extension'],
            'activo'           => (int) $fila['activo'] === 1,
        ],
    ]);
}, 'actualizarprod.php');
