import api from './api';

export const clientService = {
    getAllClients() {
        return api.get('/clientes');
    },
    
    getClientById(id) {
        return api.get(`/clientes/${id}`);
    },
    
    createClient(clientData) {
        return api.post('/clientes', clientData);
    },
    
    updateClient(id, clientData) {
        return api.post(`/clientes/${id}`, clientData);
    },
    
    deleteClient(id) {
        return api.delete(`/clientes/${id}`);
    }
};
