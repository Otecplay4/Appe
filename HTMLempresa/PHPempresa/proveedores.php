<?php
/**
 * A.P.P.E. — Gestión de proveedores.
 *
 * Requisito funcional RF-09 «gestionar los datos de los proveedores del
 * gimnasio» · Caso de uso CU-05 «Administrar Productos y Proveedores».
 * Todas las operaciones exigen sesión de entrenador y token CSRF.
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
            listar_proveedores();
            break;
        case 'crear':
            crear_proveedor();
            break;
        case 'actualizar':
            actualizar_proveedor();
            break;
        case 'eliminar':
            eliminar_proveedor();
            break;
        case 'productos':
            productos_del_proveedor();
            break;
        default:
            throw new ValidacionException('La operación solicitada no es válida.');
    }
}, 'proveedores.php');

/**
 * Lista los proveedores con los productos que abastecen.
 */
function listar_proveedores(): void
{
    exigir_metodo('GET');
    requiere_entrenador();

    $stmt = appe_conexion()->prepare(
        'SELECT pr.id_proveedor, pr.nombre, pr.departamento, pr.calle, pr.manzana, pr.solar,
                pr.correo, pr.telefono, pr.activo,
                (SELECT COUNT(*) FROM provee pv WHERE pv.id_proveedor = pr.id_proveedor) AS productos
           FROM proveedor pr
          ORDER BY pr.activo DESC, pr.nombre ASC'
    );
    $stmt->execute();
    $filas = $stmt->fetchAll();

    $proveedores = array_map(static function (array $fila): array {
        return [
            'id_proveedor' => (int) $fila['id_proveedor'],
            'nombre'       => (string) $fila['nombre'],
            'direccion'    => trim(implode(' ', array_filter([
                $fila['departamento'], $fila['calle'], $fila['manzana'], $fila['solar'],
            ]))),
            'departamento' => $fila['departamento'],
            'calle'        => $fila['calle'],
            'manzana'      => $fila['manzana'],
            'solar'        => $fila['solar'],
            'correo'       => (string) $fila['correo'],
            'telefono'     => $fila['telefono'],
            'activo'       => (int) $fila['activo'] === 1,
            'productos'    => (int) $fila['productos'],
        ];
    }, $filas);

    responder_exito('Listado de proveedores.', [
        'proveedores' => $proveedores,
    ]);
}

/**
 * Alta de un proveedor.
 */
function crear_proveedor(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $nombre   = texto_requerido($_POST['nombre'] ?? '', 'Nombre', 100);
    $correo   = correo_requerido($_POST['correo'] ?? '');
    $telefono = texto_opcional($_POST['telefono'] ?? '', 'Teléfono', 20);
    $calle    = texto_opcional($_POST['calle'] ?? '', 'Calle', 50);
    $depto    = texto_opcional($_POST['departamento'] ?? '', 'Departamento', 50);
    $manzana  = texto_opcional($_POST['manzana'] ?? '', 'Manzana', 15);
    $solar    = texto_opcional($_POST['solar'] ?? '', 'Solar', 15);

    if ($telefono !== '' && !preg_match('/^[0-9+ ]{7,20}$/', $telefono)) {
        throw new ValidacionException('El teléfono no es válido.');
    }

    $stmt = appe_conexion()->prepare('SELECT id_proveedor FROM proveedor WHERE correo = ?');
    $stmt->execute([$correo]);

    if ($stmt->fetch() !== false) {
        throw new ConflictoException('Ya existe un proveedor con ese correo.');
    }

    $stmt = appe_conexion()->prepare(
        'INSERT INTO proveedor (nombre, departamento, calle, manzana, solar, correo, telefono, activo)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([
        $nombre,
        $depto === '' ? null : $depto,
        $calle === '' ? null : $calle,
        $manzana === '' ? null : $manzana,
        $solar === '' ? null : $solar,
        $correo,
        $telefono === '' ? null : $telefono,
    ]);

    $id = (int) appe_conexion()->lastInsertId();

    auditar('alta_proveedor', 'proveedor', $id, $nombre);

    responder_exito('Proveedor creado correctamente.', [
        'id_proveedor' => $id,
    ], 201);
}

