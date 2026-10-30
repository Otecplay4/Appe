<?php
/**
 * A.P.P.E. — Plantilla de configuración.
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
        'nombre' => 'A.P.P.E.',
        // Ruta de la cookie de sesión. Vacío = detección automática a partir
        // de la raíz de publicación. Fijela solo si el
        // proyecto se publica en una URL distinta de la carpeta física.
        'ruta_cookie' => '',
        // Muestra detalles de los errores internos. siempre false en producción.
        'depurar' => false,
        'zona_horaria' => 'America/Montevideo',
    ],

    'seguridad' => [
        // Máximo de intentos fallidos de autenticación por cuenta.
        'max_intentos'            => 5,
        // Máximo de intentos fallidos por dirección IP. Más
        // alto que el límite por cuenta ya que en un gimnasio varias personas
        // comparten la misma conexión, y un error permitiría bloquear
        // a todos los usuarios legítimos.
        'max_intentos_ip'         => 30,
        // Ventana de bloqueo (minutos) una vez superado el máximo.
        'ventana_bloqueo_min'     => 15,
        // Vigencia del token de recuperación de contraseña (minutos).
        'vigencia_token_min'  => 30,
        // Tamaño máximo admitido para las imágenes subidas (MB).
        'max_imagen_mb'       => 5,
        // Días de histórico que se conservan en la bitácora de auditoría.
        'retencion_auditoria_dias' => 180,
    ],
];
