<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Gimnasio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para el icono del ojito -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Global -->
    <link rel="stylesheet" href="css/style_global.css">
    <!-- CSS Login -->
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_login.css">
    <style>
        /* Estilos para el ojito de contraseña */
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        .password-toggle:hover {
            color: #495057;
        }
        .form-control {
            padding-right: 45px; /* Espacio para el ojito */
        }
    </style>
</head>
<body>

    <div class="login-background row no-gutters vh-100">
        <!-- Columna izquierda: imagen -->
        <div class="col-md-6 login-image d-none d-md-block"></div>

        <!-- Columna derecha: fondo negro y formulario -->
        <div class="col-md-6 login-form d-flex justify-content-center align-items-center">
            <div class="login-container">
                <img src="imagenes/logo_sin_fondo.png" alt="Logo" class="logo mb-3">
                <h2>Iniciar sesión</h2>
                <form method="POST" action="funciones/login_funciones.php">
                    <?php
                    session_start();
                    if (isset($_SESSION['error_login'])) {
                        echo "<div class='alert alert-danger text-center'>".$_SESSION['error_login']."</div>";
                        unset($_SESSION['error_login']); // limpiar error después de mostrarlo
                    }
                    ?>
                    <div class="mb-3">
                        <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
                    </div>
                    <div class="mb-3 password-container">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Contraseña" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sección de información / créditos -->
    <section class="info-footer py-3 text-center">
        <div class="container info-footer-container">
            <span>💪 Sistema de gestión de gimnasio</span> |
            <span>Desarrollado por: Ing. Diego Armando Barboza González</span> |
            <span>Fecha: 15/09/2025</span> |
            <span>Versión: 1.0</span>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Funcionalidad para mostrar/ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                // Cambiar el tipo de input
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Cambiar el icono
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>