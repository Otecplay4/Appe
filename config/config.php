<?php
/**
 * A.P.P.E. — Configuración local de la instalación.
 */

declare(strict_types=1);

return [
    'base_datos' => [
        'host'    => '127.0.0.1',
        'puerto'  => 3306,
        'nombre'  => 'appe',
        'usuario' => 'app_appe',
        'clave'   => '',
        'charset' => 'utf8mb4',
    ],

    'aplicacion' => [
        'nombre'       => 'A.P.P.E.',
        // Ruta de la cookie de sesión. Si se deja vacío, se detecta sola a
        // partir de la raíz de publicación del servidor, de modo que el
        // proyecto funciona en la raíz del dominio o en cualquier subcarpeta.
        'ruta_cookie'  => '',
        'depurar'      => false,
        'zona_horaria' => 'America/Montevideo',
    ],

    'seguridad' => [
        'max_intentos'            => 5,
        'max_intentos_ip'         => 30,
        'ventana_bloqueo_min'     => 15,
        'vigencia_token_min'     => 30,
        'max_imagen_mb'          => 5,
        'retencion_auditoria_dias' => 180,
    ],
];
