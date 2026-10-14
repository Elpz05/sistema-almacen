<?php
include 'auth.php'; // Verifica sesión
include 'db.php';   // Conexión a la BD

// Desactivamos errores de texto para que no rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos los datos del formulario
    $id_prod = $_POST['id_producto'];
    $id_emp  = $_POST['id_empresa'];
    $cant    = $_POST['cantidad'];
    $tipo    = $_POST['tipo_movimiento']; // Recibe 'Entrada' o 'Salida'
    $importe = $_POST['num_importe'];
    $id_emp_session = $_SESSION['user_id'];

    // Validar que los campos no vengan vacíos
    if (empty($id_prod) || empty($id_emp) || $cant <= 0) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos o cantidad inválida"]);
        exit;
    }

    // Iniciar transacción para seguridad de datos
    $conn->begin_transaction();

    try {
        // 1. Insertar el movimiento (Asegúrate de incluir la columna 'fecha')
        // Usamos NOW() para que la base de datos asigne la hora exacta automáticamente
        $stmt = $conn->prepare("INSERT INTO movimientos (id_producto, id_empleado, id_empresa, tipo_movimiento, cantidad, num_importe, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiisis", $id_prod, $id_emp_session, $id_emp, $tipo, $cant, $importe);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al registrar movimiento: " . $stmt->error);
        }

        // 2. Actualizar stock en la tabla productos
        if ($tipo == 'Entrada') {
            $update = $conn->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?");
        } else {
            // Verificar stock antes de permitir una Salida
            $res = $conn->query("SELECT stock_actual FROM productos WHERE id = $id_prod");
            $p = $res->fetch_assoc();
            
            if (!$p || $p['stock_actual'] < $cant) {
                throw new Exception("Stock insuficiente. Disponible: " . ($p['stock_actual'] ?? 0));
            }
            $update = $conn->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
        }
        
        $update->bind_param("ii", $cant, $id_prod);
        
        if (!$update->execute()) {
            throw new Exception("Error al actualizar el inventario.");
        }

        // Si todo salió bien, guardamos permanentemente
        $conn->commit();
        echo json_encode(["status" => "success"]);

    } catch (Exception $e) {
        // Si algo falló, deshacemos todos los cambios
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}