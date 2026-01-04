<?php
session_start();
require_once 'config/config.php';
require_once 'models/Ingreso.php';
require_once 'models/Egreso.php';
require_once 'models/FlujoCaja.php';

$usuario_id = $_SESSION['user_id'] ?? null;
$anio = date('Y');

if (!$usuario_id) {
    echo "No hay usuario logueado\n";
    exit;
}

echo "Usuario ID: $usuario_id\n";
echo "Año: $anio\n\n";

$ingresoModel = new Ingreso();
$egresoModel = new Egreso();
$flujoModel = new FlujoCaja();

// Verificar datos del mes actual
$mes_actual = date('M');
echo "Mes actual: $mes_actual\n\n";

$total_ingresos = $ingresoModel->getTotalByPeriod($usuario_id, $anio, $mes_actual);
$total_egresos = $egresoModel->getTotalByPeriod($usuario_id, $anio, $mes_actual);

echo "Total ingresos $mes_actual $anio: $total_ingresos\n";
echo "Total egresos $mes_actual $anio: $total_egresos\n\n";

// Verificar si hay datos en flujo_caja
$flujos = $flujoModel->getByPeriod($usuario_id, $anio);
echo "Registros en flujo_caja para $anio: " . count($flujos) . "\n";

if (count($flujos) > 0) {
    echo "Datos del flujo:\n";
    foreach ($flujos as $flujo) {
        echo "Mes: {$flujo['mes']}, Ingresos: {$flujo['total_ingresos']}, Egresos: {$flujo['total_egresos']}, Neto: {$flujo['flujo_financiero']}\n";
    }
} else {
    echo "No hay datos en flujo_caja\n";
}

// Forzar cálculo para el mes actual
echo "\nForzando cálculo para $mes_actual $anio...\n";
$result = $flujoModel->calculateFlujo($usuario_id, $anio, $mes_actual);
echo "Resultado del cálculo: " . ($result ? 'Éxito' : 'Fallo') . "\n";

// Verificar nuevamente
$flujo_actual = $flujoModel->getByMonth($usuario_id, $anio, $mes_actual);
echo "Flujo calculado:\n";
print_r($flujo_actual);
?>