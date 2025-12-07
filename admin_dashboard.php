<?php
require_once 'config.php';

if (!esAdmin()) {
    redirigir('index.php');
}

// Obtener configuración
$votacionesAbiertas = obtenerConfig('votaciones_abiertas', $pdo) === 'true';
$mostrarTernas = obtenerConfig('mostrar_ternas', $pdo) === 'true';

// Obtener estadísticas generales
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'user'");
$totalUsuarios = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(DISTINCT usuario_id) as total FROM votos");
$usuariosQueVotaron = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM votos");
$totalVotos = $stmt->fetch()['total'];

// Obtener todas las categorías
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY orden");
$categorias = $stmt->fetchAll();

// Obtener resultados por categoría
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Checho Awards</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>🏆 Checho Awards</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-link active" onclick="mostrarSeccion('dashboard')">
                    <span>📊</span> Dashboard
                </a>
                <a href="#" class="nav-link" onclick="mostrarSeccion('categorias')">
                    <span>🎬</span> Categorías
                </a>
                <a href="#" class="nav-link" onclick="mostrarSeccion('nominados')">
                    <span>👥</span> Nominados
                </a>
                <a href="#" class="nav-link" onclick="mostrarSeccion('configuracion')">
                    <span>⚙️</span> Configuración
                </a>
                <a href="#" class="nav-link" onclick="mostrarSeccion('resultados')">
                    <span>📈</span> Resultados
                </a>
                <a href="#" class="nav-link" onclick="mostrarSeccion('respuestas')">
                    <span>✍️</span> Respuestas de Texto
                </a>
                <a href="#" class="nav-link" onclick="cerrarSesion()">
                    <span>🚪</span> Salir
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="admin-header">
                <h1>Panel de Administración</h1>
                <div class="user-info">
                    <span><?= e($_SESSION['nombre']) ?></span>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="seccionDashboard" class="admin-section">
                <h2>Estadísticas Generales</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?= $totalUsuarios ?></h3>
                            <p>Usuarios Registrados</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?= $usuariosQueVotaron ?></h3>
                            <p>Usuarios que Votaron</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🗳️</div>
                        <div class="stat-info">
                            <h3><?= $totalVotos ?></h3>
                            <p>Total de Votos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🎬</div>
                        <div class="stat-info">
                            <h3><?= count($categorias) ?></h3>
                            <p>Categorías</p>
                        </div>
                    </div>
                </div>

                <div class="estado-actual">
                    <h3>Estado Actual del Sistema</h3>
                    <div class="estado-items">
                        <div class="estado-item">
                            <span class="label">Votaciones:</span>
                            <span class="badge <?= $votacionesAbiertas ? 'badge-success' : 'badge-danger' ?>">
                                <?= $votacionesAbiertas ? 'Abiertas' : 'Cerradas' ?>
                            </span>
                        </div>
                        <div class="estado-item">
                            <span class="label">Ternas Visibles:</span>
                            <span class="badge <?= $mostrarTernas ? 'badge-success' : 'badge-danger' ?>">
                                <?= $mostrarTernas ? 'Sí' : 'No' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Categorías Section -->
            <section id="seccionCategorias" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2>Gestión de Categorías</h2>
                    <button class="btn btn-primary" onclick="mostrarModalCategoria()">
                        ➕ Nueva Categoría
                    </button>
                </div>
                
                <div id="listaCategorias" class="tabla-container">
                    <!-- Se carga dinámicamente -->
                </div>
            </section>

            <!-- Nominados Section -->
            <section id="seccionNominados" class="admin-section" style="display: none;">
                <div class="section-header">
                    <h2>Gestión de Nominados</h2>
                    <button class="btn btn-primary" onclick="mostrarModalNominado()">
                        ➕ Nuevo Nominado
                    </button>
                </div>
                
                <div class="filtros-nominados">
                    <select id="filtroCategoria" class="form-control" onchange="cargarNominados()">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>
                
                <div id="listaNominados" class="nominados-grid-admin">
                    <!-- Se carga dinámicamente -->
                </div>
            </section>

            <!-- Configuración Section -->
            <section id="seccionConfiguracion" class="admin-section" style="display: none;">
                <h2>Configuración del Sistema</h2>
                
                <div class="config-panel">
                    <h3>Control de Votaciones</h3>
                    <div class="config-controls">
                        <div class="control-item">
                            <label class="switch">
                                <input type="checkbox" id="toggleVotaciones" <?= $votacionesAbiertas ? 'checked' : '' ?> onchange="toggleVotaciones()">
                                <span class="slider"></span>
                            </label>
                            <span class="control-label">Votaciones Abiertas</span>
                        </div>
                        
                        <div class="control-item">
                            <label class="switch">
                                <input type="checkbox" id="toggleTernas" <?= $mostrarTernas ? 'checked' : '' ?> onchange="toggleTernas()">
                                <span class="slider"></span>
                            </label>
                            <span class="control-label">Mostrar Ternas</span>
                        </div>
                    </div>
                </div>
                
                <div class="config-panel" style="margin-top: 1.5rem;">
                    <h3>⏰ Programar Revelación de Ternas</h3>
                    <p style="color: var(--admin-text-secondary); margin-bottom: 1.5rem; font-size: 0.9rem;">
                        Configura una fecha y hora para mostrar la cuenta regresiva.
                    </p>
                    
                    <div class="form-group">
                        <label>Fecha y Hora de Revelación</label>
                        <input 
                            type="datetime-local" 
                            id="fechaRevelacion" 
                            class="form-control" 
                            value="<?= obtenerConfig('fecha_revelacion', $pdo) ? date('Y-m-d\TH:i', strtotime(obtenerConfig('fecha_revelacion', $pdo))) : '' ?>"
                            style="max-width: 400px;">
                        <small>Deja vacío para no mostrar cuenta regresiva</small>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button class="btn btn-primary" onclick="guardarFechaRevelacion()">
                            💾 Guardar Fecha
                        </button>
                        <button class="btn btn-secondary" onclick="limpiarFechaRevelacion()">
                            🗑️ Limpiar Fecha
                        </button>
                    </div>
                </div>
                
                <div id="mensajeConfig" class="mensaje-exito" style="display: none; margin-top: 1.5rem;"></div>
            </section>

            <!-- Resultados Section -->
