import api from './api';

export default {
    async list(params = {}) {
        const { data } = await api.get('/danos-pecuarios', { params });
        return data.danos;
    },
    async create(registro) {
        const { data } = await api.post('/danos-pecuarios', registro);
        return data.dano;
    },
    async remove(id) {
        await api.delete(`/danos-pecuarios/${id}`);
    },
};
