<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/Ingreso.php';

$ingresoModel = new Ingreso();
$usuario_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Crear ingreso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $data = [
        'usuario_id' => $usuario_id,
        'fecha' => $_POST['fecha'],
        'mes' => date('M', strtotime($_POST['fecha'])),
        'cliente' => '',
        'categoria_id' => $_POST['categoria_id'] ?: null,
        'total' => $_POST['total'],
        'descripcion' => $_POST['descripcion']
    ];
    
    if ($ingresoModel->create($data)) {
        $message = 'Ingreso creado exitosamente';
        
        require_once __DIR__ . '/../models/FlujoCaja.php';
        $flujoCaja = new FlujoCaja();
        $flujoCaja->calculateFlujo($usuario_id, date('Y', strtotime($_POST['fecha'])), date('M', strtotime($_POST['fecha'])));
    } else {
        $error = 'Error al crear el ingreso';
    }
}

// Eliminar ingreso
if (isset($_GET['delete'])) {
    if ($ingresoModel->delete($_GET['delete'], $usuario_id)) {
        $message = 'Ingreso eliminado exitosamente';
    } else {
        $error = 'Error al eliminar el ingreso';
    }
}

// Filtros
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$mes = isset($_GET['mes']) ? $_GET['mes'] : null;

// Obtener ingresos
$ingresos = $ingresoModel->getAll($usuario_id, $anio, $mes);

// Obtener categorías
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare("SELECT * FROM categorias_ingreso WHERE usuario_id = ? AND activo = 1");
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll();

$page_title = 'Ingresos';
include __DIR__ . '/../views/layouts/header.php';
?>



<div class="page-header">
    <h1>Gestión de Ingresos</h1>
    <button class="btn btn-primary" onclick="openModal('createModal')">
        <i class="fas fa-plus"></i> Nuevo Ingreso
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="filters-section">
    <form method="GET" action="" class="filter-form">
        <select name="anio" onchange="this.form.submit()">
            <option value="">Todos los años</option>
            <?php for($y = 2020; $y <= date('Y') + 1; $y++): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $anio ? 'selected' : ''; ?>>
                    <?php echo $y; ?>
                </option>
            <?php endfor; ?>
        </select>
        
        <select name="mes" onchange="this.form.submit()">
            <option value="">Todos los meses</option>
            <?php 
            $meses = ['Ene'=>'Enero','Feb'=>'Febrero','Mar'=>'Marzo','Abr'=>'Abril','May'=>'Mayo',
                      'Jun'=>'Junio','Jul'=>'Julio','Ago'=>'Agosto','Sep'=>'Septiembre','Oct'=>'Octubre',
                      'Nov'=>'Noviembre','Dic'=>'Diciembre'];
            foreach($meses as $key => $nombre): ?>
                <option value="<?php echo $key; ?>" <?php echo $key == $mes ? 'selected' : ''; ?>>
                    <?php echo $nombre; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Tabla -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Mes</th>
                <th>Cliente</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th class="text-right">Total</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($ingresos)): ?>
            <tr>
                <td colspan="7" class="text-center">No hay ingresos registrados</td>
            </tr>
        <?php else: ?>
            <?php foreach($ingresos as $ingreso): ?>
            <tr>
                <td data-label="Fecha"><?php echo date('d/m/Y', strtotime($ingreso['fecha'])); ?></td>
                <td data-label="Mes"><?php echo $ingreso['mes']; ?></td>
                <td data-label="Cliente"><?php echo htmlspecialchars($ingreso['cliente'] ?? '-'); ?></td>
                <td data-label="Categoría"><?php echo htmlspecialchars($ingreso['categoria_nombre'] ?? '-'); ?></td>
                <td data-label="Descripción"><?php echo htmlspecialchars($ingreso['descripcion'] ?? '-'); ?></td>
                <td data-label="Total" class="text-right font-weight-bold"><?php echo formatMoney($ingreso['total']); ?></td>
                <td data-label="Acciones" class="text-center">
                    <a href="ingresos_edit.php?id=<?php echo $ingreso['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?delete=<?php echo $ingreso['id']; ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('¿Eliminar este ingreso?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong><?php echo formatMoney(array_sum(array_column($ingresos, 'total'))); ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nuevo Ingreso</h2>
            <span class="close" onclick="closeModal('createModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="fecha">Fecha *</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            

            
            <div class="form-group">
                <label for="categoria_id">Categoría</label>
                <select id="categoria_id" name="categoria_id">
                    <option value="">Seleccionar...</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="total">Total *</label>
                <input type="number" id="total" name="total" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>

            <!-- Placeholder Comprobante -->
            <div class="form-group">
                <label>Comprobante / Recibo</label>
                <div style="border: 2px dashed var(--border); padding: 10px; text-align: center; border-radius: 8px; background: var(--bg-primary);">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 1.5em; color: var(--muted); margin-bottom: 5px;"></i>
                    <p style="margin:0; font-size: 0.8em; color: var(--text-secondary);">Adjuntar recibo</p>
                    <input type="file" disabled style="display:none">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                <button type="submit" name="create" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
