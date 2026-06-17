import { defineStore } from 'pinia';
import { ref } from 'vue';

const API_URL = '/concretos-oriente/Backend/api/v1';

export const useAuthStore = defineStore('auth', () => {
    // Inicializar leyendo del localStorage si existe
    const userRole = ref(localStorage.getItem('userRole') || null);
    const userName = ref(localStorage.getItem('userName') || null);
    const token = ref(localStorage.getItem('token') || null);
    const userPermisos = ref(JSON.parse(localStorage.getItem('userPermisos') || '[]'));
    const userProyectos = ref(JSON.parse(localStorage.getItem('userProyectos') || '[]'));

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

            let permisosArray = [];
            if (data.user.permisos) {
                // Si ya es objeto/array, úsalo, sino haz parse
                permisosArray = typeof data.user.permisos === 'string' ? JSON.parse(data.user.permisos) : data.user.permisos;
            }

            let proyectosArray = [];
            if (data.user.proyectos) {
                proyectosArray = typeof data.user.proyectos === 'string' ? JSON.parse(data.user.proyectos) : data.user.proyectos;
            }

            // Éxito
            userRole.value = data.user.rol;
            userName.value = data.user.nombre;
            token.value = data.token;
            userPermisos.value = permisosArray;
            userProyectos.value = proyectosArray;

            localStorage.setItem('userRole', data.user.rol);
            localStorage.setItem('userName', data.user.nombre);
            localStorage.setItem('token', data.token);
            localStorage.setItem('userPermisos', JSON.stringify(permisosArray));
            localStorage.setItem('userProyectos', JSON.stringify(proyectosArray));

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
        userPermisos.value = [];
        userProyectos.value = [];
        
        localStorage.removeItem('userRole');
        localStorage.removeItem('userName');
        localStorage.removeItem('token');
        localStorage.removeItem('userPermisos');
        localStorage.removeItem('userProyectos');
    };

    return { userRole, userName, token, userPermisos, userProyectos, login, logout };
});
