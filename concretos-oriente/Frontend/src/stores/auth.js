import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    // Inicializar leyendo del localStorage si existe
    const userRole = ref(localStorage.getItem('userRole') || null);
    const userName = ref(localStorage.getItem('userName') || null);

    const login = (role, username) => {
        userRole.value = role;
        userName.value = username;
        localStorage.setItem('userRole', role);
        localStorage.setItem('userName', username);
    };

    const logout = () => {
        userRole.value = null;
        userName.value = null;
        localStorage.removeItem('userRole');
        localStorage.removeItem('userName');
    };

    return { userRole, userName, login, logout };
});
