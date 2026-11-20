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
    /** Guarda comentario y/o imagen de una fila. `imagen` es un File o null (no tocar). */
    guardarSeguimiento(id, { comentario, imagen }) {
        const datos = new FormData();
        datos.append('comentario', comentario ?? '');
        if (imagen) datos.append('imagen', imagen);
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
