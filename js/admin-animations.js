// ========================================
// ADMIN PANEL - ANIMACIONES Y EFECTOS
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Animación de entrada para las stat cards
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.5s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observar stat cards
    document.querySelectorAll('.stat-card').forEach((card, index) => {
        card.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(card);
    });
    
    // Observar categorías y nominados cuando se cargan
    const observeNewElements = () => {
        document.querySelectorAll('.nominado-admin-card, .categoria-card').forEach((card, index) => {
            if (!card.dataset.observed) {
                card.dataset.observed = 'true';
                card.style.transitionDelay = `${index * 0.05}s`;
                observer.observe(card);
            }
        });
    };
    
    // Ejecutar después de cargar datos
    const originalCargarCategorias = window.cargarCategorias;
    const originalCargarNominados = window.cargarNominados;
    
    if (typeof originalCargarCategorias === 'function') {
        window.cargarCategorias = async function() {
            await originalCargarCategorias();
            setTimeout(observeNewElements, 100);
        };
    }
    
    if (typeof originalCargarNominados === 'function') {
        window.cargarNominados = async function() {
            await originalCargarNominados();
            setTimeout(observeNewElements, 100);
        };
    }
    
    // Efecto de ripple en botones
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn, .btn-primary, .btn-secondary')) {
            const button = e.target;
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.className = 'ripple';
            
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            
            const existingRipple = button.querySelector('.ripple');
            if (existingRipple) {
                existingRipple.remove();
            }
            
            button.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        }
    });
    
    // Agregar estilos para ripple
    const style = document.createElement('style');
    style.textContent = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Efecto de typing en el título
    const title = document.querySelector('.admin-header h1');
    if (title) {
        const text = title.textContent;
        title.textContent = '';
        title.style.opacity = '1';
        
        let index = 0;
        const typeInterval = setInterval(() => {
            if (index < text.length) {
                title.textContent += text.charAt(index);
                index++;
            } else {
                clearInterval(typeInterval);
            }
        }, 50);
    }
    
    // Animación de contador para las estadísticas
    const animateCounter = (element, target) => {
        const duration = 1500;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    };
    
    // Animar contadores cuando son visibles
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.animated) {
                const target = parseInt(entry.target.textContent);
                if (!isNaN(target)) {
                    entry.target.dataset.animated = 'true';
                    animateCounter(entry.target, target);
                }
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.stat-info h3').forEach(counter => {
        counterObserver.observe(counter);
    });
    
    // Smooth scroll para navegación
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.onclick) return;
            
            // Añadir efecto de pulso
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
    
    // Efecto de hover mejorado para cards
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // Tooltip para iconos
    document.querySelectorAll('.btn-icon').forEach(btn => {
        const title = btn.getAttribute('title');
        if (title) {
            btn.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = title;
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(15, 23, 42, 0.95);
                    color: white;
                    padding: 0.5rem 0.75rem;
                    border-radius: 6px;
                    font-size: 0.8125rem;
                    pointer-events: none;
                    z-index: 10000;
                    white-space: nowrap;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
                `;
                
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.bottom + 8 + 'px';
                
                this.tooltip = tooltip;
            });
            
            btn.addEventListener('mouseleave', function() {
                if (this.tooltip) {
                    this.tooltip.remove();
                    this.tooltip = null;
                }
            });
        }
    });
    
    console.log('✨ Animaciones del admin panel cargadas');
});