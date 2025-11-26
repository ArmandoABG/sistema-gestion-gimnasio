<?php
// DEBUG - Mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivo de conexión
require_once '../inc/conexion.php';

// Verificar conexión antes de continuar
if (!$conn) {
    echo json_encode(['error' => 'No hay conexión a la base de datos']);
    exit;
}

// DEBUG: Log de la solicitud
error_log("🔍 Solicitud recibida: " . $_SERVER['QUERY_STRING']);

class FinanzasFunciones {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Obtener todos los pagos con información de miembros y usuarios
    public function obtenerPagos($filtroFecha = null) {
        error_log("📊 obtenerPagos llamado con fecha: " . ($filtroFecha ?: 'null'));
        
        $sql = "SELECT p.id_pago, p.monto, p.fecha_pago, 
                       m.nombre, m.apellido, 
                       mem.nombre as membresia,
                       u.usuario as cajero
                FROM pagos p
                INNER JOIN miembros m ON p.id_miembro = m.id_miembro
                INNER JOIN miembros_membresias mm ON p.id_miembro_membresia = mm.id_miembro_membresia
                INNER JOIN membresias mem ON mm.id_membresia = mem.id_membresia
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario";
        
        if ($filtroFecha) {
            $sql .= " WHERE DATE(p.fecha_pago) = $1";
        }
        
        $sql .= " ORDER BY p.fecha_pago DESC";
        
        error_log("🔍 SQL a ejecutar: " . $sql);
        
        try {
            if ($filtroFecha) {
                $result = pg_query_params($this->conn, $sql, array($filtroFecha));
                error_log("🔍 Ejecutando con parámetro: " . $filtroFecha);
            } else {
                $result = pg_query($this->conn, $sql);
                error_log("🔍 Ejecutando sin parámetros");
            }
            
            if (!$result) {
                $error = pg_last_error($this->conn);
                error_log("❌ Error en la consulta: " . $error);
                throw new Exception("Error en la consulta de pagos: " . $error);
            }
            
            $pagos = [];
            $count = 0;
            while ($row = pg_fetch_assoc($result)) {
                $pagos[] = $row;
                $count++;
            }
            
            error_log("✅ Se encontraron " . $count . " pagos");
            
            // DEBUG: Mostrar primeros resultados
            if ($count > 0) {
                error_log("📄 Primer pago: " . json_encode($pagos[0]));
            }
            
            return $pagos;
        } catch (Exception $e) {
            error_log("❌ Error en obtenerPagos: " . $e->getMessage());
            return [];
        }
    }

    // Obtener estadísticas financieras
    public function obtenerEstadisticas($periodo = 'mes') {
        $estadisticas = [];
        
        try {
            // Ingresos del mes actual
            $sqlMes = "SELECT COALESCE(SUM(monto), 0) as total_mes 
                       FROM pagos 
                       WHERE EXTRACT(MONTH FROM fecha_pago) = EXTRACT(MONTH FROM CURRENT_DATE)
                       AND EXTRACT(YEAR FROM fecha_pago) = EXTRACT(YEAR FROM CURRENT_DATE)";
            
            $result = pg_query($this->conn, $sqlMes);
            if (!$result) {
                throw new Exception("Error en consulta de mes: " . pg_last_error($this->conn));
            }
            $row = pg_fetch_assoc($result);
            $estadisticas['ingresos_mes'] = $row['total_mes'] ?: 0;
            
            // Ingresos del día actual
            $sqlHoy = "SELECT COALESCE(SUM(monto), 0) as total_hoy 
                       FROM pagos 
                       WHERE DATE(fecha_pago) = CURRENT_DATE";
            
            $result = pg_query($this->conn, $sqlHoy);
            if (!$result) {
                throw new Exception("Error en consulta de hoy: " . pg_last_error($this->conn));
            }
            $row = pg_fetch_assoc($result);
            $estadisticas['ingresos_hoy'] = $row['total_hoy'] ?: 0;
            
            // Ingresos del año actual
            $sqlAnio = "SELECT COALESCE(SUM(monto), 0) as total_anio 
                        FROM pagos 
                        WHERE EXTRACT(YEAR FROM fecha_pago) = EXTRACT(YEAR FROM CURRENT_DATE)";
            
            $result = pg_query($this->conn, $sqlAnio);
            if (!$result) {
                throw new Exception("Error en consulta de año: " . pg_last_error($this->conn));
            }
            $row = pg_fetch_assoc($result);
            $estadisticas['ingresos_anio'] = $row['total_anio'] ?: 0;
            
            // Total de pagos del mes
            $sqlPagosMes = "SELECT COUNT(*) as total_pagos_mes 
                            FROM pagos 
                            WHERE EXTRACT(MONTH FROM fecha_pago) = EXTRACT(MONTH FROM CURRENT_DATE)
                            AND EXTRACT(YEAR FROM fecha_pago) = EXTRACT(YEAR FROM CURRENT_DATE)";
            
            $result = pg_query($this->conn, $sqlPagosMes);
            if (!$result) {
                throw new Exception("Error en consulta de pagos mes: " . pg_last_error($this->conn));
            }
            $row = pg_fetch_assoc($result);
            $estadisticas['total_pagos_mes'] = $row['total_pagos_mes'] ?: 0;
            
            return $estadisticas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return [
                'ingresos_mes' => 0,
                'ingresos_hoy' => 0,
                'ingresos_anio' => 0,
                'total_pagos_mes' => 0
            ];
        }
    }

