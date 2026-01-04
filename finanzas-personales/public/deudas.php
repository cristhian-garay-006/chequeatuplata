<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../controllers/DeudaController.php';

$deudaController = new DeudaController();
$usuario_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_alerta = '';

$categorias = $deudaController->getCategorias($usuario_id);

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $data = [
                'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                'acreedor' => $_POST['acreedor'],
                'descripcion' => $_POST['descripcion'],
                'monto_total' => $_POST['monto_total'],
                'monto_cuota' => $_POST['monto_cuota'],
                'fecha_inicio' => $_POST['fecha_inicio'],
                'frecuencia' => $_POST['frecuencia']
            ];
            
            if ($deudaController->create($usuario_id, $data)) {
                $mensaje = "Deuda registrada exitosamente.";
                $tipo_alerta = "success";
            } else {
                $mensaje = "Error al registrar la deuda.";
                $tipo_alerta = "error";
            }
        } elseif ($_POST['action'] == 'payment') {
            $deuda_id = $_POST['deuda_id'];
            $monto = $_POST['monto'];
            
            if ($deudaController->registerPayment($deuda_id, $usuario_id, $monto)) {
                $mensaje = "Pago registrado exitosamente.";
                $tipo_alerta = "success";
            } else {
                $mensaje = "Error al registrar el pago.";
                $tipo_alerta = "error";
            }
        } elseif ($_POST['action'] == 'delete') {
             if ($deudaController->delete($_POST['deuda_id'], $usuario_id)) {
                $mensaje = "Deuda eliminada.";
                $tipo_alerta = "success";
             } else {
                $mensaje = "Error al eliminar.";
                $tipo_alerta = "error";
             }
        }
    }
}

$deudas = $deudaController->getAll($usuario_id);

include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-hand-holding-usd"></i> Mis Deudas por Pagar</h1>
    <button onclick="openModal('nuevaDeudaModal')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nueva Deuda
    </button>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_alerta; ?>">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="content-wrapper">
    <div class="dashboard-section">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Acreedor/Descripción</th>
                        <th>Monto Total</th>
                        <th>Cuota</th>
                        <th>Pendiente</th>
                        <th>Frecuencia</th>
                        <th>Próximo Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deudas)): ?>
                        <tr><td colspan="9" class="text-center">No tienes deudas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach($deudas as $deuda): ?>
                            <tr>
                                <td>
                                    <?php 
                                        // Find category name
                                        $catName = '-';
                                        foreach($categorias as $c) {
                                            if($c['id'] == $deuda['categoria_id']) {
                                                $catName = $c['nombre'];
                                                break;
                                            }
                                        }
                                        echo htmlspecialchars($catName);
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($deuda['acreedor']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($deuda['descripcion']); ?></small>
                                </td>
                                <td><?php echo formatMoney($deuda['monto_total']); ?></td>
                                <td><?php echo $deuda['monto_cuota'] ? formatMoney($deuda['monto_cuota']) : '-'; ?></td>
                                <td style="font-weight: 700; color: var(--expense-color);"><?php echo formatMoney($deuda['monto_pendiente']); ?></td>
                                <td><?php echo ucfirst($deuda['frecuencia']); ?></td>
                                <td>
                                    <?php 
                                        $fecha_pago = new DateTime($deuda['proximo_pago']);
                                        $hoy = new DateTime();
                                        $class = '';
                                        if ($deuda['estado'] == 'pendiente') {
                                            if ($fecha_pago < $hoy) $class = 'text-danger font-weight-bold'; // Vencido
                                            elseif ($fecha_pago == $hoy) $class = 'text-warning font-weight-bold'; // Hoy
                                        }
                                        echo "<span class='$class'>" . $fecha_pago->format('d/m/Y') . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 15px; font-size: 0.85em; background: <?php echo $deuda['estado'] == 'pagado' ? '#dcfce7; color: #16a34a' : '#fee2e2; color: #dc2626'; ?>">
                                        <?php echo ucfirst($deuda['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($deuda['estado'] == 'pendiente'): ?>
                                        <button onclick="openPaymentModal(<?php echo $deuda['id']; ?>, '<?php echo htmlspecialchars($deuda['acreedor']); ?>', <?php echo $deuda['monto_pendiente']; ?>, <?php echo $deuda['monto_cuota'] ?: 'null'; ?>)" class="btn btn-sm btn-success" title="Registrar Pago">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                    <?php endif; ?>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar esta deuda?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="deuda_id" value="<?php echo $deuda['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Deuda -->
<div id="nuevaDeudaModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Registrar Nueva Deuda</h2>
            <span class="close" onclick="closeModal('nuevaDeudaModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">Seleccione una categoría de gasto (Opcional)</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Acreedor (¿A quién le debes?)</label>
                    <input type="text" name="acreedor" required placeholder="Ej. Banco, Tarjeta, Amigo...">
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" placeholder="Ej. Compra de Laptop">
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Monto Total Deuda</label>
                        <input type="number" step="0.01" name="monto_total" required>
                    </div>
                     <div class="form-group" style="flex:1;">
                        <label>Monto Cuota (Opcional)</label>
                        <input type="number" step="0.01" name="monto_cuota" placeholder="¿Cuánto pagas por periodo?">
                    </div>
                </div>

                <div class="form-group">
                    <label>Fecha Inicio (o Primer Pago)</label>
                    <input type="date" name="fecha_inicio" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Frecuencia de Pago</label>
                    <select name="frecuencia" required>
                        <option value="mensual">Mensual</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="semanal">Semanal</option>
                        <option value="diario">Diario</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('nuevaDeudaModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Deuda</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div id="pagoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Registrar Pago de Cuota</h2>
            <span class="close" onclick="closeModal('pagoModal')">&times;</span>
        </div>
        <div class="modal-body">
            <p>Registrando pago para: <strong id="pagoAcreedor"></strong></p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="payment">
                <input type="hidden" name="deuda_id" id="pagoDeudaId">
                
                <div class="form-group">
                    <label>Monto a Pagar</label>
                    <input type="number" step="0.01" name="monto" id="pagoMonto" required>
                    <small style="display:block; margin-top:5px; color:var(--text-muted);">Pendiente actual: <span id="pagoPendiente"></span></small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal('pagoModal')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Pago</button>
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

    function openPaymentModal(id, acreedor, pendiente, cuota) {
        document.getElementById('pagoDeudaId').value = id;
        document.getElementById('pagoAcreedor').innerText = acreedor;
        
        // Si hay cuota definida, sugerirla. Si no, sugerir el pendiente (o dejar vacío, pero pendiente es seguro)
        // Preferimos sugerir la cuota si existe y es menor o igual al pendiente.
        if (cuota && cuota <= pendiente) {
             document.getElementById('pagoMonto').value = cuota;
        } else {
             document.getElementById('pagoMonto').value = pendiente;
        }
       
        document.getElementById('pagoPendiente').innerText = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(pendiente);
        
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
