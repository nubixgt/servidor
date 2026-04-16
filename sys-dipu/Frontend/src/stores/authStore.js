import { defineStore } from 'pinia';
import { ref } from 'vue';

// Role and Category types
// Role: 'administrador' | 'tecnico'
// Category: one of the 8 categories

export const useAuthStore = defineStore('auth', () => {
    const role = ref(null); // null | 'administrador' | 'tecnico'
    const assignedCategory = ref(null); // null | Category string
    const currentView = ref('dashboard');
    const token = ref(null);
    const user = ref(null);

    async function login(username, password) {
        try {
            // Utilizamos ruta absoluta basada en el dominio actual para que funcione en produccón (m.nubix.gt) o local
            const API_URL = import.meta.env.VITE_API_URL || '/sys-dipu/Backend/api/v1';
            
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
