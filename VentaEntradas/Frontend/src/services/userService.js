import api from './api';

// Solo administradores.
export default {
    getAll() {
        return api.get('/users');
    },
    create(payload) {
        return api.post('/users', payload); // { username, fullName, password, role }
    },
    update(id, payload) {
        return api.put(`/users/${id}`, payload); // { fullName?, role?, active?, password? }
    }
};
