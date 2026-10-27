import api, { descargar } from './api';

export default {
    async catalogo() {
        return (await api.get('/reportes')).data;
    },
    exportar(cuerpo) {
        return descargar('/reportes/exportar', cuerpo);
    }
};
