import api from './api';

export default {
    async list() {
        const { data } = await api.get('/usuarios');
        return data.usuarios;
    },
    async create(usuario) {
        const { data } = await api.post('/usuarios', usuario);
        return data.usuario;
    },
    async update(id, usuario) {
        const { data } = await api.put(`/usuarios/${id}`, usuario);
        return data.usuario;
    },
    async remove(id) {
        await api.delete(`/usuarios/${id}`);
    },
};
