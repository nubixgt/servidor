<template>
  <div class="relative w-full overflow-hidden">
    <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary-container/20 blur-3xl pointer-events-none"></div>
    <span class="absolute right-6 top-8 select-none font-display-hero text-[140px] leading-none uppercase text-surface-container-highest/40 font-bold tracking-tighter pointer-events-none">FIXTURE</span>

    <div class="max-w-[1280px] mx-auto px-gutter-desktop py-space-xl relative z-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md mb-space-xl">
        <div class="flex flex-col gap-space-2xs">
          <div class="flex items-center gap-space-xs">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-container animate-ping"></span>
            <span class="font-label-meta text-label-meta uppercase tracking-widest text-primary">Temporada 2025</span>
          </div>
          <h1 class="font-headline-xl text-headline-xl uppercase tracking-tight text-on-surface">
            Calendario & <span class="text-primary font-bold">Fixture Oficial</span>
          </h1>
        </div>

        <div class="flex items-center gap-space-xs bg-surface-container-low p-space-2xs rounded-full shadow-sm">
          <button
            v-for="f in statusFilters"
            :key="f.id"
            @click="activeStatus = f.id"
            :class="[
              'px-space-md py-space-2xs rounded-full font-label-pill text-label-pill transition-all duration-200',
              activeStatus === f.id ? 'bg-primary-container text-on-primary-fixed shadow-sm font-semibold' : 'text-secondary hover:text-on-surface'
            ]"
          >{{ f.label }}</button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-start">
        <div class="lg:col-span-8 flex flex-col gap-space-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-space-xs">
              <span class="material-symbols-outlined text-primary text-[20px]">sports_basketball</span>
              <h2 class="font-headline-md text-headline-md uppercase tracking-wide text-on-surface">Encuentros</h2>
            </div>
            <span class="font-label-meta text-label-meta text-secondary uppercase bg-surface-container px-space-sm py-space-2xs rounded-full">{{ filteredPartidos.length }} juegos</span>
          </div>

          <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-md">Cargando…</div>
          <div v-else-if="!filteredPartidos.length" class="py-space-3xl text-center text-secondary font-body-md">No hay partidos.</div>

          <div
            v-for="m in filteredPartidos"
            :key="m.id"
            :class="['rounded-xl bg-surface-container-lowest p-space-xl shadow-sm relative overflow-hidden', m.estado === 'En Vivo' ? 'border-l-4 border-error' : '']"
          >
            <div class="flex flex-wrap items-center justify-between gap-space-sm mb-space-lg">
              <div class="flex items-center gap-space-xs">
                <span v-if="m.estado === 'En Vivo'" class="inline-flex items-center gap-1.5 px-space-sm py-1 rounded-full bg-error/10 text-error font-label-pill text-label-pill uppercase font-bold">
                  <span class="w-2 h-2 rounded-full bg-error animate-ping"></span> EN VIVO
                </span>
                <span v-else-if="m.estado === 'Programado'" class="px-space-sm py-1 rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase font-bold">
                  PRÓXIMO
                </span>
                <span v-else class="px-space-sm py-1 rounded-full bg-surface-container-high text-secondary font-label-pill text-label-pill uppercase font-bold">{{ m.estado.toUpperCase() }}</span>
                <span class="font-label-meta text-label-meta text-secondary uppercase">Jornada {{ m.jornada || '—' }}</span>
              </div>
              <div v-if="m.sede" class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span class="material-symbols-outlined text-[16px] text-primary">pin_drop</span>{{ m.sede }}
              </div>
            </div>

            <div class="flex items-center justify-between my-space-md px-space-2xs sm:px-space-md gap-space-sm">
              <div class="flex flex-col items-center gap-space-2xs flex-1 text-center min-w-0">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-inverse-surface flex items-center justify-center overflow-hidden p-2 shadow-md">
                  <img v-if="m.local.logo_ruta" :src="assetUrl(m.local.logo_ruta)" alt="" class="w-full h-full object-contain" />
                  <span v-else class="material-symbols-outlined text-primary-container text-[28px]">shield</span>
                </div>
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface mt-space-2xs truncate w-full">{{ m.local.nombre }}</h3>
                <span class="font-label-meta text-label-meta text-secondary">Local</span>
              </div>

              <div class="flex flex-col items-center justify-center px-space-md flex-shrink-0">
                <div v-if="m.estado !== 'Programado'" class="flex items-center gap-space-sm">
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">{{ m.marcador_local }}</span>
                  <span class="font-headline-xl text-headline-xl text-outline-variant leading-none">:</span>
                  <span class="font-stat-display text-stat-display text-primary leading-none">{{ m.marcador_visitante }}</span>
                </div>
                <div v-else class="px-space-lg py-space-2xs rounded-full bg-primary-container/20 text-on-primary-fixed font-stat-display text-headline-xl leading-none">
                  {{ m.hora || '—' }}
                </div>
                <span v-if="m.fecha" class="mt-space-2xs px-space-sm py-0.5 rounded-full bg-surface-container font-label-meta text-label-meta text-on-surface uppercase">{{ m.fecha }}</span>
              </div>

              <div class="flex flex-col items-center gap-space-2xs flex-1 text-center min-w-0">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-inverse-surface flex items-center justify-center overflow-hidden p-2 shadow-md">
                  <img v-if="m.visitante.logo_ruta" :src="assetUrl(m.visitante.logo_ruta)" alt="" class="w-full h-full object-contain" />
                  <span v-else class="material-symbols-outlined text-primary-container text-[28px]">shield</span>
                </div>
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface mt-space-2xs truncate w-full">{{ m.visitante.nombre }}</h3>
                <span class="font-label-meta text-label-meta text-secondary">Visita</span>
              </div>
            </div>

            <div v-if="m.arbitro_principal" class="mt-space-lg pt-space-md bg-surface-container-low -mx-space-xl -mb-space-xl px-space-xl pb-space-lg flex items-center gap-space-sm text-secondary font-body-sm text-body-sm rounded-b-xl">
              <span class="material-symbols-outlined text-[18px]">sports</span>
              Árbitro: {{ m.arbitro_principal }}
            </div>
          </div>
        </div>

        <!-- Tabla de posiciones -->
        <div class="lg:col-span-4 flex flex-col gap-space-lg">
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
            <h3 class="font-headline-md text-headline-md uppercase text-on-surface mb-space-md">Tabla de Posiciones</h3>
            <div v-if="!standings.length" class="font-body-sm text-body-sm text-secondary">Sin datos aún.</div>
            <div v-else class="flex flex-col gap-space-xs">
              <div v-for="(pos, idx) in standings" :key="pos.id" class="flex items-center justify-between p-space-xs bg-surface-container-low rounded-lg">
                <div class="flex items-center gap-space-xs min-w-0">
                  <span class="font-headline-md text-headline-md w-6 text-center text-on-surface">{{ idx + 1 }}</span>
                  <span class="font-body-sm text-body-sm font-semibold text-on-surface truncate">{{ pos.nombre }}</span>
                </div>
                <span class="font-headline-md text-headline-md text-primary shrink-0">{{ pos.clasificacion.record }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { assetUrl } from '../../services/assets';
import partidosService from '../../services/partidosService';
import equiposService from '../../services/equiposService';

const activeStatus = ref('all');
const loading = ref(true);
const partidos = ref([]);
const standings = ref([]);

const statusFilters = [
  { id: 'all', label: 'Todos' },
  { id: 'En Vivo', label: 'En Vivo' },
  { id: 'Programado', label: 'Próximos' },
  { id: 'Finalizado', label: 'Finalizados' }
];

const filteredPartidos = computed(() =>
  partidos.value.filter((m) => activeStatus.value === 'all' || m.estado === activeStatus.value)
);

onMounted(async () => {
  try {
    const [p, e] = await Promise.all([partidosService.list(), equiposService.list()]);
    partidos.value = p;
    standings.value = e; // ya viene ordenado por puntos_liga desde el backend
  } catch {
    partidos.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
