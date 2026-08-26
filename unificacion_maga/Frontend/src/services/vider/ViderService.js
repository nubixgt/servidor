import api from '../api';

const ViderService = {
    getDashboardStats(filters = {}) {
        return api.get('/vider/dashboard', { 
            params: { ...filters, action: 'stats' } 
        }).then(res => res.data);
    },

    getMapData(filters = {}) {
        return api.get('/vider/dashboard', { 
            params: { ...filters, action: 'map' } 
        }).then(res => res.data);
    },

    getCatalogos(dependenciaId = null) {
        return api.get('/vider/dashboard', { 
            params: { dependencia_id: dependenciaId, action: 'advanced_catalogos' } 
        }).then(res => res.data);
    },

    getRecords(filters = {}) {
        return api.get('/vider/dashboard', { 
            params: { ...filters, action: 'records' } 
        }).then(res => res.data);
    },

    getTobanikSummary(filters = {}) {
        return api.get('/vider/dashboard', { 
            params: { ...filters, action: 'tobanik' } 
        }).then(res => res.data);
    },

    // Ejecución CRUD
    createEjecucion(data) {
        return api.post('/vider/ejecucion', data).then(res => res.data);
    },

    updateEjecucion(id, data) {
        return api.put(`/vider/ejecucion/${id}`, data).then(res => res.data);
    },

    deleteEjecucion(id) {
        return api.delete(`/vider/ejecucion/${id}`).then(res => res.data);
    },

    // Tobanik CRUD
    createTobanik(data) {
        return api.post('/vider/tobanik', data).then(res => res.data);
    },

    updateTobanik(id, data) {
        return api.put(`/vider/tobanik/${id}`, data).then(res => res.data);
    },

    deleteTobanik(id) {
        return api.delete(`/vider/tobanik/${id}`).then(res => res.data);
    }
};

export default ViderService;
