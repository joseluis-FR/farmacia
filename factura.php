<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Incluir PHPMailer
require_once '../PHPMailer-master/src/PHPMailer.php';
require_once '../PHPMailer-master/src/SMTP.php';
require_once '../PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);
$emails = $data['emails'] ?? [];
$ticket_html = $data['ticket_html'] ?? '';

if (empty($emails)) {
    echo json_encode(['success' => false, 'message' => 'Ingrese al menos un correo']);
    exit();
}

if (empty($ticket_html)) {
    echo json_encode(['success' => false, 'message' => 'No hay ticket para enviar']);
    exit();
}

// Validar correos
$emails_validos = [];
foreach ($emails as $email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emails_validos[] = $email;
    }
}

if (empty($emails_validos)) {
    echo json_encode(['success' => false, 'message' => 'No hay correos válidos']);
    exit();
}

$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP de Gmail
    $mail->SMTPDebug = 0; // 0 = sin debug, 1 = errores, 2 = todo
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    // ========== CAMBIA ESTOS DATOS ==========
    $mail->Username   = 'docno.dorantes@gmail.com';  // Tu correo Gmail
    $mail->Password   = 'hubn zbag vlhx amce';  // La contraseña de 16 letras (sin espacios)
    // ========================================
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Configuración adicional para evitar problemas SSL
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Remitente
    $mail->setFrom($mail->Username, 'Farmacia Saludable');
    
    // Agregar destinatarios
    foreach ($emails_validos as $email) {
        $mail->addAddress($email);
    }
    
    // Responder a
    $mail->addReplyTo($mail->Username, 'Soporte');
    
    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = "Ticket de Compra - Farmacia Saludable";
    
    // Construir el HTML completo
    $html_completo = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .ticket { max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
            .header { text-align: center; border-bottom: 2px solid #2E7D32; padding-bottom: 10px; margin-bottom: 20px; }
            .header h2 { color: #2E7D32; margin: 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="ticket">
            <div class="header">
                <h2>🏥 FARMACIA SALUDABLE</h2>
                <p>¡Gracias por su compra!</p>
            </div>
            ' . $ticket_html . '
            <div class="footer">
                <p>Este es un ticket electrónico válido</p>
                <p>Fecha: ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    $mail->Body = $html_completo;
    $mail->AltBody = "Ticket de compra. Gracias por su compra.";
    
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Ticket enviado exitosamente a: ' . implode(', ', $emails_validos)
    ]);
    
} catch (Exception $e) {
    // Si falla el envío, guardar como archivo
    $carpeta = __DIR__ . '/../tickets/';
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    
    $archivo = 'ticket_' . date('Ymd_His') . '.html';
    file_put_contents($carpeta . $archivo, $ticket_html);
    
    echo json_encode([
        'success' => true,
        'message' => '⚠️ No se pudo enviar por correo. Error: ' . $mail->ErrorInfo . '\n\nTicket guardado en: tickets/' . $archivo
    ]);
}
?>