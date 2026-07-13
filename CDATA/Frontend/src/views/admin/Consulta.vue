<template>
  <div class="glass-panel query-panel">
      <h2><i class="fa-solid fa-users-viewfinder text-blue"></i> Consulta Directa al Padrón Electoral</h2>
      <p class="panel-subtitle">Busca ciudadanos registrados en el departamento de El Progreso por Nombre, DPI o Aldea de residencia.</p>

      <!-- Search Controls -->
      <div class="query-controls-grid">
          <div class="form-group search-box">
              <div class="search-input-wrapper">
                  <i class="fa-solid fa-magnifying-glass search-icon"></i>
                  <input v-model="query" @keyup.enter="handleSearch" type="text" placeholder="Ingresa DPI (e.g. 3406...), Nombre (e.g. AGUSTIN...) o Aldea (e.g. EL FLORIDO)...">
              </div>
          </div>

          <div class="form-group">
              <div class="select-wrapper">
                  <select v-model="municipio">
                      <option value="">Todos los Municipios</option>
                      <option v-for="muni in municipiosList" :key="muni" :value="muni">{{ muni }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down"></i>
              </div>
          </div>

          <button @click="handleSearch" :disabled="loading" class="btn-primary">
              <span v-if="!loading"><i class="fa-solid fa-magnifying-glass"></i> Buscar</span>
              <span v-else><i class="fa-solid fa-spinner fa-spin"></i></span>
          </button>
      </div>

      <!-- Results Table Container -->
      <div class="table-container mt-6">
          <table class="glass-table">
              <thead>
                  <tr>
                      <th>DPI</th>
                      <th>Nombre del Ciudadano</th>
                      <th>Municipio</th>
                      <th>Aldea / Localidad</th>
                      <th>Edad</th>
                  </tr>
              </thead>
              <tbody>
                  <tr v-if="records.length === 0 && !loading">
                      <td colspan="5" class="table-empty">
                          <i class="fa-solid fa-search"></i>
                          <p>Realiza una búsqueda para ver los registros del padrón.</p>
                      </td>
                  </tr>
                  <tr v-else-if="loading">
                      <td colspan="5" class="table-empty">
                          <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: var(--primary);"></i>
                          <p>Buscando registros...</p>
                      </td>
                  </tr>
                  <tr v-for="record in records" :key="record.id">
                      <td>{{ record.dpi }}</td>
                      <td style="font-weight: 600;">{{ record.nombre_ciudadano }}</td>
                      <td><span class="status-pill status-completed">{{ record.municipio }}</span></td>
                      <td>{{ record.aldea }}</td>
                      <td>{{ record.edad }}</td>
                  </tr>
              </tbody>
          </table>
      </div>
      
      <!-- Pagination Controls (Optional Simple Version) -->
      <div v-if="records.length > 0" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
          <span style="color: var(--text-secondary); font-size: 14px;">Mostrando {{ records.length }} de {{ formatNumber(totalRecords) }} resultados</span>
          <div style="display: flex; gap: 10px;">
              <button @click="changePage(-1)" :disabled="page === 1" class="btn-primary" style="padding: 5px 15px; border-radius: 8px;">Anterior</button>
              <button @click="changePage(1)" :disabled="records.length < limit" class="btn-primary" style="padding: 5px 15px; border-radius: 8px;">Siguiente</button>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const query = ref('');
const municipio = ref('');
const municipiosList = ref([]);
const records = ref([]);
const loading = ref(false);
const totalRecords = ref(0);
const page = ref(1);
const limit = ref(50);

const formatNumber = (num) => {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const fetchMunicipios = async () => {
    try {
        const res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/stats`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        if (res.ok) {
            const data = await res.json();
            municipiosList.value = data.municipios;
        }
    } catch (e) {
        console.error(e);
    }
};

const handleSearch = () => {
    page.value = 1;
    fetchResults();
};

const changePage = (dir) => {
    page.value += dir;
    fetchResults();
};

const fetchResults = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            q: query.value,
            municipio: municipio.value,
            page: page.value,
            limit: limit.value
        });
        
        const res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/query?${params.toString()}`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });
        
        if (res.ok) {
            const data = await res.json();
            records.value = data.records;
            totalRecords.value = data.total;
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchMunicipios();
});
</script>
