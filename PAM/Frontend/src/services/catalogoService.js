import api from './api';

export default {
    async todos() {
        return (await api.get('/catalogos')).data;
    },
    async calidad() {
        return (await api.get('/catalogos/calidad')).data;
    }
};
