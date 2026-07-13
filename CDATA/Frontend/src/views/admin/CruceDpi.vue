<template>
  <div class="dashboard-grid">
      <!-- Left: Control Panel -->
      <div class="glass-panel control-panel">
          <h2><i class="fa-solid fa-id-card text-cyan"></i> Cruce de DPIs</h2>
          <p class="panel-subtitle">Sube un listado de números de DPI para verificar si se encuentran registrados en el padrón.</p>

          <div class="input-method-tabs">
              <button class="method-btn" :class="{ active: inputMethod === 'text' }" @click="inputMethod = 'text'">Pega Texto</button>
              <button class="method-btn" :class="{ active: inputMethod === 'file' }" @click="inputMethod = 'file'">Sube Excel</button>
          </div>

          <div v-if="inputMethod === 'text'" class="method-content active">
              <div class="form-group">
                  <label for="dpis-textarea">Escribe o pega el listado de DPIs</label>
                  <textarea id="dpis-textarea" v-model="aldeasText" placeholder="Escribe un DPI por línea, ejemplo:&#10;3406981230101&#10;2998123450202&#10;1992012340301"></textarea>
                  <p class="input-helper">Coloca cada DPI en un renglón diferente.</p>
              </div>
          </div>

          <!-- Method: File Upload -->
          <div v-if="inputMethod === 'file'" class="method-content active">
              <div class="form-group">
                  <label>Cargar Archivo de DPIs (Excel)</label>
                  <div class="file-upload-zone" @click="$refs.fileInput.click()">
                      <input type="file" ref="fileInput" accept=".xlsx" @change="handleFileChange" style="display: none;">
                      <i class="fa-solid fa-cloud-arrow-up"></i>
                      <p class="upload-title" v-if="!selectedFile">Arrastra tu archivo Excel aquí</p>
                      <p class="upload-title" v-else style="color: var(--cyan);">{{ selectedFile.name }}</p>
                      <p class="upload-subtitle" v-if="!selectedFile">O haz clic para explorar tus archivos (.xlsx)</p>
                  </div>
                  <p class="input-helper">El sistema buscará automáticamente en la primera columna.</p>
              </div>
          </div>

          <button @click="iniciarCruce" :disabled="loading" class="btn-primary btn-block">
              <span v-if="!loading"><i class="fa-solid fa-play"></i> Verificar DPIs</span>
              <span v-else><i class="fa-solid fa-spinner fa-spin"></i> Verificando en base de datos...</span>
          </button>
      </div>

      <!-- Right: Results Panel -->
      <div class="glass-panel results-panel">
          <div class="results-header">
              <h2><i class="fa-solid fa-square-poll-vertical text-purple"></i> Resultados del Empadronamiento</h2>
          </div>
          
          <!-- Results Content -->
          <div class="results-body">
              <div v-if="!results" class="empty-state">
                  <i class="fa-solid fa-id-card-clip"></i>
                  <h3>Sin datos para mostrar</h3>
                  <p>Carga un archivo Excel o pega un listado de DPIs a la izquierda y presiona "Verificar DPIs".</p>
              </div>
              
              <div v-else class="results-data-view">
                  <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                      <div class="stat-card" style="padding: 15px; flex: 1; border: 1px solid var(--border-color); border-radius: 10px;">
                          <p style="color: var(--text-secondary); font-size: 13px;">DPIs Encontrados</p>
                          <h3 style="font-size: 24px; color: var(--cyan);">{{ formatNumber(totalPersonas) }}</h3>
                      </div>
                      <div class="stat-card" style="padding: 15px; flex: 1; border: 1px solid var(--border-color); border-radius: 10px;">
                          <p style="color: var(--text-secondary); font-size: 13px;">Total Consultados</p>
                          <h3 style="font-size: 24px; color: var(--purple);">{{ totalAldeas }}</h3>
                      </div>
                  </div>

                  <table class="glass-table" style="width: 100%;">
                      <thead>
                          <tr>
                              <th>DPI Consultado</th>
                              <th>Estado</th>
                              <th>Nombre del Ciudadano</th>
                              <th>Municipio y Aldea</th>
                          </tr>
                      </thead>
                      <tbody>
                          <tr v-for="(res, index) in results" :key="index">
                              <td style="font-family: monospace;">{{ res.dpi_buscado }}</td>
                              <td>
                                  <span v-if="res.encontrado" class="status-pill status-completed">EMPADRONADO</span>
                                  <span v-else class="status-pill status-processing" style="background: rgba(255,50,50,0.1); color: #ff6b6b; border-color: rgba(255,50,50,0.2);">NO APARECE</span>
                              </td>
                              <td style="font-weight: 600;">{{ res.nombre }}</td>
                              <td style="color: var(--text-secondary);">{{ res.encontrado ? `${res.municipio} / ${res.aldea}` : '-' }}</td>
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
            res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/match-dpi`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ dpis: list })
            });
        } else {
            if (!selectedFile.value) {
                alert('Por favor selecciona un archivo Excel.');
                loading.value = false;
                return;
            }
            const formData = new FormData();
            formData.append('file', selectedFile.value);
            
            res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/match-dpi-file`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: formData
            });
        }

        if (res.ok) {
            const data = await res.json();
            results.value = data.results;
            totalPersonas.value = data.total_encontrados;
            totalAldeas.value = data.total_analizados;
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