<section id="seccionResultados" class="admin-section" style="display: none;">
    <div class="section-header">
        <h2>Resultados en Tiempo Real</h2>
        <button class="btn btn-secondary" onclick="actualizarResultados()">
            🔄 Actualizar Resultados
        </button>
    </div>
    
    <div id="resultadosContainer" class="resultados-container">
        <div class="loading">⏳ Cargando resultados...</div>
    </div>
</section>

            <!-- Respuestas de Texto Section -->
            <section id="seccionRespuestas" class="admin-section" style="display: none;">
                <h2>Respuestas de Texto Libre</h2>
                
                <!-- Pestañas -->
                <div class="tabs-container">
                    <button class="tab-btn active" onclick="cambiarTabRespuestas('categoria')">
                        📋 Por Categoría
                    </button>
                    <button class="tab-btn" onclick="cambiarTabRespuestas('usuario')">
                        👤 Por Usuario
                    </button>
                </div>
                
                <!-- Tab: Por Categoría -->
                <div id="tabCategoria" class="tab-content active">
                    <div class="filtros-respuestas">
                        <select id="filtroCategoriaRespuestas" class="form-control" onchange="cargarRespuestasPorCategoria()">
                            <option value="">Todas las categorías de texto libre</option>
                        </select>
                    </div>
                    
                    <div id="listaRespuestasCategoria" class="respuestas-container">
                        <!-- Se carga dinámicamente -->
                    </div>
                </div>
                
                <!-- Tab: Por Usuario -->
                <div id="tabUsuario" class="tab-content" style="display: none;">
                    <div class="filtros-respuestas">
                        <select id="filtroUsuarioRespuestas" class="form-control" onchange="cargarRespuestasPorUsuario()">
                            <option value="">Selecciona un usuario</option>
                        </select>
                    </div>
                    
                    <div id="listaRespuestasUsuario" class="respuestas-container">
                        <!-- Se carga dinámicamente -->
                    </div>
                </div>
            </section>
        </main>
    </div>

