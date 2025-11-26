<?php
// ajax_clases.php
require_once '../inc/conexion.php'; // Asegúrate de que esta ruta sea correcta

if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_cupo' && isset($_GET['id_horario_clase'])) {
    $id_horario_clase = (int)$_GET['id_horario_clase'];
    
    // Función duplicada o incluida desde clases.php (debes asegurarte de que esta función exista/esté disponible)
    // Por simplicidad, la definiremos aquí nuevamente (en una aplicación real se usaría un archivo de funciones).
    function obtenerDatosHorarioYCupo($conn, $id_horario_clase) {
        $sql = "
            SELECT 
                hc.id_horario_clase, 
                c.nombre AS nombre_clase,
                c.capacidad_maxima,
                (
                    SELECT COUNT(*) 
                    FROM inscripciones_clases 
                    WHERE id_horario_clase = hc.id_horario_clase AND estado = 'alta'
                ) AS inscritos_actuales
            FROM horarios_clases hc
            JOIN clases c ON hc.id_clase = c.id_clase
            WHERE hc.id_horario_clase = $1
        ";
        
        $resultado = pg_query_params($conn, $sql, array($id_horario_clase));
        
        if ($resultado && pg_num_rows($resultado) > 0) {
            $datos = pg_fetch_assoc($resultado);
            $datos['cupo_disponible'] = $datos['capacidad_maxima'] - $datos['inscritos_actuales'];
            return $datos;
        }
        
        return null;
    }
    
    $datos = obtenerDatosHorarioYCupo($conn, $id_horario_clase);

    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}
// Más acciones AJAX podrían ir aquí
?>