<template>
  <div>
    <div class="fila-seccion">
      <h2>Mis insignias</h2>
      <span style="font-size:.78rem;color:var(--texto-suave);">{{ store.insignias.length }} de {{ INSIGNIAS.length }} obtenidas</span>
    </div>
    <p style="font-size:.84rem;color:var(--texto-suave);margin-bottom:14px;">
      Completa lecciones, aprueba evaluaciones y participa en el programa para desbloquearlas todas.
    </p>
    <div class="grid-insignias">
      <div v-for="b in INSIGNIAS" :key="b.id" class="hex-card">
        <div class="hexagono" :class="obtenerColor(b)">
          {{ store.insignias.includes(b.id) ? b.ic : '🔒' }}
        </div>
        <div>
          <b>{{ b.t }}</b>
          <small>{{ b.d }}</small>
          <small v-if="store.insignias.includes(b.id)" class="obtenida">Obtenida ✓</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAppStore } from '../../stores/app.js';
import { INSIGNIAS } from '../../data/local.js';

const store = useAppStore();
const colores = { semilla: 'verde', modulo1: 'verde', tres: 'verde', explorador: 'azul' };
function obtenerColor(b) {
  if (!store.insignias.includes(b.id)) return 'gris';
  return colores[b.id] || '';
}
</script>

<style scoped>
.grid-insignias { display: grid; grid-template-columns: repeat(auto-fill, minmax(225px, 1fr)); gap: 14px; }
.hex-card { display: flex; gap: 14px; align-items: center; background: rgba(13,38,48,0.35); border: 1px solid var(--borde); border-radius: 16px; padding: 14px; transition: all 0.3s; }
.hex-card:hover { border-color: rgba(255,255,255,0.25); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
.hexagono { width: 52px; height: 58px; flex: none; font-size: 1.4rem; clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); background: linear-gradient(160deg, #FDE68A, #D97706); display: flex; align-items: center; justify-content: center; transition: all 0.3s cubic-bezier(0.175,0.885,0.32,1.275); }
.hex-card:hover .hexagono { transform: scale(1.18) rotate(10deg); }
.hexagono.verde { background: linear-gradient(160deg, #86EFAC, #15803D); box-shadow: 0 0 12px rgba(34,197,94,0.4); }
.hexagono.azul  { background: linear-gradient(160deg, #7DD3FC, #0369A1); box-shadow: 0 0 12px rgba(56,189,248,0.4); }
.hexagono.gris  { background: linear-gradient(160deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05)); filter: grayscale(1); opacity: 0.4; }
.hex-card b     { font-size: 0.88rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 700; }
.hex-card small { font-size: 0.76rem; color: var(--texto-suave); display: block; line-height: 1.4; margin-top: 1px; }
.obtenida { color: var(--lima) !important; font-weight: 700; }
</style>
