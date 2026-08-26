import api from '../api';

export default {
    async getInspecciones() {
        const response = await api.get('/visar/inspecciones');
        return response.data;
    },
    async getLicencias() {
        const response = await api.get('/visar/licencias');
        return response.data;
    }
};
