import axios from 'axios';

// En producción resuelve a `/VentaEntradas/Backend/api/v1` (mismo origen).
// En desarrollo se puede sobreescribir con VITE_API_BASE (ver .env.example).
const baseURL =
    import.meta.env.VITE_API_BASE ||
    `${import.meta.env.BASE_URL}Backend/api/v1`;

const api = axios.create({
    baseURL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor for API calls
api.interceptors.request.use(
    config => {
        const token = (() => { try { return localStorage.getItem('sanafer_token'); } catch { return null; } })();
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Sesión vencida o usuario desactivado: App.vue escucha este evento y vuelve al login.
api.interceptors.response.use(
    response => response,
    error => {
        const isLogin = error.config?.url?.includes('/auth/login');
        if (error.response?.status === 401 && !isLogin) {
            window.dispatchEvent(new CustomEvent('auth:expired', { detail: error.response.data?.error }));
        }
        return Promise.reject(error);
    }
);

export default api;
