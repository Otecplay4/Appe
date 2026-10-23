<?php
/**
 * A.P.P.E. — Imagen de un producto o de un plan (panel de administración).
 *
 * Los directorios `img/` e `img_planes/` están protegidos con un
 * `.htaccess` que impide el acceso directo y la ejecución de código. Por eso
 * el panel de administración no puede pedir los archivos por su ruta: los
 * pide a este endpoint, que valida la sesión y el rol antes de entregarlos.
 *
 * Uso:
 *   GET imagen.php?tipo=producto&id=3
 *   GET imagen.php?tipo=plan&id=1
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $tipo = enum_requerido($_GET['tipo'] ?? '', ['plan', 'producto'], 'Tipo de imagen');
    $id = id_requerido($_GET['id'] ?? null, 'Identificador');

    if ($tipo === 'plan') {
        $stmt = appe_conexion()->prepare('SELECT extension FROM plan WHERE id_plan = ?');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        if ($fila === false) {
            throw new NoEncontradoException('La imagen no existe.');
        }

        servir_imagen(__DIR__ . '/img_planes', (string) ($fila['extension'] ?? ''), $id);
    }

    $stmt = appe_conexion()->prepare('SELECT extension FROM producto WHERE id_producto = ?');
    $stmt->execute([$id]);
    $fila = $stmt->fetch();

    if ($fila === false) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    servir_imagen(__DIR__ . '/img', (string) ($fila['extension'] ?? ''), $id);
}, 'imagen.php');
