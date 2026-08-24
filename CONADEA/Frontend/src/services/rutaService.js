import api from './api';

// Backend/src/Controllers/RutaController.php — crear/actualizar/eliminar
// son exclusivos del rol Administrador (Authorize(['Administrador'])).
export default {
    listar() {
        return api.get('/rutas');
    },
    obtener(id) {
        return api.get(`/rutas/${id}`);
    },
    crear(datos) {
        return api.post('/rutas', datos);
    },
    actualizar(id, datos) {
        return api.put(`/rutas/${id}`, datos);
    },
    eliminar(id) {
        return api.delete(`/rutas/${id}`);
    }
};
