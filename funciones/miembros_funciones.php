<?php
// miembros_funciones.php - SOLUCIÓN SEGURA
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0);

// Usar __DIR__ para obtener la ruta absoluta de este archivo
$base_dir = dirname(__DIR__); // Esto sube un nivel desde 'funciones' a 'Proyecto Final'

require_once $base_dir . '/inc/conexion.php';
require_once $base_dir . '/libs/phpqrcode/qrlib.php';
require_once $base_dir . '/libs/PHPMailer/src/PHPMailer.php';
require_once $base_dir . '/libs/PHPMailer/src/SMTP.php';
require_once $base_dir . '/libs/PHPMailer/src/Exception.php';

// CONFIGURACIÓN DE ZONA HORARIA
date_default_timezone_set('America/Mexico_City');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ----------------------
// CREAR (Insertar Miembro, Membresía y Pago)
// ----------------------
/**
 * Inserta un nuevo miembro, registra su membresía y el pago asociado.
 *
 * @param object $conn La conexión a la base de datos.
 * @param string $nombre Nombre del miembro.
 * @param string $apellido Apellido del miembro.
 * @param string $telefono Teléfono del miembro.
 * @param string $correo Correo del miembro.
 * @param int $id_membresia ID de la membresía seleccionada.
 * @param float $monto Monto pagado por la membresía.
 * @param int $id_usuario ID del usuario que registra (debe venir de la sesión).
 * @return mixed El ID del miembro creado o false si falla.
 */
