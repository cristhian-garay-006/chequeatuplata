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

$message = '';
$error = '';

// Procesar Formulario en Lote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_batch'])) {
    
    // Validar tipo global
    $transactionType = $_POST['transaction_type'] ?? 'ingreso';
    $items = $_POST['items'] ?? [];
    $count = 0;
    $errors = 0;
    
    if (!empty($items)) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $conn->beginTransaction();

            foreach ($items as $item) {
                // Datos comunes
                $fecha = $item['fecha'];
                $mes = date('M', strtotime($fecha));
                $anio = date('Y', strtotime($fecha));
                $total = $item['total'];
                $descripcion = $item['descripcion'];
                $categoria_id = !empty($item['categoria_id']) ? $item['categoria_id'] : null;

                if ($transactionType === 'ingreso') {
                    $cliente = $item['extra_field'] ?? ''; // Cliente para ingresos
                    $data = [
                        'usuario_id' => $usuario_id,
                        'fecha' => $fecha,
                        'mes' => $mes,
                        'cliente' => $cliente,
                        'categoria_id' => $categoria_id,
                        'total' => $total,
                        'descripcion' => $descripcion
                    ];
                    if ($ingresoModel->create($data)) {
                        $flujoCajaModel->calculateFlujo($usuario_id, $anio, $mes);
                        $count++;
                    } else {
                        $errors++;
                    }
                } else {
                    $tipo_egreso = $item['extra_field'] ?? 'Gastos'; // Tipo para egresos (campo texto)
                    $data = [
                        'usuario_id' => $usuario_id,
                        'fecha' => $fecha,
                        'mes' => $mes,
                        'tipo' => $tipo_egreso,
                        'categoria_id' => $categoria_id,
                        'total' => $total,
                        'descripcion' => $descripcion
                    ];
                    // Nota: Egreso::create usa bindParam o execute array, asumimos compatibilidad con array
                    // Revisando Egreso.php, usa execute($data). Asegurar claves coinciden.
                    if ($egresoModel->create($data)) {
                        $flujoCajaModel->calculateFlujo($usuario_id, $anio, $mes);
                        $count++;
                    } else {
                        $errors++;
                    }
                }
            }
            
            $conn->commit();
            $message = "Se registraron $count transacciones exitosamente.";
            if ($errors > 0) $error = "Hubo $errors errores al registrar.";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Eliminar Transacción
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $id = $_GET['delete'];
    $type = $_GET['type'];
    
    if ($type === 'ingreso') {
        if ($ingresoModel->delete($id, $usuario_id)) {
            $message = 'Ingreso eliminado.';
        } else {
            $error = 'Error al eliminar ingreso.';
        }
    } else {
        if ($egresoModel->delete($id, $usuario_id)) {
            $message = 'Egreso eliminado.';
        } else {
            $error = 'Error al eliminar egreso.';
        }
    }
}

// Filtros
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y'); // Si está vacío, es 'Todos'. Si no start, es date('Y')
$mes = isset($_GET['mes']) ? $_GET['mes'] : '';
$dia = isset($_GET['dia']) ? $_GET['dia'] : '';

// Casting para uso seguro, pero permitiendo 0/vacio para 'Todos'
$filtro_anio = ($anio !== '') ? (int)$anio : null;
$filtro_mes = ($mes !== '') ? $mes : null;
$filtro_dia = ($dia !== '') ? (int)$dia : null;

// Obtener Datos
$ingresos = $ingresoModel->getAll($usuario_id, $filtro_anio, $filtro_mes, $filtro_dia);
$egresos = $egresoModel->getAll($usuario_id, $filtro_anio, $filtro_mes, $filtro_dia);

// Fusionar y Ordenar
$transacciones = [];
foreach ($ingresos as $i) {
    $i['type'] = 'ingreso';
    $transacciones[] = $i;
}
foreach ($egresos as $e) {
    $e['type'] = 'egreso';
    $transacciones[] = $e;
}

