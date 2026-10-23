<?php
/**
 * A.P.P.E. — Arranque común de todos los endpoints PHP.
 *
 * Uso obligatorio en cada endpoint:
 *
 *     <?php
 *     require_once __DIR__ . '/../../config/bootstrap.php';
 *
 *     try {
 *         // ... lógica del endpoint ...
 *         responder_exito('Listo.', ['items' => $filas]);
 *     } catch (ValidacionException $e) {
 *         responder_error($e->getMessage(), 422, 'validacion');
 *     } catch (AccesoDenegadoException $e) {
 *         responder_error($e->getMessage(), 403, 'acceso');
 *     } catch (NoEncontradoException $e) {
 *         responder_error($e->getMessage(), 404, 'no_encontrado');
 *     } catch (ConflictoException $e) {
 *         responder_error($e->getMessage(), 409, 'conflicto');
 *     } catch (Throwable $e) {
 *         responder_error('Ocurrió un error inesperado. Referencia: ' . manejar_error($e, 'endpoint'), 500, 'error_interno');
 *     }
 *
 * IMPORTANTE: este archivo debe ser la PRIMERA sentencia del endpoint. El
 * hallazgo 27 documenta que `mirutina.php` emitía una línea en blanco antes de
 * `<?php`, lo que contaminaba el JSON con la advertencia de "cabeceras ya
 * enviadas".
 */

declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

/* --------------------------------------------------------------------------
 * 1. Configuración general
 * -------------------------------------------------------------------------- */

$aplicacion = appe_config()['aplicacion'];

date_default_timezone_set((string) $aplicacion['zona_horaria']);
mb_internal_encoding('UTF-8');

/* --------------------------------------------------------------------------
 * 2. Errores: nunca se muestran al cliente (hallazgo 17)
 * -------------------------------------------------------------------------- */
$depurar = (bool) $aplicacion['depurar'];

