import api, { descargar } from './api';

export default {
    async listar() {
        return (await api.get('/importaciones')).data;
    },
    async detalle(id) {
        return (await api.get(`/importaciones/${id}`)).data;
    },
    /** Sube el archivo y reporta el avance de la subida (0-100). */
    async subir(archivo, modo, alAvance) {
        const fd = new FormData();
        fd.append('archivo', archivo);
        fd.append('modo', modo);
        const r = await api.post('/importaciones', fd, {
            onUploadProgress: (e) => { if (e.total && alAvance) alAvance(e.loaded / e.total * 100); }
        });
        return r.data;
    },
    erroresCsv(id) {
        return descargar(`/importaciones/${id}/errores.csv`);
    }
};
