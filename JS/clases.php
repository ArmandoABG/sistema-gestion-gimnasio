<?php
session_start();  
// =========================================================================
// 1. INCLUSIONES Y CONEXIÓN 
// =========================================================================
// Incluye tu archivo de conexión y las funciones
require_once '../inc/conexion.php';
require_once '../funciones/clases_funciones.php';
require_once '../funciones/horarios_clases_funciones.php';
include('../inc/seguridad.php');

// Definición de variables de datos vacías para evitar errores de Undefined
$clases = [];
$horarios_clases = [];
$todos_los_instructores = [];
$todas_las_clases = []; 
$todos_los_miembros = [];


// =========================================================================
// 2. PROCESAMIENTO DE FORMULARIOS (POST)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    switch ($accion) {
        // --- CRUD de Clases ---
        case 'crear_clase':
            crearClase($conn, $_POST['nombre'], $_POST['descripcion'], $_POST['duracion_minutos'], $_POST['capacidad_maxima']);
            header("Location: clases.php?exito=crear_clase");
            exit();
        case 'actualizar_clase':
            actualizarClase($conn, $_POST['id_clase'], $_POST['nombre'], $_POST['descripcion'], $_POST['duracion_minutos'], $_POST['capacidad_maxima']);
            header("Location: clases.php?exito=editar_clase");
            exit();
        case 'eliminar_clase':
            eliminarClase($conn, $_POST['id_clase']);
            header("Location: clases.php?exito=eliminar_clase");
            exit();

        // --- CRUD de Horarios ---
        case 'crear_horario':
            crearHorarioClase($conn, $_POST['id_clase'], $_POST['id_instructor'], $_POST['dia_semana'], $_POST['hora_inicio'], $_POST['salon']);
            header("Location: clases.php?seccion=horarios&exito=crear_horario");
            exit();
        case 'actualizar_horario':
            actualizarHorarioClase($conn, $_POST['id_horario_clase'], $_POST['id_clase'], $_POST['id_instructor'], $_POST['dia_semana'], $_POST['hora_inicio'], $_POST['salon']);
            header("Location: clases.php?seccion=horarios&exito=editar_horario");
            exit();
        case 'eliminar_horario':
            eliminarHorarioClase($conn, $_POST['id_horario_clase']);
            header("Location: clases.php?seccion=horarios&exito=eliminar_horario");
            exit();

        // --- Inscripción a Clases ---
        case 'inscribir_miembro':
            if (isset($_POST['id_horario_clase']) && isset($_POST['id_miembro'])) {
                // Llama a la función y CAPTURA el array de resultado
                $result = inscribirMiembroAClase($conn, $_POST['id_miembro'], $_POST['id_horario_clase']);
                
                // Guarda el mensaje en la sesión para mostrarlo después de la redirección
                $_SESSION['message_status'] = $result['success'] ? 'success' : 'error';
                $_SESSION['message'] = $result['message'];
            }
            // Redirigir siempre de vuelta a la sección de horarios
            header("Location: clases.php?seccion=horarios");
            exit();

        // --- Desinscripción de Clases ---
        case 'desinscribir_miembro':
            if (isset($_POST['id_horario_clase']) && isset($_POST['id_miembro'])) {
                $result = desinscribirMiembroDeClase($conn, $_POST['id_miembro'], $_POST['id_horario_clase']);
                
                $_SESSION['message_status'] = $result['success'] ? 'success' : 'error';
                $_SESSION['message'] = $result['message'];
            }
            header("Location: clases.php?seccion=inscripciones");
            exit();
            
    }
}

