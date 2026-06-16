import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL;

export const reportService = {
    getDashboardData() {
        const token = localStorage.getItem('token');
        return axios.get(`${API_URL}/reports/dashboard`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }
};
