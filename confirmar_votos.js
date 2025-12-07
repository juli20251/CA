// ================================================
// SISTEMA DE CONFIRMACIÓN DE VOTOS
// ================================================

let votosConfirmados = false;

// Verificar estado al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    verificarEstadoConfirmacion();
});

// Verificar si el usuario ya confirmó sus votos
async function verificarEstadoConfirmacion() {
    try {
        const response = await fetch('confirmar_votos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=verificar_estado'
        });
        
        const data = await response.json();
        
        if (data.success) {
            votosConfirmados = data.confirmado;
            
            if (data.confirmado) {
                mostrarPanelConfirmado(data);
                bloquearInteracciones();
            } else {
                mostrarPanelPendiente(data);
            }
        }
    } catch (error) {
        console.error('Error al verificar estado:', error);
    }
}

// Mostrar panel cuando los votos están pendientes
function mostrarPanelPendiente(data) {
    const panel = document.getElementById('panelConfirmacion');
    if (!panel) return;
    
    const totalParticipacion = (data.total_votos || 0) + (data.total_respuestas || 0) + (data.total_clips || 0);
    
    if (totalParticipacion === 0) {
        panel.innerHTML = `
            <div class="confirmacion-pendiente">
                <div class="confirmacion-icon">ℹ️</div>
                <div class="confirmacion-content">
                    <h3>Vota en las categorías</h3>
                    <p>Cuando termines, confirma tus votos para que queden registrados definitivamente.</p>
                </div>
            </div>
        `;
        return;
    }
    
    panel.innerHTML = `
        <div class="confirmacion-pendiente">
            <div class="confirmacion-icon">⚠️</div>
            <div class="confirmacion-content">
                <h3>¿Listo para confirmar tus votos?</h3>
                <p>Has participado en ${totalParticipacion} categoría(s). Una vez confirmados, <strong>no podrás hacer cambios</strong>.</p>
                <div class="confirmacion-stats">
                    ${data.total_votos > 0 ? `<span>✅ ${data.total_votos} voto(s)</span>` : ''}
                    ${data.total_respuestas > 0 ? `<span>✍️ ${data.total_respuestas} respuesta(s)</span>` : ''}
                    ${data.total_clips > 0 ? `<span>🎬 ${data.total_clips} clip(s)</span>` : ''}
                </div>
                <button class="btn-confirmar-votos" onclick="confirmarVotos()">
                    🔒 Confirmar Mis Votos Definitivamente
                </button>
                <small class="advertencia">⚠️ Esta acción es irreversible</small>
            </div>
        </div>
    `;
}

// Mostrar panel cuando los votos ya están confirmados
function mostrarPanelConfirmado(data) {
    const panel = document.getElementById('panelConfirmacion');
    if (!panel) return;
    
    const fecha = new Date(data.fecha_confirmacion).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const totalParticipacion = (data.total_votos || 0) + (data.total_respuestas || 0) + (data.total_clips || 0);
    
    panel.innerHTML = `
        <div class="confirmacion-completada">
            <div class="confirmacion-icon">✅</div>
            <div class="confirmacion-content">
                <h3>¡Votos Confirmados!</h3>
                <p>Tus votos han sido confirmados el <strong>${fecha}</strong></p>
                <div class="confirmacion-stats">
                    ${data.total_votos > 0 ? `<span>✅ ${data.total_votos} voto(s)</span>` : ''}
                    ${data.total_respuestas > 0 ? `<span>✍️ ${data.total_respuestas} respuesta(s)</span>` : ''}
                    ${data.total_clips > 0 ? `<span>🎬 ${data.total_clips} clip(s)</span>` : ''}
                </div>
                <p class="confirmacion-final">🔒 Tus ${totalParticipacion} participaciones están registradas y bloqueadas.</p>
            </div>
        </div>
    `;
}

// Confirmar votos
async function confirmarVotos() {
    if (!confirm('⚠️ ¿Estás seguro?\n\nUna vez confirmados, NO podrás cambiar tus votos.\n\n¿Deseas confirmar definitivamente?')) {
        return;
    }
    
    const btn = document.querySelector('.btn-confirmar-votos');
    if (btn) {
        btn.disabled = true;
        btn.textContent = '⏳ Confirmando...';
    }
    
    try {
        const response = await fetch('confirmar_votos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'accion=confirmar_votos'
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizar estado global
            votosConfirmados = true;
            
            // Mostrar mensaje de éxito
            mostrarMensajeGlobal(data.mensaje, 'success');
            
            // Actualizar panel
            setTimeout(() => {
                verificarEstadoConfirmacion();
            }, 1500);
            
            // Mostrar animación de celebración
            celebrarConfirmacion();
        } else {
            mostrarMensajeGlobal(data.mensaje, 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = '🔒 Confirmar Mis Votos Definitivamente';
            }
        }
    } catch (error) {
        console.error('Error al confirmar votos:', error);
        mostrarMensajeGlobal('Error al confirmar votos', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = '🔒 Confirmar Mis Votos Definitivamente';
        }
    }
}

// Bloquear interacciones una vez confirmado
function bloquearInteracciones() {
    // Bloquear cards de nominados
    document.querySelectorAll('.nominado-card').forEach(card => {
        card.style.opacity = '0.7';
        card.style.pointerEvents = 'none';
    });
    
    // Bloquear botones de votar
    document.querySelectorAll('.btn-votar').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
    });
    
    // Bloquear formularios de texto
    document.querySelectorAll('.form-textarea-large, .form-input').forEach(input => {
        input.disabled = true;
        input.style.opacity = '0.7';
    });
    
    // Bloquear botones de envío
    document.querySelectorAll('.btn-enviar-respuesta, .btn-nominar').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.textContent = '🔒 Votos Confirmados';
    });
    
    // Mostrar overlay de bloqueo
    document.querySelectorAll('.categoria-votacion, .categoria-texto-libre, .categoria-nominacion-usuario').forEach(cat => {
        const overlay = document.createElement('div');
        overlay.className = 'overlay-bloqueado';
        overlay.innerHTML = '<span>🔒 Votos Confirmados</span>';
        cat.style.position = 'relative';
        cat.appendChild(overlay);
    });
}

// Animación de celebración
function celebrarConfirmacion() {
    // Crear confetti
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            crearConfetti();
        }, i * 30);
    }
}

function crearConfetti() {
    const confetti = document.createElement('div');
    confetti.className = 'confetti';
    confetti.style.cssText = `
        position: fixed;
        width: 10px;
        height: 10px;
        background: ${['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b'][Math.floor(Math.random() * 4)]};
        left: ${Math.random() * 100}vw;
        top: -10px;
        animation: confettiFall ${2 + Math.random() * 2}s linear;
        z-index: 9999;
    `;
    document.body.appendChild(confetti);
    
    setTimeout(() => confetti.remove(), 4000);
}

// Agregar animación CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes confettiFall {
        to {
            transform: translateY(100vh) rotate(360deg);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Mostrar mensaje global
function mostrarMensajeGlobal(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensajeGlobal');
    if (!mensajeDiv) return;
    
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = tipo === 'success' ? 'mensaje-exito' : 'mensaje-error';
    mensajeDiv.style.display = 'block';
    
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 5000);
}

console.log('✅ Sistema de confirmación de votos cargado');