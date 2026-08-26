<template>
  <aside>
    <div class="brand">
      <div class="logo">IT</div>
      <div class="brand-text">
        <b>Inteligencia Territorial</b>
        <small>Guatemala · 340 Municipios</small>
      </div>
    </div>

    <div class="section-label">Panel</div>
    <button class="navbtn active" @click="store.selectDept(null)"><i class="fas fa-map-marked-alt"></i> Mapa Interactivo</button>

    <div class="section-label">Estadísticas</div>
    <div class="stats-container">
      <div class="stat-mini">
        <div class="label">Municipios totales</div>
        <div class="value">{{ store.totalMunicipios }}</div>
      </div>
      <div class="stat-mini">
        <div class="label">Con diputado asignado</div>
        <div class="value">{{ store.municipiosConDiputado }}</div>
      </div>
      <div class="stat-mini">
        <div class="label">Con GPC asignado</div>
        <div class="value">{{ store.municipiosConGPC }}</div>
      </div>
    </div>

    <div class="section-label">Departamentos</div>
    <div class="dept-list">
      <div v-for="d in store.departamentos" :key="d.departamento" 
           class="dept-item" 
           :class="{'active-dept': store.selectedDept === d.departamento}"
           @click="store.selectDept(d.departamento)">
        <span>{{ d.departamento }}</span>
        <span class="count">{{ d.total_municipios }}</span>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { useMunicipiosStore } from '../stores/municipios';
const store = useMunicipiosStore();
</script>
