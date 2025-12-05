// votacion.js - Sistema de votación interactivo

// Verificar autenticación al cargar
document.addEventListener('DOMContentLoaded', function() {
    // Verificar que auth.js esté cargado
    if (typeof isLoggedIn !== 'function') {
        console.error('auth.js no está cargado');
        return;
    }

    // Verificar que el usuario esté logueado
    if (!isLoggedIn()) {
        window.location.href = 'login.html';
        return;
    }

    console.log('✅ Sistema de votación cargado correctamente');
});

// Función para seleccionar un nominado (visual)
function seleccionarNominado(categoriaId, nominadoId, elemento) {
    // Prevenir que el clic en el botón active el card
    if (event.target.classList.contains('btn-votar')) {
        return;
    }

    // Remover selección visual de otros nominados en la misma categoría
    const categoria = document.getElementById(`categoria-${categoriaId}`);
    const cards = categoria.querySelectorAll('.nominado-card');
    
    cards.forEach(card => {
        card.classList.remove('seleccionado');
        const badge = card.querySelector('.nominado-badge');
        if (badge) badge.remove();
        
        const btn = card.querySelector('.btn-votar');
        if (btn) {
            btn.textContent = 'Votar';
            btn.style.background = 'rgba(255, 69, 0, 0.1)';
            btn.style.color = 'var(--primary)';
            btn.style.borderColor = 'var(--primary)';
        }
    });

    // Marcar este como seleccionado
    elemento.classList.add('seleccionado');
    
    // Agregar badge si no existe
    if (!elemento.querySelector('.nominado-badge')) {
        const mediaDiv = elemento.querySelector('.nominado-media');
        const badge = document.createElement('div');
        badge.className = 'nominado-badge';
        badge.innerHTML = '<span class="badge-icon">✓</span><span class="badge-text">Tu Voto</span>';
        mediaDiv.appendChild(badge);
    }

    // Cambiar texto del botón
    const btn = elemento.querySelector('.btn-votar');
    if (btn) {
        btn.textContent = '✓ Votado';
        btn.style.background = 'var(--success)';
        btn.style.color = 'white';
        btn.style.borderColor = 'var(--success)';
    }

    // Llamar a la función de voto
    votar(categoriaId, nominadoId);
}

// Función para enviar el voto al servidor
async function votar(categoriaId, nominadoId) {
    try {
        const formData = new FormData();
        formData.append('categoria_id', categoriaId);
        formData.append('nominado_id', nominadoId);

        const response = await fetch('votar.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            mostrarMensaje(data.mensaje, 'success');
            actualizarProgreso();
            actualizarEstadoCategoria(categoriaId, true);
            
            // Scroll suave a la siguiente categoría
            scrollSiguienteCategoria(categoriaId);
        } else {
            mostrarMensaje(data.mensaje, 'error');
        }

    } catch (error) {
        console.error('Error al votar:', error);
        mostrarMensaje('Error de conexión. Por favor intenta nuevamente.', 'error');
    }
}

// Mostrar mensaje de feedback
function mostrarMensaje(mensaje, tipo = 'success') {
    const mensajeDiv = document.getElementById('mensajeGlobal');
    
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = tipo;
    mensajeDiv.style.display = 'block';

    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 3000);

    // Scroll al mensaje
    mensajeDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Actualizar barra de progreso
function actualizarProgreso() {
    const totalCategorias = document.querySelectorAll('.categoria-votacion').length;
    const categoriasVotadas = document.querySelectorAll('.categoria-status.votado').length;
    
    const progreso = Math.round((categoriasVotadas / totalCategorias) * 100);
    
    // Actualizar barra visual
    const barraProgreso = document.querySelector('.progreso-bar');
    const textoProgreso = document.querySelector('.progreso-text');
    const infoProgreso = document.querySelector('.progreso-info p');
    
    if (barraProgreso) {
        barraProgreso.style.width = `${progreso}%`;
    }
    
    if (textoProgreso) {
        textoProgreso.textContent = `${progreso}%`;
    }
    
    if (infoProgreso) {
        infoProgreso.textContent = `${categoriasVotadas} de ${totalCategorias} categorías completadas`;
    }

    // Si completó todas, mostrar mensaje de felicitación
    if (progreso === 100) {
        mostrarVotacionCompleta();
    }
}

// Actualizar estado de la categoría
function actualizarEstadoCategoria(categoriaId, votado) {
    const categoria = document.getElementById(`categoria-${categoriaId}`);
    const status = categoria.querySelector('.categoria-status');
    
    if (votado) {
        status.classList.remove('pendiente');
        status.classList.add('votado');
        status.querySelector('.status-icon').textContent = '✓';
        status.querySelector('.status-text').textContent = 'Votado';
    }
}

// Scroll a la siguiente categoría pendiente
function scrollSiguienteCategoria(categoriaActualId) {
    const todasCategorias = document.querySelectorAll('.categoria-votacion');
    let siguienteCategoria = null;
    let encontradaActual = false;

    todasCategorias.forEach(cat => {
        if (encontradaActual && !siguienteCategoria) {
            const status = cat.querySelector('.categoria-status');
            if (status && status.classList.contains('pendiente')) {
                siguienteCategoria = cat;
            }
        }
        if (cat.id === `categoria-${categoriaActualId}`) {
            encontradaActual = true;
        }
    });

    if (siguienteCategoria) {
        setTimeout(() => {
            siguienteCategoria.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 500);
    }
}

// Mostrar mensaje de votación completa
function mostrarVotacionCompleta() {
    setTimeout(() => {
        const existeDiv = document.querySelector('.votacion-completa');
        if (!existeDiv) {
            const container = document.querySelector('.categorias-container');
            const divCompleto = document.createElement('div');
            divCompleto.className = 'votacion-completa';
            divCompleto.innerHTML = `
                <div class="completado-icon">🎉</div>
                <h3>¡Felicitaciones!</h3>
                <p>Has completado todas las votaciones. ¡Gracias por participar!</p>
                <a href="index.php" class="btn btn-success">Volver al Inicio</a>
            `;
            container.after(divCompleto);
            
            divCompleto.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }, 1000);
}

// Event listeners para los botones de votar
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-votar')) {
        e.stopPropagation();
        
        const categoriaId = parseInt(e.target.dataset.categoria);
        const nominadoId = parseInt(e.target.dataset.nominado);
        const card = e.target.closest('.nominado-card');
        
        seleccionarNominado(categoriaId, nominadoId, card);
    }
});

// Función para cerrar sesión
function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        handleLogout();
    }
}

// Prevenir el envío de formularios (si hubiera)
document.addEventListener('submit', function(e) {
    e.preventDefault();
});

// Log para depuración
console.log('📊 Sistema de votación iniciado');