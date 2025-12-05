<?php
// Configuración de codificación
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Mostrar errores solo en desarrollo (comentar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'chechoawards');
define('DB_USER', 'root');
define('DB_PASS', '');

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
    die("Error de conexión: " . $e->getMessage());
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