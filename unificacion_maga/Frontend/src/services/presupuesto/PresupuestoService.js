import api from '../api';

export default {
    async getDashboard(params) {
        const response = await api.get('/presupuesto/dashboard', { params });
        return response.data;
    }
};
