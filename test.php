<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $result = $conn->query("SELECT * FROM productos WHERE id = $id");
            echo json_encode($result->fetch_assoc());
        } else {
            $result = $conn->query("SELECT * FROM productos ORDER BY id DESC");
            $productos = [];
            while($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
            echo json_encode($productos);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $nombre = $conn->real_escape_string($data['nombre']);
        $precio = floatval($data['precio']);
        $stock = intval($data['stock']);
        $stock_minimo = intval($data['stock_minimo']);
        
        $sql = "INSERT INTO productos (nombre, precio, stock, stock_minimo) VALUES ('$nombre', $precio, $stock, $stock_minimo)";
        
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id, 'message' => 'Producto agregado']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id']);
        $nombre = $conn->real_escape_string($data['nombre']);
        $precio = floatval($data['precio']);
        $stock = intval($data['stock']);
        $stock_minimo = intval($data['stock_minimo']);
        
        $sql = "UPDATE productos SET nombre='$nombre', precio=$precio, stock=$stock, stock_minimo=$stock_minimo WHERE id=$id";
        
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'DELETE':
        $id = intval($_GET['id']);
        $sql = "DELETE FROM productos WHERE id = $id";
        
        if($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
}

$conn->close();
?>