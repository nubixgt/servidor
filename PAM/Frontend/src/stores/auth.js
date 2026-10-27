import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import authService from '../services/authService';
import { hooks, setToken } from '../services/api';

export const useAuthStore = defineStore('auth', () => {
    const usuario = ref(null);
    const intentada = ref(false); // ya se intentó restaurar la sesión con la cookie
    const mensajeLogin = ref('');

    const autenticado = computed(() => !!usuario.value);

    const puede = (permiso) => !!(usuario.value && usuario.value.permisos.includes(permiso));
    /** `req` puede ser un permiso o una lista (basta uno). */
    const puedeVer = (req) => (Array.isArray(req) ? req.some(puede) : puede(req));

    async function login(user, clave) {
        const d = await authService.login(user, clave);
        setToken(d.access_token);
        usuario.value = d.usuario;
        return d;
    }

    async function reanudar() {
        if (intentada.value) return !!usuario.value;
        intentada.value = true;
        const d = await authService.reanudar();
        if (d) usuario.value = d.usuario;
        return !!d;
    }

    async function cambiarClave(actual, nueva) {
        await authService.cambiarClave(actual, nueva);
        usuario.value = await authService.yo();
    }

    async function logout() {
        await authService.logout();
        setToken(null);
        usuario.value = null;
    }

    /** La renovación falló: se vuelve al login con un aviso. */
    function expirar() {
        setToken(null);
        usuario.value = null;
        mensajeLogin.value = 'Su sesión expiró. Ingrese de nuevo.';
    }

    hooks.alRenovar = (u) => { usuario.value = u; };

    return { usuario, intentada, mensajeLogin, autenticado, puede, puedeVer, login, reanudar, cambiarClave, logout, expirar };
});
