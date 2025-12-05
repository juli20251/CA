// ============================================
// UTILS.JS - Funciones Utilitarias Compartidas
// ============================================

/**
 * Mostrar mensaje de feedback al usuario
 * @param {HTMLElement} elemento - Elemento donde mostrar el mensaje
 * @param {string} mensaje - Texto del mensaje
 * @param {string} tipo - Tipo: 'success' o 'error'
 */
function mostrarMensaje(elemento, mensaje, tipo) {
    if (!elemento) {
        console.error('Elemento no encontrado para mostrar mensaje:', elemento);
        return;
    }
    elemento.className = `mensaje-formulario mensaje-${tipo}`;
    elemento.textContent = mensaje;
    elemento.style.display = 'block';
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        elemento.style.display = 'none';
    }, 5000);
}

/**
 * Formatear fecha al formato español
 * @param {string} fecha - Fecha en formato ISO
 * @returns {string} - Fecha formateada
 */
function formatearFecha(fecha) {
    if (!fecha) return '';
    
    try {
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Error al formatear fecha:', error);
        return fecha;
    }
}

/**
 * Escapar HTML para prevenir XSS
 * @param {string} text - Texto a escapar
 * @returns {string} - Texto escapado
 */
function escapeHtml(text) {
    if (!text) return '';
    
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Obtener URL embed de videos (YouTube/Twitch)
 * @param {string} url - URL original del video
 * @returns {string} - URL embed
 */
function obtenerURLEmbedJS(url) {
    if (!url) return '';
    
    // YouTube - formato watch
    let match = url.match(/youtube\.com\/watch\?v=([^\&\?\/]+)/);
    if (match) return `https://www.youtube.com/embed/${match[1]}`;
    
    // YouTube - formato corto youtu.be
    match = url.match(/youtu\.be\/([^\&\?\/]+)/);
    if (match) return `https://www.youtube.com/embed/${match[1]}`;
    
    // YouTube - formato embed
    match = url.match(/youtube\.com\/embed\/([^\&\?\/]+)/);
    if (match) return `https://www.youtube.com/embed/${match[1]}`;
    
    // Twitch Clips
    match = url.match(/clips\.twitch\.tv\/([^\&\?\/]+)/);
    if (match) {
        const domain = window.location.hostname;
        return `https://clips.twitch.tv/embed?clip=${match[1]}&parent=${domain}`;
    }
    
    // Twitch VOD
    match = url.match(/twitch\.tv\/videos\/(\d+)/);
    if (match) {
        const domain = window.location.hostname;
        return `https://player.twitch.tv/?video=${match[1]}&parent=${domain}`;
    }
    
    return url;
}

/**
 * Generar preview del clip
 * @param {string} url - URL del clip
 * @param {string} tipo - Tipo: 'youtube' o 'twitch'
 * @returns {string} - HTML del preview
 */
function generarPreviewClip(url, tipo) {
    const urlEmbed = obtenerURLEmbedJS(url);
    return `
        <div class="video-preview">
            <iframe 
                src="${urlEmbed}"
                frameborder="0"
                allowfullscreen
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
            </iframe>
        </div>
    `;
}

/**
 * Actualizar progreso de votación (si existe la función en votacion.js)
 */
function actualizarProgresoVotacion() {
    if (typeof actualizarProgreso === 'function') {
        actualizarProgreso();
    } else {
        console.log('Función actualizarProgreso no disponible');
    }
}

// Log de carga
console.log('✅ utils.js cargado correctamente');