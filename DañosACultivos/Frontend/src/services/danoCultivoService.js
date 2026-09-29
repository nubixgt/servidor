import api from './api';

export default {
    async list(params = {}) {
        const { data } = await api.get('/danos-cultivos', { params });
        return data.danos;
    },
    /**
     * Crea el registro y, si hay archivos, los sube después (multipart) al registro creado.
     * Si la subida de fotos falla, el registro ya quedó guardado: se devuelve con `fotosError`.
     */
    async create(registro, archivos = []) {
        const { data } = await api.post('/danos-cultivos', registro);
        const dano = data.dano;
        if (archivos.length) {
            const fd = new FormData();
            archivos.forEach((f) => fd.append('fotos[]', f));
            try {
                // 'Content-Type': undefined deja que axios genere el boundary multipart.
                const res = await api.post(`/danos-cultivos/${dano.id}/fotos`, fd, { headers: { 'Content-Type': undefined } });
                dano.fotos = res.data.fotos;
            } catch (e) {
                dano.fotosError = e.message;
            }
        }
        return dano;
    },
    async remove(id) {
        await api.delete(`/danos-cultivos/${id}`);
    },
};
