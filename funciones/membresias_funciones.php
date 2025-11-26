<?php

// Incluye tu archivo de conexión
require_once '../inc/conexion.php';

// Función para crear una nueva membresia
function crearMembresia($conn, $nombre, $precio, $duracion_dias, $estado) {
    $query = "INSERT INTO membresias (nombre, precio, duracion_dias, estado) 
              VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($conn, $query, array($nombre, $precio, $duracion_dias, $estado));
    return $result ? pg_affected_rows($result) : 0;
}

// Función para obtener todos los membresias
function obtenerMembresias($conn) {
    $query = "SELECT id_membresia, nombre, precio, duracion_dias, estado FROM membresias ORDER BY nombre";
    $result = pg_query($conn, $query);
    $membresias = array();
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $membresias[] = $row;
        }
    }
    return $membresias; 
}

// Función para obtener un instructor por ID
function obtenerMembresiasPorId($conn, $id_membresia) {
    $query = "SELECT id_membresia, nombre, precio, duracion_dias, estado FROM membresias WHERE id_membresia = $1";
    $result = pg_query_params($conn, $query, array($id_membresia));
    return $result ? pg_fetch_assoc($result) : null;
}

// Función para actualizar un instructor
function actualizarMembresia($conn, $id_membresia, $nombre, $precio, $duracion_dias, $estado) {
    $query = "UPDATE membresias 
              SET nombre = $1, precio = $2, duracion_dias = $3, estado = $4
              WHERE id_membresia = $5";
              
    $result = pg_query_params($conn, $query, array($nombre, $precio, $duracion_dias, $estado, $id_membresia));
    return $result ? pg_affected_rows($result) : 0;
}

// Función para desactivar una membresía (en lugar de eliminar)
function desactivarMembresia($conn, $id_membresia) {
    $query = "UPDATE membresias SET estado = 'inactivo' WHERE id_membresia = $1";
    $result = pg_query_params($conn, $query, array($id_membresia));
    return $result ? pg_affected_rows($result) : 0;
}

// (Opcional) Función para reactivar una membresía si la necesitas después
function activarMembresia($conn, $id_membresia) {
    $query = "UPDATE membresias SET estado = 'activo' WHERE id_membresia = $1";
    $result = pg_query_params($conn, $query, array($id_membresia));
    return $result ? pg_affected_rows($result) : 0;
}

// FUNCIÓN CORREGIDA - SOLO UNA VEZ
function obtenerTodasMembresiasActivas($conn) {
    $query = "SELECT id_membresia, nombre, precio, duracion_dias FROM membresias WHERE estado = 'activo' ORDER BY nombre";
    $result = pg_query($conn, $query);
    $membresias = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $membresias[] = $row;
        }
    }
    return $membresias;
}

// =========================================================================
// FUNCIONES CORREGIDAS PARA RENOVACIÓN DE MEMBRESÍAS
// =========================================================================

function verificarMembresiaActiva($conn, $id_membresia) {
    $sql = "SELECT id_membresia FROM membresias 
            WHERE id_membresia = $1 AND estado = 'activo'";
    
    $result = pg_query_params($conn, $sql, array($id_membresia));
    if (!$result) {
        error_log("Error en verificarMembresiaActiva: " . pg_last_error($conn));
        return false;
    }
    
    return pg_num_rows($result) > 0;
}

// Función auxiliar para obtener la membresía activa más reciente de un miembro
function obtenerMembresiaActual($conn, $id_miembro) {
    $sql = "SELECT mm.id_miembro_membresia, mm.id_membresia, m.nombre as nombre_membresia,
                   mm.fecha_inicio, mm.fecha_fin, mm.estado
            FROM miembros_membresias mm
            INNER JOIN membresias m ON mm.id_membresia = m.id_membresia
            WHERE mm.id_miembro = $1 AND mm.estado = 'activo'
            ORDER BY mm.fecha_fin DESC 
            LIMIT 1";
    
    $result = pg_query_params($conn, $sql, array($id_miembro));
    
    if (!$result || pg_num_rows($result) === 0) {
        // Buscar la última membresía registrada (activa o inactiva)
        $sql_ultima = "SELECT mm.id_miembro_membresia, mm.id_membresia, m.nombre as nombre_membresia,
                       mm.fecha_inicio, mm.fecha_fin, mm.estado
                FROM miembros_membresias mm
                INNER JOIN membresias m ON mm.id_membresia = m.id_membresia
                WHERE mm.id_miembro = $1
                ORDER BY mm.fecha_fin DESC
                LIMIT 1";
        $result_ultima = pg_query_params($conn, $sql_ultima, array($id_miembro));
        return ($result_ultima && pg_num_rows($result_ultima) > 0) ? pg_fetch_assoc($result_ultima) : null;
    }
    
    return pg_fetch_assoc($result);
}


