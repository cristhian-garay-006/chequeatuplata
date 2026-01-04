<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

// Si no está logueado, redirigir al login
if (!isLoggedIn()) {
    redirect('login.php');
}

// Redirigir al dashboard
redirect('public/dashboard.php');
?>



    

     



