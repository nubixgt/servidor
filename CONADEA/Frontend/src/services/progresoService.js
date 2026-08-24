import api from './api';

// Backend/src/Controllers/ProgresoController.php
export default {
    obtenerTodo() {
        return api.get('/progreso');
    },
    // completada/segundosVideo son opcionales: solo se manda lo que cambió,
    // sin pisar el otro campo en el servidor.
    guardarLeccion(leccionId, { completada, segundosVideo } = {}) {
        const body = {};
        if (completada !== undefined) body.completada = completada;
        if (segundosVideo !== undefined) body.segundos_video = segundosVideo;
        return api.put(`/lecciones/${leccionId}/progreso`, body);
    },
    guardarEvaluacion(cursoId, nota, total) {
        return api.put(`/cursos/${cursoId}/evaluacion`, { nota, total });
    }
};
