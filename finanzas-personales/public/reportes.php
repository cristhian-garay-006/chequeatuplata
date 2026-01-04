<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../models/FlujoCaja.php';

$ingresoModel = new Ingreso();
$egresoModel = new Egreso();
$flujoCajaModel = new FlujoCaja();
$usuario_id = $_SESSION['user_id'];

// --- LÓGICA DE FILTROS ---
$tipo_reporte = $_GET['tipo'] ?? 'anual'; // anual, trimestral, mensual, semanal, diario
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$trimestre = isset($_GET['trimestre']) ? (int)$_GET['trimestre'] : ceil(date('n') / 3);
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$semana = isset($_GET['semana']) ? $_GET['semana'] : date('Y-\nW'); // Formato ISO-8601
$fecha_diaria = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Arrays auxiliares
$meses_nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$meses_cortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

// Calcular Rango de Fechas y Título
switch ($tipo_reporte) {
    case 'anual':
        $fecha_inicio = "$anio-01-01";
        $fecha_fin = "$anio-12-31";
        $titulo_reporte = "Reporte Anual $anio";
        break;
    case 'trimestral':
        $mes_inicio = ($trimestre - 1) * 3 + 1;
        $mes_fin = $mes_inicio + 2;
        $fecha_inicio = "$anio-" . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . "-01";
        $fecha_fin = date("Y-m-t", strtotime("$anio-" . str_pad($mes_fin, 2, '0', STR_PAD_LEFT) . "-01"));
        $titulo_reporte = "Reporte Trimestral (T$trimestre - $anio)";
        break;
    case 'mensual':
        $fecha_inicio = "$anio-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
        $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));
        $titulo_reporte = "Reporte Mensual (" . $meses_nombres[$mes - 1] . " $anio)";
        break;
    case 'semanal':
        // Semana ISO
        if (strpos($semana, 'W') === false) { $semana = date('Y-\nW'); }
        $dto = new DateTime();
        $dto->setISODate((int)substr($semana, 0, 4), (int)substr($semana, 6)); // Año y número de semana
        $fecha_inicio = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $fecha_fin = $dto->format('Y-m-d');
        $titulo_reporte = "Reporte Semanal ($fecha_inicio al $fecha_fin)";
        break;
    case 'diario':
        $fecha_inicio = $fecha_diaria;
        $fecha_fin = $fecha_diaria;
        $titulo_reporte = "Reporte Diario (" . date("d/m/Y", strtotime($fecha_diaria)) . ")";
        break;
    default:
        $tipo_reporte = 'anual';
        $fecha_inicio = "$anio-01-01";
        $fecha_fin = "$anio-12-31";
        $titulo_reporte = "Reporte Anual $anio";
}

// Obtener datos
$ingresos = $ingresoModel->getByDateRange($usuario_id, $fecha_inicio, $fecha_fin);
$egresos = $egresoModel->getByDateRange($usuario_id, $fecha_inicio, $fecha_fin);

// Calcular Totales
$total_ingresos = array_sum(array_column($ingresos, 'total'));
$total_egresos = array_sum(array_column($egresos, 'total'));
$balance = $total_ingresos - $total_egresos;

// Categorizar Egresos para Gráfico
$egresos_por_categoria = [];
foreach ($egresos as $egreso) {
    // Fallback: si no hay categoria_tipo, usar categoria_nombre o 'Otros'
    $catName = $egreso['categoria_tipo'] ?? $egreso['categoria_nombre'] ?? 'Otros'; // Ajuste según lo que devuelve el modelo

    if (!isset($egresos_por_categoria[$catName])) {
        $egresos_por_categoria[$catName] = 0;
    }
    $egresos_por_categoria[$catName] += $egreso['total'];
}

// Datos para Gráfico de Evolución
// Dependiendo del tipo, agrupamos por Mes o por Día
$evolucion_labels = [];
$evolucion_ingresos = [];
$evolucion_egresos = [];

