import { defineStore } from 'pinia';
import { ref } from 'vue';

const API_URL = '/concretos-oriente/Backend/api/v1';

export const useAuthStore = defineStore('auth', () => {
    // Inicializar leyendo del localStorage si existe
    const userRole = ref(localStorage.getItem('userRole') || null);
    const userName = ref(localStorage.getItem('userName') || null);
    const token = ref(localStorage.getItem('token') || null);

    const login = async (username, password) => {
        try {
            const response = await fetch(`${API_URL}/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ usuario: username, password: password })
            });

            const data = await response.json();

            if (!response.ok || data.status === 'error') {
                throw new Error(data.message || 'Error de autenticación');
            }

            // Éxito
            userRole.value = data.user.rol;
            userName.value = data.user.nombre;
            token.value = data.token;

            localStorage.setItem('userRole', data.user.rol);
            localStorage.setItem('userName', data.user.nombre);
            localStorage.setItem('token', data.token);

            return data;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    };

    const logout = () => {
        userRole.value = null;
        userName.value = null;
        token.value = null;
        
        localStorage.removeItem('userRole');
        localStorage.removeItem('userName');
        localStorage.removeItem('token');
    };

    return { userRole, userName, token, login, logout };
});
