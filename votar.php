<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!estaLogueado()) {
    echo json_encode(['success' => false, 'mensaje' => 'Debes iniciar sesión']);
    exit;
}

$categoriaId = $_POST['categoria_id'] ?? 0;
$nominadoId = $_POST['nominado_id'] ?? 0;

try {
    // Verificar que las votaciones estén abiertas
    $votacionesAbiertas = obtenerConfig('votaciones_abiertas', $pdo) === 'true';
    if (!$votacionesAbiertas) {
        throw new Exception('Las votaciones están cerradas');
    }
    
    // Verificar que la categoría existe y está visible
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND visible = 1");
    $stmt->execute([$categoriaId]);
    if (!$stmt->fetch()) {
        throw new Exception('Categoría no válida');
    }
    
    // Verificar que el nominado existe y pertenece a la categoría
    $stmt = $pdo->prepare("SELECT id FROM nominados WHERE id = ? AND categoria_id = ?");
    $stmt->execute([$nominadoId, $categoriaId]);
    if (!$stmt->fetch()) {
        throw new Exception('Nominado no válido');
    }
    
    // Verificar si el usuario ya votó en esta categoría
    $stmt = $pdo->prepare("SELECT id FROM votos WHERE usuario_id = ? AND categoria_id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $categoriaId]);
    $votoExistente = $stmt->fetch();
    
    if ($votoExistente) {
        // Actualizar voto
        $stmt = $pdo->prepare("UPDATE votos SET nominado_id = ?, fecha_voto = NOW() WHERE usuario_id = ? AND categoria_id = ?");
        $stmt->execute([$nominadoId, $_SESSION['usuario_id'], $categoriaId]);
        $mensaje = 'Voto actualizado correctamente';
    } else {
        // Insertar nuevo voto
        $stmt = $pdo->prepare("INSERT INTO votos (usuario_id, nominado_id, categoria_id) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $nominadoId, $categoriaId]);
        $mensaje = 'Voto registrado correctamente';
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => $mensaje
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}
?>