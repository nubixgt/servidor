import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../services/api';

const TOKEN_KEY = 'token';
const USER_KEY = 'user';

function readUser() {
    try {
        return JSON.parse(localStorage.getItem(USER_KEY) || 'null');
    } catch {
        return null;
    }
}

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem(TOKEN_KEY) || null);
    const user = ref(readUser());

    const isAuthenticated = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.rol === 'admin' || user.value?.role === 'admin');

    function setSession(newToken, newUser) {
        token.value = newToken;
        user.value = newUser;
        localStorage.setItem(TOKEN_KEY, newToken);
        localStorage.setItem(USER_KEY, JSON.stringify(newUser));
    }

    function logout() {
        token.value = null;
        user.value = null;
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    }

    async function login({ usuario, password }) {
        const { data } = await api.post('/auth/login', { usuario, password });
        setSession(data.data.token, data.data.user);
        return data.data.user;
    }

    async function fetchMe() {
        if (!token.value) return null;
        try {
            const { data } = await api.get('/auth/me');
            user.value = data.data;
            localStorage.setItem(USER_KEY, JSON.stringify(data.data));
            return data.data;
        } catch {
            logout();
            return null;
        }
    }

    return { token, user, isAuthenticated, isAdmin, login, logout, fetchMe };
});
