<?php
session_start();
// Incluye tu archivo de conexión y las funciones
require_once '../inc/conexion.php';
require_once '../funciones/instructores_funciones.php';
include('../inc/seguridad.php');

// Manejar peticiones POST (Crear, Actualizar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        if ($accion === 'crear') {
            crearInstructor($conn, $_POST['nombre'], $_POST['apellido'], $_POST['telefono'], $_POST['correo'], $_POST['especialidad'], $_POST['fecha_contratacion']);
            header("Location: instructores.php?exito=crear");
        } elseif ($accion === 'actualizar') {
            actualizarInstructor($conn, $_POST['id_instructor'], $_POST['nombre'], $_POST['apellido'], $_POST['telefono'], $_POST['correo'], $_POST['especialidad'], $_POST['fecha_contratacion']);
            header("Location: instructores.php?exito=editar");
        } elseif ($accion === 'eliminar') {
            eliminarInstructor($conn, $_POST['id_instructor']);
            header("Location: instructores.php?exito=eliminar");
        }
    }
    exit();
}

// Sección actual (por defecto 'registrados')
$seccion = $_GET['seccion'] ?? 'registrados';

// Obtener datos de los instructores
$instructores = [];
if ($seccion === 'registrados') {
    $instructores = obtenerInstructores($conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de instructores</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- SWEETALERT2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/style_instructores.css">
    
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SWEETALERT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_menu.css">
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>
    
    <div class="menu-superior">
        <a href="instructores.php?seccion=registrados" class="menu-btn <?= ($seccion === 'registrados' ? 'active' : ''); ?>">Instructores registrados</a>
        <a href="instructores.php?seccion=registrar" class="menu-btn <?= ($seccion === 'registrar' ? 'active' : ''); ?>">Registrar instructor</a>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            <h1>Gestión de Instructores</h1>
            <hr>

            <?php if ($seccion === 'registrados'): ?>
                <h2>Lista de Instructores</h2>
                <?php if (count($instructores) > 0): ?>
                    <table id="tablaInstructores" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Especialidad</th>
                                <th>Fecha Contratación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructores as $instructor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($instructor['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['correo']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['especialidad']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['fecha_contratacion']); ?></td>

                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-editar" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editarModal" 
                                        data-id="<?php echo htmlspecialchars($instructor['id_instructor']); ?>" 
                                        data-nombre="<?php echo htmlspecialchars($instructor['nombre']); ?>" 
                                        data-apellido="<?php echo htmlspecialchars($instructor['apellido']); ?>" 
                                        data-telefono="<?php echo htmlspecialchars($instructor['telefono']); ?>" 
                                        data-correo="<?php echo htmlspecialchars($instructor['correo']); ?>" 
                                        data-especialidad="<?php echo htmlspecialchars($instructor['especialidad']); ?>" 
                                        data-fecha_contratacion="<?php echo htmlspecialchars($instructor['fecha_contratacion']); ?>">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>

                                    <?php if ($es_admin): ?>  
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                        data-id="<?php echo htmlspecialchars($instructor['id_instructor']); ?>"
                                        data-nombre="<?php echo htmlspecialchars($instructor['nombre']); ?>"
                                        data-apellido="<?php echo htmlspecialchars($instructor['apellido']); ?>">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </button>
                                    <?php endif; ?>

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay instructores registrados.</p>
                <?php endif; ?> 

            <?php elseif ($seccion === 'registrar'): ?>
                <h2>Registrar Nuevo Instructor</h2>
                <form id="formCrear" action="instructores.php" method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellido" class="form-label">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo:</label>
                        <input type="email" id="correo" name="correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="especialidad" class="form-label">Especialidad:</label>
                        <input type="text" id="especialidad" name="especialidad" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_contratacion" class="form-label">Fecha Contratación:</label>
                        <input type="date" id="fecha_contratacion" name="fecha_contratacion" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Instructor</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel">Editar Instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditar" action="instructores.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_instructor" id="editar_id_instructor">
                        <div class="mb-3">
                            <label for="editar_nombre" class="form-label">Nombre:</label>
                            <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_apellido" class="form-label">Apellido:</label>
                            <input type="text" id="editar_apellido" name="apellido" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_telefono" class="form-label">Teléfono:</label>
                            <input type="tel" id="editar_telefono" name="telefono" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_correo" class="form-label">Correo:</label>
                            <input type="email" id="editar_correo" name="correo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_especialidad" class="form-label">Especialidad:</label>
                            <input type="text" id="editar_especialidad" name="especialidad" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_fecha_contratacion" class="form-label">Fecha Contratación:</label>
                            <input type="date" id="editar_fecha_contratacion" name="fecha_contratacion" class="form-control">
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

    <script>
        $(document).ready(function() {
            // Inicializar DataTables solo si la tabla de instructores existe en la página
            if ($('#tablaInstructores').length) {
                $('#tablaInstructores').DataTable({
                    "language": {
                         url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                    }
                });
            }

            // Lógica para mostrar y cargar datos en el modal de edición
            $('.btn-editar').on('click', function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                var apellido = $(this).data('apellido');
                var telefono = $(this).data('telefono');
                var correo = $(this).data('correo');
                var especialidad = $(this).data('especialidad');
                var fecha_contratacion = $(this).data('fecha_contratacion');

                $('#editar_id_instructor').val(id);
                $('#editar_nombre').val(nombre);
                $('#editar_apellido').val(apellido);
                $('#editar_telefono').val(telefono);
                $('#editar_correo').val(correo);
                $('#editar_especialidad').val(especialidad);
                $('#editar_fecha_contratacion').val(fecha_contratacion);
            });

            // =========================================================================
            // ALERTAS SWEETALERT2 PARA INSTRUCTORES
            // =========================================================================

            // Confirmación para eliminar instructor
            $(document).on('click', '.btn-eliminar', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');
                const apellido = $(this).data('apellido');
                const nombreCompleto = `${nombre} ${apellido}`;
                
                Swal.fire({
                    title: '¿Eliminar Instructor?',
                    html: `<div class="text-center">
                           <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres eliminar al instructor <strong>"${nombreCompleto}"</strong>?</p>
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
                        // Crear formulario dinámico y enviar
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'instructores.php';
                        
                        const accionInput = document.createElement('input');
                        accionInput.type = 'hidden';
                        accionInput.name = 'accion';
                        accionInput.value = 'eliminar';
                        
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id_instructor';
                        idInput.value = id;
                        
                        form.appendChild(accionInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            // Confirmación para crear instructor
            $('#formCrear').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const nombre = $('#nombre').val();
                const apellido = $('#apellido').val();
                const nombreCompleto = `${nombre} ${apellido}`;
                const especialidad = $('#especialidad').val();
                
                Swal.fire({
                    title: '¿Crear Nuevo Instructor?',
                    html: `<div class="text-center">
                           <i class="bi bi-person-plus text-primary fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres crear el instructor <strong>"${nombreCompleto}"</strong>?</p>
                           <p class="mb-1"><strong>Especialidad:</strong> ${especialidad}</p>
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

            // Confirmación para editar instructor
            $('#formEditar').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const nombre = $('#editar_nombre').val();
                const apellido = $('#editar_apellido').val();
                const nombreCompleto = `${nombre} ${apellido}`;
                const especialidad = $('#editar_especialidad').val();
                
                Swal.fire({
                    title: '¿Guardar Cambios?',
                    html: `<div class="text-center">
                           <i class="bi bi-pencil-square text-warning fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres guardar los cambios en el instructor <strong>"${nombreCompleto}"</strong>?</p>
                           <p class="mb-1"><strong>Nueva especialidad:</strong> ${especialidad}</p>
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
                    case 'crear':
                        mensaje = 'El instructor se creó correctamente.';
                        break;
                    case 'editar':
                        mensaje = 'Los cambios se guardaron correctamente.';
                        break;
                    case 'eliminar':
                        mensaje = 'El instructor se eliminó correctamente.';
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