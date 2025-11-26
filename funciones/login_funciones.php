<?php
session_start();
require_once "../inc/conexion.php"; // tu conexión con pg_connect()

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}

$usuario = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    $_SESSION['error_login'] = "Por favor completa usuario y contraseña.";
    header("Location: ../login.php");
    exit();
}

// Buscar usuario
$sql = "SELECT id_usuario, usuario, password, rol FROM usuarios WHERE usuario = $1 LIMIT 1";
$result = pg_query_params($conn, $sql, array($usuario));

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_login'] = "⚠️ Usuario no encontrado";
    header("Location: ../login.php");
    exit();
}

$row = pg_fetch_assoc($result);
$stored = $row['password'];

// 1) Detectar si el valor almacenado es un hash creado por password_hash()
$info = password_get_info($stored);

// Si password_get_info devuelve algo distinto de 0 en 'algo', entonces parece un hash compatible
if (isset($info['algo']) && $info['algo'] !== 0) {
    // Comparar con password_verify
    if (password_verify($password, $stored)) {
        // Login exitoso
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['rol'] = $row['rol']; // ← ESTA ES LA LÍNEA IMPORTANTE

        header("Location: ../JS/inicio.php");
        exit();
    } else {
        $_SESSION['error_login'] = "⚠️ Contraseña incorrecta";
        header("Location: ../login.php");
        exit();
    }
} else {
    // Parece que la contraseña en BD NO está en un formato generado por password_hash()
    // Posible texto plano o hash distinto (md5/sha1). Primero intentar igualdad (migración segura si coincide).
    if ($password === $stored) {
        // Migrar: crear hash seguro y actualizar BD
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $update = pg_query_params($conn,
            "UPDATE usuarios SET password = $1 WHERE id_usuario = $2",
            array($new_hash, $row['id_usuario'])
        );
        // Ignorar fallo en update por ahora (pero podrías manejarlo)
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['rol'] = $row['rol']; // ← ESTA ES LA LÍNEA IMPORTANTE

        header("Location: ../JS/inicio.php");
        exit();
    } else {
        // No coincide. Puede que la BD tenga md5/sha1/etc. (prueba varias)
        // Ejemplo: si usas MD5 en BD (NO recomendado), puedes probar:
        if (hash('md5', $password) === $stored) {
            // Migrar a password_hash
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update = pg_query_params($conn,
                "UPDATE usuarios SET password = $1 WHERE id_usuario = $2",
                array($new_hash, $row['id_usuario'])
            );
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['rol'] = $row['rol']; // ← ESTA ES LA LÍNEA IMPORTANTE

            header("Location: ../JS/inicio.php");
            exit();
        }

        // Si llegamos aquí, no se pudo verificar la contraseña
        $_SESSION['error_login'] = "⚠️ Contraseña incorrecta";
        header("Location: ../login.php");
        exit();
    }
}
?>