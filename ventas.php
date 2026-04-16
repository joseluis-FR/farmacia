<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, nombre, stock, stock_minimo 
            FROM productos 
            WHERE stock <= stock_minimo 
            ORDER BY stock ASC";
    
    $result = $conn->query($sql);
    $productos = [];
    
    while($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    echo json_encode($productos);
}

$conn->close();
?>