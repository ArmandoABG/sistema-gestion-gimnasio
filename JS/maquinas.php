<?php
session_start();
require_once '../inc/conexion.php';
require_once '../funciones/maquinas_funciones.php';
include('../inc/seguridad.php');

// Simulación de ID de usuario logueado (Reemplazar con tu lógica real)
$id_usuario_actual = $_SESSION['id_usuario'] ?? 0; 

// Manejo de acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];

        if ($accion === 'crear') {
            crearMaquina($conn, $_POST['nombre'], $_POST['descripcion'], $_POST['fecha_adquisicion']);
            header("Location: maquinas.php?seccion=registradas&exito=crear");
        } elseif ($accion === 'actualizar') {
            actualizarMaquina($conn, $_POST['id_maquina'], $_POST['nombre'], $_POST['descripcion'], $_POST['fecha_adquisicion']);
            header("Location: maquinas.php?seccion=registradas&exito=editar");
        } elseif ($accion === 'eliminar') {
            eliminarMaquina($conn, $_POST['id_maquina']);
            header("Location: maquinas.php?seccion=registradas&exito=eliminar");
        } elseif ($accion === 'registrar_mantenimiento') {
            registrarMantenimiento($conn, $_POST['id_maquina'], $id_usuario_actual, $_POST['descripcion_mant'], $_POST['tipo_mant']);
            header("Location: maquinas.php?seccion=mantenimiento_activo&exito=registrar_mantenimiento");
        } elseif ($accion === 'finalizar_mantenimiento') {
            finalizarMantenimiento($conn, $_POST['id_mantenimiento'], $_POST['id_maquina']);
            header("Location: maquinas.php?seccion=mantenimiento_activo&exito=finalizar_mantenimiento");
        }
    }
    exit();
}
 
// Sección actual
$seccion = $_GET['seccion'] ?? 'registradas';

// Datos
$maquinas = [];
$mantenimientosActivos = [];
$historialMantenimientos = [];
$maquinasDisponibles = [];

