import api from './api';

export default {
    async list(params = {}) {
        const { data } = await api.get('/danos-infraestructura', { params });
        return data.danos;
    },
    async create(registro) {
        const { data } = await api.post('/danos-infraestructura', registro);
        return data.dano;
    },
    async remove(id) {
        await api.delete(`/danos-infraestructura/${id}`);
    },
};