// **NUEVA FUNCIÓN DE APOYO para la lógica del modal de inscripción**
// Esta función traerá el horario y su capacidad/cupo.
function obtenerDatosHorarioYCupo($conn, $id_horario_clase) {
    // Consulta para obtener la capacidad máxima de la clase (desde la tabla 'clases')
    // y el número actual de inscritos.
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
    
    // Usar sentencia preparada (mejor práctica)
    $stmt = pg_prepare($conn, "horario_cupo_q", $sql);
    if (!$stmt) {
        error_log("Error al preparar la consulta de horario: " . pg_last_error($conn));
        return null;
    }
    
    $resultado = pg_execute($conn, "horario_cupo_q", array($id_horario_clase));
    
    if ($resultado && pg_num_rows($resultado) > 0) {
        $datos = pg_fetch_assoc($resultado);
        $datos['cupo_disponible'] = $datos['capacidad_maxima'] - $datos['inscritos_actuales'];
        return $datos;
    }
    
    return null;
}

// =========================================================================
// 3. PREPARACIÓN DE DATOS (GET/Carga de Página)
// =========================================================================

// Determinar la sección actual (por defecto 'clases')
$seccion = $_GET['seccion'] ?? 'clases';

// Obtener datos principales para la sección activa
if ($seccion === 'clases' || $seccion === 'registrar_clase') {
    $clases = obtenerClases($conn);
} elseif ($seccion === 'horarios') {
    $horarios_clases = obtenerHorariosClases($conn);
} elseif ($seccion === 'inscripciones') {
    // Obtener todas las inscripciones activas
    $sql_inscripciones = "
        SELECT 
            ic.id_inscripcion,
            m.id_miembro,
            m.nombre AS nombre_miembro,
            m.apellido AS apellido_miembro,
            c.nombre AS nombre_clase,
            hc.dia_semana,
            hc.hora_inicio,
            hc.id_horario_clase,
            i.nombre AS nombre_instructor,
            i.apellido AS apellido_instructor,
            hc.salon
        FROM inscripciones_clases ic
        JOIN miembros m ON ic.id_miembro = m.id_miembro
        JOIN horarios_clases hc ON ic.id_horario_clase = hc.id_horario_clase
        JOIN clases c ON hc.id_clase = c.id_clase
        JOIN instructores i ON hc.id_instructor = i.id_instructor
        WHERE ic.estado = 'alta'
        ORDER BY hc.dia_semana, hc.hora_inicio
    ";
    
    $result_inscripciones = pg_query($conn, $sql_inscripciones);
    $inscripciones = $result_inscripciones ? pg_fetch_all($result_inscripciones) : [];
}

// Obtener datos de apoyo (listas para SELECTs de Modals/Formularios)
// Recomendación: Utiliza sentencias preparadas para estas consultas.
$resultado_clases = pg_query($conn, "SELECT id_clase, nombre FROM clases ORDER BY nombre");
$todas_las_clases = $resultado_clases ? pg_fetch_all($resultado_clases) : [];

$resultado_instructores = pg_query($conn, "SELECT id_instructor, nombre, apellido FROM instructores ORDER BY nombre");
$todos_los_instructores = $resultado_instructores ? pg_fetch_all($resultado_instructores) : [];

