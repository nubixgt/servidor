import axios from 'axios';

// Configuración de la URL base
// En local Vite corre en 5173 y el backend PHP (ej. `php -S localhost:8000`) en 8000.
// En producción, ambos comparten el mismo origen (`m.nubix.gt`).
const isDevelopment = import.meta.env.MODE === 'development';

const api = axios.create({
  baseURL: isDevelopment 
    ? 'http://localhost:8000/api/index.php' 
    : 'https://m.nubix.gt/deportes/Backend/api',
  headers: {
    'Content-Type': 'application/json'
  }
});

// Interceptor para agregar headers o manejar errores genéricos (opcional)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export const IMAGE_BASE_URL = isDevelopment 
  ? 'http://localhost:8000/deportes/Backend/' 
  : 'https://m.nubix.gt/deportes/Backend/';

export default api;
