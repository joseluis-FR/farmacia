<?php
require_once 'db.php';

function getDatosEjemplo($tipo) {
    if ($tipo === 'ventas_por_hora') {
        $datos = [];
        for ($i = 0; $i < 24; $i++) {
            $ventas = 0;
            if ($i >= 9 && $i <= 12) $ventas = rand(8, 15);
            elseif ($i >= 16 && $i <= 19) $ventas = rand(10, 20);
            elseif ($i >= 20 && $i <= 22) $ventas = rand(5, 12);
            else $ventas = rand(0, 3);
            $datos[] = ['hora' => $i, 'total_ventas' => $ventas, 'ingresos' => $ventas * rand(50, 200)];
        }
        return $datos;
    }
    
    if ($tipo === 'ventas_por_dia_semana') {
        $dias = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
        $datos = [];
        for ($i = 0; $i < 7; $i++) {
            $ventas = ($i == 5 || $i == 6) ? rand(15, 30) : (($i == 0) ? rand(5, 15) : rand(10, 20));
            $datos[] = ['dia' => $dias[$i], 'total_ventas' => $ventas, 'ingresos' => $ventas * rand(50, 200), 'unidades' => $ventas * rand(1, 3)];
        }
        return $datos;
    }
    
    if ($tipo === 'comparativa_mensual') {
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $mes_actual = date('n') - 1;
        $mes_anterior = $mes_actual - 1;
        return [
            'mes_actual' => ['nombre_mes' => $meses[$mes_actual], 'total_ventas' => rand(80, 150), 'ingresos' => rand(5000, 10000), 'unidades' => rand(200, 400)],
            'mes_anterior' => ['nombre_mes' => $meses[$mes_anterior], 'total_ventas' => rand(60, 120), 'ingresos' => rand(4000, 8000), 'unidades' => rand(150, 300)],
            'variacion' => ['porcentaje_ingresos' => rand(-15, 25)]
        ];
    }
    
    if ($tipo === 'productos_rentables') {
        $productos = ['Paracetamol', 'Ibuprofeno', 'Amoxicilina', 'Vitamina C', 'Jarabe', 'Aspirina', 'Omeprazol', 'Losartan'];
        $datos = [];
        foreach ($productos as $p) {
            $vendido = rand(20, 100);
            $datos[] = ['nombre' => $p, 'total_vendido' => $vendido, 'ganancia_estimada' => $vendido * rand(2, 8)];
        }
        return $datos;
    }
    
    if ($tipo === 'productos_rotacion_lenta') {
        $productos = ['Medicamento A', 'Medicamento B', 'Medicamento C', 'Medicamento D', 'Crema X'];
        $datos = [];
        foreach ($productos as $p) {
            $datos[] = ['nombre' => $p, 'stock' => rand(10, 30), 'total_vendido_30dias' => rand(0, 4)];
        }
        return $datos;
    }
    
    if ($tipo === 'pronostico_ventas') {
        $reales = [];
        for ($i = 29; $i >= 0; $i--) {
            $reales[] = ['fecha' => date('Y-m-d', strtotime("-$i days")), 'ingresos' => rand(200, 800)];
        }
        $pronostico = [];
        for ($i = 1; $i <= 7; $i++) {
            $pronostico[] = ['fecha' => date('Y-m-d', strtotime("+$i days")), 'pronostico' => rand(300, 700)];
        }
        return ['reales' => $reales, 'pronostico' => $pronostico];
    }
    
    if ($tipo === 'metricas_satisfaccion') {
        return [
            'total_ventas_mes' => rand(80, 150),
            'promedio_productos' => rand(2, 5),
            'ticket_promedio' => rand(50, 150),
            'vendedores_activos' => rand(2, 5),
            'tasa_conversion' => rand(85, 98)
        ];
    }
    
    if ($tipo === 'top_productos') {
        $productos = ['Paracetamol', 'Ibuprofeno', 'Amoxicilina', 'Vitamina C', 'Jarabe'];
        $datos = [];
        foreach ($productos as $p) {
            $datos[] = ['nombre' => $p, 'total_vendido' => rand(30, 150), 'total_ingresos' => rand(200, 1000)];
        }
        return $datos;
    }
    
    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Metodo no permitido']);
    exit;
}

