<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

function esAdmin() {
    return $_SESSION['puesto'] === 'Admin';
}

function restringirAdmin() {
    if (!esAdmin()) {
        header("Location: dashboard.php?error=no_autorizado");
        exit();
    }
}
?>