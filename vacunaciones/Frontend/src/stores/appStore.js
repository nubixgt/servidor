import { defineStore } from 'pinia';
import { SEED_RECORDS, INITIAL_REMINDERS, SERVICE_COSTS } from '../utils/data';
import api from '../services/api';

export const useAppStore = defineStore('app', {
  state: () => ({
    records: JSON.parse(localStorage.getItem('vaxpoultry_records')) || SEED_RECORDS,
    reminders: JSON.parse(localStorage.getItem('vaxpoultry_reminders')) || INITIAL_REMINDERS,
    adminName: localStorage.getItem('vaxpoultry_adminName') || 'Dr. Rodrigo M.',
    adminRole: localStorage.getItem('vaxpoultry_adminRole') || 'Administrador',
    avatarUrl: localStorage.getItem('vaxpoultry_avatarUrl') || 'https://lh3.googleusercontent.com/aida-public/AB6AXuBPWDjvnTaerXWIKJZGk-08L0ZkEpA_hXHCqf4K-1A-PYPlk7sgIcB374lQNK6GEAD3sLtjoUXdMiupNQ5PktuJMeQhBnlJ5U5qR1QiZ8aKmzLOVwB0XGj1fI6DywD1iDpOyEJ3UGGvUsQuWfe9-HsTLHi915dsj5mhpvYZHsWoS0LS_z1A0SFAGfRu9doaq2csh37D0ZUJrqgV0Xw40MS14iIpJOyteW0uA1uYycC0XhToX0SdvPP50xQMSrY2zYFUXe-YaGOgxZc',
    efficiencyRate: parseFloat(localStorage.getItem('vaxpoultry_efficiency')) || 98.4,
    vaccineRates: JSON.parse(localStorage.getItem('vaxpoultry_rates')) || SERVICE_COSTS
  }),
  actions: {
    syncLocalStorage() {
      localStorage.setItem('vaxpoultry_records', JSON.stringify(this.records));
      localStorage.setItem('vaxpoultry_reminders', JSON.stringify(this.reminders));
      localStorage.setItem('vaxpoultry_adminName', this.adminName);
      localStorage.setItem('vaxpoultry_adminRole', this.adminRole);
      localStorage.setItem('vaxpoultry_avatarUrl', this.avatarUrl);
      localStorage.setItem('vaxpoultry_efficiency', this.efficiencyRate.toString());
      localStorage.setItem('vaxpoultry_rates', JSON.stringify(this.vaccineRates));
    },
    async handleAddRecord(recordData) {
      try {
        const response = await api.post('/registros-vacunacion', recordData);
        if (response.status === 201 || response.status === 200) {
          const words = recordData.cliente.trim().split(/\s+/);
      let initials = 'AV';
      if (words.length > 0) {
        if (words.length >= 2) {
          initials = (words[0][0] + words[1][0]).toUpperCase();
        } else {
          initials = words[0].slice(0, 2).toUpperCase();
        }
      }

      const now = new Date();
      let hoursStr = now.getHours();
      const ampm = hoursStr >= 12 ? 'PM' : 'AM';
      hoursStr = hoursStr % 12;
      hoursStr = hoursStr ? hoursStr : 12;
      const minutesStr = now.getMinutes().toString().padStart(2, '0');
      const hora = `${hoursStr.toString().padStart(2, '0')}:${minutesStr} ${ampm}`;

      const newRecord = {
        ...recordData,
        id: `rec_${Date.now()}`,
        hora,
        clienteIniciales: initials
      };

      this.records.unshift(newRecord);
      this.syncLocalStorage();
      return { success: true };
      }
      } catch (error) {
        console.error('Error adding record:', error);
        throw error;
      }
    },
    async fetchRecords() {
      try {
        const response = await api.get('/registros-vacunacion');
        if (response.data && response.data.status === 'success') {
          // Calculate initials and formatting
          this.records = response.data.data.map(rec => {
            const words = rec.cliente.trim().split(/\s+/);
            let initials = 'AV';
            if (words.length > 0) {
              if (words.length >= 2) {
                initials = (words[0][0] + words[1][0]).toUpperCase();
              } else {
                initials = words[0].slice(0, 2).toUpperCase();
              }
            }

            // Extract time from created_at if possible, or just mock time for now
            const timeDate = new Date(rec.created_at || rec.fecha);
            let hoursStr = timeDate.getHours();
            const ampm = hoursStr >= 12 ? 'PM' : 'AM';
            hoursStr = hoursStr % 12;
            hoursStr = hoursStr ? hoursStr : 12;
            const minutesStr = timeDate.getMinutes().toString().padStart(2, '0');
            const hora = `${hoursStr.toString().padStart(2, '0')}:${minutesStr} ${ampm}`;

            return {
              ...rec,
              id: rec.id, // ID is number from DB
              hora,
              clienteIniciales: initials,
              cantidad: Number(rec.cantidad),
              costoPorAve: Number(rec.costo_por_ave || rec.costoPorAve),
              total: Number(rec.total)
            };
          });
          this.syncLocalStorage();
        }
      } catch (error) {
        console.error('Error fetching records:', error);
      }
    },
    async handleDeleteRecord(id) {
      try {
        await api.delete(`/registros-vacunacion/${id}`);
        this.records = this.records.filter(r => r.id !== id);
        this.syncLocalStorage();
      } catch (error) {
        console.error('Error deleting record:', error);
        throw error;
      }
    },
    async handleUpdateStatus(id, newStatus) {
      try {
        await api.put(`/registros-vacunacion/${id}/estado`, { estado: newStatus });
        const idx = this.records.findIndex(r => r.id === id);
        if (idx !== -1) {
          this.records[idx].estado = newStatus;
          this.syncLocalStorage();
        }
      } catch (error) {
        console.error('Error updating status:', error);
        throw error;
      }
    },
    async handleUpdateRecord(id, updatedData) {
      try {
        await api.put(`/registros-vacunacion/${id}`, updatedData);
        // Refresh records to get accurate data
        await this.fetchRecords();
      } catch (error) {
        console.error('Error updating record:', error);
        throw error;
      }
    },
    handleAddReminder(titulo, descripcion, importancia) {
      const newRem = {
        id: `rem_${Date.now()}`,
        titulo,
        descripcion,
        importancia
      };
      this.reminders.unshift(newRem);
      this.syncLocalStorage();
    },
    handleDeleteReminder(id) {
      this.reminders = this.reminders.filter(rem => rem.id !== id);
      this.syncLocalStorage();
    }
  }
});
