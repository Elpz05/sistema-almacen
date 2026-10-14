<?php
include 'auth.php'; // Para saber quién es el usuario logueado
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $user_id = $_SESSION['user_id'];

    // 1. Obtener la contraseña actual de la base de datos
    $stmt = $conn->prepare("SELECT password FROM empleados WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // 2. Verificar si la contraseña vieja coincide
    // Si usas password_hash() en el registro:
    if (password_verify($old_pass, $user['password'])) {
        
        // 3. Encriptar la nueva contraseña y actualizar
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE empleados SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_pass, $user_id);
        
        if ($update->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo actualizar en la BD"]);
        }
        
    } else {
        // La contraseña vieja no es correcta
        echo json_encode(["status" => "error", "message" => "La contraseña actual es incorrecta"]);
    }
    exit;
}