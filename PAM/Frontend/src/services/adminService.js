import api from './api';

const limpiar = (params) => Object.fromEntries(Object.entries(params || {}).filter(([, v]) => v !== null && v !== undefined && v !== ''));

export default {
    async usuarios() {
        return (await api.get('/admin/usuarios')).data;
    },
    async crearUsuario(datos) {
        return (await api.post('/admin/usuarios', datos)).data;
    },
    async editarUsuario(id, datos) {
        return (await api.patch(`/admin/usuarios/${id}`, datos)).data;
    },
    async restablecerClave(id) {
        return (await api.post(`/admin/usuarios/${id}/restablecer-clave`)).data;
    },
    async roles() {
        return (await api.get('/admin/roles')).data;
    },
    async crearRol(datos) {
        return (await api.post('/admin/roles', datos)).data;
    },
    async asignarPermisos(id, permisos) {
        return (await api.put(`/admin/roles/${id}/permisos`, permisos)).data;
    },
    async bitacora(params) {
        return (await api.get('/admin/bitacora', { params: limpiar(params) })).data;
    },
    async parametros() {
        return (await api.get('/admin/parametros')).data;
    },
    async cambiarParametro(clave, valor) {
        // el valor puede ser un número, texto u objeto: se envía siempre como JSON
        return (await api.put(`/admin/parametros/${encodeURIComponent(clave)}`, JSON.stringify(valor), { headers: { 'Content-Type': 'application/json' } })).data;
    },
    async guardarTipoResolucion(datos) {
        return (await api.put('/admin/tipos-resolucion', datos)).data;
    }
};
