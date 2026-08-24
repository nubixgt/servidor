import api from './api';

// Backend/src/Controllers/AuthController.php
export default {
    login(usuario, password) {
        return api.post('/auth/login', { usuario, password });
    },
    register({ nombreCompleto, usuario, password, telefono, departamentoId, municipioId }) {
        return api.post('/auth/register', {
            nombre_completo: nombreCompleto,
            usuario,
            password,
            telefono,
            departamento_id: departamentoId,
            municipio_id: municipioId
        });
    }
};
