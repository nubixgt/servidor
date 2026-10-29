import api, { descargar } from './api';

function formDpi(archivo, columnas) {
    const fd = new FormData();
    fd.append('archivo', archivo);
    fd.append('columnas', JSON.stringify(columnas));
    return fd;
}

export default {
    async catalogo() {
        return (await api.get('/reportes')).data;
    },
    exportar(cuerpo) {
        return descargar('/reportes/exportar', cuerpo);
    },

    // reportes en Excel
    async columnas() {
        return (await api.get('/reportes/columnas')).data;
    },
    async geograficoResumen(cuerpo) {
        return (await api.post('/reportes/geografico/resumen', cuerpo)).data;
    },
    async geograficoPrevia(cuerpo) {
        return (await api.post('/reportes/geografico/previa', cuerpo)).data;
    },
    geograficoExportar(cuerpo) {
        return descargar('/reportes/geografico/exportar', cuerpo);
    },
    async dpiPrevia(archivo, columnas) {
        return (await api.post('/reportes/dpi/previa', formDpi(archivo, columnas))).data;
    },
    dpiExportar(archivo, columnas) {
        return descargar('/reportes/dpi/exportar', formDpi(archivo, columnas));
    },
    async bitacora(params) {
        return (await api.get('/reportes/bitacora', { params })).data;
    }
};
