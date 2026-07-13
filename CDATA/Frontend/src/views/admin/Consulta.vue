<template>
  <div class="glass-panel query-panel">
      <h2><i class="fa-solid fa-users-viewfinder text-blue"></i> Consulta Directa al Padrón Electoral</h2>
      <p class="panel-subtitle">Busca ciudadanos registrados en el departamento de El Progreso por Nombre, DPI o Aldea de residencia.</p>

      <!-- Search Controls -->
      <div class="query-controls-grid">
          <div class="form-group search-box">
              <div class="search-input-wrapper">
                  <i class="fa-solid fa-magnifying-glass search-icon"></i>
                  <input type="text" placeholder="Ingresa DPI (e.g. 3406...), Nombre (e.g. AGUSTIN...) o Aldea (e.g. EL FLORIDO)...">
              </div>
          </div>

          <div class="form-group">
              <div class="select-wrapper">
                  <select>
                      <option value="">Todos los Municipios</option>
                  </select>
                  <i class="fa-solid fa-chevron-down"></i>
              </div>
          </div>

          <button class="btn-primary">
              <i class="fa-solid fa-magnifying-glass"></i> Buscar
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
                  <tr>
                      <td colspan="5" class="table-empty">
                          <i class="fa-solid fa-search"></i>
                          <p>Realiza una búsqueda para ver los registros del padrón.</p>
                      </td>
                  </tr>
              </tbody>
          </table>
      </div>
  </div>
</template>

<script setup>
</script>
