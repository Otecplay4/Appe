<?php
/**
 * A.P.P.E. — Conexión única a la base de datos (PDO).
 * Todas las consultas del proyecto usan sentencias preparadas con
 * `ATTR_EMULATE_PREPARES = false` (PDOStatement::execute con parámetros reales).
 */

declare(strict_types=1);

if (!function_exists('appe_config')) {
    /**
     * Devuelve la configuración completa de la aplicación, con los valores por
     * defecto aplicados y las variables de entorno como respaldo.
     *
     * @return array<string, mixed>
     */
    function appe_config(): array
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $porDefecto = [
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
                'ruta_cookie'  => '',
                'depurar'      => false,
                'zona_horaria' => 'America/Montevideo',
            ],
            'seguridad' => [
                'max_intentos'            => 5,
                'max_intentos_ip'         => 30,
                'ventana_bloqueo_min'     => 15,
                'vigencia_token_min'      => 30,
                'max_imagen_mb'           => 5,
                'retencion_auditoria_dias' => 180,
            ],
        ];

        $archivo = __DIR__ . '/config.php';
        $propia = is_readable($archivo) ? require $archivo : [];

        if (!is_array($propia)) {
            $propia = [];
        }

        $config = array_replace_recursive($porDefecto, $propia);

        // Las variables de entorno tienen prioridad sobre el archivo de
        // configuración: permiten desplegar sin editar código.
        $entorno = [
            'host'    => 'DB_HOST',
            'puerto'  => 'DB_PORT',
            'nombre'  => 'DB_NAME',
            'usuario' => 'DB_USER',
            'clave'   => 'DB_PASS',
        ];
        foreach ($entorno as $clave => $variable) {
            $valor = getenv($variable);
            if ($valor !== false && $valor !== '') {
                $config['base_datos'][$clave] = $clave === 'puerto' ? (int) $valor : $valor;
            }
        }

        return $config;
    }
}

if (!function_exists('appe_conexion')) {
    /**
     * Devuelve la instancia PDO compartida. La crea en la primera llamada.
     */
    function appe_conexion(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $bd = appe_config()['base_datos'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $bd['host'],
            (int) $bd['puerto'],
            $bd['nombre'],
            $bd['charset']
        );

        try {
            $pdo = new PDO($dsn, (string) $bd['usuario'], (string) $bd['clave'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {

            error_log('[APPE] No se pudo conectar a la base de datos: ' . $e->getMessage());
            throw new RuntimeException('La base de datos no está disponible en este momento.', 0, $e);
        }

        return $pdo;
    }
}
