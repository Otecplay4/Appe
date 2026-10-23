<?php
/**
 * A.P.P.E. — Baja de un producto del catálogo.
 *
 * Requisito funcional RF-08 · Caso de uso CU-05.
 *
 * Correcciones:
 *   - Solo se admite POST: la eliminación no se ejecuta con GET (hallazgo 15).
 *   - El identificador se valida y la ruta final se comprueba con `realpath()`
 *     antes de borrar el archivo (hallazgo 7: path traversal).
 *   - Se borran el archivo Y el registro de la base de datos; antes solo se
 *     borraba el archivo y quedaban listados obsoletos (hallazgo 24).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/imagenes.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idProducto = id_requerido($_POST['id'] ?? null, 'Producto');

    $stmt = appe_conexion()->prepare('SELECT id_producto, nombre, extension FROM producto WHERE id_producto = ?');
    $stmt->execute([$idProducto]);
    $producto = $stmt->fetch();

    if ($producto === false) {
        throw new NoEncontradoException('El producto no existe.');
    }

    // 1. Se borra el archivo (con la comprobación de contención de la ruta).
    $archivoBorrado = eliminar_imagen(__DIR__ . '/img', $idProducto, (string) $producto['extension']);

    // 2. Se borra el registro. Si el archivo no se pudo borrar, la operación
    //    se informa como incompleta pero el registro no queda colgado.
    $stmt = appe_conexion()->prepare('DELETE FROM producto WHERE id_producto = ?');
    $stmt->execute([$idProducto]);

    auditar('baja_producto', 'producto', $idProducto, (string) $producto['nombre']);

    responder_exito('El producto fue eliminado correctamente.', [
        'id_producto'     => $idProducto,
        'archivo_borrado' => $archivoBorrado,
    ]);
}, 'eliminarpro.php');
