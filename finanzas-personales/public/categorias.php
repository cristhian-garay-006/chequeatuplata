<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->getConnection();
$usuario_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Crear categoría de ingreso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ingreso'])) {
    $query = "INSERT INTO categorias_ingreso (usuario_id, nombre, descripcion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$usuario_id, $_POST['nombre'], $_POST['descripcion']])) {
        $message = 'Categoría de ingreso creada exitosamente';
    } else {
        $error = 'Error al crear la categoría';
    }
}

// Crear categoría de egreso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_egreso'])) {
    $query = "INSERT INTO categorias_egreso (usuario_id, nombre, tipo, descripcion) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$usuario_id, $_POST['nombre'], $_POST['tipo'], $_POST['descripcion']])) {
        $message = 'Categoría de egreso creada exitosamente';
    } else {
        $error = 'Error al crear la categoría';
    }
}

// Eliminar categoría de ingreso
if (isset($_GET['delete_ingreso'])) {
    $query = "DELETE FROM categorias_ingreso WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$_GET['delete_ingreso'], $usuario_id])) {
        $message = 'Categoría eliminada exitosamente';
    } else {
        $error = 'Error al eliminar la categoría';
    }
}

// Eliminar categoría de egreso
if (isset($_GET['delete_egreso'])) {
    $query = "DELETE FROM categorias_egreso WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$_GET['delete_egreso'], $usuario_id])) {
        $message = 'Categoría eliminada exitosamente';
    } else {
        $error = 'Error al eliminar la categoría';
    }
}

// Obtener categorías
$stmt_ingreso = $conn->prepare("SELECT * FROM categorias_ingreso WHERE usuario_id = ? ORDER BY nombre");
$stmt_ingreso->execute([$usuario_id]);
$categorias_ingreso = $stmt_ingreso->fetchAll();

$stmt_egreso = $conn->prepare("SELECT * FROM categorias_egreso WHERE usuario_id = ? ORDER BY nombre");
$stmt_egreso->execute([$usuario_id]);
$categorias_egreso = $stmt_egreso->fetchAll();

$page_title = 'Categorías';
include __DIR__ . '/../views/layouts/header.php';
?>


<div class="page-header">
    <h1>Gestión de Categorías</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>


<div class="dashboard-grid">
    <!-- Categorías de Ingreso -->
    <div class="dashboard-section" style="text-align: center; padding: 40px 20px;">
        <div style="background: rgba(16, 185, 129, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-wallet" style="font-size: 32px; color: var(--income-color);"></i>
        </div>
        <h3 style="border: none; margin-bottom: 10px;">Categorías de Ingreso</h3>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">Gestiona las fuentes de tus ingresos</p>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-primary" onclick="openModal('createIngresoModal')">
                <i class="fas fa-plus"></i> Nueva
            </button>
            <button class="btn btn-outline-primary" style="background: transparent; border: 2px solid var(--primary-color); color: var(--primary-color);" onclick="openModal('viewIngresosModal')">
                <i class="fas fa-list"></i> Ver Lista
            </button>
        </div>
    </div>

    <!-- Categorías de Egreso -->
    <div class="dashboard-section" style="text-align: center; padding: 40px 20px;">
        <div style="background: rgba(239, 68, 68, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-shopping-cart" style="font-size: 32px; color: var(--expense-color);"></i>
        </div>
        <h3 style="border: none; margin-bottom: 10px;">Categorías de Egreso</h3>
        <p style="color: var(--text-secondary); margin-bottom: 25px;">Administra tus gastos fijos y variables</p>
        
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-primary" onclick="openModal('createEgresoModal')">
                <i class="fas fa-plus"></i> Nueva
            </button>
            <button class="btn btn-outline-primary" style="background: transparent; border: 2px solid var(--primary-color); color: var(--primary-color);" onclick="openModal('viewEgresosModal')">
                <i class="fas fa-list"></i> Ver Lista
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Categorías de Ingreso -->
<div id="viewIngresosModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Categorías de Ingreso</h2>
            <span class="close" onclick="closeModal('viewIngresosModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias_ingreso)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No hay categorías registradas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($categorias_ingreso as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cat['descripcion'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <a href="?delete_ingreso=<?php echo $cat['id']; ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       style="padding: 5px 10px;"
                                       onclick="return confirm('¿Eliminar esta categoría?')">
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
        <div class="modal-footer form-actions" style="border-top: 1px solid var(--border-color); padding: 20px; margin-top: 0;">
             <button type="button" class="btn btn-secondary" onclick="closeModal('viewIngresosModal')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Ver Categorías de Egreso -->
