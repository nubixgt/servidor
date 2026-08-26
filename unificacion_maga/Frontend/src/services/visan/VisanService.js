import api from '../api';

export default {
    async getDashboard() {
        const response = await api.get('/visan/dashboard');
        return response.data;
    },
    async getTable() {
        const response = await api.get('/visan/tabla');
        return response.data;
    },
    async getDapca() {
        const response = await api.get('/visan/dapca');
        return response.data;
    },
    async importExcel(formData) {
        const response = await api.post('/visan/importar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return response.data;
    },
    async getEntregas(params) {
        const response = await api.get('/visan/entregas', { params });
        return response.data;
    },
    async updateEntrega(id, data) {
        const response = await api.put(`/visan/entregas/${id}`, data);
        return response.data;
    }
};
