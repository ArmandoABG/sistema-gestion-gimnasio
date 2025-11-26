<?php
// Incluir el archivo de conexión, usando la ruta relativa correcta
date_default_timezone_set('America/Mexico_City');
require_once '../inc/conexion.php'; 

// Ya que conexion.php establece $conn, solo necesitamos verificarlo y usarlo en las funciones.
if (!isset($conn) || !$conn) {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Error crítico del sistema (DB).']));
    }
}

/**
 * Función genérica para ejecutar consultas y obtener resultados.
 */
function db_query($sql, $params = []) {
    global $conn;
    // Usamos pg_query_params para seguridad contra inyección SQL
    $result = pg_query_params($conn, $sql, $params); 
    if (!$result) {
        // Loggear el error de PostgreSQL para depuración, pero no exponerlo al cliente.
        error_log("PostgreSQL Error: " . pg_last_error($conn) . " | SQL: " . $sql);
        return null;
    }
    return $result;
}

// =========================================================================
// FUNCIONES DE LÓGICA DE NEGOCIO Y BASE DE DATOS (FINAL)
// =========================================================================

/**
 * Verifica si un miembro tiene alguna membresía activa (fecha_fin >= CURRENT_DATE)
 * y con estado 'activa'.
 * @param int $id_miembro El ID del miembro.
 * @return bool True si tiene membresía activa, False en caso contrario.
 */
function verificar_membresia_activa($id_miembro) {
    global $conn;
    $sql = "
        SELECT EXISTS (
            SELECT 1 
            FROM miembros_membresias 
            WHERE 
                id_miembro = $1 
                AND fecha_fin >= CURRENT_DATE 
                AND estado = 'activo' 
        ) AS activo;
    ";
    
    $result = db_query($sql, [$id_miembro]);
    
    return $result ? (pg_fetch_result($result, 0, 0) === 't') : false;
}

function contar_asistencias_hoy() {
    $sql = "SELECT COUNT(*) FROM asistencias WHERE fecha = CURRENT_DATE";
    $result = db_query($sql);
    return $result ? (int) pg_fetch_result($result, 0, 0) : 0;
}

function contar_asistencias_esta_semana() {
    $sql = "
        SELECT COUNT(*) 
        FROM asistencias 
        WHERE fecha >= date_trunc('week', CURRENT_DATE)::date
          AND fecha <= CURRENT_DATE
    ";
    $result = db_query($sql);
    return $result ? (int) pg_fetch_result($result, 0, 0) : 0;
}

function calcular_porcentaje_miembros_activos() {
    $sql_total = "SELECT COUNT(id_miembro) FROM miembros";
    $result_total = db_query($sql_total);
    $total_miembros = $result_total ? (int) pg_fetch_result($result_total, 0, 0) : 0;

    if ($total_miembros === 0) {
        return 0.0;
    }

    $sql_activos = "
        SELECT COUNT(DISTINCT id_miembro) 
        FROM asistencias 
        WHERE fecha >= CURRENT_DATE - INTERVAL '7 days'
    ";
    $result_activos = db_query($sql_activos);
    $miembros_activos = $result_activos ? (int) pg_fetch_result($result_activos, 0, 0) : 0;
    
    return round(($miembros_activos / $total_miembros) * 100, 1);
}

/**
 * Registra una asistencia, permitiendo múltiples registros en el mismo día. 
 * @return mixed True si éxito, 'vencida' si membresía expiró, False si error DB.
 */
function registrar_asistencia($id_miembro, $fecha, $hora) {
    
    // 1. VERIFICAR MEMBRESÍA ACTIVA (Se mantiene tu lógica actual)
    if (!verificar_membresia_activa($id_miembro)) {
        error_log("Intento de asistencia denegada para Miembro ID: " . $id_miembro . " - Membresía vencida.");
        return 'vencida'; 
    }

    // 2. Insertar asistencia USANDO EL PROCEDIMIENTO ALMACENADO
    // Cambiamos el "INSERT INTO..." por "CALL..."
    $sql_insert = "CALL sp_registrar_asistencia($1, $2, $3)";
    
    // Ejecutamos la llamada
    $result_insert = db_query($sql_insert, [$id_miembro, $fecha, $hora]);
    
    return $result_insert !== null ? true : false;
}

