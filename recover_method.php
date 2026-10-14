<?php
include 'db.php';
header('Content-Type: application/json');

// Desactivar errores para no ensuciar el JSON
error_reporting(0);
ini_set('display_errors', 0);

$accion = $_GET['accion'] ?? '';
$correo = $_GET['correo'] ?? '';

// PASO 1: Obtener la pregunta asignada a ese correo
if ($accion == 'get_pregunta') {
    if (empty($correo)) {
        echo json_encode(["status" => "error", "message" => "Ingrese un correo válido"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT pregunta_seguridad FROM empleados WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        if (!empty($user['pregunta_seguridad'])) {
            echo json_encode(["status" => "success", "pregunta" => $user['pregunta_seguridad']]);
        } else {
            echo json_encode(["status" => "error", "message" => "Este usuario no tiene configurada una pregunta de seguridad. Contacte al administrador."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "El correo no está registrado"]);
    }
}

// PASO 2: Verificar si la respuesta es correcta
if ($accion == 'verificar_resp') {
    $resp = $_GET['resp'] ?? '';

    $stmt = $conn->prepare("SELECT id FROM empleados WHERE correo = ? AND respuesta_seguridad = ?");
    $stmt->bind_param("ss", $correo, $resp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Respuesta incorrecta"]);
    }
}

// PASO 3: Cambiar la contraseña finalmente
if ($accion == 'cambiar_pass') {
    $pass = $_GET['pass'] ?? '';

    if (strlen($pass) < 6) {
        echo json_encode(["status" => "error", "message" => "La contraseña debe tener al menos 6 caracteres"]);
        exit;
    }

    // Encriptamos la nueva contraseña
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE empleados SET password = ? WHERE correo = ?");
    $stmt->bind_param("ss", $hashed, $correo);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo actualizar la contraseña"]);
    }
}
exit;