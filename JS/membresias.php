<?php
session_start(); 
require_once '../inc/conexion.php';
require_once '../funciones/membresias_funciones.php';

// Manejo de acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        if ($accion === 'crear') {
            crearMembresia($conn, $_POST['nombre'], $_POST['precio'], $_POST['duracion_dias'], $_POST['estado']);
            header("Location: membresias.php?exito=crear");
        } elseif ($accion === 'actualizar') {
            actualizarMembresia($conn, $_POST['id_membresia'], $_POST['nombre'], $_POST['precio'], $_POST['duracion_dias'], $_POST['estado']);
            header("Location: membresias.php?exito=editar");
        } elseif ($accion === 'desactivar') {
            desactivarMembresia($conn, $_POST['id_membresia']);
            header("Location: membresias.php?exito=desactivar");
        } elseif ($accion === 'activar') {
            activarMembresia($conn, $_POST['id_membresia']);
            header("Location: membresias.php?exito=activar");
        }
    }
    exit();
}

// Sección actual
$seccion = $_GET['seccion'] ?? 'registrados';

// Datos
$membresias = [];
if ($seccion === 'registrados') {
    $membresias = obtenerMembresias($conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Membresías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- SWEETALERT2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SWEETALERT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_menu.css">
    <link rel="stylesheet" href="../css/style_membresias.css">
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>
    
    <div class="menu-superior">
        <a href="membresias.php?seccion=registrados" class="menu-btn <?= ($seccion === 'registrados' ? 'active' : ''); ?>">Membresías registradas</a>
        <a href="membresias.php?seccion=registrar" class="menu-btn <?= ($seccion === 'registrar' ? 'active' : ''); ?>">Registrar membresía</a>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            <h1>Gestión de Membresías</h1>
            <hr>

            <?php if ($seccion === 'registrados'): ?>
                <h2 class="section-title">Lista de Membresías</h2>
                <?php if (count($membresias) > 0): ?>
                    <table id="tablaMembresias" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Duración (días)</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($membresias as $membresia): ?>
                            <tr>
                                <td><?= htmlspecialchars($membresia['nombre']); ?></td>
                                <td class="fw-bold text-success">$<?= number_format($membresia['precio'], 2); ?></td>
                                <td><?= htmlspecialchars($membresia['duracion_dias']); ?></td>
                                <td>
                                    <span class="badge <?= $membresia['estado'] == 'activo' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?= htmlspecialchars(ucfirst($membresia['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons-grid">
                                        <button type="button" class="btn btn-warning btn-sm btn-editar" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editarModal" 
                                            data-id="<?= $membresia['id_membresia']; ?>" 
                                            data-nombre="<?= htmlspecialchars($membresia['nombre']); ?>" 
                                            data-precio="<?= htmlspecialchars($membresia['precio']); ?>" 
                                            data-duracion_dias="<?= htmlspecialchars($membresia['duracion_dias']); ?>" 
                                            data-estado="<?= htmlspecialchars($membresia['estado']); ?>">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </button>

                                        <?php if ($membresia['estado'] == 'activo'): ?>
                                            <button type="button" class="btn btn-danger btn-sm btn-desactivar" 
                                                data-id="<?= $membresia['id_membresia']; ?>"
                                                data-nombre="<?= htmlspecialchars($membresia['nombre']); ?>">
                                                <i class="bi bi-pause-fill"></i> Desactivar
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success btn-sm btn-activar" 
                                                data-id="<?= $membresia['id_membresia']; ?>"
                                                data-nombre="<?= htmlspecialchars($membresia['nombre']); ?>">
                                                <i class="bi bi-play-fill"></i> Activar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No hay membresías registradas.</div>
                <?php endif; ?>

            <?php elseif ($seccion === 'registrar'): ?>
                <div class="main-content-form">
                    <h2 class="section-title text-center">Registrar Nueva Membresía</h2>
                    <form id="formCrear" action="membresias.php" method="POST">
                        <input type="hidden" name="accion" value="crear">
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card card-dark-custom">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre:</label>
                                            <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Mensual, Anual, Estudiante">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="precio" class="form-label">Precio:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" id="precio" name="precio" class="form-control" required min="0" placeholder="0.00">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="duracion_dias" class="form-label">Duración (días):</label>
                                                <input type="number" id="duracion_dias" name="duracion_dias" class="form-control" required min="1" placeholder="Ej: 30">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="estado" class="form-label">Estado Inicial:</label>
                                            <select id="estado" name="estado" class="form-select" required>
                                                <option value="activo">Activo</option>
                                                <option value="inactivo">Inactivo</option>
                                            </select>
                                        </div>
                                        
                                        <div class="d-grid gap-2 mt-4">
                                            <button type="submit" class="btn btn-primary btn-lg custom-submit-btn">Crear Membresía</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditar" action="membresias.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarModalLabel">Editar Membresía</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_membresia" id="editar_id_membresia">
                        
                        <div class="mb-3">
                            <label for="editar_nombre" class="form-label">Nombre:</label>
                            <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editar_precio" class="form-label">Precio:</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" id="editar_precio" name="precio" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editar_duracion_dias" class="form-label">Duración (días):</label>
                            <input type="number" id="editar_duracion_dias" name="duracion_dias" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="editar_estado" class="form-label">Estado:</label>
                            <select id="editar_estado" name="estado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
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

    <script>
        $(document).ready(function() {
            // Configuración DataTables
            if ($('#tablaMembresias').length) {
                $('#tablaMembresias').DataTable({
                    "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
                    "order": [[ 1, "asc" ]] // Ordenar por precio
                });
            }

            // Cargar datos al modal editar
            $('.btn-editar').on('click', function() {
                $('#editar_id_membresia').val($(this).data('id'));
                $('#editar_nombre').val($(this).data('nombre'));
                $('#editar_precio').val($(this).data('precio'));
                $('#editar_duracion_dias').val($(this).data('duracion_dias'));
                $('#editar_estado').val($(this).data('estado'));
            });

            // --- SWEETALERTS ---

            // Desactivar
            $(document).on('click', '.btn-desactivar', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: '¿Desactivar Membresía?',
                    html: `<div class="text-center"><i class="bi bi-pause-circle text-warning fs-1"></i><p class="mt-3">¿Desactivar <strong>"${nombre}"</strong>?</p><p class="text-muted small">No estará disponible para nuevos registros.</p></div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, desactivar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST'; form.action = 'membresias.php';
                        form.innerHTML = `<input type="hidden" name="accion" value="desactivar"><input type="hidden" name="id_membresia" value="${id}">`;
                        document.body.appendChild(form); form.submit();
                    }
                });
            });

            // Activar
            $(document).on('click', '.btn-activar', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');
                
                Swal.fire({
                    title: '¿Activar Membresía?',
                    html: `<div class="text-center"><i class="bi bi-play-circle text-success fs-1"></i><p class="mt-3">¿Activar <strong>"${nombre}"</strong>?</p></div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Sí, activar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST'; form.action = 'membresias.php';
                        form.innerHTML = `<input type="hidden" name="accion" value="activar"><input type="hidden" name="id_membresia" value="${id}">`;
                        document.body.appendChild(form); form.submit();
                    }
                });
            });

            // Crear
            $('#formCrear').on('submit', function(e) {
                e.preventDefault();
                const nombre = $('#nombre').val();
                const precio = $('#precio').val();
                Swal.fire({
                    title: '¿Crear Membresía?',
                    html: `<p><strong>${nombre}</strong> - $${precio}</p>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Sí, crear'
                }).then((result) => { if (result.isConfirmed) this.submit(); });
            });

            // Editar
            $('#formEditar').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Guardar Cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: 'Sí, guardar'
                }).then((result) => { if (result.isConfirmed) this.submit(); });
            });

            // Alertas Éxito
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('exito')) {
                let msg = 'Operación exitosa';
                if(urlParams.get('exito') === 'crear') msg = 'Membresía creada correctamente';
                if(urlParams.get('exito') === 'editar') msg = 'Membresía actualizada';
                if(urlParams.get('exito') === 'desactivar') msg = 'Membresía desactivada';
                if(urlParams.get('exito') === 'activar') msg = 'Membresía activada';
                
                Swal.fire({
                    title: '¡Éxito!', text: msg, icon: 'success',
                    confirmButtonText: 'Aceptar', timer: 3000, timerProgressBar: true
                });
            }
        });
    </script>
</body>
</html>