if (in_array($tipo_reporte, ['anual', 'trimestral'])) {
    // Agrupar por meses
    $start = new DateTime($fecha_inicio);
    $end = new DateTime($fecha_fin);
    $end->modify('last day of this month'); // Asegurar cubrir todo el rango
    $interval = DateInterval::createFromDateString('1 month');
    $period = new DatePeriod($start, $interval, $end);

    foreach ($period as $dt) {
        $mesKey = $meses_cortos[$dt->format('n') - 1]; // "Ene", "Feb"...
        $mNum = $dt->format('n');
        
        $evolucion_labels[] = $mesKey;

        // Filtrar ingresos del mes
        $ingresos_mes = array_filter($ingresos, function($i) use ($mNum) {
            return date('n', strtotime($i['fecha'])) == $mNum;
        });
        $evolucion_ingresos[] = array_sum(array_column($ingresos_mes, 'total'));

        // Filtrar egresos del mes
        $egresos_mes = array_filter($egresos, function($e) use ($mNum) {
            return date('n', strtotime($e['fecha'])) == $mNum;
        });
        $evolucion_egresos[] = array_sum(array_column($egresos_mes, 'total'));
    }

} else {
    // Agrupar por días (Mensual, Semanal, Diario)
    // Para diario será solo 1 barra, pero funciona igual
    $start = new DateTime($fecha_inicio);
    $end = new DateTime($fecha_fin);
    $end->modify('+1 day'); // Incluir el último día en el loop
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($start, $interval, $end);

    foreach ($period as $dt) {
        $diaKey = $dt->format('d/m'); // "01/01"
        $yMd = $dt->format('Y-m-d');
        
        $evolucion_labels[] = $diaKey;

        $ingresos_dia = array_filter($ingresos, function($i) use ($yMd) {
            return substr($i['fecha'], 0, 10) == $yMd;
        });
        $evolucion_ingresos[] = array_sum(array_column($ingresos_dia, 'total'));

        $egresos_dia = array_filter($egresos, function($e) use ($yMd) {
            return substr($e['fecha'], 0, 10) == $yMd;
        });
        $evolucion_egresos[] = array_sum(array_column($egresos_dia, 'total'));
    }
}


$page_title = 'Reportes';
include __DIR__ . '/../views/layouts/header.php';
?>

<!-- Print Header -->
<div class="print-header" style="display: none;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid var(--primary-color); padding-bottom: 20px; margin-bottom: 30px;">
        <div style="display:flex; align-items:center; gap: 15px;">
            <img src="img/logo.png" alt="Logo" style="height: 60px;">
            <div>
                <h2 style="margin:0; color:var(--primary-color);">Finanzas Personales</h2>
                <p style="margin:0; font-size: 0.9em; color: var(--text-secondary);">Reporte de Gestión Financiera</p>
            </div>
        </div>
        <div style="text-align:right;">
            <h1 style="margin:0; font-size: 1.8em;"><?php echo mb_strtoupper($titulo_reporte); ?></h1>
            <p style="margin:5px 0 0; font-size: 0.9em;">Generado el: <?php echo date('d/m/Y H:i'); ?></p>
            <p style="margin:0; font-size: 0.9em;">Usuario: <?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></p>
        </div>
    </div>
</div>

