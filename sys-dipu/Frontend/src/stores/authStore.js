import { defineStore } from 'pinia';
import { ref } from 'vue';
import { getApiBaseUrl } from '../services/api';

// Role and Category types
// Role: 'administrador' | 'tecnico'
// Category: one of the 8 categories

export const useAuthStore = defineStore('auth', () => {
    const storedToken = localStorage.getItem('auth_token');
    let initialUser = null, initialRole = null, initialCategory = null;

    if (storedToken) {
        try {
            // Decodificar el payload del token JWT
            const base64Url = storedToken.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(window.atob(base64).split('').map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)).join(''));
            const payload = JSON.parse(jsonPayload);

            // Comprobar si no ha expirado
            if (payload.exp * 1000 > Date.now()) {
                initialUser = { id: payload.id, nombre: payload.nombre, rol: payload.rol, categoria: payload.categoria };
                initialRole = payload.rol;
                initialCategory = payload.categoria;
            } else {
                localStorage.removeItem('auth_token');
            }
        } catch (e) {
            localStorage.removeItem('auth_token');
        }
    }

    const role = ref(initialRole);
    const assignedCategory = ref(initialCategory);
    const currentView = ref('dashboard');
    const token = ref(storedToken && initialRole ? storedToken : null);
    const user = ref(initialUser);

    async function login(username, password) {
        try {
            const API_URL = getApiBaseUrl();



            const response = await fetch(`${API_URL}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ usuario: username, password })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error al iniciar sesión');
            }

            token.value = data.token;
            user.value = data.user;
            role.value = data.user.rol;
            assignedCategory.value = data.user.categoria;
            currentView.value = 'dashboard';

            // Opcional: Guardar en localStorage para mantener sesión
            localStorage.setItem('auth_token', data.token);

            return data.user;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }

    // Método rápido para pruebas sin backend si es que no está configurado (Fallback)
    function quickLogin(selectedRole, category = null) {
        role.value = selectedRole;
        assignedCategory.value = category;
        currentView.value = 'dashboard';
    }

    function logout() {
        role.value = null;
        assignedCategory.value = null;
        currentView.value = 'dashboard';
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
    }

    function setView(view) {
        currentView.value = view;
    }

    return { role, assignedCategory, currentView, token, user, login, quickLogin, logout, setView };
});
