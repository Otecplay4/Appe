<?php
/**
 * A.P.P.E. — Modificación de datos de un cliente.
 *
 * Requisito funcional RF-06 · Caso de uso CU-02 «Gestionar Usuarios».
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $idCliente = id_requerido($_POST['id'] ?? null, 'Cliente');

    // Existencia del cliente.
    $stmt = appe_conexion()->prepare('SELECT id FROM cliente WHERE id = ?');
    $stmt->execute([$idCliente]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('El cliente no existe.');
    }

    $campos = [];
    $parametros = [];

    if (isset($_POST['nombre'])) {
        $campos[] = 'nombre = ?';
        $parametros[] = texto_requerido($_POST['nombre'], 'Nombre', 70);
    }

    if (isset($_POST['apellido'])) {
        $campos[] = 'apellido = ?';
        $parametros[] = texto_requerido($_POST['apellido'], 'Apellido', 70);
    }

    if (isset($_POST['correo'])) {
        $correo = correo_requerido($_POST['correo']);

        $stmt = appe_conexion()->prepare('SELECT id FROM cliente WHERE correo = ? AND id <> ?');
        $stmt->execute([$correo, $idCliente]);

        if ($stmt->fetch() !== false) {
            throw new ConflictoException('Ese correo electrónico ya está registrado por otro cliente.');
        }

        $campos[] = 'correo = ?';
        $parametros[] = $correo;
    }

    if (isset($_POST['telefono'])) {
        $telefono = texto_opcional($_POST['telefono'], 'Teléfono', 20);

        if ($telefono !== '' && !preg_match('/^[0-9+ ]{7,20}$/', $telefono)) {
            throw new ValidacionException('El teléfono no es válido.');
        }

        $campos[] = 'telefono = ?';
        $parametros[] = $telefono === '' ? null : $telefono;
    }

    // Asignación de plan (CU-03 «Registrar Plan/Suscripción»).
    if (isset($_POST['id_plan'])) {
        $idPlan = $_POST['id_plan'] === '' || $_POST['id_plan'] === null
            ? null
            : entero_requerido($_POST['id_plan'], 'Plan', 1);

        if ($idPlan !== null) {
            $stmt = appe_conexion()->prepare('SELECT id_plan FROM plan WHERE id_plan = ? AND activo = 1');
            $stmt->execute([$idPlan]);

            if ($stmt->fetch() === false) {
                throw new ValidacionException('El plan seleccionado no existe o está inactivo.');
            }
        }

        $campos[] = 'id_plan = ?';
        $parametros[] = $idPlan;
    }

    // Asignación de entrenador responsable.
    if (isset($_POST['id_entrenador'])) {
        $idEntrenador = $_POST['id_entrenador'] === '' || $_POST['id_entrenador'] === null
            ? null
            : entero_requerido($_POST['id_entrenador'], 'Entrenador', 1);

        if ($idEntrenador !== null) {
            $stmt = appe_conexion()->prepare('SELECT id_entrenador FROM entrenador WHERE id_entrenador = ? AND activo = 1');
            $stmt->execute([$idEntrenador]);

            if ($stmt->fetch() === false) {
                throw new ValidacionException('El entrenador seleccionado no existe o está inactivo.');
            }
        }

        $campos[] = 'id_entrenador = ?';
        $parametros[] = $idEntrenador;
    }

    if (isset($_POST['activo'])) {
        $activo = enum_requerido($_POST['activo'], ['0', '1'], 'Estado');
        $campos[] = 'activo = ?';
        $parametros[] = (int) $activo;
    }

    if ($campos === []) {
        throw new ValidacionException('No se envió ningún dato para modificar.');
    }

    $parametros[] = $idCliente;

    $stmt = appe_conexion()->prepare('UPDATE cliente SET ' . implode(', ', $campos) . ' WHERE id = ?');
    $stmt->execute($parametros);

    auditar('modificacion_cliente', 'cliente', $idCliente, 'Campos: ' . implode(', ', $campos));

    // Se devuelve el registro ya actualizado para que el front-end no tenga que
    // adivinar el resultado (hallazgo 25).
    $stmt = appe_conexion()->prepare(
        'SELECT id AS id_cliente, nombre, apellido, correo, telefono, activo, id_plan, id_entrenador, id_rutina
           FROM cliente WHERE id = ?'
    );
    $stmt->execute([$idCliente]);
    $fila = $stmt->fetch();

    responder_exito('Cliente actualizado correctamente.', [
        'cliente' => $fila === false ? null : [
            'id_cliente'    => (int) $fila['id_cliente'],
            'nombre'        => (string) $fila['nombre'],
            'apellido'      => (string) $fila['apellido'],
            'correo'        => (string) $fila['correo'],
            'telefono'      => $fila['telefono'],
            'activo'        => (int) $fila['activo'] === 1,
            'id_plan'       => $fila['id_plan'] === null ? null : (int) $fila['id_plan'],
            'id_entrenador' => $fila['id_entrenador'] === null ? null : (int) $fila['id_entrenador'],
            'id_rutina'     => $fila['id_rutina'] === null ? null : (int) $fila['id_rutina'],
        ],
    ]);
}, 'actualizar.php');
