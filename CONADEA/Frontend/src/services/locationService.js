import api from './api';

// Backend/src/Controllers/LocationController.php — catálogo departamento -> municipio
// usado por el selector en cascada del registro.
export default {
    listarDepartamentos() {
        return api.get('/ubicacion/departamentos');
    },
    listarMunicipios(departamentoId) {
        return api.get(`/ubicacion/municipios/${departamentoId}`);
    }
};
