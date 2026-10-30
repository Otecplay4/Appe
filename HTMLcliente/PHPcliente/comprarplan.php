<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

ejecutar_endpoint(static function (): void {
    requiere_rol('cliente');
    exigir_metodo('POST');

    $idCliente = cliente_id_actual();
    $datos = json_decode(file_get_contents('php://input'), true);

    $idPlan = isset($datos['id_plan']) ? (int) $datos['id_plan'] : 0;
    $periodo = isset($datos['periodo']) ? trim($datos['periodo']) : 'mensual';

    // Obtener el plan y el precio según el periodo seleccionado
    $stmt = appe_conexion()->prepare(
        'SELECT p.nombre, pp.precio 
           FROM plan p 
           JOIN planprecio pp ON pp.id_plan = p.id_plan 
          WHERE p.id_plan = ? AND pp.periodo = ? AND p.activo = 1'
    );
    $stmt->execute([$idPlan, $periodo]);
    $planInfo = $stmt->fetch();

    if (!$planInfo) {
        throw new NoEncontradoException('El plan o el periodo seleccionado no existe.');
    }

    $precio = (float) $planInfo['precio'];
    $nombrePlan = $planInfo['nombre'] . ' (' . ucfirst($periodo) . ')';

    // Calcular fechas de vigencia según el periodo
    $fechaInicio = date('Y-m-d');
    $fechaFin = match ($periodo) {
        'semanal' => date('Y-m-d', strtotime('+7 days')),
        'mensual' => date('Y-m-d', strtotime('+1 month')),
        'trimestral' => date('Y-m-d', strtotime('+3 months')),
        'anual' => date('Y-m-d', strtotime('+1 year')),
        default => date('Y-m-d', strtotime('+1 month')),
    };

    $pdo = appe_conexion();
    $pdo->beginTransaction();

    try {
        // 1. Asignar el plan y las fechas directamente al cliente
        $stmtUpdate = $pdo->prepare(
            'UPDATE cliente 
                SET id_plan = ?, fecha_inicio = ?, fecha_fin = ?
              WHERE id = ?'
        );
        $stmtUpdate->execute([$idPlan, $fechaInicio, $fechaFin, $idCliente]);

        // 2. Registrar la cuota en el estado de cuenta del cliente
        $stmtCuota = $pdo->prepare(
            'INSERT INTO cuota (id_cliente, id_plan, concepto, monto, fecha_emision, fecha_vencimiento, estado) 
             VALUES (?, ?, ?, ?, ?, ?, "pendiente")'
        );
        $stmtCuota->execute([
            $idCliente, 
            $idPlan, 
            'Cuota - ' . $nombrePlan, 
            $precio, 
            $fechaInicio, 
            $fechaFin
        ]);

        $pdo->commit();
        responder_exito('Plan contratado con éxito.');
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new InternalServerException('Error al procesar la contratación.');
    }
}, 'comprarplan.php');