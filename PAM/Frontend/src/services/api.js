import axios from 'axios';

const api = axios.create({
    // Ajustar en Frontend/.env con VITE_API_URL (ver .env.example)
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost/IEMCOOP/template/Backend/api/v1',
    withCredentials: true, // el refresh token viaja en una cookie httpOnly
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'fetch'
    }
});

/** Error de la API con el código HTTP y el mensaje que envía el backend. */
export class ErrorApi extends Error {
    constructor(status, message) {
        super(message);
        this.status = status;
    }
}

// El access token vive solo en memoria; la renovación usa la cookie
let token = null;
let renovando = null;

export const setToken = (t) => { token = t; };
export const getToken = () => token;

/** Ganchos que registra el store de autenticación. */
export const hooks = { alExpirar: null, alRenovar: null };

// Request interceptor for API calls
api.interceptors.request.use(
    config => {
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

async function mensaje(error) {
    let data = error.response?.data;
    if (data instanceof Blob) {
        try { data = JSON.parse(await data.text()); } catch (e) { data = null; }
    }
    if (data && typeof data.message === 'string') return data.message;
    const status = error.response?.status;
    if (!error.response) return 'No se pudo conectar con el servidor';
    return status === 403 ? 'No tiene permiso para esta acción'
        : status >= 500 ? 'Error del servidor. Intente de nuevo o contacte a soporte.'
            : 'Solicitud no válida';
}

/** Pide un nuevo access token con la cookie de renovación (una sola petición a la vez). */
export function renovar() {
    if (!renovando) {
        renovando = axios.post(api.defaults.baseURL + '/auth/refresh', null, { withCredentials: true })
            .then(r => {
                token = r.data.access_token;
                hooks.alRenovar && hooks.alRenovar(r.data.usuario);
                return r.data;
            })
            .finally(() => { setTimeout(() => { renovando = null; }, 0); });
    }
    return renovando;
}

api.interceptors.response.use(
    response => response,
    async error => {
        const { config, response } = error;
        if (response?.status === 401 && config && !config._reintento && !config.url.startsWith('/auth/')) {
            config._reintento = true;
            try {
                await renovar();
                return api(config);
            } catch (e) {
                hooks.alExpirar && hooks.alExpirar();
                throw new ErrorApi(401, 'La sesión expiró. Ingrese de nuevo.');
            }
        }
        throw new ErrorApi(response?.status ?? 0, await mensaje(error));
    }
);

/** Descarga un archivo (CSV) respetando el nombre que envía el servidor. */
export async function descargar(ruta, cuerpo, nombre) {
    const r = await api.request({ url: ruta, method: cuerpo ? 'POST' : 'GET', data: cuerpo, responseType: 'blob' });
    const cd = r.headers['content-disposition'] || '';
    const m = cd.match(/filename="?([^"]+)"?/);
    const a = document.createElement('a');
    a.href = URL.createObjectURL(r.data);
    a.download = (m && m[1]) || nombre || 'descarga';
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
}

export default api;
