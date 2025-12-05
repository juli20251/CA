<?php
// Asegurar que no haya salida antes del JSON
ob_start();

require_once 'config.php';

// Limpiar cualquier salida previa
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

// Log para depuración (comentar en producción)
error_log("POST data: " . print_r($_POST, true));

$accion = $_POST['accion'] ?? '';

if (empty($accion)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se especificó ninguna acción'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    switch ($accion) {
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                throw new Exception('Por favor completa todos los campos');
            }
            
            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email no válido');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if (!$usuario || !password_verify($password, $usuario['password'])) {
                throw new Exception('Email o contraseña incorrectos');
            }
            
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
            
            // Determinar redirección según rol
            $redirect = ($usuario['rol'] === 'admin') ? 'admin_dashboard.php' : 'votacion.php';
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Inicio de sesión exitoso',
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol'],
                'redirect' => $redirect
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'registro':
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['passwordConfirm'] ?? '';
            
            // Validaciones
            if (empty($nombre) || empty($email) || empty($password)) {
                throw new Exception('Por favor completa todos los campos');
            }
            
            if (strlen($nombre) < 3) {
                throw new Exception('El nombre debe tener al menos 3 caracteres');
            }
            
            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email no válido');
            }
            
            if ($password !== $passwordConfirm) {
                throw new Exception('Las contraseñas no coinciden');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Este email ya está registrado');
            }
            
            // Crear usuario
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$nombre, $email, $passwordHash]);
            
            // Iniciar sesión automáticamente
            $usuarioId = $pdo->lastInsertId();
            $_SESSION['usuario_id'] = $usuarioId;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            $_SESSION['rol'] = 'user';
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Registro exitoso. ¡Bienvenido!',
                'nombre' => $nombre,
                'email' => $email,
                'rol' => 'user',
                'redirect' => 'votacion.php'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'logout':
            session_destroy();
            echo json_encode([
                'success' => true,
                'mensaje' => 'Sesión cerrada correctamente',
                'redirect' => 'index.php'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>