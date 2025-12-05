// ========================================
// CHECHO AWARDS - JAVASCRIPT PRINCIPAL
// ========================================

console.log('✅ Scripts cargado correctamente');

// Funciones para modales
function mostrarModal(tipo) {
    console.log('Mostrando modal:', tipo);
    const modalId = 'modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1);
    const modal = document.getElementById(modalId);
    
    if (!modal) {
        console.error('Modal no encontrado:', modalId);
        return;
    }
    
    modal.style.display = 'block';
    
    // Limpiar errores previos
    const errorDiv = modal.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
}

function cerrarModal(tipo) {
    const modalId = 'modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1);
    const modal = document.getElementById(modalId);
    
    if (modal) {
        modal.style.display = 'none';
        
        const form = modal.querySelector('form');
        if (form) form.reset();
        
        const errorDiv = modal.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
    }
}

function cambiarModal(cerrar, abrir) {
    cerrarModal(cerrar);
    setTimeout(() => mostrarModal(abrir), 100);
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado');
    
    // FORMULARIO DE LOGIN
    const formLogin = document.getElementById('formLogin');
    if (formLogin) {
        formLogin.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;
            const errorDiv = document.getElementById('loginError');
            const submitBtn = formLogin.querySelector('button[type="submit"]');
            
            if (!email || !password) {
                errorDiv.textContent = 'Por favor completa todos los campos';
                errorDiv.style.display = 'block';
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errorDiv.textContent = 'Por favor ingresa un email válido';
                errorDiv.style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Iniciando sesión...';
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        accion: 'login',
                        email: email,
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.mensaje;
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Ingresar';
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = 'Error al procesar la solicitud';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ingresar';
            }
        });
    }
    
    // FORMULARIO DE REGISTRO
    const formRegistro = document.getElementById('formRegistro');
    if (formRegistro) {
        formRegistro.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const nombre = document.getElementById('regNombre').value.trim();
            const email = document.getElementById('regEmail').value.trim();
            const password = document.getElementById('regPassword').value;
            const passwordConfirm = document.getElementById('regPasswordConfirm').value;
            const errorDiv = document.getElementById('regError');
            const submitBtn = formRegistro.querySelector('button[type="submit"]');
            
            if (!nombre || !email || !password || !passwordConfirm) {
                errorDiv.textContent = 'Por favor completa todos los campos';
                errorDiv.style.display = 'block';
                return;
            }
            
            if (nombre.length < 3) {
                errorDiv.textContent = 'El nombre debe tener al menos 3 caracteres';
                errorDiv.style.display = 'block';
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errorDiv.textContent = 'Por favor ingresa un email válido';
                errorDiv.style.display = 'block';
                return;
            }
            
            if (password.length < 6) {
                errorDiv.textContent = 'La contraseña debe tener al menos 6 caracteres';
                errorDiv.style.display = 'block';
                return;
            }
            
            if (password !== passwordConfirm) {
                errorDiv.textContent = 'Las contraseñas no coinciden';
                errorDiv.style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registrando...';
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        accion: 'registro',
                        nombre: nombre,
                        email: email,
                        password: password,
                        passwordConfirm: passwordConfirm
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.mensaje;
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Registrarse';
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = 'Error al procesar la solicitud';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Registrarse';
            }
        });
    }
});

// Función para votar
async function votar(categoriaId, nominadoId) {
    try {
        const response = await fetch('votar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                categoria_id: categoriaId,
                nominado_id: nominadoId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const categoria = document.querySelector(`[data-categoria="${categoriaId}"]`).closest('.categoria-votacion');
            const botones = categoria.querySelectorAll('.btn-votar');
            const nominados = categoria.querySelectorAll('.nominado-voto');
            
            botones.forEach(btn => {
                btn.classList.remove('votado');
                btn.textContent = 'Votar';
            });
            nominados.forEach(nom => {
                nom.classList.remove('seleccionado');
            });
            
            const btnActual = document.querySelector(`[data-categoria="${categoriaId}"][data-nominado="${nominadoId}"]`);
            btnActual.classList.add('votado');
            btnActual.textContent = 'Votado ✓';
            btnActual.closest('.nominado-voto').classList.add('seleccionado');
            
            mostrarMensaje(data.mensaje, 'exito');
            
            if (typeof actualizarProgreso === 'function') {
                actualizarProgreso();
            }
        } else {
            mostrarMensaje(data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error al votar:', error);
        mostrarMensaje('Error al procesar el voto', 'error');
    }
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensajeExito');
    if (mensajeDiv) {
        mensajeDiv.textContent = mensaje;
        mensajeDiv.style.display = 'block';
        mensajeDiv.className = tipo === 'exito' ? 'mensaje-exito' : 'error-message';
        
        setTimeout(() => {
            mensajeDiv.style.display = 'none';
        }, 3000);
    }
}

// Función para cerrar sesión
async function cerrarSesion() {
    if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        return;
    }
    
    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                accion: 'logout'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert('Error al cerrar sesión: ' + (data.mensaje || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        alert('Error al cerrar sesión. Redirigiendo...');
        // Si hay error, intentar redirigir directamente
        window.location.href = 'index.php';
    }
}