function crearMiembro(
    $conn,
    $nombre,
    $apellido,
    $telefono,
    $correo,  // Ahora puede estar vacío o NULL
    $id_membresia,
    $monto,
    $id_usuario
) {
    
    // Validación inicial de datos (correo ya no es obligatorio)
    if (!$nombre || !$apellido || !$telefono || !$id_membresia || !$monto) {
        error_log("Datos incompletos para crearMiembro");
        return false;
    }

    // Iniciar una transacción
    pg_query($conn, "BEGIN");

    try {
        $fecha_registro = date("Y-m-d");

        // ✅ CORRECCIÓN: Manejar correo vacío - convertir a NULL de forma segura
        $correo_param = (empty(trim($correo))) ? null : trim($correo);

        // 1. Insertar miembro (ahora acepta NULL en correo)
        $query_miembro = "INSERT INTO miembros (nombre, apellido, telefono, correo, fecha_registro) 
                          VALUES ($1, $2, $3, $4, $5) RETURNING id_miembro";
        $result_miembro = pg_query_params($conn, $query_miembro, [
            $nombre, 
            $apellido, 
            $telefono, 
            $correo_param,  // ← Usar el parámetro corregido
            $fecha_registro
        ]);

        if (!$result_miembro) {
            throw new Exception("Error al insertar miembro: " . pg_last_error($conn));
        }

        $row = pg_fetch_assoc($result_miembro);
        $id_miembro = $row['id_miembro'];

        // 2. Generar código QR y actualizar
        $codigo_qr = "GYM-" . $id_miembro;
        $update_qr = "UPDATE miembros SET codigo_qr = $1 WHERE id_miembro = $2";
        $result_update = pg_query_params($conn, $update_qr, [$codigo_qr, $id_miembro]);
        if (!$result_update) throw new Exception("Error al actualizar código QR: " . pg_last_error($conn));

        // 3. Obtener duración de la membresía
        $query_duracion = "SELECT duracion_dias FROM membresias WHERE id_membresia = $1 AND estado='activo'";
        $result_duracion = pg_query_params($conn, $query_duracion, [$id_membresia]);
        if (!pg_num_rows($result_duracion)) throw new Exception("Membresía no encontrada o inactiva.");
        $duracion = pg_fetch_result($result_duracion, 0, 'duracion_dias');
        
        // Calcular fecha_fin
        $fecha_inicio = $fecha_registro;
        $fecha_fin = date('Y-m-d', strtotime("+$duracion days", strtotime($fecha_inicio)));
        $estado_membresia = 'activo';

        // 4. Insertar en miembros_membresias
        $query_memb = "INSERT INTO miembros_membresias (id_miembro, id_membresia, fecha_inicio, fecha_fin, estado) 
                       VALUES ($1, $2, $3, $4, $5) RETURNING id_miembro_membresia";
        $result_memb = pg_query_params($conn, $query_memb, [$id_miembro, $id_membresia, $fecha_inicio, $fecha_fin, $estado_membresia]);

        if (!$result_memb) throw new Exception("Error al insertar membresía del miembro: " . pg_last_error($conn));

        $row_memb = pg_fetch_assoc($result_memb);
        $id_miembro_membresia = $row_memb['id_miembro_membresia'];

        // 5. Insertar en pagos
        $query_pago = "INSERT INTO pagos (id_miembro, id_miembro_membresia, monto, id_usuario) 
                       VALUES ($1, $2, $3, $4) RETURNING id_pago";
        $monto_float = floatval($monto);
        $result_pago = pg_query_params($conn, $query_pago, [$id_miembro, $id_miembro_membresia, $monto_float, $id_usuario]);

        if (!$result_pago) throw new Exception("Error al insertar pago: " . pg_last_error($conn));

        // 6. INTENTAR generar gafete y enviar correo SOLO si hay correo válido
        $correo_enviado = false;
        $tempGafeteFile = null;
        
        // ✅ CORRECCIÓN: SOLO enviar correo si hay dirección de correo VÁLIDA
        if ($correo_param !== null && filter_var($correo_param, FILTER_VALIDATE_EMAIL)) {
            try {
                // Generar gafete
                $tempGafeteFile = generarGafeteIdentico($codigo_qr, $nombre, $apellido);
                
                if ($tempGafeteFile && file_exists($tempGafeteFile)) {
                    // Configurar y enviar correo
                    $mail = new PHPMailer(true);
                    
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'gymportezuelo585@gmail.com';
                    $mail->Password   = 'pesr akmw dlhl zqrh';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom('gymportezuelo585@gmail.com', 'Portezuelo Gym');
                    $mail->addAddress($correo_param, $nombre . ' ' . $apellido);

                    // Adjuntar el GAFETE
                    $mail->addAttachment($tempGafeteFile, 'gafete_miembro.png');

                    $mail->isHTML(true);
                    $mail->Subject = 'Tu Gafete de Miembro - Portezuelo Gym';
                    $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #003366;'>¡Bienvenido a Portezuelo Gym!</h2>
                        
                        <p>Hola <strong>$nombre $apellido</strong>,</p>
                        
                        <p>Tu membresía ha sido activada exitosamente y estará vigente hasta el <strong>$fecha_fin</strong>.</p>
                        
                        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #003366; margin: 20px 0;'>
                            <h3 style='color: #003366; margin-top: 0;'>📋 Tu Gafete de Miembro</h3>
                            <p>Hemos adjuntado tu gafete oficial que incluye:</p>
                            <ul>
                                <li>Tu código QR único para acceso</li>
                                <li>Tu información personal</li>
                                <li>Fecha de vencimiento</li>
                            </ul>
                            <p><strong>💡 Importante:</strong> Imprime este gafete y preséntalo al ingresar al gimnasio.</p>
                        </div>
                        
                        <p>Tu código de acceso es: <strong style='font-size: 18px;'>$codigo_qr</strong></p>
                        
                        <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                        
                        <hr style='border: none; border-top: 2px solid #eee; margin: 30px 0;'>
                        
                        <p style='color: #666; font-size: 14px;'>
                            Atentamente,<br>
                            <strong>Equipo Portezuelo Gym</strong><br>
                        </p>
                    </div>";

                    $mail->send();
                    $correo_enviado = true;
                    
                    // Limpiar archivo temporal
                    unlink($tempGafeteFile);
                    
                } else {
                    error_log("No se pudo generar el archivo de gafete para el miembro $id_miembro");
                }
                
            } catch (Exception $e) {
                // Solo loguear el error del correo, no abortar la transacción
                error_log("Error al enviar correo con gafete para miembro $id_miembro: " . $e->getMessage());
                
                // Limpiar archivo temporal si existe
                if ($tempGafeteFile && file_exists($tempGafeteFile)) {
                    unlink($tempGafeteFile);
                }
                
                // Intentar enviar correo simple con QR como fallback
                try {
                    $mail_simple = new PHPMailer(true);
                    $mail_simple->isSMTP();
                    $mail_simple->Host       = 'smtp.gmail.com';
                    $mail_simple->SMTPAuth   = true;
                    $mail_simple->Username   = 'gymportezuelo585@gmail.com';
                    $mail_simple->Password   = 'pesr akmw dlhl zqrh';
                    $mail_simple->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail_simple->Port       = 587;
                    
                    $mail_simple->setFrom('gymportezuelo585@gmail.com', 'Portezuelo Gym');
                    $mail_simple->addAddress($correo_param, $nombre . ' ' . $apellido);
                    
                    $mail_simple->isHTML(true);
                    $mail_simple->Subject = 'Bienvenido a Portezuelo Gym';
                    $mail_simple->Body    = "Hola <b>$nombre $apellido</b>,<br><br>
                                          Bienvenido a <b>Portezuelo Gym</b>.<br>
                                          Tu membresía está activa hasta el <strong>$fecha_fin</strong>.<br>
                                          Tu código QR único es: <strong>$codigo_qr</strong>.<br>
                                          <br>¡Preséntalo al ingresar!";
                    
                    $mail_simple->send();
                    $correo_enviado = true;
                    
                } catch (Exception $e2) {
                    error_log("Error también en correo simple: " . $e2->getMessage());
                }
            }
        } else {
            // ✅ NUEVO: Log informativo cuando no hay correo
            error_log("Miembro $id_miembro creado sin correo válido - No se envió gafete por email. Correo proporcionado: " . ($correo_param ?: 'NULL/VACÍO'));
        }

        // Si todo fue bien en la base de datos, confirmar la transacción
        pg_query($conn, "COMMIT");
        
        // Log del resultado
        if ($correo_enviado) {
            error_log("Miembro $id_miembro creado exitosamente - Correo enviado a: $correo_param");
        } else {
            if ($correo_param !== null) {
                error_log("Miembro $id_miembro creado exitosamente - Pero falló el envío de correo a: $correo_param");
            } else {
                error_log("Miembro $id_miembro creado exitosamente - Sin correo proporcionado, gafete disponible para imprimir");
            }
        }
        
        return $id_miembro;

    } catch (Exception $e) {
        // Si algo falló, revertir los cambios
        pg_query($conn, "ROLLBACK");
        
        // Log del error detallado
        error_log("❌ Error en la Transacción de Registro: " . $e->getMessage());
        
        return false;
    }
}
// ----------------------
// LISTAR (Leer)
// ----------------------
function obtenerMiembros($conn) {
    $query = "SELECT * FROM miembros ORDER BY id_miembro DESC";
    $result = pg_query($conn, $query);
    $miembros = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $miembros[] = $row;
        }
    }
    return $miembros;
}

