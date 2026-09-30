import axios from 'axios';

const api = axios.create({
    baseURL: '/Da%C3%B1osACultivos/Backend/api/v1',
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

// Sesión vencida o token inválido: limpia la sesión y vuelve al login (excepto en el propio login).
api.interceptors.response.use(
    response => response,
    error => {
        const url = error.config?.url || '';
        if (error.response?.status === 401 && !url.includes('/auth/login')) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = import.meta.env.BASE_URL + 'login';
        }
        return Promise.reject(error);
    }
);

export default api;
