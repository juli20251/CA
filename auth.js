// auth.js - Sistema de autenticación mejorado

// Verificar si el usuario está logueado
function isLoggedIn() {
    return localStorage.getItem('isLoggedIn') === 'true';
}

// Obtener información del usuario
function getUserInfo() {
    return {
        nombre: localStorage.getItem('userName'),
        email: localStorage.getItem('userEmail'),
        rol: localStorage.getItem('userRole')
    };
}

// Manejar Login
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const errorDiv = document.getElementById('loginError');
    const successDiv = document.getElementById('loginSuccess');
    
    // Limpiar mensajes
    errorDiv.textContent = '';
    successDiv.textContent = '';
    
    try {
        const formData = new FormData();
        formData.append('accion', 'login');
        formData.append('email', email);
        formData.append('password', password);
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        // Intentar parsear la respuesta
        let data;
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // Si no es JSON, mostrar el texto crudo para depuración
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            throw new Error('El servidor no respondió con JSON válido');
        }
        
        if (data.success) {
            // Guardar información del usuario
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userName', data.nombre || email.split('@')[0]);
            localStorage.setItem('userEmail', email);
            localStorage.setItem('userRole', data.rol || 'user');
            
            // Mostrar mensaje de éxito
            successDiv.textContent = data.mensaje || 'Inicio de sesión exitoso';
            
            // Redirigir según el rol después de un breve delay
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // Fallback por si no viene redirect
                    if (data.rol === 'admin') {
                        window.location.href = 'admin_dashboard.php';
                    } else {
                        window.location.href = 'votacion.php';
                    }
                }
            }, 500);
            
        } else {
            errorDiv.textContent = data.mensaje || 'Error al iniciar sesión';
        }
        
    } catch (error) {
        console.error('Error:', error);
        errorDiv.textContent = 'Error de conexión. Por favor intenta nuevamente.';
    }
}

// Manejar Registro
async function handleRegister(event) {
    event.preventDefault();
    
    const nombre = document.getElementById('registerUsername').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const errorDiv = document.getElementById('registerError');
    const successDiv = document.getElementById('registerSuccess');
    
    // Limpiar mensajes
    errorDiv.textContent = '';
    successDiv.textContent = '';
    
    // Validación básica del lado del cliente
    if (password.length < 6) {
        errorDiv.textContent = 'La contraseña debe tener al menos 6 caracteres';
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('accion', 'registro');
        formData.append('nombre', nombre);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('passwordConfirm', password);
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        // Intentar parsear la respuesta
        let data;
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            throw new Error('El servidor no respondió con JSON válido');
        }
        
        if (data.success) {
            // Guardar información del usuario
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userName', nombre);
            localStorage.setItem('userEmail', email);
            localStorage.setItem('userRole', 'user');
            
            // Mostrar mensaje de éxito
            successDiv.textContent = data.mensaje || 'Registro exitoso';
            
            // Redirigir después de un breve delay
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = 'votacion.php';
                }
            }, 500);
            
        } else {
            errorDiv.textContent = data.mensaje || 'Error al registrarse';
        }
        
    } catch (error) {
        console.error('Error:', error);
        errorDiv.textContent = 'Error de conexión. Por favor intenta nuevamente.';
    }
}

// Manejar Logout
async function handleLogout() {
    try {
        const formData = new FormData();
        formData.append('accion', 'logout');
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Limpiar localStorage
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userName');
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userRole');
            
            // Redirigir
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'index.php';
            }
        }
        
    } catch (error) {
        console.error('Error:', error);
        // Forzar logout local aunque falle el servidor
        localStorage.clear();
        window.location.href = 'index.php';
    }
}

// Función para cerrar sesión (alias)
function cerrarSesion() {
    handleLogout();
}

// Mostrar/Ocultar modal de autenticación
function showModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // Limpiar formularios
    document.getElementById('loginEmail').value = '';
    document.getElementById('loginPassword').value = '';
    document.getElementById('registerUsername').value = '';
    document.getElementById('registerEmail').value = '';
    document.getElementById('registerPassword').value = '';
    document.getElementById('loginError').textContent = '';
    document.getElementById('loginSuccess').textContent = '';
    document.getElementById('registerError').textContent = '';
    document.getElementById('registerSuccess').textContent = '';
}

// Cambiar entre login y registro
function showLogin() {
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('registerForm').style.display = 'none';
}

function showRegister() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'block';
}

// Inicializar el estado de autenticación
function initAuth() {
    const loggedIn = isLoggedIn();
    const userInfo = getUserInfo();
    
    // Elementos del DOM
    const authButton = document.getElementById('authButton');
    const userSection = document.getElementById('userSection');
    const userName = document.getElementById('userName');
    
    if (loggedIn && authButton && userSection) {
        // Usuario logueado: mostrar info del usuario
        authButton.style.display = 'none';
        userSection.style.display = 'block';
        if (userName) {
            userName.textContent = userInfo.nombre || 'Usuario';
        }
    } else if (authButton && userSection) {
        // Usuario no logueado: mostrar botón de login
        authButton.style.display = 'block';
        userSection.style.display = 'none';
    }
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('authModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuth);
} else {
    initAuth();
}