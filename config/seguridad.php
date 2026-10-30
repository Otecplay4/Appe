<?php
/**
 * A.P.P.E. — Biblioteca central de seguridad, sesión, validación y respuestas.
 */

declare(strict_types=1);

if (!defined('APPE_INICIADO')) {
    define('APPE_INICIADO', true);
}

/* ==========================================================================
 * 1. Excepciones de dominio
 * ========================================================================== */

if (!class_exists('ValidacionException', false)) {
    /** Entrada inválida: se responde 422 y no se escribe nada en la base. */
    class ValidacionException extends RuntimeException {}
}

if (!class_exists('AccesoDenegadoException', false)) {
    /** El usuario está autenticado pero no tiene permiso para la operación. */
    class AccesoDenegadoException extends RuntimeException {}
}

if (!class_exists('NoAutenticadoException', false)) {
    /** No hay sesión válida: se responde 401. */
    class NoAutenticadoException extends RuntimeException {}
}

if (!class_exists('NoEncontradoException', false)) {
    /** El recurso solicitado no existe: se responde 404. */
    class NoEncontradoException extends RuntimeException {}
}

if (!class_exists('ConflictoException', false)) {
    /** El recurso ya existe o entra en conflicto con el estado actual. */
    class ConflictoException extends RuntimeException {}
}


/**
 * Envía una respuesta JSON y finaliza la ejecución.
 *
 * @param array<string, mixed> $cuerpo
 */
