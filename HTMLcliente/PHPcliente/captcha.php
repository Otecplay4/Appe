<?php
/**
 * A.P.P.E. — Desafío CAPTCHA para el registro.
 *
 * El desafío es aritmético y la respuesta correcta nunca
 * sale del servidor. Se guarda únicamente en la sesión y se consume en un solo
 * intento.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    $a = random_int(1, 9);
    $b = random_int(1, 9);

    $_SESSION['captcha_registro'] = [
        'respuesta' => (string) ($a + $b),
        'creado'    => time(),
    ];

    responder_exito('Desafío generado.', [
        'pregunta' => '¿Cuánto es ' . $a . ' + ' . $b . '?',
    ]);
}, 'captcha.php');
