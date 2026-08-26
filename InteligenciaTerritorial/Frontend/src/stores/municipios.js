import { defineStore } from 'pinia';
import api from '../services/api';

export const useMunicipiosStore = defineStore('municipios', {
  state: () => ({
    departamentos: [],
    municipios: [],
    loading: false,
    selectedDept: null,
    selectedMuni: null,
  }),
  getters: {
    totalMunicipios: (state) => state.municipios.length,
    municipiosConDiputado: (state) => state.departamentos.filter(d => d.diputado_asignado).reduce((acc, d) => acc + parseInt(d.total_municipios), 0),
    municipiosConGPC: (state) => state.departamentos.filter(d => d.gpc).reduce((acc, d) => acc + parseInt(d.total_municipios), 0),
  },
  actions: {
    async fetchDepartamentos() {
      this.loading = true;
      try {
        const response = await api.get('?action=departamentos');
        // Asegurarnos de que siempre sea un array
        this.departamentos = Array.isArray(response.data) ? response.data : [];
        if (!Array.isArray(response.data)) {
          console.error("La API no devolvió un array para departamentos:", response.data);
        }
      } catch (error) {
        console.error('Error fetching departamentos', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchMunicipios() {
      this.loading = true;
      try {
        const response = await api.get('?action=municipios');
        // Asegurarnos de que siempre sea un array
        this.municipios = Array.isArray(response.data) ? response.data : [];
        if (!Array.isArray(response.data)) {
          console.error("La API no devolvió un array para municipios:", response.data);
        }
      } catch (error) {
        console.error('Error fetching municipios', error);
      } finally {
        this.loading = false;
      }
    },
    async saveDepartamento(deptData) {
      try {
        await api.put('?action=departamento', deptData);
        // Refresh data
        await this.fetchDepartamentos();
        await this.fetchMunicipios();
        return true;
      } catch (error) {
        console.error('Error saving', error);
        return false;
      }
    },
    async saveMunicipio(muniData) {
      try {
        await api.put('?action=municipio', muniData);
        await this.fetchMunicipios();
        // Update selected muni if needed
        if (this.selectedMuni) {
          this.selectedMuni = this.municipios.find(m => m.id === this.selectedMuni.id);
        }
        return true;
      } catch (error) {
        console.error('Error saving municipio', error);
        return false;
      }
    },
    selectDept(dept) {
      this.selectedDept = dept;
      this.selectedMuni = null;
    },
    selectMuni(muni) {
      this.selectedMuni = muni;
    }
  }
});
