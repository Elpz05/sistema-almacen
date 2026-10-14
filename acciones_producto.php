<?php
// Desactivar visualización de errores para evitar corromper el JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
include 'db.php';

$accion = $_GET['accion'] ?? '';

// 1. LEER PRODUCTOS (Con el nombre de la empresa incluido)
if ($accion == 'leer') {
    // Seleccionamos todo de productos (p.*) y el nombre de la empresa (e.nombre_empresa)
    $sql = "SELECT p.*, e.nombre_empresa 
            FROM productos p 
            LEFT JOIN empresas e ON p.id_empresa = e.id";
            
    $result = $conn->query($sql);
    $data = [];
    
    while($row = $result->fetch_assoc()) { 
        $data[] = $row; 
    }
    echo json_encode($data);
    exit;
}

// 2. GUARDAR (CREATE O UPDATE) - Tu código actual está bien aquí
if ($accion == 'guardar') {
    $id = $_POST['id'] ?? '';
    $codigo = $_POST['codigo_barras'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $tipo = $_POST['tipo_producto'] ?? '';
    $id_empresa = $_POST['id_empresa'] ?? null;
    $peso = $_POST['peso'] ?? 0;
    $precio = $_POST['precio'] ?? 0;
    $ancho = $_POST['ancho'] ?? 0;
    $altura = $_POST['altura'] ?? 0;

    if (empty($id)) {
        $stmt = $conn->prepare("INSERT INTO productos (codigo_barras, nombre, tipo_producto, peso, ancho, altura, id_empresa, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdddid", $codigo, $nombre, $tipo, $peso, $ancho, $altura, $id_empresa, $precio);
    } else {
        $stmt = $conn->prepare("UPDATE productos SET codigo_barras=?, nombre=?, tipo_producto=?, peso=?, ancho=?, altura=?, id_empresa=?, precio=? WHERE id=?");
        $stmt->bind_param("sssdddidi", $codigo, $nombre, $tipo, $peso, $ancho, $altura, $id_empresa, $precio, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// 3. ELIMINAR (DELETE)
if ($accion == 'eliminar') {
    $id = $_POST['id'] ?? '';
    
    if (!empty($id)) {
        try {
            $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar el producto."]);
            }
            $stmt->close();
            
        } catch (mysqli_sql_exception $e) {
            // El código de error 1451 en MySQL significa "Restricción de Llave Foránea"
            if ($e->getCode() == 1451) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "No puedes eliminar este producto porque ya tiene historial de Entradas o Salidas.'."
                ]);
            } else {
                // Para cualquier otro tipo de error SQL
                echo json_encode([
                    "status" => "error", 
                    "message" => "Error de base de datos: " . $e->getMessage()
                ]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID de producto no válido."]);
    }
    exit;

}
?>