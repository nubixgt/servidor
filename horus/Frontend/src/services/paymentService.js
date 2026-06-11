import api from './api';

export const paymentService = {
    getAllPayments() {
        return api.get('/pagos');
    },
    
    getPaymentById(id) {
        return api.get(`/pagos/${id}`);
    },
    
    createPayment(paymentData) {
        return api.post('/pagos', paymentData);
    },
    
    updatePayment(id, paymentData) {
        return api.post(`/pagos/${id}`, paymentData);
    },
    
    deletePayment(id) {
        return api.delete(`/pagos/${id}`);
    }
};
