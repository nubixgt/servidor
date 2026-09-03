<template>
  <div class="relative w-full overflow-hidden">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[720px] h-[360px] bg-primary-container/20 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-[1280px] mx-auto px-gutter-desktop pt-space-xl pb-space-3xl relative z-10 flex flex-col gap-space-2xl">
      <!-- Header -->
      <section class="flex flex-col gap-space-xs max-w-2xl">
        <span class="inline-flex items-center gap-1.5 px-space-sm py-1 bg-primary-container text-on-primary-fixed rounded-full font-label-meta text-label-meta uppercase tracking-widest shadow-sm w-fit">
          <span class="w-1.5 h-1.5 rounded-full bg-on-primary-fixed animate-pulse"></span>
          Temporada Oficial 2025
        </span>
        <h1 class="font-display-hero text-display-hero uppercase tracking-tight text-on-surface leading-none">
          Estadísticas Oficiales <span class="text-primary">& Líderes</span>
        </h1>
        <p class="font-body-md text-body-md text-secondary max-w-xl">
          Marcas individuales y tabla de posiciones de la Liga de Baloncesto Sanarateca.
        </p>
      </section>

      <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-md">Cargando estadísticas…</div>

      <template v-else>
        <!-- Podio -->
        <section class="flex flex-col gap-space-md">
          <div class="flex items-center gap-space-sm">
            <div class="w-2.5 h-6 bg-primary-container rounded-full"></div>
            <h2 class="font-headline-lg text-headline-lg uppercase tracking-wide text-on-surface">Líderes Individuales</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
            <div
              v-for="(l, i) in lideres"
              :key="l.key"
              class="group relative bg-surface-container-lowest rounded-xl p-space-xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden"
            >
              <span class="absolute -right-4 -bottom-6 font-stat-display text-[130px] font-bold text-surface-container-high/50 select-none pointer-events-none leading-none">0{{ i + 1 }}</span>
              <div class="flex items-start justify-between relative z-10">
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">{{ l.label }}</span>
                <span class="px-space-sm py-1 bg-primary-container text-on-primary-fixed rounded-full font-label-pill text-label-pill shadow-sm">{{ l.key.toUpperCase() }} #1</span>
              </div>

              <div v-if="l.jugador" class="my-space-lg flex items-center gap-space-md relative z-10">
                <div class="relative w-20 h-20 shrink-0 rounded-full overflow-hidden bg-surface-container flex items-center justify-center shadow-inner">
                  <img v-if="l.jugador.foto_ruta" class="w-full h-full object-cover" :alt="l.jugador.nombre_completo" :src="assetUrl(l.jugador.foto_ruta)" />
                  <span v-else class="material-symbols-outlined text-[36px] text-outline-variant">person</span>
                </div>
                <div class="flex flex-col min-w-0">
                  <h3 class="font-headline-md text-headline-md uppercase text-on-surface truncate leading-tight">{{ l.jugador.nombre_completo }}</h3>
                  <div class="flex items-center gap-1.5 text-secondary font-body-sm text-body-sm">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    <span class="truncate">{{ l.jugador.equipo_nombre || 'Sin equipo' }}</span>
                    <span v-if="l.jugador.dorsal != null" class="text-surface-variant">•</span>
                    <span v-if="l.jugador.dorsal != null">#{{ l.jugador.dorsal }}</span>
                  </div>
                </div>
              </div>
              <p v-else class="my-space-lg font-body-sm text-body-sm text-secondary relative z-10">Sin datos disponibles.</p>

              <div v-if="l.jugador" class="relative z-10 flex items-end justify-between pt-space-md bg-surface-container-low/60 p-space-md rounded-lg">
                <div>
                  <span class="font-stat-display text-stat-display text-on-surface leading-none block">{{ l.value }}</span>
                  <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">{{ l.unit }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Tabla de posiciones -->
        <section class="flex flex-col gap-space-md">
          <div class="flex items-center gap-space-sm">
            <div class="w-2.5 h-6 bg-primary-container rounded-full"></div>
            <h2 class="font-headline-lg text-headline-lg uppercase tracking-wide text-on-surface">Tabla Oficial de Posiciones</h2>
          </div>

          <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
            <table class="w-full min-w-[640px] text-left">
              <thead>
                <tr class="font-label-meta text-label-meta uppercase text-secondary border-b border-surface-container-high">
                  <th class="p-space-md">#</th>
                  <th class="p-space-md">Club</th>
                  <th class="p-space-md text-center">PJ</th>
                  <th class="p-space-md text-center">G</th>
                  <th class="p-space-md text-center">P</th>
                  <th class="p-space-md text-center">PF</th>
                  <th class="p-space-md text-center">PC</th>
                  <th class="p-space-md text-center">DIF</th>
                  <th class="p-space-md text-center">PTS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(c, idx) in clasificacion" :key="c.equipo_id" class="border-b border-surface-container last:border-0 font-body-sm text-body-sm">
                  <td class="p-space-md font-headline-md text-headline-md" :class="idx === 0 ? 'text-primary' : 'text-on-surface'">{{ idx + 1 }}</td>
                  <td class="p-space-md font-semibold text-on-surface">{{ c.equipo_nombre }}</td>
                  <td class="p-space-md text-center">{{ c.pj }}</td>
                  <td class="p-space-md text-center">{{ c.pg }}</td>
                  <td class="p-space-md text-center">{{ c.pp }}</td>
                  <td class="p-space-md text-center">{{ c.pf }}</td>
                  <td class="p-space-md text-center">{{ c.pc }}</td>
                  <td class="p-space-md text-center" :class="c.dif > 0 ? 'text-primary' : c.dif < 0 ? 'text-error' : ''">{{ c.dif > 0 ? '+' : '' }}{{ c.dif }}</td>
                  <td class="p-space-md text-center font-headline-md text-headline-md text-on-surface">{{ c.puntos_liga }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { assetUrl } from '../../services/assets';
import estadisticasService from '../../services/estadisticasService';

const loading = ref(true);
const jugadores = ref([]);
const clasificacion = ref([]);

function topBy(key) {
  return [...jugadores.value].filter((j) => j[key] > 0).sort((a, b) => b[key] - a[key])[0] || null;
}

const lideres = computed(() => [
  { key: 'ppg', label: 'Líder anotación', unit: 'Puntos por juego', jugador: topBy('ppg'), value: topBy('ppg')?.ppg ?? 0 },
  { key: 'rpg', label: 'Amo del tablero', unit: 'Rebotes por juego', jugador: topBy('rpg'), value: topBy('rpg')?.rpg ?? 0 },
  { key: 'apg', label: 'Visión de juego', unit: 'Asistencias por juego', jugador: topBy('apg'), value: topBy('apg')?.apg ?? 0 }
]);

onMounted(async () => {
  try {
    const [j, c] = await Promise.all([
      estadisticasService.jugadores(),
      estadisticasService.clasificacion()
    ]);
    jugadores.value = j;
    clasificacion.value = c;
  } catch {
    jugadores.value = [];
    clasificacion.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
