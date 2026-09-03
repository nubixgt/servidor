import api from './api';

const unwrap = (res) => res.data.data;

export default {
    /** @param {{activo?: string, conferencia?: string, rama?: string, search?: string}} params */
    async list(params = {}) {
        return unwrap(await api.get('/equipos', { params }));
    },

    async get(id) {
        return unwrap(await api.get(`/equipos/${id}`));
    },

    async create(payload) {
        return unwrap(await api.post('/equipos', payload));
    },

    async update(id, payload) {
        return unwrap(await api.put(`/equipos/${id}`, payload));
    },

    async remove(id) {
        return unwrap(await api.delete(`/equipos/${id}`));
    },

    async uploadLogo(id, file) {
        const fd = new FormData();
        fd.append('logo', file);
        return unwrap(await api.post(`/equipos/${id}/logo`, fd, {
            headers: { 'Content-Type': undefined }
        }));
    }
};
