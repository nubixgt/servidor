import api from '../api';

export default {
    async getAll() {
        const response = await api.get('/actividades-despacho');
        return response.data;
    },
    async getStats() {
        const response = await api.get('/actividades-despacho/stats');
        return response.data;
    },
    async getTecnicos() {
        const response = await api.get('/actividades-despacho/tecnicos');
        return response.data;
    },
    async create(data) {
        return await api.post('/actividades-despacho', data);
    },
    async update(id, data) {
        return await api.put(`/actividades-despacho/${id}`, data);
    },
    async delete(id) {
        return await api.delete(`/actividades-despacho/${id}`);
    }
};
