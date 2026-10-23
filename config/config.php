<?php
/**
 * A.P.P.E. — Configuración local de la instalación.
 *
 * Este archivo SÍ se incluye en el proyecto (a diferencia de config.example.php)
 * para que la aplicación funcione de inmediato en un entorno de desarrollo.
 *
 * ANTES DE PUBLICAR EN UN SERVIDOR REAL:
 *   1. Cambie la clave de 'base_datos' por una cadena larga y aleatoria.
 *   2. Verifique que la cuenta 'app_appe' tenga SOLO los privilegios
 *      SELECT, INSERT, UPDATE, DELETE sobre la base 'appe' (ver
 *      database/APPE_base_datos.sql, sección "Usuario de aplicación").
 *   3. Ajuste 'aplicacion.ruta_cookie' si la aplicación no está en la raíz.
 *   4. Mantenga 'aplicacion.depurar' en false.
 */

declare(strict_types=1);

return [
    'base_datos' => [
        'host'    => '127.0.0.1',
        'puerto'  => 3306,
        'nombre'  => 'appe',
        'usuario' => 'app_appe',
        // Debe coincidir con la clave que crea appe-db-completa.sql.
        'clave'   => '371365a1565409e5c0f7bd02d7007303',
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
