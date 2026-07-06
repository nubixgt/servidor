import axios from 'axios';

// Si no hay variable de entorno (como en el GitHub Action), asume la ruta de Hostinger
const API_URL = import.meta.env.VITE_API_BASE || '/Backend/api/v1';

export const notificationService = {
    async getUnread() {
        try {
            // Asumiendo que el token se guarda en localStorage como en el resto de la app
            const token = localStorage.getItem('token') || '';
            const response = await axios.get(`${API_URL}/notifications`, {
                headers: { Authorization: `Bearer ${token}` }
            });
            return response.data.data || [];
        } catch (error) {
            console.error('Error fetching notifications:', error);
            return [];
        }
    },

    async markAllAsRead() {
        try {
            const token = localStorage.getItem('token') || '';
            const response = await axios.put(`${API_URL}/notifications/read-all`, {}, {
                headers: { Authorization: `Bearer ${token}` }
            });
            return response.data;
        } catch (error) {
            console.error('Error marking notifications as read:', error);
            throw error;
        }
    }
};
