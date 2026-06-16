import api from './api';

export const expenseService = {
    getAllEgresos: () => api.get('/egresos'),
    getEgresoById: (id) => api.get(`/egresos/${id}`),
    createEgreso: (payload) => api.post('/egresos', payload, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
    updateEgreso: (id, payload) => api.post(`/egresos/${id}`, payload, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }),
    deleteEgreso: (id) => api.delete(`/egresos/${id}`)
};
