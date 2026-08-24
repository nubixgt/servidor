import api from './api';

// Backend/src/Controllers/ProgresoController.php
export default {
    obtenerTodo() {
        return api.get('/progreso');
    }
};
