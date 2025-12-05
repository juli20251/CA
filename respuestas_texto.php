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
        case 'guardar_respuesta':
            // Verificar que las votaciones estén abiertas
            $votacionesAbiertas = obtenerConfig('votaciones_abiertas', $pdo) === 'true';
            if (!$votacionesAbiertas) {
                throw new Exception('Las votaciones están cerradas');
            }
            
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            $respuesta = trim($_POST['respuesta'] ?? '');
            
            // Validaciones
            if (empty($respuesta)) {
                throw new Exception('La respuesta no puede estar vacía');
            }
            
            if (mb_strlen($respuesta) < 3) {
                throw new Exception('La respuesta debe tener al menos 3 caracteres');
            }
            
            if (mb_strlen($respuesta) > 500) {
                throw new Exception('La respuesta no puede exceder 500 caracteres');
            }
            
            // Verificar que la categoría existe y es de tipo texto_libre
            $stmt = $pdo->prepare("SELECT id, tipo_votacion FROM categorias WHERE id = ? AND visible = 1");
            $stmt->execute([$categoriaId]);
            $categoria = $stmt->fetch();
            
            if (!$categoria) {
                throw new Exception('Categoría no válida');
            }
            
            if ($categoria['tipo_votacion'] !== 'texto_libre') {
                throw new Exception('Esta categoría no acepta respuestas de texto');
            }
            
            // Verificar si el usuario ya respondió en esta categoría
            $stmt = $pdo->prepare("SELECT id FROM respuestas_texto WHERE usuario_id = ? AND categoria_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
            
            if ($stmt->fetch()) {
                // Actualizar respuesta existente
                $stmt = $pdo->prepare("
                    UPDATE respuestas_texto 
                    SET respuesta = ?, fecha_respuesta = NOW() 
                    WHERE usuario_id = ? AND categoria_id = ?
                ");
                $stmt->execute([$respuesta, $_SESSION['usuario_id'], $categoriaId]);
                $mensaje = 'Respuesta actualizada correctamente';
            } else {
                // Insertar nueva respuesta
                $stmt = $pdo->prepare("
                    INSERT INTO respuestas_texto (usuario_id, categoria_id, respuesta) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['usuario_id'], $categoriaId, $respuesta]);
                $mensaje = 'Respuesta guardada correctamente';
            }
            
            echo json_encode([
                'success' => true,
                'mensaje' => $mensaje
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'obtener_mi_respuesta':
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT * FROM respuestas_texto 
                WHERE usuario_id = ? AND categoria_id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
            $respuesta = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'respuesta' => $respuesta
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'eliminar_respuesta':
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            
            $stmt = $pdo->prepare("
                DELETE FROM respuestas_texto 
                WHERE usuario_id = ? AND categoria_id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Respuesta eliminada correctamente'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('No se pudo eliminar la respuesta');
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