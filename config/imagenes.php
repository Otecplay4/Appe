<?php
/**
 * A.P.P.E. — Validación y almacenamiento de imágenes subidas.
 *
 * Hallazgo 8 (severidad alta): la versión anterior movía el archivo sin
 * comprobar el código de error de la subida, ni el tamaño, ni el contenido real.
 * Renombrar un archivo no transforma su contenido.
 *
 * Lo que hace esta función:
 *   1. Comprueba el código de error real de la subida (`$_FILES['x']['error']`).
 *   2. Comprueba el tamaño máximo configurado.
 *   3. Comprueba el TIPO REAL del archivo con `getimagesize()` y `finfo`, no la
 *      extensión ni el `Content-Type` que declara el navegador.
 *   4. Recodifica la imagen a JPEG cuando la extensión GD está disponible, lo
 *      que elimina cualquier carga incrustada.
 *   5. Guarda el archivo con un nombre derivado de un identificador numérico,
 *      nunca del nombre que eligió el usuario.
 */

declare(strict_types=1);

if (!function_exists('validar_subida_imagen')) {
    /**
     * Valida la subida de una imagen sin guardarla.
     *
     * @param array<string, mixed> $archivo  Entrada de `$_FILES`.
     *
     * @throws ValidacionException
     */
    function validar_subida_imagen(array $archivo): void
    {
        if (!isset($archivo['error']) || is_array($archivo['error'])) {
            throw new ValidacionException('No se recibió ningún archivo.');
        }

        switch ((int) $archivo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new ValidacionException('Debe seleccionar una imagen.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new ValidacionException('La imagen supera el tamaño máximo permitido.');
            case UPLOAD_ERR_PARTIAL:
                throw new ValidacionException('La subida del archivo se interrumpió. Intente nuevamente.');
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                throw new ValidacionException('El servidor no pudo guardar temporalmente el archivo.');
            default:
                throw new ValidacionException('No se pudo subir la imagen.');
        }

        $rutaTemporal = (string) ($archivo['tmp_name'] ?? '');

        if ($rutaTemporal === '' || !is_uploaded_file($rutaTemporal)) {
            throw new ValidacionException('El archivo recibido no es válido.');
        }

        $maximoBytes = (int) appe_config()['seguridad']['max_imagen_mb'] * 1024 * 1024;

        if (filesize($rutaTemporal) > $maximoBytes) {
            throw new ValidacionException('La imagen supera el tamaño máximo permitido.');
        }

        // Tipo real del archivo: getimagesize no se fía de la extensión.
        $informacion = @getimagesize($rutaTemporal);

        if ($informacion === false || !isset($informacion['mime'])) {
            throw new ValidacionException('El archivo no es una imagen válida.');
        }

        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array((string) $informacion['mime'], $tiposPermitidos, true)) {
            throw new ValidacionException('Solo se admiten imágenes JPG, PNG o GIF.');
        }

        // Segunda verificación con finfo, que lee los bytes y no la cabecera.
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo->file($rutaTemporal);

            if ($mimeReal !== false && !in_array($mimeReal, $tiposPermitidos, true)) {
                throw new ValidacionException('El contenido del archivo no corresponde a una imagen válida.');
            }
        }
    }
}

if (!function_exists('guardar_imagen')) {
    /**
     * Valida y guarda una imagen en el directorio indicado.
     *
     * @param array<string, mixed> $archivo    Entrada de `$_FILES`.
     * @param string               $directorio Directorio destino (debe existir).
     * @param string               $nombreBase Nombre base del archivo (sin extensión).
     *
     * @return string La extensión con la que se guardó ('jpg' o 'png').
     *
     * @throws ValidacionException
     */
    function guardar_imagen(array $archivo, string $directorio, string $nombreBase): string
    {
        validar_subida_imagen($archivo);

        if (!is_dir($directorio) || !is_writable($directorio)) {
            throw new ValidacionException('El directorio de imágenes no está disponible.');
        }

        $rutaTemporal = (string) $archivo['tmp_name'];
        $informacion = (array) getimagesize($rutaTemporal);
        $extension = 'jpg';

        // Con GD se recodifica a JPEG: se eliminan los metadatos y cualquier
        // contenido incrustado en el archivo original.
        if (function_exists('imagecreatetruecolor') && (int) $informacion[0] > 0 && (int) $informacion[1] > 0) {
            $origen = @imagecreatefromstring((string) file_get_contents($rutaTemporal));

            if ($origen !== false) {
                $imagen = imagecreatetruecolor((int) $informacion[0], (int) $informacion[1]);
                imagealphablending($imagen, false);
                imagesavealpha($imagen, true);
                $transparente = imagecolorallocatealpha($imagen, 0, 0, 0, 127);
                imagefilledrectangle($imagen, 0, 0, (int) $informacion[0], (int) $informacion[1], $transparente);
                imagecopyresampled($imagen, $origen, 0, 0, 0, 0, (int) $informacion[0], (int) $informacion[1]);
                imagejpeg($imagen, $directorio . DIRECTORY_SEPARATOR . $nombreBase . '.jpg', 85);
                imagedestroy($imagen);
                imagedestroy($origen);

                return 'jpg';
            }
        }

        // Sin GD: se conserva el formato original, que ya fue validado como
        // imagen. El directorio tiene un `.htaccess` que impide la ejecución
        // de código.
        $extension = strtolower((string) pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true) ? $extension : 'jpg';

        $destino = $directorio . DIRECTORY_SEPARATOR . $nombreBase . '.' . $extension;

        if (!@move_uploaded_file($rutaTemporal, $destino)) {
            throw new ValidacionException('No se pudo guardar la imagen en el servidor.');
        }

        @chmod($destino, 0644);

        return $extension;
    }
}

if (!function_exists('eliminar_imagen')) {
    /**
     * Elimina una imagen del directorio, comprobando que la ruta final siga
     * dentro del directorio permitido (hallazgo 7: path traversal).
     */
    function eliminar_imagen(string $directorio, int $id, string $extension): bool
    {
        $base = realpath($directorio);

        if ($base === false) {
            return false;
        }

        $extension = strtolower(trim($extension));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return false;
        }

        $ruta = $base . DIRECTORY_SEPARATOR . $id . '.' . $extension;
        $real = realpath($ruta);

        if ($real === false || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }

        return @unlink($real);
    }
}