function responder_json(array $cuerpo, int $codigoHttp = 200): void
{
    if (!headers_sent()) {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
    }

    echo json_encode(
        $cuerpo,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

/**
 * Respuesta de éxito.
 *
 * @param array<string, mixed> $datos
 */
function responder_exito(string $mensaje = 'Operación realizada.', array $datos = [], int $codigoHttp = 200): void
{
    responder_json([
        'exito'   => true,
        'mensaje' => $mensaje,
        'datos'   => $datos,
    ], $codigoHttp);
}


function responder_error(
    string $mensaje,
    int $codigoHttp = 400,
    string $codigo = 'error',
    ?string $referencia = null
): void {
    $cuerpo = [
        'exito'   => false,
        'mensaje' => $mensaje,
        'codigo'  => $codigo,
    ];

    if ($referencia !== null) {
        $cuerpo['referencia'] = $referencia;
    }

    responder_json($cuerpo, $codigoHttp);
}


function nueva_referencia(): string
{
    return 'ERR-' . strtoupper(bin2hex(random_bytes(3)));
}


function manejar_error(Throwable $e, string $contexto): string
{
    $referencia = nueva_referencia();

    error_log(sprintf(
        '[APPE][%s] %s: %s en %s:%d',
        $referencia,
        $contexto,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    return $referencia;
}


function rol_actual(): ?string
{
    $usuario = $_SESSION['usuario'] ?? null;

    return is_array($usuario) && isset($usuario['rol']) ? (string) $usuario['rol'] : null;
}

/**
 * Datos del usuario autenticado, o null si la sesión es anónima.
 *
 * @return array<string, mixed>|null
 */
function usuario_actual(): ?array
{
    $usuario = $_SESSION['usuario'] ?? null;

    return is_array($usuario) ? $usuario : null;
}

/**
 * Identificador del cliente autenticado (solo si el rol es "cliente").
 */
function cliente_id_actual(): ?int
{
    $usuario = usuario_actual();

    if ($usuario === null || ($usuario['rol'] ?? '') !== 'cliente') {
        return null;
    }

    return (int) $usuario['id'];
}

/**
 * Identificador del entrenador autenticado (roles "entrenador" o "admin").
 */
function entrenador_id_actual(): ?int
{
    $usuario = usuario_actual();
    $rol = $usuario['rol'] ?? '';

    if ($rol !== 'entrenador' && $rol !== 'admin') {
        return null;
    }

    return (int) $usuario['id'];
}

/**
 * Indica si el usuario autenticado tiene alguno de los roles indicados.
 */
function es_rol(string ...$roles): bool
{
    $rol = rol_actual();

    return $rol !== null && in_array($rol, $roles, true);
}

/**
 * Exige que la sesión exista y tenga uno de los roles indicados.
 * Responde 401 si no hay sesión y 403 si el rol no alcanza (hallazgos 1, 11).
 *
 * @throws AccesoDenegadoException
 */
function requiere_rol(string ...$roles): void
{
    if (usuario_actual() === null) {
        throw new NoAutenticadoException('Debe iniciar sesión para continuar.');
    }

    if (!es_rol(...$roles)) {
        throw new AccesoDenegadoException('No tiene permisos para realizar esta operación.');
    }
}

/**
 * Exige rol "cliente".
 *
 * @throws AccesoDenegadoException
 */
function requiere_cliente(): int
{
    requiere_rol('cliente');

    return (int) $_SESSION['usuario']['id'];
}

/**
 * Exige rol "entrenador" o "admin".
 *
 * @throws AccesoDenegadoException
 */
function requiere_entrenador(): int
{
    requiere_rol('entrenador', 'admin');

    return (int) $_SESSION['usuario']['id'];
}

/**
 * Exige rol "admin" (Administrador del sistema según el PDF).
 *
 * @throws AccesoDenegadoException
 */
function requiere_admin(): int
{
    requiere_rol('admin');

    return (int) $_SESSION['usuario']['id'];
}

/**
 * Establece la identidad del usuario en la sesión.
 * @param array<string, mixed> $usuario
 */
function iniciar_sesion_usuario(array $usuario): void
{
    session_regenerate_id(true);

    $_SESSION = [];
    $_SESSION['usuario'] = [
        'id'       => (int) $usuario['id'],
        'rol'      => (string) $usuario['rol'],
        'nombre'   => (string) ($usuario['nombre'] ?? ''),
        'apellido' => (string) ($usuario['apellido'] ?? ''),
        'correo'   => (string) ($usuario['correo'] ?? ''),
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['login_intentos'] = 0;
}

/**
 * Cierra la sesión por completo y expira la cookie.
 */
function cerrar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}


/**
 * Devuelve (y crea si hace falta) el token CSRF de la sesión.
 */
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Extrae el token CSRF de la petición (campo `csrf_token` o cabecera
 * `X-CSRF-Token`).
 */
function csrf_recibido(): ?string
{
    if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }

    $cabecera = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    return is_string($cabecera) ? $cabecera : null;
}

/**
 * Valida el token CSRF de la petición actual.
 */
function csrf_valido(): bool
{
    $recibido = csrf_recibido();

    return is_string($recibido)
        && $recibido !== ''
        && isset($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $recibido);
}

/**
 * Exige un token CSRF válido en cualquier operación que modifique el estado.
 *
 * @throws AccesoDenegadoException
 */
function exigir_csrf(): void
{
    if (!csrf_valido()) {
        throw new AccesoDenegadoException('La sesión de seguridad expiró. Recargue la página e intente nuevamente.');
    }
}



/**
 * Normaliza un texto recibido: recorta, elimina etiquetas y caracteres de
 * control, y colapsa espacios repetidos.
 */
function limpiar_texto(mixed $valor): string
{
    if (!is_scalar($valor)) {
        return '';
    }

    $texto = (string) $valor;
    $texto = strip_tags($texto);
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto) ?? '';
    $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

    return trim($texto);
}

/**
 * Texto obligatorio con control de longitud.
 *
 * @throws ValidacionException
 */
function texto_requerido(mixed $valor, string $campo, int $maximo = 255, int $minimo = 1): string
{
    $texto = limpiar_texto($valor);

    $longitud = mb_strlen($texto);

    if ($longitud < $minimo) {
        throw new ValidacionException(sprintf('El campo "%s" es obligatorio.', $campo));
    }

    if ($longitud > $maximo) {
        throw new ValidacionException(sprintf('El campo "%s" no puede superar los %d caracteres.', $campo, $maximo));
    }

    return $texto;
}

/**
 * Texto opcional. Devuelve "" si no se informó.
 *
 * @throws ValidacionException
 */
function texto_opcional(mixed $valor, string $campo, int $maximo = 255): string
{
    if ($valor === null) {
        return '';
    }

    $texto = limpiar_texto($valor);

    if (mb_strlen($texto) > $maximo) {
        throw new ValidacionException(sprintf('El campo "%s" no puede superar los %d caracteres.', $campo, $maximo));
    }

    return $texto;
}

/**
 * Correo electrónico válido y normalizado en minúsculas.
 *
 * @throws ValidacionException
 */
function correo_requerido(mixed $valor, string $campo = 'Correo electrónico'): string
{
    $correo = trim(is_scalar($valor) ? (string) $valor : '');

    if ($correo === '' || mb_strlen($correo) > 120) {
        throw new ValidacionException('El correo electrónico no es válido.');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new ValidacionException('El correo electrónico no tiene un formato válido.');
    }

    return mb_strtolower($correo);
}

/**
 * Entero dentro de un rango.
 *
 * @throws ValidacionException
 */
function entero_requerido(mixed $valor, string $campo, int $minimo = 0, int $maximo = PHP_INT_MAX): int
{
    if (is_int($valor)) {
        $entero = $valor;
    } elseif (is_string($valor) && preg_match('/^-?\d+$/', trim($valor)) === 1) {
        $entero = (int) trim($valor);
    } else {
        throw new ValidacionException(sprintf('El campo "%s" debe ser un número entero.', $campo));
    }

    if ($entero < $minimo || $entero > $maximo) {
        throw new ValidacionException(sprintf('El campo "%s" está fuera del rango permitido.', $campo));
    }

    return $entero;
}

/**
 * Identificador numérico positivo.
 *
 * @throws ValidacionException
 */
function id_requerido(mixed $valor, string $campo = 'Identificador'): int
{
    return entero_requerido($valor, $campo, 1);
}

/**
 * Número decimal dentro de un rango.
 *
 * @throws ValidacionException
 */
function decimal_requerido(mixed $valor, string $campo, float $minimo = 0.0, float $maximo = 99999999.99): float
{
    if (is_int($valor) || is_float($valor)) {
        $numero = (float) $valor;
    } elseif (is_string($valor) && preg_match('/^-?\d+([.,]\d+)?$/', trim($valor)) === 1) {
        $numero = (float) str_replace(',', '.', trim($valor));
    } else {
        throw new ValidacionException(sprintf('El campo "%s" debe ser un número.', $campo));
    }

    if ($numero < $minimo || $numero > $maximo) {
        throw new ValidacionException(sprintf('El campo "%s" está fuera del rango permitido.', $campo));
    }

    return round($numero, 2);
}

/**
 * Valor que debe pertenecer a una lista cerrada (evita inyección en ENUM y
 * de paso evita XSS en los listados).
 *
 * @param list<string> $opciones
 *
 * @throws ValidacionException
 */
function enum_requerido(mixed $valor, array $opciones, string $campo): string
{
    $texto = trim(is_scalar($valor) ? (string) $valor : '');

    if (!in_array($texto, $opciones, true)) {
        throw new ValidacionException(sprintf('El valor de "%s" no es válido.', $campo));
    }

    return $texto;
}

/**
 * Fecha en formato YYYY-MM-DD.
 *
 * @throws ValidacionException
 */
function fecha_requerida(mixed $valor, string $campo): string
{
    $texto = trim(is_scalar($valor) ? (string) $valor : '');

    $fecha = DateTime::createFromFormat('Y-m-d', $texto);

    if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
        throw new ValidacionException(sprintf('El campo "%s" no es una fecha válida.', $campo));
    }

    return $texto;
}

/**
 * Exige un método HTTP concreto (nada de modificar el estado con GET:
 * hallazgo 15).
 *
 * @throws AccesoDenegadoException
 */
function exigir_metodo(string $metodo): void
{
    $actual = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($actual !== strtoupper($metodo)) {
        header('Allow: ' . strtoupper($metodo));
        throw new AccesoDenegadoException('Método HTTP no permitido para esta operación.');
    }
}

/**
 * Escapa un valor para imprimirlo en HTML (método de seguridad mencionado en el
 * PDF: htmlspecialchars).
 */
function escapar(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ==========================================================================
 * 7. Limitación de intentos de autenticación (hallazgo 14)
 * ========================================================================== */

/**
 * Dirección IP del cliente (teniendo en cuenta el proxy inverso habitual).
 */
function ip_cliente(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    return substr((string) $ip, 0, 45);
}

/**
 * Comprueba si se puede intentar autenticar.
 *
 * Devuelve los segundos restantes de bloqueo o null si el intento está
 * permitido. Se aplican dos umbrales distintos:
 *
 *   - Por cuenta (correo): `max_intentos` fallos. Es el límite que evita la
 *     adivinación de credenciales.
 *   - Por origen (IP): `max_intentos_ip` fallos. El umbral es
 *     deliberadamente más alto, porque en un gimnasio varias personas
 *     comparten la misma conexión: un umbral bajo permitiría que un atacante
 *     bloquee a todos los usuarios legítimos con solo unas pocas
 *     intentionally fallidas (denegación de servicio).
 *
 * Un inicio de sesión correcto borra los fallos acumulados de esa cuenta, de
 * modo que un usuario legítimo que se equivoca un par de veces no queda al
 * borde del bloqueo.
 */
function segundos_de_bloqueo(string $correo, string $ip): ?int
{
    $seguridad = appe_config()['seguridad'];
    $maxIntentos = (int) $seguridad['max_intentos'];
    $maxIntentosIp = (int) $seguridad['max_intentos_ip'];
    $ventanaMin = (int) $seguridad['ventana_bloqueo_min'];
    $pdo = appe_conexion();

    $desde = date('Y-m-d H:i:s', time() - ($ventanaMin * 60));
    $correo = mb_strtolower($correo);

    // --- Límite por cuenta ---------------------------------------------------
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS fallos, UNIX_TIMESTAMP(MIN(fecha)) AS mas_antiguo
           FROM intentologin
          WHERE exito = 0 AND fecha >= ? AND correo = ?'
    );
    $stmt->execute([$desde, $correo]);
    $fila = $stmt->fetch() ?: [];

    if ((int) ($fila['fallos'] ?? 0) >= $maxIntentos) {
        $liberaEn = (int) ($fila['mas_antiguo'] ?? 0) + ($ventanaMin * 60);
        if ($liberaEn > time()) {
            return $liberaEn - time();
        }
    }

    // --- Límite por origen ---------------------------------------------------
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS fallos, UNIX_TIMESTAMP(MIN(fecha)) AS mas_antiguo
           FROM intentologin
          WHERE exito = 0 AND fecha >= ? AND ip = ?'
    );
    $stmt->execute([$desde, $ip]);
    $fila = $stmt->fetch() ?: [];

    if ((int) ($fila['fallos'] ?? 0) >= $maxIntentosIp) {
        $liberaEn = (int) ($fila['mas_antiguo'] ?? 0) + ($ventanaMin * 60);
        if ($liberaEn > time()) {
            return $liberaEn - time();
        }
    }

    return null;
}

