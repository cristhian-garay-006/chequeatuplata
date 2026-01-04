<?php
session_start();
require_once 'config/config.php';
require_once 'models/Ingreso.php';
require_once 'models/Egreso.php';

$usuario_id = $_SESSION['user_id'] ?? null;
if (!$usuario_id) {
    echo "No hay sesión activa\n";
    exit;
}

$ingresoModel = new Ingreso();
$egresoModel = new Egreso();

echo "Usuario ID: $usuario_id\n";
echo "Verificando datos para 2025:\n\n";

$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

foreach ($meses as $mes) {
    $ingresos = $ingresoModel->getTotalByPeriod($usuario_id, 2025, $mes);
    $egresos = $egresoModel->getTotalByPeriod($usuario_id, 2025, $mes);
    
    if ($ingresos > 0 || $egresos > 0) {
        echo "$mes: Ingresos: $ingresos, Egresos: $egresos\n";
    }
}

echo "\nVerificando datos crudos en base de datos:\n";

// Verificar ingresos
$pdo = new PDO('mysql:host=localhost;dbname=finanzas_personales2;charset=utf8mb4', 'root', '');
$stmt = $pdo->prepare("SELECT fecha, mes, total, descripcion FROM ingresos WHERE usuario_id = ? AND YEAR(fecha) = 2025");
$stmt->execute([$usuario_id]);
$ingresos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Ingresos en DB: " . count($ingresos_db) . "\n";
foreach ($ingresos_db as $ing) {
    echo "  - {$ing['fecha']}: {$ing['mes']} - {$ing['total']} - {$ing['descripcion']}\n";
}

// Verificar egresos
$stmt = $pdo->prepare("SELECT fecha, mes, total, descripcion FROM egresos WHERE usuario_id = ? AND YEAR(fecha) = 2025");
$stmt->execute([$usuario_id]);
$egresos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nEgresos en DB: " . count($egresos_db) . "\n";
foreach ($egresos_db as $egr) {
    echo "  - {$egr['fecha']}: {$egr['mes']} - {$egr['total']} - {$egr['descripcion']}\n";
}
?>