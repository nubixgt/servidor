import api from './api';

export default {
    login(usuario, clave) {
        return api.post('/auth/login', { usuario, clave }).then(r => r.data.data);
    },
    me() {
        return api.get('/auth/me').then(r => r.data.data);
    }
};