// ----------------------
// ACTUALIZAR (Editar)
// ----------------------
function actualizarMiembro($conn, $id, $nombre, $apellido, $telefono, $correo) {
    // ✅ PASO 3: Manejar correo vacío - convertir a NULL
    $correo = trim($correo);
    if (empty($correo)) {
        $correo = null; // Usar NULL para la base de datos
    }
    
    $query = "UPDATE miembros 
              SET nombre = $1, apellido = $2, telefono = $3, correo = $4
              WHERE id_miembro = $5";
    return pg_query_params($conn, $query, [$nombre, $apellido, $telefono, $correo, $id]);
}

// ----------------------
// ELIMINAR
// ----------------------
function eliminarMiembro($conn, $id) {
    // DELETE CASCADE se encarga de las tablas relacionadas
    $query = "DELETE FROM miembros WHERE id_miembro = $1";
    return pg_query_params($conn, $query, [$id]);
}

// Las funciones registrarMembresia y registrarPago no son estrictamente necesarias
// ya que están integradas en crearMiembro, pero se dejan para referencia si las necesita.
// =========================================================
// CAMBIAR/ACTUALIZAR MEMBRESÍA (UPGRADE)
// =========================================================

/**
 * Cambia la membresía activa de un miembro, calcula el crédito restante 
 * y registra el nuevo pago y la nueva membresía en una transacción segura.
 *
 * @param object $conn La conexión a la base de datos.
 * @param int $id_miembro ID del miembro.
 * @param int $id_nueva_membresia ID de la membresía seleccionada.
 * @param int $id_usuario ID del usuario que registra el cambio (de la sesión).
 * @return array Resultado del proceso (success, monto_pagado, error, etc.).
 */
