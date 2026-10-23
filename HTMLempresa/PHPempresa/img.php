<?php
/**
 * A.P.P.E. — Alta de un producto del catálogo.
 *
 * Requisito funcional RF-08 «gestionar el catálogo de productos disponibles
 * para la venta» · Caso de uso CU-05 «Administrar Productos y Proveedores».
 *
 * Correcciones respecto de la versión anterior:
 *   - Exige sesión y token CSRF (hallazgos 1 y 15).
 *   - Comprueba el código de error de la subida, el tamaño y el tipo real del
 *     archivo (hallazgo 8).
 *   - El registro en la base de datos y el archivo en disco quedan
 *     coordinados: si el archivo no se puede guardar, se borra el registro
 *     (hallazgo 24).
 *   - Devuelve JSON con `Content-Type` declarado (hallazgo 26).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/imagenes.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $nombre      = texto_requerido($_POST['nombre'] ?? '', 'Nombre del producto', 50);
    $descripcion = texto_opcional($_POST['descripcion'] ?? '', 'Descripción', 200);
    $precio      = decimal_requerido($_POST['precio'] ?? 0, 'Precio', 0, 99999999.99);
    $stock       = entero_requerido($_POST['stock'] ?? 0, 'Stock', 0, 1000000);
    $tipo        = enum_requerido(
        $_POST['tipo_de_producto'] ?? '',
        ['consumible', 'accesorio', 'atuendo'],
        'Tipo de producto'
    );

    if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
        throw new ValidacionException('Debe seleccionar una imagen del producto.');
    }

    $directorio = __DIR__ . '/img';

    if (!is_dir($directorio) && !@mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new ValidacionException('No se pudo preparar el directorio de imágenes.');
    }

    $pdo = appe_conexion();

    // 1. Se inserta el registro para obtener el identificador; el archivo se
    //    guardará con ese identificador como nombre.
    $stmt = $pdo->prepare(
        'INSERT INTO producto (id_gimnasio, precio, nombre, descripcion, tipo_de_producto, extension, stock, activo)
         VALUES (1, ?, ?, ?, ?, \'jpg\', ?, 1)'
    );
    $stmt->execute([$precio, $nombre, $descripcion === '' ? null : $descripcion, $tipo, $stock]);

    $idProducto = (int) $pdo->lastInsertId();

    // 2. Se guarda la imagen. Si algo falla, se deshace el alta (hallazgo 24).
    try {
        $extension = guardar_imagen($_FILES['archivo'], $directorio, (string) $idProducto);

        $stmt = $pdo->prepare('UPDATE producto SET extension = ? WHERE id_producto = ?');
        $stmt->execute([$extension, $idProducto]);
    } catch (Throwable $e) {
        $stmt = $pdo->prepare('DELETE FROM producto WHERE id_producto = ?');
        $stmt->execute([$idProducto]);

        throw $e;
    }

    auditar('alta_producto', 'producto', $idProducto, $nombre);

    responder_exito('Producto creado correctamente.', [
        'id_producto'      => $idProducto,
        'nombre'           => $nombre,
        'tipo_de_producto' => $tipo,
        'precio'           => $precio,
        'stock'            => $stock,
        'extension'        => $extension,
    ], 201);
}, 'img.php');
