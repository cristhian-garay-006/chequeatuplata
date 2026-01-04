<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('public/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    
    if (isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $result = $authController->login($email, $password);
        
        if ($result['success']) {
            redirect('public/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <!-- Background Shapes -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    
    <div class="login-container">
        <!-- Left Side: Hero/Info -->
        <div class="login-hero">
            <div class="hero-content">
                <div class="brand-badge">
                    <i class="fas fa-chart-pie"></i> Finanzas Personales
                </div>
                <h1>Toma el control de tu futuro financiero</h1>
                <p>Gestiona ingresos, gastos y metas en una plataforma intuitiva y segura.</p>
                
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="f-icon"><i class="fas fa-check"></i></div>
                        <span>Reportes Detallados</span>
                    </div>
                    <div class="feature-item">
                        <div class="f-icon"><i class="fas fa-check"></i></div>
                        <span>Acceso 24/7</span>
                    </div>
                    <div class="feature-item">
                        <div class="f-icon"><i class="fas fa-check"></i></div>
                        <span>Seguridad Total</span>
                    </div>
                </div>
            </div>
            <div class="hero-footer">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-form-wrapper">
            <div class="login-box">
                <div class="login-header">
                    <img src="img/logo.png" alt="Logo" class="login-logo">
                    <h2>Bienvenido de nuevo</h2>
                    <p>Ingresa tus credenciales para continuar</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error animate-shake">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success animate-fade-in">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="input-group-modern">
                        <div class="icon-wrapper"><i class="fas fa-envelope"></i></div>
                        <div class="input-wrapper">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
                        </div>
                    </div>
                    
                    <div class="input-group-modern">
                        <div class="icon-wrapper"><i class="fas fa-lock"></i></div>
                        <div class="input-wrapper">
                            <label for="password">Contraseña</label>
                            <input type="password" id="password" name="password" required placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> Recordarme
                        </label>
                        <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit" name="login" class="btn-login-modern">
                        Ingresar <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="login-footer">
                    ¿No tienes cuenta? <a href="register.php">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>