ini_set('display_errors', $depurar ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/**
 * Convierte los avisos y errores de PHP en registros internos, sin imprimir
 * nada en la respuesta. Evita que un `Undefined array key` termine dentro del
 * JSON que el front-end intenta parsear.
 */
set_error_handler(static function (int $severidad, string $mensaje, string $archivo, int $linea): bool {
    if ((error_reporting() & $severidad) === 0) {
        return false;
    }

    error_log(sprintf('[APPE] %s en %s:%d', $mensaje, $archivo, $linea));

    return true;
});

/* --------------------------------------------------------------------------
 * 2.b. Ruta de publicación y de la cookie de sesión
 *
 * El proyecto puede instalarse en la raíz del dominio, en un subdirectorio o
 * en una carpeta cuyo nombre lleva espacios. Para que la cookie de sesión
 * llegue siempre al sitio, su ruta se deduce de la raíz de publicación en vez
 * de fijarla a mano.
 * -------------------------------------------------------------------------- */

if (!function_exists('appe_ruta_publicacion')) {
    /**
     * Devuelve la ruta URL donde está publicado el proyecto, con barra final.
     *
     * La ruta se deduce de la URL de la petición, no de la carpeta física.
     * Esto importa porque las cookies comparan su ruta distinguiendo
     * mayúsculas: si el proyecto está en `C:\...\APPE` pero se abre con
     * `http://localhost/appe/`, la cookie creada con la ruta del disco
     * (`/APPE/`) no vuelve a enviarse y la sesión se pierde en cada
     * petición (el login fallaría con «La sesión de seguridad expiró»).
     */
    function appe_ruta_publicacion(): string
    {
        static $ruta = null;

        if ($ruta !== null) {
            return $ruta;
        }

        $ruta = '/';

        // 1. Desde la URL: se quitan los niveles que separan el endpoint de
        //    la raíz del proyecto.
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $carpetaUrl = rtrim(dirname($script), '/');

        $raizApp = str_replace('\\', '/', dirname(__DIR__));
        $dirScript = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')));

        $relativo = trim(str_replace($raizApp, '', $dirScript), '/');
        $niveles = $relativo === '' ? 0 : count(explode('/', $relativo));

        $partes = explode('/', trim($carpetaUrl, '/'));
        $raizUrl = implode('/', array_slice($partes, 0, max(0, count($partes) - $niveles)));

        if ($raizUrl !== '') {
            $ruta = '/' . $raizUrl . '/';
        }

        return $ruta;
    }
}

if (!function_exists('appe_ruta_cookie')) {
    /**
     * Devuelve la ruta que se asigna a la cookie de sesión.
     *
     * Si `aplicacion.ruta_cookie` está vacío, se usa la ruta detectada
     * automáticamente.
     *
     * @param array<string, mixed> $aplicacion
     */
    function appe_ruta_cookie(array $aplicacion): string
    {
        $configurada = (string) ($aplicacion['ruta_cookie'] ?? '');

        return $configurada !== '' ? $configurada : appe_ruta_publicacion();
    }
}

/* --------------------------------------------------------------------------
 * 3. Sesión única y endurecida (hallazgos 10, 11 y 12)
 *
 * Antes existían dos espacios de sesión independientes (APPE_CLIENTE y
 * APPE_EMPRESA) y ambos escribían `$_SESSION['correo']`, de modo que una
 * sesión de cliente satisfacía el chequeo de acceso empresarial. Ahora hay una
 * sola sesión con el rol explícito.
 *
 * La ruta de la cookie se detecta sola a partir de la raíz de publicación, de
 * modo que el proyecto funciona igual si se instala en la raíz del dominio,
 * en un subdirectorio o en una carpeta cuyo nombre lleva espacios.
 * -------------------------------------------------------------------------- */

if (session_status() !== PHP_SESSION_ACTIVE) {
    $usarHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    session_name('APPE_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => appe_ruta_cookie($aplicacion),
        'domain'   => '',
        'secure'   => $usarHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/* --------------------------------------------------------------------------
 * 4. Cabeceras de seguridad de la aplicación
 * -------------------------------------------------------------------------- */

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; "
        . "style-src 'self' 'unsafe-inline'; "
        . "script-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'"
    );
}

/* --------------------------------------------------------------------------
 * 5. Cuerpo JSON -> $_POST
 *
 * `assets/js/api.js` envía las peticiones POST en formato JSON. En ese caso
 * PHP no rellena `$_POST`, de modo que el cuerpo se traduce a `$_POST` para
 * que todos los endpoints lean los datos de la misma manera, sin importar
 * cómo los envíe el front-end.
 * -------------------------------------------------------------------------- */

if (
    strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false
) {
    $cuerpoJson = file_get_contents('php://input');

    if (is_string($cuerpoJson) && $cuerpoJson !== '') {
        $datosJson = json_decode($cuerpoJson, true);

        if (is_array($datosJson)) {
            foreach ($datosJson as $clave => $valor) {
                if (!array_key_exists($clave, $_POST)) {
                    $_POST[$clave] = $valor;
                }
            }
        }
    }
}

/* --------------------------------------------------------------------------
 * 6. Manejador global de excepciones no capturadas
 * -------------------------------------------------------------------------- */

set_exception_handler(static function (Throwable $e): void {
    $referencia = manejar_error($e, 'excepción no controlada');

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'exito'     => false,
        'mensaje'   => 'Ocurrió un error inesperado. Referencia: ' . $referencia,
        'codigo'    => 'error_interno',
        'referencia' => $referencia,
    ], JSON_UNESCAPED_UNICODE);
});

/* --------------------------------------------------------------------------
 * 7. Utilidades de paginación y filtrado (RF-12)
 * -------------------------------------------------------------------------- */

/**
 * Normaliza los parámetros de paginación.
 *
 * @return array{limite: int, desplazamiento: int}
 */
function parametros_paginacion(int $limiteMaximo = 200): array
{
    $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
    $desplazamiento = isset($_GET['desplazamiento']) ? (int) $_GET['desplazamiento'] : 0;

    if ($limite < 1) {
        $limite = 1;
    }
    if ($limite > $limiteMaximo) {
        $limite = $limiteMaximo;
    }
    if ($desplazamiento < 0) {
        $desplazamiento = 0;
    }

    return ['limite' => $limite, 'desplazamiento' => $desplazamiento];
}
