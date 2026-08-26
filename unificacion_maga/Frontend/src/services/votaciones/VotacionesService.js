import api from '../api';

export default {
    async getEventos(filters = {}) {
        const response = await api.get('/votaciones/eventos', { params: filters });
        return response.data;
    },
    async getSummary() {
        const response = await api.get('/votaciones/summary');
        return response.data;
    },
    async getEstadisticas(filters = {}) {
        const response = await api.get('/votaciones/estadisticas', { params: filters });
        return response.data;
    },
    async getCongresistas(filters = {}) {
        const response = await api.get('/votaciones/congresistas', { params: filters });
        return response.data;
    },
    async getBloques() {
        const response = await api.get('/votaciones/bloques');
        return response.data;
    },
    async uploadPdf(formData) {
        const response = await api.post('/votaciones/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        return response.data;
    }
};
