<?php 
// Evitar error si seguridad.php ya fue incluido en el archivo padre
if (!defined('SEGURIDAD_INCLUIDA')) {
    // Intenta incluir seguridad.php asumiendo estructura estándar
    // Si esto falla, asegúrate de incluir seguridad.php en tus archivos principales antes del menú
    @include_once('../inc/seguridad.php');
}
?>

<!-- ESTILOS DEL MENÚ INTEGRADOS -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap');

    :root {
        --primary-color: #fcbd00;       /* Amarillo Neón */
        --sidebar-width: 230px;         /* REDUCIDO: Antes 255px */
        --sidebar-bg: rgba(15, 15, 20, 0.85); /* Fondo oscuro semi-transparente */
        --text-muted: #a0a0a0;
        --text-light: #ffffff;
    }

    /* Reset básico para el componente */
    .sidebar * {
        box-sizing: border-box;
    }

    /* --- ESTRUCTURA PRINCIPAL (SIDEBAR) --- */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background-color: var(--sidebar-bg);
        backdrop-filter: blur(15px);        /* Efecto Cristal */
        -webkit-backdrop-filter: blur(15px);
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        padding: 20px 0;
        box-shadow: 5px 0 25px rgba(0, 0, 0, 0.5);
        overflow-y: auto; /* Permitir scroll si es muy alto */
        font-family: 'Montserrat', sans-serif;
    }

    /* Scrollbar fino */
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 4px; }

/* --- LOGO (MÁS GRANDE Y OPTIMIZADO) --- */
    .logo-container {
        text-align: center;
        /* REDUCIMOS el padding vertical para compensar el aumento de tamaño */
        padding: 0px 10px 10px 10px; 
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 15px;
    }

    .logo-glow {
        /* AUMENTADO: De 160px a 210px (Ocupa casi todo el ancho del sidebar) */
        width: 230px;  
        height: 230px; 
        /* REDUCIDO: Margen inferior reducido para que no empuje el texto */
        margin: 0 auto 5px; 
        border-radius: 50%;
        background: radial-gradient(circle, rgba(252, 189, 0, 0.2) 0%, transparent 70%);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    .logo-container img {
        /* AUMENTADO: De 130px a 180px */
        max-width: 300px; 
        height: auto;
        filter: drop-shadow(0 0 10px rgba(252, 189, 0, 0.6));
    }

    /* Efecto hover sutil */
    .logo-container:hover .logo-glow {
        transform: scale(1.02); /* Reducido un poco para que no se salga */
    }

    .logo-container h2 {
        color: var(--primary-color);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 1.6rem; 
        margin: 0;
        text-shadow: 0 0 10px rgba(252, 189, 0, 0.3);
        position: relative; 
        top: -10px; /* Truco: Subimos el texto un poquito para pegarlo al logo */
    }

    /* --- NAVEGACIÓN --- */
    .sidebar-nav {
        flex: 1;
        padding: 0 8px; /* Padding reducido para acercar al borde */
    }

    .menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .menu li {
        /* Espacio entre botones reducido */
        margin-bottom: 4px;
    }

    /* Separador de secciones (ADMINISTRACIÓN) */
    .menu-header {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-muted);
        /* Alineación ajustada */
        margin: 25px 0 10px 20px; 
        font-weight: 600;
        opacity: 0.7;
    }

    /* --- ENLACES (NAV-LINK) --- */
    .nav-link {
        display: flex;
        align-items: center;
        /* Padding ajustado */
        padding: 10px 15px; 
        color: var(--text-muted);
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        font-weight: 600;
        font-size: 0.9rem;
        border: 1px solid transparent;
    }

    .nav-link i {
        font-size: 1.1rem;
        margin-right: 15px;
        width: 24px;
        text-align: center;
        transition: color 0.3s;
    }

    /* Hover State */
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--text-light);
        transform: translateX(5px);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .nav-link:hover i {
        color: var(--primary-color);
    }

    /* --- ESTADO ACTIVO (PRIORIDAD) --- */
    .nav-link.active {
        background: linear-gradient(135deg, var(--primary-color) 0%, #e0a800 100%) !important;
        color: #000000 !important;
        font-weight: 800 !important;
        box-shadow: 0 0 15px rgba(252, 189, 0, 0.4) !important;
        border: none !important;
    }

    .nav-link.active i {
        color: #000000 !important;
    }

    .nav-link.active:hover {
        transform: none;
        cursor: default;
    }

    /* Botón Salir */
    .mt-auto {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .btn-logout {
        color: #ff6b6b !important;
    }

    .btn-logout:hover {
        background-color: rgba(255, 107, 107, 0.1);
        color: #ff4757 !important;
        border-color: rgba(255, 107, 107, 0.2);
    }

    .btn-logout:hover i {
        color: #ff4757 !important;
    }

    /* --- RESPONSIVE (Móvil) --- */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            padding: 10px 0;
        }
        
        .logo-container h2, 
        .nav-link span,
        .menu-header {
            display: none;
        }
        
        .logo-container {
            padding: 0 5px 20px 5px;
        }
        
        .logo-glow {
            width: 50px;
            height: 50px;
        }
        
        .logo-container img {
            max-width: 45px; /* Logo pequeño en móvil */
        }
        
        .nav-link {
            justify-content: center;
            padding: 15px;
        }
        
        .nav-link i {
            margin-right: 0;
            font-size: 1.5rem;
        }

        /* Ajuste forzoso al body para dejar espacio al menú colapsado */
        body {
            padding-left: 90px !important;
        }
    }
