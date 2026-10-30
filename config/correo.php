<?php
/**
 * A.P.P.E. — Envío de correo electrónico.
 *
 * El proyecto no puede depender de un servidor SMTP en el entorno de
 * desarrollo. Este envoltorio usa la función "mail()" de PHP cuando el
 * servidor de correo está configurado y, en caso contrario, registra el
 * contenido en el log del servidor para que el mensaje no se pierda.
 *
 * Configuración en "config/config.php":
 *
 *     "correo" => [
 *         "remitente" => "no-reply@olympia.local",
 *         "nombre"    => "A.P.P.E.",
 *     ],
 */

declare(strict_types=1);

if (!function_exists('enviar_correo')) {
    /**
     * Envía un correo de texto plano.
     *
     * @return bool true si el mensaje salió (o quedó registrado en el log).
     */
    function enviar_correo(string $destinatario, string $asunto, string $cuerpo): bool
    {
        $remitente = (string) (appe_config()['correo']['remitente'] ?? 'no-reply@olympia.local');
        $nombre    = (string) (appe_config()['correo']['nombre'] ?? 'A.P.P.E.');

        $cabeceras = [
            'From: ' . $nombre . ' <' . $remitente . '>',
            'Reply-To: ' . $remitente,
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ];

        if (function_exists('mail') && @mail($destinatario, $asunto, $cuerpo, implode("\r\n", $cabeceras))) {
            return true;
        }

        // Sin servidor de correo: se deja constancia en el log (nunca se
        // imprime la contraseña ni el token en la respuesta HTTP).
        error_log(sprintf(
            "[APPE][correo] Destinatario: %s | Asunto: %s\n%s",
            $destinatario,
            $asunto,
            $cuerpo
        ));

        return false;
    }
}

if (!function_exists('correo_disponible')) {
    /**
     * Indica si el servidor puede enviar correo realmente.
     */
    function correo_disponible(): bool
    {
        return function_exists('mail') && (bool) ini_get('sendmail_path');
    }
}
