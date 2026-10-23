<?php
/**
 * A.P.P.E. — Utilidades compartidas por la gestión de planes.
 *
 * No es un endpoint: solo define funciones.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/imagenes.php';

if (!defined('PERIODOS_PLAN')) {
    /** Periodos de precio que admite un plan. */
    define('PERIODOS_PLAN', ['semanal', 'mensual', 'trimestral', 'anual']);
}

if (!function_exists('directorio_imagenes_plan')) {
    /**
     * Directorio donde se guardan las imágenes de los planes.
     */
    function directorio_imagenes_plan(): string
    {
        return __DIR__ . '/img_planes';
    }
}

if (!function_exists('leer_precios_plan')) {
    /**
     * Lee y valida los precios por periodo del formulario.
     *
     * @param array<string, mixed> $post
     * @param string[]             $error
     *
     * @return array<string, float> Periodo => precio (solo los informados).
     */
    function leer_precios_plan(array $post, array &$error): array
    {
        $precios = [];

        foreach (PERIODOS_PLAN as $periodo) {
            $clave = 'precio_' . $periodo;

            if (!isset($post[$clave]) || $post[$clave] === '') {
                continue;
            }

            try {
                $valor = decimal_requerido($post[$clave], 'Precio ' . $periodo, 0, 99999999.99);
            } catch (ValidacionException $e) {
                $error[] = $e->getMessage();
                continue;
            }

            if ($valor <= 0) {
                $error[] = 'El precio ' . $periodo . ' debe ser mayor que cero.';
                continue;
            }

            $precios[$periodo] = $valor;
        }

        if ($precios === []) {
            $error[] = 'Debe informar al menos un precio (semanal, mensual, trimestral o anual).';
        }

        return $precios;
    }
}

if (!function_exists('borrar_imagen_plan')) {
    /**
     * Borra la imagen de un plan (con comprobación de contención de la ruta).
     */
    function borrar_imagen_plan(int $idPlan): bool
    {
        $directorio = directorio_imagenes_plan();

        foreach (['jpg', 'jpeg', 'png', 'gif'] as $extension) {
            if (eliminar_imagen($directorio, $idPlan, $extension)) {
                return true;
            }
        }

        return false;
    }
}
