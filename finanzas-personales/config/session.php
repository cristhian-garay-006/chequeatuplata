<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function setUserSession($user_id, $nombre, $email) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['email'] = $email;
    $_SESSION['last_activity'] = time();
}

function destroySession() {
    session_unset();
    session_destroy();
}

function checkSessionTimeout($timeout = 3600) { // 1 hora
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        destroySession();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}
?>