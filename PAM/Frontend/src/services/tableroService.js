import api from './api';

export default {
    async datos() {
        return (await api.get('/tablero/datos')).data;
    }
};
