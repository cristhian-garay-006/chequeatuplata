<?php
session_start();
echo "Usuario ID en sesión: " . ($_SESSION['user_id'] ?? 'No definido') . "\n";
echo "Nombre de usuario: " . ($_SESSION['nombre'] ?? 'No definido') . "\n";
?>