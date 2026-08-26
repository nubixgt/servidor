import api from '../api';

export default {
    async getProductores(params) {
        const response = await api.get('/productores', { params });
        return response.data;
    },
    async getProductor(id) {
        const response = await api.get(`/productores/${id}`);
        return response.data;
    },
    async createProductor(data) {
        const response = await api.post('/productores', data);
        return response.data;
    },
    async updateProductor(id, data) {
        const response = await api.put(`/productores/${id}`, data);
        return response.data;
    },
    async deleteProductor(id) {
        const response = await api.delete(`/productores/${id}`);
        return response.data;
    }
};
