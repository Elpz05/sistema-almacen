<?php
include 'db.php';
header('Content-Type: application/json');

// Recibimos las variables del filtro
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$tipo = $_GET['tipo'] ?? 'Todos';

// Agregamos m.num_importe a la selección
$sql = "SELECT m.fecha, m.tipo_movimiento, m.cantidad, m.num_importe,
               p.nombre as nombre_producto, 
               emp.nombre as nombre_empleado, 
               e.nombre_empresa 
        FROM movimientos m
        LEFT JOIN productos p ON m.id_producto = p.id
        LEFT JOIN empleados emp ON m.id_empleado = emp.id
        LEFT JOIN empresas e ON m.id_empresa = e.id
        WHERE 1=1"; 

// Filtro por fecha inicial
if (!empty($desde)) {
    $sql .= " AND DATE(m.fecha) >= '$desde'";
}

// Filtro por fecha final
if (!empty($hasta)) {
    $sql .= " AND DATE(m.fecha) <= '$hasta'";
}

// Filtro por tipo de movimiento (Entrada/Salida)
if ($tipo !== 'Todos') {
    $sql .= " AND m.tipo_movimiento = '$tipo'";
}

// Ordenamos de lo más reciente a lo más antiguo
$sql .= " ORDER BY m.fecha DESC";

$result = $conn->query($sql);
$data = [];

while($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>