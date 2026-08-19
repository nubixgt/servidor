import api from './api';

export default {
    listar() {
        return api.get('/usuarios');
    },
    crear(payload) {
        return api.post('/usuarios', payload);
    },
    actualizar(id, payload) {
        return api.put(`/usuarios/${id}`, payload);
    },
    eliminar(id) {
        return api.delete(`/usuarios/${id}`);
    },
};
