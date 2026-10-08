import api from './api';

// Backend/src/Controllers/HorarioController.php — horarios de estudio del
// usuario autenticado (misma tabla que usa el bot de WhatsApp).
export default {
    listar() {
        return api.get('/horarios');
    },
    // dias: "L,M,X,J,V,S,D" · hora: "HH:MM" (24 h)
    guardar({ cursoId, dias, hora, duracionMinutos }) {
        return api.post('/horarios', {
            curso_id: cursoId,
            dias,
            hora,
            duracion_minutos: duracionMinutos
        });
    },
    actualizarActivo(cursoId, activo) {
        return api.post('/horarios/activo', { curso_id: cursoId, activo });
    }
};
