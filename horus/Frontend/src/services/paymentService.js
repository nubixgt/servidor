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
    
    deletePayment(id) {
        return api.delete(`/pagos/${id}`);
    }
};
