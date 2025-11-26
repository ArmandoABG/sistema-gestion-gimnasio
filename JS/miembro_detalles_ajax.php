<?php
// Configuración y archivos requeridos
error_reporting(E_ALL); 
ini_set('display_errors', 1);
session_start();

require_once '../inc/conexion.php'; 
require_once '../funciones/miembros_funciones.php';
require_once '../funciones/membresias_funciones.php';

// Procesar diferentes acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'obtener_detalles') {
        // Código original para obtener detalles del miembro
        $id_miembro = $_POST['id_miembro'] ?? 0;

        if ($id_miembro == 0) {
            echo '<div class="alert alert-danger">ID de miembro no proporcionado.</div>';
            exit();
        }

        // Obtener datos del miembro
        $detalles = obtenerDetallesMiembro($conn, $id_miembro); 

        if (isset($detalles['error'])) {
            echo '<div class="alert alert-danger">Error al obtener detalles: ' . htmlspecialchars($detalles['error']) . '</div>';
            exit();
        }

        // Obtener clases inscritas
        $clases_inscritas = obtenerClasesInscritas($conn, $id_miembro); 

        // Inicializar variables de membresía
        $membresia_actual = $detalles['membresia_actual'] ?? null; 
        $estado_membresia = 'Inactiva';
        $clase_estado = 'text-danger';
        $mostrar_boton_renovar = false;
        
        if ($membresia_actual) {
            // Lógica mejorada para determinar el estado de la membresía
            $fecha_fin = new DateTime($membresia_actual['fecha_fin']);
            $hoy = new DateTime();
            
            if ($fecha_fin >= $hoy) {
                $estado_membresia = 'Activa';
                $clase_estado = 'text-success';
            } else {
                $estado_membresia = 'Expirada';
                $clase_estado = 'text-warning';
                $mostrar_boton_renovar = true;
            }
        } else {
            // No tiene membresía registrada
            $estado_membresia = 'Sin Membresía';
            $clase_estado = 'text-danger';
            $mostrar_boton_renovar = true;
        }

        // Generación del HTML de respuesta
        ob_start();
        ?>

        <!-- Tu HTML original para detalles del miembro aquí -->
        <div class="row">
            <div class="col-md-6">
                <h3><i class="bi bi-person-circle me-2"></i> Información Personal</h3>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($detalles['telefono'] ?? 'N/A'); ?></p>
                <p><strong>Correo:</strong> <?= htmlspecialchars($detalles['correo'] ?? 'N/A'); ?></p>
            </div>
            <div class="col-md-6">
                <h3><i class="bi bi-credit-card-2-front me-2"></i> Estado de Membresía</h3>
                <?php if ($membresia_actual): ?>
                    <p><strong>Membresía:</strong> <?= htmlspecialchars($membresia_actual['nombre_membresia'] ?? 'N/A'); ?></p>
                    <p><strong>Fecha de Inicio:</strong> <?= htmlspecialchars($membresia_actual['fecha_inicio'] ?? 'N/A'); ?></p>
                    <p><strong>Fecha de Fin:</strong> <span class="fw-bold <?= $clase_estado; ?>"><?= htmlspecialchars($membresia_actual['fecha_fin'] ?? 'N/A'); ?></span></p>
                    <p><strong>Estado:</strong> <span class="badge bg-dark <?= str_replace('text-', 'bg-', $clase_estado); ?>"><?= $estado_membresia; ?></span></p>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>Estado:</strong> <span class="badge bg-danger">Sin Membresía</span>
                        <p class="mt-2 mb-0">Este miembro no tiene una membresía activa o registrada.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Botón de renovación mejorado -->
                <?php if ($mostrar_boton_renovar): ?>
                    <hr>
                    <div class="d-grid">
                        <button type="button" class="btn btn-success w-100 btn-abrir-renovacion"
                                data-id-miembro="<?= $id_miembro ?>"
                                data-id-membresia-actual="<?= htmlspecialchars($membresia_actual['id_membresia'] ?? 0) ?>"
                                data-nombre-miembro="<?= htmlspecialchars($detalles['nombre'] ?? '') . ' ' . htmlspecialchars($detalles['apellido'] ?? '') ?>">
                            <i class="bi bi-arrow-clockwise"></i> 
                            <?= $membresia_actual ? 'Renovar Membresía' : 'Asignar Primera Membresía'; ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <h3 class="mt-4"><i class="bi bi-list-check me-2"></i> Clases Inscritas</h3>
        <?php if (count($clases_inscritas) > 0): ?>
            <ul class="list-group">
                <?php foreach ($clases_inscritas as $clase): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold"><?= htmlspecialchars($clase['nombre_clase']); ?></span> - 
                            <?= htmlspecialchars($clase['dia_semana']); ?> a las <?= htmlspecialchars($clase['hora_inicio']); ?>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($clase['nombre_entrenador']); ?></span> 
                    </li>
                <?php endforeach; ?>
                </ul>
        <?php else: ?>
            <div class="alert alert-warning">No está inscrito en ninguna clase actualmente.</div>
        <?php endif; ?>

        <?php
        echo ob_get_clean();
        
    } elseif ($accion === 'obtener_membresias') {
        // Nueva acción para obtener membresías
        $membresias = obtenerTodasMembresiasActivas($conn);
        
        if ($membresias) {
            echo json_encode([
                'success' => true,
                'membresias' => $membresias
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se pudieron cargar las membresías'
            ]);
        }
        
    } else {
        http_response_code(403);
        echo '<div class="alert alert-danger">Acción no válida.</div>';
    }
    
} else {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acceso denegado o solicitud incorrecta.</div>';
}
?>