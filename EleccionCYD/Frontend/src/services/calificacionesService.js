import api from './api';

export default {
  guardar(ronda, payload) {
    return api.post(`/calificaciones/${ronda}`, payload);
  },
  misCalificaciones(ronda) {
    return api.get(`/calificaciones/${ronda}/mias`);
  },
  leaderboard() {
    return api.get('/leaderboard');
  },
};
