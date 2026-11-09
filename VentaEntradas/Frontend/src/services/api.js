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

export default api;
