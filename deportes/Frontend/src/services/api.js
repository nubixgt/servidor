import axios from 'axios'

// Desarrollo: Vite en :5173 y el backend PHP en :8000 (`php -S localhost:8000 -t Backend`).
// Producción: mismo dominio. Se puede sobrescribir con VITE_API_URL / VITE_IMAGE_URL.
const isDevelopment = import.meta.env.MODE === 'development'

const API_URL = import.meta.env.VITE_API_URL || (isDevelopment ? 'http://localhost:8000/api/index.php' : 'https://m.nubix.gt/deportes/Backend/api')

export const IMAGE_BASE_URL = import.meta.env.VITE_IMAGE_URL || (isDevelopment ? 'http://localhost:8000/' : 'https://m.nubix.gt/deportes/Backend/')

// Rutas guardadas en la BD ("uploads/equipos/1/escudo.png") -> URL completa. Otras rutas (/pulse/…, blob:, http…) pasan igual.
export const imgUrl = (ruta) => (!ruta ? null : /^(https?:|\/|data:|blob:)/.test(ruta) ? ruta : IMAGE_BASE_URL + ruta)

const api = axios.create({ baseURL: API_URL })

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('deportes_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Sesión vencida o inválida: avisa a la app (main.js cierra sesión y vuelve al login).
api.interceptors.response.use(
  (respuesta) => respuesta,
  (error) => {
    if (error.response?.status === 401 && !error.config?.url?.endsWith('/login')) {
      window.dispatchEvent(new Event('sesion-expirada'))
    }
    return Promise.reject(error)
  }
)

export default api
