import axios from 'axios';

const api = axios.create({
  // Ruta absoluta recomendada para evitar problemas con las rutas relativas
  baseURL: import.meta.env.DEV 
    ? 'http://localhost/servidor/InteligenciaTerritorial/Backend/api.php' 
    : '/InteligenciaTerritorial/Backend/api.php'
});

export default api;
