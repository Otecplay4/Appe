<?php
/**
 * A.P.P.E. — Imagen pública de un producto.
 *
 * La extensión se lee de la base de datos, nunca de la petición, y la ruta
 * final se comprueba con `realpath()` para impedir el path traversal
 * (hallazgo 7).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');

    $idProducto = entero_requerido($_GET['id'] ?? null, 'Producto', 1);

    $stmt = appe_conexion()->prepare(
        'SELECT extension FROM producto WHERE id_producto = ? AND activo = 1'
    );
    $stmt->execute([$idProducto]);
    $fila = $stmt->fetch();

    if ($fila === false) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    servir_imagen(
        __DIR__ . '/../../HTMLempresa/PHPempresa/img',
        (string) ($fila['extension'] ?? ''),
        $idProducto,
        __DIR__ . '/../imagenes/OLYMPIA.jpg'
    );
}, 'imagenproducto.php');
