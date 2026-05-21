import axios from 'axios';

const isLocal = window.location.hostname === 'localhost' ||
                window.location.hostname === '127.0.0.1' ||
                window.location.hostname.startsWith('192.168.') ||
                window.location.hostname.startsWith('10.') ||
                window.location.hostname.endsWith('.local');

const api = axios.create({
    baseURL: isLocal ? 'http://localhost:8080' : '/sys-dipu/Backend/api/v1',
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
        // When sending FormData (file uploads), let the browser set the correct Content-Type
        if (config.data instanceof FormData) {
            delete config.headers['Content-Type'];
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

export default api;
