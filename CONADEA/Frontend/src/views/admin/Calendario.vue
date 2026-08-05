<template>
  <div>
    <div class="fila-seccion"><h2>Calendario · Junio 2026</h2></div>
    <div class="cal-grid-layout">
      <!-- Calendario -->
      <div class="vidrio">
        <div class="cal-grid">
          <div v-for="d in diasSemana" :key="d" class="enc">{{ d }}</div>
          <div
            v-for="dia in 30"
            :key="dia"
            class="cal-dia mes"
            :class="{ evento: eventosDias.has(dia), hoy: dia === 9 }"
          >
            {{ dia }}
          </div>
        </div>
        <p style="font-size:.72rem;color:var(--texto-suave);margin-top:12px;">
          🟩 Días con actividades del programa · Borde azul: hoy
        </p>
      </div>
      <!-- Próximas actividades -->
      <div class="vidrio">
        <div class="cab-tarjeta"><h3>Próximas actividades</h3></div>
        <div v-for="e in EVENTOS" :key="e.dia" class="evento-linea">
          <div class="fecha-caja">
            <b>{{ e.dia }}</b>
            <small>{{ e.mes }}</small>
          </div>
          <div>
            <b style="font-size:.84rem;">{{ e.ic }} {{ e.t }}</b>
            <br><small style="color:var(--texto-suave);">{{ e.h }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { EVENTOS } from '../../data/local.js';

const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
const eventosDias = computed(() => new Set(EVENTOS.map(e => e.dia)));
</script>

<style scoped>
.cal-grid-layout { display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px; }
@media (max-width: 768px) { .cal-grid-layout { grid-template-columns: 1fr; } }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; font-size: 0.85rem; }
.enc { font-weight: 800; color: var(--texto-suave); font-size: 0.72rem; text-transform: uppercase; padding: 6px 0; }
.cal-dia { padding: 12px 0; border-radius: 12px; border: 1px solid transparent; position: relative; transition: all 0.2s; }
.cal-dia.mes { border-color: var(--borde); background: var(--vidrio); }
.cal-dia.mes:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
.cal-dia.evento { background: rgba(74,222,128,0.15); border-color: var(--verde); font-weight: 800; }
.cal-dia.hoy { outline: 2.5px solid var(--azul); font-weight: 800; box-shadow: 0 0 10px rgba(56,189,248,0.4); }
.evento-linea { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); font-size: 0.9rem; align-items: flex-start; }
.evento-linea:last-child { border-bottom: none; }
.fecha-caja { width: 56px; flex: none; text-align: center; background: var(--vidrio-2); border: 1px solid var(--borde); border-radius: 12px; padding: 8px 4px; }
.fecha-caja b { display: block; font-size: 1.15rem; font-family: 'Outfit', sans-serif; font-weight: 800; }
.fecha-caja small { font-size: 0.62rem; text-transform: uppercase; color: var(--texto-suave); font-weight: 700; }
</style>
