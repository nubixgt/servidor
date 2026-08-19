import api from './api';

export default {
    login(usuario, password) {
        return api.post('/auth/login', { usuario, password });
    },
    me() {
        return api.get('/auth/me');
    },
    cambiarPassword(actual, nueva) {
        return api.post('/auth/cambiar-password', { actual, nueva });
    },
};
