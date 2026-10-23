<?php
/**
 * A.P.P.E. — Bandeja de mensajes de contacto.
 *
 * Requisito funcional RF-15 · Caso de uso CU-07 (el gimnasio recibe y
 * responde las consultas).
 *
 * Operaciones:
 *   - listar  : mensajes recibidos, con filtro por leídos/no leídos.
 *   - marcar  : marca un mensaje como leído (o como no leído).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    // La comprobación de sesión y de rol se hace ANTES de resolver la
    // operación: de lo contrario un usuario anónimo recibiría un 422 en lugar
    // del 401 que corresponde (hallazgo 1).
    requiere_entrenador();

    $accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

    switch ($accion) {
        case 'listar':
            listar_mensajes();
            break;
        case 'marcar':
            marcar_mensaje();
            break;
        default:
            throw new ValidacionException('La operación solicitada no es válida.');
    }
}, 'mensajes.php');

/**
 * Listado de mensajes.
 */
function listar_mensajes(): void
{
    exigir_metodo('GET');
    requiere_entrenador();

    $filtro = isset($_GET['filtro']) && $_GET['filtro'] !== ''
        ? enum_requerido($_GET['filtro'], ['todos', 'no_leidos', 'leidos'], 'Filtro')
        : 'todos';

    $sql = 'SELECT id_mensaje, nombre, correo, telefono, mensaje, leido, fecha FROM mensajecontacto WHERE 1 = 1';

    if ($filtro === 'no_leidos') {
        $sql .= ' AND leido = 0';
    } elseif ($filtro === 'leidos') {
        $sql .= ' AND leido = 1';
    }

    $sql .= ' ORDER BY leido ASC, fecha DESC LIMIT 200';

    $stmt = appe_conexion()->prepare($sql);
    $stmt->execute();

    responder_exito('Mensajes recibidos.', [
        'mensajes' => $stmt->fetchAll(),
    ]);
}

/**
 * Marca un mensaje como leído o no leído.
 */
function marcar_mensaje(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $id = id_requerido($_POST['id'] ?? null, 'Mensaje');
    $leido = (int) enum_requerido($_POST['leido'] ?? '1', ['0', '1'], 'Estado');

    $stmt = appe_conexion()->prepare('SELECT id_mensaje FROM mensajecontacto WHERE id_mensaje = ?');
    $stmt->execute([$id]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('El mensaje no existe.');
    }

    $stmt = appe_conexion()->prepare('UPDATE mensajecontacto SET leido = ? WHERE id_mensaje = ?');
    $stmt->execute([$leido, $id]);

    auditar('marcar_mensaje', 'mensajecontacto', $id, $leido === 1 ? 'leído' : 'no leído');

    responder_exito('Mensaje actualizado.', [
        'id_mensaje' => $id,
        'leido'      => $leido === 1,
    ]);
}