// Ordenar por fecha DESC
usort($transacciones, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Obtener Categorías para JS
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$conn = $database->getConnection();

$stmtI = $conn->prepare("SELECT * FROM categorias_ingreso WHERE usuario_id = ? AND activo = 1");
$stmtI->execute([$usuario_id]);
$catIngreso = $stmtI->fetchAll(PDO::FETCH_ASSOC);

$stmtE = $conn->prepare("SELECT * FROM categorias_egreso WHERE usuario_id = ? AND activo = 1");
$stmtE->execute([$usuario_id]);
$catEgreso = $stmtE->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Transacciones';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1>Gestión de Transacciones</h1>
    <div>
        <button class="btn btn-secondary" onclick="openModal('historyModal')">
            <i class="fas fa-list"></i> Ver Historial
        </button>
        <button class="btn btn-primary" onclick="openBatchModal()">
            <i class="fas fa-plus"></i> Nueva Transacción
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Empty State / Dashboard Placeholder (Opcional, para que no se vea vacío) -->
<div class="dashboard-grid">
    <div class="dashboard-section summary-card" style="background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); color: white;">
        <div>
            <h3 style="margin: 0; opacity: 0.9;">Total Transacciones (Año Actual)</h3>
            <p class="amount-large" style="margin: 5px 0 0 0;"><?php echo count($transacciones); ?></p>
        </div>
        <i class="fas fa-exchange-alt" style="font-size: 3em; opacity: 0.8;"></i>
    </div>
</div>


<!-- Modal Historial -->
<div id="historyModal" class="modal">
    <div class="modal-content modal-lg" style="max-width: 95%; height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h2>Historial de Transacciones</h2>
            <span class="close" onclick="closeModal('historyModal')">&times;</span>
        </div>
        
        <div class="modal-body" style="overflow-y: auto; flex: 1;">
            
            <!-- Filtros Modernos -->
            <div class="filters-section" style="background: var(--card-bg); padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 20px;">
                <form method="GET" action="" id="filterForm" class="filter-form" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                    
                    <!-- Trigger para auto-abrir modal al recargar -->
                    <input type="hidden" name="show_history" value="1">

                    <!-- Hidden params for backend -->
                    <input type="hidden" name="anio" id="h_anio" value="<?php echo $anio; ?>">
                    <input type="hidden" name="mes" id="h_mes" value="<?php echo $mes ?? ''; ?>">
                    <input type="hidden" name="dia" id="h_dia" value="<?php echo $dia ?? ''; ?>">

                    <!-- Selector de Modo -->
                    <div class="filter-mode-group" style="display: flex; gap: 5px; background: var(--bg-primary); padding: 5px; border-radius: 8px;">
                        <label class="btn btn-sm" style="margin:0; border:none; cursor:pointer;" id="lbl_anio">
                            <input type="radio" name="filter_mode" value="anio" style="display:none" onchange="switchMode('anio')"> 
                            Año
                        </label>
                        <label class="btn btn-sm" style="margin:0; border:none; cursor:pointer;" id="lbl_mes">
                            <input type="radio" name="filter_mode" value="mes" style="display:none" onchange="switchMode('mes')"> 
                            Mes
                        </label>
                        <label class="btn btn-sm" style="margin:0; border:none; cursor:pointer;" id="lbl_dia">
                            <input type="radio" name="filter_mode" value="dia" style="display:none" onchange="switchMode('dia')"> 
                            Día
                        </label>
                        <label class="btn btn-sm" style="margin:0; border:none; cursor:pointer;" id="lbl_todos">
                            <input type="radio" name="filter_mode" value="todos" style="display:none" onchange="switchMode('todos')"> 
                            Todos
                        </label>
                    </div>

                    <!-- Inputs dinámicos -->
                    <div id="input_container" style="display: flex; align-items: center;">
                        
                        <!-- Input Año -->
                        <div id="wrapper_anio" class="filter-wrapper" style="display:none;">
                            <input type="number" id="inp_anio" class="form-control-inline" min="2020" max="2030" placeholder="Año" 
                                   value="<?php echo $anio; ?>" onchange="applyFilter()">
                        </div>

                        <!-- Input Mes (YYYY-MM) -->
                        <div id="wrapper_mes" class="filter-wrapper" style="display:none;">
                            <!-- Usamos type month nativo -->
                            <input type="month" id="inp_mes" class="form-control-inline" onchange="applyFilter()">
                        </div>

                        <!-- Input Dia (YYYY-MM-DD) -->
                        <div id="wrapper_dia" class="filter-wrapper" style="display:none;">
                            <input type="date" id="inp_dia" class="form-control-inline" onchange="applyFilter()">
                        </div>

                    </div>

                    <!-- Botón Limpiar condicional -->
                    <?php if($dia || $mes || $anio != date('Y')): ?>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    <?php endif; ?>

                </form>
            </div>

            <script>
            // Mapeo de meses Numérico a Código (Debe coincidir con la BD/Config)
            const monthMap = {
                '01': 'Ene', '02': 'Feb', '03': 'Mar', '04': 'Abr', 
                '05': 'May', '06': 'Jun', '07': 'Jul', '08': 'Ago', 
                '09': 'Sep', '10': 'Oct', '11': 'Nov', '12': 'Dic'
            };
            // Inverso para llenar inputs
            const monthMapReverse = Object.fromEntries(Object.entries(monthMap).map(a => a.reverse()));

            // Estado inicial PHP
            const phpAnio = "<?php echo $anio; ?>";
            const phpMes = "<?php echo $mes; ?>";
            const phpDia = "<?php echo $dia; ?>";

            function initFilters() {
                let mode = 'todos';
                
                // Auto-detectar modo
                if (phpDia) mode = 'dia';
                else if (phpMes) mode = 'mes';
                else if (phpAnio && phpAnio != "<?php echo date('Y'); ?>") mode = 'anio'; 
                else if (phpAnio) mode = 'anio'; // Default a anio si solo hay anio

                // Marcar radio
                document.querySelector(`input[name="filter_mode"][value="${mode}"]`).checked = true;
                switchMode(mode, false); // false = no submit init

                // Pre-llenar valores
                if (mode === 'anio') {
                    document.getElementById('inp_anio').value = phpAnio;
                } 
                else if (mode === 'mes') {
                    // Convertir "2024" + "Ene" a "2024-01"
                    if(phpAnio && phpMes) {
                        let numMes = Object.keys(monthMap).find(key => monthMap[key] === phpMes);
                        if(numMes) document.getElementById('inp_mes').value = `${phpAnio}-${numMes}`;
                    }
                }
                else if (mode === 'dia') {
                    // Convertir "2024" + "Ene" + "15" a "2024-01-15" (Wait. backend stores "Ene" but date input needs "01")
                    if(phpAnio && phpMes && phpDia) {
                         let numMes = Object.keys(monthMap).find(key => monthMap[key] === phpMes);
                         // Pad day
                         let d = phpDia.toString().padStart(2, '0');
                         if(numMes) document.getElementById('inp_dia').value = `${phpAnio}-${numMes}-${d}`;
                    }
                }
            }

            function switchMode(mode, autoSubmit = true) {
                // Styles for active button
                document.querySelectorAll('.filter-mode-group .btn').forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-light'); // Assuming btn-light exists or just reset
                    b.style.background = 'transparent';
                    b.style.color = 'var(--text-primary)';
                });
                const activeLbl = document.getElementById('lbl_' + mode);
                if(activeLbl) {
                    activeLbl.style.background = 'var(--primary-color)';
                    activeLbl.style.color = '#fff';
                }

                // Hide all wrappers
                document.querySelectorAll('.filter-wrapper').forEach(w => w.style.display = 'none');

                // Show selected wrapper
                if (mode !== 'todos') {
                    document.getElementById('wrapper_' + mode).style.display = 'block';
                }

                if (autoSubmit && mode === 'todos') {
                    resetFilters();
                }
            }

            function applyFilter() {
                const mode = document.querySelector('input[name="filter_mode"]:checked').value;
                
                // Reset hidden values
                document.getElementById('h_anio').value = '';
                document.getElementById('h_mes').value = '';
                document.getElementById('h_dia').value = '';

                if (mode === 'anio') {
                    const val = document.getElementById('inp_anio').value;
                    if (val) document.getElementById('h_anio').value = val;
                }
                else if (mode === 'mes') {
                    const val = document.getElementById('inp_mes').value; // "2024-02"
                    if (val) {
                        const parts = val.split('-'); // [2024, 02]
                        document.getElementById('h_anio').value = parts[0];
                        document.getElementById('h_mes').value = monthMap[parts[1]] || ''; 
                    }
                }
                else if (mode === 'dia') {
                    const val = document.getElementById('inp_dia').value; // "2024-02-15"
                    if (val) {
                        const parts = val.split('-'); // [2024, 02, 15]
                        document.getElementById('h_anio').value = parts[0];
                        document.getElementById('h_mes').value = monthMap[parts[1]] || ''; 
                        document.getElementById('h_dia').value = parseInt(parts[2]); // strip leading zero
                    }
                }

                document.getElementById('filterForm').submit();
            }

            function resetFilters() {
                document.getElementById('h_anio').value = '';
                document.getElementById('h_mes').value = '';
                document.getElementById('h_dia').value = '';
                document.getElementById('filterForm').submit();
            }

            // Run init
            window.addEventListener('DOMContentLoaded', initFilters);
            </script>

            <!-- Tabla Unificada -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Extra</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transacciones)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay transacciones registradas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($transacciones as $t): ?>
                            <tr>
                                <td data-label="Fecha"><?php echo date('d/m/Y', strtotime($t['fecha'])); ?></td>
                                <td data-label="Tipo">
                                    <span class="badge badge-<?php echo $t['type'] == 'ingreso' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($t['type']); ?>
                                    </span>
                                </td>
                                <td data-label="Categoría"><?php echo htmlspecialchars($t['categoria_nombre'] ?? '-'); ?></td>
                                <td data-label="Descripción"><?php echo htmlspecialchars($t['descripcion'] ?? '-'); ?></td>
                                <td data-label="Extra">
                                    <?php if ($t['type'] == 'ingreso'): ?>
                                        <small class="text-muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($t['cliente']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($t['tipo']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Total" class="text-right font-weight-bold" style="color: <?php echo $t['type'] == 'ingreso' ? 'var(--income-color)' : 'var(--expense-color)'; ?>">
                                    <?php echo formatMoney($t['total']); ?>
                                </td>
                                <td data-label="Acciones" class="text-center">
                                    <!-- Editar -->
                                    <?php 
                                        $editUrl = $t['type'] == 'ingreso' ? 'ingresos_edit.php' : 'egresos_edit.php';
                                    ?>
                                    <a href="<?php echo $editUrl; ?>?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Eliminar (Podríamos usar AJAX o un form invisible para POST, pero GET link es lo existente) -->
                                    <?php 
                                        $deleteUrl = $t['type'] == 'ingreso' ? 'ingresos' : 'egresos';
                                    ?>
                                    <a href="<?php echo $deleteUrl; ?>.php?delete=<?php echo $t['id']; ?>&type=<?php echo $t['type']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('¿Eliminar esta transacción?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Fin Tabla -->

        </div> <!-- Fin Modal Body -->
    </div>