/**
 * Modificación de un proveedor.
 */
function actualizar_proveedor(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $id = id_requerido($_POST['id'] ?? null, 'Proveedor');

    $stmt = appe_conexion()->prepare('SELECT id_proveedor FROM proveedor WHERE id_proveedor = ?');
    $stmt->execute([$id]);

    if ($stmt->fetch() === false) {
        throw new NoEncontradoException('El proveedor no existe.');
    }

    $campos = [];
    $parametros = [];

    if (isset($_POST['nombre'])) {
        $campos[] = 'nombre = ?';
        $parametros[] = texto_requerido($_POST['nombre'], 'Nombre', 100);
    }

    if (isset($_POST['correo'])) {
        $correo = correo_requerido($_POST['correo']);

        $stmt = appe_conexion()->prepare('SELECT id_proveedor FROM proveedor WHERE correo = ? AND id_proveedor <> ?');
        $stmt->execute([$correo, $id]);

        if ($stmt->fetch() !== false) {
            throw new ConflictoException('Ese correo ya pertenece a otro proveedor.');
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

    foreach (['calle' => 50, 'departamento' => 50, 'manzana' => 15, 'solar' => 15] as $campo => $maximo) {
        if (isset($_POST[$campo])) {
            $valor = texto_opcional($_POST[$campo], ucfirst($campo), $maximo);
            $campos[] = '`' . $campo . '` = ?';
            $parametros[] = $valor === '' ? null : $valor;
        }
    }

    if (isset($_POST['activo'])) {
        $campos[] = 'activo = ?';
        $parametros[] = (int) enum_requerido($_POST['activo'], ['0', '1'], 'Estado');
    }

    if ($campos === []) {
        throw new ValidacionException('No se envió ningún dato para modificar.');
    }

    $parametros[] = $id;

    $stmt = appe_conexion()->prepare('UPDATE proveedor SET ' . implode(', ', $campos) . ' WHERE id_proveedor = ?');
    $stmt->execute($parametros);

    auditar('modificacion_proveedor', 'proveedor', $id, implode(', ', $campos));

    responder_exito('Proveedor actualizado correctamente.', [
        'id_proveedor' => $id,
    ]);
}

/**
 * Baja de un proveedor. Los productos quedan sin proveedor asignado.
 */
function eliminar_proveedor(): void
{
    exigir_metodo('POST');
    exigir_csrf();
    requiere_entrenador();

    $id = id_requerido($_POST['id'] ?? null, 'Proveedor');

    $stmt = appe_conexion()->prepare('SELECT id_proveedor, nombre FROM proveedor WHERE id_proveedor = ?');
    $stmt->execute([$id]);
    $proveedor = $stmt->fetch();

    if ($proveedor === false) {
        throw new NoEncontradoException('El proveedor no existe.');
    }

    $stmt = appe_conexion()->prepare('DELETE FROM proveedor WHERE id_proveedor = ?');
    $stmt->execute([$id]);

    auditar('baja_proveedor', 'proveedor', $id, (string) $proveedor['nombre']);

    responder_exito('El proveedor fue eliminado correctamente.', [
        'id_proveedor' => $id,
    ]);
}

/**
 * Productos que abastece un proveedor.
 */
function productos_del_proveedor(): void
{
    exigir_metodo('GET');
    requiere_entrenador();

    $id = id_requerido($_GET['id'] ?? null, 'Proveedor');

    $stmt = appe_conexion()->prepare(
        'SELECT p.id_producto, p.nombre, p.tipo_de_producto, p.precio, p.stock, p.activo
           FROM provee pv
           JOIN producto p ON p.id_producto = pv.id_producto
          WHERE pv.id_proveedor = ?
          ORDER BY p.nombre ASC'
    );
    $stmt->execute([$id]);

    responder_exito('Productos del proveedor.', [
        'productos' => $stmt->fetchAll(),
    ]);
}
