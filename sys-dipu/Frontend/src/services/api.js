import axios from 'axios';

export const getApiBaseUrl = () => {
    // 🚀 Detección dinámica y ultra robusta para evitar conflictos de puertos en XAMPP y local dev
    const isLocal = window.location.hostname === 'localhost' || 
                    window.location.hostname === '127.0.0.1' || 
                    window.location.hostname.startsWith('192.168.') || 
                    window.location.hostname.startsWith('10.') || 
                    window.location.hostname.endsWith('.local');

    if (!isLocal) {
        // En producción remota, usamos la ruta de producción configurada o la relativa estándar
        return 'https://m.nubix.gt/sys-dipu/Backend/api/v1';
    }

    // En local:
    // Si estamos en un servidor de desarrollo de Vite (ej. puerto 5173 u otro diferente a Apache 80/8080)
    // y el backend corre en el PHP dev server (8080)
    const hasViteDevServer = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';
    
    if (hasViteDevServer) {
        const envUrl = import.meta.env.VITE_API_URL;
        if (envUrl) {
            return envUrl.endsWith('/api/v1') ? envUrl : `${envUrl}/api/v1`;
        }
        return 'http://localhost:8080/api/v1';
    } else {
        // XAMPP encendido: Frontend y Backend se sirven bajo Apache local (puerto 80, 8080, etc.)
        // La ruta del backend es relativa: /sys-dipu/Backend/api/v1
        const path = window.location.pathname;
        const sysDipuIndex = path.indexOf('/sys-dipu/');
        const basePath = sysDipuIndex !== -1 ? path.substring(0, sysDipuIndex) : '';
        const port = window.location.port ? `:${window.location.port}` : '';
        return `${window.location.protocol}//${window.location.hostname}${port}${basePath}/sys-dipu/Backend/api/v1`;
    }
};

export const getBackendBaseUrl = () => {
    return getApiBaseUrl().replace('/api/v1', '');
};

const api = axios.create({
    baseURL: getApiBaseUrl(),
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor for API calls
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.set('Authorization', `Bearer ${token}`);
        }
        
        // 🚀 Si enviamos FormData (subidas de archivos/fotos), eliminamos la cabecera 
        // Content-Type por defecto para que el navegador genere la cabecera multipart
        // correcta con su boundary único e indispensable.
        if (config.data instanceof FormData) {
            config.headers.delete('Content-Type');
        }
        
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

export default api;
