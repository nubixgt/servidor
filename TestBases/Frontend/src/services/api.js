import axios from 'axios';

const api = axios.create({
    // Ajustar en Frontend/.env con VITE_API_URL (ver .env.example)
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost/TestBases/Backend/api/v1',
    headers: {
        'Accept': 'application/json'
    }
});

export const CLAVE_SESION = 'tb_sesion';

/** Sesión guardada en el navegador: { token, expira, usuario } o null si no hay o ya venció. */
export const leerSesion = () => {
    try {
        const s = JSON.parse(sessionStorage.getItem(CLAVE_SESION));
        return s && s.token && s.expira * 1000 > Date.now() ? s : null;
    } catch {
        return null;
    }
};

/** Gancho que registra el store de autenticación para cerrar sesión ante un 401. */
export const hooks = { alExpirar: null };

api.interceptors.request.use(config => {
    const sesion = leerSesion();
    if (sesion) {
        config.headers['Authorization'] = `Bearer ${sesion.token}`;
    }
    return config;
});

api.interceptors.response.use(
    r => r,
    error => {
        const url = error.config?.url || '';
        if (error.response?.status === 401 && !url.includes('/auth/login')) {
            hooks.alExpirar?.();
        }
        return Promise.reject(error);
    }
);

/** Mensaje legible de un error de axios, usando el que envía el backend cuando existe. */
export const mensajeDeError = (error, porDefecto = 'Ocurrió un error inesperado') => {
    if (error.response?.data?.message) return error.response.data.message;
    if (error.code === 'ERR_NETWORK') return 'No hay conexión con el servidor';
    return porDefecto;
};

export default api;
