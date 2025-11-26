<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Incluye tu archivo de conexión
require_once '../inc/conexion.php';

function crearHorarioClase($conn, $id_clase, $id_instructor, $dia_semana, $hora_inicio, $salon) {
    $query = "INSERT INTO horarios_clases (id_clase, id_instructor, dia_semana, hora_inicio, salon)
            VALUES ($1, $2, $3, $4, $5)";
    $result = pg_query_params($conn, $query, array($id_clase, $id_instructor, $dia_semana, $hora_inicio, $salon));
    return $result ? pg_affected_rows($result) : 0;
}


// Función para obtener todos los instructores
// Archivo: ../funciones/horarios_clases_funciones.php (o donde esté esta función)

function obtenerHorariosClases($conn) {
    $query = "SELECT 
                hc.id_horario_clase,
                hc.id_clase,                   
                hc.id_instructor,               
                c.nombre AS nombre_clase,
                i.nombre || ' ' || i.apellido AS nombre_instructor,  -- 👈 CORRECCIÓN APLICADA AQUÍ
                hc.dia_semana,
                hc.hora_inicio,
                hc.salon
            FROM horarios_clases hc
            INNER JOIN clases c ON hc.id_clase = c.id_clase
            INNER JOIN instructores i ON hc.id_instructor = i.id_instructor
            ORDER BY hc.dia_semana, hc.hora_inicio;
";
    $result = pg_query($conn, $query);
    $horarios_clases = array();
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $horarios_clases[] = $row;
        }
    }
    return $horarios_clases;
}

// Función para eliminar un instructor
function eliminarHorarioClase($conn, $id_horario_clase) {
    $query = "DELETE FROM horarios_clases WHERE id_horario_clase = $1";
    $result = pg_query_params($conn, $query, array($id_horario_clase));
    return $result ? pg_affected_rows($result) : 0;
}

// Función para actualizar un horario de clase
function actualizarHorarioClase($conn, $id_horario_clase, $id_clase, $id_instructor, $dia_semana, $hora_inicio, $salon) {
    $query = "UPDATE horarios_clases
              SET id_clase = $1, id_instructor = $2, dia_semana = $3, hora_inicio = $4, salon = $5
              WHERE id_horario_clase = $6";
              
    $result = pg_query_params($conn, $query, array($id_clase, $id_instructor, $dia_semana, $hora_inicio, $salon, $id_horario_clase));
    return $result ? pg_affected_rows($result) : 0;
}

?>

