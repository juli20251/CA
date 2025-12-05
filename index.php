<?php
require_once 'config.php';

// Verificar si ya está logueado y redirigir según rol
if (estaLogueado() && esAdmin()) {
    redirigir('admin_dashboard.php');
}

// Obtener configuración
$valorTernas = obtenerConfig('mostrar_ternas', $pdo);
$valorVotaciones = obtenerConfig('votaciones_abiertas', $pdo);
$fechaRevelacion = obtenerConfig('fecha_revelacion', $pdo);

// Conversión robusta a booleano
$mostrarTernas = ($valorTernas === 'true' || $valorTernas === '1' || $valorTernas === 1 || $valorTernas === true);
$votacionesAbiertas = ($valorVotaciones === 'true' || $valorVotaciones === '1' || $valorVotaciones === 1 || $valorVotaciones === true);

// Verificar si hay fecha de revelación programada
$hayCountdown = !empty($fechaRevelacion) && !$mostrarTernas;
$fechaRevelacionTimestamp = $hayCountdown ? strtotime($fechaRevelacion) : null;

// Obtener categorías visibles si mostrar_ternas está activado
$categorias = [];
if ($mostrarTernas) {
    try {
        $stmt = $pdo->query("SELECT * FROM categorias WHERE visible = 1 ORDER BY orden");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categorias as $key => &$categoria) {
            $stmt = $pdo->prepare("SELECT * FROM nominados WHERE categoria_id = ? ORDER BY nombre");
            $stmt->execute([$categoria['id']]);
            $categoria['nominados'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($categoria);
    } catch (Exception $e) {
        error_log("Error al cargar categorías: " . $e->getMessage());
        $categorias = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checho Awards 2025 - Inicio</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        /* Estilos para los botones de votación */
        .cta-votacion {
            background: linear-gradient(135deg, rgba(255, 69, 0, 0.1), rgba(255, 140, 0, 0.05));
            border: 2px solid var(--primary, #ff4500);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            margin: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .cta-votacion::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 69, 0, 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            100% { left: 100%; }
        }

        .cta-votacion h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ff4500 0%, #ff8c00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-votacion p {
            color: var(--text-secondary, #94a3b8);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .btn-votar-grande {
            display: inline-block;
            background: linear-gradient(135deg, #ff4500 0%, #ff8c00 100%);
            color: white;
            padding: 1.25rem 3rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(255, 69, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .btn-votar-grande:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(255, 69, 0, 0.5);
        }

        .btn-votar-grande:active {
            transform: translateY(-2px);
        }

        .estado-votacion {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .estado-votacion.abierto {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            color: #10b981;
        }

        .estado-votacion.cerrado {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
        }

        .indicador {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .estado-votacion.abierto .indicador {
            background: #10b981;
        }

        .estado-votacion.cerrado .indicador {
            background: #ef4444;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Botón en el header */
        .btn-votar-header {
            background: var(--primary, #ff4500);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-votar-header:hover {
            background: #cc3700;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <!-- Auth Modal -->
    <div id="authModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal()">×</button>

            <!-- Login Form -->
            <div id="loginForm">
                <h2 class="modal-title">Iniciar Sesión</h2>
                <form onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="loginEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" id="loginPassword" required>
                    </div>
                    <button type="submit" class="submit-btn">Ingresar</button>
                    <div class="error-message" id="loginError"></div>
                    <div class="success-message" id="loginSuccess"></div>
                </form>
                <div class="switch-auth">
                    ¿No tienes cuenta? <a onclick="showRegister()">Regístrate</a>
                </div>
            </div>

            <div id="registerForm" style="display: none;">
                <h2 class="modal-title">Crear Cuenta</h2>
                <form onsubmit="handleRegister(event)">
                    <div class="form-group">
                        <label>Nombre de usuario</label>
                        <input type="text" id="registerUsername" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="registerEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" id="registerPassword" required minlength="6">
                    </div>
                    <button type="submit" class="submit-btn">Crear Cuenta</button>
                    <div class="error-message" id="registerError"></div>
                    <div class="success-message" id="registerSuccess"></div>
                </form>
                <div class="switch-auth">
                    ¿Ya tienes cuenta? <a onclick="showLogin()">Inicia sesión</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <nav>
            <a href="index.php" class="logo">CHECHO AWARDS</a>
            <button class="menu-toggle" onclick="toggleMenu()">☰</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="#ternas">Ternas</a></li>
                
                <!-- Botón de Votar en el Header -->
                <?php if ($mostrarTernas && $votacionesAbiertas): ?>
                    <li>
                        <?php if (estaLogueado()): ?>
                            <a href="votacion.php" class="btn-votar-header">🗳️ Votar Ahora</a>
                        <?php else: ?>
                            <a href="login.html" class="btn-votar-header">🗳️ Iniciar Sesión para Votar</a>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
                
                <li id="authButton">
                    <a href="login.html" onclick="showModal(); return false">Login</a>
                </li>
                <li id="userSection" style="display: none;">
                    <div class="user-info">
                        <span class="user-name" id="userName"></span>
                        <button class="logout-btn" onclick="handleLogout()">Salir</button>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div>
                <h1 class="hero-logo">CHECHO AWARDS</h1>
                <p class="hero-subtitle">La premiación oficial de la comunidad de chechobarregood</p>
                <p class="hero-subtitle">🏆 Edición 2025 🏆</p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">200</div>
                        <div class="stat-label">STREAMS EN EL AÑO</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">25</div>
                        <div class="stat-label">Categorías</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">+40</div>
                        <div class="stat-label">Participantes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">2025</div>
                        <div class="stat-label">Primera Edición</div>
                    </div>
                </div>

                <!-- Call to Action de Votación -->
                <?php if ($mostrarTernas && $votacionesAbiertas): ?>
                    <div class="cta-votacion">
                        <div class="estado-votacion abierto">
                            <span class="indicador"></span>
                            <span>Votaciones Abiertas</span>
                        </div>
                        
                        <h3>🗳️ ¡Es tu momento de votar!</h3>
                        <p>Tu voz cuenta. Elige a tus favoritos en cada categoría y sé parte de la historia de los Checho Awards.</p>
                        
                        <?php if (estaLogueado()): ?>
                            <a href="votacion.php" class="btn-votar-grande">
                                Ir a Votar →
                            </a>
                        <?php else: ?>
                            <a href="login.html" class="btn-votar-grande">
                                Iniciar Sesión para Votar →
                            </a>
                            <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
                                ¿No tienes cuenta? <a href="login.html" style="color: var(--primary); text-decoration: underline;">Regístrate gratis</a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php elseif (!$votacionesAbiertas): ?>
                    <div class="cta-votacion">
                        <div class="estado-votacion cerrado">
                            <span class="indicador"></span>
                            <span>Votaciones Cerradas</span>
                        </div>
                        
                        <h3>Las votaciones han finalizado</h3>
                        <p>Gracias a todos los que participaron. Los ganadores se anunciarán pronto.</p>
                    </div>
                <?php endif; ?>

                <!-- Info Section -->
                <div class="about-content">
                    <h2>¿Qué son los Checho Awards?</h2>
                    <p>Los <strong>Checho Awards</strong> son la celebración anual más importante de la comunidad de
                        chechobarregood, donde reconocemos a los mejores streamers, momentos épicos, memes inolvidables
                        y miembros destacados.</p>
                    <p>Inspirados en los legendarios Coscu Army Awards, esta premiación busca unir a toda la comunidad
                        en una celebración única donde <strong>TÚ decides quiénes son los ganadores</strong>.</p>
                    <p>Desde el mejor clip del año hasta el moderador más dedicado, cada categoría representa lo mejor
                        de nuestra comunidad. ¡Vota por tus favoritos y sé parte de la historia!</p>
                </div>
            </div>
        </section>

        <!-- Salón de la Fama -->
        <section class="hall-of-fame">
            <div class="hall-container">
                <h2 class="hall-title">
                    <span class="hall-icon">👑</span>
                    SALÓN DE LA FAMA
                    <span class="hall-icon">👑</span>
                </h2>
                <p class="hall-subtitle">Las leyendas que hicieron posible esta comunidad</p>

                <div class="legends-grid">
                    <!-- Luna -->
                    <div class="legend-card luna-card">
                        <div class="legend-image">
                            <img src="assets/img/luna.png" alt="Luna">
                        </div>
                        <div class="legend-crown">👑</div>
                        <div class="legend-badge">MVP</div>
                        <h3 class="legend-name">Luna</h3>
                        <p class="legend-title">La Reina del Canal</p>
                        <div class="legend-description">
                            <p>La número uno, mi novia y la mejor de todas. Luna no solo es el corazón del canal, sino también su alma creativa.</p>
                            <p>Ha participado en innumerables videos, streams épicos y eventos memorables que marcaron la historia de la comunidad. Su presencia ilumina cada stream y su energía contagiosa hace que todo sea mejor (ademas que es la dueña del canal).</p>
                            <p class="legend-highlight">Sin ella, nada de esto sería igual (te amo luna). 💖</p>
                        </div>
                        <div class="legend-stats">
                            <div class="stat-item">
                                <span class="stat-value">∞</span>
                                <span class="stat-label">Apariciones</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">100%</span>
                                <span class="stat-label">Amor</span>
                            </div>
                        </div>
                    </div>

                    <!-- Nex -->
                    <div class="legend-card nex-card">
                        <div class="legend-image">
                            <img src="assets/img/nex.png" alt="Nex">
                        </div>
                        <div class="legend-crown">⭐</div>
                        <div class="legend-badge">FANÁTICO #1</div>
                        <h3 class="legend-name">Nex</h3>
                        <p class="legend-title">El Espectador Supremo</p>
                        <div class="legend-description">
                            <p>El récord mundial de visualizaciones del canal tiene dueño: Nex. Es la persona que más me ve, el viewer más dedicado y activo que existe.</p>
                            <p>Siempre está presente en el chat, apoyando cada stream sin importar la hora o el día y carreando en el hax. Su lealtad es inquebrantable y su compromiso con el contenido es admirable.</p>
                            <p class="legend-highlight">El capo del chat. 🎮</p>
                        </div>
                        <div class="legend-stats">
                            <div class="stat-item">
                                <span class="stat-value">#1</span>
                                <span class="stat-label">Viewer</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">24/7</span>
                                <span class="stat-label">Presente</span>
                            </div>
                        </div>
                    </div>

                    <!-- Joaco -->
                    <div class="legend-card joaco-card">
                        <div class="legend-image">
                            <img src="assets/img/joaco.png" alt="Joaco">
                        </div>
                        <div class="legend-crown">🔥</div>
                        <div class="legend-badge">FUNDADOR</div>
                        <h3 class="legend-name">Joaco</h3>
                        <p class="legend-title">El Pionero Original</p>
                        <div class="legend-description">
                            <p>El más OG de todos. Joaco estuvo desde el día uno, cuando el canal era apenas una idea y las vistas se contaban con los dedos de una mano.</p>
                            <p>Ha visto cada etapa de crecimiento, cada momento épico y cada cambio del canal (CON UN BANEO DE POR MEDIO). Su antigüedad es legendaria y su conocimiento de la lore es enciclopédico.</p>
                            <p class="legend-highlight">Si hay historia del canal, Joaco la vivió. 🚀</p>
                        </div>
                        <div class="legend-stats">
                            <div class="stat-item">
                                <span class="stat-value">OG</span>
                                <span class="stat-label">Desde</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">∞</span>
                                <span class="stat-label">Lore</span>
                            </div>
                        </div>
                    </div>

                    <!-- Nico -->
                    <div class="legend-card nico-card">
                        <div class="legend-image">
                            <img src="assets/img/nico.png" alt="Nico">
                        </div>
                        <div class="legend-crown">💰</div>
                        <div class="legend-badge">BENEFACTOR</div>
                        <h3 class="legend-name">Nico</h3>
                        <p class="legend-title">El midas del Canal</p>
                        <div class="legend-description">
                            <p>El pilar económico que sostuvo los sueños. Nico es quien más ha bancado el canal en términos monetarios, haciendo posible mejoras y equipos para proyectos ambiciosos.</p>
                            <p>Sus subs, donaciones y apoyo constante han permitido que el contenido siga mejorando. Un capo cuando dijo que vaccari se tenia que ir (se adelanto como 13 fechas)</p>
                            <p class="legend-highlight">El verdadero patron del canal (volve nico). 💎</p>
                        </div>
                        <div class="legend-stats">
                            <div class="stat-item">
                                <span class="stat-value">💸</span>
                                <span class="stat-label">Tier 3</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">MAX</span>
                                <span class="stat-label">Support</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hall-footer">
                    <p class="update-notice">🔄 Este salón se actualizará próximamente con más leyendas</p>
                    <p class="hall-message">Gracias a todos los que hacen esta comunidad especial ❤️</p>
                </div>
            </div>
        </section>

        <!-- Ternas Section -->
        <?php if ($mostrarTernas && count($categorias) > 0): ?>
            <section class="ternas-section" id="ternas">
                <div class="container">
                    <h2 class="section-title">Categorías y Nominados</h2>
                    
                    <div class="categorias-grid">
                        <?php foreach ($categorias as $categoria): ?>
                            <div class="categoria-card">
                                <h3 class="categoria-titulo"><?= e($categoria['nombre']) ?></h3>
                                <p class="categoria-desc"><?= e($categoria['descripcion']) ?></p>
                                
                                <?php if (isset($categoria['nominados']) && count($categoria['nominados']) > 0): ?>
                                    <div class="nominados-grid">
                                        <?php foreach ($categoria['nominados'] as $nominado): ?>
                                            <div class="nominado-card">
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
                                                    <img src="<?= e($nominado['imagen_url']) ?>" alt="<?= e($nominado['nombre']) ?>">
                                                <?php endif; ?>
                                                
                                                <h4><?= e($nominado['nombre']) ?></h4>
                                                <?php if (!empty($nominado['descripcion'])): ?>
                                                    <p><?= e($nominado['descripcion']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                        No hay nominados en esta categoría aún.
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Botón para votar al final de las ternas -->
                    <?php if ($votacionesAbiertas): ?>
                        <div class="cta-votacion" style="margin-top: 3rem;">
                            <h3>¿Ya decidiste tus favoritos?</h3>
                            <p>Es momento de hacer que tu voz cuente. ¡Vota ahora!</p>
                            
                            <?php if (estaLogueado()): ?>
                                <a href="votacion.php" class="btn-votar-grande">
                                    Ir a Votar →
                                </a>
                            <?php else: ?>
                                <a href="login.html" class="btn-votar-grande">
                                    Iniciar Sesión para Votar →
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        
        <?php elseif ($hayCountdown): ?>
            <!-- Countdown para revelación de ternas -->
            <section class="ternas-section" id="ternas">
                <div class="container">
                    <div class="countdown-container">
                        <h2 class="countdown-title">🎬 Revelación de Ternas</h2>
                        <p class="countdown-subtitle">Las nominaciones se revelarán en:</p>
                        
                        <div class="countdown-timer" id="countdownTimer">
                            <div class="countdown-item">
                                <div class="countdown-number" id="days">00</div>
                                <div class="countdown-label">Días</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="hours">00</div>
                                <div class="countdown-label">Horas</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="minutes">00</div>
                                <div class="countdown-label">Minutos</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-number" id="seconds">00</div>
                                <div class="countdown-label">Segundos</div>
                            </div>
                        </div>
                        
                        <div class="countdown-message">
                            <p>📅 Fecha de revelación: <strong><?= date('d/m/Y - H:i', $fechaRevelacionTimestamp) ?></strong></p>
                        </div>
                    </div>
                </div>
            </section>
            
            <script>
                // Countdown timer
                const countdownDate = new Date("<?= date('Y-m-d H:i:s', $fechaRevelacionTimestamp) ?>").getTime();
                
                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = countdownDate - now;
                    
                    if (distance < 0) {
                        location.reload();
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    document.getElementById('days').textContent = String(days).padStart(2, '0');
                    document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                    document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                    document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
                    
                    const items = document.querySelectorAll('.countdown-item');
                    if (distance < 3600000) {
                        items.forEach(item => item.classList.add('urgent'));
                    }
                }
                
                updateCountdown();
                setInterval(updateCountdown, 1000);
            </script>
            
        <?php else: ?>
            <section class="ternas-section" id="ternas">
                <div class="container">
                    <div class="mensaje-espera">
                        <h2>🎬 Las ternas se revelarán pronto</h2>
                        <p>Las nominaciones se anunciarán en las próximas semanas. Mantente atento a nuestras redes sociales.</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer" style="background: rgba(0,0,0,0.3); padding: 2rem 0; text-align: center; margin-top: 4rem;">
        <div class="container">
            <p>&copy; 2025 Checho Awards. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="auth.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }

        // Initialize
        initAuth();
    </script>
</body>
</html>