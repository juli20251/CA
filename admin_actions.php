<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!esAdmin()) {
    echo json_encode(['success' => false, 'mensaje' => 'No tienes permisos']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        // =====================
        // CONFIGURACIÓN
        // =====================
        case 'toggle_votaciones':
            $estado = $_POST['estado'] ?? 'false';
            actualizarConfig('votaciones_abiertas', $estado, $pdo);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Estado de votaciones actualizado'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'toggle_ternas':
            $estado = $_POST['estado'] ?? 'false';
            actualizarConfig('mostrar_ternas', $estado, $pdo);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Visibilidad de ternas actualizada'
            ], JSON_UNESCAPED_UNICODE);
            break;
        
        case 'guardar_fecha_revelacion':
            $fecha = $_POST['fecha'] ?? '';
            
            if (empty($fecha)) {
                throw new Exception('La fecha es obligatoria');
            }
            
            // Validar que sea una fecha válida
            $timestamp = strtotime($fecha);
            if ($timestamp === false) {
                throw new Exception('Fecha no válida');
            }
            
            // Validar que la fecha sea futura
            if ($timestamp < time()) {
                throw new Exception('La fecha debe ser futura');
            }
            
            // Guardar en formato MySQL datetime
            $fechaMySQL = date('Y-m-d H:i:s', $timestamp);
            actualizarConfig('fecha_revelacion', $fechaMySQL, $pdo);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Fecha de revelación guardada correctamente',
                'fecha_formateada' => date('d/m/Y - H:i', $timestamp)
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'limpiar_fecha_revelacion':
            actualizarConfig('fecha_revelacion', '', $pdo);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Fecha de revelación eliminada'
            ], JSON_UNESCAPED_UNICODE);
            break;
        
        // =====================
        // CATEGORÍAS
        // =====================
        case 'listar_categorias':
            $stmt = $pdo->query("
                SELECT c.*, COUNT(n.id) as total_nominados 
                FROM categorias c 
                LEFT JOIN nominados n ON c.id = n.categoria_id 
                GROUP BY c.id 
                ORDER BY c.orden
            ");
            $categorias = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'crear_categoria':
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $orden = intval($_POST['orden'] ?? 0);
            $visible = ($_POST['visible'] ?? 'false') === 'true' ? 1 : 0;
            
            if (empty($nombre)) {
                throw new Exception('El nombre de la categoría es obligatorio');
            }
            
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, orden, visible) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $orden, $visible]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Categoría creada exitosamente',
                'id' => $pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'actualizar_categoria':
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $orden = intval($_POST['orden'] ?? 0);
            $visible = ($_POST['visible'] ?? 'false') === 'true' ? 1 : 0;
            
            if (empty($nombre)) {
                throw new Exception('El nombre de la categoría es obligatorio');
            }
            
            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, orden = ?, visible = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $orden, $visible, $id]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Categoría actualizada exitosamente'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'eliminar_categoria':
            $id = intval($_POST['id'] ?? 0);
            
            // Verificar si tiene nominados
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM nominados WHERE categoria_id = ?");
            $stmt->execute([$id]);
            $total = $stmt->fetch()['total'];
            
            if ($total > 0) {
                throw new Exception('No se puede eliminar una categoría con nominados. Elimina primero los nominados.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Categoría eliminada exitosamente'
            ], JSON_UNESCAPED_UNICODE);
            break;
        
        // =====================
        // NOMINADOS
        // =====================
        case 'listar_nominados':
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            
            if ($categoriaId > 0) {
                $stmt = $pdo->prepare("
                    SELECT n.*, c.nombre as categoria_nombre 
                    FROM nominados n 
                    INNER JOIN categorias c ON n.categoria_id = c.id 
                    WHERE n.categoria_id = ?
                    ORDER BY n.nombre
                ");
                $stmt->execute([$categoriaId]);
            } else {
                $stmt = $pdo->query("
                    SELECT n.*, c.nombre as categoria_nombre 
                    FROM nominados n 
                    INNER JOIN categorias c ON n.categoria_id = c.id 
                    ORDER BY c.orden, n.nombre
                ");
            }
            
            $nominados = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'nominados' => $nominados
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'crear_nominado':
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $imagenUrl = trim($_POST['imagen_url'] ?? '');
            $tipoMedia = $_POST['tipo_media'] ?? 'imagen';
            
            if (empty($nombre)) {
                throw new Exception('El nombre del nominado es obligatorio');
            }
            
            if (empty($imagenUrl)) {
                throw new Exception('La URL del medio es obligatoria');
            }
            
            if ($categoriaId <= 0) {
                throw new Exception('Debes seleccionar una categoría');
            }
            
            // Verificar que la categoría existe
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
            $stmt->execute([$categoriaId]);
            if (!$stmt->fetch()) {
                throw new Exception('La categoría seleccionada no existe');
            }
            
            $stmt = $pdo->prepare("INSERT INTO nominados (categoria_id, nombre, descripcion, imagen_url, tipo_media) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$categoriaId, $nombre, $descripcion, $imagenUrl, $tipoMedia]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Nominado creado exitosamente',
                'id' => $pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'actualizar_nominado':
            $id = intval($_POST['id'] ?? 0);
            $categoriaId = intval($_POST['categoria_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $imagenUrl = trim($_POST['imagen_url'] ?? '');
            $tipoMedia = $_POST['tipo_media'] ?? 'imagen';
            
            if (empty($nombre)) {
                throw new Exception('El nombre del nominado es obligatorio');
            }
            
            if (empty($imagenUrl)) {
                throw new Exception('La URL del medio es obligatoria');
            }
            
            if ($categoriaId <= 0) {
                throw new Exception('Debes seleccionar una categoría');
            }
            
            $stmt = $pdo->prepare("UPDATE nominados SET categoria_id = ?, nombre = ?, descripcion = ?, imagen_url = ?, tipo_media = ? WHERE id = ?");
            $stmt->execute([$categoriaId, $nombre, $descripcion, $imagenUrl, $tipoMedia, $id]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Nominado actualizado exitosamente'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'eliminar_nominado':
            $id = intval($_POST['id'] ?? 0);
            
            // Eliminar votos asociados primero
            $stmt = $pdo->prepare("DELETE FROM votos WHERE nominado_id = ?");
            $stmt->execute([$id]);
            
            // Eliminar nominado
            $stmt = $pdo->prepare("DELETE FROM nominados WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Nominado eliminado exitosamente'
            ], JSON_UNESCAPED_UNICODE);
            break;
        
        // =====================
        // RESULTADOS
        // =====================
        case 'obtener_resultados':
            $stmt = $pdo->query("SELECT * FROM categorias ORDER BY orden");
            $categorias = $stmt->fetchAll();
            
            foreach ($categorias as &$categoria) {
                $stmt = $pdo->prepare("
                    SELECT n.*, COUNT(v.id) as votos
                    FROM nominados n
                    LEFT JOIN votos v ON n.id = v.nominado_id
                    WHERE n.categoria_id = ?
                    GROUP BY n.id
                    ORDER BY votos DESC
                ");
                $stmt->execute([$categoria['id']]);
                $categoria['resultados'] = $stmt->fetchAll();
                
                $totalVotosCategoria = array_sum(array_column($categoria['resultados'], 'votos'));
                $categoria['total_votos'] = $totalVotosCategoria;
            }
            
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
            ], JSON_UNESCAPED_UNICODE);
            break;
            
            case 'listar_clips_nominados':
    $estado = $_POST['estado'] ?? 'todos';
    
    $sql = "
        SELECT c.*, u.nombre as usuario_nombre, cat.nombre as categoria_nombre
        FROM clips_usuarios c
        INNER JOIN usuarios u ON c.usuario_id = u.id
        INNER JOIN categorias cat ON c.categoria_id = cat.id
    ";
    
    if ($estado !== 'todos') {
        $sql .= " WHERE c.estado = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY c.fecha_nominacion DESC");
        $stmt->execute([$estado]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY c.fecha_nominacion DESC");
    }
    
    echo json_encode([
        'success' => true,
        'clips' => $stmt->fetchAll()
    ], JSON_UNESCAPED_UNICODE);
    break;

case 'cambiar_estado_clip':
    $clipId = intval($_POST['clip_id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    
    if (!in_array($estado, ['aprobado', 'rechazado', 'pendiente'])) {
        throw new Exception('Estado no válido');
    }
    
    $stmt = $pdo->prepare("UPDATE clips_usuarios SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $clipId]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Estado actualizado correctamente'
    ], JSON_UNESCAPED_UNICODE);
    break;

    case 'crear_categoria':
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);
    $visible = ($_POST['visible'] ?? 'false') === 'true' ? 1 : 0;
    $tipoVotacion = $_POST['tipo_votacion'] ?? 'normal'; // ← NUEVO
    
    if (empty($nombre)) {
        throw new Exception('El nombre de la categoría es obligatorio');
    }
    
    if (!in_array($tipoVotacion, ['normal', 'texto_libre', 'nominacion_usuarios'])) { // ← NUEVO
        $tipoVotacion = 'normal';
    }
    
    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, orden, visible, tipo_votacion) VALUES (?, ?, ?, ?, ?)"); // ← MODIFICADO
    $stmt->execute([$nombre, $descripcion, $orden, $visible, $tipoVotacion]); // ← MODIFICADO
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Categoría creada exitosamente',
        'id' => $pdo->lastInsertId()
    ], JSON_UNESCAPED_UNICODE);
    break;

case 'actualizar_categoria':
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);
    $visible = ($_POST['visible'] ?? 'false') === 'true' ? 1 : 0;
    $tipoVotacion = $_POST['tipo_votacion'] ?? 'normal'; // ← NUEVO
    
    if (empty($nombre)) {
        throw new Exception('El nombre de la categoría es obligatorio');
    }
    
    if (!in_array($tipoVotacion, ['normal', 'texto_libre', 'nominacion_usuarios'])) { // ← NUEVO
        $tipoVotacion = 'normal';
    }
    
    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, orden = ?, visible = ?, tipo_votacion = ? WHERE id = ?"); // ← MODIFICADO
    $stmt->execute([$nombre, $descripcion, $orden, $visible, $tipoVotacion, $id]); // ← MODIFICADO
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Categoría actualizada exitosamente'
    ], JSON_UNESCAPED_UNICODE);
    break;

    case 'listar_respuestas_texto':
    $categoriaId = intval($_POST['categoria_id'] ?? 0);
    
    $sql = "
        SELECT r.*, u.nombre as usuario_nombre, u.email as usuario_email
        FROM respuestas_texto r
        INNER JOIN usuarios u ON r.usuario_id = u.id
    ";
    
    if ($categoriaId > 0) {
        $sql .= " WHERE r.categoria_id = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY r.fecha_respuesta DESC");
        $stmt->execute([$categoriaId]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY r.fecha_respuesta DESC");
    }
    
    echo json_encode([
        'success' => true,
        'respuestas' => $stmt->fetchAll()
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