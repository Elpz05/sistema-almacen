<?php
$host = "localhost";
$user = "u589634571_josue"; // Cambiar según tu configuración
$pass = "2*v*^SpZlA>";     // Cambiar según tu configuración
$db   = "u589634571_almacen";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a utf8 para evitar problemas con acentos
$conn->set_charset("utf8");
?>