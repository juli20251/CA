// Cargar formulario de nominación
async function cargarFormularioNominacion(categoriaId) {
    const container = document.getElementById(`formularioNominacion-${categoriaId}`);
    
    try {
        // Verificar si ya tiene una nominación
        const response = await fetch('nominar_clip.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=obtener_mi_nominacion&categoria_id=${categoriaId}`
        });
        
        const data = await response.json();
        
        if (data.nominacion) {
            // Ya tiene una nominación
            mostrarNominacionExistente(categoriaId, data.nominacion);
        } else {
            // Mostrar formulario
            mostrarFormularioNominacion(categoriaId);
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = '<p class="error">Error al cargar el formulario</p>';
    }
}

// Mostrar formulario para nominar
function mostrarFormularioNominacion(categoriaId) {
    const container = document.getElementById(`formularioNominacion-${categoriaId}`);
    
    container.innerHTML = `
        <div class="nominacion-card">
            <div class="nominacion-header">
                <h4>📹 Nomina tu Clip del Año</h4>
                <p>Comparte el clip que más te gustó este año</p>
            </div>
            
            <form id="formNominarClip-${categoriaId}" onsubmit="submitNominacion(event, ${categoriaId})">
                <div class="form-group">
                    <label>🏷️ Título del Clip *</label>
                    <input 
                        type="text" 
                        id="titulo-${categoriaId}" 
                        class="form-input"
                        placeholder="Ej: La mejor jugada del año"
                        required
                        maxlength="255">
                </div>
                
                <div class="form-group">
                    <label>🔗 URL del Clip *</label>
                    <input 
                        type="url" 
                        id="urlClip-${categoriaId}" 
                        class="form-input"
                        placeholder="https://www.youtube.com/watch?v=... o https://clips.twitch.tv/..."
                        required>
                    <small class="form-help">Solo clips de YouTube o Twitch</small>
                </div>
                
                <div class="form-group">
                    <label>📝 Descripción (opcional)</label>
                    <textarea 
                        id="descripcion-${categoriaId}" 
                        class="form-input"
                        rows="3"
                        placeholder="¿Por qué elegiste este clip?"
                        maxlength="500"></textarea>
                </div>
                
                <div id="mensaje-${categoriaId}" class="mensaje-formulario"></div>
                
                <button type="submit" class="btn-nominar">
                    🚀 Nominar Clip
                </button>
            </form>
        </div>
    `;
}

// Mostrar nominación existente
function mostrarNominacionExistente(categoriaId, nominacion) {
    const container = document.getElementById(`formularioNominacion-${categoriaId}`);
    
    const estadoBadge = {
        'pendiente': '<span class="badge badge-warning">⏳ Pendiente de revisión</span>',
        'aprobado': '<span class="badge badge-success">✅ Aprobado</span>',
        'rechazado': '<span class="badge badge-danger">❌ Rechazado</span>'
    };
    
    container.innerHTML = `
        <div class="nominacion-card nominacion-existente">
            <div class="nominacion-header">
                <h4>✅ Tu Nominación</h4>
                ${estadoBadge[nominacion.estado]}
            </div>
            
            <div class="nominacion-preview">
                <div class="preview-media">
                    ${generarPreviewClip(nominacion.url_clip, nominacion.tipo_clip)}
                </div>
                
                <div class="nominacion-info">
                    <h5>${escapeHtml(nominacion.titulo)}</h5>
                    ${nominacion.descripcion ? `<p>${escapeHtml(nominacion.descripcion)}</p>` : ''}
                    <small>Nominado el ${formatearFecha(nominacion.fecha_nominacion)}</small>
                </div>
            </div>
            
            ${nominacion.estado === 'pendiente' ? `
                <button class="btn-eliminar" onclick="eliminarNominacion(${categoriaId})">
                    🗑️ Eliminar Nominación
                </button>
            ` : ''}
        </div>
    `;
}

// Submit nominación
async function submitNominacion(event, categoriaId) {
    event.preventDefault();
    
    const titulo = document.getElementById(`titulo-${categoriaId}`).value;
    const urlClip = document.getElementById(`urlClip-${categoriaId}`).value;
    const descripcion = document.getElementById(`descripcion-${categoriaId}`).value;
    const mensajeDiv = document.getElementById(`mensaje-${categoriaId}`);
    
    try {
        const response = await fetch('nominar_clip.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=nominar_clip&categoria_id=${categoriaId}&titulo=${encodeURIComponent(titulo)}&url_clip=${encodeURIComponent(urlClip)}&descripcion=${encodeURIComponent(descripcion)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensaje(mensajeDiv, data.mensaje, 'success');
            setTimeout(() => cargarFormularioNominacion(categoriaId), 2000);
        } else {
            mostrarMensaje(mensajeDiv, data.mensaje, 'error');
        }
    } catch (error) {
        mostrarMensaje(mensajeDiv, 'Error al enviar la nominación', 'error');
    }
}

// Eliminar nominación
async function eliminarNominacion(categoriaId) {
    if (!confirm('¿Estás seguro de que quieres eliminar tu nominación?')) {
        return;
    }
    
    try {
        const response = await fetch('nominar_clip.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=eliminar_nominacion&categoria_id=${categoriaId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            cargarFormularioNominacion(categoriaId);
        } else {
            alert(data.mensaje);
        }
    } catch (error) {
        alert('Error al eliminar la nominación');
    }
}

// Generar preview del clip
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

// Las funciones utilitarias (mostrarMensaje, formatearFecha, escapeHtml, 
// obtenerURLEmbedJS, generarPreviewClip) están en utils.js

// Log de carga
console.log('📹 nominar_clip.js cargado correctamente');