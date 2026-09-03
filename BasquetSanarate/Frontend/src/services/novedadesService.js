import api from './api';

const unwrap = (res) => res.data.data;

export default {
    /** Público: solo publicadas. Con sesión admin: todas (+ ?estado). */
    async list(params = {}) {
        return unwrap(await api.get('/novedades', { params }));
    },

    async get(id) {
        return unwrap(await api.get(`/novedades/${id}`));
    },

    async create(payload) {
        return unwrap(await api.post('/novedades', payload));
    },

    async update(id, payload) {
        return unwrap(await api.put(`/novedades/${id}`, payload));
    },

    async remove(id) {
        return unwrap(await api.delete(`/novedades/${id}`));
    },

    async uploadPortada(id, file) {
        const fd = new FormData();
        fd.append('portada', file);
        return unwrap(await api.post(`/novedades/${id}/portada`, fd, { headers: { 'Content-Type': undefined } }));
    },

    async uploadPdf(id, file) {
        const fd = new FormData();
        fd.append('pdf', file);
        return unwrap(await api.post(`/novedades/${id}/pdf`, fd, { headers: { 'Content-Type': undefined } }));
    }
};
