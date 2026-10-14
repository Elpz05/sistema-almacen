<?php
include 'db.php';
header('Content-Type: application/json');

$accion = $_GET['accion'] ?? '';

// 1. LEER (Incluimos campos de seguridad)
if ($accion == 'leer') {
    $sql = "SELECT id, nombre, puesto, correo, telefono, pregunta_seguridad, respuesta_seguridad FROM empleados";
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) { $data[] = $row; }
    echo json_encode($data);
}

// 2. CREAR O ACTUALIZAR
if ($accion == 'guardar') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $puesto = $_POST['puesto'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $pregunta = $_POST['pregunta_seguridad'];
    $respuesta = $_POST['respuesta_seguridad'];

    if (empty($id)) {
        // CREAR NUEVO
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO empleados (nombre, puesto, correo, telefono, password, pregunta_seguridad, respuesta_seguridad) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $nombre, $puesto, $correo, $telefono, $pass, $pregunta, $respuesta);
    } else {
        // ACTUALIZAR (Si la contraseña viene vacía, no se cambia)
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE empleados SET nombre=?, puesto=?, correo=?, telefono=?, pregunta_seguridad=?, respuesta_seguridad=?, password=? WHERE id=?");
            $stmt->bind_param("sssssssi", $nombre, $puesto, $correo, $telefono, $pregunta, $respuesta, $pass, $id);
        } else {
            $stmt = $conn->prepare("UPDATE empleados SET nombre=?, puesto=?, correo=?, telefono=?, pregunta_seguridad=?, respuesta_seguridad=? WHERE id=?");
            $stmt->bind_param("ssssssi", $nombre, $puesto, $correo, $telefono, $pregunta, $respuesta, $id);
        }
    }
    
    echo json_encode(["status" => $stmt->execute() ? "success" : "error"]);
}

// 3. ELIMINAR
if ($accion == 'eliminar') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM empleados WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo json_encode(["status" => $stmt->execute() ? "success" : "error"]);
}
?>