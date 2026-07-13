<template>
  <div class="dashboard-layout">
      <!-- Top stats row and Central holographic globe -->
      <div class="dashboard-top-section">
          
          <!-- Left Stats Columns -->
          <div class="stats-column-left">
              <!-- Card 1: Padrón Electoral -->
              <div class="glass-panel stat-card">
                  <div class="stat-card-header">
                      <div class="stat-icon-wrapper bg-purple">
                          <i class="fa-solid fa-users"></i>
                      </div>
                      <div class="stat-card-title">
                          <span>PADRÓN ELECTORAL</span>
                          <h3>{{ formatNumber(stats.total_registros) }}</h3>
                      </div>
                  </div>
                  <div class="stat-card-footer">
                      <span class="stat-footer-text">Ciudadanos registrados</span>
                      <!-- Sparkline SVG -->
                      <div class="sparkline-container">
                          <svg class="sparkline" viewBox="0 0 100 30">
                              <path d="M 0,25 Q 20,20 40,22 T 80,10 T 100,5" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round"></path>
                          </svg>
                      </div>
                  </div>
              </div>

              <!-- Card 2: Municipios Activos -->
              <div class="glass-panel stat-card">
                  <div class="stat-card-header">
                      <div class="stat-icon-wrapper bg-blue">
                          <i class="fa-solid fa-building-columns"></i>
                      </div>
                      <div class="stat-card-title">
                          <span>MUNICIPIOS ACTIVOS</span>
                          <h3>{{ stats.total_municipios }}</h3>
                      </div>
                  </div>
                  <div class="stat-card-footer">
                      <span class="stat-footer-text">Municipios activos</span>
                      <div class="sparkline-container">
                          <svg class="sparkline" viewBox="0 0 100 30">
                              <path d="M 0,25 Q 15,28 30,22 T 60,18 T 80,12 T 100,10" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round"></path>
                          </svg>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Center: Holographic globe -->
          <div class="center-globe-container">
              <div class="globe-outer-ring animate-pulse-slow"></div>
              <div class="globe-orbit-1"></div>
              <div class="globe-orbit-2"></div>
              <div class="globe-hologram">
                  <div class="globe-mesh">
                      <div class="globe-mesh-layer layers-1"></div>
                      <div class="globe-mesh-layer layers-2"></div>
                      <div class="globe-mesh-layer layers-3"></div>
                  </div>
                  <div class="hologram-stack">
                      <div class="hologram-layer-element element-1"></div>
                      <div class="hologram-layer-element element-2"></div>
                      <div class="hologram-layer-element element-3"></div>
                  </div>
              </div>
              <div class="globe-data-label">
                  <p>Datos que transforman</p>
                  <strong>decisiones.</strong>
              </div>
          </div>

          <!-- Right Stats Columns -->
          <div class="stats-column-right">
              <!-- Card 3: Aldeas en Padrón -->
              <div class="glass-panel stat-card">
                  <div class="stat-card-header">
                      <div class="stat-icon-wrapper bg-cyan">
                          <i class="fa-solid fa-location-dot"></i>
                      </div>
                      <div class="stat-card-title">
                          <span>ALDEAS EN PADRÓN</span>
                          <h3>{{ formatNumber(stats.total_aldeas) }}</h3>
                      </div>
                  </div>
                  <div class="stat-card-footer">
                      <span class="stat-footer-text">Aldeas registradas</span>
                      <div class="sparkline-container">
                          <svg class="sparkline" viewBox="0 0 100 30">
                              <path d="M 0,25 Q 25,23 50,15 T 75,18 T 100,8" fill="none" stroke="#22d3ee" stroke-width="2" stroke-linecap="round"></path>
                          </svg>
                      </div>
                  </div>
              </div>

              <!-- Card 4: Cobertura del Padrón -->
              <div class="glass-panel stat-card">
                  <div class="stat-card-header">
                      <!-- Circle progress SVG -->
                      <div class="progress-circle-container">
                          <svg viewBox="0 0 36 36" class="circular-chart-blue">
                              <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                              <path class="circle" stroke-dasharray="98, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                              <text x="18" y="20.35" class="percentage">98%</text>
                          </svg>
                      </div>
                      <div class="stat-card-title">
                          <span>COBERTURA DEL PADRÓN</span>
                          <h3>Excelente</h3>
                      </div>
                  </div>
                  <div class="stat-card-footer">
                      <span class="stat-footer-text">Estado del padrón</span>
                  </div>
              </div>
          </div>

      </div>

      <!-- Bottom panel grid (Activity, Donut stats, Ranked list) -->
      <div class="dashboard-bottom-section">
          
          <!-- Panel Left: Actividad en Tiempo Real -->
          <div class="glass-panel activity-panel">
              <h2><i class="fa-solid fa-bolt text-cyan"></i> ACTIVIDAD EN TIEMPO REAL</h2>
              <div class="timeline">
                  <div class="timeline-item">
                      <span class="timeline-time">10:23 AM</span>
                      <div class="timeline-badge bg-blue"><i class="fa-solid fa-map-location-dot"></i></div>
                      <div class="timeline-content">
                          <h4>Cruce de Aldeas ejecutado</h4>
                          <p>Se procesó el listado "GT_Padron EL PROGRESO"</p>
                      </div>
                      <span class="status-pill status-completed">Completado</span>
                  </div>
                  <div class="timeline-item">
                      <span class="timeline-time">10:15 AM</span>
                      <div class="timeline-badge bg-blue"><i class="fa-solid fa-file-excel"></i></div>
                      <div class="timeline-content">
                          <h4>Muestra Aldeas cargada</h4>
                          <p>Archivo "Muestra Aldeas_Antigravity.xlsx" importado</p>
                      </div>
                      <span class="status-pill status-completed">Completado</span>
                  </div>
                  <div class="timeline-item">
                      <span class="timeline-time">10:10 AM</span>
                      <div class="timeline-badge bg-purple"><i class="fa-solid fa-magnifying-glass"></i></div>
                      <div class="timeline-content">
                          <h4>Búsqueda en Padrón</h4>
                          <p>Consulta realizada para "AGUSTIN BURGOS"</p>
                      </div>
                      <span class="status-pill status-processing">Procesando</span>
                  </div>
                  <div class="timeline-item">
                      <span class="timeline-time">10:05 AM</span>
                      <div class="timeline-badge bg-muted"><i class="fa-solid fa-server"></i></div>
                      <div class="timeline-content">
                          <h4>Servidor Inicializado</h4>
                          <p>Carga de base de datos del padrón electoral</p>
                      </div>
                      <span class="status-pill status-pending">Pendiente</span>
                  </div>
              </div>
          </div>

          <!-- Panel Center: Estadísticas Generales (Donut Chart) -->
          <div class="glass-panel gender-stats-panel">
              <h2>ESTADÍSTICAS GENERALES</h2>
              <div class="gender-donut-container">
                  <!-- Donut SVG -->
                  <div class="donut-chart-wrapper">
                      <svg viewBox="0 0 200 200" class="donut-chart" v-if="stats.genero">
                          <circle cx="100" cy="100" r="70" class="donut-track" fill="transparent" stroke-width="20"></circle>
                          <circle cx="100" cy="100" r="70" class="donut-segment segment-women" fill="transparent" stroke-width="20" :stroke-dasharray="`${(stats.genero.mujeres / (stats.genero.hombres + stats.genero.mujeres)) * 439.8} 439.8`" stroke-dashoffset="0"></circle>
                          <circle cx="100" cy="100" r="70" class="donut-segment segment-men" fill="transparent" stroke-width="20" :stroke-dasharray="`${(stats.genero.hombres / (stats.genero.hombres + stats.genero.mujeres)) * 439.8} 439.8`" :stroke-dashoffset="`-${(stats.genero.mujeres / (stats.genero.hombres + stats.genero.mujeres)) * 439.8}`"></circle>
                      </svg>
                      <div class="donut-center-text">
                          <span class="donut-val">{{ (stats.total_registros / 1000).toFixed(0) }}K</span>
                          <span class="donut-lbl">Total</span>
                      </div>
                  </div>
                  <div class="donut-legend" v-if="stats.genero">
                      <div class="legend-item">
                          <span class="dot bg-blue"></span>
                          <span class="legend-label">Hombres</span>
                          <strong class="legend-val">{{ formatNumber(stats.genero.hombres) }} ({{ ((stats.genero.hombres / (stats.genero.hombres + stats.genero.mujeres)) * 100).toFixed(1) }}%)</strong>
                      </div>
                      <div class="legend-item">
                          <span class="dot bg-purple"></span>
                          <span class="legend-label">Mujeres</span>
                          <strong class="legend-val">{{ formatNumber(stats.genero.mujeres) }} ({{ ((stats.genero.mujeres / (stats.genero.hombres + stats.genero.mujeres)) * 100).toFixed(1) }}%)</strong>
                      </div>
                      <div class="legend-item">
                          <span class="dot bg-cyan"></span>
                          <span class="legend-label">Otros</span>
                          <strong class="legend-val">0 (0.0%)</strong>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Panel Right: Top Municipios (Ranked List) -->
          <div class="glass-panel top-municipios-panel">
              <h2><i class="fa-solid fa-trophy text-purple"></i> TOP MUNICIPIOS</h2>
              <div class="ranked-list" v-if="stats.top_municipios.length">
                  <div class="ranked-item" v-for="(muni, index) in stats.top_municipios" :key="index">
                      <span class="rank-num">{{ index + 1 }}</span>
                      <div class="rank-details">
                          <div class="rank-name-row">
                              <span class="rank-name">{{ muni.name }}</span>
                              <span class="rank-val">{{ formatNumber(muni.count) }}</span>
                          </div>
                          <div class="rank-progress-bar">
                              <div class="progress-fill" :style="{ width: calculateProgressWidth(muni.count, stats.top_municipios[0].count) + '%' }"></div>
                          </div>
                      </div>
                  </div>
              </div>
              <div v-else style="padding: 20px; text-align: center; color: var(--text-muted);">
                  Cargando ranking...
              </div>
          </div>

      </div>

      <!-- Footer Row Widget -->
      <div class="dashboard-footer-row glass-panel">
          <div class="footer-quote">
              <i class="fa-solid fa-quote-left"></i>
              <p>La información correcta, en el momento correcto, cambia el futuro de nuestro municipio.</p>
          </div>
          <div class="footer-sync">
              <span class="sync-indicator"></span>
              <div class="sync-text">
                  <strong>Sincronización en tiempo real</strong>
                  <span>Última actualización: Hace unos instantes</span>
              </div>
              <div class="sync-wave">
                  <svg viewBox="0 0 100 20" class="wave-svg">
                      <path d="M0,10 Q10,2 20,10 T40,10 T60,10 T80,10 T100,10" fill="none" stroke="#22d3ee" stroke-width="1.5"></path>
                  </svg>
              </div>
          </div>
          <div class="footer-actions">
              <button class="btn-quick-export">
                  <div class="export-icon-btn">
                      <i class="fa-regular fa-file-lines"></i>
                  </div>
                  <div class="export-text-btn">
                      <strong>Exportar Reporte</strong>
                      <span>Excel &bull; PDF &bull; CSV</span>
                  </div>
                  <i class="fa-solid fa-arrow-right export-arrow"></i>
              </button>
          </div>
      </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const stats = ref({
    total_registros: 0,
    total_aldeas: 0,
    total_municipios: 0,
    top_municipios: [],
    genero: { hombres: 67243, mujeres: 68762, otros: 0 }
});

const formatNumber = (num) => {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const calculateProgressWidth = (count, maxCount) => {
    if (!maxCount) return 0;
    return (count / maxCount) * 100;
};

const fetchStats = async () => {
    const token = localStorage.getItem('token');
    try {
        const res = await fetch(`${import.meta.env.BASE_URL}Backend/api/v1/stats`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        if (res.ok) {
            const data = await res.json();
            stats.value = data;
        }
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
};

onMounted(() => {
    fetchStats();
});
</script>
