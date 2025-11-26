<?php
require_once "../inc/conexion.php";

// ----------------------
// CREAR
// ----------------------
function crearMaquina($conn, $nombre, $descripcion, $fecha, $estado = 'disponible') {
    $query = "INSERT INTO maquinas (nombre, descripcion, fecha_adquisicion, estado) 
              VALUES ($1, $2, $3, $4)";
    return pg_query_params($conn, $query, [$nombre, $descripcion, $fecha, $estado]);
}

// ----------------------
// LISTAR
// ----------------------
function obtenerMaquinas($conn) {
    $query = "SELECT * FROM maquinas ORDER BY id_maquina DESC";
    $result = pg_query($conn, $query);
    $maquinas = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $maquinas[] = $row;
        }
    }
    return $maquinas;
}

// ----------------------
// ACTUALIZAR
// ----------------------
function actualizarMaquina($conn, $id, $nombre, $descripcion, $fecha) {
    $query = "UPDATE maquinas 
              SET nombre=$1, descripcion=$2, fecha_adquisicion=$3 
              WHERE id_maquina=$4";
    return pg_query_params($conn, $query, [$nombre, $descripcion, $fecha, $id]);
}

// ----------------------
// ELIMINAR
// ----------------------
function eliminarMaquina($conn, $id) {
    $query = "DELETE FROM maquinas WHERE id_maquina=$1";
    return pg_query_params($conn, $query, [$id]);
}

// ----------------------
// OBTENER MAQUINAS POR ESTADO
// ----------------------
function obtenerMaquinasPorEstado($conn, $estado) {
    $query = "SELECT id_maquina, nombre FROM maquinas WHERE estado = $1 ORDER BY nombre ASC";
    $result = pg_query_params($conn, $query, [$estado]);
    $maquinas = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $maquinas[] = $row;
        }
    }
    return $maquinas;
}

// ----------------------
// REGISTRAR MANTENIMIENTO (LA FUNCIÓN FALTANTE)
// ----------------------
function registrarMantenimiento($conn, $id_maquina, $id_usuario, $descripcion, $tipo) {
    // 1. Insertar el registro de mantenimiento
    $query_mant = "INSERT INTO mantenimiento_maquinas (id_maquina, id_usuario, descripcion, tipo) 
                   VALUES ($1, $2, $3, $4)
                   RETURNING id_mantenimiento";
    $result_mant = pg_query_params($conn, $query_mant, [$id_maquina, $id_usuario, $descripcion, $tipo]);

    if (!$result_mant) {
        return false;
    }

    // 2. Actualizar el estado de la máquina a 'en_mantenimiento'
    $query_maquina = "UPDATE maquinas 
                      SET estado='en mantenimiento' 
                      WHERE id_maquina=$1";
    $result_maquina = pg_query_params($conn, $query_maquina, [$id_maquina]);

    return (bool)$result_maquina;
}

// ----------------------
// FINALIZAR MANTENIMIENTO
// ----------------------
function finalizarMantenimiento($conn, $id_mantenimiento, $id_maquina) {
    // 1. Actualizar la fecha_fin del registro de mantenimiento
    $query_mant = "UPDATE mantenimiento_maquinas 
                   SET fecha_fin = CURRENT_TIMESTAMP 
                   WHERE id_mantenimiento = $1 AND fecha_fin IS NULL";
    $result_mant = pg_query_params($conn, $query_mant, [$id_mantenimiento]);

    if (!$result_mant) {
        return false;
    }

    // 2. Actualizar el estado de la máquina a 'disponible'
    $query_maquina = "UPDATE maquinas 
                      SET estado='disponible' 
                      WHERE id_maquina=$1";
    $result_maquina = pg_query_params($conn, $query_maquina, [$id_maquina]);

    return $result_maquina;
}

// ----------------------
// OBTENER MANTENIMIENTOS ACTIVOS
// ----------------------
function obtenerMantenimientosActivos($conn) {
    $query = "SELECT m.*, ma.nombre AS nombre_maquina, u.usuario AS nombre_usuario
              FROM mantenimiento_maquinas m
              JOIN maquinas ma ON m.id_maquina = ma.id_maquina
              JOIN usuarios u ON m.id_usuario = u.id_usuario
              WHERE m.fecha_fin IS NULL 
              ORDER BY m.fecha_inicio DESC";
    $result = pg_query($conn, $query);
    $mantenimientos = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $mantenimientos[] = $row;
        }
    }
    return $mantenimientos;
}

// ----------------------
// OBTENER HISTORIAL MANTENIMIENTOS
// ----------------------
function obtenerHistorialMantenimientos($conn) {
    $query = "SELECT m.*, ma.nombre AS nombre_maquina, u.usuario AS nombre_usuario
              FROM mantenimiento_maquinas m
              JOIN maquinas ma ON m.id_maquina = ma.id_maquina
              JOIN usuarios u ON m.id_usuario = u.id_usuario
              ORDER BY m.fecha_inicio DESC";
    $result = pg_query($conn, $query);
    $mantenimientos = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $mantenimientos[] = $row;
        }
    }
    return $mantenimientos;
}