$resultado_miembros = pg_query($conn, "SELECT id_miembro, nombre, apellido FROM miembros ORDER BY apellido, nombre");
$todos_los_miembros = $resultado_miembros ? pg_fetch_all($resultado_miembros) : [];
?> 


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de clases</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- SWEETALERT2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/style_clases.css">
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_menu.css">
    
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>
    
    <div class="menu-superior">
        <a href="clases.php?seccion=clases" class="menu-btn <?= ($seccion === 'clases' ? 'active' : ''); ?>">Clases registradas</a>
        <a href="clases.php?seccion=registrar_clase" class="menu-btn <?= ($seccion === 'registrar_clase' ? 'active' : ''); ?>">Registrar clase</a>
        <a href="clases.php?seccion=horarios" class="menu-btn <?= ($seccion === 'horarios' ? 'active' : ''); ?>">Horarios clases</a>
        <a href="clases.php?seccion=agendar" class="menu-btn <?= ($seccion === 'agendar' ? 'active' : ''); ?>">Agendar clase</a>
        <a href="clases.php?seccion=inscripciones" class="menu-btn <?= ($seccion === 'inscripciones' ? 'active' : ''); ?>">Inscripciones</a>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            <h1>Gestión de clases</h1>
                    <?php 
                    // Lógica para mostrar mensajes de la sesión
                    if (isset($_SESSION['message'])): 
                        // Determina la clase de Bootstrap para el color de la alerta
                        // 'alert-success' para éxito, 'alert-danger' para error/advertencia
                        $alert_class = ($_SESSION['message_status'] == 'success') ? 'alert-success' : 'alert-danger';
                    ?>
                        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                            <strong>Resultado:</strong> <?php echo htmlspecialchars($_SESSION['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php 
            // Limpia las variables de sesión inmediatamente después de mostrar el mensaje
            // para que no se muestre de nuevo al recargar la página
            unset($_SESSION['message']);
            unset($_SESSION['message_status']);
        endif; 
        ?>

            <?php if ($seccion === 'clases'): // ------------------ SECCIÓN CLASES REGISTRADAS ------------------ ?>
                
                <h2>Lista de Clases</h2>
                <?php if (!empty($clases)): ?>
                    <table id="tablaClases" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Duración (min.)</th>
                                <th>Capacidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clases as $clase): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($clase['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($clase['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($clase['duracion_minutos']); ?></td>
                                <td><?php echo htmlspecialchars($clase['capacidad_maxima']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-editar-clase" 
                                        data-bs-toggle="modal" data-bs-target="#editarClaseModal" 
                                        data-id="<?php echo htmlspecialchars($clase['id_clase']); ?>" 
                                        data-nombre="<?php echo htmlspecialchars($clase['nombre']); ?>" 
                                        data-descripcion="<?php echo htmlspecialchars($clase['descripcion']); ?>" 
                                        data-duracion_minutos="<?php echo htmlspecialchars($clase['duracion_minutos']); ?>" 
                                        data-capacidad_maxima="<?php echo htmlspecialchars($clase['capacidad_maxima']); ?>">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>

                                    <?php if ($es_admin): ?>  
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-clase" 
                                        data-id="<?php echo htmlspecialchars($clase['id_clase']); ?>"
                                        data-nombre="<?php echo htmlspecialchars($clase['nombre']); ?>">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </button>
                                    <?php endif; ?>

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay clases registradas.</p>
                <?php endif; ?>

            
            <?php elseif ($seccion === 'registrar_clase'): // ------------------ SECCIÓN REGISTRAR CLASE ------------------ ?>
                
                <h2>Registrar Nueva Clase</h2>
                <form id="formCrearClase" action="clases.php" method="POST">
                    <input type="hidden" name="accion" value="crear_clase">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <input type="text" id="descripcion" name="descripcion" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="duracion_minutos" class="form-label">Duración (Minutos):</label>
                        <input type="number" id="duracion_minutos" name="duracion_minutos" class="form-control" required min="10">
                    </div>
                    <div class="mb-3">
                        <label for="capacidad_maxima" class="form-label">Capacidad Máxima:</label>
                        <input type="number" id="capacidad_maxima" name="capacidad_maxima" class="form-control" required min="1">
                    </div>
                    <button type="submit" class="btn btn-primary">Crear clase</button>
                </form>


            <?php elseif ($seccion === 'horarios'): // ------------------ SECCIÓN HORARIOS ------------------ ?>
                
                <h2>Horarios de Clases Programadas</h2>
                <?php if (!empty($horarios_clases)): ?>
                    <table id="tablaHorarios" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Clase</th>
                                <th>Instructor</th>
                                <th>Día</th>
                                <th>Hora de inicio</th>
                                <th>Salón</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($horarios_clases as $horario_clase): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($horario_clase['nombre_clase']); ?></td>
                                <td><?php echo htmlspecialchars($horario_clase['nombre_instructor']); ?></td>
                                <td><?php echo htmlspecialchars($horario_clase['dia_semana']); ?></td>
                                <td><?php echo htmlspecialchars(substr($horario_clase['hora_inicio'], 0, 5)); ?></td> 
                                <td><?php echo htmlspecialchars($horario_clase['salon']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-editar-horario" 
                                        data-bs-toggle="modal" data-bs-target="#editarHorarioModal" 
                                        data-id="<?php echo htmlspecialchars($horario_clase['id_horario_clase']); ?>" 
                                        data-id_clase="<?php echo htmlspecialchars($horario_clase['id_clase']); ?>" 
                                        data-id_instructor="<?php echo htmlspecialchars($horario_clase['id_instructor']); ?>" 
                                        data-dia_semana="<?php echo htmlspecialchars($horario_clase['dia_semana']); ?>" 
                                        data-hora_inicio="<?php echo htmlspecialchars(substr($horario_clase['hora_inicio'], 0, 5)); ?>"
                                        data-salon="<?php echo htmlspecialchars($horario_clase['salon']); ?>">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>

                                    <?php if ($es_admin): ?>  
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-horario" 
                                        data-id="<?php echo htmlspecialchars($horario_clase['id_horario_clase']); ?>"
                                        data-clase="<?php echo htmlspecialchars($horario_clase['nombre_clase']); ?>"
                                        data-dia="<?php echo htmlspecialchars($horario_clase['dia_semana']); ?>"
                                        data-hora="<?php echo htmlspecialchars(substr($horario_clase['hora_inicio'], 0, 5)); ?>">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </button>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-success btn-sm btn-inscribir-miembro" 
                                        data-bs-toggle="modal" data-bs-target="#inscribirMiembroModal" 
                                        data-id_horario_clase="<?php echo htmlspecialchars($horario_clase['id_horario_clase']); ?>">
                                        <i class="bi bi-person-plus-fill"></i> Inscribir Miembro
                                    </button>
 
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay horarios registrados.</p>
                <?php endif; ?>

            <?php elseif ($seccion === 'agendar'): // ------------------ SECCIÓN AGENDAR NUEVO HORARIO ------------------ ?>
                
                <h2>Agendar Nuevo Horario de Clase</h2>
                <form id="formCrearHorario" method="POST" action="clases.php">
                    <input type="hidden" name="accion" value="crear_horario">
                    
                    <div class="mb-3">
                        <label for="id_clase" class="form-label">Clase:</label>
                        <select name="id_clase" id="id_clase" class="form-control" required>
                             <option value="">-- Seleccione una clase --</option>
                            <?php foreach ($todas_las_clases as $clase_select): ?>
                                <option value="<?php echo htmlspecialchars($clase_select['id_clase']); ?>"><?php echo htmlspecialchars($clase_select['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="id_instructor" class="form-label">Instructor:</label>
                        <select name="id_instructor" id="id_instructor" class="form-control" required>
                             <option value="">-- Seleccione un instructor --</option>
                            <?php foreach ($todos_los_instructores as $inst_select): 
                                $nombreCompleto = htmlspecialchars($inst_select['nombre'] . " " . $inst_select['apellido']);
                            ?>
                                <option value="<?php echo htmlspecialchars($inst_select['id_instructor']); ?>"><?php echo $nombreCompleto; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="dia_semana" class="form-label">Día de la semana:</label>
                        <select name="dia_semana" id="dia_semana" class="form-control" required>
                            <option value="">-- Seleccione un día --</option>
                            <?php $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']; ?>
                            <?php foreach ($dias as $dia): ?>
                                <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="hora_inicio" class="form-label">Hora de inicio:</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="salon" class="form-label">Salón:</label>
                        <select name="salon" id="salon" class="form-control" required>
                            <option value="">-- Seleccione un salón --</option>
                            <option value="Salon A">Salón A</option>
                            <option value="Salon B">Salón B</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Agregar Horario</button>
                </form>

            <?php elseif ($seccion === 'inscripciones'): // ------------------ SECCIÓN INSCRIPCIONES ------------------ ?>
                
                <h2>Gestión de Inscripciones</h2>
                
                <?php if (!empty($inscripciones)): ?>
                    <div class="table-responsive">
                        <table id="tablaInscripciones" class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Miembro</th>
                                    <th>Clase</th>
                                    <th>Instructor</th>
                                    <th>Día</th>
                                    <th>Hora</th>
                                    <th>Salón</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscripciones as $inscripcion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inscripcion['nombre_miembro'] . ' ' . $inscripcion['apellido_miembro']); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['nombre_clase']); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['nombre_instructor'] . ' ' . $inscripcion['apellido_instructor']); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['dia_semana']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($inscripcion['hora_inicio'], 0, 5)); ?></td>
                                    <td><?php echo htmlspecialchars($inscripcion['salon']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm btn-desinscribir" 
                                            data-id_miembro="<?php echo htmlspecialchars($inscripcion['id_miembro']); ?>"
                                            data-id_horario_clase="<?php echo htmlspecialchars($inscripcion['id_horario_clase']); ?>"
                                            data-miembro="<?php echo htmlspecialchars($inscripcion['nombre_miembro'] . ' ' . $inscripcion['apellido_miembro']); ?>"
                                            data-clase="<?php echo htmlspecialchars($inscripcion['nombre_clase']); ?>">
                                            <i class="bi bi-person-dash-fill"></i> Desinscribir
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No hay inscripciones activas en este momento.
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
    
    <div class="modal fade" id="editarClaseModal" tabindex="-1" aria-labelledby="editarClaseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarClaseModalLabel">Editar Clase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarClase" action="clases.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar_clase">
                        <input type="hidden" name="id_clase" id="editar_id_clase">
                        <div class="mb-3">
                            <label for="editar_nombre" class="form-label">Nombre:</label>
                            <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_descripcion" class="form-label">Descripción:</label>
                            <input type="text" id="editar_descripcion" name="descripcion" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_duracion_minutos" class="form-label">Duración (Minutos):</label>
                            <input type="number" id="editar_duracion_minutos" name="duracion_minutos" class="form-control" required min="10">
                        </div>
                        <div class="mb-3">
                            <label for="editar_capacidad_maxima" class="form-label">Capacidad Máxima:</label>
                            <input type="number" id="editar_capacidad_maxima" name="capacidad_maxima" class="form-control" required min="1">
                        </div> 
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editarHorarioModal" tabindex="-1" aria-labelledby="editarHorarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarHorarioModalLabel">Editar Horario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarHorario" action="clases.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar_horario">
                        <input type="hidden" name="id_horario_clase" id="editar_id_horario_clase">

                        <div class="mb-3">
                            <label for="editar_id_clase" class="form-label">Clase:</label>
                            <select name="id_clase" id="editar_id_clase" class="form-control" required>
                                <?php foreach ($todas_las_clases as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['id_clase']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editar_id_instructor" class="form-label">Instructor:</label>
                            <select name="id_instructor" id="editar_id_instructor" class="form-control" required>
                                <?php foreach ($todos_los_instructores as $i): 
                                    $nombreCompleto = htmlspecialchars($i['nombre'] . " " . $i['apellido']);
                                ?>
                                    <option value="<?php echo htmlspecialchars($i['id_instructor']); ?>"><?php echo $nombreCompleto; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editar_dia_semana" class="form-label">Día de la semana:</label>
                            <select name="dia_semana" id="editar_dia_semana" class="form-control" required>
                                <?php $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']; ?>
                                <?php foreach ($dias as $dia): ?>
                                    <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editar_hora_inicio" class="form-label">Hora de inicio:</label>
                            <input type="time" name="hora_inicio" id="editar_hora_inicio" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_salon" class="form-label">Salón:</label>
                            <select name="salon" id="editar_salon" class="form-control" required>
                                <option value="Salon A">Salón A</option>
                                <option value="Salon B">Salón B</option>
                            </select>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inscribirMiembroModal" tabindex="-1" aria-labelledby="inscribirMiembroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inscribirMiembroModalLabel">Inscribir Miembro a Clase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formInscribirMiembro" action="clases.php" method="POST">
                        <input type="hidden" name="accion" value="inscribir_miembro">
                        <input type="hidden" name="id_horario_clase" id="inscribir_id_horario_clase">

                        <p>Clase: <strong id="info_nombre_clase"></strong></p>
                        <p>Cupo Disponible: <span id="info_cupo_disponible"></span> / <span id="info_capacidad_maxima"></span></p>

                        <div class="mb-3">
                            <label for="inscribir_id_miembro" class="form-label">Seleccionar Miembro:</label>
                            <select name="id_miembro" id="inscribir_id_miembro" class="form-control" required>
                                <option value="">-- Seleccione un miembro --</option>
                                <?php foreach ($todos_los_miembros as $m): 
                                    $nombreCompleto = htmlspecialchars($m['nombre'] . " " . $m['apellido']);
                                ?>
                                    <option value="<?php echo htmlspecialchars($m['id_miembro']); ?>"><?php echo $nombreCompleto; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-success">Inscribir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SWEETALERT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            
            // Inicializar DataTables solo si las tablas existen
            if ($('#tablaClases').length) {
                $('#tablaClases').DataTable({
                    "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' }
                });
            }
            if ($('#tablaHorarios').length) {
                $('#tablaHorarios').DataTable({
                    "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' }
                });
            }
            if ($('#tablaInscripciones').length) {
                $('#tablaInscripciones').DataTable({
                    "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' }
                });
            }

            // Lógica para cargar datos en el modal de edición HORARIOS
            $('.btn-editar-horario').on('click', function() {
                $('#editar_id_horario_clase').val($(this).data('id'));
                $('#editar_id_clase').val($(this).data('id_clase'));
                $('#editar_id_instructor').val($(this).data('id_instructor'));
                $('#editar_dia_semana').val($(this).data('dia_semana'));
                $('#editar_hora_inicio').val($(this).data('hora_inicio'));
                $('#editar_salon').val($(this).data('salon')); 
            });

            // Lógica para cargar datos en el modal de edición CLASES
            $('.btn-editar-clase').on('click', function() {
                $('#editar_id_clase').val($(this).data('id')); 
                $('#editar_nombre').val($(this).data('nombre')); 
                $('#editar_descripcion').val($(this).data('descripcion')); 
                $('#editar_duracion_minutos').val($(this).data('duracion_minutos')); 
                $('#editar_capacidad_maxima').val($(this).data('capacidad_maxima')); 
            });

            // Lógica para cargar datos en el modal de inscripción de miembros
            $('.btn-inscribir-miembro').on('click', function() {
                var idHorario = $(this).data('id_horario_clase');
                
                $('#inscribir_id_horario_clase').val(idHorario);

                $.ajax({
                    url: 'ajax_clases.php',
                    type: 'GET',
                    data: { 
                        accion: 'obtener_cupo',
                        id_horario_clase: idHorario 
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data) {
                            $('#info_nombre_clase').text(data.nombre_clase);
                            $('#info_capacidad_maxima').text(data.capacidad_maxima);
                            
                            var cupoDisp = parseInt(data.cupo_disponible);
                            $('#info_cupo_disponible').text(cupoDisp);
                            
                            if (cupoDisp <= 0) {
                                $('#info_cupo_disponible').css('color', 'red');
                                $('#inscribir_id_miembro').prop('disabled', true);
                                $('#formInscribirMiembro button[type="submit"]').prop('disabled', true).text('Clase Llena');
                            } else {
                                $('#info_cupo_disponible').css('color', 'green');
                                $('#inscribir_id_miembro').prop('disabled', false);
                                $('#formInscribirMiembro button[type="submit"]').prop('disabled', false).text('Inscribir');
                            }
                        } else {
                            $('#info_nombre_clase').text("Error al cargar");
                            $('#info_cupo_disponible').text("N/A");
                            $('#info_capacidad_maxima').text("N/A");
                            $('#inscribir_id_miembro').prop('disabled', true);
                            $('#formInscribirMiembro button[type="submit"]').prop('disabled', true).text('Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: " + status + error);
                        alert("Hubo un error al cargar la información del cupo.");
                    }
                });
            });

            // =========================================================================
            // ALERTAS SWEETALERT2 PARA CLASES Y HORARIOS
            // =========================================================================

            // Confirmación para eliminar clase
            $(document).on('click', '.btn-eliminar-clase', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: '¿Eliminar Clase?',
                    html: `<div class="text-center">
                           <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres eliminar la clase <strong>"${nombre}"</strong>?</p>
                           <p class="text-muted small">Esta acción no se puede deshacer y afectará los horarios asociados.</p>
                           </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash-fill"></i> Sí, eliminar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'clases.php';
                        
                        const accionInput = document.createElement('input');
                        accionInput.type = 'hidden';
                        accionInput.name = 'accion';
                        accionInput.value = 'eliminar_clase';
                        
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id_clase';
                        idInput.value = id;
                        
                        form.appendChild(accionInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            // Confirmación para eliminar horario
            $(document).on('click', '.btn-eliminar-horario', function() {
                const id = $(this).data('id');
                const clase = $(this).data('clase');
                const dia = $(this).data('dia');
                const hora = $(this).data('hora');
                
                Swal.fire({
                    title: '¿Eliminar Horario?',
                    html: `<div class="text-center">
                           <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres eliminar el horario de <strong>"${clase}"</strong>?</p>
                           <p class="mb-1"><strong>Día:</strong> ${dia}</p>
                           <p class="mb-1"><strong>Hora:</strong> ${hora}</p>
                           <p class="text-muted small">Esta acción no se puede deshacer.</p>
                           </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash-fill"></i> Sí, eliminar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'clases.php';
                        
                        const accionInput = document.createElement('input');
                        accionInput.type = 'hidden';
                        accionInput.name = 'accion';
                        accionInput.value = 'eliminar_horario';
                        
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id_horario_clase';
                        idInput.value = id;
                        
                        form.appendChild(accionInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            // Confirmación para desinscribir miembro
            $(document).on('click', '.btn-desinscribir', function() {
                const idMiembro = $(this).data('id_miembro');
                const idHorarioClase = $(this).data('id_horario_clase');
                const miembro = $(this).data('miembro');
                const clase = $(this).data('clase');
                
                Swal.fire({
                    title: '¿Desinscribir Miembro?',
                    html: `<div class="text-center">
                           <i class="bi bi-person-dash text-danger fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres desinscribir a <strong>"${miembro}"</strong> de la clase <strong>"${clase}"</strong>?</p>
                           <p class="text-muted small">El miembro perderá su lugar en esta clase.</p>
                           </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-person-dash-fill"></i> Sí, desinscribir',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-danger mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'clases.php';
                        
                        const accionInput = document.createElement('input');
                        accionInput.type = 'hidden';
                        accionInput.name = 'accion';
                        accionInput.value = 'desinscribir_miembro';
                        
                        const idMiembroInput = document.createElement('input');
                        idMiembroInput.type = 'hidden';
                        idMiembroInput.name = 'id_miembro';
                        idMiembroInput.value = idMiembro;
                        
                        const idHorarioInput = document.createElement('input');
                        idHorarioInput.type = 'hidden';
                        idHorarioInput.name = 'id_horario_clase';
                        idHorarioInput.value = idHorarioClase;
                        
                        form.appendChild(accionInput);
                        form.appendChild(idMiembroInput);
                        form.appendChild(idHorarioInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            // Confirmación para crear clase
            $('#formCrearClase').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const nombre = $('#nombre').val();
                const capacidad = $('#capacidad_maxima').val();
                const duracion = $('#duracion_minutos').val();
                
                Swal.fire({
                    title: '¿Crear Nueva Clase?',
                    html: `<div class="text-center">
                           <i class="bi bi-plus-circle text-primary fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres crear la clase <strong>"${nombre}"</strong>?</p>
                           <p class="mb-1"><strong>Duración:</strong> ${duracion} minutos</p>
                           <p class="mb-1"><strong>Capacidad:</strong> ${capacidad} personas</p>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-check-lg"></i> Sí, crear',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-primary mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Confirmación para crear horario
            $('#formCrearHorario').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const claseSelect = document.getElementById('id_clase');
                const claseNombre = claseSelect.options[claseSelect.selectedIndex].text;
                const dia = $('#dia_semana').val();
                const hora = $('#hora_inicio').val();
                const salon = $('#salon').val();
                
                Swal.fire({
                    title: '¿Crear Nuevo Horario?',
                    html: `<div class="text-center">
                           <i class="bi bi-calendar-plus text-primary fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres crear el horario para <strong>"${claseNombre}"</strong>?</p>
                           <p class="mb-1"><strong>Día:</strong> ${dia}</p>
                           <p class="mb-1"><strong>Hora:</strong> ${hora}</p>
                           <p class="mb-1"><strong>Salón:</strong> ${salon}</p>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-check-lg"></i> Sí, crear',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-primary mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Confirmación para editar clase
            $('#formEditarClase').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const nombre = $('#editar_nombre').val();
                const capacidad = $('#editar_capacidad_maxima').val();
                const duracion = $('#editar_duracion_minutos').val();
                
                Swal.fire({
                    title: '¿Guardar Cambios?',
                    html: `<div class="text-center">
                           <i class="bi bi-pencil-square text-warning fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres guardar los cambios en la clase <strong>"${nombre}"</strong>?</p>
                           <p class="mb-1"><strong>Nueva duración:</strong> ${duracion} minutos</p>
                           <p class="mb-1"><strong>Nueva capacidad:</strong> ${capacidad} personas</p>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-check-lg"></i> Sí, guardar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-warning mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Confirmación para editar horario
            $('#formEditarHorario').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const claseSelect = document.getElementById('editar_id_clase');
                const claseNombre = claseSelect.options[claseSelect.selectedIndex].text;
                const dia = $('#editar_dia_semana').val();
                const hora = $('#editar_hora_inicio').val();
                const salon = $('#editar_salon').val();
                
                Swal.fire({
                    title: '¿Guardar Cambios?',
                    html: `<div class="text-center">
                           <i class="bi bi-pencil-square text-warning fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres guardar los cambios en el horario de <strong>"${claseNombre}"</strong>?</p>
                           <p class="mb-1"><strong>Nuevo día:</strong> ${dia}</p>
                           <p class="mb-1"><strong>Nueva hora:</strong> ${hora}</p>
                           <p class="mb-1"><strong>Nuevo salón:</strong> ${salon}</p>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-check-lg"></i> Sí, guardar',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    reverseButtons: false,
                    customClass: {
                        confirmButton: 'btn btn-warning mx-2',
                        cancelButton: 'btn btn-secondary mx-2'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Mostrar alerta de éxito si hay parámetro en la URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('exito')) {
                let mensaje = '';
                let titulo = '¡Éxito!';
                
                switch(urlParams.get('exito')) {
                    case 'crear_clase':
                        mensaje = 'La clase se creó correctamente.';
                        break;
                    case 'editar_clase':
                        mensaje = 'Los cambios en la clase se guardaron correctamente.';
                        break;
                    case 'eliminar_clase':
                        mensaje = 'La clase se eliminó correctamente.';
                        break;
                    case 'crear_horario':
                        mensaje = 'El horario se creó correctamente.';
                        break;
                    case 'editar_horario':
                        mensaje = 'Los cambios en el horario se guardaron correctamente.';
                        break;
                    case 'eliminar_horario':
                        mensaje = 'El horario se eliminó correctamente.';
                        break;
                    default:
                        mensaje = 'La operación se realizó correctamente.';
                }
                
                Swal.fire({
                    title: titulo,
                    text: mensaje,
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    timer: 3000,
                    timerProgressBar: true
                });
            }

        });
    </script>

    
</body>
</html>