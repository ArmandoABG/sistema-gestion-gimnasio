<?php

// Si no existe la variable de sesión "usuario", redirigir al login
if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit();
}

// Variable para saber si es admin
$es_admin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
?>