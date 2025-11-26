<?php
session_start();
// Incluye tu archivo de conexión y las funciones
require_once '../inc/conexion.php';
require_once '../funciones/usuarios_funciones.php';

// Manejar peticiones POST (Crear, Actualizar, Eliminar, Cambiar Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        if ($accion === 'crear') {
            crearUsuario($conn, $_POST['usuario'], $_POST['password'], $_POST['rol']);
            header("Location: usuarios.php?exito=crear");
        } elseif ($accion === 'actualizar') {
            $nueva_password = !empty($_POST['nueva_password']) ? $_POST['nueva_password'] : null;
            
            // Validar que las contraseñas coincidan si se está intentando cambiar
            if ($nueva_password && $nueva_password !== $_POST['confirmar_password']) {
                header("Location: usuarios.php?error=password_no_coincide");
            } else {
                actualizarUsuario($conn, $_POST['id_usuario'], $_POST['usuario'], $_POST['rol'], $nueva_password);
                header("Location: usuarios.php?exito=editar");
            }
        } elseif ($accion === 'eliminar') {
            eliminarUsuario($conn, $_POST['id_usuario']);
            header("Location: usuarios.php?exito=eliminar");
        }
    }
    exit();
}
 
// Determinar la sección actual desde el parámetro GET
$seccion = $_GET['seccion'] ?? 'registrados';

