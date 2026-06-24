import api from './api';

export const referidoService = {
    getReferidos() {
        return api.get('/referidos');
    },
    getReferido(id) {
        return api.get(`/referidos/${id}`);
    },
    createReferido(data) {
        // use FormData for multipart
        return api.post('/referidos', data);
    },
    updateReferido(id, data) {
        return api.post(`/referidos/${id}`, data);
    },
    deleteReferido(id) {
        return api.delete(`/referidos/${id}`);
    },
    getPagos(id) {
        return api.get(`/referidos/${id}/pagos`);
    },
    addPago(id, data) {
        return api.post(`/referidos/${id}/pagos`, data);
    },
    deletePago(id) {
        return api.delete(`/referidos/pagos/${id}`);
    }
};
