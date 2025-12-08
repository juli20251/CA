<?php
// Configuración de codificación
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// ⚠️ IMPORTANTE: Comentar estas líneas en PRODUCCIÓN
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// En producción, usar esto en su lugar:
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores al público
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Guardar errores en archivo

// ========================================
// CONFIGURACIÓN DE BASE DE DATOS
// ========================================

// Detectar si estamos en local o servidor
$esLocal = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0
);

if ($esLocal) {
    // 💻 CONFIGURACIÓN LOCAL (DESARROLLO)
    define('DB_HOST', 'https://mattprofe.com.ar/10014/');
    define('DB_NAME', '10014');
    define('DB_USER', '10014');
    define('DB_PASS', 'perro.cipres.jugo');
} else {
    // 🌐 CONFIGURACIÓN SERVIDOR (PRODUCCIÓN)
    // ⚠️ CAMBIAR ESTOS VALORES CON LOS DE TU HOSTING
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'NOMBRE_DE_TU_BD');      // Ej: tuusuario_chechoawards
    define('DB_USER', 'USUARIO_DE_TU_BD');     // Ej: tuusuario_dbuser
    define('DB_PASS', 'CONTRASEÑA_DE_TU_BD');  // Tu contraseña de BD
}

// Configuración de la aplicación
// Detectar automáticamente la URL base del proyecto
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptName);

// Eliminar el nombre del archivo si está presente
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

define('SITE_URL', $protocol . '://' . $host . $basePath);
define('SITE_NAME', 'Checho Awards');

// Iniciar sesión con configuración segura
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Conexión a la base de datos con UTF-8
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    // En producción, no mostrar detalles del error
    if ($esLocal) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        error_log("Error de conexión a BD: " . $e->getMessage());
        die("Error de conexión a la base de datos. Por favor contacta al administrador.");
    }
}

// Función para verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

// Función para verificar si el usuario es admin
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// Función para redirigir
function redirigir($url) {
    // Si la URL ya es completa, usarla directamente
    if (strpos($url, 'http') === 0) {
        $fullUrl = $url;
    } else {
        // Si no, agregar SITE_URL
        $fullUrl = SITE_URL . '/' . ltrim($url, '/');
    }
    
    header("Location: " . $fullUrl);
    exit();
}

// Función para obtener configuración
function obtenerConfig($clave, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $result = $stmt->fetch();
        return $result ? $result['valor'] : null;
    } catch (PDOException $e) {
        error_log("Error al obtener config: " . $e->getMessage());
        return null;
    }
}

// Función para actualizar configuración
function actualizarConfig($clave, $valor, $pdo) {
    try {
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        return $stmt->execute([$valor, $clave]);
    } catch (PDOException $e) {
        error_log("Error al actualizar config: " . $e->getMessage());
        return false;
    }
}

// Función para sanitizar salida HTML
function e($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Función para obtener URL embed de videos
function obtenerURLEmbed($url) {
    $url = trim($url);
    
    // YouTube - formato watch
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $id)) {
        return 'https://www.youtube.com/embed/' . $id[1];
    }
    
    // YouTube - formato corto youtu.be
    if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $id)) {
        return 'https://www.youtube.com/embed/' . $id[1];
    }
    
    // YouTube - formato embed
    if (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $id)) {
        return 'https://www.youtube.com/embed/' . $id[1];
    }
    
    // Twitch Clips
    if (preg_match('/clips\.twitch\.tv\/([^\&\?\/]+)/', $url, $id)) {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return 'https://clips.twitch.tv/embed?clip=' . $id[1] . '&parent=' . $domain;
    }
    
    // Twitch VOD
    if (preg_match('/twitch\.tv\/videos\/(\d+)/', $url, $id)) {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return 'https://player.twitch.tv/?video=' . $id[1] . '&parent=' . $domain;
    }
    
    // Si no es reconocido, devolver la URL original (sanitizada)
    return e($url);
}
?>