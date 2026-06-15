import api from './api';

export const prestamoService = {
    getPrestamos() {
        return api.get('/prestamos');
    },
    
    getPrestamoById(id) {
        return api.get(`/prestamos/${id}`);
    },
    
    createPrestamo(prestamoData) {
        return api.post('/prestamos', prestamoData);
    },
    
    updatePrestamo(id, prestamoData) {
        return api.post(`/prestamos/${id}`, prestamoData);
    },
    
    deletePrestamo(id) {
        return api.delete(`/prestamos/${id}`);
    }
};