<div class="page-header no-print">
    <h1>Reportes</h1>
    
    <!-- Filtros -->
    <div class="report-filters" style="background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: nowrap; align-items: center; width: 100%; overflow-x: auto;">
            <!-- Selector de Tipo -->
            <select name="tipo" class="form-control-inline" onchange="this.form.submit()" style="min-width: 120px;">
                <option value="anual" <?php echo $tipo_reporte == 'anual' ? 'selected' : ''; ?>>Anual</option>
                <option value="trimestral" <?php echo $tipo_reporte == 'trimestral' ? 'selected' : ''; ?>>Trimestral</option>
                <option value="mensual" <?php echo $tipo_reporte == 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                <option value="semanal" <?php echo $tipo_reporte == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                <option value="diario" <?php echo $tipo_reporte == 'diario' ? 'selected' : ''; ?>>Diario</option>
            </select>

            <!-- Inputs dinámicos según tipo -->
            <?php if ($tipo_reporte == 'anual' || $tipo_reporte == 'trimestral' || $tipo_reporte == 'mensual'): ?>
                <select name="anio" class="form-control-inline" onchange="this.form.submit()" style="min-width: 100px;">
                    <?php for($y = 2020; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $anio ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            <?php endif; ?>

            <?php if ($tipo_reporte == 'trimestral'): ?>
                <select name="trimestre" class="form-control-inline" onchange="this.form.submit()" style="flex-grow: 1;">
                    <option value="1" <?php echo $trimestre == 1 ? 'selected' : ''; ?>>T1 (Ene-Mar)</option>
                    <option value="2" <?php echo $trimestre == 2 ? 'selected' : ''; ?>>T2 (Abr-Jun)</option>
                    <option value="3" <?php echo $trimestre == 3 ? 'selected' : ''; ?>>T3 (Jul-Sep)</option>
                    <option value="4" <?php echo $trimestre == 4 ? 'selected' : ''; ?>>T4 (Oct-Dic)</option>
                </select>
            <?php endif; ?>

            <?php if ($tipo_reporte == 'mensual'): ?>
                <select name="mes" class="form-control-inline" onchange="this.form.submit()" style="min-width: 120px;">
                    <?php foreach ($meses_nombres as $idx => $mNombre): ?>
                        <option value="<?php echo $idx + 1; ?>" <?php echo ($idx + 1) == $mes ? 'selected' : ''; ?>>
                            <?php echo $mNombre; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if ($tipo_reporte == 'semanal'): ?>
                <input type="week" name="semana" class="form-control-inline" value="<?php echo $semana; ?>" onchange="this.form.submit()" style="min-width: 150px;">
            <?php endif; ?>

            <?php if ($tipo_reporte == 'diario'): ?>
                <input type="date" name="fecha" class="form-control-inline" value="<?php echo $fecha_diaria; ?>" onchange="this.form.submit()" style="min-width: 150px;">
            <?php endif; ?>

            <a href="reportes.php" class="btn btn-sm btn-secondary" title="Reiniciar filtros" style="white-space: nowrap;"><i class="fas fa-sync"></i></a>
            
            <div style="margin-left: auto; display: flex; gap: 5px; white-space: nowrap;">
                 <button type="button" onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i>
                </button>
                <a href="export_excel.php?tipo=<?php echo $tipo_reporte; ?>&anio=<?php echo $anio; ?>&mes=<?php echo $mes; ?>&trimestre=<?php echo $trimestre; ?>&semana=<?php echo $semana; ?>&fecha=<?php echo $fecha_diaria; ?>" class="btn btn-success" style="text-decoration: none; color: white;">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
        </form>
    </div>
</div>

<h2 class="text-center section-title" style="margin-top: 20px;"><?php echo $titulo_reporte; ?></h2>

<!-- Resumen -->
<div class="cards-grid">
    <div class="card card-success">
        <h3>Total Ingresos</h3>
        <p class="amount"><?php echo formatMoney($total_ingresos); ?></p>
    </div>
    
    <div class="card card-danger">
        <h3>Total Egresos</h3>
        <p class="amount"><?php echo formatMoney($total_egresos); ?></p>
    </div>
    
    <div class="card <?php echo $balance >= 0 ? 'card-success' : 'card-danger'; ?>">
        <h3>Balance</h3>
        <p class="amount"><?php echo formatMoney($balance); ?></p>
    </div>
    
    <div class="card card-info">
        <h3>Promedio <?php echo $tipo_reporte == 'diario' ? 'Diario' : 'del Período'; ?></h3>
        <?php 
            // Calcular promedio simple basado en cantidad de elementos en gráfico evolución (meses o días)
            $divisor = count($evolucion_labels) > 0 ? count($evolucion_labels) : 1;
            $promedio = $total_ingresos / $divisor;
        ?>
        <p class="amount"><?php echo formatMoney($promedio); ?></p>
    </div>
</div>

<!-- Gráficos -->
<div class="dashboard-grid graphic-section">
    <div class="chart-section" style="page-break-inside: avoid;">
        <h3>Evolución</h3>
        <div class="chart-wrapper" style="height: 300px; position: relative;">
            <canvas id="evolucionChart"></canvas>
        </div>
    </div>
    
    <div class="chart-section" style="page-break-inside: avoid;">
        <h3>Distribución de Egresos</h3>
        <div class="chart-wrapper" style="height: 300px; position: relative;">
            <canvas id="distribucionChart"></canvas>
        </div>
        <?php if(empty($egresos_por_categoria)): ?>
            <p class="text-center text-muted" style="margin-top: 20px;">No hay egresos registrados en este período.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Botones de Detalle (Screen Only) -->
<div class="dashboard-grid no-print" style="margin-top: 30px;">
    <div class="dashboard-section text-center">
        <h3>Detalle de Ingresos</h3>
        <button class="btn btn-success btn-lg btn-block" onclick="openReportModal('ingresosModal')">
            <i class="fas fa-chart-line"></i> Evolución Mensual
        </button>
        <button class="btn btn-outline-success btn-lg btn-block" style="margin-top: 10px;" onclick="openReportModal('detalleIngresosModal')">
            <i class="fas fa-list"></i> Ver Lista de Transacciones
        </button>
    </div>
    
    <div class="dashboard-section text-center">
        <h3>Detalle de Egresos</h3>
        <button class="btn btn-danger btn-lg btn-block" onclick="openReportModal('egresosModal')">
            <i class="fas fa-chart-pie"></i> Evolución Mensual
        </button>
        <button class="btn btn-outline-danger btn-lg btn-block" style="margin-top: 10px;" onclick="openReportModal('detalleEgresosModal')">
            <i class="fas fa-list"></i> Ver Lista de Transacciones
        </button>
    </div>
</div>

<!-- DETALLE IMPRIMIBLE (Print Only) -->
<div class="print-details" style="display: none; margin-top: 30px;">
    
    <!-- Tabla Ingresos -->
    <div class="section-break" style="page-break-before: always;">
        <h3 style="border-bottom: 2px solid var(--income-color); padding-bottom: 10px; color: var(--income-color);">
            <i class="fas fa-arrow-up"></i> Detalle de Ingresos
        </h3>
        <table class="table table-bordered text-sm" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <thead>
                <tr style="background: #f0fdf4;">
                    <th style="border: 1px solid #ddd; padding: 8px;">Fecha</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Descripción</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Categoría</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingresos as $ing): ?>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($ing['descripcion'] ?? '-'); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($ing['categoria_nombre'] ?? 'Sin cat.'); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; color: var(--income-color);"><?php echo formatMoney($ing['total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f0fdf4;">
                    <td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>TOTAL</strong></td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; font-size: 1.1em; color: var(--income-color);"><?php echo formatMoney($total_ingresos); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Tabla Egresos -->
    <div class="section-break" style="margin-top: 30px;">
        <h3 style="border-bottom: 2px solid var(--expense-color); padding-bottom: 10px; color: var(--expense-color);">
            <i class="fas fa-arrow-down"></i> Detalle de Egresos
        </h3>
        <table class="table table-bordered text-sm" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
             <thead>
                <tr style="background: #fef2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px;">Fecha</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Descripción</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Categoría</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: right;">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($egresos as $egr): ?>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo date('d/m/Y', strtotime($egr['fecha'])); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($egr['descripcion']); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($egr['categoria_tipo'] ?? $egr['categoria_nombre'] ?? 'Otro'); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; color: var(--expense-color);"><?php echo formatMoney($egr['total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #fef2f2;">
                    <td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: right;"><strong>TOTAL</strong></td>
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; font-size: 1.1em; color: var(--expense-color);"><?php echo formatMoney($total_egresos); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Firma / Footer -->
    <div style="margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; text-align: center; color: var(--text-secondary); font-size: 0.8em;">
        <p>Este reporte ha sido generado automáticamente por el sistema de Finanzas Personales.</p>
        <p>Fin del reporte.</p>
    </div>
</div>

<!-- Modal Ingresos Mensual (Cards) -->
<div id="ingresosModal" class="modal no-print">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Evolución de Ingresos - <?php echo $titulo_reporte; ?></h2>
            <span class="close" onclick="closeReportModal('ingresosModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
            <div class="monthly-grid">
                <?php 
                // Usamos evolucion_labels para generar las cards según el período
                foreach ($evolucion_labels as $idx => $label): 
                    $monto = $evolucion_ingresos[$idx] ?? 0;
                    $isActive = $monto > 0 ? 'active-month' : '';
                ?>
                <div class="month-card <?php echo $isActive; ?>">
                    <h4><?php echo $label; ?></h4>
                    <div class="month-total text-success">
                        <?php echo formatMoney($monto); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 20px; text-align: right; padding: 20px; border-top: 1px solid var(--border-color);">
                <strong>TOTAL: </strong>
                <span class="text-success" style="font-size: 20px; font-weight: 800;"><?php echo formatMoney($total_ingresos); ?></span>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Modal Egresos Mensual (Cards) -->
<div id="egresosModal" class="modal no-print">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>Evolución de Egresos - <?php echo $titulo_reporte; ?></h2>
            <span class="close" onclick="closeReportModal('egresosModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="monthly-grid">
                <?php 
                 foreach ($evolucion_labels as $idx => $label): 
                    $monto = $evolucion_egresos[$idx] ?? 0;
                    $isActive = $monto > 0 ? 'active-month' : '';
                ?>
                <div class="month-card <?php echo $isActive; ?>">
                    <h4><?php echo $label; ?></h4>
                    <div class="month-total text-danger">
                        <?php echo formatMoney($monto); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 20px; text-align: right; padding: 20px; border-top: 1px solid var(--border-color);">
                <strong>TOTAL: </strong>
                <span class="text-danger" style="font-size: 20px; font-weight: 800;"><?php echo formatMoney($total_egresos); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- NUEVOS MODALES: Detalle Transacciones -->

<!-- Modal Detalle Ingresos (Tabla) -->
<div id="detalleIngresosModal" class="modal no-print">
    <div class="modal-content modal-lg">
         <div class="modal-header">
            <h2 style="color: var(--income-color);"><i class="fas fa-list"></i> Lista de Ingresos - <?php echo $titulo_reporte; ?></h2>
            <span class="close" onclick="closeReportModal('detalleIngresosModal')">&times;</span>
        </div>
        <div class="modal-body">
             <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingresos as $ing): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($ing['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($ing['descripcion'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($ing['categoria_nombre'] ?? 'Sin cat.'); ?></td>
                            <td class="text-right text-success font-weight-bold"><?php echo formatMoney($ing['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($ingresos)): ?>
                        <tr><td colspan="4" class="text-center">No hay registros.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Egresos (Tabla) -->
<div id="detalleEgresosModal" class="modal no-print">
    <div class="modal-content modal-lg">
         <div class="modal-header">
            <h2 style="color: var(--expense-color);"><i class="fas fa-list"></i> Lista de Egresos - <?php echo $titulo_reporte; ?></h2>
            <span class="close" onclick="closeReportModal('detalleEgresosModal')">&times;</span>
        </div>
        <div class="modal-body">
             <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($egresos as $egr): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($egr['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($egr['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($egr['categoria_tipo'] ?? $egr['categoria_nombre'] ?? 'Otro'); ?></td> 
                            <td class="text-right text-danger font-weight-bold"><?php echo formatMoney($egr['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($egresos)): ?>
                        <tr><td colspan="4" class="text-center">No hay registros.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Función para manejo de modales
function openReportModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.add('active');
        modal.style.display = 'flex'; 
        document.body.style.overflow = 'hidden';
    }
}

function closeReportModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Datos Evolución
const labels_ev = <?php echo json_encode($evolucion_labels); ?>;
const data_ingresos_ev = <?php echo json_encode($evolucion_ingresos); ?>;
const data_egresos_ev = <?php echo json_encode($evolucion_egresos); ?>;

const ctx1 = document.getElementById('evolucionChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: labels_ev,
        datasets: [
            {
                label: 'Ingresos',
                data: data_ingresos_ev,
                backgroundColor: 'rgba(76, 175, 80, 0.7)',
                borderColor: '#4CAF50',
                borderWidth: 2
            },
            {
                label: 'Egresos',
                data: data_egresos_ev,
                backgroundColor: 'rgba(244, 67, 54, 0.7)',
                borderColor: '#f44336',
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) { return 'S/ ' + value.toLocaleString('es-PE'); }
                }
            }
        }
    }
});

// Datos Distribución Egresos
const egresos_cats = <?php echo json_encode(array_keys($egresos_por_categoria)); ?>;
const egresos_vals = <?php echo json_encode(array_values($egresos_por_categoria)); ?>;

const ctx2 = document.getElementById('distribucionChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: egresos_cats,
        datasets: [{
            data: egresos_vals,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF', '#E7E9ED', '#71B37C'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>