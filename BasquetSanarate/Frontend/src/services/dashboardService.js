import api from './api';

export default {
    /** Resumen del panel de administración. */
    async getResumen() {
        const { data } = await api.get('/dashboard/resumen');
        return data.data;
    }
};
