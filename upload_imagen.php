<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!esAdmin()) {
    echo json_encode(['success' => false, 'mensaje' => 'No tienes permisos']);
    exit;
}

try {
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ninguna imagen o hubo un error en la carga');
    }
    
    $file = $_FILES['imagen'];
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, GIF, WEBP)');
    }
    
    // Validar tamaño (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB');
    }
    
    // Crear carpeta uploads si no existe
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('img_') . '_' . time() . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;
    
    // Mover archivo a la carpeta de uploads
    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Devolver la URL de la imagen
    $urlImagen = SITE_URL . '/' . $rutaDestino;
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Imagen subida exitosamente',
        'url' => $urlImagen,
        'ruta' => $rutaDestino
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>