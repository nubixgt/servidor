import api from './api';

export const recurringExpenseService = {
    getAllGastos: () => api.get('/gastos-recurrentes'),
    getGastoById: (id) => api.get(`/gastos-recurrentes/${id}`),
    createGasto: (payload) => api.post('/gastos-recurrentes', payload),
    updateGasto: (id, payload) => api.put(`/gastos-recurrentes/${id}`, payload),
    deleteGasto: (id) => api.delete(`/gastos-recurrentes/${id}`)
};
