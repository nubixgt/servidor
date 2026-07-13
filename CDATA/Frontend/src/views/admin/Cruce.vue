<template>
  <div class="dashboard-grid">
      <!-- Left: Control Panel -->
      <div class="glass-panel control-panel">
          <h2><i class="fa-solid fa-sliders text-cyan"></i> Parámetros de Búsqueda</h2>
          <p class="panel-subtitle">Define el municipio y carga los listados de aldeas para realizar el conteo de personas.</p>
          
          <div class="form-group">
              <label for="municipio-select">Filtro por Municipio</label>
              <div class="select-wrapper">
                  <select id="municipio-select">
                      <option value="">Todos los Municipios (Búsqueda Global)</option>
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
                  <textarea id="aldeas-textarea" placeholder="Escribe una aldea por línea, ejemplo:&#10;ALDEA EL FLORIDO SECTOR I&#10;COMUNIDAD SAN LORENZO&#10;ALDEA SAN JUAN"></textarea>
                  <p class="input-helper">Coloca cada aldea en un renglón diferente.</p>
              </div>
          </div>

          <!-- Method: File Upload -->
          <div v-if="inputMethod === 'file'" class="method-content active">
              <div class="form-group">
                  <label>Cargar Archivo de Aldeas (Excel)</label>
                  <div class="file-upload-zone">
                      <input type="file" accept=".xlsx, .xls">
                      <i class="fa-solid fa-cloud-arrow-up"></i>
                      <p class="upload-title">Arrastra tu archivo Excel aquí</p>
                      <p class="upload-subtitle">O haz clic para explorar tus archivos (.xlsx, .xls)</p>
                  </div>
                  <p class="input-helper">El sistema buscará automáticamente la columna de aldeas.</p>
              </div>
          </div>

          <button class="btn-primary btn-block">
              <span><i class="fa-solid fa-play"></i> Iniciar Cruce de Aldeas</span>
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
              <div class="empty-state">
                  <i class="fa-solid fa-diagram-predecessor"></i>
                  <h3>Sin datos para mostrar</h3>
                  <p>Carga un archivo Excel o pega un listado de aldeas a la izquierda y presiona "Iniciar Cruce".</p>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const inputMethod = ref('text');
</script>
