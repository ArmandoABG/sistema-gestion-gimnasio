<?php
// 1. INICIAR EL BUFFER DE SALIDA INMEDIATAMENTE
// Esto "graba" todo lo que se imprima (errores, espacios) para poder borrarlo después.
ob_start();

session_start();

// Configuración de errores (para log, no para pantalla)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Variable para la respuesta final
$response = ['success' => false, 'error' => 'Error desconocido'];

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Incluir archivos
    // NOTA: Si conexion.php tiene un espacio antes de <?php, ob_start() lo atrapará.
    if (!file_exists('../inc/conexion.php')) throw new Exception('Falta conexion.php');
    if (!file_exists('../funciones/membresias_funciones.php')) throw new Exception('Falta membresias_funciones.php');
    
    require_once '../inc/conexion.php';
    require_once '../funciones/membresias_funciones.php';

    // Obtener datos
    $id_miembro = isset($_POST['id_miembro']) ? intval($_POST['id_miembro']) : 0;
    $id_membresia = isset($_POST['id_membresia_nueva']) ? intval($_POST['id_membresia_nueva']) : 0;
    $monto_pagado = isset($_POST['monto_pagado']) ? floatval($_POST['monto_pagado']) : 0.0;
    $id_usuario = $_SESSION['id_usuario'] ?? 1;

    // Validar
    if ($id_miembro <= 0 || $id_membresia <= 0 || $monto_pagado <= 0) {
        throw new Exception('Datos incompletos o inválidos.');
    }

    // Ejecutar lógica
    $response = renovarMembresia($conn, $id_miembro, $id_membresia, $monto_pagado, $id_usuario);

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// 2. LIMPIAR EL BUFFER
// Aquí borramos cualquier HTML, Warning o espacio en blanco que se haya generado arriba.
ob_end_clean();

// 3. ENVIAR JSON LIMPIO
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>