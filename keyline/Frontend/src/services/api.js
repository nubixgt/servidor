import axios from 'axios';

const API_BASE_URL = 'https://m.nubix.gt/keyline/Backend/api/v1';

const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Backend/uploads vive junto a Backend/api/v1, no bajo /api/v1.
// Cada apartado que suba archivos usa: uploads/{apartado}/{id}/... (ver parcelaFotoUrl).
export const uploadsBaseUrl = API_BASE_URL.replace(/\/api\/v1\/?$/, '/uploads');

/** URL pública de una foto de parcela: uploads/parcelas/{parcelaId}/fotos/{filename} */
export function parcelaFotoUrl(parcelaId, filename) {
    return `${uploadsBaseUrl}/parcelas/${parcelaId}/fotos/${filename}`;
}

// Request interceptor for API calls
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Normaliza los errores del backend ({ error: "mensaje" }) a Error.message,
// para poder mostrar err.message directamente en la UI.
api.interceptors.response.use(
    response => response,
    error => {
        const message = error.response?.data?.error || error.message || 'Ocurrió un error inesperado.';
        return Promise.reject(new Error(message));
    }
);

export default api;