function cambiarMembresia($conn, $id_miembro, $id_nueva_membresia, $id_usuario) {
    pg_query($conn, "BEGIN");

    try {
        // 1. Obtener detalles de la NUEVA membresía
        $query_nueva = "SELECT precio, duracion_dias, nombre FROM membresias 
                       WHERE id_membresia = $1";
        $result_nueva = pg_query_params($conn, $query_nueva, [$id_nueva_membresia]);
        if (!pg_num_rows($result_nueva)) {
            throw new Exception("La nueva membresía no existe.");
        }
        $nueva = pg_fetch_assoc($result_nueva);

        // 2. Obtener membresía ACTUAL (si existe)
        $query_actual = "
            SELECT mm.id_miembro_membresia, mm.fecha_fin, m.precio, m.duracion_dias, 
                   m.nombre AS nombre_actual_membresia
            FROM miembros_membresias mm
            JOIN membresias m ON mm.id_membresia = m.id_membresia
            WHERE mm.id_miembro = $1";
            
        $result_actual = pg_query_params($conn, $query_actual, [$id_miembro]);
        $tiene_membresia_actual = pg_num_rows($result_actual) > 0;
        $actual = $tiene_membresia_actual ? pg_fetch_assoc($result_actual) : null;

        // 3. Validaciones
        if ($tiene_membresia_actual) {
            $precio_actual = floatval($actual['precio']);
            $precio_nuevo = floatval($nueva['precio']);
            
            if ($precio_nuevo < $precio_actual) {
                throw new Exception("No se permite downgrade de '{$actual['nombre_actual_membresia']}' a '{$nueva['nombre']}'.");
            }

            // 4. Cálculo de crédito (solo si tenía membresía anterior)
            $hoy = new DateTime();
            $fecha_fin_actual = new DateTime($actual['fecha_fin']);
            
            $interval = $hoy->diff($fecha_fin_actual);
            $dias_restantes = $fecha_fin_actual > $hoy ? $interval->days : 0;
            $dias_totales_actual = intval($actual['duracion_dias']);
            
            $credito = 0.0;
            if ($dias_restantes > 0 && $dias_totales_actual > 0) {
                $valor_por_dia = $precio_actual / $dias_totales_actual;
                $credito = round($valor_por_dia * $dias_restantes, 2);
            }
            
            $monto_a_pagar = max(0.0, $precio_nuevo - $credito);
        } else {
            // Si es nueva membresía (sin anterior)
            $credito = 0.0;
            $monto_a_pagar = floatval($nueva['precio']);
        }

        // 🟢🟢🟢 CORRECCIÓN PRINCIPAL: ACTUALIZAR EN LUGAR DE ELIMINAR 🟢🟢🟢
        
        if ($tiene_membresia_actual) {
            // 5. ACTUALIZAR membresía existente
            $fecha_inicio_nueva = date("Y-m-d");
            $duracion_nueva = intval($nueva['duracion_dias']);
            $fecha_fin_nueva = date('Y-m-d', strtotime("+$duracion_nueva days"));

            $query_actualizar = "UPDATE miembros_membresias 
                                SET id_membresia = $1, 
                                    fecha_inicio = $2, 
                                    fecha_fin = $3,
                                    estado = 'activo'
                                WHERE id_miembro_membresia = $4 
                                RETURNING id_miembro_membresia";
            
            $result_actualizar = pg_query_params($conn, $query_actualizar, [
                $id_nueva_membresia, 
                $fecha_inicio_nueva, 
                $fecha_fin_nueva,
                $actual['id_miembro_membresia']
            ]);
            
            if (!$result_actualizar) {
                throw new Exception("Error al actualizar membresía: " . pg_last_error($conn));
            }

            $id_miembro_membresia = pg_fetch_result($result_actualizar, 0, 'id_miembro_membresia');
            
        } else {
            // 6. INSERTAR nueva membresía (si no tenía anterior)
            $fecha_inicio_nueva = date("Y-m-d");
            $duracion_nueva = intval($nueva['duracion_dias']);
            $fecha_fin_nueva = date('Y-m-d', strtotime("+$duracion_nueva days"));

            $query_insertar = "INSERT INTO miembros_membresias 
                              (id_miembro, id_membresia, fecha_inicio, fecha_fin, estado) 
                              VALUES ($1, $2, $3, $4, 'activo') 
                              RETURNING id_miembro_membresia";
            
            $result_insertar = pg_query_params($conn, $query_insertar, [
                $id_miembro, 
                $id_nueva_membresia, 
                $fecha_inicio_nueva, 
                $fecha_fin_nueva, 
                'activo'
            ]);
            
            if (!$result_insertar) {
                throw new Exception("Error al insertar nueva membresía: " . pg_last_error($conn));
            }

            $id_miembro_membresia = pg_fetch_result($result_insertar, 0, 'id_miembro_membresia');
        }

        // 7. Registrar pago si aplica
        if ($monto_a_pagar > 0) {
            $query_pago = "INSERT INTO pagos (id_miembro, id_miembro_membresia, monto, id_usuario) 
                          VALUES ($1, $2, $3, $4)";
            
            $result_pago = pg_query_params($conn, $query_pago, [
                $id_miembro, 
                $id_miembro_membresia, 
                $monto_a_pagar, 
                $id_usuario
            ]);
            
            if (!$result_pago) {
                throw new Exception("Error al registrar pago: " . pg_last_error($conn));
            }
        }

        pg_query($conn, "COMMIT");
        
        return [
            'success' => true, 
            'monto_pagado' => $monto_a_pagar, 
            'credito_aplicado' => $credito,
            'fecha_fin' => $fecha_fin_nueva,
            'mensaje' => $tiene_membresia_actual ? "Membresía actualizada exitosamente" : "Membresía asignada exitosamente"
        ];

    } catch (Exception $e) {
        pg_query($conn, "ROLLBACK"); 
        return [
            'success' => false, 
            'error' => $e->getMessage()
        ];
    }
}




// En archivo: ../funciones/miembros_funciones.php

function obtenerDetallesMiembro($conn, $id_miembro) {
    // 1. Obtener datos personales del miembro
    $consulta_miembro = "
        SELECT id_miembro, nombre, apellido, telefono, correo
        FROM miembros
        WHERE id_miembro = $1;
    ";
    $resultado_miembro = pg_query_params($conn, $consulta_miembro, array($id_miembro));
    
    if (!$resultado_miembro || pg_num_rows($resultado_miembro) == 0) {
        return ['error' => 'Miembro no encontrado.'];
    }
    
    $detalles = pg_fetch_assoc($resultado_miembro);

    // 2. Obtener la membresía activa o la más reciente
    $consulta_membresia = "
        SELECT 
            mm.id_membresia, mm.fecha_inicio, mm.fecha_fin, mm.estado, 
            m.nombre AS nombre_membresia, m.precio
        FROM 
            miembros_membresias mm
        JOIN
            membresias m ON mm.id_membresia = m.id_membresia
        WHERE 
            mm.id_miembro = $1
        ORDER BY
            mm.fecha_fin DESC
        LIMIT 1;
    ";
    
    $resultado_membresia = pg_query_params($conn, $consulta_membresia, array($id_miembro));
    
    $detalles['membresia_actual'] = null;
    if ($resultado_membresia && pg_num_rows($resultado_membresia) > 0) {
        $membresia = pg_fetch_assoc($resultado_membresia);
        $detalles['membresia_actual'] = $membresia;
    }
    
    return $detalles;
}

