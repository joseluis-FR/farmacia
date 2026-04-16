<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tipo = $_GET['tipo'] ?? 'factura';
    $serie = $_GET['serie'] ?? 'F001';
    
    $sql = "SELECT MAX(CAST(SUBSTRING(numero, 1, 8) AS UNSIGNED)) as ultimo FROM facturas WHERE serie = '$serie' AND tipo = '$tipo'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $nuevo = str_pad(($row['ultimo'] ?? 0) + 1, 8, '0', STR_PAD_LEFT);
    
    echo json_encode(['success' => true, 'serie' => $serie, 'numero' => $nuevo, 'completo' => "$serie-$nuevo"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $tipo = $data['tipo'];
    $serie = $data['serie'];
    $numero = $data['numero'];
    $cliente_documento = $data['cliente']['documento'] ?? '';
    $cliente_nombre = $data['cliente']['nombre'] ?? '';
    $cliente_direccion = $data['cliente']['direccion'] ?? '';
    $cliente_email = $data['cliente']['email'] ?? '';
    $cliente_telefono = $data['cliente']['telefono'] ?? '';
    $subtotal = $data['subtotal'];
    $igv = $data['igv'];
    $total = $data['total'];
    $items = json_encode($data['items']);
    $metodo_pago = $data['metodo_pago'];
    $vendedor = $data['vendedor'];
    $fecha = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO facturas (tipo, serie, numero, cliente_documento, cliente_nombre, cliente_direccion, cliente_email, cliente_telefono, subtotal, igv, total, items, metodo_pago, vendedor, fecha) 
            VALUES ('$tipo', '$serie', '$numero', '$cliente_documento', '$cliente_nombre', '$cliente_direccion', '$cliente_email', '$cliente_telefono', $subtotal, $igv, $total, '$items', '$metodo_pago', '$vendedor', '$fecha')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'message' => 'Factura generada']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}

$conn->close();
?>