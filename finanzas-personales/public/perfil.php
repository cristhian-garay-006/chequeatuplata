<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../models/Usuario.php';

$usuarioModel = new Usuario();
$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validacion simple
    if (empty($nombre) || empty($email)) {
        $mensaje = "Nombre y Email son obligatorios";
        $tipo_mensaje = "error";
    } elseif ($password && $password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden";
        $tipo_mensaje = "error";
    } else {
        // Intentar actualizar
        if ($usuarioModel->update($user_id, $nombre, $email, $password)) {
            $mensaje = "Perfil actualizado correctamente";
            $tipo_mensaje = "success";
            
            // Actualizar sesión
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_email'] = $email;
        } else {
            $mensaje = "Error al actualizar perfil";
            $tipo_mensaje = "error";
        }
    }
}

// Obtener datos actuales del usuario
$usuario = $usuarioModel->getById($user_id);

$page_title = 'Mi Perfil';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="page-header">
    <h1>Mi Perfil</h1>
</div>

<?php if($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid" style="grid-template-columns: 1fr 2fr; align-items: start;">
    
    <!-- Tarjeta de Perfil -->
    <div class="dashboard-section text-center">
        <div style="width: 120px; height: 120px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 3em; color: white; box-shadow: var(--shadow-md);">
            <i class="fas fa-user"></i>
        </div>
        <h2 style="margin-bottom: 5px;"><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
        <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($usuario['email']); ?></p>
        
        <div style="margin-top: 20px; text-align: left; background: var(--bg-primary); padding: 15px; border-radius: 12px; font-size: 0.9em;">
            <p><strong>Miembro desde:</strong> 2024</p>
            <p><strong>Estado:</strong> <span class="text-success">Activo</span></p>
        </div>
    </div>

    <!-- Formulario de Edición -->
    <div class="dashboard-section">
        <h3>Editar Información</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Nombre Completo</label>
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                    <input type="text" name="nombre" class="form-control" style="padding-left: 40px;" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Correo Electrónico</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 15px; color: var(--text-muted);"></i>
                    <input type="email" name="email" class="form-control" style="padding-left: 40px;" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                </div>
            </div>

            <div style="margin: 30px 0; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <h4 style="margin-bottom: 20px; color: var(--text-secondary);"><i class="fas fa-lock"></i> Cambiar Contraseña</h4>
                <p class="text-muted" style="font-size: 0.9em; margin-bottom: 20px;">Deja los campos en blanco si no deseas cambiar tu contraseña.</p>
                
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>

</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
