import axios from 'axios';

const api = axios.create({
  // En producción, apuntará a la misma ruta
  baseURL: import.meta.env.DEV ? 'http://localhost/servidor/InteligenciaTerritorial/Backend/api.php' : '../Backend/api.php'
});

export default api;
