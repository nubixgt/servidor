import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';

export const useAuthStore = defineStore('auth', () => {
    const storedToken = localStorage.getItem('auth_token');
    let initialUser = null, initialRole = null, initialInspectorId = null;

    if (storedToken) {
        try {
            // Decodificar el payload del token JWT
            const base64Url = storedToken.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(window.atob(base64).split('').map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)).join(''));
            const payload = JSON.parse(jsonPayload);

            // Comprobar si no ha expirado
            if (payload.exp * 1000 > Date.now()) {
                initialUser = { id: payload.id, nombre: payload.nombre, rol: payload.rol, inspector_id: payload.inspector_id };
                initialRole = payload.rol;
                initialInspectorId = payload.inspector_id;
            } else {
                localStorage.removeItem('auth_token');
            }
        } catch (e) {
            localStorage.removeItem('auth_token');
        }
    }

    const role = ref(initialRole);
    const inspectorId = ref(initialInspectorId);
    const token = ref(storedToken && initialRole ? storedToken : null);
    const user = ref(initialUser);

    async function login(username, password) {
        try {
            const response = await api.post('/auth/login', { usuario: username, password });
            const data = response.data;

            token.value = data.token;
            user.value = data.user;
            role.value = data.user.rol;
            inspectorId.value = data.user.inspector_id;

            // Guardar en localStorage para mantener sesión
            localStorage.setItem('auth_token', data.token);

            return data.user;
        } catch (error) {
            console.error('Login error:', error);
            const errMsg = error.response?.data?.error || 'Error al iniciar sesión';
            throw new Error(errMsg);
        }
    }

    function logout() {
        role.value = null;
        inspectorId.value = null;
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
    }

    return { role, inspectorId, token, user, login, logout };
});
