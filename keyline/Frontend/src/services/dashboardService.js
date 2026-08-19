import api from './api';

export default {
    resumen() {
        return api.get('/dashboard/resumen');
    },
};