/**
 * Registra un intento de autenticación (exitoso o no) para la bitácora y el
 * control de intentos.
 */
function registrar_intento(string $correo, string $ip, bool $exito): void
{
    try {
        $stmt = appe_conexion()->prepare(
            'INSERT INTO intentologin (correo, ip, exito, fecha) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([mb_strtolower($correo), $ip, $exito ? 1 : 0]);
    } catch (Throwable $e) {
        // Un fallo al registrar el intento nunca debe impedir el login.
        error_log('[APPE] No se pudo registrar el intento de login: ' . $e->getMessage());
    }
}

/**
 * Limpia los fallos acumulados de una cuenta tras un inicio de sesión
 * correcto: el usuario legítimo que se equivocó un par de veces no debe
 * quedar al borde del bloqueo.
 */
function limpiar_intentos_cuenta(string $correo): void
{
    try {
        $stmt = appe_conexion()->prepare('DELETE FROM intentologin WHERE exito = 0 AND correo = ?');
        $stmt->execute([mb_strtolower($correo)]);
    } catch (Throwable $e) {
        error_log('[APPE] No se pudieron limpiar los intentos de la cuenta: ' . $e->getMessage());
    }
}

/**
 * Limpia los intentos antiguos (mantenimiento).
 */
function limpiar_intentos(): void
{
    try {
        $dias = (int) appe_config()['seguridad']['retencion_auditoria_dias'];
        $stmt = appe_conexion()->prepare('DELETE FROM intentologin WHERE fecha < (NOW() - INTERVAL ? DAY)');
        $stmt->bindValue(1, $dias, PDO::PARAM_INT);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('[APPE] No se pudieron limpiar los intentos: ' . $e->getMessage());
    }
}



/**
 * Registra una operación sensible en la bitácora.
 */
function auditar(string $accion, string $entidad, ?int $idEntidad = null, string $detalle = ''): void
{
    try {
        $usuario = usuario_actual();
        $stmt = appe_conexion()->prepare(
            'INSERT INTO auditoria (id_actor, rol, accion, entidad, id_entidad, detalle, ip, fecha)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $usuario !== null ? (int) $usuario['id'] : null,
            $usuario !== null ? (string) $usuario['rol'] : 'anonimo',
            mb_substr($accion, 0, 60),
            mb_substr($entidad, 0, 40),
            $idEntidad,
            mb_substr($detalle, 0, 255),
            ip_cliente(),
        ]);
    } catch (Throwable $e) {
        error_log('[APPE] No se pudo auditar la operación: ' . $e->getMessage());
    }
}



