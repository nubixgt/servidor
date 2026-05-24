import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    // Inicializar leyendo del localStorage si existe
    const userRole = ref(localStorage.getItem('userRole') || null);

    const login = (role) => {
        userRole.value = role;
        localStorage.setItem('userRole', role);
    };

    const logout = () => {
        userRole.value = null;
        localStorage.removeItem('userRole');
    };

    return { userRole, login, logout };
});
