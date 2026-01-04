<?php
// Configuración general del sistema
define('BASE_URL', 'http://localhost/finanzas-personales/');

define('APP_NAME', 'Sistema de Finanzas Personales');
define('APP_VERSION', '1.0.0');

// Zona horaria
date_default_timezone_set('America/Lima');

// Moneda
define('CURRENCY', 'S/');

// Seguridad
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

// Autoload de clases
spl_autoload_register(function ($class_name) {
    $paths = [
        __DIR__ . '/../models/' . $class_name . '.php',
        __DIR__ . '/../controllers/' . $class_name . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Funciones auxiliares
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatMoney($amount) {
    return CURRENCY . ' ' . number_format($amount, 2);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}
?>