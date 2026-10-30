<?php
/**
 * A.P.P.E. — Cierre de sesión del entrenador / administrador.
 *
 * Caso de uso CU-08 «Cerrar Sesión».
 *
 * Destruye el estado de sesión y expira la cookie.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    auditar('cierre_sesion', 'entrenador', entrenador_id_actual(), 'Cierre de sesión');

    cerrar_sesion();

    // Si el navegador navega directamente a este archivo, se redirige al
    // login. Si la llamada viene del front-end (fetch), se responde JSON.
    $acepta = $_SERVER['HTTP_ACCEPT'] ?? '';
    $esNavegacion = strpos((string) $acepta, 'text/html') !== false
        && !isset($_SERVER['HTTP_X_REQUESTED_WITH']);

    if ($esNavegacion) {
        header('Location: ../interfaz2.html', true, 302);
        exit;
    }

    responder_exito('Sesión cerrada correctamente.', [
        'csrf' => csrf_token(),
    ]);
}, 'logout.php');
