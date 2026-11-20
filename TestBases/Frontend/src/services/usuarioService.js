import api from './api';

export default {
    listar() {
        return api.get('/usuarios').then(r => r.data.data);
    },
    crear(datos) {
        return api.post('/usuarios', datos).then(r => r.data.data);
    },
    actualizar(id, datos) {
        return api.put(`/usuarios/${id}`, datos).then(r => r.data.data);
    },
    eliminar(id) {
        return api.delete(`/usuarios/${id}`).then(r => r.data.data);
    }
};
