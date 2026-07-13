<template>
  <div>
    <!-- Sección de Búsqueda -->
    <section class="search-section">
      <div class="search-box-container">
        <div class="search-icon">
          <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>
        <input 
          type="text" 
          id="search-input" 
          v-model="searchQuery"
          @input="handleInput"
          placeholder="Busca por Nombre Completo (ej: Pedro López) o por DPI..." 
          autocomplete="off"
          autofocus
        >
        <button 
          v-if="searchQuery"
          @click="clearSearch"
          class="clear-button visible" 
          title="Limpiar búsqueda"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="search-helper">
        <span>Busca instantáneamente entre más de 10 millones de registros de la República de Guatemala.</span>
      </div>
    </section>

    <!-- Información de Resultados / Mensajes -->
    <div class="status-bar" v-show="appState === 'results'">
      <div class="results-count">
        <template v-if="isDpiSearch">
          Búsqueda por DPI. Se encontró <span>1</span> registro coincidente.
        </template>
        <template v-else>
          Se encontraron <span>{{ results.length }}</span> coincidencia(s).
        </template>
      </div>
      <div class="warning-badge" v-show="hasMore">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        Mostrando las primeras 100 coincidencias. Por favor, sé más específico.
      </div>
    </div>

    <!-- Panel Principal de Contenido -->
    <main class="results-area">
      <!-- Estado Inicial -->
      <div class="empty-state" v-if="appState === 'empty'">
        <div class="empty-illustration">
          <svg viewBox="0 0 24 24" width="80" height="80" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            <path d="M11 8v6M8 11h6" stroke-opacity="0.4"/>
          </svg>
        </div>
        <h3>Escribe para buscar</h3>
        <p>Ingresa un nombre completo (nombres o apellidos) o el número de DPI de 13 dígitos para ver el registro correspondiente.</p>
      </div>

      <!-- Loader (Skeletons) -->
      <div class="skeleton-container" v-if="appState === 'loading'">
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
      </div>

      <!-- Resultados -->
      <div class="results-grid" v-if="appState === 'results'">
        <div 
          class="citizen-card" 
          v-for="(citizen, index) in results" 
          :key="index"
          @click="openModal(citizen)"
        >
          <div>
            <div class="card-header">
              <h4 class="card-name" :title="citizen.nombre">{{ citizen.nombre ? citizen.nombre.toLowerCase() : 'SIN NOMBRE' }}</h4>
              <span class="badge badge-age">{{ citizen.edad !== undefined ? citizen.edad + ' años' : 'Edad no reg.' }}</span>
            </div>
            
            <div class="card-details">
              <div class="detail-row">
                <span class="detail-icon">
                  <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                </span>
                <div class="detail-text">
                  <label>Ubicación</label>
                  <span>{{ citizen.departamento }}, {{ citizen.municipio }}</span>
                </div>
              </div>
              
              <div class="detail-row">
                <span class="detail-icon">
                  <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="9" y1="3" x2="9" y2="21"/>
                  </svg>
                </span>
                <div class="detail-text">
                  <label>Colonia / Sector / Aldea</label>
                  <span :title="citizen.aldea || 'No especificada'">{{ citizen.aldea || 'No especificada' }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="card-footer">
            <div class="dpi-container">
              <label>DPI</label>
              <span class="dpi-value">{{ formatDPI(citizen.dpi) }}</span>
            </div>
            <span class="view-more-hint">
              Ver detalle
              <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
              </svg>
            </span>
          </div>
        </div>
      </div>

      <!-- Sin Resultados -->
      <div class="no-results-state" v-if="appState === 'no-results'">
        <div class="empty-illustration">
          <svg viewBox="0 0 24 24" width="80" height="80" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
          </svg>
        </div>
        <h3>No se encontraron registros</h3>
        <p>No pudimos encontrar ninguna coincidencia para tu búsqueda. Verifica la ortografía e intenta de nuevo.</p>
      </div>
    </main>

    <!-- Modal para Detalle Ampliado -->
    <div class="modal-overlay" v-if="selectedCitizen" @click.self="closeModal">
      <div class="modal-card">
        <button class="modal-close" @click="closeModal">
          <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
        <div class="modal-header">
          <div class="modal-avatar">
            <svg viewBox="0 0 24 24" width="36" height="36" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h3>{{ selectedCitizen.nombre || 'SIN NOMBRE' }}</h3>
          <span class="badge badge-age">{{ selectedCitizen.edad !== undefined ? selectedCitizen.edad + ' años' : 'Edad no reg.' }}</span>
        </div>
        <div class="modal-body">
          <div class="info-group">
            <label>DPI / Código de Identificación</label>
            <div class="value-highlight">{{ formatDPI(selectedCitizen.dpi) }}</div>
          </div>
          <div class="detail-grid">
            <div class="info-group">
              <label>Departamento</label>
              <div class="value">{{ selectedCitizen.departamento || '---' }}</div>
            </div>
            <div class="info-group">
              <label>Municipio</label>
              <div class="value">{{ selectedCitizen.municipio || '---' }}</div>
            </div>
            <div class="info-group full-width">
              <label>Colonia / Sector / Aldea</label>
              <div class="value">{{ selectedCitizen.aldea || '---' }}</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="primary-btn" @click="closeModal">Entendido</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const searchQuery = ref('');
const appState = ref('empty'); // 'empty', 'loading', 'results', 'no-results'
const results = ref([]);
const isDpiSearch = ref(false);
const hasMore = ref(false);
const selectedCitizen = ref(null);
let searchTimeout = null;

const formatDPI = (dpi) => {
  if (!dpi) return '---';
  const digits = String(dpi).replace(/\D/g, '');
  if (digits.length === 13) {
    return `${digits.substring(0, 4)} ${digits.substring(4, 9)} ${digits.substring(9, 13)}`;
  }
  return dpi;
};

const clearSearch = () => {
  searchQuery.value = '';
  appState.value = 'empty';
  results.value = [];
};

const handleInput = () => {
  const val = searchQuery.value.trim();
  if (val === '') {
    clearSearch();
    return;
  }
  
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    performSearch(val);
  }, 300);
};

const performSearch = async (query) => {
  appState.value = 'loading';
  
  try {
    // We fetch from the placeholder backend endpoint, adjust URL if needed based on Vite proxy setup
    const response = await fetch(`${import.meta.env.BASE_URL}api/search?q=${encodeURIComponent(query)}`);
    if (!response.ok) {
      throw new Error('Respuesta de red no válida');
    }
    
    const data = await response.json();
    
    if (data.error || !data.results || data.results.length === 0) {
      appState.value = 'no-results';
      return;
    }
    
    results.value = data.results;
    isDpiSearch.value = data.is_dpi;
    hasMore.value = data.has_more;
    appState.value = 'results';
    
  } catch (error) {
    console.error('Error al realizar la búsqueda:', error);
    appState.value = 'no-results';
  }
};

const openModal = (citizen) => {
  selectedCitizen.value = citizen;
  document.body.style.overflow = 'hidden';
};

const closeModal = () => {
  selectedCitizen.value = null;
  document.body.style.overflow = '';
};
</script>
