<?php
// Incluye tu archivo de conexión
require_once '../inc/conexion.php';

// Función para crear un nuevo instructor
function crearInstructor($conn, $nombre, $apellido, $telefono, $correo, $especialidad, $fecha_contratacion) {
    $query = "INSERT INTO instructores (nombre, apellido, telefono, correo, especialidad, fecha_contratacion) 
              VALUES ($1, $2, $3, $4, $5, $6)";
    $result = pg_query_params($conn, $query, array($nombre, $apellido, $telefono, $correo, $especialidad, $fecha_contratacion));
    return $result ? pg_affected_rows($result) : 0;
} 

// Función para obtener todos los instructores
function obtenerInstructores($conn) {
    $query = "SELECT id_instructor, nombre, apellido, telefono, correo, especialidad, fecha_contratacion FROM instructores ORDER BY nombre";
    $result = pg_query($conn, $query);
    $instructores = array();
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $instructores[] = $row;
        }
    }
    return $instructores;
}

// Función para obtener un instructor por ID
function obtenerInstructorPorId($conn, $id_instructor) {
    $query = "SELECT id_instructor, nombre, apellido, telefono, correo, especialidad, fecha_contratacion FROM instructores WHERE id_instructor = $1";
    $result = pg_query_params($conn, $query, array($id_instructor));
    return $result ? pg_fetch_assoc($result) : null;
}

// Función para actualizar un instructor
function actualizarInstructor($conn, $id_instructor, $nombre, $apellido, $telefono, $correo, $especialidad, $fecha_contratacion) {
    $query = "UPDATE instructores 
              SET nombre = $1, apellido = $2, telefono = $3, correo = $4, especialidad = $5, fecha_contratacion = $6 
              WHERE id_instructor = $7";
              
    $result = pg_query_params($conn, $query, array($nombre, $apellido, $telefono, $correo, $especialidad, $fecha_contratacion, $id_instructor));
    return $result ? pg_affected_rows($result) : 0;
}


// Función para eliminar un instructor
function eliminarInstructor($conn, $id_instructor) {
    $query = "DELETE FROM instructores WHERE id_instructor = $1";
    $result = pg_query_params($conn, $query, array($id_instructor));
    return $result ? pg_affected_rows($result) : 0;
}
?>