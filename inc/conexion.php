<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Datos de conexión
$host = "localhost";
$port = "5432"; // Puerto por defecto de PostgreSQL
$dbname = "GymDB";
$user = "tu_usuario"; // o el usuario que uses
$password = "tu_password"; // o la contraseña que uses

// Conexión
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Verificar conexión
if (!$conn) {
    die("❌ Error al conectar con PostgreSQL.");
} 
?>