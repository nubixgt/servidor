import api from './api';

export const padreService = {
    registrar(payload) {
        return api.post('/padres', {
            nombre_completo: payload.nombreCompleto,
            telefono:        payload.telefono,
            correo:          payload.correo,
            direccion:       payload.direccion,
            departamento:    payload.departamento,
        });
    },

    obtenerTodos() {
        return api.get('/padres');
    },

    actualizar(id, payload) {
        return api.put(`/padres/${id}`, {
            nombre_completo: payload.nombreCompleto,
            telefono:        payload.telefono,
            correo:          payload.correo,
            direccion:       payload.direccion,
            departamento:    payload.departamento,
        });
    },

    eliminar(id) {
        return api.delete(`/padres/${id}`);
    },
};