</div>

<?php 
// Auto-open logic if filter was submitted
if (isset($_GET['show_history'])) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { openModal('historyModal'); });</script>";
}
?>

<!-- Modal Batch -->
<div id="batchModal" class="modal">
    <div class="modal-content modal-lg"> <!-- modal-lg class for wider -->
        <div class="modal-header">
            <h2>Registrar Transacciones</h2>
            <span class="close" onclick="closeModal('batchModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <!-- Selector de Tipo Global -->
                <div class="transaction-type-selector">
                    <label class="type-option">
                        <input type="radio" name="transaction_type" value="ingreso" checked onchange="updateBatchUI()">
                        <div class="option-box option-ingreso">
                            <i class="fas fa-arrow-up"></i>
                            <span>Ingresos</span>
                        </div>
                    </label>
                    <label class="type-option">
                        <input type="radio" name="transaction_type" value="egreso" onchange="updateBatchUI()">
                        <div class="option-box option-egreso">
                            <i class="fas fa-arrow-down"></i>
                            <span>Egresos</span>
                        </div>
                    </label>
                </div>

                <div class="batch-rows-container" id="rowsContainer">
                    <!-- Rows injected by JS -->
                </div>
                
                <button type="button" class="btn btn-outline-primary btn-block btn-add-row" onclick="addBatchRow()">
                    <i class="fas fa-plus"></i> Agregar otra fila
                </button>
            </div>
            
            <div class="modal-footer">
                <div class="total-summary">
                    Total: <span id="batchTotal">0.00</span>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('batchModal')">Cancelar</button>
                    <button type="submit" name="create_batch" class="btn btn-primary">Guardar Todo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const catIngresos = <?php echo json_encode($catIngreso); ?>;
