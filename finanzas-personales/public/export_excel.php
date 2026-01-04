<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';

$usuario_id = $_SESSION['user_id'];
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

$ingresoModel = new Ingreso();
$egresoModel = new Egreso();

// Obtener datos
$ingresos = $ingresoModel->getAll($usuario_id, $anio);
$egresos = $egresoModel->getAll($usuario_id, $anio);

// Combinar datos para el reporte
$data = [];

foreach ($ingresos as $ingreso) {
    $data[] = [
        'fecha' => $ingreso['fecha'],
        'tipo' => 'Ingreso',
        'categoria' => $ingreso['nombre_categoria'] ?? 'Sin Categoría', // Asumiendo que el modelo hace join, si no, habría que ajustar
        'descripcion' => $ingreso['descripcion'] ?? $ingreso['cliente'] ?? '',
        'monto' => $ingreso['total']
    ];
}

foreach ($egresos as $egreso) {
    $data[] = [
        'fecha' => $egreso['fecha'],
        'tipo' => 'Egreso',
        'categoria' => $egreso['nombre_categoria'] ?? 'Sin Categoría',
        'descripcion' => $egreso['descripcion'] ?? '',
        'monto' => -$egreso['total'] // Negativo para distinguir fácilmente
    ];
}

// Ordenar por fecha reciente primero
usort($data, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Configurar cabeceras para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_financiero_' . $anio . '.csv');

// Crear salida
$output = fopen('php://output', 'w');

// BOM para Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeceras de columna
fputcsv($output, ['Fecha', 'Tipo', 'Categoría', 'Descripción', 'Monto']);

// Escribir datos
foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
