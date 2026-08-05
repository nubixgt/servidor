<template>
  <div>
    <div class="fila-seccion"><h2>Foros de productores</h2></div>
    <p style="font-size:.84rem;color:var(--texto-suave);margin-bottom:14px;">
      Espacio de intercambio entre asociaciones y cooperativas participantes.
    </p>
    <div class="vidrio">
      <div v-for="f in FOROS" :key="f.t" class="foro-item">
        <div class="foro-icono">{{ f.ic }}</div>
        <div>
          <b>{{ f.t }}</b>
          <small>{{ f.a }}</small>
        </div>
        <div class="resp">
          <b>{{ f.r }}</b> respuestas
        </div>
      </div>
      <button class="btn btn-ancho" @click="store.mostrarToastGlobal('💬','Función en desarrollo','Los foros estarán disponibles en la fase piloto del programa.')">
        + Nueva publicación
      </button>
    </div>
  </div>
</template>

<script setup>
import { useAppStore } from '../../stores/app.js';
import { FOROS } from '../../data/local.js';
const store = useAppStore();
</script>

<style scoped>
.foro-item { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--borde); align-items: center; transition: all 0.2s; }
.foro-item:hover { padding-left: 4px; }
.foro-item:last-child { border-bottom: none; }
.foro-icono { width: 44px; height: 44px; border-radius: 50%; background: var(--vidrio-2); border: 1px solid var(--borde); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex: none; }
.foro-item b { font-size: 0.9rem; display: block; font-family: 'Outfit', sans-serif; font-weight: 700; }
.foro-item small { font-size: 0.76rem; color: var(--texto-suave); display: block; margin-top: 2px; }
.resp { margin-left: auto; text-align: center; font-size: 0.74rem; color: var(--texto-suave); flex: none; background: rgba(255,255,255,0.04); padding: 6px 12px; border-radius: 10px; border: 1px solid var(--borde); }
.resp b { font-size: 1.1rem; color: var(--verde); display: inline; font-family: 'Outfit', sans-serif; font-weight: 800; margin-right: 2px; }
</style>
