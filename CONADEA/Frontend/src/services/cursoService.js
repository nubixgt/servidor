import api from './api';

// Backend/src/Controllers/CursoController.php — crear() es exclusivo del
// rol Administrador (ver Authorize(['Administrador']) en el backend).
export default {
    crear(datos, imagenFile, videosLecciones = {}) {
        const formData = new FormData();
        formData.append('data', JSON.stringify(datos));
        formData.append('imagen', imagenFile);
        Object.entries(videosLecciones).forEach(([indice, archivo]) => {
            formData.append(`leccion_video_${indice}`, archivo);
        });

        // Deja que el navegador ponga el Content-Type con el boundary del
        // multipart — el header por defecto de la instancia (application/json)
        // rompería el envío de archivos si no se anula aquí.
        return api.post('/cursos', formData, {
            headers: { 'Content-Type': undefined }
        });
    },
    listar() {
        return api.get('/cursos');
    },
    obtener(id) {
        return api.get(`/cursos/${id}`);
    }
};
