<?php
// Configuración inicial
error_reporting(E_ALL); 
ini_set('display_errors', 1);
session_start(); 

require_once '../inc/conexion.php'; 
require_once '../funciones/miembros_funciones.php';
include('../inc/seguridad.php');

// OBTENER EL ID DEL USUARIO LOGEADO
$id_usuario_logeado = $_SESSION['id_usuario'] ?? 0; 

// =========================================================================
// MANEJO DE ACCIONES POST
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];

        if ($accion === 'crear') {
            $id_membresia = $_POST['id_membresia'] ?? null;
            $monto = $_POST['monto'] ?? null;
            
            crearMiembro(
                $conn, 
                $_POST['nombre'] ?? '', 
                $_POST['apellido'] ?? '', 
                $_POST['telefono'] ?? '', 
                $_POST['correo'] ?? '', 
                $id_membresia,          
                $monto,                 
                $id_usuario_logeado 
            );
            header("Location: miembros.php?exito=crear");
        } elseif ($accion === 'actualizar') {
            actualizarMiembro(
                $conn, 
                $_POST['id_miembro'] ?? null, 
                $_POST['nombre'] ?? '', 
                $_POST['apellido'] ?? '', 
                $_POST['telefono'] ?? '', 
                $_POST['correo'] ?? ''
            );
            header("Location: miembros.php?exito=editar");
        } elseif ($accion === 'eliminar') {
            eliminarMiembro($conn, $_POST['id_miembro'] ?? null);
            header("Location: miembros.php?exito=eliminar");
        }
    }
    exit();
}

// =========================================================================
// LÓGICA DE VISUALIZACIÓN (GET)
// =========================================================================
$seccion = $_GET['seccion'] ?? 'registrados';
$miembros = [];
$membresias_disponibles = [];

if ($seccion === 'registrados') {
    $miembros = obtenerMiembros($conn);
} elseif ($seccion === 'registrar') {
    $consulta_m = "SELECT id_membresia, nombre, precio, duracion_dias FROM membresias WHERE estado='activo' ORDER BY nombre";
    $resultado_m = pg_query($conn, $consulta_m);
    if ($resultado_m) {
        while ($row = pg_fetch_assoc($resultado_m)) {
            $membresias_disponibles[] = $row;
        }
    }
}

// Lista para modales
$membresias_todas = [];
$consulta_todas = "SELECT id_membresia, nombre FROM membresias WHERE estado='activo' ORDER BY nombre";
$resultado_todas = pg_query($conn, $consulta_todas);
if ($resultado_todas) {
    while ($row = pg_fetch_assoc($resultado_todas)) {
        $membresias_todas[] = $row;
    }
}

