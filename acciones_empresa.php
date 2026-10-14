<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include 'db.php';

$accion = $_GET['accion'] ?? '';

// LEER
if ($accion == 'leer') {
    $result = $conn->query("SELECT * FROM empresas");
    $data = [];
    while($row = $result->fetch_assoc()) { $data[] = $row; }
    echo json_encode($data);
}

// GUARDAR (CREAR / ACTUALIZAR)
if ($accion == 'guardar') {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre_empresa'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    if (empty($id)) {
        // Create
        $stmt = $conn->prepare("INSERT INTO empresas (nombre_empresa, direccion, telefono) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $direccion, $telefono);
    } else {
        // Update
        $stmt = $conn->prepare("UPDATE empresas SET nombre_empresa=?, direccion=?, telefono=? WHERE id=?");
        $stmt->bind_param("sssi", $nombre, $direccion, $telefono, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// ELIMINAR
if ($accion == 'eliminar') {
    $id = $_POST['id'] ?? '';
    
    if (!empty($id)) {
        try {
            $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar la empresa."]);
            }
            $stmt->close();
            
        } catch (mysqli_sql_exception $e) {
            // El código 1451 significa que hay registros ligados a esta empresa
            if ($e->getCode() == 1451) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "No puedes eliminar este proveedor/cliente porque tiene historial de entradas o salidas en el sistema'."
                ]);
            } else {
                // Cualquier otro error de SQL
                echo json_encode([
                    "status" => "error", 
                    "message" => "Error de base de datos: " . $e->getMessage()
                ]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID de empresa no válido."]);
    }
    exit;
}
?>