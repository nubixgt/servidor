import api from './api';

export default {
    listar(params) {
        return api.get('/beneficiarios', { params }).then(r => r.data.data);
    },
    resumen() {
        return api.get('/beneficiarios/resumen').then(r => r.data.data);
    },
    catalogos() {
        return api.get('/catalogos').then(r => r.data.data);
    },
    /**
     * Guarda comentario, imagen y/o teléfono de una fila. `imagen` es un File o null (no tocar);
     * `telefono` solo se envía cuando la fila no tenía uno.
     * Responde { seguimiento, telefono }.
     */
    guardarSeguimiento(id, { comentario, imagen, telefono }) {
        const datos = new FormData();
        datos.append('comentario', comentario ?? '');
        if (imagen) datos.append('imagen', imagen);
        if (telefono) datos.append('telefono', telefono);
        return api.post(`/beneficiarios/${id}/seguimiento`, datos).then(r => r.data.data);
    },
    quitarImagen(id) {
        return api.delete(`/beneficiarios/${id}/seguimiento/imagen`).then(r => r.data.data);
    },
    /** Descarga la imagen con el token y devuelve un Blob. */
    imagen(id) {
        return api.get(`/beneficiarios/${id}/imagen`, { responseType: 'blob' }).then(r => r.data);
    }
};