const catEgresos = <?php echo json_encode($catEgreso); ?>;
let rowCount = 0;

function openBatchModal() {
    document.getElementById('batchModal').classList.add('active');
    // Reiniciar si está vacío
    if (document.getElementById('rowsContainer').innerHTML === '') {
        updateBatchUI(); // This triggers render
    }
}

function updateBatchUI() {
    const type = document.querySelector('input[name="transaction_type"]:checked').value;
    const container = document.getElementById('rowsContainer');
    
    // Limpiar filas existentes para evitar inconsistencias de categorías
    container.innerHTML = '';
    
    // Agregar primera fila
    addBatchRow();
}

function addBatchRow() {
    const type = document.querySelector('input[name="transaction_type"]:checked').value;
    const container = document.getElementById('rowsContainer');
    const index = rowCount++;
    const cats = type === 'ingreso' ? catIngresos : catEgresos;
    const extraLabel = type === 'ingreso' ? '' : 'Tipo (Efec/Tarj)'; // No label for income
    const extraPlaceholder = type === 'ingreso' ? '' : 'Ej: Efectivo';
    const extraDisplay = type === 'ingreso' ? 'none' : 'block';
    
    let catOptions = '<option value="">Categoría</option>';
    cats.forEach(c => {
        catOptions += `<option value="${c.id}">${c.nombre}</option>`;
    });

    const rowHtml = `
        <div class="batch-row" id="row-${index}">
            <div class="row-inputs">
                <div class="input-group date-input">
                    <input type="date" name="items[${index}][fecha]" value="<?php echo date('Y-m-d'); ?>" required aria-label="Fecha">
                </div>
                <div class="input-group cat-input">
                    <select name="items[${index}][categoria_id]" required aria-label="Categoría">
                        ${catOptions}
                    </select>
                </div>
                <div class="input-group desc-input">
                    <input type="text" name="items[${index}][descripcion]" placeholder="Descripción" aria-label="Descripción">
                </div>
                <div class="input-group extra-input" style="display: ${extraDisplay};">
                    <input type="text" name="items[${index}][extra_field]" placeholder="${extraPlaceholder}" aria-label="${extraLabel}">
                </div>
                <!-- Placeholder Comprobante -->
                <div class="input-group file-input" style="flex: 0 0 40px; display: flex; align-items: center; justify-content: center;">
                    <label style="cursor: pointer; margin: 0;" title="Subir Comprobante (Demo)">
                        <i class="fas fa-camera" style="color: var(--primary-color); font-size: 1.2em;"></i>
                        <input type="file" disabled style="display: none;">
                    </label>
                </div>
                <div class="input-group amount-input">
                    <input type="number" step="0.01" min="0" name="items[${index}][total]" placeholder="0.00" required oninput="calculateTotal()" aria-label="Monto">
                </div>
            </div>
            <button type="button" class="btn-remove-row" onclick="removeRow(${index})" title="Eliminar fila">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', rowHtml);
}

function removeRow(index) {
    const row = document.getElementById(`row-${index}`);
    if (row) {
        row.remove();
        calculateTotal();
        // Si no quedan filas, agregar una
        if (document.getElementById('rowsContainer').children.length === 0) {
            addBatchRow();
        }
    }
}

function calculateTotal() {
    let total = 0;
    const inputs = document.querySelectorAll('input[name*="[total]"]');
    inputs.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('batchTotal').textContent = total.toFixed(2);
}

// Initial render handled by onload or click
</script>



<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