// En archivo: ../funciones/miembros_funciones.php

function obtenerClasesInscritas($conn, $id_miembro) {
    // Consulta SQL usando las nuevas tablas: inscripciones_clases, horarios_clases, clases, instructores
    $consulta = "
        SELECT 
            c.nombre AS nombre_clase, hc.dia_semana, hc.hora_inicio, 
            i.nombre AS nombre_instructor, i.apellido AS apellido_instructor
        FROM 
            inscripciones_clases ic
        JOIN 
            horarios_clases hc ON ic.id_horario_clase = hc.id_horario_clase
        JOIN
            clases c ON hc.id_clase = c.id_clase
        JOIN 
            instructores i ON hc.id_instructor = i.id_instructor
        WHERE 
            ic.id_miembro = $1
        ORDER BY
            hc.dia_semana, hc.hora_inicio;
    ";
    
    $resultado = pg_query_params($conn, $consulta, array($id_miembro));
    
    $clases = [];
    if ($resultado) {
        while ($row = pg_fetch_assoc($resultado)) {
            // Unir nombre y apellido del instructor
            $row['nombre_entrenador'] = trim($row['nombre_instructor'] . ' ' . $row['apellido_instructor']);
            $clases[] = $row;
        }
    }
    return $clases;
}

/**
 * Genera un gafete IDÉNTICO al diseño de la imagen usando fuentes del sistema
 */
