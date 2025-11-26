<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../inc/conexion.php';

header('Content-Type: application/json');

// Verificar conexión
if (!$conn) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

try { 
    // Obtener la fecha y hora actual del servidor para consistencia
    $result = pg_query($conn, "SELECT CURRENT_DATE as hoy, CURRENT_TIME as ahora");
    $fecha_actual = pg_fetch_assoc($result);
    $hoy = $fecha_actual['hoy'];
    $hora_actual = $fecha_actual['ahora'];

    // 1. Total de miembros
    $result = pg_query($conn, "SELECT COUNT(*) as total FROM miembros");
    $row = pg_fetch_assoc($result);
    $total_miembros = $row['total'];

    // 2. Membresías activas
    $result = pg_query($conn, "SELECT COUNT(*) as activas FROM miembros_membresias WHERE estado = 'activo'");
    $row = pg_fetch_assoc($result);
    $membresias_activas = $row['activas'];

    // 3. Asistencias hoy - usar la fecha del servidor
    $result = pg_query($conn, "SELECT COUNT(*) as hoy FROM asistencias WHERE fecha = '$hoy'");
    $row = pg_fetch_assoc($result);
    $asistencias_hoy = $row['hoy'];

    // 4. Ingresos del mes actual - usar fecha del servidor
    $result = pg_query($conn, "
        SELECT COALESCE(SUM(monto), 0) as ingresos 
        FROM pagos 
        WHERE EXTRACT(MONTH FROM fecha_pago) = EXTRACT(MONTH FROM '$hoy'::date) 
        AND EXTRACT(YEAR FROM fecha_pago) = EXTRACT(YEAR FROM '$hoy'::date)
    ");
    $row = pg_fetch_assoc($result);
    $ingresos_mes = $row['ingresos'];

    // 5. Gráfico de estado de membresías
    $result = pg_query($conn, "
        SELECT 
            COUNT(CASE WHEN estado = 'activo' THEN 1 END) as activas,
            COUNT(CASE WHEN estado = 'inactivo' THEN 1 END) as vencidas
        FROM miembros_membresias
    ");
    $estados_membresias = pg_fetch_assoc($result);

    // 6. Gráfico de asistencias últimos 7 días (incluyendo hoy)
    $result = pg_query($conn, "
        SELECT 
            fecha,
            COUNT(*) as cantidad
        FROM asistencias 
        WHERE fecha >= ('$hoy'::date - INTERVAL '6 days')
        AND fecha <= '$hoy'::date
        GROUP BY fecha 
        ORDER BY fecha
    ");
    $asistencias_semana = [];
    while ($row = pg_fetch_assoc($result)) {
        $asistencias_semana[] = $row;
    }

    // 7. Gráfico de ingresos últimos 6 meses (incluyendo mes actual)
    $result = pg_query($conn, "
        SELECT 
            TO_CHAR(fecha_pago, 'YYYY-MM') as mes,
            SUM(monto) as total
        FROM pagos 
        WHERE fecha_pago >= DATE_TRUNC('month', '$hoy'::date - INTERVAL '5 months')
        AND fecha_pago < DATE_TRUNC('month', '$hoy'::date) + INTERVAL '1 month'
        GROUP BY TO_CHAR(fecha_pago, 'YYYY-MM')
        ORDER BY mes
    ");
    $ingresos_meses = [];
    while ($row = pg_fetch_assoc($result)) {
        $ingresos_meses[] = $row;
    }

    // Gráfico de clases más populares
    $result = pg_query($conn, "
        SELECT 
            c.nombre as clase,
            COUNT(i.id_inscripcion) as total_inscritos
        FROM clases c
        LEFT JOIN horarios_clases hc ON c.id_clase = hc.id_clase
        LEFT JOIN inscripciones_clases i ON hc.id_horario_clase = i.id_horario_clase
        WHERE i.estado = 'alta' OR i.estado IS NULL
        GROUP BY c.id_clase, c.nombre
        ORDER BY total_inscritos DESC
        LIMIT 5
    ");
    
    $labels_clases = [];
    $data_clases = [];
    
    while ($row = pg_fetch_assoc($result)) {
        $labels_clases[] = $row['clase'];
        $data_clases[] = (int)$row['total_inscritos'];
    }

    // 8. Alertas
    $alertas = [];

    // Membresías que vencen en los próximos 7 días
    $result = pg_query($conn, "
        SELECT COUNT(*) as count 
        FROM miembros_membresias 
        WHERE estado = 'activo' 
        AND fecha_fin BETWEEN '$hoy'::date AND ('$hoy'::date + INTERVAL '7 days')
    ");
    $row = pg_fetch_assoc($result);
    $membresias_por_vencer = $row['count'];
    
    if ($membresias_por_vencer > 0) {
        $alertas[] = [
            'tipo' => 'advertencia',
            'mensaje' => $membresias_por_vencer . ' membresía(s) vencen esta semana'
        ];
    }

    // Máquinas en mantenimiento
    $result = pg_query($conn, "
        SELECT COUNT(*) as count 
        FROM maquinas 
        WHERE estado = 'en mantenimiento'
    ");
    $row = pg_fetch_assoc($result);
    $maquinas_mantenimiento = $row['count'];
    
    if ($maquinas_mantenimiento > 0) {
        $alertas[] = [
            'tipo' => 'peligro',
            'mensaje' => $maquinas_mantenimiento . ' máquina(s) en mantenimiento'
        ];
    }

    // CLASES PROGRAMADAS PARA HOY - CÓDIGO CORREGIDO
    $result = pg_query($conn, "SELECT EXTRACT(DOW FROM '$hoy'::date) as dia_semana_num");
    $row = pg_fetch_assoc($result);
    $numero_dia = $row['dia_semana_num']; // 0=domingo, 1=lunes, ..., 6=sábado
    
    // Primero verificar cómo están almacenados los días en la BD
    $result_debug = pg_query($conn, "SELECT DISTINCT dia_semana FROM horarios_clases LIMIT 10");
    $dias_en_bd = [];
    while ($row_debug = pg_fetch_assoc($result_debug)) {
        $dias_en_bd[] = $row_debug['dia_semana'];
    }
    
    // Mapear número de día a nombres posibles
    $dias_semana_posibles = [
        1 => ['lunes', 'Lunes', 'LUNES'],
        2 => ['martes', 'Martes', 'MARTES'], 
        3 => ['miercoles', 'miércoles', 'Miercoles', 'Miércoles', 'MIERCOLES'],
        4 => ['jueves', 'Jueves', 'JUEVES'],
        5 => ['viernes', 'Viernes', 'VIERNES'],
        6 => ['sabado', 'sábado', 'Sabado', 'Sábado', 'SABADO'],  
        0 => ['domingo', 'Domingo', 'DOMINGO']
    ];
    
    $dias_buscar = $dias_semana_posibles[$numero_dia];
    
    // Construir condición OR para buscar en todos los formatos posibles
    $condiciones = [];
    foreach ($dias_buscar as $dia) {
        $condiciones[] = "LOWER(TRIM(dia_semana)) = LOWER('$dia')";
    }
    $where_clause = implode(' OR ', $condiciones);
    
    // Consulta corregida para clases de hoy
    $result = pg_query($conn, "
        SELECT COUNT(*) as count 
        FROM horarios_clases 
        WHERE $where_clause
    ");
    
    if (!$result) {
        // Si falla la consulta compleja, intentar una más simple
        $result = pg_query($conn, "
            SELECT COUNT(*) as count 
            FROM horarios_clases 
            WHERE dia_semana ILIKE '%" . $dias_buscar[0] . "%'
        ");
    }
    
    $row = pg_fetch_assoc($result);
    $clases_hoy = $row['count'];
    
    if ($clases_hoy > 0) {
        $alertas[] = [
            'tipo' => 'info',
            'mensaje' => $clases_hoy . ' clase(s) programadas para hoy'
        ];
    }

    // Preparar datos para el gráfico de asistencias (últimos 7 días incluyendo hoy)
    $labels_asistencias = [];
    $data_asistencias = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days", strtotime($hoy)));
        $labels_asistencias[] = date('d/m', strtotime($fecha));
        
        $encontrado = false;
        foreach ($asistencias_semana as $asistencia) {
            if ($asistencia['fecha'] == $fecha) {
                $data_asistencias[] = (int)$asistencia['cantidad'];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $data_asistencias[] = 0;
        }
    }

    // Preparar datos para el gráfico de ingresos (últimos 6 meses incluyendo actual)
    $labels_ingresos = [];
    $data_ingresos = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months", strtotime($hoy)));
        $labels_ingresos[] = date('M Y', strtotime($mes));
        
        $encontrado = false;
        foreach ($ingresos_meses as $ingreso) {
            if ($ingreso['mes'] == $mes) {
                $data_ingresos[] = (float)$ingreso['total'];
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            $data_ingresos[] = 0;
        }
    }

    // Devolver datos en formato JSON
    echo json_encode([
        'total_miembros' => (int)$total_miembros,
        'membresias_activas' => (int)$membresias_activas,
        'asistencias_hoy' => (int)$asistencias_hoy,
        'ingresos_mes' => (float)$ingresos_mes,
        
        'grafico_membresias' => [
            'labels' => ['Activas', 'Vencidas'],
            'data' => [
                (int)$estados_membresias['activas'],
                (int)$estados_membresias['vencidas']
            ]
        ],
        
        'grafico_asistencias' => [
            'labels' => $labels_asistencias,
            'data' => $data_asistencias
        ],
        
        'grafico_ingresos' => [
            'labels' => $labels_ingresos,
            'data' => $data_ingresos
        ],
        
        'grafico_clases' => [
            'labels' => $labels_clases,
            'data' => $data_clases
        ],
        
        'alertas' => $alertas,
        'debug' => [ // Para debugging - puedes eliminar esto en producción
            'fecha_servidor' => $hoy,
            'hora_servidor' => $hora_actual,
            'dia_semana_num' => $numero_dia,
            'dias_en_bd' => $dias_en_bd,
            'dias_buscados' => $dias_buscar,
            'clases_encontradas_hoy' => $clases_hoy
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
}

pg_close($conn);
?>