    // Obtener ingresos por mes para gráfico
    public function obtenerIngresosMensuales($anio = null) {
        if (!$anio) {
            $anio = date('Y');
        }
        
        $sql = "SELECT EXTRACT(MONTH FROM fecha_pago) as mes, 
                       COALESCE(SUM(monto), 0) as total
                FROM pagos 
                WHERE EXTRACT(YEAR FROM fecha_pago) = $1
                GROUP BY EXTRACT(MONTH FROM fecha_pago)
                ORDER BY mes";
        
        try {
            $result = pg_query_params($this->conn, $sql, array($anio));
            
            if (!$result) {
                throw new Exception("Error en consulta de ingresos mensuales: " . pg_last_error($this->conn));
            }
            
            $resultados = [];
            while ($row = pg_fetch_assoc($result)) {
                $resultados[] = $row;
            }
            
            // Crear array con todos los meses
            $ingresosMensuales = array_fill(1, 12, 0);
            
            foreach ($resultados as $fila) {
                $mes = (int)$fila['mes'];
                $ingresosMensuales[$mes] = (float)$fila['total'];
            }
            
            return $ingresosMensuales;
        } catch (Exception $e) {
            error_log("Error en obtenerIngresosMensuales: " . $e->getMessage());
            return array_fill(1, 12, 0);
        }
    }

    // Generar corte de caja por fecha
    public function generarCorteCaja($fecha) {
        $corte = [];
        
        $sql = "SELECT p.id_pago, p.monto, p.fecha_pago, 
                       m.nombre, m.apellido, 
                       mem.nombre as membresia,
                       u.usuario as cajero
                FROM pagos p
                INNER JOIN miembros m ON p.id_miembro = m.id_miembro
                INNER JOIN miembros_membresias mm ON p.id_miembro_membresia = mm.id_miembro_membresia
                INNER JOIN membresias mem ON mm.id_membresia = mem.id_membresia
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE DATE(p.fecha_pago) = $1
                ORDER BY p.fecha_pago";
        
        $sqlTotal = "SELECT COALESCE(SUM(monto), 0) as total, COUNT(*) as cantidad
                     FROM pagos 
                     WHERE DATE(fecha_pago) = $1";
        
        try {
            // Obtener detalles de pagos
            $result = pg_query_params($this->conn, $sql, array($fecha));
            if (!$result) {
                throw new Exception("Error en consulta de detalles: " . pg_last_error($this->conn));
            }
            
            $detalles = [];
            while ($row = pg_fetch_assoc($result)) {
                $detalles[] = $row;
            }
            $corte['detalles'] = $detalles;
            
            // Obtener total y cantidad
            $result = pg_query_params($this->conn, $sqlTotal, array($fecha));
            if (!$result) {
                throw new Exception("Error en consulta de total: " . pg_last_error($this->conn));
            }
            
            $total = pg_fetch_assoc($result);
            $corte['total'] = $total['total'] ?: 0;
            $corte['cantidad_pagos'] = $total['cantidad'] ?: 0;
            $corte['fecha'] = $fecha;
            
            return $corte;
        } catch (Exception $e) {
            error_log("Error en generarCorteCaja: " . $e->getMessage());
            return [
                'detalles' => [],
                'total' => 0,
                'cantidad_pagos' => 0,
                'fecha' => $fecha
            ];
        }
    }
}

// Crear instancia y procesar solicitudes
if (isset($_GET['accion'])) {
    session_start();
    
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    
    try {
        $finanzas = new FinanzasFunciones($conn);
        
        switch ($_GET['accion']) {
            case 'obtener_pagos':
                $filtroFecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;
                error_log("🎯 Acción: obtener_pagos con fecha: " . ($filtroFecha ?: 'null'));
                $pagos = $finanzas->obtenerPagos($filtroFecha);
                error_log("📦 Enviando respuesta: " . count($pagos) . " pagos");
                echo json_encode($pagos);
                break;
                
            case 'obtener_estadisticas':
                $estadisticas = $finanzas->obtenerEstadisticas();
                echo json_encode($estadisticas);
                break;
                
            case 'obtener_ingresos_mensuales':
                $anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
                $ingresos = $finanzas->obtenerIngresosMensuales($anio);
                echo json_encode($ingresos);
                break;
                
            case 'generar_corte_caja':
                $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
                $corte = $finanzas->generarCorteCaja($fecha);
                echo json_encode($corte);
                break;
                
            default:
                echo json_encode(['error' => 'Acción no válida']);
                break;
        }
    } catch (Exception $e) {
        error_log("❌ Error general en finanzas_funciones: " . $e->getMessage());
        echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
    }
}
?>