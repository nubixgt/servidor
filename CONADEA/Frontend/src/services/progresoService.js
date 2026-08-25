import api from './api';

// Backend/src/Controllers/ProgresoController.php
export default {
    obtenerTodo() {
        return api.get('/progreso');
    },
    // completada/segundosVideo/nota/total son opcionales: solo se manda lo que cambió.
    guardarLeccion(leccionId, { completada, segundosVideo, nota, total } = {}) {
        const body = {};
        if (completada !== undefined) body.completada = completada;
        if (segundosVideo !== undefined) body.segundos_video = segundosVideo;
        if (nota !== undefined) body.nota = nota;
        if (total !== undefined) body.total = total;
        return api.put(`/lecciones/${leccionId}/progreso`, body);
    }
};