if ($seccion === 'registradas') {
    $maquinas = obtenerMaquinas($conn);
} elseif ($seccion === 'registrar_mantenimiento') {
    $maquinasDisponibles = obtenerMaquinasPorEstado($conn, 'disponible');
} elseif ($seccion === 'mantenimiento_activo') {
    $mantenimientosActivos = obtenerMantenimientosActivos($conn);
} elseif ($seccion === 'historial_mantenimiento') {
    $historialMantenimientos = obtenerHistorialMantenimientos($conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Máquinas</title>
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
    <link rel="stylesheet" href="../css/style_maquinas.css">
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>

    <div class="menu-superior">
        <a href="maquinas.php?seccion=registradas" class="menu-btn <?= ($seccion === 'registradas' ? 'active' : ''); ?>">Máquinas registradas</a>
        <a href="maquinas.php?seccion=registrar" class="menu-btn <?= ($seccion === 'registrar' ? 'active' : ''); ?>">Registrar máquina</a>
        <a href="maquinas.php?seccion=registrar_mantenimiento" class="menu-btn <?= ($seccion === 'registrar_mantenimiento' ? 'active' : ''); ?>">Registrar Mantenimiento</a>
        <a href="maquinas.php?seccion=mantenimiento_activo" class="menu-btn <?= ($seccion === 'mantenimiento_activo' ? 'active' : ''); ?>">Mantenimiento Activo</a>
        <a href="maquinas.php?seccion=historial_mantenimiento" class="menu-btn <?= ($seccion === 'historial_mantenimiento' ? 'active' : ''); ?>">Historial</a>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            <h1>Gestión de Máquinas</h1>
            <hr>

            <?php if ($seccion === 'registradas'): ?>
                <h2>Lista de Máquinas</h2>
                <?php if (count($maquinas) > 0): ?>
                    <table id="tablaMaquinas" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Fecha Adquisición</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maquinas as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nombre']); ?></td>
                                <td><?= htmlspecialchars($m['descripcion']); ?></td>
                                <td><?= htmlspecialchars($m['fecha_adquisicion']); ?></td>
                                <td>
                                    <span class="badge <?= $m['estado'] == 'disponible' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?= htmlspecialchars($m['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                        data-bs-toggle="modal" data-bs-target="#editarModal"
                                        data-id="<?= $m['id_maquina']; ?>"
                                        data-nombre="<?= htmlspecialchars($m['nombre']); ?>"
                                        data-descripcion="<?= htmlspecialchars($m['descripcion']); ?>"
                                        data-fecha="<?= htmlspecialchars($m['fecha_adquisicion']); ?>">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>
                                    <?php if ($es_admin): ?> 
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                        data-id="<?= $m['id_maquina']; ?>"
                                        data-nombre="<?= htmlspecialchars($m['nombre']); ?>">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay máquinas registradas.</p>
                <?php endif; ?>

            <?php elseif ($seccion === 'registrar'): ?>
                <h2>Registrar Nueva Máquina</h2>
                <form id="formCrear" action="maquinas.php" method="POST">
                    <input type="hidden" name="accion" value="crear">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_adquisicion" class="form-label">Fecha adquisición:</label>
                        <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Crear Máquina</button>
                </form>

            <?php elseif ($seccion === 'registrar_mantenimiento'): ?>
                <h2>Registrar Máquina en Mantenimiento</h2>
                <?php if (count($maquinasDisponibles) > 0): ?>
                    <form id="formMantenimiento" action="maquinas.php" method="POST">
                        <input type="hidden" name="accion" value="registrar_mantenimiento">
                        
                        <div class="mb-3">
                            <label for="id_maquina" class="form-label">Máquina:</label>
                            <select id="id_maquina" name="id_maquina" class="form-select" required>
                                <option value="">Selecciona una máquina (Solo Disponibles)</option>
                                <?php foreach ($maquinasDisponibles as $m): ?>
                                    <option value="<?= $m['id_maquina']; ?>"><?= htmlspecialchars($m['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tipo_mant" class="form-label">Tipo de Mantenimiento:</label>
                            <select id="tipo_mant" name="tipo_mant" class="form-select" required>
                                <option value="preventivo">Preventivo</option>
                                <option value="correctivo">Correctivo</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion_mant" class="form-label">Descripción/Notas:</label>
                            <textarea id="descripcion_mant" name="descripcion_mant" class="form-control" required rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">Poner en Mantenimiento</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">Todas las máquinas están actualmente en mantenimiento o fuera de servicio.</div>
                <?php endif; ?>

            <?php elseif ($seccion === 'mantenimiento_activo'): ?>
                <h2>Máquinas Actualmente en Mantenimiento</h2>
                <?php if (count($mantenimientosActivos) > 0): ?>
                    <table id="tablaMantenimientoActivo" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Máquina</th>
                                <th>Tipo</th>
                                <th>Iniciado por</th>
                                <th>Fecha Inicio</th>
                                <th>Descripción</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mantenimientosActivos as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nombre_maquina']); ?></td>
                                <td>
                                    <span class="badge <?= $m['tipo'] == 'preventivo' ? 'bg-info text-dark' : ($m['tipo'] == 'correctivo' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                        <?= htmlspecialchars($m['tipo']); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($m['nombre_usuario']); ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($m['fecha_inicio'])); ?></td>
                                <td><?= htmlspecialchars($m['descripcion']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm btn-finalizar-mantenimiento"
                                        data-id-mantenimiento="<?= $m['id_mantenimiento']; ?>"
                                        data-id-maquina="<?= $m['id_maquina']; ?>"
                                        data-nombre-maquina="<?= htmlspecialchars($m['nombre_maquina']); ?>"
                                        data-tipo-mantenimiento="<?= htmlspecialchars($m['tipo']); ?>">
                                        <i class="bi bi-check-circle-fill"></i> Finalizar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-success">No hay máquinas en mantenimiento activo. ¡Genial! 🎉</div>
                <?php endif; ?>
            
            <?php elseif ($seccion === 'historial_mantenimiento'): ?>
                <h2>Historial de Mantenimientos</h2>
                <?php if (count($historialMantenimientos) > 0): ?>
                    <table id="tablaHistorialMantenimiento" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Máquina</th>
                                <th>Tipo</th>
                                <th>Iniciado por</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historialMantenimientos as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nombre_maquina']); ?></td>
                                <td>
                                    <span class="badge <?= $m['tipo'] == 'preventivo' ? 'bg-info text-dark' : ($m['tipo'] == 'correctivo' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                        <?= htmlspecialchars($m['tipo']); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($m['nombre_usuario']); ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($m['fecha_inicio'])); ?></td>
                                <td>
                                    <?php if ($m['fecha_fin']): ?>
                                        <?= date('Y-m-d H:i', strtotime($m['fecha_fin'])); ?>
                                    <?php else: ?>
                                        <span class="badge bg-danger">En Curso</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($m['descripcion']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay registros de mantenimiento.</p>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditar" action="maquinas.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Máquina</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_maquina" id="editar_id_maquina">

                        <div class="mb-3">
                            <label class="form-label">Nombre:</label>
                            <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción:</label>
                            <textarea id="editar_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha adquisición:</label>
                            <input type="date" id="editar_fecha" name="fecha_adquisicion" class="form-control">
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
        // Configuración global de DataTables para este archivo
        const dataTableConfig = {
            "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
            "responsive": true
        };

        if ($('#tablaMaquinas').length) {
            $('#tablaMaquinas').DataTable({ ...dataTableConfig, "order": [[ 0, "asc" ]] });
        }
        if ($('#tablaMantenimientoActivo').length) {
            $('#tablaMantenimientoActivo').DataTable({ ...dataTableConfig, "order": [[ 3, "desc" ]] });
        }
        if ($('#tablaHistorialMantenimiento').length) {
            $('#tablaHistorialMantenimiento').DataTable({ ...dataTableConfig, "order": [[ 3, "desc" ]] });
        }

        // Modal Editar
        $('.btn-editar').on('click', function() {
            $('#editar_id_maquina').val($(this).data('id'));
            $('#editar_nombre').val($(this).data('nombre'));
            $('#editar_descripcion').val($(this).data('descripcion'));
            $('#editar_fecha').val($(this).data('fecha'));
        });

        // --- SWEETALERT ---
        
        // Eliminar
        $(document).on('click', '.btn-eliminar', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            Swal.fire({
                title: '¿Eliminar Máquina?',
                html: `<div class="text-center"><i class="bi bi-exclamation-triangle text-danger fs-1"></i><p class="mt-3">¿Eliminar <strong>"${nombre}"</strong>?</p><p class="text-muted small">Se borrará el historial.</p></div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = 'maquinas.php';
                    form.innerHTML = `<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_maquina" value="${id}">`;
                    document.body.appendChild(form); form.submit();
                }
            });
        });

        // Crear
        $('#formCrear').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: '¿Crear Máquina?',
                text: `¿Confirmas crear "${$('#nombre').val()}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Sí, crear'
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        // Editar
        $('#formEditar').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: '¿Guardar Cambios?',
                text: '¿Estás seguro de actualizar esta máquina?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Sí, guardar'
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        // Mantenimiento
        $('#formMantenimiento').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: '¿Poner en Mantenimiento?',
                text: 'La máquina no estará disponible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Confirmar'
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        // Finalizar Mantenimiento
        $(document).on('click', '.btn-finalizar-mantenimiento', function() {
            const idMant = $(this).data('id-mantenimiento');
            const idMaq = $(this).data('id-maquina');
            Swal.fire({
                title: '¿Finalizar Mantenimiento?',
                text: 'La máquina volverá a estar disponible.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Sí, finalizar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST'; form.action = 'maquinas.php';
                    form.innerHTML = `<input type="hidden" name="accion" value="finalizar_mantenimiento"><input type="hidden" name="id_mantenimiento" value="${idMant}"><input type="hidden" name="id_maquina" value="${idMaq}">`;
                    document.body.appendChild(form); form.submit();
                }
            });
        });

        // Alertas Exito
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('exito')) {
            let msg = 'Operación exitosa';
            if(urlParams.get('exito') === 'crear') msg = 'Máquina creada correctamente';
            if(urlParams.get('exito') === 'editar') msg = 'Máquina actualizada correctamente';
            if(urlParams.get('exito') === 'eliminar') msg = 'Máquina eliminada correctamente';
            
            Swal.fire({
                title: '¡Éxito!', text: msg, icon: 'success', timer: 2500, showConfirmButton: false
            });
        }
    });
    </script>
</body>
</html>