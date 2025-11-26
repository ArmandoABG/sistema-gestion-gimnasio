<?php
session_start();
require_once "../inc/conexion.php";
require_once "../funciones/miembros_funciones.php";

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Obtener acción
$accion = $_POST['accion'] ?? '';

if ($accion === 'ver_gafete') {
    // Ver gafete (generar y mostrar)
    $id_miembro = $_POST['id_miembro'] ?? 0;
    
    if (!$id_miembro) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de miembro no válido']);
        exit;
    }
    
    try {
        // Obtener datos del miembro
        $query = "SELECT nombre, apellido, codigo_qr FROM miembros WHERE id_miembro = $1";
        $result = pg_query_params($conn, $query, [$id_miembro]);
        
        if (!$result || pg_num_rows($result) === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Miembro no encontrado']);
            exit;
        }
        
        $miembro = pg_fetch_assoc($result);
        $nombre = $miembro['nombre'];
        $apellido = $miembro['apellido'];
        $codigo_qr = $miembro['codigo_qr'];
        
        // Generar gafete
        $gafete_file = generarGafeteIdentico($codigo_qr, $nombre, $apellido);
        
        if ($gafete_file && file_exists($gafete_file)) {
            // Enviar la imagen como respuesta
            header('Content-Type: image/png');
            header('Content-Disposition: inline; filename="gafete_miembro.png"');
            readfile($gafete_file);
            
            // Limpiar archivo temporal
            unlink($gafete_file);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al generar el gafete']);
            exit;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        exit;
    }
    
} elseif ($accion === 'reenviar_gafete') {
    // Reenviar gafete por correo
    $id_miembro = $_POST['id_miembro'] ?? 0;
    
    if (!$id_miembro) {
        echo json_encode(['success' => false, 'error' => 'ID de miembro no válido']);
        exit;
    }
    
    try {
        // Obtener datos del miembro
        $query = "SELECT m.nombre, m.apellido, m.correo, m.codigo_qr, 
                         mm.fecha_fin, mem.nombre as nombre_membresia
                  FROM miembros m
                  LEFT JOIN miembros_membresias mm ON m.id_miembro = mm.id_miembro AND mm.estado = 'activo'
                  LEFT JOIN membresias mem ON mm.id_membresia = mem.id_membresia
                  WHERE m.id_miembro = $1";
        $result = pg_query_params($conn, $query, [$id_miembro]);
        
        if (!$result || pg_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'error' => 'Miembro no encontrado']);
            exit;
        }
        
        $miembro = pg_fetch_assoc($result);
        $nombre = $miembro['nombre'];
        $apellido = $miembro['apellido'];
        $correo = $miembro['correo']; // Puede ser NULL
        $codigo_qr = $miembro['codigo_qr'];
        $fecha_fin = $miembro['fecha_fin'];
        $nombre_membresia = $miembro['nombre_membresia'];
        
        // ✅ PASO 6: Verificar si hay correo (incluyendo NULL y vacío)
        if (empty($correo)) {
            echo json_encode(['success' => false, 'error' => 'El miembro no tiene correo registrado']);
            exit;
        }
        
        // Generar gafete
        $gafete_file = generarGafeteIdentico($codigo_qr, $nombre, $apellido);
        
        if (!$gafete_file || !file_exists($gafete_file)) {
            echo json_encode(['success' => false, 'error' => 'Error al generar el gafete']);
            exit;
        }
        
        // Enviar correo
        require_once '../libs/PHPMailer/src/PHPMailer.php';
        require_once '../libs/PHPMailer/src/SMTP.php';
        require_once '../libs/PHPMailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'gymportezuelo585@gmail.com';
            $mail->Password   = 'pesr akmw dlhl zqrh';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('gymportezuelo585@gmail.com', 'Portezuelo Gym');
            $mail->addAddress($correo, $nombre . ' ' . $apellido);
            
            // Adjuntar gafete
            $mail->addAttachment($gafete_file, 'gafete_miembro.png');

            $mail->isHTML(true);
            $mail->Subject = 'Tu Gafete de Miembro - Portezuelo Gym';
            $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #003366;'>Gafete de Miembro - Portezuelo Gym</h2>
                
                <p>Hola <strong>$nombre $apellido</strong>,</p>
                
                <p>Te reenviamos tu gafete oficial de miembro.</p>";
            
            if ($fecha_fin) {
                $mail->Body .= "<p>Tu membresía <strong>$nombre_membresia</strong> está vigente hasta el <strong>$fecha_fin</strong>.</p>";
            }
            
            $mail->Body .= "
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #003366; margin: 20px 0;'>
                    <h3 style='color: #003366; margin-top: 0;'>Tu Gafete de Miembro</h3>
                    <p>Hemos adjuntado tu gafete oficial que incluye:</p>
                    <ul>
                        <li>Tu código QR único para acceso</li>
                        <li>Tu información personal</li>
                        <li>Fecha de vencimiento</li>
                    </ul>
                    <p><strong>Importante:</strong> Imprime este gafete y preséntalo al ingresar al gimnasio.</p>
                </div>
                
                <p>Tu código de acceso es: <strong style='font-size: 18px;'>$codigo_qr</strong></p>
                
                <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                
                <hr style='border: none; border-top: 2px solid #eee; margin: 30px 0;'>
                
                <p style='color: #666; font-size: 14px;'>
                    Atentamente,<br>
                    <strong>Equipo Portezuelo Gym</strong><br>
                </p>
            </div>";

            $mail->send();
            
            // Limpiar archivo temporal
            unlink($gafete_file);
            
            echo json_encode([
                'success' => true, 
                'mensaje' => "Gafete reenviado exitosamente a $correo"
            ]);
            
        } catch (Exception $e) {
            // Limpiar archivo temporal en caso de error
            if (file_exists($gafete_file)) {
                unlink($gafete_file);
            }
            
            echo json_encode([
                'success' => false, 
                'error' => 'Error al enviar el correo: ' . $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    }
    
}  elseif ($accion === 'verificar_correo') {
    // Verificar si el miembro tiene correo
    $id_miembro = $_POST['id_miembro'] ?? 0;
    
    if (!$id_miembro) {
        echo json_encode(['tiene_correo' => false]);
        exit;
    }
    
    try {
        $query = "SELECT correo FROM miembros WHERE id_miembro = $1";
        $result = pg_query_params($conn, $query, [$id_miembro]);
        
        if (!$result || pg_num_rows($result) === 0) {
            echo json_encode(['tiene_correo' => false]);
            exit;
        }
        
        $miembro = pg_fetch_assoc($result);
        // Verificar si el correo no es NULL y no está vacío
        $tiene_correo = ($miembro['correo'] !== null && trim($miembro['correo']) !== '');
        
        echo json_encode([
            'tiene_correo' => $tiene_correo,
            'correo' => $tiene_correo ? $miembro['correo'] : null
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['tiene_correo' => false, 'error' => $e->getMessage()]);
    }
}else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>