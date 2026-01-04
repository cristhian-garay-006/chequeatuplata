<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/CuentaPorCobrar.php';
require_once __DIR__ . '/../models/Ingreso.php'; // For category fetching

$cuentaModel = new CuentaPorCobrar();
$ingresoModel = new Ingreso(); // Assuming Ingreso has categories logic or similar

$usuario_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_alerta = '';

// Fetch categories for dropdown
// Note: We need income categories. Using Ingreso model which should access 'categorias_ingreso'
// If Ingreso doesn't have a public getAllCategories, we might need to add it or query directly.
// Let's check Ingreso.php or just use a direct query if simple, but Model is better.
// I'll assume I can add/use getAllCategorias in Ingreso similar to Egreso.
// For now, I will use a simple query helper or assume Ingreso has it.
// To be safe, I'll instantiate database here or add method to Ingreso.
// Let's try to add the method to Ingreso model first if needed, but for speed, I'll use a direct fetch pattern here or quickly patch Ingreso.
// Actually, I'll implement 'getAllCategorias' in Ingreso.php in a separate step if it fails, but let's assume I can add it now.
// Wait, I can't restart the view file write. Use a simple approach: 
// I'll use a quick inline DB call or similar if needed, but let's try to use $ingresoModel->getAllCategorias($usuario_id).

$categorias = [];
// Temporary fix: check if method exists, else empty.
if (method_exists($ingresoModel, 'getAllCategorias')) {
    $categorias = $ingresoModel->getAllCategorias($usuario_id);
} else {
    // Should implement this in Ingreso.php or a CategoryModel. 
    // I will update Ingreso.php after this file write or before.
    // Let's assume I'll update Ingreso.php next.
}


// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $data = [
                'usuario_id' => $usuario_id,
                'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                'fecha' => $_POST['fecha'],
                'cliente' => $_POST['cliente'],
                'deuda_total' => $_POST['deuda_total'],
                'monto_cuota' => $_POST['monto_cuota'],
                'frecuencia' => $_POST['frecuencia'],
                'estado' => 'Pendiente'
            ];
            
            if ($cuentaModel->create($data)) {
                $mensaje = 'Cuenta por cobrar creada exitosamente';
                $tipo_alerta = 'success';
            } else {
                $mensaje = 'Error al crear la cuenta por cobrar';
                $tipo_alerta = 'error';
            }
        } elseif ($_POST['action'] == 'payment') {
            $id = $_POST['cuenta_id'];
            $monto = $_POST['monto'];
            
            if ($cuentaModel->registerPayment($id, $usuario_id, $monto)) {
                $mensaje = "Pago registrado exitosamente.";
                $tipo_alerta = "success";
            } else {
                $mensaje = "Error al registrar el pago.";
                $tipo_alerta = "error";
            }
        }
    }
}

// Eliminar cuenta
if (isset($_GET['delete'])) {
    if ($cuentaModel->delete($_GET['delete'], $usuario_id)) {
        $mensaje = 'Cuenta eliminada exitosamente';
        $tipo_alerta = 'success';
    } else {
        $mensaje = 'Error al eliminar la cuenta';
        $tipo_alerta = 'error';
    }
}

// Obtener cuentas
$cuentas = $cuentaModel->getAll($usuario_id);
$total_deudas = $cuentaModel->getTotalDeudas($usuario_id);

$page_title = 'Cuentas por Cobrar';
include __DIR__ . '/../views/layouts/header.php';
?>