// Mensajes de sesión
$mensaje = $_SESSION['mensaje'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Miembros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- SWEETALERT2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_menu.css">
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_miembros.css">
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>

    <div class="menu-superior">
        <a href="miembros.php?seccion=registrados" class="menu-btn <?php echo ($seccion === 'registrados') ? 'active' : ''; ?>">Miembros registrados</a>
        <a href="miembros.php?seccion=registrar" class="menu-btn <?php echo ($seccion === 'registrar') ? 'active' : ''; ?>">Registrar miembro</a>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            <h1>Gestión de Miembros</h1>
            <hr>

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($seccion === 'registrados'): ?>
                <h2>Lista de Miembros</h2>
                <?php if (count($miembros) > 0): ?>
                    <div class="table-responsive">
                        <table id="tablaMiembros" class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($miembros as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['nombre'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($m['apellido'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($m['telefono'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($m['correo'] ?? ''); ?></td>
                                    <td>
                                        <div class="action-buttons-grid">
                                            <!-- Fila 1 -->
                                            <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                data-bs-toggle="modal" data-bs-target="#editarModal"
                                                data-id="<?= htmlspecialchars($m['id_miembro'] ?? ''); ?>"
                                                data-nombre="<?= htmlspecialchars($m['nombre'] ?? ''); ?>"
                                                data-apellido="<?= htmlspecialchars($m['apellido'] ?? ''); ?>"
                                                data-telefono="<?= htmlspecialchars($m['telefono'] ?? ''); ?>"
                                                data-correo="<?= htmlspecialchars($m['correo'] ?? ''); ?>">
                                                <i class="bi bi-pencil-square"></i> Editar
                                            </button>
                                            
                                            <button type="button" class="btn btn-info btn-sm btn-upgrade"
                                                data-bs-toggle="modal" data-bs-target="#upgradeModal"
                                                data-id="<?= htmlspecialchars($m['id_miembro'] ?? ''); ?>"
                                                data-nombre-completo="<?= htmlspecialchars(($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? '')); ?>">
                                                <i class="bi bi-arrow-up-circle"></i> Upgrade
                                            </button>

                                            <!-- Fila 2 -->
                                            <?php if ($es_admin): ?>
                                            <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                                data-id="<?= htmlspecialchars($m['id_miembro'] ?? ''); ?>"
                                                data-nombre-completo="<?= htmlspecialchars(($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? '')); ?>">
                                                <i class="bi bi-trash-fill"></i> Eliminar
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-secondary btn-sm btn-detalles"
                                                data-bs-toggle="modal" data-bs-target="#detallesModal"
                                                data-id="<?= htmlspecialchars($m['id_miembro'] ?? ''); ?>"
                                                data-nombre-completo="<?= htmlspecialchars(($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? '')); ?>">
                                                <i class="bi bi-receipt-cutoff"></i> Detalles
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert alert-info">No hay miembros registrados.</p>
                <?php endif; ?>

            <?php elseif ($seccion === 'registrar'): ?>
                <div class="main-content-form"> 
                    <h2 class="text-center mb-4">Registrar Nuevo Miembro</h2>
                    <form id="formRegistroMiembro" action="miembros.php" method="POST">
                        <input type="hidden" name="accion" value="crear">

                        <div class="row g-4"> 
                            
                            <div class="col-md-6">
                                <div class="card card-dark-custom h-100"> 
                                    <div class="card-header bg-primary text-white card-header-custom">
                                        <i class="bi bi-person-badge-fill me-2"></i> Datos del Miembro
                                    </div>
                                    <div class="card-body">
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
                                            <label for="correo" class="form-label">Correo <span class="text-muted small">(Opcional)</span>:</label>
                                            <input type="email" id="correo" name="correo" class="form-control" placeholder="ejemplo@correo.com">
                                            <div class="form-text text-white-50">Si no tiene correo, dejar en blanco.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex flex-column"> 
                                <div class="card card-dark-custom mb-4">
                                    <div class="card-header bg-warning text-dark card-header-custom">
                                        <i class="bi bi-calendar-check-fill me-2"></i> Selección de Membresía
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="id_membresia" class="form-label">Membresía:</label>
                                            <select id="id_membresia" name="id_membresia" class="form-select" required onchange="actualizarDatosMembresia()">
                                                <option value="">Seleccione una membresía</option>
                                                <?php foreach ($membresias_disponibles as $m): ?>
                                                <option value="<?= htmlspecialchars($m['id_membresia']); ?>" 
                                                    data-nombre="<?= htmlspecialchars($m['nombre']); ?>" 
                                                    data-precio="<?= htmlspecialchars($m['precio']); ?>" 
                                                    data-duracion="<?= htmlspecialchars($m['duracion_dias']); ?>">
                                                    <?= htmlspecialchars($m['nombre']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card card-dark-custom flex-grow-1">
                                    <div class="card-header bg-success text-white card-header-custom"> 
                                        <i class="bi bi-cash-stack me-2"></i> Resumen de Pago
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Nombre:</strong> <span id="membresia_nombre" class="fw-bold text-warning">---</span></p>
                                        <p><strong>Precio:</strong> <span id="membresia_precio" class="fw-bold text-info">---</span></p>
                                        <p><strong>Duración:</strong> <span id="membresia_duracion" class="fw-bold text-info">---</span></p>
                                        <hr class="text-secondary">
                                        <h4 class="mt-3"><strong>Total:</strong> <span id="monto_mostrar" class="fw-bold text-success">---</span></h4>
                                        <input type="hidden" id="monto" name="monto" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row my-5">
                            <div class="col-12 d-flex justify-content-center">
                                <button type="submit" id="btnFinalizarRegistro" class="btn btn-primary btn-lg custom-submit-btn w-100" disabled>
                                    <i class="bi bi-person-plus-fill me-2"></i> Finalizar Registro
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <script>
                    const form = document.getElementById('formRegistroMiembro');
                    const btnSubmit = document.getElementById('btnFinalizarRegistro');
                    const selectMembresia = document.getElementById('id_membresia');
                    
                    function verificarCampos() {
                        let todosLlenos = true;
                        const inputsObligatorios = [
                            document.getElementById('nombre'),
                            document.getElementById('apellido'), 
                            document.getElementById('telefono')
                        ];
                        
                        inputsObligatorios.forEach(input => {
                            if (input.value.trim() === '') todosLlenos = false;
                        });
                        
                        if (selectMembresia.value === '') todosLlenos = false;
                        
                        btnSubmit.disabled = !todosLlenos;
                    }

                    function actualizarDatosMembresia() {
                        const selected = selectMembresia.options[selectMembresia.selectedIndex];
                        
                        if (selected.value === "") {
                            document.getElementById('membresia_nombre').textContent = '---';
                            document.getElementById('membresia_precio').textContent = '---';
                            document.getElementById('membresia_duracion').textContent = '---';
                            document.getElementById('monto_mostrar').textContent = '---';
                            document.getElementById('monto').value = '';
                        } else {
                            const nombre = selected.dataset.nombre || '---';
                            const precio = selected.dataset.precio || '---';
                            const duracion = selected.dataset.duracion || '---';

                            document.getElementById('membresia_nombre').textContent = nombre;
                            document.getElementById('membresia_precio').textContent = `$${precio}`;
                            document.getElementById('membresia_duracion').textContent = `${duracion} días`;
                            document.getElementById('monto_mostrar').textContent = `$${precio}`;
                            document.getElementById('monto').value = precio;
                        }
                        verificarCampos(); 
                    }

                    form.addEventListener('input', verificarCampos);
                    selectMembresia.addEventListener('change', actualizarDatosMembresia);
                    document.addEventListener('DOMContentLoaded', () => {
                        actualizarDatosMembresia();
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditar" action="miembros.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarModalLabel">Editar Miembro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_miembro" id="editar_id_miembro">

                        <div class="mb-3">
                            <label class="form-label">Nombre:</label>
                            <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Apellido:</label>
                            <input type="text" id="editar_apellido" name="apellido" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono:</label>
                            <input type="tel" id="editar_telefono" name="telefono" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo:</label>
                            <input type="email" id="editar_correo" name="correo" class="form-control">
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

    <!-- MODAL UPGRADE -->
    <div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formUpgrade" action="membresia_upgrade.php" method="POST">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="upgradeModalLabel">Cambiar Membresía (Upgrade)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="finalizar_upgrade">
                        <input type="hidden" name="id_miembro" id="upgrade_id_miembro">
                        
                        <p>Miembro: <strong><span id="upgrade_nombre_miembro"></span></strong></p>
                        <p class="mb-3">Membresía actual: <strong class="text-primary" id="membresia_actual_display">---</strong></p>

                        <div class="mb-3">
                            <label for="id_nueva_membresia" class="form-label">Seleccionar Nueva Membresía:</label>
                            <select id="id_nueva_membresia" name="id_nueva_membresia" class="form-select" required>
                                <option value="" disabled selected>Seleccione</option>
                                <?php foreach ($membresias_todas as $m): ?>
                                <option value="<?= htmlspecialchars($m['id_membresia']); ?>">
                                    <?= htmlspecialchars($m['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <hr>
                        <h4>Resumen</h4>
                        <div id="upgrade_resultado">
                            Seleccione una opción para calcular el costo.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-info" id="btn_finalizar_upgrade" disabled>Finalizar Upgrade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLES -->
    <div class="modal fade" id="detallesModal" tabindex="-1" aria-labelledby="detallesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="detallesModalLabel"><i class="bi bi-person-lines-fill me-2"></i> Perfil: <span id="nombreMiembro"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesContent">
                        <p class="text-center my-5"><i class="bi bi-arrow-repeat spin spinner-border"></i> Cargando datos...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-ver-gafete" id="btnVerGafete" style="display: none;">
                        <i class="bi bi-eye-fill me-2"></i> Ver Gafete
                    </button>
                    <button type="button" class="btn btn-info btn-imprimir-gafete" id="btnImprimirGafete" style="display: none;">
                        <i class="bi bi-printer-fill me-2"></i> Imprimir
                    </button>
                    <button type="button" class="btn btn-warning btn-reenviar-gafete" id="btnReenviarGafete" style="display: none;">
                        <i class="bi bi-send-fill me-2"></i> Reenviar Gafete
                    </button>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL RENOVACIÓN -->
    <div class="modal fade" id="modalRenovacion" tabindex="-1" aria-labelledby="modalRenovacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalRenovacionLabel">
                        <i class="bi bi-arrow-clockwise me-2"></i> <span id="modalTitulo">Renovar Membresía</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contenidoRenovacion">
                        <div class="alert alert-info mb-3">
                            <h6>Información del Miembro</h6>
                            <p class="mb-1" id="infoMiembroModal">Cargando...</p>
                        </div>

                        <form id="formRenovacion">
                            <input type="hidden" id="id_miembro_modal" name="id_miembro">
                            <input type="hidden" id="id_membresia_actual_modal" name="id_membresia_actual">
                            
                            <div class="mb-3">
                                <label for="selectMembresia" class="form-label">Seleccionar Membresía *</label>
                                <select class="form-select" id="selectMembresia" name="id_membresia_nueva" required>
                                    <option value="">-- Seleccione una membresía --</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="montoPagado" class="form-label">Monto Pagado *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="montoPagado" name="monto_pagado" 
                                           step="0.01" min="0.01" required placeholder="0.00">
                                </div>
                            </div>

                            <div class="card mt-3 card-dark-custom">
                                <div class="card-header bg-dark text-white">
                                    <h6 class="mb-0">Resumen</h6>
                                </div>
                                <div class="card-body">
                                    <div id="resumenRenovacion" class="text-muted">Seleccione una membresía...</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div id="estadoCarga" class="text-center d-none">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p>Procesando...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnProcesarRenovacion">
                        <i class="bi bi-check-circle me-2"></i>Procesar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
    // INICIALIZACIÓN
    $(document).ready(function() {
        if ($('#tablaMiembros').length) {
            $('#tablaMiembros').DataTable({
                "language": { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' }
            });
        }

        // Modal Editar
        $('#editarModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const modal = $(this);
            modal.find('#editar_id_miembro').val(button.data('id'));
            modal.find('#editar_nombre').val(button.data('nombre'));
            modal.find('#editar_apellido').val(button.data('apellido'));
            modal.find('#editar_telefono').val(button.data('telefono'));
            modal.find('#editar_correo').val(button.data('correo'));
        });
        
        // Modal Upgrade
        $('#upgradeModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const modal = $(this);
            modal.find('#upgrade_id_miembro').val(button.data('id'));
            modal.find('#upgrade_nombre_miembro').text(button.data('nombre-completo'));
            modal.find('#id_nueva_membresia').val('');
            modal.find('#membresia_actual_display').text('---');
            modal.find('#upgrade_resultado').html('Seleccione una opción para calcular el costo.');
            modal.find('#btn_finalizar_upgrade').prop('disabled', true);
        });

        $('#id_nueva_membresia').on('change', function() {
            actualizarDatosUpgrade();
        });

        // Alertas de éxito URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('exito')) {
            let mensaje = 'Operación realizada correctamente';
            if(urlParams.get('exito') === 'crear') mensaje = 'El miembro se registró correctamente.';
            if(urlParams.get('exito') === 'editar') mensaje = 'Los cambios se guardaron correctamente.';
            if(urlParams.get('exito') === 'eliminar') mensaje = 'El miembro se eliminó correctamente.';
            
            Swal.fire({
                title: '¡Éxito!',
                text: mensaje,
                icon: 'success',
                confirmButtonText: 'Aceptar',
                timer: 3000,
                timerProgressBar: true
            });
        }
    });

    // --- SWEETALERTS ---

    $(document).on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre-completo');
        Swal.fire({
            title: '¿Eliminar Miembro?',
            html: `<div class="text-center"><i class="bi bi-exclamation-triangle text-danger fs-1"></i><p class="mt-3">¿Eliminar a <strong>"${nombre}"</strong>?</p><p class="text-muted small">Se borrarán membresías e historial.</p></div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST'; form.action = 'miembros.php';
                form.innerHTML = `<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_miembro" value="${id}">`;
                document.body.appendChild(form); form.submit();
            }
        });
    });

    $('#formRegistroMiembro').on('submit', function(e) {
        e.preventDefault();
        const nombre = $('#nombre').val();
        const apellido = $('#apellido').val();
        const memb = $('#membresia_nombre').text();
        const monto = $('#monto_mostrar').text();
        
        Swal.fire({
            title: '¿Registrar Miembro?',
            html: `<div class="text-center"><p><strong>${nombre} ${apellido}</strong></p><div class="alert alert-info"><p class="mb-0">Membresía: ${memb} | Pago: ${monto}</p></div></div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Sí, registrar'
        }).then((result) => { if (result.isConfirmed) this.submit(); });
    });

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

    $('#formUpgrade').on('submit', function(e) {
        e.preventDefault();
        const nombre = $('#upgrade_nombre_miembro').text();
        const monto = $('#upgrade_resultado').find('.badge').text() || '$0.00';
        Swal.fire({
            title: '¿Confirmar Upgrade?',
            html: `<p>Upgrade para <strong>${nombre}</strong></p><p>Monto a pagar: <strong>${monto}</strong></p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            confirmButtonText: 'Confirmar'
        }).then((result) => { if (result.isConfirmed) this.submit(); });
    });

    // --- LÓGICA AJAX UPGRADE ---
    function actualizarDatosUpgrade() {
        const idMiembro = $('#upgrade_id_miembro').val();
        const idNueva = $('#id_nueva_membresia').val();
        
        if (!idMiembro || !idNueva) return;
        
        $('#membresia_actual_display').text('Calculando...');
        $('#upgrade_resultado').html('<i class="bi bi-arrow-repeat spin"></i> Calculando...');
        $('#btn_finalizar_upgrade').prop('disabled', true);
        
        $.ajax({
            url: 'membresia_upgrade.php',
            type: 'POST',
            dataType: 'json',
            data: { accion: 'calcular', id_miembro: idMiembro, id_nueva_membresia: idNueva },
            success: function(data) {
                if (data.success) {
                    $('#membresia_actual_display').text(data.nombre_actual || 'Ninguna');
                    $('#upgrade_resultado').html(`
                        <p>Crédito actual: <strong>$${(data.credito_aplicado || 0).toFixed(2)}</strong></p>
                        <h4 class="mt-2">A Pagar: <span class="badge bg-success">$${(data.monto_a_pagar || 0).toFixed(2)}</span></h4>
                    `);
                    if ((data.monto_a_pagar || 0) >= 0) $('#btn_finalizar_upgrade').prop('disabled', false);
                } else {
                    $('#membresia_actual_display').text(data.nombre_actual || 'N/A');
                    $('#upgrade_resultado').html(`<p class="text-danger">Error: ${data.error}</p>`);
                }
            },
            error: function() {
                $('#membresia_actual_display').text('Error');
                $('#upgrade_resultado').text('Error de conexión');
            }
        });
    }

    // --- LÓGICA DETALLES & GAFETE ---
    $('#detallesModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const id = button.data('id');
        const nombre = button.data('nombre-completo');
        const modal = $(this);
        
        modal.find('#nombreMiembro').text(nombre);
        modal.find('#btnVerGafete, #btnImprimirGafete, #btnReenviarGafete').hide().data('id-miembro', id);
        modal.find('#detallesContent').html('<p class="text-center my-5"><i class="bi bi-arrow-repeat spin spinner-border"></i> Cargando...</p>');

        $.ajax({
            url: 'miembro_detalles_ajax.php',
            type: 'POST',
            data: { id_miembro: id, accion: 'obtener_detalles' },
            success: function(html) {
                modal.find('#detallesContent').html(html);
                modal.find('#btnVerGafete, #btnImprimirGafete, #btnReenviarGafete').show();
            }
        });
    });

    // Eventos Gafete
    $(document).on('click', '#btnVerGafete', function() { verGafete($(this).data('id-miembro')); });
    $(document).on('click', '#btnReenviarGafete', function() { reenviarGafete($(this).data('id-miembro')); });
    $(document).on('click', '#btnImprimirGafete', function() { imprimirGafete($(this).data('id-miembro')); });

    function verGafete(id) {
        Swal.fire({ title: 'Generando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: 'gafete_ajax.php', type: 'POST', data: { accion: 'ver_gafete', id_miembro: id },
            xhrFields: { responseType: 'blob' },
            success: function(data) {
                Swal.close();
                const url = URL.createObjectURL(new Blob([data], { type: 'image/png' }));
                Swal.fire({
                    title: 'Gafete', html: `<img src="${url}" class="img-fluid" style="max-height:400px">`,
                    showCloseButton: true, showCancelButton: true, confirmButtonText: 'Descargar', cancelButtonText: 'Cerrar'
                }).then((res) => {
                    if(res.isConfirmed) {
                        const a = document.createElement('a'); a.href = url; a.download = `gafete_${id}.png`; a.click();
                    }
                    URL.revokeObjectURL(url);
                });
            },
            error: () => Swal.fire('Error', 'No se pudo generar el gafete', 'error')
        });
    }

    function reenviarGafete(id) {
        Swal.fire({ title: 'Verificando correo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: 'gafete_ajax.php', type: 'POST', data: { accion: 'verificar_correo', id_miembro: id },
            success: function(res) {
                Swal.close();
                const data = JSON.parse(res);
                if(data.tiene_correo) {
                    Swal.fire({
                        title: '¿Reenviar?', text: `Se enviará a: ${data.correo}`, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, enviar'
                    }).then((r) => {
                        if(r.isConfirmed) {
                            Swal.fire({ title: 'Enviando...', didOpen: () => Swal.showLoading() });
                            $.ajax({
                                url: 'gafete_ajax.php', type: 'POST', data: { accion: 'reenviar_gafete', id_miembro: id },
                                success: (resp) => {
                                    const d = JSON.parse(resp);
                                    Swal.fire(d.success ? 'Enviado' : 'Error', d.mensaje || d.error, d.success ? 'success' : 'error');
                                }
                            });
                        }
                    });
                } else {
                    Swal.fire('Sin Correo', 'Este miembro no tiene correo registrado.', 'warning');
                }
            }
        });
    }

    function imprimirGafete(id) {
        Swal.fire({ title: 'Preparando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: 'gafete_ajax.php', type: 'POST', data: { accion: 'ver_gafete', id_miembro: id },
            xhrFields: { responseType: 'blob' },
            success: function(data) {
                Swal.close();
                const url = URL.createObjectURL(new Blob([data], { type: 'image/png' }));
                const win = window.open('', '_blank');
                win.document.write(`<html><body style="display:flex;justify-content:center;align-items:center;height:100vh"><img src="${url}" onload="window.print();window.close()"></body></html>`);
                win.document.close();
                setTimeout(() => URL.revokeObjectURL(url), 5000);
            }
        });
    }

    // --- LÓGICA RENOVACIÓN ---
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-abrir-renovacion')) abrirModalRenovacion(e.target);
    });

    function abrirModalRenovacion(btn) {
        const id = btn.getAttribute('data-id-miembro');
        const nombre = btn.getAttribute('data-nombre-miembro');
        
        $('#id_miembro_modal').val(id);
        $('#infoMiembroModal').text(nombre);
        $('#formRenovacion')[0].reset();
        $('#resumenRenovacion').text('Seleccione una membresía...');
        $('#contenidoRenovacion').removeClass('d-none');
        $('#estadoCarga').addClass('d-none');
        
        cargarMembresiasDisponibles();
        new bootstrap.Modal(document.getElementById('modalRenovacion')).show();
    }

    function cargarMembresiasDisponibles() {
        const select = $('#selectMembresia');
        select.html('<option>Cargando...</option>').prop('disabled', true);
        $.ajax({
            url: 'miembro_detalles_ajax.php', type: 'POST', data: { accion: 'obtener_membresias' },
            success: function(res) {
                const data = JSON.parse(res);
                if(data.success) {
                    select.html('<option value="">-- Seleccione --</option>');
                    data.membresias.forEach(m => {
                        select.append(`<option value="${m.id_membresia}" data-precio="${m.precio}">${m.nombre} - $${m.precio}</option>`);
                    });
                    select.prop('disabled', false);
                }
            }
        });
    }

    $('#selectMembresia').on('change', function() {
        const opt = $(this).find(':selected');
        const precio = opt.data('precio');
        if(precio) {
            $('#montoPagado').val(precio);
            $('#resumenRenovacion').html(`<p>A pagar: <span class="text-success fw-bold">$${precio}</span></p>`);
        } else {
            $('#montoPagado').val('');
            $('#resumenRenovacion').text('Seleccione...');
        }
    });

    $('#btnProcesarRenovacion').on('click', function() {
        const formData = new FormData($('#formRenovacion')[0]);
        if(!formData.get('id_membresia_nueva') || !formData.get('monto_pagado')) {
            Swal.fire('Atención', 'Complete los campos', 'warning'); return;
        }
        
        $('#contenidoRenovacion').addClass('d-none');
        $('#estadoCarga').removeClass('d-none');
        $(this).prop('disabled', true);

        fetch('procesar_renovacion.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            $('#estadoCarga').addClass('d-none');
            $(this).prop('disabled', false);
            if(d.success) {
                Swal.fire('Éxito', d.mensaje, 'success').then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalRenovacion')).hide();
                    const id = $('#id_miembro_modal').val();
                    if($('#detallesModal').is(':visible')) { // Recargar detalles si está abierto
                         $.ajax({
                            url: 'miembro_detalles_ajax.php', type: 'POST', data: { id_miembro: id, accion: 'obtener_detalles' },
                            success: (h) => $('#detallesContent').html(h)
                        });
                    }
                });
            } else {
                Swal.fire('Error', d.error, 'error');
                $('#contenidoRenovacion').removeClass('d-none');
            }
        });
    });

    </script>
</body>
</html>