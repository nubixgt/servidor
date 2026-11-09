import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import authService from '../services/authService';

// Claves con prefijo: otros proyectos de m.nubix.gt comparten el mismo localStorage.
export const TOKEN_KEY = 'sanafer_token';
const USER_KEY = 'sanafer_user';

const read = (key) => {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
};
const readUser = () => {
    try {
        return JSON.parse(read(USER_KEY));
    } catch {
        return null;
    }
};

export const ROLE_LABELS = { admin: 'Administrador', vendedor: 'Vendedor' };

export const useAuthStore = defineStore('auth', () => {
    const token = ref(read(TOKEN_KEY));
    const user = ref(readUser());

    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const isAdmin = computed(() => user.value?.role === 'admin');
    const roleLabel = computed(() => ROLE_LABELS[user.value?.role] || '');

    function clear() {
        token.value = null;
        user.value = null;
        try {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
        } catch {
            /* sin almacenamiento */
        }
    }

    // Devuelve { ok } o { ok: false, message }
    async function login(username, password) {
        try {
            const { data } = await authService.login(username, password);
            token.value = data.token;
            user.value = data.user;
            try {
                localStorage.setItem(TOKEN_KEY, data.token);
                localStorage.setItem(USER_KEY, JSON.stringify(data.user));
            } catch {
                /* la sesión dura hasta recargar */
            }
            return { ok: true };
        } catch (err) {
            if (err.response) return { ok: false, message: err.response.data?.error || 'No se pudo iniciar sesión.' };
            return { ok: false, message: 'No se pudo conectar. Revisa la conexión e intenta de nuevo.' };
        }
    }

    return { token, user, isAuthenticated, isAdmin, roleLabel, login, logout: clear };
});
