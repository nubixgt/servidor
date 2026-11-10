import api from './api';

// Contrato esperado del backend (ver Diseno/LEEME.md):
//   GET    /reservations                      -> [{ tableId, bookingId, name, createdAt }]
//   POST   /reservations  { tableIds, name }  -> 201 { bookingId, tableIds, createdAt } | 409/400 { error }
//   DELETE /reservations  { bookingId }       -> 200 { ok: true }
export default {
    getAll() {
        return api.get('/reservations');
    },
    create(tableIds, name, phone) {
        return api.post('/reservations', { tableIds, name, phone });
    },
    cancel(bookingId) {
        return api.delete('/reservations', { data: { bookingId } });
    }
};
