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
    }
};