function renovarMembresia($conn, $id_miembro, $id_membresia, $monto_pagado, $id_usuario) {
    // 1. Verificar que la nueva membresía existe en el catálogo
    if (!verificarMembresiaActiva($conn, $id_membresia)) {
        return ['success' => false, 'error' => 'Membresía seleccionada no encontrada.'];
    }
    
    // 2. Obtener datos de la nueva membresía (duración)
    $sql_info = "SELECT duracion_dias FROM membresias WHERE id_membresia = $1";
    $res_info = pg_query_params($conn, $sql_info, array($id_membresia));
    $info_nueva = pg_fetch_assoc($res_info);
    $duracion_dias = (int)$info_nueva['duracion_dias'];
    
    // 3. Verificar si el miembro YA tiene un registro en miembros_membresias
    // Buscamos cualquier registro, activo o inactivo, porque la tabla tiene restricción UNIQUE
    $sql_existente = "SELECT id_miembro_membresia, fecha_fin, estado 
                      FROM miembros_membresias 
                      WHERE id_miembro = $1";
    $res_existente = pg_query_params($conn, $sql_existente, array($id_miembro));
    $registro_existente = pg_fetch_assoc($res_existente);

    // 4. Calcular fechas
    $hoy = new DateTime('today'); // 00:00:00
    $fecha_inicio_dt = clone $hoy;

    // Si existe y está activa, la renovación arranca cuando termine la actual
    if ($registro_existente && $registro_existente['estado'] === 'activo') {
        $fecha_fin_anterior = new DateTime($registro_existente['fecha_fin']);
        $fecha_fin_anterior->setTime(0,0,0);
        
        if ($fecha_fin_anterior >= $hoy) {
            $fecha_inicio_dt = $fecha_fin_anterior->modify('+1 day');
        }
    }
    // Si está inactiva o no existe, arranca hoy ($fecha_inicio_dt ya es hoy)

    $fecha_inicio = $fecha_inicio_dt->format('Y-m-d');
    
    // Calcular nueva fecha fin
    $fecha_fin_dt = clone $fecha_inicio_dt;
    $fecha_fin = $fecha_fin_dt->modify("+$duracion_dias days")->format('Y-m-d');
    
    // 5. Iniciar transacción
    pg_query($conn, "BEGIN");
    
    try {
        $id_miembro_membresia = 0;

        if ($registro_existente) {
            // === ESCENARIO A: ACTUALIZAR (UPDATE) ===
            // El miembro ya existe en la tabla, SOBRESCRIBIMOS sus datos para respetar la restricción UNIQUE
            $id_miembro_membresia = $registro_existente['id_miembro_membresia'];
            
            $sql_update = "UPDATE miembros_membresias 
                           SET id_membresia = $1, 
                               fecha_inicio = $2, 
                               fecha_fin = $3, 
                               estado = 'activo' 
                           WHERE id_miembro_membresia = $4";
            
            $result_accion = pg_query_params($conn, $sql_update, array(
                $id_membresia, 
                $fecha_inicio, 
                $fecha_fin, 
                $id_miembro_membresia
            ));
            
            if (!$result_accion) throw new Exception("Error al actualizar la membresía existente.");

        } else {
            // === ESCENARIO B: INSERTAR (INSERT) ===
            // Es un miembro nuevo que NUNCA ha tenido membresía
            $sql_insert = "INSERT INTO miembros_membresias 
                           (id_miembro, id_membresia, fecha_inicio, fecha_fin, estado) 
                           VALUES ($1, $2, $3, $4, 'activo') 
                           RETURNING id_miembro_membresia";
            
            $result_accion = pg_query_params($conn, $sql_insert, array(
                $id_miembro, 
                $id_membresia, 
                $fecha_inicio, 
                $fecha_fin
            ));
            
            if (!$result_accion) throw new Exception("Error al crear la primera membresía.");
            $id_miembro_membresia = pg_fetch_result($result_accion, 0, 0);
        }
        
        // 6. Insertar PAGO (El historial de pagos SÍ se acumula, eso está bien)
        $sql_pago = "INSERT INTO pagos (id_miembro, id_miembro_membresia, monto, id_usuario) VALUES ($1, $2, $3, $4)";
        $result_pago = pg_query_params($conn, $sql_pago, array($id_miembro, $id_miembro_membresia, $monto_pagado, $id_usuario));
        
        if (!$result_pago) throw new Exception("Error al registrar el pago.");
        
        pg_query($conn, "COMMIT");
        
        return [
            'success' => true,
            'fecha_fin' => $fecha_fin,
            'monto_registrado' => $monto_pagado,
            'id_miembro_membresia' => $id_miembro_membresia
        ];
        
    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK");
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>