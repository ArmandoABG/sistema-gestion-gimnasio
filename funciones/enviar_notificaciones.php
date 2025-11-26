<?php
// enviar_notificaciones.php - CON DEBUG EN PANTALLA
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar la ruta base del proyecto
$base_dir = 'C:/xampp/htdocs/Proyecto Final';

// Incluir archivos con rutas ABSOLUTAS
require_once $base_dir . '/funciones/miembros_funciones.php';

// CONFIGURACIÓN DE ZONA HORARIA
date_default_timezone_set('America/Mexico_City');

// Configurar rutas de logs
$log_dir = $base_dir . '/logs';
$log_file = $log_dir . '/notificaciones.log';

// Crear carpeta logs si no existe
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Función para loguear Y mostrar en pantalla
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    echo $log_entry; // ← ESTA LÍNEA MUESTRA EN PANTALLA
}

// Función para debug rápido
function debug($message) {
    echo "DEBUG: " . $message . "\n";
    error_log("DEBUG: " . $message);
}

// Iniciar proceso
debug("=== INICIANDO SCRIPT DE NOTIFICACIONES ===");
debug("Hora actual: " . date('Y-m-d H:i:s'));
debug("Día actual en inglés: " . date('l'));
debug("Día actual en español: " . date('l'));

logMessage("=== INICIANDO SCRIPT DE NOTIFICACIONES ===");

try {
    // Verificar conexión a BD
    if ($conn) {
        debug("Conexión a BD establecida");
        logMessage("Conexión a BD establecida");
        
        // Ejecutar las funciones de notificación
        debug("Ejecutando recordatorios de membresías...");
        logMessage("Ejecutando recordatorios de membresías...");
        $resultado_membresias = enviarRecordatoriosVencimientoMembresias($conn);
        
        if ($resultado_membresias['success']) {
            debug("Membresías - Enviados: " . $resultado_membresias['enviados'] . ", Errores: " . $resultado_membresias['errores']);
            logMessage("Membresías - Enviados: " . $resultado_membresias['enviados'] . ", Errores: " . $resultado_membresias['errores']);
        } else {
            debug("ERROR en membresías: " . $resultado_membresias['error']);
            logMessage("ERROR en membresías: " . $resultado_membresias['error']);
        }
        
        debug("Ejecutando recordatorios de clases...");
        logMessage("Ejecutando recordatorios de clases...");
        $resultado_clases = enviarRecordatoriosClasesDelDia($conn);
        
        if ($resultado_clases['success']) {
            debug("Clases - Enviados: " . $resultado_clases['enviados'] . ", Errores: " . $resultado_clases['errores']);
            debug("Clases - Total encontradas: " . $resultado_clases['total']);
            debug("Clases - Día buscado: " . $resultado_clases['dia']);
            logMessage("Clases - Enviados: " . $resultado_clases['enviados'] . ", Errores: " . $resultado_clases['errores']);
        } else {
            debug("ERROR en clases: " . $resultado_clases['error']);
            logMessage("ERROR en clases: " . $resultado_clases['error']);
        }
        
    } else {
        throw new Exception("No se pudo conectar a la BD");
    }
    
    debug("Proceso completado exitosamente");
    logMessage("Proceso completado exitosamente");
    
} catch (Exception $e) {
    debug("ERROR: " . $e->getMessage());
    logMessage("ERROR: " . $e->getMessage());
}

debug("=== FIN DEL SCRIPT ===");
logMessage("=== FIN DEL SCRIPT ===");
echo "Proceso completado. Revisa el archivo de log: $log_file\n";
?>