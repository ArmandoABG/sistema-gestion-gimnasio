<?php

session_start();
require_once '../inc/conexion.php';
require_once '../funciones/miembros_funciones.php'; // Aquí está la función corregida cambiarMembresia

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el ID del usuario logeado para registrar quién realiza el upgrade
$id_usuario_logeado = $_SESSION['id_usuario'] ?? 0; 

if ($id_usuario_logeado === 0) {
    // Es crucial que el usuario esté logeado para realizar pagos/upgrades
    if (isset($_POST['accion'])) {
         header('Content-Type: application/json');
         echo json_encode(['success' => false, 'error' => 'Usuario no autenticado.']);
         exit();
    }
    // Si no es una llamada AJAX, redirige y es VITAL que esto NO esté comentado.
    header("Location: ../login.php");
    exit();
}


// -----------------------------------------------------
// Lógica para Cálculo (llamado por AJAX desde miembros.php)
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'calcular') {
    header('Content-Type: application/json');

    $id_miembro = $_POST['id_miembro'] ?? null;
    $id_nueva_membresia = $_POST['id_nueva_membresia'] ?? null;
    
    if (!$id_miembro || !$id_nueva_membresia) {
        echo json_encode(['success' => false, 'error' => 'Faltan IDs para el cálculo.']);
        exit();
    }
    
    // --- INICIO DE CORRECCIÓN: ELIMINAR TRANSACCIÓN INNECESARIA ---
    // NO se usa pg_query($conn, "BEGIN"); aquí, ya que es solo una lectura.
    // -------------------------------------------------------------

    try {
        // 1. Obtener la membresía ACTIVA actual, incluyendo el nombre
        $query_actual = "
             SELECT 
                 mm.fecha_fin, m.precio, m.duracion_dias, m.nombre AS nombre_actual_membresia
             FROM miembros_membresias mm
             JOIN membresias m ON mm.id_membresia = m.id_membresia
             WHERE mm.id_miembro = $1 AND mm.estado = 'activo' 
             ORDER BY mm.fecha_fin DESC LIMIT 1";
            
        // Se debe sanitizar el $id_miembro en caso de que no sea entero antes de pasar a pg_query_params
        // Asumiendo que viene sanitizado desde el HTML o se confía en la protección del PG.
        $result_actual = pg_query_params($conn, $query_actual, [$id_miembro]); 
        if (!pg_num_rows($result_actual)) throw new Exception("El miembro no tiene una membresía activa.");
        $actual = pg_fetch_assoc($result_actual);

        // 2. Obtener detalles de la NUEVA membresía
        // Necesitamos OBTENER AMBOS PRECIOS para hacer la comparación
        $query_nueva = "SELECT precio, nombre FROM membresias WHERE id_membresia = $1 AND estado='activo'";
        $result_nueva = pg_query_params($conn, $query_nueva, [$id_nueva_membresia]);
        if (!pg_num_rows($result_nueva)) throw new Exception("La nueva membresía no existe o está inactiva.");
        $nueva = pg_fetch_assoc($result_nueva);

        // OBTENEMOS LOS PRECIOS
        $precio_actual = floatval($actual['precio']);
        $precio_nuevo = floatval($nueva['precio']);

        // =========================================================
        // 🛑 LÓGICA DE RESTRICCIÓN DE DOWNGRADE
        // =========================================================
        if ($precio_nuevo < $precio_actual) {
            throw new Exception("No se permite el downgrade (cambiar de una membresía más cara a una más barata).");
        }
        // =========================================================


        // CÁLCULO DE CRÉDITO 
        $hoy = new DateTime();
        $fecha_fin_actual = new DateTime($actual['fecha_fin']);
        $interval = $hoy->diff($fecha_fin_actual);
        $dias_restantes = $fecha_fin_actual > $hoy ? $interval->days : 0;
        $dias_totales_actual = intval($actual['duracion_dias']);
        
        $credito = 0.0;
        if ($dias_restantes > 0 && $dias_totales_actual > 0) {
             $valor_por_dia = floatval($actual['precio']) / $dias_totales_actual;
             $credito = round($valor_por_dia * $dias_restantes, 2);
        }
        
        $precio_nuevo = floatval($nueva['precio']);
        $monto_a_pagar = max(0.0, $precio_nuevo - $credito);
        
        // --- FIN DE CORRECCIÓN: ELIMINAR TRANSACCIÓN INNECESARIA ---
        // NO se usa pg_query($conn, "ROLLBACK"); aquí.
        // -------------------------------------------------------------
        
        echo json_encode([
             'success' => true, 
             'monto_a_pagar' => $monto_a_pagar,
             'credito_aplicado' => $credito,
             'fecha_fin_actual' => $actual['fecha_fin'],
             'nombre_actual' => $actual['nombre_actual_membresia'] 
        ]);

    } catch (Exception $e) {
        // Si hay error en la conexión o la consulta, el script falla o la excepción es capturada.
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// -----------------------------------------------------
// Lógica para Finalizar Upgrade (llamado por el formulario)
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'finalizar_upgrade') {
    
    $id_miembro = $_POST['id_miembro'] ?? null;
    $id_nueva_membresia = $_POST['id_nueva_membresia'] ?? null;
    
    // La función cambiarMembresia DEBE estar corregida en miembros_funciones.php
    $resultado = cambiarMembresia(
        $conn, 
        $id_miembro, 
        $id_nueva_membresia, 
        $id_usuario_logeado
    );

    // Manejar el resultado y redirigir
    if ($resultado['success']) {
        // Redirige con un mensaje de éxito
        $_SESSION['mensaje'] = "Upgrade exitoso. Monto pagado: $" . number_format($resultado['monto_pagado'], 2) . ". Nueva fecha fin: " . date("d/m/Y", strtotime($resultado['fecha_fin']));
        header("Location: miembros.php");
    } else {
        // Redirige con un mensaje de error
        $_SESSION['error'] = "Error al realizar el Upgrade: " . $resultado['error'];
        header("Location: miembros.php");
    }
    exit();
}
// Si se accede sin POST, redirige a la lista
header("Location: miembros.php");
exit();
?>