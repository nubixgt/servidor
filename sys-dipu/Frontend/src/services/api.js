import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8080', // ⚠️ LOCAL - Para producción: 'https://m.nubix.gt/sys-dipu/Backend/api/v1'
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
