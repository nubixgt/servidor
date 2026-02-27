/**
 * assets/js/pages/dashboard-tecnico.js
 * Dashboard Técnico - Glassmorphism Edition
 * Sistema de Supervisión v6.0.4
 * ACTUALIZADO: Logout movido a navbar_tecnico.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Dashboard Técnico cargado');
    
    // ========== ANIMACIÓN DE CONTADORES ==========
    animateCounters();
    
    // ========== PARALLAX CARDS ==========
    initParallaxCards();
});

/**
 * Animar números de estadísticas
 */
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number[data-target]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000; // 2 segundos
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        // Usar Intersection Observer para iniciar cuando sea visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(counter);
    });
}

/**
 * Efecto parallax suave en las tarjetas
 */
function initParallaxCards() {
    const cards = document.querySelectorAll('.stat-card, .quick-action-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });
}

// Inicializar parallax después de cargar
window.addEventListener('load', () => {
    initParallaxCards();
});