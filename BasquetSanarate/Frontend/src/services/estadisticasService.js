import api from './api';

const unwrap = (res) => res.data.data;

export default {
    async jugadores() {
        return unwrap(await api.get('/estadisticas/jugadores'));
    },

    async updateJugador(id, payload) {
        return unwrap(await api.put(`/estadisticas/jugadores/${id}`, payload));
    },

    async clasificacion() {
        return unwrap(await api.get('/estadisticas/clasificacion'));
    },

    async updateClasificacion(equipoId, payload) {
        return unwrap(await api.put(`/estadisticas/clasificacion/${equipoId}`, payload));
    },

    async recalcular() {
        return unwrap(await api.post('/estadisticas/recalcular'));
    }
};
