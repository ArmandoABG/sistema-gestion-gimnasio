<?php
// Incluye tu archivo de conexión
require_once '../inc/conexion.php';

// Función para crear un nuevo usuario
function crearUsuario($conn, $usuario, $password, $rol) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO usuarios (usuario, password, rol) VALUES ($1, $2, $3)";
    $result = pg_query_params($conn, $query, array($usuario, $hashed_password, $rol));
    return $result ? pg_affected_rows($result) : 0;
}

// Función para obtener todos los usuarios
function obtenerUsuarios($conn) {
    $query = "SELECT id_usuario, usuario, rol FROM usuarios ORDER BY usuario";
    $result = pg_query($conn, $query);
    $usuarios = array();
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $usuarios[] = $row;
        }
    }
    return $usuarios;
}

// Función para obtener un usuario por ID
function obtenerUsuarioPorId($conn, $id_usuario) {
    $query = "SELECT id_usuario, usuario, rol FROM usuarios WHERE id_usuario = $1";
    $result = pg_query_params($conn, $query, array($id_usuario));
    return $result ? pg_fetch_assoc($result) : null;
}

// Función para actualizar un usuario (con opción de cambiar contraseña)
function actualizarUsuario($conn, $id_usuario, $usuario, $rol, $nueva_password = null) {
    if ($nueva_password) {
        // Actualizar usuario Y contraseña
        $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
        $query = "UPDATE usuarios SET usuario = $1, rol = $2, password = $3 WHERE id_usuario = $4";
        $result = pg_query_params($conn, $query, array($usuario, $rol, $hashed_password, $id_usuario));
    } else {
        // Actualizar solo usuario y rol
        $query = "UPDATE usuarios SET usuario = $1, rol = $2 WHERE id_usuario = $3";
        $result = pg_query_params($conn, $query, array($usuario, $rol, $id_usuario));
    }
    return $result ? pg_affected_rows($result) : 0;
}

// Función para eliminar un usuario
function eliminarUsuario($conn, $id_usuario) {
    $query = "DELETE FROM usuarios WHERE id_usuario = $1";
    $result = pg_query_params($conn, $query, array($id_usuario));
    return $result ? pg_affected_rows($result) : 0;
}

function obtenerMovimientos($conn) {
    
    // ESTA ES LA LÍNEA QUE DEBES CAMBIAR EN TU ARCHIVO
    $sql = "SELECT fecha_hora, autor_accion, rol_autor, tipo_movimiento, persona_o_equipo_afectado, descripcion_accion 
            FROM movimientos_base 
            ORDER BY fecha_hora DESC";
    
    // 1. Ejecutar la consulta con pg_query()
    $resultado = pg_query($conn, $sql);
    
    if (!$resultado) {
        // Muestra el error de PostgreSQL para depuración (opcional, pero útil)
        error_log("Error al obtener movimientos: " . pg_last_error($conn));
        return [];
    }
    
    $movimientos = [];
    // Recorremos el resultado
    while ($fila = pg_fetch_assoc($resultado)) {
        $movimientos[] = $fila;
    }
    
    pg_free_result($resultado);
    
    return $movimientos;
}

?>