<?php
session_start(); 
// RUTA CORREGIDA: Se ajusta a tu estructura de carpetas
require_once '../funciones/asistencias_funciones.php'; 

// Obtener datos iniciales para las estadísticas y la tabla
$asistencias_hoy = contar_asistencias_hoy();
$asistencias_semana = contar_asistencias_esta_semana();
$porcentaje_activos = calcular_porcentaje_miembros_activos();
$historial_asistencias = obtener_historial_asistencias(15);
$miembros_select = obtener_miembros_para_select();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Asistencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    
    <!-- SWEETALERT2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- ESTILOS PERSONALIZADOS -->
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_menu.css">
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_asistencias.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>
    <?php require_once '../inc/menu.php'; ?> 

    <!-- CONTENEDOR PRINCIPAL CON EFECTO CRISTAL -->
    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container">
            
            <div class="d-flex justify-content-between align-items-center mb-4 header-section">
                <h1><i class="bi bi-person-check"></i> Asistencias</h1>
                <button class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#modalAsistenciaManual">
                    <i class="bi bi-pencil-square"></i> Registrar manual
                </button>
            </div>
            
            <!-- TARJETAS DE ESTADÍSTICAS -->
            <div class="row mb-4 g-4">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm text-center h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title text-primary-custom">Hoy</h5>
                            <p class="fs-1 fw-bold mb-0" id="stat_hoy"><?php echo $asistencias_hoy; ?></p>
                            <small class="text-muted-light">asistencias registradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm text-center h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title text-success-custom">Esta semana</h5>
                            <p class="fs-1 fw-bold mb-0" id="stat_semana"><?php echo $asistencias_semana; ?></p>
                            <small class="text-muted-light">asistencias totales</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm text-center h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title text-warning-custom">Miembros activos</h5>
                            <p class="fs-1 fw-bold mb-0" id="stat_activos"><?php echo $porcentaje_activos; ?>%</p>
                            <small class="text-muted-light">en últimos 7 días</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE ESCANEO QR -->
            <div class="card qr-section shadow-sm mb-4">
                <div class="card-body text-center p-5">
                    <h2 class="card-title mb-3"><i class="bi bi-qr-code-scan text-primary-custom"></i> Escaneo de Código QR</h2>
                    <p class="text-light mb-4">Mantén el foco en el campo de abajo y escanea el código del miembro.</p>
                    <form id="formQR" autocomplete="off">
                        <input type="text" id="inputQR" name="codigo_qr" class="form-control form-control-lg qr-input" placeholder="Esperando lectura..." autofocus>
                    </form>
                    <div id="resultadoQR" class="mt-2" role="alert"></div>
                </div>
            </div>

            <!-- TABLA DE HISTORIAL -->
            <div class="card table-card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Historial Reciente</h3>
                    <div class="table-responsive">
                        <table id="tablaAsistencias" class="table table-dark table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>Miembro</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial_asistencias as $asistencia): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($asistencia['miembro_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($asistencia['fecha']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($asistencia['hora'], 0, 5)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL ASISTENCIA MANUAL -->
    <div class="modal fade" id="modalAsistenciaManual" tabindex="-1">
        <div class="modal-dialog">
            <form id="formManual" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Registrar asistencia manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info-custom mb-3">
                        <i class="bi bi-info-circle"></i> 
                        La asistencia se registrará con la fecha y hora actual automáticamente.
                    </div>
                    
                    <label for="selectMiembro" class="form-label">Seleccionar miembro</label>
                    <select id="selectMiembro" name="id_miembro" class="form-select" required>
                        <option value="">Seleccione un miembro...</option>
                        <?php foreach ($miembros_select as $miembro): ?>
                            <option value="<?php echo htmlspecialchars($miembro['id_miembro']); ?>">
                                <?php echo htmlspecialchars($miembro['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div id="resultadoManual" class="mt-3" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Registrar asistencia</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Inicializar DataTables
        const tabla = $('#tablaAsistencias').DataTable({
            "pageLength": 10,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
            },
            "order": [[1, "desc"], [2, "desc"]] 
        });
        
        // ... (El resto de tu Javascript se mantiene igual, ya funciona perfecto con AJAX) ...
        
        // =========================================================================
        // 1. FUNCIONES MODULARES PARA LA ACTUALIZACIÓN
        // =========================================================================

        function actualizarEstadisticas() {
            $.ajax({
                url: '../funciones/asistencias_funciones.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'obtener_stats' },
                success: function(response) {
                    if (response.success && response.stats) {
                        $('#stat_hoy').text(response.stats.hoy);
                        $('#stat_semana').text(response.stats.semana);
                        $('#stat_activos').text(response.stats.activos_pct + '%');
                    } else {
                        console.error("Error al obtener stats:", response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Fallo AJAX al obtener stats:", status, error);
                }
            });
        }

        function actualizarTablaHistorial() {
            $.ajax({
                url: '../funciones/asistencias_funciones.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'obtener_historial' },
                success: function(response) {
                    if (response.success && response.data) {
                        tabla.clear();
                        const nuevosDatos = response.data.map(item => [
                            item.miembro_nombre,
                            item.fecha,
                            item.hora.substring(0, 5) 
                        ]);
                        tabla.rows.add(nuevosDatos).draw();
                    }
                }
            });
        }
        
        function actualizarTodo() {
            actualizarEstadisticas();
            actualizarTablaHistorial();
        }

        // =========================================================================
        // 2. Lógica de Escaneo QR (AJAX) - CON SWEETALERT
        // =========================================================================
        $('#formQR').on('submit', function(e) {
            e.preventDefault();
            const $inputQR = $('#inputQR');
            const codigo = $inputQR.val().trim();

            if (codigo === "") {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor, escanea un código QR válido.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            $.ajax({
                url: '../funciones/asistencias_funciones.php', 
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'registrar_qr',
                    codigo_qr: codigo
                },
                success: function(response) {
                    if (response.success) {
                        // SweetAlert para éxito
                        Swal.fire({
                            title: '¡Asistencia Registrada!',
                            html: `<div class="text-center">
                                   <i class="bi bi-check-circle-fill text-success fs-1"></i>
                                   <p class="mt-3"><strong>${response.miembro}</strong></p>
                                   <p>Asistencia registrada correctamente</p>
                                   <small class="text-muted">Hora: ${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'})}</small>
                                   </div>`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            timer: 2000,
                            timerProgressBar: true
                        });
                        
                        $inputQR.val('');
                        actualizarTodo();  
                        
                    } else {
                        // Verificar si es error de membresía vencida
                        if (response.message.includes('Membresía Vencida') || response.message.includes('membresía')) {
                            // SweetAlert para membresía vencida con botón de acción
                            Swal.fire({
                                title: 'Membresía No Activa',
                                html: `<div class="text-center">
                                       <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                                       <p class="mt-3">${response.message}</p>
                                       <p>Es necesario gestionar la membresía del miembro.</p>
                                       </div>`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#ffc107',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="bi bi-people-fill"></i> Gestionar Membresía',
                                cancelButtonText: '<i class="bi bi-clock"></i> Después',
                                reverseButtons: false,
                                customClass: {
                                    confirmButton: 'btn btn-warning mx-2',
                                    cancelButton: 'btn btn-secondary mx-2'
                                },
                                buttonsStyling: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'miembros.php';
                                } else {
                                    // Si elige "Después", mantener el foco en el input
                                    $inputQR.val('').focus();
                                }
                            });
                        } else {
                            // SweetAlert para otros errores
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error de Conexión',
                        text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    console.error("Fallo AJAX QR:", status, error);
                },
                complete: function() {
                    setTimeout(() => $inputQR.focus(), 100); 
                }
            });
        });

        // =========================================================================
        // 3. Lógica de Registro Manual (AJAX) - CON SWEETALERT - CORREGIDO
        // =========================================================================
        $('#formManual').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $modal = $('#modalAsistenciaManual');
            
            // Obtener fecha y hora actual
            const fechaActual = new Date().toISOString().split('T')[0];
            const horaActual = new Date().toTimeString().slice(0, 8); // Formato HH:MM:SS
            
            const idMiembro = $('#selectMiembro').val();
            
            if (!idMiembro) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor, selecciona un miembro.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            $.ajax({
                url: '../funciones/asistencias_funciones.php', 
                type: 'POST',
                dataType: 'json',
                data: {
                    id_miembro: idMiembro,
                    fecha: fechaActual,
                    hora: horaActual,
                    action: 'registrar_manual'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: '¡Éxito!',
                            html: `<div class="text-center">
                                   <i class="bi bi-check-circle-fill text-success fs-1"></i>
                                   <p class="mt-3">${response.message}</p>
                                   <small class="text-muted">Hora: ${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'})}</small>
                                   </div>`,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            timer: 1500,
                            timerProgressBar: true
                        }).then((result) => {
                            // Limpiar el formulario
                            $form[0].reset();
                            // Cerrar el modal usando Bootstrap
                            const modal = bootstrap.Modal.getInstance($modal[0]);
                            modal.hide();
                            // Actualizar la interfaz
                            actualizarTodo();
                        });
                        
                    } else {
                        if (response.message.includes('Membresía Vencida') || response.message.includes('membresía')) {
                            Swal.fire({
                                title: 'Membresía No Activa',
                                html: `<div class="text-center">
                                       <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                                       <p class="mt-3">${response.message}</p>
                                       <p>Es necesario gestionar la membresía del miembro.</p>
                                       </div>`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#ffc107',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="bi bi-people-fill"></i> Gestionar Membresía',
                                cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                                reverseButtons: false,
                                customClass: {
                                    confirmButton: 'btn btn-warning mx-2',
                                    cancelButton: 'btn btn-secondary mx-2'
                                },
                                buttonsStyling: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'miembros.php';
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error de Conexión',
                        text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
                        icon: 'error',
                        confirmButtonText: 'Entendido'
                    });
                    console.error("Error AJAX:", error);
                }
            });
        });

        // Evento para cuando el modal se cierra
        $('#modalAsistenciaManual').on('hidden.bs.modal', function () {
            $('#formManual')[0].reset();
        });

    });
    </script>
</body>
</html>