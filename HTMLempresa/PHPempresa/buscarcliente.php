<?php
/**
 * A.P.P.E. — Búsqueda de clientes.
 *
 * Requisito funcional RF-12. Se mantiene como endpoint independiente porque
 * el constructor de rutinas lo usa para poblar el selector de clientes.
 *
 * Correcciones:
 *   - Exige sesión de entrenador (hallazgo 3).
 *   - Escapa los comodines `%` y `_` de la búsqueda (antes el usuario podía
 *     forzar el comportamiento del LIKE).
 *   - Limita la cantidad de resultados.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('GET');
    requiere_entrenador();

    $texto = isset($_GET['q']) ? limpiar_texto($_GET['q']) : '';
    $limite = isset($_GET['limite']) ? entero_requerido($_GET['limite'], 'Límite', 1, 100) : 50;

    $sql = 'SELECT c.id AS id_cliente, c.nombre, c.apellido, c.correo, c.telefono, c.activo
              FROM cliente c
             WHERE c.activo = 1';
    $parametros = [];

    if ($texto !== '') {
        $like = '%' . escapar_like($texto) . '%';
        $sql .= ' AND (c.nombre LIKE ? OR c.apellido LIKE ? OR c.correo LIKE ?'
              . ' OR CONCAT(c.nombre, \' \', c.apellido) LIKE ?)';
        $parametros = array_merge($parametros, [$like, $like, $like, $like]);
    }

    $sql .= ' ORDER BY c.nombre ASC, c.apellido ASC LIMIT ' . $limite;

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute($parametros);
    $filas = $stmt->fetchAll();

    $clientes = array_map(static function (array $fila): array {
        return [
            'id_cliente' => (int) $fila['id_cliente'],
            'nombre'     => (string) $fila['nombre'],
            'apellido'   => (string) $fila['apellido'],
            'nombre_completo' => trim($fila['nombre'] . ' ' . $fila['apellido']),
            'correo'     => (string) $fila['correo'],
            'telefono'   => $fila['telefono'],
        ];
    }, $filas);

    responder_exito('Resultado de la búsqueda.', [
        'clientes' => $clientes,
    ]);
}, 'buscarcliente.php');
