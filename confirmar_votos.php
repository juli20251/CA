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
        case 'confirmar_votos':
            // Verificar que el usuario no haya confirmado ya
            $stmt = $pdo->prepare("SELECT votos_confirmados FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch();
            
            if ($usuario['votos_confirmados'] == 1) {
                throw new Exception('Ya has confirmado tus votos anteriormente');
            }
            
            // Verificar que tenga al menos un voto
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $totalVotos = $stmt->fetch()['total'];
            
            // También contar respuestas de texto
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM respuestas_texto WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $totalRespuestas = $stmt->fetch()['total'];
            
            // También contar clips nominados
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clips_usuarios WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $totalClips = $stmt->fetch()['total'];
            
            $totalParticipacion = $totalVotos + $totalRespuestas + $totalClips;
            
            if ($totalParticipacion == 0) {
                throw new Exception('Debes votar en al menos una categoría antes de confirmar');
            }
            
            // Confirmar votos
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET votos_confirmados = 1, fecha_confirmacion = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            
            // Actualizar sesión
            $_SESSION['votos_confirmados'] = 1;
            
            echo json_encode([
                'success' => true,
                'mensaje' => '¡Votos confirmados exitosamente! Ya no podrás realizar cambios.',
                'total_votos' => $totalVotos,
                'total_respuestas' => $totalRespuestas,
                'total_clips' => $totalClips
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'verificar_estado':
            $stmt = $pdo->prepare("
                SELECT votos_confirmados, fecha_confirmacion 
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch();
            
            // Contar participación
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votos WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $totalVotos = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM respuestas_texto WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $totalRespuestas = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clips_usuarios WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $totalClips = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'confirmado' => $usuario['votos_confirmados'] == 1,
                'fecha_confirmacion' => $usuario['fecha_confirmacion'],
                'total_votos' => $totalVotos,
                'total_respuestas' => $totalRespuestas,
                'total_clips' => $totalClips
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