function generarGafeteIdentico($codigo_qr, $nombre, $apellido) {
    $ancho = 500;
    $alto = 300;
    $imagen = imagecreatetruecolor($ancho, $alto);
    
    // Colores EXACTOS de la imagen
    $amarillo_oro = imagecolorallocate($imagen, 212, 175, 55);
    $negro_fondo = imagecolorallocate($imagen, 0, 0, 0);
    $blanco = imagecolorallocate($imagen, 255, 255, 255);
    $gris_claro = imagecolorallocate($imagen, 200, 200, 200);
    
    try {
        // --- 1. BUSCAR FUENTES DEL SISTEMA ---
        $font_bold = null;
        $font_regular = null;
        
        // Rutas comunes de fuentes en Windows
        $windows_fonts = [
            'C:/Windows/Fonts/arialbd.ttf',      // Arial Bold
            'C:/Windows/Fonts/arial.ttf',        // Arial Regular
            'C:/Windows/Fonts/tahomabd.ttf',     // Tahoma Bold
            'C:/Windows/Fonts/tahoma.ttf',       // Tahoma Regular
        ];
        
        // Rutas comunes en Linux
        $linux_fonts = [
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];
        
        $all_fonts = array_merge($windows_fonts, $linux_fonts);
        
        foreach ($all_fonts as $font_path) {
            if (file_exists($font_path)) {
                if (strpos($font_path, 'bd.ttf') !== false || strpos($font_path, 'Bold') !== false) {
                    $font_bold = $font_path;
                } else {
                    $font_regular = $font_path;
                }
            }
        }
        
        // Si no encontramos fuentes específicas, usar cualquier fuente disponible
        if (!$font_bold && !$font_regular && !empty($all_fonts)) {
            foreach ($all_fonts as $font_path) {
                if (file_exists($font_path)) {
                    $font_bold = $font_path;
                    $font_regular = $font_path;
                    break;
                }
            }
        }
        
        // --- 2. FONDO NEGRO ---
        imagefill($imagen, 0, 0, $negro_fondo);

        // --- 3. CABECERA DORADA ---
        $cabecera_alto = 70;
        imagefilledrectangle($imagen, 0, 0, $ancho, $cabecera_alto, $amarillo_oro);

        // --- 4. TEXTO CON FUENTES DEL SISTEMA ---
        if ($font_bold && $font_regular) {
            // PORTEZUELO GYM (Cabecera)
            $texto_gym = "PORTEZUELO GYM";
            $bbox_gym = imagettfbbox(16, 0, $font_bold, $texto_gym);
            $x_gym = ($ancho - ($bbox_gym[2] - $bbox_gym[0])) / 2;
            imagettftext($imagen, 16, 0, $x_gym, 42, $negro_fondo, $font_bold, $texto_gym);
            
            // BIENVENIDO
            imagettftext($imagen, 11, 0, 30, 105, $gris_claro, $font_regular, "BIENVENIDO");
            
            // NOMBRE DEL MIEMBRO - MÁS PEQUEÑO (12 en lugar de 14)
            $nombre_completo = strtoupper($nombre . " " . $apellido);
            
            // Si el nombre es muy largo, hacerlo aún más pequeño
            if (strlen($nombre_completo) > 20) {
                $tamano_nombre = 10;
            } else {
                $tamano_nombre = 12; // 🔽 MÁS PEQUEÑO que antes (era 14)
            }
            
            imagettftext($imagen, $tamano_nombre, 0, 30, 135, $blanco, $font_bold, $nombre_completo);
            
            // Línea separadora dorada
            imageline($imagen, 30, 150, 250, 150, $amarillo_oro);
            
            // CÓDIGO DE ACCESO
            imagettftext($imagen, 10, 0, 30, 175, $gris_claro, $font_regular, "CODIGO DE ACCESO");
            
            // CÓDIGO QR (texto)
            imagettftext($imagen, 16, 0, 30, 200, $amarillo_oro, $font_bold, $codigo_qr);
            
            // Presentar al ingresar
            imagettftext($imagen, 9, 0, 365, 230, $gris_claro, $font_regular, "Presentar al ingresar");
            
        } else {
            // FALLBACK: Texto básico si no hay fuentes TTF
            imagestring($imagen, 5, 150, 25, "PORTEZUELO GYM", $negro_fondo);
            imagestring($imagen, 3, 30, 85, "BIENVENIDO", $gris_claro);
            
            // Nombre más pequeño
            $nombre_completo = strtoupper($nombre . " " . $apellido);
            if (strlen($nombre_completo) > 20) {
                imagestring($imagen, 2, 30, 110, $nombre_completo, $blanco);
            } else {
                imagestring($imagen, 3, 30, 110, $nombre_completo, $blanco); // 🔽 Más pequeño
            }
            
            imageline($imagen, 30, 150, 250, 150, $amarillo_oro);
            imagestring($imagen, 3, 30, 155, "CODIGO DE ACCESO", $gris_claro);
            imagestring($imagen, 5, 30, 175, $codigo_qr, $amarillo_oro);
            imagestring($imagen, 2, 365, 220, "Presentar al ingresar", $gris_claro);
        }

        // --- 5. CÓDIGO QR ---
        $tempQRFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        QRcode::png($codigo_qr, $tempQRFile, QR_ECLEVEL_H, 6, 2);
        
        $qr_imagen = imagecreatefrompng($tempQRFile);
        $qr_size = 110;
        $qr_redimensionado = imagecreatetruecolor($qr_size, $qr_size);
        imagecopyresampled($qr_redimensionado, $qr_imagen, 0, 0, 0, 0, $qr_size, $qr_size, imagesx($qr_imagen), imagesy($qr_imagen));
        
        $qr_x = 360;
        $qr_y = 95;
        imagefilledrectangle($imagen, $qr_x - 4, $qr_y - 4, $qr_x + $qr_size + 4, $qr_y + $qr_size + 4, $blanco);
        imagecopy($imagen, $qr_redimensionado, $qr_x, $qr_y, 0, 0, $qr_size, $qr_size);

        // --- 6. MARCOS ELEGANTES ---
        imagerectangle($imagen, 5, 5, $ancho - 6, $alto - 6, $amarillo_oro);
        imagerectangle($imagen, 8, 8, $ancho - 9, $alto - 9, $negro_fondo);

        // --- 7. GUARDAR ---
        $tempGafeteFile = tempnam(sys_get_temp_dir(), 'gafete_') . '.png';
        imagepng($imagen, $tempGafeteFile, 9);

        // Limpiar
        imagedestroy($imagen);
        imagedestroy($qr_imagen);
        imagedestroy($qr_redimensionado);
        if (file_exists($tempQRFile)) unlink($tempQRFile);
        
        return $tempGafeteFile;
        
    } catch (Exception $e) {
        // Limpieza de errores
        if (isset($imagen)) imagedestroy($imagen);
        if (isset($qr_imagen)) imagedestroy($qr_imagen);
        if (isset($qr_redimensionado)) imagedestroy($qr_redimensionado);
        if (isset($tempQRFile) && file_exists($tempQRFile)) unlink($tempQRFile);
        
        throw new Exception("Error generando gafete: " . $e->getMessage());
    }
}

// ----------------------
// FUNCIONES DE NOTIFICACIONES AUTOMÁTICAS
// ----------------------

/**
 * Envía recordatorios de vencimiento de membresías
 * @param object $conn Conexión a la base de datos
 * @return array Resultado del proceso
 */
