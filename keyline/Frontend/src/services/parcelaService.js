import api from './api';

export default {
    listar(params = {}) {
        return api.get('/parcelas', { params });
    },
    obtener(id) {
        return api.get(`/parcelas/${id}`);
    },
    crear(payload) {
        return api.post('/parcelas', payload);
    },
    actualizar(id, payload) {
        return api.put(`/parcelas/${id}`, payload);
    },
    revisar(id, estadoValidacion, comentario) {
        return api.post(`/parcelas/${id}/revision`, { estadoValidacion, comentario });
    },
    eliminar(id) {
        return api.delete(`/parcelas/${id}`);
    },
    subirFotos(id, formData) {
        // 'Content-Type': undefined deja que axios detecte el FormData y
        // genere el boundary multipart automáticamente (el header fijo del
        // cliente por defecto es application/json y rompería la carga).
        return api.post(`/parcelas/${id}/fotos`, formData, {
            headers: { 'Content-Type': undefined },
        });
    },
    eliminarFoto(id, fotoId) {
        return api.delete(`/parcelas/${id}/fotos/${fotoId}`);
    },
};
