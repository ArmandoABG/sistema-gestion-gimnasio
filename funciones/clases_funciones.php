<?php
// Incluye tu archivo de conexión
require_once '../inc/conexion.php';
require_once 'miembros_funciones.php';
require_once 'horarios_clases_funciones.php';

// Función para crear un nuevo instructor
function crearClase($conn, $nombre, $descripcion, $duracion_minutos, $capacidad_maxima) {
    $query = "INSERT INTO clases (nombre, descripcion, duracion_minutos, capacidad_maxima) 
              VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($conn, $query, array($nombre, $descripcion, $duracion_minutos, $capacidad_maxima));
    return $result ? pg_affected_rows($result) : 0;
}

// Función para obtener todos los instructores
function obtenerClases($conn) {
    $query = "SELECT id_clase, nombre, descripcion, duracion_minutos, capacidad_maxima FROM clases ORDER BY nombre";
    $result = pg_query($conn, $query);
    $clases = array();
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $clases[] = $row;
        }
    }
    return $clases;
}

// Función para obtener un instructor por ID
function obtenerClasesPorId($conn, $id_clase) {
    $query = "SELECT id_clase, nombre, descripcion, duracion_minutos, capacidad_maxima FROM clases WHERE id_clase = $1";
    $result = pg_query_params($conn, $query, array($id_clase));
    return $result ? pg_fetch_assoc($result) : null;
}

// Función para actualizar un instructor
function actualizarClase($conn, $id_clase, $nombre, $descripcion, $duracion_minutos, $capacidad_maxima) {
    $query = "UPDATE clases 
              SET nombre = $1, descripcion = $2, duracion_minutos = $3, capacidad_maxima = $4
              WHERE id_clase = $5";
              
    $result = pg_query_params($conn, $query, array($nombre, $descripcion, $duracion_minutos, $capacidad_maxima, $id_clase));
    return $result ? pg_affected_rows($result) : 0;
}

// Función para eliminar un instructor
function eliminarClase($conn, $id_clase) {
    $query = "DELETE FROM clases WHERE id_clase = $1";
    $result = pg_query_params($conn, $query, array($id_clase));
    return $result ? pg_affected_rows($result) : 0;
}

function inscribirMiembroAClase($conn, $id_miembro, $id_horario_clase) {
    
    // =================================================================
    // PASO 0: Mantenimiento - Actualizar estados de membresías vencidas
    // =================================================================
    $sql_mantenimiento = "SELECT actualizar_estado_membresias_vencidas();";
    $mantenimiento_exitoso = pg_query($conn, $sql_mantenimiento);
    
    if (!$mantenimiento_exitoso) {
        error_log("ADVERTENCIA: Falló la actualización de membresías vencidas.");
    }

    // 1. Obtener datos de Cupo y Capacidad
    $datos_cupo = obtenerDatosHorarioYCupo($conn, $id_horario_clase);
    
    if (!$datos_cupo) {
        return ['success' => false, 'message' => 'Error: Horario de clase no encontrado.'];
    }

    $cupo_disponible = (int)$datos_cupo['cupo_disponible'];
    
    // 2. Validación de Cupo
    if ($cupo_disponible <= 0) {
        return ['success' => false, 'message' => 'Error: El cupo máximo para esta clase ha sido alcanzado. 😥'];
    }

    // =================================================================
    // PASO 3: Validación de Membresía Activa
    // =================================================================
    $sql_check_membership = "
        SELECT 1 
        FROM miembros_membresias 
        WHERE id_miembro = $1 AND estado = 'activo'  
        LIMIT 1
    ";
    $result_membership = pg_query_params($conn, $sql_check_membership, array($id_miembro));

    if (!$result_membership || pg_num_rows($result_membership) === 0) {
        return ['success' => false, 'message' => 'Error: El miembro no tiene una membresía activa y vigente para inscribirse a clases.'];
    }

    // 4. Verificar si el miembro ya está inscrito (Duplicidad Check)
    $sql_check_duplicate = "
        SELECT 1 
        FROM inscripciones_clases 
        WHERE id_miembro = $1 AND id_horario_clase = $2 AND estado = 'alta'
    ";
    $result_duplicate = pg_query_params($conn, $sql_check_duplicate, array($id_miembro, $id_horario_clase));
    
    if ($result_duplicate && pg_num_rows($result_duplicate) > 0) {
        return ['success' => false, 'message' => 'Advertencia: El miembro ya está inscrito en este horario de clase.'];
    }

    // 5. Insertar la inscripción
    $sql_insert = "
        INSERT INTO inscripciones_clases 
            (id_miembro, id_horario_clase, estado) 
        VALUES 
            ($1, $2, 'alta')
    ";
    
    $resultado = pg_query_params($conn, $sql_insert, array($id_miembro, $id_horario_clase));

    if ($resultado) {
        return ['success' => true, 'message' => '¡Inscripción a la clase completada con éxito! 🎉'];
    } else {
        error_log("Error al inscribir miembro: " . pg_last_error($conn));
        return ['success' => false, 'message' => 'Error de Base de Datos: No se pudo completar la inscripción.'];
    }
}

// FUNCIÓN AGREGADA: Para desinscribir miembro de clase
function desinscribirMiembroDeClase($conn, $id_miembro, $id_horario_clase) {
    $sql = "
        UPDATE inscripciones_clases 
        SET estado = 'baja' 
        WHERE id_miembro = $1 AND id_horario_clase = $2 AND estado = 'alta'
    ";
    
    $resultado = pg_query_params($conn, $sql, array($id_miembro, $id_horario_clase));
    
    if ($resultado && pg_affected_rows($resultado) > 0) {
        return ['success' => true, 'message' => 'Miembro desinscrito de la clase correctamente.'];
    } else {
        error_log("Error al desinscribir miembro: " . pg_last_error($conn));
        return ['success' => false, 'message' => 'Error: No se pudo desinscribir al miembro de la clase.'];
    }
}
?>