function enviarRecordatoriosVencimientoMembresias($conn) {
    try {
        // Buscar membresías que vencerán en los próximos 3 días
        $query = "
            SELECT 
                m.id_miembro,
                m.nombre,
                m.apellido,
                m.correo,
                mm.fecha_fin,
                mem.nombre as nombre_membresia,
                (mm.fecha_fin - CURRENT_DATE) as dias_restantes
            FROM 
                miembros m
            JOIN 
                miembros_membresias mm ON m.id_miembro = mm.id_miembro
            JOIN 
                membresias mem ON mm.id_membresia = mem.id_membresia
            WHERE 
                mm.estado = 'activo'
                AND mm.fecha_fin BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '3 days'
                AND m.correo IS NOT NULL
                AND m.correo != ''
        ";
        
        $result = pg_query($conn, $query);
        if (!$result) {
            throw new Exception("Error en consulta: " . pg_last_error($conn));
        }
        
        $enviados = 0;
        $errores = 0;
        $resultados = [];
        
        while ($row = pg_fetch_assoc($result)) {
            $envio_exitoso = enviarCorreoVencimientoMembresia(
                $row['correo'],
                $row['nombre'],
                $row['apellido'],
                $row['fecha_fin'],
                $row['nombre_membresia'],
                $row['dias_restantes']
            );
            
            if ($envio_exitoso) {
                $enviados++;
                $resultados[] = [
                    'miembro' => $row['nombre'] . ' ' . $row['apellido'],
                    'correo' => $row['correo'],
                    'fecha_fin' => $row['fecha_fin'],
                    'estado' => 'enviado'
                ];
            } else {
                $errores++;
                $resultados[] = [
                    'miembro' => $row['nombre'] . ' ' . $row['apellido'],
                    'correo' => $row['correo'],
                    'fecha_fin' => $row['fecha_fin'],
                    'estado' => 'error'
                ];
            }
        }
        
        return [
            'success' => true,
            'enviados' => $enviados,
            'errores' => $errores,
            'total' => pg_num_rows($result),
            'detalles' => $resultados
        ];
        
    } catch (Exception $e) {
        error_log("Error en enviarRecordatoriosVencimientoMembresias: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Envía recordatorios de clases del día
 * @param object $conn Conexión a la base de datos
 * @return array Resultado del proceso
 */
function enviarRecordatoriosClasesDelDia($conn) {
    try {
        // Obtener el nombre del día actual en español
        $dias_semana = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        
        $dia_actual_ingles = date('l');
        $dia_actual = $dias_semana[$dia_actual_ingles];
        
        // DEBUG: Mostrar información
        error_log("Día actual en inglés: " . $dia_actual_ingles);
        error_log("Día actual en español: " . $dia_actual);
        
        // Buscar inscripciones a clases para hoy
        $query = "
            SELECT 
                m.id_miembro,
                m.nombre,
                m.apellido,
                m.correo,
                c.nombre as nombre_clase,
                hc.hora_inicio,
                i.nombre as nombre_instructor,
                i.apellido as apellido_instructor,
                hc.dia_semana,
                ic.estado
            FROM 
                miembros m
            JOIN 
                inscripciones_clases ic ON m.id_miembro = ic.id_miembro
            JOIN 
                horarios_clases hc ON ic.id_horario_clase = hc.id_horario_clase
            JOIN 
                clases c ON hc.id_clase = c.id_clase
            JOIN 
                instructores i ON hc.id_instructor = i.id_instructor
            WHERE 
                hc.dia_semana = $1
                AND ic.estado = 'alta'
                AND m.correo IS NOT NULL
                AND m.correo != ''
        ";
        
        $result = pg_query_params($conn, $query, [$dia_actual]);
        if (!$result) {
            throw new Exception("Error en consulta: " . pg_last_error($conn));
        }
        
        $total = pg_num_rows($result);
        error_log("Clases encontradas: " . $total);
        
        // DEBUG: Mostrar los resultados
        while ($row = pg_fetch_assoc($result)) {
            error_log("Clase encontrada: " . $row['nombre'] . " " . $row['apellido'] . 
                     " - " . $row['nombre_clase'] . " - " . $row['dia_semana'] . 
                     " - Estado: " . $row['estado']);
        }
        
        // Reiniciar el puntero del resultado
        pg_result_seek($result, 0);
        
        $enviados = 0;
        $errores = 0;
        $resultados = [];
        
        while ($row = pg_fetch_assoc($result)) {
            $envio_exitoso = enviarCorreoRecordatorioClase(
                $row['correo'],
                $row['nombre'],
                $row['apellido'],
                $row['nombre_clase'],
                $row['hora_inicio'],
                $row['nombre_instructor'] . ' ' . $row['apellido_instructor']
            );
            
            if ($envio_exitoso) {
                $enviados++;
                $resultados[] = [
                    'miembro' => $row['nombre'] . ' ' . $row['apellido'],
                    'correo' => $row['correo'],
                    'clase' => $row['nombre_clase'],
                    'hora' => $row['hora_inicio'],
                    'estado' => 'enviado'
                ];
            } else {
                $errores++;
                $resultados[] = [
                    'miembro' => $row['nombre'] . ' ' . $row['apellido'],
                    'correo' => $row['correo'],
                    'clase' => $row['nombre_clase'],
                    'hora' => $row['hora_inicio'],
                    'estado' => 'error'
                ];
            }
        }
        
        return [
            'success' => true,
            'enviados' => $enviados,
            'errores' => $errores,
            'total' => $total,
            'dia' => $dia_actual,
            'detalles' => $resultados
        ];
        
    } catch (Exception $e) {
        error_log("Error en enviarRecordatoriosClasesDelDia: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Envía correo de recordatorio de vencimiento de membresía
 */
function enviarCorreoVencimientoMembresia($correo, $nombre, $apellido, $fecha_fin, $membresia, $dias_restantes) {
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gymportezuelo585@gmail.com';
        $mail->Password   = 'pesr akmw dlhl zqrh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('gymportezuelo585@gmail.com', 'Portezuelo Gym');
        $mail->addAddress($correo, $nombre . ' ' . $apellido);

        $mail->isHTML(true);
        $mail->Subject = 'Recordatorio de Vencimiento de Membresía - Portezuelo Gym';
        
        $fecha_formateada = date('d/m/Y', strtotime($fecha_fin));
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #003366;'>Recordatorio de Membresía</h2>
            
            <p>Hola <strong>$nombre $apellido</strong>,</p>
            
            <div style='background-color: #fff3cd; padding: 20px; border-radius: 10px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                <h3 style='color: #856404; margin-top: 0;'>⚠️ Tu membresía está por vencer</h3>
                <p><strong>Tipo de membresía:</strong> $membresia</p>
                <p><strong>Fecha de vencimiento:</strong> $fecha_formateada</p>
                <p><strong>Días restantes:</strong> $dias_restantes día(s)</p>
            </div>
            
            <p>Te recomendamos renovar tu membresía para continuar disfrutando de todos nuestros servicios:</p>
            <ul>
                <li>Acceso ilimitado al gimnasio</li>
                <li>Uso de todas las instalaciones</li>
                <li>Participación en clases grupales</li>
                <li>Asesoramiento profesional</li>
            </ul>
            
            <div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 0; color: #155724;'>
                    <strong>💡 ¿Necesitas ayuda?</strong><br>
                    Visítanos en nuestro gimnasio o contáctanos para renovar tu membresía.
                </p>
            </div>
            
            <hr style='border: none; border-top: 2px solid #eee; margin: 30px 0;'>
            
            <p style='color: #666; font-size: 14px;'>
                Atentamente,<br>
                <strong>Equipo Portezuelo Gym</strong><br>
                <em>Tu salud es nuestra prioridad</em>
            </p>
        </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando recordatorio de membresía a $correo: " . $e->getMessage());
        return false;
    }
}

/**
 * Envía correo de recordatorio de clase
 */
function enviarCorreoRecordatorioClase($correo, $nombre, $apellido, $clase, $hora, $instructor) {
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gymportezuelo585@gmail.com';
        $mail->Password   = 'pesr akmw dlhl zqrh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('gymportezuelo585@gmail.com', 'Portezuelo Gym');
        $mail->addAddress($correo, $nombre . ' ' . $apellido);

        $mail->isHTML(true);
        $mail->Subject = 'Recordatorio de Clase Hoy - Portezuelo Gym';
        
        $hora_formateada = date('h:i A', strtotime($hora));
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #003366;'>Recordatorio de Clase</h2>
            
            <p>Hola <strong>$nombre $apellido</strong>,</p>
            
            <div style='background-color: #d1ecf1; padding: 20px; border-radius: 10px; border-left: 4px solid #0c5460; margin: 20px 0;'>
                <h3 style='color: #0c5460; margin-top: 0;'>📅 Tienes una clase programada para hoy</h3>
                <p><strong>Clase:</strong> $clase</p>
                <p><strong>Hora:</strong> $hora_formateada</p>
                <p><strong>Instructor:</strong> $instructor</p>
            </div>
            
            <p>¡No olvides asistir! Te recomendamos:</p>
            <ul>
                <li>Llegar 10 minutos antes</li>
                <li>Usar ropa cómoda</li>
                <li>Traer una toalla y agua</li>
                <li>Calentar antes de comenzar</li>
            </ul>
            
            <div style='background-color: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 0; color: #383d41;'>
                    <strong>📍 Ubicación:</strong><br>
                    Portezuelo Gym - C. Morelos, 47920 Portezuelo, Jal.
                </p>
            </div>
            
            <hr style='border: none; border-top: 2px solid #eee; margin: 30px 0;'>
            
            <p style='color: #666; font-size: 14px;'>
                ¡Te esperamos!<br>
                <strong>Equipo Portezuelo Gym</strong><br>
                <em>Committed to your fitness journey</em>
            </p>
        </div>";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando recordatorio de clase a $correo: " . $e->getMessage());
        return false;
    }
}
?>