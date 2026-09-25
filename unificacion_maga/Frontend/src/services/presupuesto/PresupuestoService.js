import api from '../api';

export default {
    async getDashboard(params) {
        const response = await api.get('/presupuesto/dashboard', { params });
        return response.data;
    },
    async getDetalleUE(params) {
        const response = await api.get('/presupuesto/detalle-ue', { params });
        return response.data;
    }
};
