<template>
  <div>
    <div class="fila-seccion"><h2>Rutas de aprendizaje</h2></div>
    <p style="font-size:.84rem;color:var(--texto-suave);margin-bottom:14px;">
      Las rutas agrupan cursos de la malla curricular AgroIA según tu actividad productiva.
    </p>
    <div v-for="r in RUTAS" :key="r.id" class="vidrio ruta-card">
      <div class="ruta-header" :class="r.clase">
        <div class="ruta-icono">{{ r.ic }}</div>
        <div class="ruta-info">
          <b>{{ r.t }}</b>
          <p>{{ r.d }}</p>
          <span class="estado-ruta">{{ store.pctRuta(r) }}% completado · {{ r.mods.length }} cursos</span>
          <div class="pista" style="margin-top:6px;">
            <div class="pista-fill" :style="{ width: store.pctRuta(r) + '%' }"></div>
          </div>
        </div>
      </div>
      <!-- Módulos de la ruta -->
      <div style="margin-top:12px;">
        <div v-for="id in r.mods" :key="id" class="curso-fila-mini">
          <div class="mini-ic" :style="gradMod(id)">{{ getModulo(id)?.ic }}</div>
          <div class="datos">
            <b>{{ getModulo(id)?.t }}</b>
            <div class="pista"><div class="pista-fill" :style="{ width: store.pctModulo(getModulo(id)) + '%' }"></div></div>
          </div>
          <button class="btn-continuar-sm" :class="{ hecho: store.progDe(id).ok }" @click="router.push(`/catalogo/${id}`)">
            {{ store.progDe(id).ok ? 'Repasar' : 'Ir al curso' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { MODULOS, RUTAS, gradMod } from '../../data/local.js';

const router = useRouter();
const store = useAppStore();
const getModulo = (id) => MODULOS.find(m => m.id === id);
</script>

<style scoped>
.ruta-card { margin-bottom: 14px; }
.ruta-header { display: flex; gap: 16px; align-items: flex-start; }
.ruta-icono { width: 56px; height: 56px; border-radius: 14px; flex: none; font-size: 1.7rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #34D399, #059669); }
.ruta-header.azul .ruta-icono { background: linear-gradient(135deg, #60A5FA, #1D4ED8); }
.ruta-header.oro .ruta-icono  { background: linear-gradient(135deg, #FCD34D, #D97706); }
.ruta-header.verde .ruta-icono{ background: linear-gradient(135deg, #4ADE80, #15803D); }
.ruta-info { flex: 1; min-width: 0; }
.ruta-info b { font-size: 0.95rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
.ruta-info p { font-size: 0.78rem; color: var(--texto-suave); margin: 4px 0 6px; }
.estado-ruta { font-size: 0.76rem; color: var(--lima); font-weight: 700; }
.curso-fila-mini { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--borde); }
.curso-fila-mini:last-child { border-bottom: none; }
.mini-ic { width: 36px; height: 36px; border-radius: 10px; flex: none; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.datos { flex: 1; min-width: 0; }
.datos b { font-size: 0.88rem; display: block; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Outfit', sans-serif; font-weight: 700; }
.btn-continuar-sm { border: 1.5px solid var(--verde); border-bottom: 3px solid var(--verde-oscuro); color: var(--verde); font-size: 0.74rem; font-weight: 700; padding: 5px 12px 7px; border-radius: 10px; flex: none; background: transparent; cursor: pointer; transition: all 0.1s; }
.btn-continuar-sm:hover { background: var(--verde); color: #06281A; transform: translateY(-1px); }
.btn-continuar-sm.hecho { border-color: var(--oro); border-bottom-color: #B45309; color: var(--oro); }
.btn-continuar-sm.hecho:hover { background: var(--oro); color: #3A2A00; }
</style>
