import api from './api';

const limpiar = (params) => Object.fromEntries(Object.entries(params || {}).filter(([, v]) => v !== null && v !== undefined && v !== ''));

export default {
    async listar(params) {
        return (await api.get('/expedientes', { params: limpiar(params) })).data;
    },
    async detalle(id) {
        return (await api.get(`/expedientes/${id}`)).data;
    },
    async dpi(id) {
        return (await api.get(`/expedientes/${id}/dpi`)).data;
    },
    async crear(datos) {
        return (await api.post('/expedientes', datos)).data;
    },
    async registrar(id, ruta, cuerpo) {
        return (await api.post(`/expedientes/${id}${ruta}`, cuerpo)).data;
    },
    async actualizarContacto(id, datos) {
        return (await api.patch(`/expedientes/${id}/contacto`, datos)).data;
    }
};
