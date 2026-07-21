import axios from 'axios';
import router from '../router';

export const getApiBaseUrl = () => {
    const isLocal = window.location.hostname === 'localhost' ||
                    window.location.hostname === '127.0.0.1' ||
                    window.location.hostname.startsWith('192.168.') ||
                    window.location.hostname.startsWith('10.') ||
                    window.location.hostname.endsWith('.local');

    if (!isLocal) {
        // Producción: m.nubix.gt/EleccionCYD/
        return 'https://m.nubix.gt/EleccionCYD/Backend/api/v1';
    }

    // Local: si Vite corre en su propio puerto de dev (no bajo Apache), usar VITE_API_BASE_URL
    const hasViteDevServer = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';

    if (hasViteDevServer) {
        return import.meta.env.VITE_API_BASE_URL || 'http://localhost/EleccionCYD/Backend/api/v1';
    }

    // XAMPP/Apache local: Frontend y Backend sirven bajo el mismo host, ruta relativa a /EleccionCYD/
    const path = window.location.pathname;
    const idx = path.indexOf('/EleccionCYD/');
    const basePath = idx !== -1 ? path.substring(0, idx) : '';
    const port = window.location.port ? `:${window.location.port}` : '';
    return `${window.location.protocol}//${window.location.hostname}${port}${basePath}/EleccionCYD/Backend/api/v1`;
};

const api = axios.create({
    baseURL: getApiBaseUrl(),
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor for API calls
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Si el token expiró o es inválido, cierra sesión y manda al login en vez de dejar la app en un estado roto.
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401 && router.currentRoute.value.name !== 'Login') {
            localStorage.removeItem('token');
            localStorage.removeItem('usuario');
            router.push({ name: 'Login' });
        }
        return Promise.reject(error);
    }
);

export default api;
