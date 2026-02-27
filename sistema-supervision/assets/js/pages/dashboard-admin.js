/**
 * DASHBOARD ADMIN - JavaScript con Glassmorphism
 * Sistema de Supervisión v6.0
 */

// Esperar a que TODO el DOM esté completamente cargado
window.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard Admin cargando...');
    
    // Pequeño delay para asegurar que el DOM está listo
    setTimeout(function() {
        initDashboardStats();
        initQuickAccessCards();
        console.log('✅ Dashboard completamente inicializado');
    }, 100);
});

/**
 * Animar estadísticas al cargar
 */
function initDashboardStats() {
    const statValues = document.querySelectorAll('.stat-value');
    
    console.log(`📊 Buscando estadísticas... Encontradas: ${statValues.length}`);
    
    if (statValues.length === 0) {
        console.error('❌ No se encontraron elementos .stat-value en el DOM');
        return;
    }
    
    statValues.forEach((stat, index) => {
        const target = parseInt(stat.getAttribute('data-target')) || 0;
        console.log(`📈 Tarjeta ${index + 1}: data-target="${stat.getAttribute('data-target')}" → ${target}`);
        
        animateCounter(stat, 0, target, 1500);
    });
    
    console.log('✅ Animación de estadísticas iniciada');
}

/**
 * Animar contador de números
 */
function animateCounter(element, start, end, duration) {
    console.log(`🎬 Iniciando animación: ${start} → ${end}`);
    
    // Si el valor final es 0, mostrar 0 inmediatamente
    if (end === 0) {
        element.textContent = '0';
        console.log('⚠️ Valor es 0, no se anima');
        return;
    }
    
    const startTime = performance.now();
    const range = end - start;
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function (ease-out cubic)
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (range * easeOut));
        
        // Actualizar el texto con formato de miles
        element.textContent = current.toLocaleString('es-GT');
        
        // Continuar animando
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            // Asegurar que termina en el valor exacto
            element.textContent = end.toLocaleString('es-GT');
            console.log(`✅ Animación completada: ${end}`);
        }
    }
    
    requestAnimationFrame(update);
}

/**
 * Inicializar animaciones en botones de acceso rápido
 */
function initQuickAccessCards() {
    const cards = document.querySelectorAll('.quick-access-card');
    
    console.log(`🎯 Inicializando ${cards.length} tarjetas de acceso rápido`);
    
    if (cards.length === 0) {
        console.warn('⚠️ No se encontraron tarjetas de acceso rápido');
        return;
    }
    
    cards.forEach((card, index) => {
        // Efecto de ripple al hacer click
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                border-radius: 50%;
                background: rgba(59, 130, 246, 0.3);
                transform: scale(0);
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
                z-index: 0;
            `;

            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    console.log('✅ Botones de acceso rápido inicializados');
}

/**
 * Agregar estilos de animación ripple (solo una vez)
 */
if (!document.getElementById('ripple-animation-styles')) {
    const animationStyles = document.createElement('style');
    animationStyles.id = 'ripple-animation-styles';
    animationStyles.textContent = `
        @keyframes rippleEffect {
            to {
                transform: scale(2.5);
                opacity: 0;
            }
        }
        
        .quick-access-card {
            position: relative;
            overflow: hidden;
        }
    `;
    document.head.appendChild(animationStyles);
}

/**
 * Actualizar estadísticas en tiempo real (opcional)
 */
function refreshStats() {
    console.log('🔄 Actualizando estadísticas...');
    
    // Aquí puedes implementar una llamada fetch a una API
    // para obtener las estadísticas actualizadas
    
    /*
    fetch('/api/estadisticas.php')
        .then(response => response.json())
        .then(data => {
            updateStat(1, data.usuarios);
            updateStat(2, data.contratistas);
            updateStat(3, data.trabajadores);
            updateStat(4, data.supervisiones);
            updateStat(5, data.proyectos);
            updateStat(6, data.equipos);
        })
        .catch(error => console.error('❌ Error al actualizar:', error));
    */
}

/**
 * Actualizar valor de una estadística específica
 */
function updateStat(index, newValue) {
    const statCards = document.querySelectorAll('.stat-card');
    if (statCards[index - 1]) {
        const statValue = statCards[index - 1].querySelector('.stat-value');
        const oldValue = parseInt(statValue.textContent.replace(/,/g, '')) || 0;
        
        if (oldValue !== newValue) {
            console.log(`📊 Actualizando tarjeta ${index}: ${oldValue} → ${newValue}`);
            animateCounter(statValue, oldValue, newValue, 800);
        }
    }
}

/**
 * Auto-actualizar estadísticas cada 30 segundos (descomenta para activar)
 */
// setInterval(refreshStats, 30000);

/**
 * Función de debugging - llamar desde consola
 */
window.debugDashboard = function() {
    console.log('🔍 DEBUG DASHBOARD:');
    console.log('==================');
    
    const statValues = document.querySelectorAll('.stat-value');
    console.log(`Total stat-value encontrados: ${statValues.length}`);
    
    statValues.forEach((stat, i) => {
        console.log(`\nTarjeta ${i + 1}:`);
        console.log('  Elemento:', stat);
        console.log('  data-target:', stat.getAttribute('data-target'));
        console.log('  Texto actual:', stat.textContent);
        console.log('  Padre:', stat.parentElement);
    });
    
    console.log('\n==================');
};

console.log('💡 Tip: Escribe debugDashboard() en la consola para ver info detallada');