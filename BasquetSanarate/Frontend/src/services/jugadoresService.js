import api from './api';

const unwrap = (res) => res.data.data;

export default {
    /** @param {{activo?, equipo_id?, posicion?, estado?, search?, page?, perPage?}} params */
    async list(params = {}) {
        return unwrap(await api.get('/jugadores', { params }));
    },

    async get(id) {
        return unwrap(await api.get(`/jugadores/${id}`));
    },

    async create(payload) {
        return unwrap(await api.post('/jugadores', payload));
    },

    async update(id, payload) {
        return unwrap(await api.put(`/jugadores/${id}`, payload));
    },

    async remove(id) {
        return unwrap(await api.delete(`/jugadores/${id}`));
    },

    async uploadFoto(id, file) {
        const fd = new FormData();
        fd.append('foto', file);
        return unwrap(await api.post(`/jugadores/${id}/foto`, fd, {
            headers: { 'Content-Type': undefined }
        }));
    }
};
