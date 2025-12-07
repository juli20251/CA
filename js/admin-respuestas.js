// ================================================
// GESTIÓN DE RESPUESTAS DE TEXTO - ADMIN
// ================================================

// Cambiar entre tabs
function cambiarTabRespuestas(tab) {
    // Cambiar botones activos
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Cambiar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
        content.style.display = 'none';
    });
    
    if (tab === 'categoria') {
        document.getElementById('tabCategoria').style.display = 'block';
        document.getElementById('tabCategoria').classList.add('active');
        cargarCategoriasTextoLibre();
    } else {
        document.getElementById('tabUsuario').style.display = 'block';
        document.getElementById('tabUsuario').classList.add('active');
        cargarUsuariosConRespuestas();
    }
}

// Cargar categorías de texto libre para el filtro
async function cargarCategoriasTextoLibre() {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=listar_categorias'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('filtroCategoriaRespuestas');
            const categoriasTexto = data.categorias.filter(c => c.tipo_votacion === 'texto_libre');
            
            select.innerHTML = '<option value="">Todas las categorías de texto libre</option>';
            
            categoriasTexto.forEach(cat => {
                select.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
            });
            
            cargarRespuestasPorCategoria();
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

// Cargar respuestas por categoría
async function cargarRespuestasPorCategoria() {
    const categoriaId = document.getElementById('filtroCategoriaRespuestas').value;
    const container = document.getElementById('listaRespuestasCategoria');
    
    container.innerHTML = '<div class="loading">Cargando respuestas...</div>';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=listar_respuestas_texto&categoria_id=${categoriaId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.respuestas.length === 0) {
                container.innerHTML = `
                    <div class="no-respuestas">
                        <div class="no-respuestas-icon">📝</div>
                        <h3>No hay respuestas aún</h3>
                        <p>Los usuarios aún no han enviado respuestas en estas categorías.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            data.respuestas.forEach(respuesta => {
                const card = crearCardRespuesta(respuesta);
                container.appendChild(card);
            });
        }
    } catch (error) {
        console.error('Error al cargar respuestas:', error);
        container.innerHTML = '<div class="error">Error al cargar respuestas</div>';
    }
}

// Cargar usuarios con respuestas
async function cargarUsuariosConRespuestas() {
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=listar_usuarios_con_respuestas'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('filtroUsuarioRespuestas');
            
            select.innerHTML = '<option value="">Selecciona un usuario</option>';
            
            data.usuarios.forEach(usuario => {
                select.innerHTML += `
                    <option value="${usuario.id}">
                        ${usuario.nombre} (${usuario.total_respuestas} respuestas)
                    </option>
                `;
            });
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
}

// Cargar respuestas por usuario
async function cargarRespuestasPorUsuario() {
    const usuarioId = document.getElementById('filtroUsuarioRespuestas').value;
    const container = document.getElementById('listaRespuestasUsuario');
    
    if (!usuarioId) {
        container.innerHTML = `
            <div class="no-respuestas">
                <div class="no-respuestas-icon">👤</div>
                <p>Selecciona un usuario para ver sus respuestas</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '<div class="loading">Cargando respuestas...</div>';
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=obtener_respuestas_usuario&usuario_id=${usuarioId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.respuestas.length === 0) {
                container.innerHTML = `
                    <div class="no-respuestas">
                        <div class="no-respuestas-icon">📝</div>
                        <p>Este usuario no ha enviado respuestas</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            data.respuestas.forEach(respuesta => {
                const card = crearCardRespuesta(respuesta);
                container.appendChild(card);
            });
        }
    } catch (error) {
        console.error('Error al cargar respuestas:', error);
        container.innerHTML = '<div class="error">Error al cargar respuestas</div>';
    }
}

// Crear card de respuesta
function crearCardRespuesta(respuesta) {
    const card = document.createElement('div');
    card.className = 'respuesta-card-admin';
    card.id = `respuesta-${respuesta.id}`;
    
    const fecha = new Date(respuesta.fecha_respuesta).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    card.innerHTML = `
        <div class="respuesta-header-admin">
            <div class="respuesta-meta">
                <h4>${respuesta.categoria_nombre}</h4>
                <div class="meta-info">
                    <div class="meta-item">
                        <span>👤</span>
                        <strong>${respuesta.usuario_nombre}</strong>
                    </div>
                    <div class="meta-item">
                        <span>📧</span>
                        ${respuesta.usuario_email}
                    </div>
                    <div class="meta-item">
                        <span>📅</span>
                        ${fecha}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="respuesta-contenido-admin">
            <p class="respuesta-texto-admin">${escapeHtml(respuesta.respuesta)}</p>
        </div>
        
        <div class="respuesta-actions">
            <button class="btn-eliminar-respuesta-admin" onclick="eliminarRespuestaAdmin(${respuesta.id})">
                🗑️ Eliminar Respuesta
            </button>
        </div>
    `;
    
    return card;
}

// Eliminar respuesta (admin)
async function eliminarRespuestaAdmin(respuestaId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta respuesta? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch('admin_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=eliminar_respuesta_admin&respuesta_id=${respuestaId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Eliminar visualmente con animación
            const card = document.getElementById(`respuesta-${respuestaId}`);
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                card.remove();
                
                // Verificar si quedan respuestas
                const container = card.parentElement;
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="no-respuestas">
                            <div class="no-respuestas-icon">📝</div>
                            <p>No hay más respuestas</p>
                        </div>
                    `;
                }
            }, 300);
            
            mostrarMensajeAdmin(data.mensaje, 'success');
        } else {
            mostrarMensajeAdmin(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error al eliminar respuesta:', error);
        mostrarMensajeAdmin('Error al eliminar la respuesta', 'error');
    }
}

// Mostrar mensaje admin
function mostrarMensajeAdmin(mensaje, tipo) {
    // Crear o usar elemento de mensaje existente
    let mensajeDiv = document.getElementById('mensajeAdminGlobal');
    
    if (!mensajeDiv) {
        mensajeDiv = document.createElement('div');
        mensajeDiv.id = 'mensajeAdminGlobal';
        mensajeDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;
        document.body.appendChild(mensajeDiv);
    }
    
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = tipo === 'success' ? 'mensaje-exito' : 'mensaje-error';
    mensajeDiv.style.display = 'block';
    
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 3000);
}

// Función auxiliar para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

console.log('✅ admin-respuestas.js cargado');