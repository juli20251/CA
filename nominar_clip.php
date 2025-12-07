<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaLogueado()) {
    echo json_encode(['success' => false, 'mensaje' => 'Debes iniciar sesión']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'nominar_clip':

            $votacionesAbiertas = obtenerConfig('votaciones_abiertas', $pdo) === 'true';
    if (!$votacionesAbiertas) {
        throw new Exception('Las votaciones están cerradas');
    }
    
    // NUEVO: Verificar si ya confirmó
    $stmt = $pdo->prepare("SELECT votos_confirmados FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    
    if ($usuario['votos_confirmados'] == 1) {
        throw new Exception('⛔ Has confirmado tu participación. Ya no puedes hacer cambios.');
    }
    
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            $votacionesAbiertas = obtenerConfig('votaciones_abiertas', $pdo) === 'true';

if (!$votacionesAbiertas) {
    throw new Exception('Las votaciones están cerradas');
}
            $titulo = trim($_POST['titulo'] ?? '');
            $urlClip = trim($_POST['url_clip'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            
            // Validaciones
            if (empty($titulo)) {
                throw new Exception('El título es obligatorio');
            }
            
            if (empty($urlClip)) {
                throw new Exception('La URL del clip es obligatoria');
            }
            
            // Detectar tipo de clip y validar URL
            $tipoClip = null;
            if (preg_match('/youtube\.com|youtu\.be/i', $urlClip)) {
                $tipoClip = 'youtube';
                if (!preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $urlClip)) {
                    throw new Exception('URL de YouTube no válida');
                }
            } elseif (preg_match('/twitch\.tv/i', $urlClip)) {
                $tipoClip = 'twitch';
                if (!preg_match('/clips\.twitch\.tv\/|twitch\.tv\/videos\//', $urlClip)) {
                    throw new Exception('URL de Twitch no válida (debe ser un clip o VOD)');
                }
            } else {
                throw new Exception('URL no válida. Solo se aceptan clips de YouTube o Twitch');
            }
            
            // Verificar que la categoría existe y acepta nominaciones de usuarios
            $stmt = $pdo->prepare("SELECT id, tipo_votacion FROM categorias WHERE id = ? AND visible = 1");
            $stmt->execute([$categoriaId]);
            $categoria = $stmt->fetch();
            
            if (!$categoria) {
                throw new Exception('Categoría no válida');
            }
            
            if ($categoria['tipo_votacion'] !== 'nominacion_usuarios') {
                throw new Exception('Esta categoría no acepta nominaciones de usuarios');
            }
            
            // Verificar si el usuario ya nominó un clip en esta categoría
            $stmt = $pdo->prepare("SELECT id FROM clips_usuarios WHERE usuario_id = ? AND categoria_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
            
            if ($stmt->fetch()) {
                throw new Exception('Ya has nominado un clip en esta categoría');
            }
            
            // Insertar nominación
            $stmt = $pdo->prepare("
                INSERT INTO clips_usuarios (usuario_id, categoria_id, titulo, url_clip, descripcion, tipo_clip) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['usuario_id'],
                $categoriaId,
                $titulo,
                $urlClip,
                $descripcion,
                $tipoClip
            ]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => '¡Clip nominado exitosamente! Será revisado por los administradores.'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'obtener_mi_nominacion':
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT * FROM clips_usuarios 
                WHERE usuario_id = ? AND categoria_id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
            $nominacion = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'nominacion' => $nominacion
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'eliminar_nominacion':
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                DELETE FROM clips_usuarios 
                WHERE usuario_id = ? AND categoria_id = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Nominación eliminada correctamente'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('No se pudo eliminar la nominación');
            }
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