$tipo = $_GET['tipo'] ?? 'producto_mas_vendido';

if ($tipo === 'producto_mas_vendido') {
    $sql = "SELECT p.id, p.nombre, p.precio, SUM(v.cantidad) as total_vendido, SUM(v.total) as total_ingresos
            FROM ventas v JOIN productos p ON v.producto_id = p.id 
            GROUP BY v.producto_id ORDER BY total_vendido DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['nombre' => 'Paracetamol', 'total_vendido' => 45, 'total_ingresos' => 247.50, 'precio' => 5.50]);
    }
} 
elseif ($tipo === 'top_productos') {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as total_vendido, SUM(v.total) as total_ingresos
            FROM ventas v JOIN productos p ON v.producto_id = p.id 
            GROUP BY v.producto_id ORDER BY total_vendido DESC LIMIT 10";
    $result = $conn->query($sql);
    $productos = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) $productos[] = $row;
        echo json_encode($productos);
    } else {
        echo json_encode(getDatosEjemplo('top_productos'));
    }
}
elseif ($tipo === 'ventas_por_hora') {
    $sql = "SELECT HOUR(fecha) as hora, COUNT(*) as total_ventas FROM ventas WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY HOUR(fecha) ORDER BY hora ASC";
    $result = $conn->query($sql);
    $datos = [];
    for ($i = 0; $i < 24; $i++) $datos[$i] = ['hora' => $i, 'total_ventas' => 0];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) $datos[(int)$row['hora']]['total_ventas'] = (int)$row['total_ventas'];
        echo json_encode(array_values($datos));
    } else {
        echo json_encode(getDatosEjemplo('ventas_por_hora'));
    }
}
elseif ($tipo === 'ventas_por_dia_semana') {
    $sql = "SELECT DAYOFWEEK(fecha) as dia, COUNT(*) as total_ventas, SUM(total) as ingresos FROM ventas WHERE fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP BY DAYOFWEEK(fecha)";
    $result = $conn->query($sql);
    $dias = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
    $datos = [];
    for ($i = 1; $i <= 7; $i++) $datos[$i] = ['dia' => $dias[$i-1], 'total_ventas' => 0, 'ingresos' => 0];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $datos[(int)$row['dia']]['total_ventas'] = (int)$row['total_ventas'];
            $datos[(int)$row['dia']]['ingresos'] = (float)$row['ingresos'];
        }
        echo json_encode(array_values($datos));
    } else {
        echo json_encode(getDatosEjemplo('ventas_por_dia_semana'));
    }
}
elseif ($tipo === 'comparativa_mensual') {
    $mes_actual = date('Y-m');
    $mes_anterior = date('Y-m', strtotime('-1 month'));
    $sql_actual = "SELECT COUNT(*) as ventas, SUM(total) as ingresos FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_actual'";
    $sql_anterior = "SELECT COUNT(*) as ventas, SUM(total) as ingresos FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_anterior'";
    $res_actual = $conn->query($sql_actual)->fetch_assoc();
    $res_anterior = $conn->query($sql_anterior)->fetch_assoc();
    
    if (($res_actual['ventas'] > 0) || ($res_anterior['ventas'] > 0)) {
        echo json_encode([
            'mes_actual' => ['nombre_mes' => date('F Y'), 'total_ventas' => (int)$res_actual['ventas'], 'ingresos' => (float)$res_actual['ingresos']],
            'mes_anterior' => ['nombre_mes' => date('F Y', strtotime('-1 month')), 'total_ventas' => (int)$res_anterior['ventas'], 'ingresos' => (float)$res_anterior['ingresos']],
            'variacion' => ['porcentaje_ingresos' => $res_anterior['ingresos'] > 0 ? round((($res_actual['ingresos'] - $res_anterior['ingresos']) / $res_anterior['ingresos']) * 100, 2) : 0]
        ]);
    } else {
        echo json_encode(getDatosEjemplo('comparativa_mensual'));
    }
}
elseif ($tipo === 'productos_rentables') {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as total_vendido, (SUM(v.total) * 0.4) as ganancia FROM ventas v JOIN productos p ON v.producto_id = p.id GROUP BY v.producto_id ORDER BY ganancia DESC LIMIT 10";
    $result = $conn->query($sql);
    $productos = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) $productos[] = $row;
        echo json_encode($productos);
    } else {
        echo json_encode(getDatosEjemplo('productos_rentables'));
    }
}
elseif ($tipo === 'productos_rotacion_lenta') {
    $sql = "SELECT p.nombre, p.stock, COALESCE(SUM(v.cantidad), 0) as vendidos FROM productos p LEFT JOIN ventas v ON p.id = v.producto_id AND v.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.id HAVING vendidos < 5 ORDER BY vendidos ASC LIMIT 10";
    $result = $conn->query($sql);
    $productos = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) $productos[] = $row;
        echo json_encode($productos);
    } else {
        echo json_encode(getDatosEjemplo('productos_rotacion_lenta'));
    }
}
elseif ($tipo === 'pronostico_ventas') {
    $sql = "SELECT DATE(fecha) as fecha, SUM(total) as ingresos FROM ventas WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(fecha) ORDER BY fecha ASC";
    $result = $conn->query($sql);
    $reales = [];
    if ($result && $result->num_rows >= 7) {
        while($row = $result->fetch_assoc()) $reales[] = ['fecha' => $row['fecha'], 'ingresos' => (float)$row['ingresos']];
        $promedio = array_sum(array_column($reales, 'ingresos')) / count($reales);
        $pronostico = [];
        for ($i = 1; $i <= 7; $i++) $pronostico[] = ['fecha' => date('Y-m-d', strtotime("+$i days")), 'pronostico' => round($promedio * (0.9 + ($i * 0.02)), 2)];
        echo json_encode(['reales' => $reales, 'pronostico' => $pronostico]);
    } else {
        echo json_encode(getDatosEjemplo('pronostico_ventas'));
    }
}
elseif ($tipo === 'metricas_satisfaccion') {
    $sql = "SELECT COUNT(*) as ventas, AVG(cantidad) as promedio, AVG(total) as ticket FROM ventas WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $result = $conn->query($sql)->fetch_assoc();
    if ($result && $result['ventas'] > 0) {
        echo json_encode([
            'total_ventas_mes' => (int)$result['ventas'],
            'promedio_productos' => round($result['promedio'], 1),
            'ticket_promedio' => round($result['ticket'], 2),
            'vendedores_activos' => 3,
            'tasa_conversion' => 92.5
        ]);
    } else {
        echo json_encode(getDatosEjemplo('metricas_satisfaccion'));
    }
}
elseif ($tipo === 'reporte_semanal') {
    $semana = $_GET['semana'] ?? date('Y-m-d', strtotime('-7 days'));
    $sql = "SELECT DATE(fecha) as fecha, COUNT(*) as ventas, SUM(cantidad) as unidades, SUM(total) as ingresos FROM ventas WHERE fecha >= '$semana' GROUP BY DATE(fecha) ORDER BY fecha DESC";
    $result = $conn->query($sql);
    $diario = [];
    while($row = $result->fetch_assoc()) $diario[] = $row;
    
    $sql2 = "SELECT p.nombre, SUM(v.cantidad) as vendido FROM ventas v JOIN productos p ON v.producto_id = p.id WHERE v.fecha >= '$semana' GROUP BY v.producto_id ORDER BY vendido DESC LIMIT 5";
    $result2 = $conn->query($sql2);
    $top = [];
    while($row = $result2->fetch_assoc()) $top[] = $row;
    
    echo json_encode([
        'resumen_diario' => $diario,
        'top_productos' => $top,
        'fecha_inicio' => $semana,
        'fecha_fin' => date('Y-m-d'),
        'numero_semana' => date('W', strtotime($semana)),
        'anio' => date('Y', strtotime($semana))
    ]);
}

$conn->close();
?>