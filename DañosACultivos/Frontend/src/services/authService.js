import api from './api';

const TOKEN_KEY = 'token';
const USER_KEY = 'user';

const decode = (token) => {
    try {
        const b64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
        return JSON.parse(atob(b64));
    } catch {
        return null;
    }
};

export default {
    async login(usuario, password) {
        const { data } = await api.post('/auth/login', { usuario, password });
        localStorage.setItem(TOKEN_KEY, data.token);
        localStorage.setItem(USER_KEY, JSON.stringify(data.user));
        return data.user;
    },
    logout() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    },
    /** Hay token y no ha expirado. */
    isAuthenticated() {
        const token = localStorage.getItem(TOKEN_KEY);
        const payload = token && decode(token);
        return !!payload && (!payload.exp || payload.exp * 1000 > Date.now());
    },
    user() {
        try {
            return JSON.parse(localStorage.getItem(USER_KEY));
        } catch {
            return null;
        }
    },
};