/**
 * Categorías que usa la interfaz del cliente y su correspondencia con el
 * enumerado `tipo_de_producto` del MER (consumible | accesorio | atuendo).
 *
 * @return array<string, string>
 */
function categorias_producto(): array
{
    return [
        'ropa'        => 'atuendo',
        'suplementos' => 'consumible',
        'accesorios'  => 'accesorio',
    ];
}

/**
 * Traduce una categoría de la interfaz al tipo del enumerado del MER.
 * Devuelve null si la categoría no es válida (lista cerrada).
 */
function tipo_producto_desde_categoria(string $categoria): ?string
{
    $mapa = categorias_producto();
    $clave = mb_strtolower(trim($categoria));

    return $mapa[$clave] ?? null;
}

/**
 * Devuelve una fila o null.
 *
 * @param array<string, mixed>|null $fila
 */
function primera_fila(?array $fila): ?array
{
    return $fila === null ? null : $fila;
}

/**
 * Escapa los comodines de LIKE para que el usuario no pueda forzar el
 * comportamiento de la búsqueda (hallazgo 12 del análisis de código).
 */
function escapar_like(string $texto): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
}

/**
 * Sirve una imagen guardada en disco de forma segura.
 *
 * - La extensión nunca proviene de la petición: la lee el endpoint desde la
 *   base de datos, dentro de una lista cerrada.
 * - El identificador es un entero validado.
 * - Se comprueba con `realpath()` que la ruta final siga dentro del
 *   directorio permitido (hallazgo 7: path traversal).
 * - Si el archivo no existe, se sirve la imagen por defecto indicada (si la
 *   hay) en lugar de un 404, para que la interfaz no muestre imágenes
 *   rotas.
 *
 * @param string|null $imagenPorDefecto  Ruta alternativa a servir.
 *
 * @throws NoEncontradoException
 */
