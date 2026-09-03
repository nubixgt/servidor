import api from './api';

const unwrap = (res) => res.data.data;

export default {
    /** @param {{estado?, jornada?, fase?, equipo_id?}} params */
    async list(params = {}) {
        return unwrap(await api.get('/partidos', { params }));
    },

    async get(id) {
        return unwrap(await api.get(`/partidos/${id}`));
    },

    async create(payload) {
        return unwrap(await api.post('/partidos', payload));
    },

    async update(id, payload) {
        return unwrap(await api.put(`/partidos/${id}`, payload));
    },

    async remove(id) {
        return unwrap(await api.delete(`/partidos/${id}`));
    }
};
