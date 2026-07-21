import api from './api';

export default {
  getAll() {
    return api.get('/participantes');
  },
};
