<template>
  <div>
    <div class="fila-seccion"><h2>Novedades del programa</h2></div>
    <div class="vidrio">
      <div v-for="(n, i) in NOVEDADES" :key="i" class="nov-item" :class="n.tipo">
        <div class="nov-icono">
          {{ n.tipo === 'video' ? '🎬' : n.tipo === 'alerta' ? '📢' : '📗' }}
        </div>
        <div>
          <b>{{ n.t }}</b>
          <p>{{ n.p }}</p>
          <small>{{ n.chip }} · {{ n.fecha }}</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { NOVEDADES } from '../../data/local.js';
</script>

<style scoped>
.nov-item { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--borde); transition: all 0.2s; }
.nov-item:hover { padding-left: 4px; }
.nov-item:last-child { border-bottom: none; }
.nov-icono { width: 42px; height: 42px; border-radius: 12px; flex: none; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; }
.nov-item.video .nov-icono   { background: linear-gradient(135deg, #60A5FA, #2563EB); }
.nov-item.alerta .nov-icono  { background: linear-gradient(135deg, #FBBF24, #D97706); }
.nov-item.novedad .nov-icono { background: linear-gradient(135deg, #4ADE80, #15803D); }
.nov-item b    { font-size: 0.88rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 700; }
.nov-item p    { font-size: 0.8rem; color: var(--texto-suave); line-height: 1.45; margin-top: 2px; }
.nov-item small{ font-size: 0.72rem; color: var(--texto-suave); opacity: 0.8; display: block; margin-top: 4px; }
</style>