</style>

<!-- ESTRUCTURA HTML -->
<aside class="sidebar">
    <div class="logo-container">
        <div class="logo-glow">
            <!-- Asegúrate de que la ruta de la imagen sea correcta -->
            <img src="../imagenes/logo_sin_fondo.png" alt="Logo del Gimnasio">
        </div>
        <h2>Mi Gym</h2>
    </div>

    <nav class="sidebar-nav">
        <ul class="menu">
            <li>
                <a href="inicio.php" class="nav-link">
                    <i class="bi bi-house-door"></i> 
                    <span>Inicio</span>
                </a>
            </li>
            <li>
                <a href="finanzas.php" class="nav-link">
                    <i class="bi bi-currency-dollar"></i> 
                    <span>Finanzas</span>
                </a>
            </li>
            <li>
                <a href="miembros.php" class="nav-link">
                    <i class="bi bi-people"></i> 
                    <span>Miembros</span>
                </a>
            </li>
            <li>
                <a href="maquinas.php" class="nav-link">
                    <i class="bi bi-bicycle"></i> 
                    <span>Máquinas</span>
                </a>
            </li>
            <li>
                <a href="asistencias.php" class="nav-link">
                    <i class="bi bi-check2-square"></i> 
                    <span>Asistencia</span>
                </a>
            </li>
            <li>
                <a href="instructores.php" class="nav-link">
                    <i class="bi bi-person-badge"></i> 
                    <span>Instructores</span>
                </a>
            </li>
            <li>
                <a href="clases.php" class="nav-link">
                    <i class="bi bi-journal-bookmark"></i> 
                    <span>Clases</span>
                </a>
            </li>
            
            <?php if (isset($es_admin) && $es_admin): ?>  

            <li>
                <a href="usuarios.php" class="nav-link">
                    <i class="bi bi-person-gear"></i> 
                    <span>Usuarios</span>
                </a>
            </li>
            <li>
                <a href="membresias.php" class="nav-link">
                    <i class="bi bi-card-checklist"></i> 
                    <span>Membresías</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="mt-auto">
                <a href="../funciones/logout.php" class="nav-link btn-logout">
                    <i class="bi bi-box-arrow-right"></i> 
                    <span>Salir</span>
                </a>
            </li>
        </ul>
    </nav>
</aside> 

<!-- SCRIPT DE NAVEGACIÓN ACTIVA -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paginaActual = window.location.href;
    const enlaces = document.querySelectorAll('.nav-link');
    let encontrado = false;

    enlaces.forEach(enlace => {
        const href = enlace.getAttribute('href');
        
        // Ignorar logout para no marcarlo activo
        if (href.includes('logout.php')) return;

        // Comparación simple
        if (paginaActual.indexOf(href) !== -1) {
            enlace.classList.add('active');
            encontrado = true;
        }
    });

    // Fallback: si estamos en la raíz, activar Inicio
    if (!encontrado) {
        const linkInicio = document.querySelector('a[href="inicio.php"]');
        if (linkInicio && (paginaActual.endsWith('/') || paginaActual.includes('index.php'))) {
            linkInicio.classList.add('active');
        }
    }
});
</script>