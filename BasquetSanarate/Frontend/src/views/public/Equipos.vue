<template>
  <div class="relative w-full max-w-[1280px] mx-auto px-gutter-desktop py-space-xl overflow-hidden">
    <!-- Marca de agua gigante -->
    <div class="absolute -top-10 right-4 pointer-events-none select-none opacity-[0.03] text-on-surface font-display-hero text-[180px] leading-none uppercase tracking-tighter">
      TEAMS
    </div>

    <!-- Encabezado de la página -->
    <div class="relative z-10 flex flex-col gap-space-lg mb-space-2xl">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md">
        <div>
          <div class="flex items-center gap-space-2xs mb-space-2xs">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-container shadow-[0_0_10px_rgba(204,255,0,0.8)]"></span>
            <span class="font-label-meta text-label-meta uppercase tracking-widest text-secondary">Temporada Regular 2025</span>
          </div>
          <h1 class="font-headline-xl text-headline-xl uppercase text-on-surface tracking-tight">Equipos en Competencia</h1>
          <p class="font-body-md text-body-md text-secondary max-w-xl">
            Clubes oficiales inscritos en la Liga Sanarateca. Analiza estadísticas avanzadas de franquicia, rachas y plantillas completas.
          </p>
        </div>
        <div class="flex items-center gap-space-sm bg-surface-container px-space-md py-space-xs rounded-full self-start md:self-auto shadow-sm">
          <span class="font-label-meta text-label-meta text-secondary uppercase">Activos:</span>
          <span class="font-headline-md text-headline-md text-on-surface leading-none">{{ equipos.length }} Clubes</span>
        </div>
      </div>

      <!-- Filtros y Búsqueda -->
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-space-md pt-space-xs">
        <div class="flex items-center gap-space-xs overflow-x-auto pb-space-2xs scrollbar-none">
          <button
            v-for="filter in filters"
            :key="filter.id"
            @click="activeFilter = filter.id"
            :class="[
              'px-space-lg py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-all duration-200 whitespace-nowrap',
              activeFilter === filter.id
                ? 'bg-primary-container text-on-primary-fixed shadow-[0_4px_16px_rgba(204,255,0,0.4)]'
                : 'bg-surface-container-high text-secondary hover:bg-surface-container-highest'
            ]"
          >
            {{ filter.label }}
          </button>
        </div>

        <div class="relative w-full lg:w-72">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar franquicia o sede..."
            class="w-full bg-surface-container-high text-on-surface font-body-sm text-body-sm rounded-full pl-11 pr-space-md py-space-xs placeholder:text-secondary focus:outline-none focus:bg-surface-container-lowest focus:shadow-md transition-all"
          />
          <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-secondary text-[20px]">
            search
          </span>
        </div>
      </div>
    </div>

    <!-- Estados -->
    <div v-if="loading" class="relative z-10 py-space-3xl text-center text-secondary font-body-md">Cargando equipos…</div>
    <div v-else-if="!filteredEquipos.length" class="relative z-10 py-space-3xl text-center text-secondary font-body-md">
      No hay equipos para mostrar.
    </div>

    <!-- Grid de Equipos -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg relative z-10">
      <div
        v-for="(team, idx) in filteredEquipos"
        :key="team.id"
        class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between relative overflow-hidden group"
      >
        <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-primary-container/20 blur-2xl group-hover:bg-primary-container/30 transition-all"></div>
        <div>
          <div class="flex items-center justify-between mb-space-md relative z-10">
            <span class="font-headline-md text-headline-md text-on-surface bg-surface-container px-space-sm py-0.5 rounded-full">#{{ idx + 1 }}</span>
            <div v-if="team.clasificacion.racha" class="flex items-center gap-space-2xs bg-primary-container/30 text-on-primary-container px-space-sm py-1 rounded-full font-label-pill text-label-pill">
              <span class="w-2 h-2 rounded-full bg-primary"></span>
              {{ team.clasificacion.racha }}
            </div>
          </div>

          <div class="flex items-center gap-space-md mb-space-lg">
            <div
              class="w-16 h-16 rounded-full bg-inverse-surface flex items-center justify-center shadow-md flex-shrink-0 overflow-hidden p-1.5 border-4"
              :style="{ borderColor: team.color_hex || 'rgba(204,255,0,0.35)' }"
            >
              <img v-if="team.logo_ruta" :src="assetUrl(team.logo_ruta)" alt="" class="w-full h-full object-contain" />
              <span v-else class="material-symbols-outlined text-[32px] text-primary-container">shield</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-label-meta text-label-meta uppercase tracking-wider text-secondary">{{ team.sede }}</span>
              <h2 class="font-headline-lg text-headline-lg uppercase text-on-surface truncate leading-tight">{{ team.nombre }}</h2>
              <div class="flex items-center gap-1 mt-1 flex-wrap">
                <span class="inline-block bg-surface-container-high text-secondary px-space-xs py-0.5 rounded-full font-label-meta text-label-meta uppercase">{{ team.rama }}</span>
                <span class="inline-block bg-surface-container-high text-secondary px-space-xs py-0.5 rounded-full font-label-meta text-label-meta uppercase">Conf. {{ team.conferencia }}</span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-space-xs p-space-xs bg-surface-container rounded-lg mb-space-lg">
            <div class="flex flex-col items-center justify-center p-space-xs bg-surface-container-lowest rounded-DEFAULT text-center shadow-sm">
              <span class="font-label-meta text-label-meta uppercase text-secondary">Récord</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ team.clasificacion.record }}</span>
              <span class="font-label-meta text-[9px] text-primary">{{ team.clasificacion.pct }} PCT</span>
            </div>
            <div class="flex flex-col items-center justify-center p-space-xs bg-surface-container-lowest rounded-DEFAULT text-center shadow-sm">
              <span class="font-label-meta text-label-meta uppercase text-secondary">Ataque</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ team.clasificacion.ppg }}</span>
              <span class="font-label-meta text-[9px] text-secondary">PPG</span>
            </div>
            <div class="flex flex-col items-center justify-center p-space-xs bg-surface-container-lowest rounded-DEFAULT text-center shadow-sm">
              <span class="font-label-meta text-label-meta uppercase text-secondary">Defensa</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ team.clasificacion.oppg }}</span>
              <span class="font-label-meta text-[9px] text-secondary">OPP PPG</span>
            </div>
          </div>

          <div class="flex items-center justify-between py-space-xs px-space-sm bg-surface-container-low rounded-DEFAULT mb-space-lg">
            <div class="flex items-center gap-space-xs min-w-0">
              <span class="material-symbols-outlined text-[18px] text-primary">sports</span>
              <span class="font-body-sm text-body-sm text-on-surface truncate">{{ team.director_tecnico }}</span>
            </div>
            <span class="font-label-pill text-label-pill text-primary shrink-0">{{ team.jugadores_count }} jug.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { assetUrl } from '../../services/assets';
import equiposService from '../../services/equiposService';

const activeFilter = ref('all');
const searchQuery = ref('');
const loading = ref(true);
const equipos = ref([]);

const filters = [
  { id: 'all', label: 'Todos' },
  { id: 'Masculina Mayor', label: 'Masculina Mayor' },
  { id: 'Femenina Libre', label: 'Femenina Libre' },
  { id: 'Juvenil Sub-18', label: 'Sub-18' },
  { id: 'Norte', label: 'Conf. Norte' },
  { id: 'Sur', label: 'Conf. Sur' }
];

const filteredEquipos = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  return equipos.value.filter((team) => {
    const matchesFilter =
      activeFilter.value === 'all' ||
      team.rama === activeFilter.value ||
      team.conferencia === activeFilter.value;
    const matchesSearch =
      !query ||
      team.nombre.toLowerCase().includes(query) ||
      team.sede.toLowerCase().includes(query);
    return matchesFilter && matchesSearch;
  });
});

onMounted(async () => {
  try {
    equipos.value = await equiposService.list();
  } catch {
    equipos.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
