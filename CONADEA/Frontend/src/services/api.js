import axios from 'axios';

const getBaseURL = () => {
    // Backend/src/Core/Router.php solo recorta "/CONADEA/Backend" de la URL
    // (ver comentario en dispatch()) y compara el resto contra rutas como
    // "/auth/login" — sin el "/api/v1". La app móvil usa la misma base
    // (ver app_conadea/lib/core/config/api_config.dart).
    return '/CONADEA/Backend';
};

const api = axios.create({
    baseURL: getBaseURL(),
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
