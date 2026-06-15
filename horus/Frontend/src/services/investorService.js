import api from './api';

export const investorService = {
    getAllInvestors() {
        return api.get('/inversionistas');
    },
    
    getInvestorById(id) {
        return api.get(`/inversionistas/${id}`);
    },
    
    createInvestor(data) {
        return api.post('/inversionistas', data);
    },
    
    updateInvestor(id, data) {
        return api.post(`/inversionistas/${id}`, data);
    },
    
    deleteInvestor(id) {
        return api.delete(`/inversionistas/${id}`);
    }
};
