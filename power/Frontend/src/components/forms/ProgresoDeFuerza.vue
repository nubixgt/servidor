<template>
  <!-- Plegado: se carga al abrirlo, no con cada visita al perfil. -->
  <details class="group rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3" @toggle="alAbrir">
    <summary class="cursor-pointer list-none flex items-center gap-1.5 text-[12px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#caf300]">
      <span class="material-symbols-outlined text-[16px] transition-transform group-open:rotate-90">chevron_right</span>
      Tu progreso
    </summary>
    <p v-if="cargando" class="mt-2 text-[12px] text-[#8f9378]">Cargando…</p>
    <p v-else-if="!ejercicios.length" class="mt-2 text-[12px] text-[#c5c9ac]">
      Aún no has registrado ninguna sesión. En tu plan, en cada día de fuerza, pulsa «Registrar lo que hiciste».
    </p>
    <ul v-else class="mt-2 space-y-2.5">
      <li v-for="e in ejercicios" :key="e.clave">
        <p class="text-[13px] font-bold text-white">{{ e.nombre }}</p>
        <p class="flex flex-wrap gap-x-3 gap-y-0.5 text-[12px] text-[#c5c9ac] font-mono-data">
          <span v-for="(x, i) in e.entradas" :key="i" :class="i === 0 ? 'text-[#e3e6cf]' : ''">
            {{ fechaCorta(x.fecha) }} · {{ x.texto }}<span v-if="x.subio" class="text-[#caf300]" title="Más kilos que la vez anterior"> ↑</span>
          </span>
        </p>
      </li>
    </ul>
  </details>
</template>

<script setup>
/** Lo que el atleta registró, ejercicio a ejercicio (Database/026). Sólo lectura. */
import { ref } from 'vue';
import { hoyDelAtleta } from '../../utils/macrociclo.js';
import { historialPorEjercicio, registroDeFuerza } from '../../services/fuerza.js';

const ejercicios = ref([]);
const cargando = ref(false);
let cargado = false;

const hace = (dias) => {
  const d = new Date();
  d.setDate(d.getDate() - dias);
  // Su día, no el de UTC: por la noche en Perú toISOString ya es mañana.
  return hoyDelAtleta(d);
};

async function alAbrir(evento) {
  if (!evento.target.open || cargado) return;
  cargado = true;
  cargando.value = true;
  try {
    const datos = await registroDeFuerza({ desde: hace(180) });
    ejercicios.value = historialPorEjercicio(datos?.logs);
  } catch (e) {
    ejercicios.value = [];
    cargado = false;  // que se pueda reintentar al volver a abrirlo
  } finally {
    cargando.value = false;
  }
}

const fechaCorta = (iso) => {
  const d = new Date(`${iso}T12:00:00`);
  return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString('es', { day: 'numeric', month: 'short' });
};
</script>
