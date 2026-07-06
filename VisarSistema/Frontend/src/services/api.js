import axios from 'axios';
import router from '../router';

const getBaseURL = () => {
    const isLocal = window.location.hostname === 'localhost' || 
                    window.location.hostname === '127.0.0.1' ||
                    window.location.hostname.startsWith('192.168.');
    
    if (isLocal) {
        return '/VisarSistema/Backend/api/v1';
    }
    // En producción detecta dinámicamente si se accede por dominio o subcarpeta
    if (window.location.hostname === 'maga.nubix.gt') {
        return '/Backend/api/v1';
    }
    return '/VisarSistema/Backend/api/v1';
};

const api = axios.create({
    baseURL: getBaseURL(),
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// ─── Request Interceptor ───────────────────────────────────────────────────
// Adjunta el token en cada petición y verifica localmente si ya expiró
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            // Verificar expiración del token antes de enviar la petición
            try {
                const parts = token.split('.');
                if (parts.length === 3) {
                    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                    if (payload.exp && payload.exp < Math.floor(Date.now() / 1000)) {
                        // Token expirado localmente → cerrar sesión sin esperar al servidor
                        clearSession();
                        return Promise.reject(new Error('Sesión expirada'));
                    }
                }
            } catch (_) {
                // Si el token no es JWT estándar, igual lo enviamos; el servidor decidirá
            }
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => Promise.reject(error)
);

// ─── Response Interceptor ──────────────────────────────────────────────────
// Si el servidor responde 401, limpiar sesión y redirigir al login
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            clearSession();
        }
        return Promise.reject(error);
    }
);

// ─── Helper: limpiar sesión y redirigir ───────────────────────────────────
function clearSession() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('isAuthenticated');

    // Solo redirigir si no estamos ya en /login
    if (router.currentRoute.value.path !== '/login') {
        router.push({ path: '/login', query: { expired: '1' } });
    }
}

export { clearSession };
export default api;

