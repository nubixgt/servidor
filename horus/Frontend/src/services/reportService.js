import api from './api';

export const reportService = {
    getDashboardData() {
        return api.get('/reports/dashboard');
    }
};
