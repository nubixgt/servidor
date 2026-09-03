import axios from 'axios';

// En producción resuelve a `/BasquetSanarate/Backend/api/v1` (mismo origen).
// En desarrollo se puede sobreescribir con VITE_API_BASE (ver Frontend/.env.development).
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

// Adjunta el token en cada petición
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => Promise.reject(error)
);

// Si el token expira o es inválido, cerrar sesión y volver al login.
// Se usa window.location (no el router) para evitar un import circular
// api.js -> router -> store -> api.js
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            const loginUrl = `${import.meta.env.BASE_URL}login`;
            if (!window.location.pathname.endsWith('/login')) {
                window.location.assign(loginUrl);
            }
        }
        return Promise.reject(error);
    }
);

export default api;
