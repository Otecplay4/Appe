<?php
/**
 * A.P.P.E. — Compatibilidad: conexión a la base de datos.
 *
 * La conexión real vive en `config/conexion.php` (una sola para todo el
 * proyecto,). Este archivo se conserva porque el código anterior
 * lo incluía y porque las instalaciones antiguas pueden seguir llamándolo.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/conexion.php';

/**
 * @deprecated Use appe_conexion() en su lugar.
 */
$conexion = appe_conexion();
