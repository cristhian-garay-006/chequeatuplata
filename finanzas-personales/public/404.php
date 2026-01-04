
<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box" style="text-align: center;">
            <h1 style="font-size: 72px; color: var(--danger-color); margin-bottom: 20px;">404</h1>
            <h2>Página no encontrada</h2>
            <p style="color: var(--text-secondary); margin: 20px 0;">La página que buscas no existe o ha sido movida.</p>
            <a href="dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
        </div>
    </div>
</body>
</html>