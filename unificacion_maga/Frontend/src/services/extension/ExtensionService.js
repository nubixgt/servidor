import api from '../api';

export default {
    async getActividades(params) {
        const response = await api.get('/extension', { params });
        return response.data;
    },
    async createActividad(data) {
        const response = await api.post('/extension/actividades', data);
        return response.data;
    }
};
