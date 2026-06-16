import api from './api';

export const reportService = {
    getDashboardData() {
        return api.get('/reports/dashboard');
    },
    getClientReport(id) {
        return api.get(`/reports/cliente/${id}`);
    }
};
