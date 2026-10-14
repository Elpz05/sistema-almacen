<?php
// Desactivar cualquier error que ensucie la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);

include 'db.php';
header('Content-Type: application/json');

$accion = $_GET['accion'] ?? '';

// 1. Estadísticas para las tarjetas superiores
if ($accion == 'stats') {
    // Total de productos en inventario (suma de stock_actual)
    $resStock = $conn->query("SELECT SUM(stock_actual) as total FROM productos");
    $stock = $resStock->fetch_assoc();

    // Entradas registradas hoy
    $resEntradas = $conn->query("SELECT SUM(cantidad) as total FROM movimientos WHERE tipo_movimiento = 'Entrada' AND DATE(fecha) = CURDATE()");
    $entradas = $resEntradas->fetch_assoc();

    // Salidas registradas hoy
    $resSalidas = $conn->query("SELECT SUM(cantidad) as total FROM movimientos WHERE tipo_movimiento = 'Salida' AND DATE(fecha) = CURDATE()");
    $salidas = $resSalidas->fetch_assoc();

    echo json_encode([
        "stock" => $stock['total'] ?? 0,
        "entradas" => $entradas['total'] ?? 0,
        "salidas" => $salidas['total'] ?? 0
    ]);
    exit;
}

// 2. Historial para la tabla
if ($accion == 'historial') {
    $sql = "SELECT m.fecha, p.nombre as producto, m.tipo_movimiento, m.cantidad, em.nombre_empresa, m.num_importe 
            FROM movimientos m
            INNER JOIN productos p ON m.id_producto = p.id
            INNER JOIN empresas em ON m.id_empresa = em.id
            ORDER BY m.fecha DESC LIMIT 10";
            
    $result = $conn->query($sql);
    $movimientos = [];
    
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $movimientos[] = $row;
        }
    }
    
    echo json_encode($movimientos);
    exit;
}
?>