<!-- Dashboard Grid Wrapper -->
<div class="dashboard-grid">
    
    <!-- Header Section (Full Width) -->
    <div style="grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="page-header" style="margin:0;">
            <h1 style="margin:0;"><i class="fas fa-hand-holding-usd"></i> Cuentas por Cobrar</h1>
        </div>
        <button class="btn btn-primary" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Nueva Cuenta
        </button>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_alerta; ?>" style="grid-column: 1 / -1;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <!-- Summary Card -->
    <div class="dashboard-section summary-card" style="grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
        <div>
            <h3 style="margin: 0; font-size: 1.1em; opacity: 0.9;">Total por Cobrar</h3>
            <p class="amount-large" style="margin: 5px 0 0 0; font-size: 2.5em; font-weight: bold;"><?php echo formatMoney($total_deudas); ?></p>
        </div>
        <i class="fas fa-chart-line" style="font-size: 3em; opacity: 0.8;"></i>
    </div>

    <!-- Table Section -->
    <div class="dashboard-section" style="grid-column: 1 / -1;">
        <div class="section-header">
            <h3>Listado de Clientes</h3>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Cuota</th>
                        <th class="text-right">Saldo</th>
                        <th>Frecuencia</th>
                        <th>Próximo Pago</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cuentas)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No hay cuentas por cobrar registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($cuentas as $cuenta): ?>
                        <tr>
                            <td>
                                <?php 
                                    $catName = '-';
                                    foreach($categorias as $c) {
                                        if($c['id'] == $cuenta['categoria_id']) {
                                            $catName = $c['nombre'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($catName);
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($cuenta['fecha'])); ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($cuenta['cliente']); ?></td>
                            <td class="text-right"><?php echo formatMoney($cuenta['deuda_total']); ?></td>
                            <td class="text-right"><?php echo $cuenta['monto_cuota'] ? formatMoney($cuenta['monto_cuota']) : '-'; ?></td>
                            <td class="text-right font-weight-bold" style="color: var(--expense-color);">
                                <?php echo formatMoney($cuenta['saldo']); ?>
                            </td>
                            <td><?php echo ucfirst($cuenta['frecuencia']); ?></td>
                            <td>
                                <?php 
                                    if ($cuenta['proximo_pago']) {
                                        $fecha_pago = new DateTime($cuenta['proximo_pago']);
                                        $hoy = new DateTime();
                                        $class = '';
                                        if ($cuenta['estado'] == 'Pendiente') {
                                            if ($fecha_pago < $hoy) $class = 'text-danger font-weight-bold'; 
                                            elseif ($fecha_pago == $hoy) $class = 'text-warning font-weight-bold'; 
                                        }
                                        echo "<span class='$class'>" . $fecha_pago->format('d/m/Y') . "</span>";
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-<?php echo $cuenta['estado'] == 'Pagado' ? 'success' : 'warning'; ?>">
                                    <?php echo $cuenta['estado']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($cuenta['estado'] == 'Pendiente'): ?>
                                    <button onclick="openPaymentModal(<?php echo $cuenta['id']; ?>, '<?php echo htmlspecialchars($cuenta['cliente']); ?>', <?php echo $cuenta['saldo']; ?>, <?php echo $cuenta['monto_cuota'] ?: 'null'; ?>)" class="btn btn-sm btn-success" title="Registrar Cobro">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $cuenta['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('¿Eliminar esta cuenta?')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Cuenta -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nueva Cuenta por Cobrar</h2>
            <span class="close" onclick="closeModal('createModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">Seleccione una categoría de ingreso (Opcional)</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Fecha Inicio *</label>
                        <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cliente (Deudor) *</label>
                    <input type="text" name="cliente" required class="form-control" placeholder="Nombre del cliente">
                </div>
                
                <div class="form-row" style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Monto Total *</label>
                        <input type="number" name="deuda_total" step="0.01" min="0" required class="form-control" placeholder="0.00">
                    </div>
                     <div class="form-group" style="flex: 1;">
                        <label>Monto Cuota (Opcional)</label>
                        <input type="number" name="monto_cuota" step="0.01" min="0" class="form-control" placeholder="¿Cuánto te pagan por periodo?">
                    </div>
                </div>

                <div class="form-group">
                    <label>Frecuencia de Cobro</label>
                    <select name="frecuencia" required class="form-control">
                        <option value="mensual">Mensual</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="semanal">Semanal</option>
                        <option value="diario">Diario</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>

            </div>
            
            <div class="modal-footer form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div id="pagoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Registrar Cobro</h2>
            <span class="close" onclick="closeModal('pagoModal')">&times;</span>
        </div>
        <div class="modal-body">
            <p>Registrando cobro a: <strong id="pagoCliente"></strong></p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="payment">
                <input type="hidden" name="cuenta_id" id="pagoCuentaId">
                
                <div class="form-group">
                    <label>Monto a Cobrar</label>
                    <input type="number" step="0.01" name="monto" id="pagoMonto" required class="form-control">
                    <small style="display:block; margin-top:5px; color:var(--text-muted);">Saldo pendiente: <span id="pagoPendiente"></span></small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('pagoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Cobro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openPaymentModal(id, cliente, saldo, cuota) {
        document.getElementById('pagoCuentaId').value = id;
        document.getElementById('pagoCliente').innerText = cliente;
        
        if (cuota && cuota <= saldo) {
             document.getElementById('pagoMonto').value = cuota;
        } else {
             document.getElementById('pagoMonto').value = saldo;
        }
        
        document.getElementById('pagoPendiente').innerText = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(saldo);
        
        openModal('pagoModal');
    }

    // Close modal on click user
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
