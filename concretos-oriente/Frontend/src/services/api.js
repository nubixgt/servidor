import axios from 'axios';
import { isTokenExpired, handleSessionExpired } from '../utils/auth';

const api = axios.create({
    baseURL: '/concretos-oriente/Backend/api/v1', // Adjust this to match your local setup
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor for API calls
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        const url = config.url || '';
        const isLogin = url.includes('/login');

        if (token && !isLogin && isTokenExpired(token)) {
            handleSessionExpired('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
            return Promise.reject(new Error('Session expired'));
        }

        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        const userName = localStorage.getItem('userName');
        if (userName) {
            config.headers['X-User-Name'] = userName;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Response interceptor for handling 401 globally
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            const url = error.config?.url || '';
            if (!url.includes('/login')) {
                handleSessionExpired('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
            }
        }
        return Promise.reject(error);
    }
);

export default api;
