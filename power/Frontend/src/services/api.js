import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'https://m.nubix.gt/power/backend/api/v1',
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

// Response interceptor: an expired/invalid token means the session is dead,
// so clear it and send the user back to the login screen.
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('authUser');
            const login = `${import.meta.env.BASE_URL}login`;
            if (window.location.pathname !== login) {
                window.location.href = login;
            }
        }
        return Promise.reject(error);
    }
);

export default api;
