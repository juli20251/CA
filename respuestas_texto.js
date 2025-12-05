// Cargar formulario de respuesta de texto
async function cargarFormularioTexto(categoriaId) {
    const container = document.getElementById(`formularioTexto-${categoriaId}`);
    
    try {
        // Verificar si ya tiene una respuesta
        const response = await fetch('respuestas_texto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=obtener_mi_respuesta&categoria_id=${categoriaId}`
        });
        
        const data = await response.json();
        
        if (data.respuesta) {
            // Ya tiene una respuesta
            mostrarRespuestaExistente(categoriaId, data.respuesta);
        } else {
            // Mostrar formulario
            mostrarFormularioTexto(categoriaId);
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = '<p class="error">Error al cargar el formulario</p>';
    }
}

// Mostrar formulario para escribir
function mostrarFormularioTexto(categoriaId) {
    const container = document.getElementById(`formularioTexto-${categoriaId}`);
    
    container.innerHTML = `
        <div class="respuesta-texto-card">
            <div class="respuesta-header">
                <h4>✍️ Tu Respuesta</h4>
                <p>Escribe tu respuesta para esta categoría</p>
            </div>
            
            <form id="formRespuestaTexto-${categoriaId}" onsubmit="submitRespuestaTexto(event, ${categoriaId})">
                <div class="form-group">
                    <textarea 
                        id="respuestaTexto-${categoriaId}" 
                        class="form-textarea-large"
                        rows="4"
                        placeholder="Escribe aquí tu respuesta..."
                        required
                        maxlength="500"></textarea>
                    <div class="char-counter">
                        <span id="charCount-${categoriaId}">0</span> / 500 caracteres
                    </div>
                </div>
                
                <div id="mensajeTexto-${categoriaId}" class="mensaje-formulario"></div>
                
                <button type="submit" class="btn-enviar-respuesta">
                    💾 Guardar Respuesta
                </button>
            </form>
        </div>
    `;
    
    // Contador de caracteres
    const textarea = document.getElementById(`respuestaTexto-${categoriaId}`);
    const counter = document.getElementById(`charCount-${categoriaId}`);
    
    textarea.addEventListener('input', () => {
        const length = textarea.value.length;
        counter.textContent = length;
        counter.style.color = length > 450 ? '#ef4444' : '#94a3b8';
    });
}

// Mostrar respuesta existente
function mostrarRespuestaExistente(categoriaId, respuesta) {
    const container = document.getElementById(`formularioTexto-${categoriaId}`);
    
    container.innerHTML = `
        <div class="respuesta-texto-card respuesta-guardada">
            <div class="respuesta-header">
                <h4>✅ Tu Respuesta Guardada</h4>
                <span class="badge badge-success">Completado</span>
            </div>
            
            <div class="respuesta-contenido">
                <div class="respuesta-texto-display">
                    <p>${escapeHtml(respuesta.respuesta)}</p>
                </div>
                <small class="respuesta-fecha">
                    Guardado el ${formatearFecha(respuesta.fecha_respuesta)}
                </small>
            </div>
            
            <div class="respuesta-acciones">
                <button class="btn-editar-respuesta" onclick="editarRespuestaTexto(${categoriaId})">
                    ✏️ Editar Respuesta
                </button>
                <button class="btn-eliminar-respuesta" onclick="eliminarRespuestaTexto(${categoriaId})">
                    🗑️ Eliminar
                </button>
            </div>
        </div>
    `;
}

// Submit respuesta
async function submitRespuestaTexto(event, categoriaId) {
    event.preventDefault();
    
    const respuesta = document.getElementById(`respuestaTexto-${categoriaId}`).value;
    const mensajeDiv = document.getElementById(`mensajeTexto-${categoriaId}`);
    const submitBtn = event.target.querySelector('button[type="submit"]');
    
    // Deshabilitar botón
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Guardando...';
    
    try {
        const response = await fetch('respuestas_texto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=guardar_respuesta&categoria_id=${categoriaId}&respuesta=${encodeURIComponent(respuesta)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarMensaje(mensajeDiv, data.mensaje, 'success');
            setTimeout(() => {
                cargarFormularioTexto(categoriaId);
                actualizarProgresoVotacion();
            }, 1500);
        } else {
            mostrarMensaje(mensajeDiv, data.mensaje, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = '💾 Guardar Respuesta';
        }
    } catch (error) {
        mostrarMensaje(mensajeDiv, 'Error al guardar la respuesta', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = '💾 Guardar Respuesta';
    }
}

// Editar respuesta
function editarRespuestaTexto(categoriaId) {
    mostrarFormularioTexto(categoriaId);
}

// Eliminar respuesta
async function eliminarRespuestaTexto(categoriaId) {
    if (!confirm('¿Estás seguro de que quieres eliminar tu respuesta?')) {
        return;
    }
    
    try {
        const response = await fetch('respuestas_texto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=eliminar_respuesta&categoria_id=${categoriaId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            cargarFormularioTexto(categoriaId);
            actualizarProgresoVotacion();
        } else {
            alert(data.mensaje);
        }
    } catch (error) {
        alert('Error al eliminar la respuesta');
    }
}

// Las funciones utilitarias (mostrarMensaje, formatearFecha, escapeHtml) 
// están en utils.js

// Log de carga
console.log('✍️ respuestas_texto.js cargado correctamente');