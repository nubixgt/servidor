import { defineStore } from 'pinia';
import { CLAVE_SESION, hooks, leerSesion } from '../services/api';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        usuario: leerSesion()?.usuario ?? null,
    }),
    getters: {
        autenticado: (s) => !!s.usuario,
        esAdmin: (s) => s.usuario?.rol === 'admin',
        etiquetaRol: (s) => (s.usuario?.rol === 'admin' ? 'Administrador' : 'Técnico'),
    },
    actions: {
        async login(usuario, clave) {
            const datos = await authService.login(usuario, clave);
            sessionStorage.setItem(CLAVE_SESION, JSON.stringify(datos));
            this.usuario = datos.usuario;
        },
        /** Confirma con el servidor que la sesión guardada sigue vigente. */
        async verificar() {
            if (!leerSesion()) {
                this.limpiar();
                return false;
            }
            try {
                this.usuario = await authService.me();
                return true;
            } catch {
                this.limpiar();
                return false;
            }
        },
        limpiar() {
            sessionStorage.removeItem(CLAVE_SESION);
            this.usuario = null;
        },
    },
});

/** Cuando la API responde 401, se cierra la sesión y se vuelve al login. */
export const registrarCierreAutomatico = (router) => {
    hooks.alExpirar = () => {
        useAuthStore().limpiar();
        if (router.currentRoute.value.name !== 'Login') {
            router.push({ name: 'Login', query: { expirada: '1' } });
        }
    };
};
