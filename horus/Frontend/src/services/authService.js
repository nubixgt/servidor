import api from './api';

export const authService = {
    login(credentials) {
        return api.post('/login', credentials);
    },
    
    logout() {
        localStorage.removeItem('token');
    },

    getToken() {
        return localStorage.getItem('token');
    },

    isAuthenticated() {
        return !!localStorage.getItem('token');
    }
};
