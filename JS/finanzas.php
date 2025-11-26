<?php
session_start();
require_once '../inc/conexion.php';
// Asegúrate de incluir la seguridad
include('../inc/seguridad.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzas - Sistema de Gimnasio</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_menu.css">
    <link rel="stylesheet" href="/PROYECTO FINAL/css/style_finanzas.css">
</head>
<body>
    <?php require_once '../inc/menu.php'; ?>
    
    <!-- Menú Superior Flotante -->
    <div class="menu-superior no-print">
        <button class="menu-btn active" id="btnEstadisticas" onclick="cambiarPestana(this, 'seccionEstadisticas')">
            <i class="fas fa-chart-bar me-2"></i>Estadísticas
        </button>
        <button class="menu-btn" id="btnReportes" onclick="cambiarPestana(this, 'seccionReportes')">
            <i class="fas fa-file-alt me-2"></i>Reportes
        </button>
        <button class="menu-btn" id="btnCorteCaja" onclick="cambiarPestana(this, 'seccionCorteCaja')">
            <i class="fas fa-calculator me-2"></i>Corte de Caja
        </button>
    </div>

    <div class="main-content" style="view-transition-name: main-content-container;">
        <div class="container-fluid">
            
            <h1><i class="fas fa-chart-line me-2"></i>Finanzas</h1>
            <hr class="no-print">
            
            <!-- Sección de Estadísticas -->
            <div id="seccionEstadisticas" class="seccion-contenido">
                <h2 class="section-title">Resumen Financiero</h2>
                
                <div class="row mb-4 g-4">
                    <div class="col-md-3">
                        <div class="card-finanzas card-ingresos-hoy">
                            <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                            <h5>Ingresos Hoy</h5>
                            <div class="valor" id="ingresosHoy">$0.00</div>
                            <div class="subtexto">Total del día</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-finanzas card-ingresos-mes">
                            <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                            <h5>Ingresos Mes</h5>
                            <div class="valor" id="ingresosMes">$0.00</div>
                            <div class="subtexto">Mes actual</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-finanzas card-ingresos-anio">
                            <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                            <h5>Ingresos Año</h5>
                            <div class="valor" id="ingresosAnio">$0.00</div>
                            <div class="subtexto">Año actual</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-finanzas card-pagos-mes">
                            <div class="card-icon"><i class="fas fa-receipt"></i></div>
                            <h5>Pagos del Mes</h5>
                            <div class="valor" id="totalPagosMes">0</div>
                            <div class="subtexto">Transacciones</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de ingresos mensuales -->
                <div class="card card-grafico mt-4">
                    <div class="card-body">
                        <h5 class="text-white mb-4">Ingresos Mensuales <?php echo date('Y'); ?></h5>
                        <div class="chart-container">
                            <canvas id="graficoIngresos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de Reportes -->
            <div id="seccionReportes" class="seccion-contenido" style="display: none;">
                <h2 class="section-title">Reporte de Ingresos</h2>
                
                <!-- Filtros -->
                <div class="card card-filtros mb-4 no-print">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="filtroFecha" class="form-label">Filtrar por fecha:</label>
                                <input type="date" id="filtroFecha" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-warning me-2" id="btnAplicarFiltro">
                                    <i class="fas fa-filter me-1"></i> Aplicar
                                </button>
                                <button class="btn btn-secondary" id="btnLimpiarFiltro">
                                    <i class="fas fa-times me-1"></i> Limpiar
                                </button>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-info" id="btnImprimirReporte">
                                    <i class="fas fa-print me-1"></i> Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de reportes -->
                <div class="print-section">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Miembro</th>
                                    <th>Membresía</th>
                                    <th>Monto</th>
                                    <th>Fecha Pago</th>
                                    <th>Cajero</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargan via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Sección de Corte de Caja -->
            <div id="seccionCorteCaja" class="seccion-contenido" style="display: none;">
                <h2 class="section-title">Corte de Caja</h2>
                
                <!-- Filtro de fecha para corte -->
                <div class="card card-filtros mb-4 no-print">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="fechaCorte" class="form-label">Seleccionar fecha:</label>
                                <input type="date" id="fechaCorte" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-warning" id="btnGenerarCorte">
                                    <i class="fas fa-calculator me-1"></i> Generar Corte
                                </button>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-info" id="btnImprimirCorte">
                                    <i class="fas fa-print me-1"></i> Imprimir Corte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen del corte -->
                <div id="resumenCorte" class="print-section" style="display: none;">
                    <div class="card card-resumen-corte mb-4">
                        <div class="card-body">
                            <h4 class="text-center mb-4 text-white">Resumen del Corte - <span id="fechaCorteTexto" class="text-warning"></span></h4>
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Total Recaudado</p>
                                    <p class="fs-2 fw-bold text-success"><span id="totalCorte">$0.00</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Cantidad de Pagos</p>
                                    <p class="fs-2 fw-bold text-info"><span id="cantidadPagosCorte">0</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detalles del corte -->
                    <div class="mt-4">
                        <h5 class="text-white mb-3">Detalles de Transacciones</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Miembro</th>
                                        <th>Membresía</th>
                                        <th>Monto</th>
                                        <th>Hora</th>
                                        <th>Cajero</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargan dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        // Variables globales
        let tablaFinanzas, graficoIngresos;
        
        // --- FUNCIÓN DE FORMATO DE MONEDA ---
        // Esta función se encarga de poner comas en los miles y 2 decimales
        function formatoMoneda(valor) {
            return '$' + parseFloat(valor).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Función para navegación de pestañas
        window.cambiarPestana = function(btn, seccionId) {
            document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            document.querySelectorAll('.seccion-contenido').forEach(s => s.style.display = 'none');
            document.getElementById(seccionId).style.display = 'block';
            
            if(seccionId === 'seccionReportes') cargarReportes();
        };

        $(document).ready(function() {
            // Cargar estadísticas al inicio
            cargarEstadisticas();
            cargarGraficoIngresos();
            
            // Inicializar DataTable
            inicializarDataTable();
            
            // Filtros y acciones
            $('#btnAplicarFiltro').click(cargarReportes);
            $('#btnLimpiarFiltro').click(function() {
                $('#filtroFecha').val(new Date().toISOString().split('T')[0]);
                cargarReportes();
            });
            $('#btnGenerarCorte').click(generarCorteCaja);
            $('#btnImprimirReporte').click(imprimirReporte);
            $('#btnImprimirCorte').click(imprimirCorte);
        });
        
        function inicializarDataTable() {
            tablaFinanzas = $('.table').first().DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
                pageLength: 10,
                ordering: true,
                order: [[3, 'desc']],
                responsive: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        }
        
        function cargarEstadisticas() {
            $.ajax({
                url: '../funciones/finanzas_funciones.php?accion=obtener_estadisticas',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Usamos formatoMoneda para que salga con comas
                    $('#ingresosHoy').text(formatoMoneda(data.ingresos_hoy || 0));
                    $('#ingresosMes').text(formatoMoneda(data.ingresos_mes || 0));
                    $('#ingresosAnio').text(formatoMoneda(data.ingresos_anio || 0));
                    $('#totalPagosMes').text(data.total_pagos_mes || 0);
                },
                error: function() { console.error('Error cargando estadísticas'); }
            });
        }
        
        function cargarGraficoIngresos() {
            $.ajax({
                url: '../funciones/finanzas_funciones.php?accion=obtener_ingresos_mensuales',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    const ingresos = Object.values(data); 
                    
                    const ctx = document.getElementById('graficoIngresos').getContext('2d');
                    
                    if (graficoIngresos) graficoIngresos.destroy();
                    
                    graficoIngresos = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: meses,
                            datasets: [{
                                label: 'Ingresos ($)',
                                data: ingresos,
                                backgroundColor: 'rgba(252, 189, 0, 0.6)',
                                borderColor: '#fcbd00',
                                borderWidth: 1,
                                borderRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { labels: { color: '#ffffff' } },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return formatoMoneda(context.raw); // Tooltip con formato
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { color: '#adb5bd', callback: val => formatoMoneda(val) },
                                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                                },
                                x: {
                                    ticks: { color: '#adb5bd' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
            });
        }
        
        function cargarReportes() {
            const fecha = $('#filtroFecha').val();
            let url = '../funciones/finanzas_funciones.php?accion=obtener_pagos';
            if (fecha) url += '&fecha=' + fecha;
            
            Swal.fire({title: 'Cargando...', didOpen: () => Swal.showLoading(), timer: 500, showConfirmButton: false});

            $.ajax({
                url: url, type: 'GET', dataType: 'json',
                success: function(data) {
                    tablaFinanzas.clear();
                    data.forEach(function(pago) {
                        const fechaPago = new Date(pago.fecha_pago);
                        const fechaF = fechaPago.toLocaleDateString() + ' ' + fechaPago.toLocaleTimeString();
                        tablaFinanzas.row.add([
                            pago.nombre + ' ' + pago.apellido,
                            pago.membresia,
                            // Usamos formatoMoneda aquí también
                            '<span class="text-success fw-bold">' + formatoMoneda(pago.monto) + '</span>',
                            fechaF,
                            pago.cajero
                        ]);
                    });
                    tablaFinanzas.draw();
                },
                error: function() { Swal.fire('Error', 'No se pudieron cargar los datos', 'error'); }
            });
        }
        
        function generarCorteCaja() {
            const fecha = $('#fechaCorte').val();
            if (!fecha) return Swal.fire('Atención', 'Seleccione una fecha', 'warning');
            
            Swal.fire({title: 'Generando...', didOpen: () => Swal.showLoading()});
            
            $.ajax({
                url: '../funciones/finanzas_funciones.php?accion=generar_corte_caja&fecha=' + fecha,
                type: 'GET', dataType: 'json',
                success: function(data) {
                    Swal.close();
                    $('#fechaCorteTexto').text(data.fecha);
                    // Formato de moneda en el resumen
                    $('#totalCorte').text(formatoMoneda(data.total));
                    $('#cantidadPagosCorte').text(data.cantidad_pagos);
                    
                    const tbody = $('.table').last().find('tbody');
                    tbody.empty();
                    
                    if (data.detalles.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center text-muted">No hay movimientos</td></tr>');
                    } else {
                        data.detalles.forEach(p => {
                            const hora = new Date(p.fecha_pago).toLocaleTimeString();
                            // Formato de moneda en la tabla
                            tbody.append(`<tr>
                                <td>${p.nombre} ${p.apellido}</td>
                                <td>${p.membresia}</td>
                                <td class="text-success">${formatoMoneda(p.monto)}</td>
                                <td>${hora}</td>
                                <td>${p.cajero}</td>
                            </tr>`);
                        });
                    }
                    $('#resumenCorte').fadeIn();
                    Swal.fire('Corte Generado', `Total: ${formatoMoneda(data.total)}`, 'success');
                },
                error: function() { Swal.fire('Error', 'No se pudo generar el corte', 'error'); }
            });
        }

        // =================================================================
        // FUNCIONES DE IMPRESIÓN (RESTAURADAS AL ESTILO ORIGINAL)
        // =================================================================
        
        function imprimirReporte() {
            const datos = tablaFinanzas.data().toArray();
            if(datos.length === 0) return Swal.fire('Vacio', 'No hay datos para imprimir', 'info');
            
            const fecha = $('#filtroFecha').val() || 'Todas las fechas';
            let htmlRows = '';
            let total = 0;
            
            datos.forEach(row => {
                // Limpiar HTML y formato de moneda para sumar
                const montoTexto = row[2].replace(/<[^>]*>/g, '').replace('$','').replace(/,/g,'');
                total += parseFloat(montoTexto);
                
                htmlRows += `<tr>
                    <td>${row[0]}</td>
                    <td>${row[1]}</td>
                    <td>${row[2].replace(/<[^>]*>/g, '')}</td>
                    <td>${row[3]}</td>
                    <td>${row[4]}</td>
                </tr>`;
            });

            const ventana = window.open('', '_blank');
            ventana.document.write(generarPlantillaImpresion('Reporte de Ingresos', fecha, htmlRows, total, datos.length));
            ventana.document.close();
            
            setTimeout(() => {
                ventana.focus();
                ventana.print();
                setTimeout(() => ventana.close(), 500);
            }, 500);
        }

        function imprimirCorte() {
            if (!$('#resumenCorte').is(':visible')) return Swal.fire('Info', 'Genere el corte primero', 'info');
            
            const fecha = $('#fechaCorteTexto').text();
            const totalStr = $('#totalCorte').text().replace('$','').replace(/,/g,'');
            const filas = $('.table').last().find('tbody tr');
            let htmlRows = '';
            let cantidad = 0;
            
            filas.each(function() {
                const cols = $(this).find('td');
                if(cols.length > 1) { // Ignorar mensaje de "no hay datos"
                    cantidad++;
                    htmlRows += `<tr>
                        <td>${cols.eq(0).text()}</td>
                        <td>${cols.eq(1).text()}</td>
                        <td>${cols.eq(2).text()}</td>
                        <td>${cols.eq(3).text()}</td>
                        <td>${cols.eq(4).text()}</td>
                    </tr>`;
                }
            });

            const ventana = window.open('', '_blank');
            ventana.document.write(generarPlantillaImpresion('Corte de Caja', fecha, htmlRows, parseFloat(totalStr), cantidad));
            ventana.document.close();
            
            setTimeout(() => {
                ventana.focus();
                ventana.print();
                setTimeout(() => ventana.close(), 500);
            }, 500);
        }

        // Plantilla de impresión estilo "Original" que te gustaba
        function generarPlantillaImpresion(titulo, fecha, filas, total, cantidad) {
            return `
            <html>
                <head>
                    <title>${titulo}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                        .logo-container img { max-width: 150px; max-height: 80px; }
                        .title-container { text-align: center; flex: 1; }
                        .header h1 { margin: 0; font-size: 24px; color: #333; }
                        .header .fecha { color: #666; font-size: 16px; margin-top: 5px; }
                        
                        .resumen { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; border-left: 4px solid #007bff; }
                        .resumen-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                        
                        table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        
                        .totales { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
                        .total-card { padding: 15px; background-color: #e9ecef; border-radius: 5px; text-align: center; }
                        .total-valor { font-size: 18px; font-weight: bold; color: #007bff; }
                        
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
                        @media print { body { margin: 0; } .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="logo-container">
                            <img src="../imagenes/logo_sin_fondo.png" alt="Logo Gimnasio">
                        </div>
                        <div class="title-container">
                            <h1>${titulo}</h1>
                            <div class="fecha">Fecha: ${fecha}</div>
                        </div>
                        <div class="logo-container" style="width: 150px;"></div>
                    </div>
                    
                    <div class="resumen">
                        <div class="resumen-grid">
                            <div><strong>Fecha generación:</strong> ${new Date().toLocaleString()}</div>
                            <div><strong>Registros:</strong> ${cantidad}</div>
                        </div>
                    </div>
                    
                    <table>
                        <thead><tr><th>Miembro</th><th>Membresía</th><th>Monto</th><th>Fecha/Hora</th><th>Cajero</th></tr></thead>
                        <tbody>${filas}</tbody>
                    </table>
                    
                    <div class="totales">
                        <div class="total-card">
                            <div>Total de Registros</div>
                            <div class="total-valor">${cantidad}</div>
                        </div>
                        <div class="total-card">
                            <div>Total Recaudado</div>
                            <div class="total-valor">${formatoMoneda(total)}</div>
                        </div>
                    </div>
                    
                    <div class="footer">
                        Sistema de Gestión de Gimnasio - Reporte generado automáticamente
                    </div>
                </body>
            </html>`;
        }
    </script>
</body>
</html>