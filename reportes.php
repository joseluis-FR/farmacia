<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT v.id, v.fecha, p.nombre as producto, v.cantidad, v.total 
            FROM ventas v 
            JOIN productos p ON v.producto_id = p.id 
            ORDER BY v.fecha DESC 
            LIMIT 100";
    
    $result = $conn->query($sql);
    $ventas = [];
    
    while($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
}

$conn->close();
?>