// Obtener los datos necesarios para la sección actual
$usuarios = [];
$movimientos = []; // Inicializamos el arreglo de movimientos
if ($seccion === 'registrados') {
    $usuarios = obtenerUsuarios($conn);
} elseif ($seccion === 'movimientos') {
    // Lógica para obtener movimientos (implementada)
    $movimientos = obtenerMovimientos($conn); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- SWEETALERT2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_usuarios.css">
    
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
        <a href="usuarios.php?seccion=registrados" class="menu-btn <?= ($seccion === 'registrados' ? 'active' : ''); ?>">Usuarios registrados</a>
        <a href="usuarios.php?seccion=registrar" class="menu-btn <?= ($seccion === 'registrar' ? 'active' : ''); ?>">Registrar usuario</a>
        <a href="usuarios.php?seccion=movimientos" class="menu-btn <?= ($seccion === 'movimientos' ? 'active' : ''); ?>">Movimientos del sistema</a>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            <h1>Gestión de Usuarios</h1>
            <hr>

            <?php if ($seccion === 'registrados'): ?>
                <h2>Lista de Usuarios</h2>
                <?php if (count($usuarios) > 0): ?>
                    <table id="tablaUsuarios" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-editar" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editarModal" 
                                        data-id="<?php echo htmlspecialchars($usuario['id_usuario']); ?>" 
                                        data-usuario="<?php echo htmlspecialchars($usuario['usuario']); ?>" 
                                        data-rol="<?php echo htmlspecialchars($usuario['rol']); ?>">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>
                                    
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                        data-id="<?php echo htmlspecialchars($usuario['id_usuario']); ?>"
                                        data-usuario="<?php echo htmlspecialchars($usuario['usuario']); ?>">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay usuarios registrados.</p>
                <?php endif; ?>

            <?php elseif ($seccion === 'registrar'): ?>
                <h2>Registrar Nuevo Usuario</h2>
                <form id="formCrear" action="usuarios.php" method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario:</label>
                        <input type="text" id="usuario" name="usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña:</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol:</label>
                        <select id="rol" name="rol" class="form-select">
                            <option value="usuario">Usuario</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </form>
                
            <?php elseif ($seccion === 'movimientos'): ?>
                <h2>Historial de Movimientos en el sistema</h2>
                <?php if (count($movimientos) > 0): ?>
                    <table id="tablaMovimientos" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Tipo de Movimiento</th>
                                <th>Persona/Equipo Afectado</th>
                                <th>Detalle de la Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($movimiento['fecha_hora']); ?></td>
                                <td><?php echo htmlspecialchars($movimiento['tipo_movimiento']); ?></td>
                                <td><?php echo htmlspecialchars($movimiento['persona_o_equipo_afectado']); ?></td> 
                                <td><?php echo htmlspecialchars($movimiento['descripcion_accion']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No se encontraron movimientos registrados.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para Editar Usuario (INCLUYE OPCIÓN DE CONTRASEÑA) -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditar" action="usuarios.php" method="POST">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_usuario" id="editar_id_usuario">
                        
                        <div class="mb-3">
                            <label for="editar_usuario" class="form-label">Usuario:</label>
                            <input type="text" id="editar_usuario" name="usuario" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editar_rol" class="form-label">Rol:</label>
                            <select id="editar_rol" name="rol" class="form-select">
                                <option value="usuario">Usuario</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <!-- NUEVA SECCIÓN: Cambio de contraseña (opcional) -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cambiar_password_switch">
                                <label class="form-check-label" for="cambiar_password_switch">
                                    <strong>Cambiar contraseña</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div id="password_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="nueva_password" class="form-label">Nueva Contraseña:</label>
                                <input type="password" id="nueva_password" name="nueva_password" class="form-control " 
                                       minlength="6" placeholder="Mínimo 6 caracteres">
                                <div class="form-text text-white">Dejar en blanco si no quieres cambiar la contraseña.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmar_password" class="form-label">Confirmar Contraseña:</label>
                                <input type="password" id="confirmar_password" name="confirmar_password" class="form-control">
                                <div class="invalid-feedback" id="password-error">Las contraseñas no coinciden.</div>
                            </div>
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
            // Inicializar DataTables solo si la tabla de usuarios existe en la página
            if ($('#tablaUsuarios').length) {
                $('#tablaUsuarios').DataTable({
                    "language": {
                         url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                    }
                });
            }
            if ($('#tablaMovimientos').length) {
                $('#tablaMovimientos').DataTable({
                    "order": [[0, "desc"]], // Ordenar por fecha y hora descendente por defecto
                    "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' }
                });
            }

            // =========================================================================
            // LÓGICA PARA EL MODAL DE EDICIÓN Y CONTRASEÑA
            // =========================================================================

            // Lógica para mostrar y cargar datos en el modal de edición
            $('.btn-editar').on('click', function() {
                var id = $(this).data('id');
                var usuario = $(this).data('usuario');
                var rol = $(this).data('rol');

                // Llenar los campos del modal con los datos del usuario
                $('#editar_id_usuario').val(id);
                $('#editar_usuario').val(usuario);
                $('#editar_rol').val(rol);
            });

            // Toggle para mostrar/ocultar campos de contraseña
            $('#cambiar_password_switch').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#password_fields').slideDown();
                    $('#nueva_password').prop('required', true);
                    $('#confirmar_password').prop('required', true);
                } else {
                    $('#password_fields').slideUp();
                    $('#nueva_password').prop('required', false);
                    $('#confirmar_password').prop('required', false);
                    $('#nueva_password').val('');
                    $('#confirmar_password').val('');
                    $('#confirmar_password').removeClass('is-invalid');
                }
            });

            // Validación en tiempo real de contraseñas
            $('#confirmar_password').on('input', function() {
                const nuevaPassword = $('#nueva_password').val();
                const confirmarPassword = $(this).val();
                
                if (confirmarPassword !== '' && nuevaPassword !== confirmarPassword) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Al abrir el modal, resetear el switch de contraseña
            $('#editarModal').on('show.bs.modal', function() {
                $('#cambiar_password_switch').prop('checked', false);
                $('#password_fields').hide();
                $('#nueva_password').val('');
                $('#confirmar_password').val('');
                $('#confirmar_password').removeClass('is-invalid');
            });

            // =========================================================================
            // ALERTAS SWEETALERT2 PARA USUARIOS
            // =========================================================================

            // Confirmación para eliminar usuario
            $(document).on('click', '.btn-eliminar', function() {
                const id = $(this).data('id');
                const usuario = $(this).data('usuario');
                
                Swal.fire({
                    title: '¿Eliminar Usuario?',
                    html: `<div class="text-center">
                           <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres eliminar al usuario <strong>"${usuario}"</strong>?</p>
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
                        form.action = 'usuarios.php';
                        
                        const accionInput = document.createElement('input');
                        accionInput.type = 'hidden';
                        accionInput.name = 'accion';
                        accionInput.value = 'eliminar';
                        
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'id_usuario';
                        idInput.value = id;
                        
                        form.appendChild(accionInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            // Confirmación para crear usuario
            $('#formCrear').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const usuario = $('#usuario').val();
                const rol = $('#rol').val();
                
                Swal.fire({
                    title: '¿Crear Nuevo Usuario?',
                    html: `<div class="text-center">
                           <i class="bi bi-person-plus text-primary fs-1"></i>
                           <p class="mt-3">¿Estás seguro de que quieres crear el usuario <strong>"${usuario}"</strong>?</p>
                           <p class="mb-1"><strong>Rol:</strong> ${rol}</p>
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

            // Confirmación para editar usuario
            $('#formEditar').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const usuario = $('#editar_usuario').val();
                const rol = $('#editar_rol').val();
                const cambiarPassword = $('#cambiar_password_switch').is(':checked');
                const nuevaPassword = $('#nueva_password').val();
                
                // Validar contraseñas si se está intentando cambiar
                if (cambiarPassword) {
                    const confirmarPassword = $('#confirmar_password').val();
                    
                    if (nuevaPassword !== confirmarPassword) {
                        Swal.fire({
                            title: 'Error',
                            text: 'Las contraseñas no coinciden. Por favor, verifica.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                        return;
                    }
                    
                    if (nuevaPassword.length < 6) {
                        Swal.fire({
                            title: 'Error',
                            text: 'La contraseña debe tener al menos 6 caracteres.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                        return;
                    }
                }
                
                let mensajeConfirmacion = `<div class="text-start">
                    <i class="bi bi-pencil-square text-warning fs-1"></i>
                    <p class="mt-3">¿Estás seguro de que quieres guardar los cambios en el usuario <strong>"${usuario}"</strong>?</p>
                    <p class="mb-1"><strong>Nuevo rol:</strong> ${rol}</p>`;
                
                if (cambiarPassword && nuevaPassword) {
                    mensajeConfirmacion += `<p class="mb-1 text-info"><strong>Se cambiará la contraseña</strong></p>`;
                }
                
                mensajeConfirmacion += `</div>`;
                
                Swal.fire({
                    title: '¿Guardar Cambios?',
                    html: mensajeConfirmacion,
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
                        mensaje = 'El usuario se creó correctamente.';
                        break;
                    case 'editar':
                        mensaje = 'Los cambios se guardaron correctamente.';
                        break;
                    case 'eliminar':
                        mensaje = 'El usuario se eliminó correctamente.';
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

            // Manejar error de contraseñas no coincidentes
            if (urlParams.get('error') === 'password_no_coincide') {
                Swal.fire({
                    title: 'Error',
                    text: 'Las contraseñas no coinciden. Por favor, inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    </script>
</body>
</html>