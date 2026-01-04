<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/FlujoCaja.php';
require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/Egreso.php';

$flujoCajaModel = new FlujoCaja();
$usuario_id = $_SESSION['user_id'];

// Obtener año
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// DEBUG: Mostrar información del usuario actual
$ingresoModel = new Ingreso();
$egresoModel = new Egreso();
$mes_actual = date('M');
$total_ingresos_actual = $ingresoModel->getTotalByPeriod($usuario_id, $anio, $mes_actual);
$total_egresos_actual = $egresoModel->getTotalByPeriod($usuario_id, $anio, $mes_actual);

// Calcular flujo de caja para todos los meses del año
$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
foreach ($meses as $mes) {
    $flujoCajaModel->calculateFlujo($usuario_id, $anio, $mes);
}

// Obtener datos del flujo
$flujos = $flujoCajaModel->getByPeriod($usuario_id, $anio);

// Preparar datos para la tabla
$flujo_data = [];
foreach ($meses as $mes) {
    $flujo_data[$mes] = [
        'saldo_inicial' => 0,
        'total_ingresos' => 0,
        'total_egresos' => 0,
        'flujo_financiero' => 0
    ];
}

foreach ($flujos as $flujo) {
    $flujo_data[$flujo['mes']] = $flujo;
}

$page_title = 'Flujo de Caja';
include __DIR__ . '/../views/layouts/header.php';
?>
<div class="page-header">
    <h1>Flujo de Caja - <?php echo $anio; ?></h1>
    <form method="GET" action="" style="display: inline-block;">
        <select name="anio" onchange="this.form.submit()" class="form-control-inline">
            <?php for($y = 2020; $y <= date('Y') + 1; $y++): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $anio ? 'selected' : ''; ?>>
                    <?php echo $y; ?>
                </option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<?php
// Verificar si hay datos
$tiene_datos = false;
foreach ($flujo_data as $mes => $data) {
    if ($data['total_ingresos'] > 0 || $data['total_egresos'] > 0) {
        $tiene_datos = true;
        break;
    }
}
?>

<?php if (!$tiene_datos): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="fas fa-info-circle"></i> 
    <strong>Información:</strong> No se encontraron ingresos o egresos registrados para el año <?php echo $anio; ?>. 
    Los gráficos se mostrarán con valores en cero. 
    
    <br><br>
    <strong>¿Quieres ver cómo funcionan los gráficos?</strong>
    <br>
    <a href="#" onclick="agregarDatosPrueba()" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Agregar Datos de Prueba
    </a>
    <small class="d-block mt-2 text-muted">Esto agregará algunos ingresos y egresos de ejemplo para demostrar los gráficos</small>
</div>

<script>
function agregarDatosPrueba() {
    if (confirm('¿Agregar datos de prueba? Esto creará ingresos y egresos de ejemplo para los últimos 3 meses.')) {
        // Crear ingresos de prueba
        fetch('ingresos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'create': '1',
                'fecha': '2025-10-15',
                'categoria_id': '1',
                'total': '2500',
                'descripcion': 'Sueldo de prueba'
            })
        }).then(() => {
            // Crear egresos de prueba
            fetch('egresos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'create': '1',
                    'fecha': '2025-10-20',
                    'categoria_id': '1',
                    'total': '800',
                    'descripcion': 'Gastos de prueba'
                })
            }).then(() => {
                location.reload();
            });
        });
    }
}
</script>
<?php endif; ?>

<!-- Gráficos (Predominantes) -->
<div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));">
    <div class="chart-section" style="min-height: 400px;">
        <h3><i class="fas fa-chart-line"></i> Evolución: Ingresos vs Egresos</h3>
        <div style="height: 320px;">
            <canvas id="flujoChart"></canvas>
        </div>
    </div>
    
    <div class="chart-section" style="min-height: 400px;">
        <h3><i class="fas fa-chart-bar"></i> Flujo Neto Mensual</h3>
        <div style="height: 320px;">
            <canvas id="netoChart"></canvas>
        </div>
    </div>
