<?php
/**
 * A.P.P.E. — Plantilla de configuración.
 *
 * Copie este archivo como `config/config.php` y ajuste los valores.
 * `config/config.php` NO debe subirse al repositorio (contiene credenciales).
 *
 * Si no existe `config/config.php`, el sistema usa los valores por defecto
 * declarados en `config/seguridad.php` y las variables de entorno
 * DB_HOST / DB_NAME / DB_USER / DB_PASS.
 */

declare(strict_types=1);

return [
    'base_datos' => [
        // 'appe' es la base original del proyecto. Ver database/APPE_base_datos.sql
        'host'    => '127.0.0.1',
        'puerto'  => 3306,
        'nombre'  => 'appe',
        // Cuenta de aplicación con privilegios mínimos (hallazgo 9).
        // NUNCA usar 'root' ni una cuenta con contraseña vacía.
        'usuario' => 'app_appe',
        'clave'   => '',
        'charset' => 'utf8mb4',
    ],

    'aplicacion' => [
        'nombre' => 'A.P.P.E.',
        // Ruta de la cookie de sesión. Vacío = detección automática a partir
        // de la raíz de publicación (recomendado). Fijela solo si el
        // proyecto se publica en una URL distinta de la carpeta física.
        'ruta_cookie' => '',
        // Muestra detalles de los errores internos. SIEMPRE false en producción.
        'depurar' => false,
        'zona_horaria' => 'America/Montevideo',
    ],

    'seguridad' => [
        // Máximo de intentos fallidos de autenticación por cuenta.
        'max_intentos'            => 5,
        // Máximo de intentos fallidos por dirección IP. Deliberadamente más
        // alto que el límite por cuenta: en un gimnasio varias personas
        // comparten la misma conexión, y un umbral bajo permitiría bloquear
        // a todos los usuarios legítimos (denegación de servicio).
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
