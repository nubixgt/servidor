<template>
  <div class="dashboard-grid">
      <!-- Left: Control Panel -->
      <div class="glass-panel control-panel">
          <h2><i class="fa-solid fa-sliders text-cyan"></i> Parámetros de Búsqueda</h2>
          <p class="panel-subtitle">Define el municipio y carga los listados de aldeas para realizar el conteo de personas.</p>
          
          <div class="form-group">
              <label for="municipio-select">Filtro por Municipio</label>
              <div class="select-wrapper">
                  <select id="municipio-select" v-model="municipio">
                      <option value="">Todos los Municipios (Búsqueda Global)</option>
                      <option v-for="muni in municipiosList" :key="muni" :value="muni">{{ muni }}</option>
                  </select>
                  <i class="fa-solid fa-chevron-down"></i>
              </div>
              <p class="input-helper">Si filtras por un municipio, las búsquedas y coincidencias se limitarán solo a ese territorio.</p>
          </div>

          <div class="input-method-tabs">
              <button class="method-btn" :class="{ active: inputMethod === 'text' }" @click="inputMethod = 'text'">Pega Texto</button>
              <button class="method-btn" :class="{ active: inputMethod === 'file' }" @click="inputMethod = 'file'">Sube Excel</button>
          </div>

          <!-- Method: Textarea -->
          <div v-if="inputMethod === 'text'" class="method-content active">
              <div class="form-group">
                  <label for="aldeas-textarea">Escribe o pega el listado de aldeas</label>
                  <textarea id="aldeas-textarea" v-model="aldeasText" placeholder="Escribe una aldea por línea, ejemplo:&#10;ALDEA EL FLORIDO SECTOR I&#10;COMUNIDAD SAN LORENZO&#10;ALDEA SAN JUAN"></textarea>
                  <p class="input-helper">Coloca cada aldea en un renglón diferente.</p>
              </div>
          </div>

          <!-- Method: File Upload -->
          <div v-if="inputMethod === 'file'" class="method-content active">
              <div class="form-group">
                  <label>Cargar Archivo de Aldeas (Excel)</label>
                  <div class="file-upload-zone" @click="$refs.fileInput.click()">
                      <input type="file" ref="fileInput" accept=".xlsx" @change="handleFileChange" style="display: none;">
                      <i class="fa-solid fa-cloud-arrow-up"></i>
                      <p class="upload-title" v-if="!selectedFile">Arrastra tu archivo Excel aquí</p>
                      <p class="upload-title" v-else style="color: var(--cyan);">{{ selectedFile.name }}</p>
                      <p class="upload-subtitle" v-if="!selectedFile">O haz clic para explorar tus archivos (.xlsx)</p>
                  </div>
                  <p class="input-helper">El sistema buscará automáticamente la primera columna con texto.</p>
              </div>
          </div>

          <button @click="iniciarCruce" :disabled="loading" class="btn-primary btn-block">
              <span v-if="!loading"><i class="fa-solid fa-play"></i> Iniciar Cruce de Aldeas</span>
              <span v-else><i class="fa-solid fa-spinner fa-spin"></i> Procesando cruce inteligente...</span>
          </button>
      </div>

      <!-- Right: Results Panel -->
      <div class="glass-panel results-panel">
          <div class="results-header">
              <h2><i class="fa-solid fa-square-poll-vertical text-purple"></i> Resultados del Cruce</h2>
              <button class="btn-secondary" disabled>
                  <i class="fa-solid fa-file-export"></i> Exportar a Excel
              </button>
          </div>
          
          <!-- Results Content -->
          <div class="results-body">
              <div v-if="!results" class="empty-state">
                  <i class="fa-solid fa-diagram-predecessor"></i>
                  <h3>Sin datos para mostrar</h3>
                  <p>Carga un archivo Excel o pega un listado de aldeas a la izquierda y presiona "Iniciar Cruce".</p>
              </div>
              
              <div v-else class="results-data-view">
                  <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                      <div class="stat-card" style="padding: 15px; flex: 1; border: 1px solid var(--border-color); border-radius: 10px;">
                          <p style="color: var(--text-secondary); font-size: 13px;">Total Personas</p>
                          <h3 style="font-size: 24px; color: var(--cyan);">{{ formatNumber(totalPersonas) }}</h3>
                      </div>
                      <div class="stat-card" style="padding: 15px; flex: 1; border: 1px solid var(--border-color); border-radius: 10px;">
                          <p style="color: var(--text-secondary); font-size: 13px;">Aldeas Analizadas</p>
                          <h3 style="font-size: 24px; color: var(--purple);">{{ totalAldeas }}</h3>
                      </div>
                  </div>

                  <table class="glass-table" style="width: 100%;">
                      <thead>
                          <tr>
                              <th>Aldea Buscada</th>
                              <th>Match Encontrado</th>
                              <th>Precisión</th>
                              <th>Población</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr v-for="(res, index) in results" :key="index">
                              <td>{{ res.original }}</td>
                              <td><span class="status-pill status-processing">{{ res.mapped }}</span></td>
                              <td>{{ res.match_score }}%</td>
                              <td style="font-weight: bold; color: var(--blue);">{{ formatNumber(res.count) }}</td>
                          </tr>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const inputMethod = ref('text');
const municipio = ref('');
const municipiosList = ref([]);
const aldeasText = ref('');
const selectedFile = ref(null);
const fileInput = ref(null);

const loading = ref(false);
const results = ref(null);
const totalPersonas = ref(0);
const totalAldeas = ref(0);

const formatNumber = (num) => {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const handleFileChange = (e) => {
    if (e.target.files.length > 0) {
        selectedFile.value = e.target.files[0];
    }
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
    } catch (e) { console.error(e); }
};

const iniciarCruce = async () => {
    loading.value = true;
    results.value = null;

    try {
        const token = localStorage.getItem('token');
        let res;

        if (inputMethod.value === 'text') {
            const list = aldeasText.value.split('\n').map(a => a.trim()).filter(a => a);
            res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/match`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ aldeas: list, municipio: municipio.value })
            });
        } else {
            if (!selectedFile.value) {
                alert('Por favor selecciona un archivo Excel.');
                loading.value = false;
                return;
            }
            const formData = new FormData();
            formData.append('file', selectedFile.value);
            formData.append('municipio', municipio.value);
            
            res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/match-file`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: formData
            });
        }

        if (res.ok) {
            const data = await res.json();
            results.value = data.results;
            totalPersonas.value = data.total_personas;
            totalAldeas.value = data.total_aldeas_analizadas;
        } else {
            const err = await res.json();
            alert('Error: ' + err.error);
        }
    } catch (error) {
        console.error(error);
        alert('Hubo un error al procesar el cruce.');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchMunicipios();
});
</script>
