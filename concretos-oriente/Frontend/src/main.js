import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import AOS from 'aos'
import 'aos/dist/aos.css'

const app = createApp(App)

// Inicializar AOS
AOS.init({
  duration: 800, // Duración de la animación en ms
  once: false, // Animación solo la primera vez que se hace scroll? (false para que repita)
  offset: 100, // Desplazamiento desde el trigger original
})

import { isTokenExpired, handleSessionExpired } from './utils/auth'

// Interceptar peticiones fetch globalmente para agregar el JWT y detectar expiración
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    let [resource, config] = args;
    const token = localStorage.getItem('token');
    const url = resource instanceof Request ? resource.url : String(resource);
    const sameOrigin = new URL(url, window.location.href).origin === window.location.origin;
    const isBackendApi = url.includes('/Backend/api/') || url.includes('/api/v1');
    const isLoginEndpoint = url.includes('/login');

    // Si el token ya expiró antes de salir la petición a una API protegida
    if (token && isBackendApi && !isLoginEndpoint && isTokenExpired(token)) {
        handleSessionExpired('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.', router);
        return new Response(JSON.stringify({ status: 'error', message: 'Unauthorized: Token Expired' }), {
            status: 401,
            headers: { 'Content-Type': 'application/json' }
        });
    }

    if (token && sameOrigin) {
        config = config || {};
        // Manejar tanto Headers object como un plain object
        if (config.headers instanceof Headers) {
            if (!config.headers.has('Authorization')) {
                config.headers.append('Authorization', `Bearer ${token}`);
            }
        } else {
            config.headers = {
                'Authorization': `Bearer ${token}`,
                ...config.headers
            };
        }
    }
    
    try {
        const response = await originalFetch(resource, config);
        
        // Si el servidor responde 401 en cualquier ruta que no sea /login
        if (response.status === 401 && isBackendApi && !isLoginEndpoint) {
            handleSessionExpired('Tu sesión ha caducado. Por favor, inicia sesión nuevamente.', router);
        }
        
        return response;
    } catch (error) {
        throw error;
    }
};

// Verificación proactiva cuando el usuario regresa a la pestaña tras inactividad
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        const currentToken = localStorage.getItem('token');
        if (currentToken && isTokenExpired(currentToken)) {
            handleSessionExpired('Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.', router);
        }
    }
});

app.use(createPinia())
app.use(router)

app.mount('#app')
