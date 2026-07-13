<template>
  <div class="dashboard-wrapper">
    <!-- Títulos Centrados -->
    <div class="title-section">
      <h1>Sistema de <span>Búsqueda Personal</span></h1>
      <p>Búsqueda inteligente y ultra-rápida de registros nacionales</p>
    </div>

    <!-- Buscador -->
    <div class="search-section">
      <div class="search-bar-container">
        <div class="search-icon-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a0aabf" stroke-width="2.5" width="20" height="20"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="handleInput"
          placeholder="Ingrese un nombre o DPI..."
          class="search-input"
          autofocus
        >
        <button class="clear-btn" v-if="searchQuery" @click="clearSearch" title="Limpiar búsqueda">
          <svg viewBox="0 0 24 24" fill="none" stroke="#a0aabf" stroke-width="2.5" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <button class="search-action-btn" @click="performSearch(searchQuery)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </button>
      </div>

      <!-- Stats pill -->
      <div class="search-stats" v-if="appState === 'results'">
        <div class="stat-pill" v-if="searchQuery">
          <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" width="16" height="16"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Búsqueda completada en <span class="highlight">0.38 segundos</span>
        </div>
        <div class="stat-pill">
          Se encontraron <span class="highlight-bold">{{ results.length }} coincidencias</span>
        </div>
      </div>
    </div>

    <!-- Resultados Loader -->
    <div class="results-grid skeleton-grid" v-if="appState === 'loading'">
      <div class="result-card skeleton-card" v-for="i in 6" :key="i"></div>
    </div>

    <!-- Grid de Resultados -->
    <div class="results-grid" v-if="appState === 'results'">
      <div class="result-card" v-for="(citizen, idx) in paginatedResults" :key="idx" @click="openModal(citizen)">
        <div class="card-header">
          <div class="avatar-circle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <h3 class="person-name">{{ citizen.nombre || 'SIN NOMBRE' }}</h3>
          <span class="age-pill">{{ citizen.edad !== undefined ? citizen.edad + ' AÑOS' : '--' }}</span>
        </div>
        
        <div class="card-body">
          <div class="info-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="#8ca3c7" stroke-width="2" width="16" height="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <span>{{ citizen.departamento }}, {{ citizen.municipio }}</span>
          </div>
          <div class="info-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="#8ca3c7" stroke-width="2" width="16" height="16"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
            <span>{{ citizen.aldea || 'NO ESPECIFICADA' }}</span>
          </div>
        </div>

        <div class="card-footer">
          <span class="dpi-label">DPI</span>
          <span class="dpi-value">{{ formatDPI(citizen.dpi) }}</span>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="appState === 'results' && results.length > 0">
      <button class="page-btn nav-btn" @click="prevPage" :disabled="currentPage === 1">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </button>
      <div class="page-dots">
        <span v-for="page in totalPages" :key="page" class="dot" :class="{ active: page === currentPage }" @click="currentPage = page"></span>
      </div>
      <button class="page-btn nav-btn" @click="nextPage" :disabled="currentPage === totalPages">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </button>
    </div>

    <!-- Sin Resultados -->
    <div class="empty-state" v-if="appState === 'no-results'">
      <h3>No se encontraron registros</h3>
      <p>Intenta buscando de nuevo con otros términos o un número de DPI diferente.</p>
    </div>
    
    <!-- Modal para Detalle Ampliado (Opcional visual) -->
    <div class="modal-overlay" v-if="selectedCitizen" @click.self="closeModal">
      <div class="modal-card result-card">
        <button class="modal-close clear-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="card-header" style="margin-bottom:20px;">
          <div class="avatar-circle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="32" height="32"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <h3 class="person-name" style="font-size:1.5rem;">{{ selectedCitizen.nombre || 'SIN NOMBRE' }}</h3>
          <span class="age-pill">{{ selectedCitizen.edad !== undefined ? selectedCitizen.edad + ' AÑOS' : '--' }}</span>
        </div>
        <div class="card-body">
          <div class="info-row"><strong>DPI:</strong> {{ formatDPI(selectedCitizen.dpi) }}</div>
          <div class="info-row"><strong>Departamento:</strong> {{ selectedCitizen.departamento || '---' }}</div>
          <div class="info-row"><strong>Municipio:</strong> {{ selectedCitizen.municipio || '---' }}</div>
          <div class="info-row"><strong>Aldea:</strong> {{ selectedCitizen.aldea || '---' }}</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const searchQuery = ref('');
const appState = ref('empty'); // 'empty', 'loading', 'results', 'no-results'
const results = ref([]);
const isDpiSearch = ref(false);
const selectedCitizen = ref(null);
let searchTimeout = null;

onMounted(() => {
  performSearch('');
});

// Paginación lógica simple para el UI (9 por página como en la imagen)
const currentPage = ref(1);
const itemsPerPage = 9;
const totalPages = computed(() => Math.ceil(results.value.length / itemsPerPage) || 1);
const paginatedResults = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return results.value.slice(start, start + itemsPerPage);
});

const formatDPI = (dpi) => {
  if (!dpi) return '---';
  const digits = String(dpi).replace(/\D/g, '');
  if (digits.length === 13) {
    return `${digits.substring(0, 4)}  ${digits.substring(4, 9)}  ${digits.substring(9, 13)}`;
  }
  return dpi;
};

const clearSearch = () => {
  searchQuery.value = '';
  performSearch('');
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
  }, 500);
};

const performSearch = async (query) => {

  appState.value = 'loading';
  currentPage.value = 1;
  
  try {
    const response = await fetch(`/FindP/Backend/api/v1/search?q=${encodeURIComponent(query)}`);
    if (!response.ok) throw new Error('Network error');
    
    const data = await response.json();
    if (data.error || !data.results || data.results.length === 0) {
      appState.value = 'no-results';
      return;
    }
    
    results.value = data.results;
    isDpiSearch.value = data.is_dpi;
    appState.value = 'results';
  } catch (error) {
    console.error('Error:', error);
    appState.value = 'no-results';
  }
};

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};
const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};

const openModal = (citizen) => {
  selectedCitizen.value = citizen;
};
const closeModal = () => {
  selectedCitizen.value = null;
};
</script>
