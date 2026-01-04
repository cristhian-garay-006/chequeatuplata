<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/DeudaController.php';

$dashboardController = new DashboardController();
$deudaController = new DeudaController();

// Datos básicos
$anio_actual = date('Y');
$mes_actual = date('M');
$usuario_id = $_SESSION['user_id'];

// Obtener deuda pendiente para notificación
$deudas_pendientes = $deudaController->getDueDebts($usuario_id);

// Obtener resumen mensual simple
$resumen = $dashboardController->getResumenMensual($usuario_id, $anio_actual, $mes_actual, null);

// Lógica de Saludo
$hora = date('H');
$saludo = "Hola";
if ($hora >= 5 && $hora < 12) {
    $saludo = "Buenos días";
} elseif ($hora >= 12 && $hora < 19) {
    $saludo = "Buenas tardes";
} else {
    $saludo = "Buenas noches";
}

include __DIR__ . '/../views/layouts/header.php';
?>

<!-- DASHBOARD WELCOME STYLES -->
<!-- DASHBOARD WELCOME STYLES MOVED TO style.css -->

<div class="welcome-container">
    
    <!-- Hero Welcome -->
    <div class="welcome-hero">
        <div class="circle-decoration c1"></div>
        <div class="circle-decoration c2"></div>
        <div class="circle-decoration c3"></div>
        
        <h1><?php echo $saludo; ?>, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>!</h1>
        <p>Bienvenid<?php echo isset($_SESSION['genero']) && $_SESSION['genero'] == 'F' ? 'a' : 'o'; ?> a tu sistema de finanzas personales.</p>
        <p style="font-size: 0.9rem; margin-top: 10px; opacity: 0.8;">
            <i class="far fa-clock"></i> <?php echo date('l, d \d\e F \d\e Y'); ?>
        </p>
    </div>

    <!-- Alerts Deudas -->
    <!-- Alerts Deudas -->
    <?php if (!empty($deudas_pendientes)): ?>
        <?php foreach ($deudas_pendientes as $deuda): 
            $fecha_pago = new DateTime($deuda['proximo_pago']);
            $hoy = new DateTime();
            $hoy->setTime(0,0,0);
            $fecha_pago->setTime(0,0,0);
            
            $diff = $hoy->diff($fecha_pago);
            $dias_restantes = (int)$diff->format('%R%a'); // + or - days

            // Determine Style
            if ($dias_restantes < 0) {
                // Vencido
                $color = '#dc2626'; $bg = '#fee2e2'; $icon = 'exclamation-circle'; $border = '#f87171';
                $status_msg = "¡VENCIDO hace " . abs($dias_restantes) . " días!";
            } elseif ($dias_restantes == 0) {
                // Hoy
                $color = '#d97706'; $bg = '#fffbeb'; $icon = 'bell'; $border = '#fbbf24';
                $status_msg = "¡Vence HOY!";
            } else {
                // Próximo
                $color = '#2563eb'; $bg = '#eff6ff'; $icon = 'calendar-alt'; $border = '#60a5fa';
                $status_msg = "Vence en $dias_restantes días";
            }
        ?>
        <div class="alert" style="border-left: 5px solid <?php echo $border; ?>; background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 15px; border-radius: 12px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div style="background: rgba(255,255,255,0.6); padding: 10px; border-radius: 50%;">
                <i class="fas fa-<?php echo $icon; ?> fa-lg"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 5px 0; font-size: 1rem; font-weight: 700;"><?php echo $status_msg; ?></h4>
                <p style="margin: 0; font-size: 0.95rem;">
                    Pago a <strong><?php echo htmlspecialchars($deuda['acreedor']); ?></strong> 
                    de <strong><?php echo formatMoney($deuda['monto_pendiente']); ?></strong>
                    (Vence: <?php echo $fecha_pago->format('d/m/Y'); ?>)
                </p>
            </div>
            <a href="deudas.php" class="btn btn-sm" style="background: <?php echo $color; ?>; color: white; border: none; font-weight: 600;">Ver</a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Quick Actions -->
    <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-secondary);">¿Qué deseas hacer hoy?</h3>
    <div class="actions-grid">
        <a href="ingresos.php" class="action-card">
            <div class="action-icon icon-ingreso">
                <i class="fas fa-plus"></i>
            </div>
            <div class="action-title">Registrar Ingreso</div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">Añade dinero a tu cuenta</div>
        </a>

        <a href="egresos.php" class="action-card">
            <div class="action-icon icon-egreso">
                <i class="fas fa-minus"></i>
            </div>
            <div class="action-title">Registrar Gasto</div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">Controla tus salidas</div>
        </a>

        <a href="reportes.php" class="action-card">
            <div class="action-icon icon-reporte">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="action-title">Ver Reportes</div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">Analiza tus finanzas</div>
        </a>

        <a href="perfil.php" class="action-card">
            <div class="action-icon icon-config">
                <i class="fas fa-user-cog"></i>
            </div>
            <div class="action-title">Mi Perfil</div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">Configura tu cuenta</div>
        </a>
    </div>

    <!-- Monthly Snapshot -->
    <div class="summary-section">
        <div class="summary-header">
            <h3 style="margin: 0; font-weight: 700;">Resumen del Mes (<?php echo $mes_actual; ?>)</h3>
            <a href="dashboard.php?mes=<?php echo date('M', strtotime('-1 month')); ?>" class="btn btn-sm btn-outline-primary" style="border: 1px solid var(--border-color);">Ver Mes Anterior</a>
        </div>
        
        <div class="summary-metrics">
            <div class="metric-item">
                <small>Ingresos</small>
                <span style="color: var(--income-color);">
                    <i class="fas fa-arrow-up" style="font-size: 0.7em;"></i> <?php echo formatMoney($resumen['total_ingresos']); ?>
                </span>
            </div>
            
             <div class="metric-item">
                <small>Balance</small>
                <span style="color: <?php echo $resumen['balance'] >= 0 ? 'var(--primary-color)' : 'var(--expense-color)'; ?>;">
                    <?php echo formatMoney($resumen['balance']); ?>
                </span>
            </div>

            <div class="metric-item">
                <small>Gastos</small>
                <span style="color: var(--expense-color);">
                    <i class="fas fa-arrow-down" style="font-size: 0.7em;"></i> <?php echo formatMoney($resumen['total_egresos']); ?>
                </span>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>