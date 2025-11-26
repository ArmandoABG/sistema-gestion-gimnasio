<?php
session_start();
// Ajusta la ruta si es necesario según tu estructura
include('../inc/seguridad.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Sistema Gimnasio</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../css/style_menu.css">
    <link rel="stylesheet" href="../css/style_inicio.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container-fluid">
            <h2 class="fw-bold mb-4 section-title"><i class="bi bi-speedometer2"></i> Panel de Control</h2>

            <!-- Indicadores rápidos -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card total">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill fs-1 mb-2"></i>
                            <h4 class="fw-bold mt-2" id="total-miembros">0</h4>
                            <p class="mb-0 text-muted-light">Total Miembros</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card activas">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle-fill fs-1 text-success-custom mb-2"></i>
                            <h4 class="fw-bold text-success-custom mt-2" id="membresias-activas">0</h4>
                            <p class="mb-0 text-muted-light">Membresías Activas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card asistencias">
                        <div class="card-body text-center">
                            <i class="bi bi-clock-fill fs-1 text-info-custom mb-2"></i>
                            <h4 class="fw-bold text-info-custom mt-2" id="asistencias-hoy">0</h4>
                            <p class="mb-0 text-muted-light">Asistencias Hoy</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card ingresos">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar fs-1 text-danger-custom mb-2"></i>
                            <h4 class="fw-bold text-danger-custom mt-2" id="ingresos-mes">$0</h4>
                            <p class="mb-0 text-muted-light">Ingresos del Mes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primera fila de gráficas -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card card-dark-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-white"><i class="bi bi-pie-chart-fill me-2"></i> Estado de Membresías</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="graficoMembresias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-dark-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-white"><i class="bi bi-bar-chart-line-fill text-success me-2"></i> Asistencias (7 días)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="graficoAsistencias"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-dark-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-white"><i class="bi bi-graph-up text-warning me-2"></i> Ingresos Mensuales</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="graficoIngresos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segunda fila: Alertas y Clases Populares -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card card-dark-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-white"><i class="bi bi-bell-fill text-danger me-2"></i> Alertas del Sistema</h6>
                        </div>
                        <div class="card-body p-0"> <!-- Padding 0 para que la lista pegue a los bordes -->
                            <ul class="list-group list-group-flush bg-transparent" id="lista-alertas">
                                <li class="list-group-item bg-transparent text-muted-light">
                                    <i class="bi bi-hourglass-split me-2"></i> Cargando alertas...
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-dark-custom h-100">
                        <div class="card-header card-header-custom">
                            <h6 class="fw-bold mb-0 text-white"><i class="bi bi-trophy-fill text-warning me-2"></i> Clases Más Populares</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="graficoClases"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 
    <script>
    $(document).ready(function() {
        // Configuración global de Chart.js para tema oscuro
        Chart.defaults.color = '#e0e0e0';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

        let chartMembresias, chartAsistencias, chartIngresos, chartClases;

        function cargarDashboard() {
            $.ajax({
                url: '../funciones/inicio_funciones.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // 1. Indicadores
                    $('#total-miembros').text(data.total_miembros);
                    $('#membresias-activas').text(data.membresias_activas);
                    $('#asistencias-hoy').text(data.asistencias_hoy);
                    $('#ingresos-mes').text('$' + parseFloat(data.ingresos_mes).toLocaleString('en-US', {minimumFractionDigits: 2}));

                    // Destruir gráficas previas
                    if (chartMembresias) chartMembresias.destroy();
                    if (chartAsistencias) chartAsistencias.destroy();
                    if (chartIngresos) chartIngresos.destroy();
                    if (chartClases) chartClases.destroy();

                    // 2. Gráfico Membresías (Dona)
                    chartMembresias = new Chart(document.getElementById('graficoMembresias'), {
                        type: 'doughnut',
                        data: {
                            labels: data.grafico_membresias.labels,
                            datasets: [{
                                data: data.grafico_membresias.data,
                                backgroundColor: ['#28a745', '#dc3545', '#6c757d'], // Verde, Rojo, Gris
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                            }
                        }
                    });

                    // 3. Gráfico Asistencias (Barras)
                    chartAsistencias = new Chart(document.getElementById('graficoAsistencias'), {
                        type: 'bar',
                        data: {
                            labels: data.grafico_asistencias.labels,
                            datasets: [{
                                label: 'Asistencias',
                                data: data.grafico_asistencias.data,
                                backgroundColor: '#0dcaf0', // Cyan
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });

                    // 4. Gráfico Ingresos (Línea)
                    chartIngresos = new Chart(document.getElementById('graficoIngresos'), {
                        type: 'line',
                        data: {
                            labels: data.grafico_ingresos.labels,
                            datasets: [{
                                label: 'Ingresos',
                                data: data.grafico_ingresos.data,
                                borderColor: '#fcbd00', // Amarillo
                                backgroundColor: 'rgba(252, 189, 0, 0.1)',
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: '#fcbd00'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { 
                                    beginAtZero: true, 
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: { callback: (val) => '$' + val }
                                },
                                x: { grid: { display: false } }
                            }
                        }
                    });

                    // 5. Gráfico Clases Populares (Barras Horizontales)
                    chartClases = new Chart(document.getElementById('graficoClases'), {
                        type: 'bar',
                        data: {
                            labels: data.grafico_clases.labels,
                            datasets: [{
                                label: 'Inscritos',
                                data: data.grafico_clases.data,
                                backgroundColor: '#ffc107', // Amarillo
                                borderRadius: 4
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                                y: { grid: { display: false } }
                            }
                        }
                    });

                    // 6. Actualizar Alertas
                    actualizarAlertas(data.alertas);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#lista-alertas').html('<li class="list-group-item bg-transparent text-danger"><i class="bi bi-exclamation-triangle"></i> Error de conexión</li>');
                }
            });
        }

        function actualizarAlertas(alertas) {
            const lista = $('#lista-alertas');
            lista.empty();

            if (!alertas || alertas.length === 0) {
                lista.append('<li class="list-group-item bg-transparent text-success"><i class="bi bi-check-circle"></i> Todo en orden. Sin alertas.</li>');
                return;
            }

            alertas.forEach(alerta => {
                let icono = 'bi-bell';
                let colorClass = 'text-white'; // Default

                if (alerta.tipo === 'advertencia') { icono = 'bi-exclamation-triangle'; colorClass = 'text-warning'; }
                if (alerta.tipo === 'peligro') { icono = 'bi-exclamation-octagon'; colorClass = 'text-danger'; }
                if (alerta.tipo === 'info') { icono = 'bi-info-circle'; colorClass = 'text-info'; }

                // Usamos clases personalizadas para los items de lista
                lista.append(`
                    <li class="list-group-item list-group-item-custom">
                        <div class="d-flex align-items-center">
                            <i class="bi ${icono} ${colorClass} fs-4 me-3"></i>
                            <span class="text-white-50">${alerta.mensaje}</span>
                        </div>
                    </li>
                `);
            });
        }

        // Cargar al inicio y refrescar cada 5 mins
        cargarDashboard();
        setInterval(cargarDashboard, 300000);
    });
    </script>
</body>
</html>