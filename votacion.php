<?php
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!estaLogueado()) {
    redirigir('login.html');
}

// Si es admin, redirigir al dashboard
if (esAdmin()) {
    redirigir('admin_dashboard.php');
}

// Obtener configuración
$votacionesAbiertas = obtenerConfig('votaciones_abiertas', $pdo) === 'true';
$mostrarTernas = obtenerConfig('mostrar_ternas', $pdo) === 'true';

// Obtener categorías y nominados
$categorias = [];
if ($mostrarTernas) {
    $stmt = $pdo->query("SELECT * FROM categorias WHERE visible = 1 ORDER BY orden");
    $categorias = $stmt->fetchAll();
    
    foreach ($categorias as &$categoria) {
        $tipoVotacion = $categoria['tipo_votacion'] ?? 'normal';
        
        if ($tipoVotacion === 'normal') {
            // Categorías normales: cargar nominados
            $stmt = $pdo->prepare("SELECT * FROM nominados WHERE categoria_id = ? ORDER BY nombre");
            $stmt->execute([$categoria['id']]);
            $categoria['nominados'] = $stmt->fetchAll();
            
            // Verificar si el usuario ya votó en esta categoría
            $stmt = $pdo->prepare("SELECT nominado_id FROM votos WHERE usuario_id = ? AND categoria_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $categoria['id']]);
            $voto = $stmt->fetch();
            $categoria['voto_actual'] = $voto ? $voto['nominado_id'] : null;
        } elseif ($tipoVotacion === 'texto_libre') {
            // Categorías de texto libre: verificar si ya respondió
            $stmt = $pdo->prepare("SELECT id FROM respuestas_texto WHERE usuario_id = ? AND categoria_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $categoria['id']]);
            $categoria['voto_actual'] = $stmt->fetch() ? 1 : null;
        } elseif ($tipoVotacion === 'nominacion_usuarios') {
            // Categorías con nominación de usuarios: verificar si ya nominó
            $stmt = $pdo->prepare("SELECT id FROM clips_usuarios WHERE usuario_id = ? AND categoria_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $categoria['id']]);
            $categoria['voto_actual'] = $stmt->fetch() ? 1 : null;
        }
    }
}

// Calcular progreso de votación
$totalCategorias = count($categorias);
$categoriasVotadas = 0;
foreach ($categorias as $categoria) {
    if ($categoria['voto_actual']) {
        $categoriasVotadas++;
    }
}
$progreso = $totalCategorias > 0 ? round(($categoriasVotadas / $totalCategorias) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votación - Checho Awards 2025</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/votacion.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>🏆 CHECHO AWARDS</h1>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="votacion.php" class="nav-link active">Votar</a>
                <span class="user-welcome">Hola, <strong><?= e($_SESSION['nombre']) ?></strong></span>
                <button class="btn-salir" onclick="cerrarSesion()">Salir</button>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="votacion-container">
        <div class="container">
            <!-- Header de Votación -->
            <div class="votacion-header">
                <h2>🗳️ Panel de Votación</h2>
                <p>Selecciona tu nominado favorito en cada categoría</p>
            </div>

            <?php if (!$mostrarTernas): ?>
                <!-- Ternas no disponibles -->
                <div class="mensaje-info">
                    <div class="mensaje-icon">🎬</div>
                    <h3>Las ternas aún no están disponibles</h3>
                    <p>Por favor vuelve más tarde cuando se publiquen las nominaciones.</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 2rem;">Volver al Inicio</a>
                </div>
                
            <?php elseif (!$votacionesAbiertas): ?>
                <!-- Votaciones cerradas -->
                <div class="mensaje-info">
                    <div class="mensaje-icon">⏳</div>
                    <h3>Las votaciones están cerradas</h3>
                    <p>Las votaciones no están disponibles en este momento. Te notificaremos cuando se abran nuevamente.</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 2rem;">Volver al Inicio</a>
                </div>
                
            <?php else: ?>
                <!-- Barra de Progreso -->
                <div class="progreso-votacion">
                    <div class="progreso-info">
                        <h3>📊 Tu Progreso de Votación</h3>
                        <p><?= $categoriasVotadas ?> de <?= $totalCategorias ?> categorías completadas</p>
                    </div>
                    <div class="progreso-visual">
                        <div class="progreso-bar-container">
                            <div class="progreso-bar" style="width: <?= $progreso ?>%">
                                <span class="progreso-text"><?= $progreso ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de éxito/error -->
                <div id="mensajeGlobal" style="display: none;"></div>
                
                <!-- Categorías de Votación -->
                <div class="categorias-container">
                    <?php foreach ($categorias as $index => $categoria): ?>
                        <?php $tipoVotacion = $categoria['tipo_votacion'] ?? 'normal'; ?>
                        
                        <?php if ($tipoVotacion === 'texto_libre'): ?>
                            <!-- ========================================== -->
                            <!-- CATEGORÍA DE TEXTO LIBRE -->
                            <!-- ========================================== -->
                            <div class="categoria-texto-libre" id="categoria-<?= $categoria['id'] ?>">
                                <div class="categoria-header">
                                    <div class="categoria-numero">#<?= $index + 1 ?></div>
                                    <div class="categoria-info">
                                        <h3><?= e($categoria['nombre']) ?></h3>
                                        <?php if (!empty($categoria['descripcion'])): ?>
                                            <p class="categoria-desc"><?= e($categoria['descripcion']) ?></p>
                                        <?php else: ?>
                                            <p class="categoria-desc">✍️ Escribe tu respuesta</p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($categoria['voto_actual']): ?>
                                        <div class="categoria-status votado">
                                            <span class="status-icon">✓</span>
                                            <span class="status-text">Completado</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="categoria-status pendiente">
                                            <span class="status-icon">○</span>
                                            <span class="status-text">Pendiente</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div id="formularioTexto-<?= $categoria['id'] ?>" class="formulario-texto">
                                    <!-- Formulario se carga dinámicamente -->
                                </div>
                            </div>
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    cargarFormularioTexto(<?= $categoria['id'] ?>);
                                });
                            </script>
                        
                        <?php elseif ($tipoVotacion === 'nominacion_usuarios'): ?>
                            <!-- ========================================== -->
                            <!-- CATEGORÍA CON NOMINACIÓN DE USUARIOS -->
                            <!-- ========================================== -->
                            <div class="categoria-nominacion-usuario" id="categoria-<?= $categoria['id'] ?>">
                                <div class="categoria-header">
                                    <div class="categoria-numero">#<?= $index + 1 ?></div>
                                    <div class="categoria-info">
                                        <h3><?= e($categoria['nombre']) ?></h3>
                                        <?php if (!empty($categoria['descripcion'])): ?>
                                            <p class="categoria-desc"><?= e($categoria['descripcion']) ?></p>
                                        <?php else: ?>
                                            <p class="categoria-desc">🎬 Nomina tu clip favorito del año</p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($categoria['voto_actual']): ?>
                                        <div class="categoria-status votado">
                                            <span class="status-icon">✓</span>
                                            <span class="status-text">Nominado</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="categoria-status pendiente">
                                            <span class="status-icon">○</span>
                                            <span class="status-text">Pendiente</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div id="formularioNominacion-<?= $categoria['id'] ?>" class="formulario-nominacion">
                                    <!-- Formulario se carga dinámicamente -->
                                </div>
                            </div>
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    cargarFormularioNominacion(<?= $categoria['id'] ?>);
                                });
                            </script>

                        <?php else: ?>
                            <!-- ========================================== -->
                            <!-- CATEGORÍA NORMAL (CON NOMINADOS) -->
                            <!-- ========================================== -->
                            <div class="categoria-votacion" id="categoria-<?= $categoria['id'] ?>">
                                <div class="categoria-header">
                                    <div class="categoria-numero">#<?= $index + 1 ?></div>
                                    <div class="categoria-info">
                                        <h3><?= e($categoria['nombre']) ?></h3>
                                        <?php if (!empty($categoria['descripcion'])): ?>
                                            <p class="categoria-desc"><?= e($categoria['descripcion']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($categoria['voto_actual']): ?>
                                        <div class="categoria-status votado">
                                            <span class="status-icon">✓</span>
                                            <span class="status-text">Votado</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="categoria-status pendiente">
                                            <span class="status-icon">○</span>
                                            <span class="status-text">Pendiente</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="nominados-votacion">
                                    <?php foreach ($categoria['nominados'] as $nominado): ?>
                                        <div class="nominado-card <?= $categoria['voto_actual'] == $nominado['id'] ? 'seleccionado' : '' ?>" 
                                             data-nominado-id="<?= $nominado['id'] ?>"
                                             onclick="seleccionarNominado(<?= $categoria['id'] ?>, <?= $nominado['id'] ?>, this)">
                                            
                                            <div class="nominado-media">
                                                <?php 
                                                $tipoMedia = $nominado['tipo_media'] ?? 'imagen';
                                                if ($tipoMedia === 'video'): 
                                                ?>
                                                    <div class="video-container">
                                                        <iframe 
                                                            src="<?= obtenerURLEmbed($nominado['imagen_url']) ?>"
                                                            frameborder="0"
                                                            allowfullscreen
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                                                        </iframe>
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?= e($nominado['imagen_url']) ?>" 
                                                         alt="<?= e($nominado['nombre']) ?>"
                                                         loading="lazy">
                                                <?php endif; ?>
                                                
                                                <?php if ($categoria['voto_actual'] == $nominado['id']): ?>
                                                    <div class="nominado-badge">
                                                        <span class="badge-icon">✓</span>
                                                        <span class="badge-text">Tu Voto</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="nominado-info">
                                                <h4><?= e($nominado['nombre']) ?></h4>
                                                <?php if (!empty($nominado['descripcion'])): ?>
                                                    <p><?= e($nominado['descripcion']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <button class="btn-votar" 
                                                    data-categoria="<?= $categoria['id'] ?>"
                                                    data-nominado="<?= $nominado['id'] ?>">
                                                <?= $categoria['voto_actual'] == $nominado['id'] ? '✓ Votado' : 'Votar' ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php endforeach; ?>
                </div>

                <!-- Resumen Final -->
                <?php if ($progreso === 100): ?>
                    <div class="votacion-completa">
                        <div class="completado-icon">🎉</div>
                        <h3>¡Felicitaciones!</h3>
                        <p>Has completado todas las votaciones. ¡Gracias por participar!</p>
                        <a href="index.php" class="btn btn-success">Volver al Inicio</a>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Checho Awards. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="auth.js"></script>
    <script src="js/utils.js"></script> <!-- ⚠️ DEBE IR PRIMERO -->
    <script src="votacion.js"></script>
    <script src="nominar_clip.js"></script>
    <script src="respuestas_texto.js"></script>
</body>
</html>