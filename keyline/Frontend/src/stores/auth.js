import { defineStore } from 'pinia';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('token') || null,
        checked: false, // ya se intentó restaurar la sesión con /auth/me
    }),

    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
        role: (state) => state.user?.role || null,
        homeRoute(state) {
            if (state.user?.role === 'tecnico') return { name: 'MisParcelas' };
            if (state.user?.role === 'supervisor' || state.user?.role === 'administrador') return { name: 'Dashboard' };
            return { name: 'Login' };
        },
    },

    actions: {
        async login(usuario, password) {
            const { data } = await authService.login(usuario, password);
            this.setSession(data.user, data.token);
            return data.user;
        },

        setSession(user, token) {
            this.user = user;
            this.token = token;
            this.checked = true;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
        },

        async ensureLoaded() {
            if (this.checked) return this.isAuthenticated;
            if (!this.token) {
                this.checked = true;
                return false;
            }
            try {
                const { data } = await authService.me();
                this.user = data.user;
                localStorage.setItem('user', JSON.stringify(data.user));
                this.checked = true;
                return true;
            } catch (e) {
                this.logout();
                this.checked = true;
                return false;
            }
        },

        logout() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        },
    },
});