</div>

<!-- Gráfico adicional: Barras apiladas -->
<div class="dashboard-grid" style="margin-top: 20px;">
    <div class="chart-section" style="min-height: 400px;">
        <h3><i class="fas fa-layer-group"></i> Comparación Mensual: Ingresos y Egresos</h3>
        <div style="height: 320px;">
            <canvas id="comparacionChart"></canvas>
        </div>
    </div>
    
    <div class="chart-section" style="min-height: 400px;">
        <h3><i class="fas fa-balance-scale"></i> Flujo Acumulado</h3>
        <div style="height: 320px;">
            <canvas id="acumuladoChart"></canvas>
        </div>
    </div>
</div>

<!-- Botón para ver datos -->
<div class="text-center mt-4 mb-5">
    <button class="btn btn-secondary btn-lg" onclick="openModal('detalleModal')">
        <i class="fas fa-table"></i> Ver Tabla de Datos Detallada
    </button>
</div>

<!-- Modal de Tabla de Datos -->
<div id="detalleModal" class="modal">
    <div class="modal-content modal-lg" style="max-width: 95%; margin: 20px auto;">
        <div class="modal-header">
            <h2>Detalle de Flujo de Caja - <?php echo $anio; ?></h2>
            <span class="close" onclick="closeModal('detalleModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Descripción</th>
                            <?php foreach($meses as $mes): ?>
                                <th class="text-right"><?php echo strtoupper($mes); ?></th>
                            <?php endforeach; ?>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Saldo Inicial -->
                        <tr>
                            <td><strong>Saldo Inicial</strong></td>
                            <?php 
                            $total_saldo_inicial = 0;
                            foreach($meses as $mes): 
                                $total_saldo_inicial += $flujo_data[$mes]['saldo_inicial'];
                            ?>
                                <td class="text-right"><?php echo formatMoney($flujo_data[$mes]['saldo_inicial']); ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><strong><?php echo formatMoney($total_saldo_inicial); ?></strong></td>
                        </tr>

                        <!-- Total Ingresos -->
                        <tr class="text-success">
                            <td><strong>Total Ingresos</strong></td>
                            <?php 
                            $total_ingresos_anual = 0;
                            foreach($meses as $mes): 
                                $total_ingresos_anual += $flujo_data[$mes]['total_ingresos'];
                            ?>
                                <td class="text-right"><?php echo formatMoney($flujo_data[$mes]['total_ingresos']); ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><strong><?php echo formatMoney($total_ingresos_anual); ?></strong></td>
                        </tr>

                        <!-- Total Egresos -->
                        <tr class="text-danger">
                            <td><strong>Total Egresos</strong></td>
                            <?php 
                            $total_egresos_anual = 0;
                            foreach($meses as $mes): 
                                $total_egresos_anual += $flujo_data[$mes]['total_egresos'];
                            ?>
                                <td class="text-right"><?php echo formatMoney($flujo_data[$mes]['total_egresos']); ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><strong><?php echo formatMoney($total_egresos_anual); ?></strong></td>
                        </tr>

                        <!-- Flujo Financiero -->
                        <tr style="background-color: #f8f9fa;">
                            <td><strong>Flujo Neto</strong></td>
                            <?php 
                            $total_flujo_anual = 0;
                            foreach($meses as $mes): 
                                $total_flujo_anual += $flujo_data[$mes]['flujo_financiero'];
                                $color_class = $flujo_data[$mes]['flujo_financiero'] >= 0 ? 'text-success' : 'text-danger';
                            ?>
                                <td class="text-right <?php echo $color_class; ?>">
                                    <strong><?php echo formatMoney($flujo_data[$mes]['flujo_financiero']); ?></strong>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-right">
                                <strong class="<?php echo $total_flujo_anual >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo formatMoney($total_flujo_anual); ?>
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.chart-section {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
}

