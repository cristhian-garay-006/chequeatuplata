<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/CuentaPorCobrar.php';

$usuario_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: cuentas_cobrar.php?error=ID Invalido');
    exit;
}

$cuentaModel = new CuentaPorCobrar();

// Obtener datos actuales
$cuenta = $cuentaModel->getById($id, $usuario_id);

if (!$cuenta) {
    header('Location: cuentas_cobrar.php?error=Cuenta no encontrada');
    exit;
}

$message = '';
$error = '';

// Procesar Actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $cliente = $_POST['cliente'];
    $deuda_total = (float)$_POST['deuda_total'];
    $cuota_1 = (float)($_POST['cuota_1'] ?? 0);
    $cuota_2 = (float)($_POST['cuota_2'] ?? 0);
    $cuota_3 = (float)($_POST['cuota_3'] ?? 0);

    // Calcular Saldo y Estado
    $saldo = $deuda_total - ($cuota_1 + $cuota_2 + $cuota_3);
    $estado = $saldo <= 0.01 ? 'Pagado' : 'Pendiente'; // Margen de error pequeño para flotantes

    $data = [
        'fecha' => $fecha,
        'cliente' => $cliente,
        'deuda_total' => $deuda_total,
        'cuota_1' => $cuota_1,
        'cuota_2' => $cuota_2,
        'cuota_3' => $cuota_3,
        'saldo' => $saldo,
        'estado' => $estado
    ];

    if ($cuentaModel->update($id, $usuario_id, $data)) {
        header('Location: cuentas_cobrar.php?message=Cuenta actualizada correctamente');
        exit;
    } else {
        $error = "Error al actualizar la cuenta.";
    }
}

$page_title = 'Editar Cuenta por Cobrar';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1>Editar Cuenta por Cobrar</h1>
    <a href="cuentas_cobrar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="dashboard-grid">
    <div class="dashboard-section" style="max-width: 800px; margin: 0 auto; width: 100%;">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="form-card">
            <div class="form-row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="<?php echo $cuenta['fecha']; ?>" required class="form-control">
                </div>
                
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Deuda Total</label>
                    <input type="number" step="0.01" min="0" name="deuda_total" value="<?php echo $cuenta['deuda_total']; ?>" required class="form-control" style="font-weight: bold;">
                </div>
            </div>

            <div class="form-group">
                <label>Cliente</label>
                <input type="text" name="cliente" value="<?php echo htmlspecialchars($cuenta['cliente']); ?>" required class="form-control">
            </div>

            <div style="background: var(--bg-primary); padding: 20px; border-radius: 12px; margin-top: 20px; border: 1px solid var(--border-color);">
                <label style="font-weight: bold; margin-bottom: 15px; display: block; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Registro de Pagos (Cuotas)</label>
                
                <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Cuota 1</label>
                        <input type="number" step="0.01" min="0" name="cuota_1" value="<?php echo $cuenta['cuota_1']; ?>" class="form-control payment-input">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Cuota 2</label>
                        <input type="number" step="0.01" min="0" name="cuota_2" value="<?php echo $cuenta['cuota_2']; ?>" class="form-control payment-input">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px;">
                        <label>Cuota 3</label>
                        <input type="number" step="0.01" min="0" name="cuota_3" value="<?php echo $cuenta['cuota_3']; ?>" class="form-control payment-input">
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; padding: 15px; text-align: right; background: var(--card-bg);">
                <span style="font-size: 1.1em; color: var(--text-secondary);">Saldo Pendiente Estimado: </span>
                <span id="saldoPreview" style="font-size: 1.5em; font-weight: 800; color: var(--expense-color); margin-left: 10px;">
                    <?php echo number_format($cuenta['saldo'], 2); ?>
                </span>
            </div>

            <div class="form-actions" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Cálculo en vivo del saldo visual
const inputs = document.querySelectorAll('input[type="number"]');
const totalInput = document.querySelector('input[name="deuda_total"]');
const saldoDisplay = document.getElementById('saldoPreview');

function updateSaldo() {
    const total = parseFloat(totalInput.value) || 0;
    const c1 = parseFloat(document.querySelector('input[name="cuota_1"]').value) || 0;
    const c2 = parseFloat(document.querySelector('input[name="cuota_2"]').value) || 0;
    const c3 = parseFloat(document.querySelector('input[name="cuota_3"]').value) || 0;
    
    const pagado = c1 + c2 + c3;
    let saldo = total - pagado;
    if (saldo < 0) saldo = 0;
    
    saldoDisplay.textContent = saldo.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    if (saldo <= 0.01) {
        saldoDisplay.style.color = 'var(--income-color)';
        saldoDisplay.textContent += ' (Pagado)';
    } else {
        saldoDisplay.style.color = 'var(--expense-color)';
    }
}

inputs.forEach(input => input.addEventListener('input', updateSaldo));
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
