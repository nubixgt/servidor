import api, { renovar } from './api';

export default {
    async login(usuario, clave) {
        const { data } = await api.post('/auth/login', { usuario, clave });
        return data;
    },
    /** Restaura la sesión con la cookie de renovación; null si no hay sesión. */
    async reanudar() {
        try { return await renovar(); } catch (e) { return null; }
    },
    async logout() {
        try { await api.post('/auth/logout'); } catch (e) { /* la sesión local se cierra igual */ }
    },
    async yo() {
        return (await api.get('/auth/yo')).data;
    },
    async cambiarClave(actual, nueva) {
        return (await api.post('/auth/cambiar-clave', { actual, nueva })).data;
    }
};