.chart-section h3 {
    color: var(--text-primary);
    margin-bottom: 15px;
    font-size: 1.1rem;
    font-weight: 600;
}

.chart-section h3 i {
    margin-right: 8px;
    color: var(--primary-color);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const meses = <?php echo json_encode($meses); ?>;
const ingresos = <?php echo json_encode(array_values(array_map(function($m) use ($flujo_data) { 
    return (float)$flujo_data[$m]['total_ingresos']; 
}, $meses))); ?>;
const egresos = <?php echo json_encode(array_values(array_map(function($m) use ($flujo_data) { 
    return (float)$flujo_data[$m]['total_egresos']; 
}, $meses))); ?>;
const flujos = <?php echo json_encode(array_values(array_map(function($m) use ($flujo_data) { 
    return (float)$flujo_data[$m]['flujo_financiero']; 
}, $meses))); ?>;

// DEBUG: Mostrar datos en consola
console.log('Datos para gráficos - Ingresos:', ingresos, 'Egresos:', egresos, 'Flujos:', flujos);

// Calcular flujo acumulado
const acumulado = [];
let suma = 0;
flujos.forEach(flujo => {
    suma += flujo;
    acumulado.push(suma);
});

// Verificar si Chart.js está disponible
if (typeof Chart === 'undefined') {
    console.error('Chart.js no está cargado');
} else {
    console.log('Chart.js está disponible');
}

// Gráfico 1: Evolución Línea (mejorado)
const ctx1 = document.getElementById('flujoChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: meses,
        datasets: [
            {
                label: 'Ingresos',
                data: ingresos,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            },
            {
                label: 'Egresos',
                data: egresos,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': S/ ' + context.raw.toLocaleString('es-PE');
                    }
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { 
                    callback: (val) => 'S/ ' + val.toLocaleString('es-PE'),
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        }
    }
    });
}

// Gráfico 2: Barras Neto (mejorado)
const ctx2 = document.getElementById('netoChart').getContext('2d');
const backgroundColors = flujos.map(val => val >= 0 ? 'rgba(16, 185, 129, 0.8)' : 'rgba(239, 68, 68, 0.8)');
const borderColors = flujos.map(val => val >= 0 ? '#10b981' : '#ef4444');

new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: meses,
        datasets: [{
            label: 'Flujo Neto',
            data: flujos,
            backgroundColor: backgroundColors,
            borderColor: borderColors,
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const tipo = context.raw >= 0 ? 'Superávit' : 'Déficit';
                        return tipo + ': S/ ' + Math.abs(context.raw).toLocaleString('es-PE');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { 
                    callback: (val) => 'S/ ' + val.toLocaleString('es-PE'),
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        }
    }
    });
}

// Gráfico 3: Barras apiladas (Ingresos vs Egresos)
const ctx3 = document.getElementById('comparacionChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: meses,
        datasets: [
            {
                label: 'Ingresos',
                data: ingresos,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: '#10b981',
                borderWidth: 1
            },
            {
                label: 'Egresos',
                data: egresos,
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderColor: '#ef4444',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': S/ ' + context.raw.toLocaleString('es-PE');
                    }
                }
            }
        },
        scales: {
            x: {
                stacked: false,
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            y: {
                stacked: false,
                beginAtZero: true,
                ticks: { 
                    callback: (val) => 'S/ ' + val.toLocaleString('es-PE'),
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        }
    }
    });
}

// Gráfico 4: Flujo acumulado
const ctx4 = document.getElementById('acumuladoChart').getContext('2d');
new Chart(ctx4, {
    type: 'line',
    data: {
        labels: meses,
        datasets: [{
            label: 'Flujo Acumulado',
            data: acumulado,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Acumulado: S/ ' + context.raw.toLocaleString('es-PE');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { 
                    callback: (val) => 'S/ ' + val.toLocaleString('es-PE'),
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        }
    }
});

</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>