function servir_imagen(string $directorioBase, string $extension, int $id, ?string $imagenPorDefecto = null): void
{
    $tiposPermitidos = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];

    $extension = strtolower(trim($extension));

    // Una extensión vacía significa que el registro no tiene imagen: se sirve
    // directamente la imagen por defecto.
    if ($extension === '') {
        $rutaReal = $imagenPorDefecto !== null ? realpath($imagenPorDefecto) : false;

        if ($rutaReal === false) {
            throw new NoEncontradoException('La imagen no existe.');
        }

        $contenido = @file_get_contents($rutaReal);

        if ($contenido === false) {
            throw new NoEncontradoException('La imagen no existe.');
        }

        if (!headers_sent()) {
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . strlen($contenido));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: public, max-age=3600');
        }

        echo $contenido;
        exit;
    }

    if (!isset($tiposPermitidos[$extension])) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    $base = realpath($directorioBase);

    if ($base === false) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    $rutaFinal = $base . DIRECTORY_SEPARATOR . $id . '.' . $extension;
    $rutaReal  = realpath($rutaFinal);
    $tipo = $tiposPermitidos[$extension];

    // La comprobación de contención es la barrera real contra el path
    // traversal: aunque el identificador se escapara, la ruta quedaría fuera
    // del directorio permitido.
    if ($rutaReal === false || strpos($rutaReal, $base . DIRECTORY_SEPARATOR) !== 0) {
        $rutaReal = $imagenPorDefecto !== null ? realpath($imagenPorDefecto) : false;
        $tipo = 'image/jpeg';
    }

    if ($rutaReal === false) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    $contenido = @file_get_contents($rutaReal);

    if ($contenido === false) {
        throw new NoEncontradoException('La imagen no existe.');
    }

    if (!headers_sent()) {
        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . strlen($contenido));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=3600');
    }

    echo $contenido;
    exit;
}

/**
 * Ejecuta la operación de un endpoint y traduce cualquier excepción a la
 * respuesta HTTP correspondiente.
 */
function ejecutar_endpoint(callable $operacion, string $contexto = 'endpoint'): void
{
    try {
        $operacion();
    } catch (ValidacionException $e) {
        responder_error($e->getMessage(), 422, 'validacion');
    } catch (NoAutenticadoException $e) {
        responder_error($e->getMessage(), 401, 'no_autenticado');
    } catch (AccesoDenegadoException $e) {
        responder_error($e->getMessage(), 403, 'acceso');
    } catch (NoEncontradoException $e) {
        responder_error($e->getMessage(), 404, 'no_encontrado');
    } catch (ConflictoException $e) {
        responder_error($e->getMessage(), 409, 'conflicto');
    } catch (Throwable $e) {
        responder_error(
            'Ocurrió un error inesperado. Referencia: ' . manejar_error($e, $contexto),
            500,
            'error_interno'
        );
    }
}