<div id="viewEgresosModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Categorías de Egreso</h2>
            <span class="close" onclick="closeModal('viewEgresosModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias_egreso)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No hay categorías registradas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($categorias_egreso as $cat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $cat['tipo'] == 'fijo' ? 'info' : 'warning'; ?>" 
                                          style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; background: <?php echo $cat['tipo'] == 'fijo' ? 'var(--info-color)' : 'var(--warning-color)'; ?>; color: white;">
                                        <?php echo ucfirst($cat['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($cat['descripcion'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <a href="?delete_egreso=<?php echo $cat['id']; ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       style="padding: 5px 10px;"
                                       onclick="return confirm('¿Eliminar esta categoría?')">
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
          <div class="modal-footer form-actions" style="border-top: 1px solid var(--border-color); padding: 20px; margin-top: 0;">
             <button type="button" class="btn btn-secondary" onclick="closeModal('viewEgresosModal')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Crear Categoría de Ingreso -->
<div id="createIngresoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nueva Categoría de Ingreso</h2>
            <span class="close" onclick="closeModal('createIngresoModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre_ingreso">Nombre *</label>
                    <input type="text" id="nombre_ingreso" name="nombre" required class="form-control" placeholder="Ej: Salario, Ventas...">
                </div>
                
                <div class="form-group">
                    <label for="descripcion_ingreso">Descripción</label>
                    <textarea id="descripcion_ingreso" name="descripcion" rows="3" class="form-control" placeholder="Opcional"></textarea>
                </div>
                
                <!-- Placeholder Icono -->
                <div class="form-group">
                    <label>Icono / Imagen</label>
                    <div style="border: 2px dashed var(--border-color); padding: 15px; text-align: center; border-radius: 8px; background: var(--bg-primary);">
                        <i class="fas fa-image" style="font-size: 2em; color: var(--text-muted); margin-bottom: 10px;"></i>
                        <p style="margin:0; font-size: 0.9em; color: var(--text-secondary);">Arrastra una imagen o ver iconos</p>
                        <input type="file" disabled style="display:none"> <!-- Disabled demo -->
                    </div>
                </div>
            </div>
            
            <div class="modal-footer form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createIngresoModal')">Cancelar</button>
                <button type="submit" name="create_ingreso" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Crear Categoría de Egreso -->
<div id="createEgresoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nueva Categoría de Egreso</h2>
            <span class="close" onclick="closeModal('createEgresoModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre_egreso">Nombre *</label>
                    <input type="text" id="nombre_egreso" name="nombre" required class="form-control" placeholder="Ej: Alquiler, Comida...">
                </div>
                
                <div class="form-group">
                    <label for="tipo">Tipo *</label>
                    <select id="tipo" name="tipo" required class="form-control">
                        <option value="variable">Variable</option>
                        <option value="fijo">Fijo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="descripcion_egreso">Descripción</label>
                    <textarea id="descripcion_egreso" name="descripcion" rows="3" class="form-control" placeholder="Opcional"></textarea>
                </div>
                
                <!-- Placeholder Icono -->
                <div class="form-group">
                    <label>Icono / Imagen</label>
                    <div style="border: 2px dashed var(--border-color); padding: 15px; text-align: center; border-radius: 8px; background: var(--bg-primary);">
                        <i class="fas fa-image" style="font-size: 2em; color: var(--text-muted); margin-bottom: 10px;"></i>
                        <p style="margin:0; font-size: 0.9em; color: var(--text-secondary);">Arrastra una imagen o ver iconos</p>
                        <input type="file" disabled style="display:none"> <!-- Disabled demo -->
                    </div>
                </div>
            </div>
            
            <div class="modal-footer form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createEgresoModal')">Cancelar</button>
                <button type="submit" name="create_egreso" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>