function obtener_historial_asistencias($limite = 15) {
    $sql = "
        SELECT 
            a.id_asistencia, 
            a.fecha, 
            a.hora, 
            m.id_miembro,
            m.nombre || ' ' || m.apellido AS miembro_nombre
        FROM asistencias a
        JOIN miembros m ON a.id_miembro = m.id_miembro
        ORDER BY a.fecha DESC, a.hora DESC
        LIMIT $1
    ";
    $result = db_query($sql, [$limite]);
    $data = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function obtener_miembros_para_select() {
    $sql = "
        SELECT 
            id_miembro, 
            nombre || ' ' || apellido AS nombre_completo 
        FROM miembros 
        ORDER BY nombre_completo
    ";
    $result = db_query($sql);
    $data = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function obtener_miembro_por_qr($codigo_qr) {
    $sql = "
        SELECT id_miembro, nombre || ' ' || apellido AS nombre_completo 
        FROM miembros 
        WHERE codigo_qr = $1
    ";
    $result = db_query($sql, [$codigo_qr]);
    return $result ? pg_fetch_assoc($result) : null;
}


// =========================================================================
// LÓGICA DE MANEJO DE PETICIONES AJAX (FINAL)
// =========================================================================

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        switch ($_POST['action']) {
            
            case 'registrar_qr':
                $codigo_qr = trim($_POST['codigo_qr'] ?? '');
                if (empty($codigo_qr)) {
                    throw new Exception("El código QR no puede estar vacío.");
                }
                
                $miembro_data = obtener_miembro_por_qr($codigo_qr);
                
                if (!$miembro_data) {
                    throw new Exception("Código QR no válido. Miembro no encontrado.");
                }

                $id_miembro = (int) $miembro_data['id_miembro'];
                $fecha = date('Y-m-d');
                $hora = date('H:i:s');
                
                $registro_resultado = registrar_asistencia($id_miembro, $fecha, $hora);
                
                if ($registro_resultado === true) {
                    $response['success'] = true;
                    $response['message'] = "✅ Asistencia registrada con éxito.";
                    $response['miembro'] = $miembro_data['nombre_completo'];
                } elseif ($registro_resultado === 'vencida') {
                    $response['message'] = "❌ **Membresía Vencida.** No se puede registrar la asistencia. Contactar a administración.";
                } else {
                    $response['message'] = "Hubo un error al intentar registrar la asistencia (DB Error).";
                }
                break;
                
            case 'registrar_manual':
                $id_miembro = (int) ($_POST['id_miembro'] ?? 0);
                $fecha = $_POST['fecha'] ?? '';
                $hora = $_POST['hora'] ?? '';

                if ($id_miembro <= 0 || empty($fecha) || empty($hora)) {
                    throw new Exception("Faltan datos requeridos para el registro manual.");
                }
                
                $registro_resultado = registrar_asistencia($id_miembro, $fecha, $hora);
                
                if ($registro_resultado === true) {
                    $response['success'] = true;
                    $response['message'] = "✅ Asistencia manual registrada con éxito.";
                } elseif ($registro_resultado === 'vencida') {
                    $response['message'] = "❌ **Membresía Vencida.** No se puede registrar la asistencia manual.";
                } else {
                    $response['message'] = "Hubo un error al intentar registrar la asistencia manual (DB Error).";
                }
                break;
                
            case 'obtener_historial':
                $response['success'] = true;
                $response['data'] = obtener_historial_asistencias(15);
                break;
                
            case 'obtener_stats':
                $response['success'] = true;
                $response['stats'] = [
                    'hoy' => contar_asistencias_hoy(),
                    'semana' => contar_asistencias_esta_semana(),
                    'activos_pct' => calcular_porcentaje_miembros_activos()
                ];
                break;

            default:
                throw new Exception("Acción no válida.");
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response); 
    exit;
}