<!-- Modal Categoría -->
    <div id="modalCategoria" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalCategoria()">&times;</span>
            <h2 id="tituloModalCategoria">Nueva Categoría</h2>
            <form id="formCategoria" onsubmit="guardarCategoria(event)">
                <input type="hidden" id="categoriaId">
                
                <div class="form-group">
                    <label>Nombre de la Categoría *</label>
                    <input type="text" id="categoriaNombre" required class="form-control" placeholder="Ej: Mejor Streamer">
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea id="categoriaDescripcion" class="form-control" rows="3" placeholder="Descripción de la categoría"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" id="categoriaOrden" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="categoriaVisible" checked>
                            Visible para usuarios
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tipo de Votación</label>
                    <select id="categoriaTipoVotacion" class="form-control">
                        <option value="normal">📊 Normal (con nominados cargados por admin)</option>
                        <option value="texto_libre">✍️ Texto Libre (usuarios escriben respuesta)</option>
                        <option value="nominacion_usuarios">🎬 Nominación de Clips (usuarios envían videos)</option>
                    </select>
                    <small>Tipo de interacción que tendrán los usuarios en esta categoría</small>
                </div>
                
                <div id="errorCategoria" class="error-message"></div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalCategoria()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nominado -->
    <div id="modalNominado" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalNominado()">&times;</span>
            <h2 id="tituloModalNominado">Nuevo Nominado</h2>
            <form id="formNominado" onsubmit="guardarNominado(event)">
                <input type="hidden" id="nominadoId">
                <input type="hidden" id="nominadoImagen">
                
                <div class="form-group">
                    <label>Categoría *</label>
                    <select id="nominadoCategoria" required class="form-control">
                        <option value="">Selecciona una categoría</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tipo de Medio *</label>
                    <select id="tipoMedia" required class="form-control" onchange="actualizarLabelURL()">
                        <option value="imagen">🖼️ Imagen</option>
                        <option value="video">📹 Video (URL)</option>
                    </select>
                    <small>Imagen: Sube arrastrando. Video: Pega la URL de YouTube o Twitch</small>
                </div>
                
                <div class="form-group">
                    <label>Nombre del Nominado *</label>
                    <input type="text" id="nominadoNombre" required class="form-control" placeholder="Nombre del nominado">
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea id="nominadoDescripcion" class="form-control" rows="3" placeholder="Descripción breve"></textarea>
                </div>
                
                <!-- Área de Upload para Imágenes -->
                <div id="uploadArea" class="form-group" style="display: block;">
                    <label>Imagen *</label>
                    <div id="dropArea" class="drop-area">
                        <input type="file" id="fileInput" accept="image/*" style="display: none;">
                        <img id="previewImage" style="display: none; max-width: 100%; max-height: 300px; border-radius: 8px; margin-bottom: 10px;">
                        <p>📷 Arrastra una imagen aquí o haz clic para seleccionar<br><small style="color: #94a3b8;">Formatos: JPG, PNG, GIF, WEBP (Máx. 5MB)</small></p>
                    </div>
                </div>
                
                <!-- Input de URL para Videos -->
                <div id="urlInputArea" class="form-group" style="display: none;">
                    <label>URL del Video *</label>
                    <input type="url" id="videoUrl" class="form-control" placeholder="https://www.youtube.com/watch?v=... o https://clips.twitch.tv/...">
                    <small>Pega el enlace completo del video de YouTube o Twitch Clip</small>
                </div>
                
                <div id="errorNominado" class="error-message"></div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalNominado()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/scripts.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/admin-animations.js"></script>
    <script src="js/admin-respuestas.js"></script>
</body>
</html>