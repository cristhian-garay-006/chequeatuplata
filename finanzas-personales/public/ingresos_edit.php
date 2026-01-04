<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/Ingreso.php';
require_once __DIR__ . '/../models/FlujoCaja.php';
require_once __DIR__ . '/../config/database.php';

$usuario_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: transacciones.php?error=ID Invalido');
    exit;
}

$ingresoModel = new Ingreso();
$flujoCajaModel = new FlujoCaja();

// Obtener datos actuales
$transaccion = $ingresoModel->getById($id, $usuario_id);

if (!$transaccion) {
    header('Location: transacciones.php?error=Transacción no encontrada');
    exit;
}

$message = '';
$error = '';

// Procesar Actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $mes = date('M', strtotime($fecha));
    $cliente = $_POST['cliente'];
    $categoria_id = $_POST['categoria_id'];
    $total = $_POST['total'];
    $descripcion = $_POST['descripcion'];

    $data = [
        'fecha' => $fecha,
        'mes' => $mes,
        'cliente' => $cliente,
        'categoria_id' => $categoria_id,
        'total' => $total,
        'descripcion' => $descripcion
    ];

    if ($ingresoModel->update($id, $usuario_id, $data)) {
        // Recalcular flujo (podría ser mes diferente si cambió la fecha, 
        // idealmente recalcular tanto el antiguo como el nuevo, 
        // por simplicidad recalculamos el actual y el original)
        $anio = date('Y', strtotime($fecha));
        $flujoCajaModel->calculateFlujo($usuario_id, $anio, $mes);
        
        // Si cambio de mes, recalcular el original tambien
        if ($mes !== $transaccion['mes'] || date('Y', strtotime($fecha)) !== date('Y', strtotime($transaccion['fecha']))) {
             $flujoCajaModel->calculateFlujo($usuario_id, date('Y', strtotime($transaccion['fecha'])), $transaccion['mes']);
        }

        header('Location: transacciones.php?message=Ingreso actualizado correctamente');
        exit;
    } else {
        $error = "Error al actualizar la transacción.";
    }
}

// Obtener Categorías
$database = new Database();
$conn = $database->getConnection();
$stmtI = $conn->prepare("SELECT * FROM categorias_ingreso WHERE usuario_id = ? AND activo = 1");
$stmtI->execute([$usuario_id]);
$categorias = $stmtI->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Editar Ingreso';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1>Editar Ingreso</h1>
    <a href="transacciones.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="dashboard-grid">
    <div class="dashboard-section" style="max-width: 600px; margin: 0 auto; width: 100%;">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="form-card">
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="fecha" value="<?php echo $transaccion['fecha']; ?>" required class="form-control">
            </div>

            <div class="form-group">
                <label>Categoría</label>
                <select name="categoria_id" required class="form-control">
                    <option value="">Seleccione Categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $transaccion['categoria_id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cliente</label>
                <input type="text" name="cliente" value="<?php echo htmlspecialchars($transaccion['cliente']); ?>" placeholder="Nombre del cliente" class="form-control">
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <input type="text" name="descripcion" value="<?php echo htmlspecialchars($transaccion['descripcion']); ?>" placeholder="Descripción opcional" class="form-control">
            </div>

            <div class="form-group">
                <label>Monto</label>
                <input type="number" step="0.01" min="0" name="total" value="<?php echo $transaccion['total']; ?>" required class="form-control" style="font-size: 1.2em; font-weight: bold; color: var(--income-color);">
            </div>

            <div class="form-actions" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
