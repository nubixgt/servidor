<template>
  <div class="flex flex-col w-full">
    <!-- Subheader -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop pt-space-xl pb-space-md">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md">
        <div>
          <div class="flex items-center gap-space-xs mb-space-2xs">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary-container"></span>
            <span class="font-label-meta text-label-meta uppercase tracking-widest text-secondary">Temporada Oficial 2025</span>
          </div>
          <h1 class="font-headline-xl text-headline-xl uppercase text-on-surface tracking-tight">Roster & Figuras de la Liga</h1>
        </div>
        <div class="flex items-center gap-space-sm bg-surface-container-low p-space-xs rounded-full shadow-sm">
          <div class="flex items-center gap-space-xs px-space-md py-space-2xs rounded-full bg-surface-container-lowest shadow-sm">
            <span class="material-symbols-outlined text-primary text-[18px]">sports_basketball</span>
            <span class="font-label-pill text-label-pill text-on-surface">{{ total }} Atletas</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Figura destacada (mejor PPG) -->
    <section v-if="figura" class="w-full max-w-[1280px] mx-auto px-gutter-desktop my-space-md">
      <div class="relative w-full rounded-xl overflow-hidden bg-gradient-to-br from-[#ebfadc] via-surface-container-low to-[#e2f5b8] shadow-md p-space-lg lg:p-space-2xl">
        <div class="relative z-10 flex flex-col lg:flex-row gap-space-xl items-center">
          <div class="flex-1">
            <div class="inline-flex items-center gap-space-xs px-space-md py-space-2xs rounded-full bg-surface-container-lowest/90 shadow-sm mb-space-md">
              <span class="w-2 h-2 rounded-full bg-primary-container"></span>
              <span class="font-label-meta text-label-meta uppercase tracking-wider text-on-surface">Máximo anotador de la liga</span>
            </div>
            <p class="font-body-md text-body-md text-secondary uppercase font-semibold tracking-wider">{{ figura.posicion || 'Jugador' }}</p>
            <h2 class="font-headline-xl text-display-hero uppercase text-on-surface tracking-tight leading-none mt-space-2xs">{{ figura.nombre_completo }}</h2>
            <div class="flex items-center gap-space-md mt-space-xs text-secondary flex-wrap">
              <span v-if="figura.dorsal != null" class="font-headline-md text-headline-md text-primary font-bold">#{{ figura.dorsal }}</span>
              <span class="font-body-md text-body-md font-semibold text-on-surface">{{ figura.equipo_nombre || 'Sin equipo' }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-space-md mt-space-xl">
              <div class="bg-surface-container-lowest/95 rounded-lg p-space-md shadow-sm">
                <span class="font-label-meta text-label-meta text-secondary uppercase">PPG</span>
                <div class="font-stat-display text-stat-display text-on-surface leading-none">{{ figura.stats.ppg }}</div>
              </div>
              <div class="bg-surface-container-lowest/95 rounded-lg p-space-md shadow-sm">
                <span class="font-label-meta text-label-meta text-secondary uppercase">RPG</span>
                <div class="font-stat-display text-stat-display text-on-surface leading-none">{{ figura.stats.rpg }}</div>
              </div>
              <div class="bg-surface-container-lowest/95 rounded-lg p-space-md shadow-sm">
                <span class="font-label-meta text-label-meta text-secondary uppercase">APG</span>
                <div class="font-stat-display text-stat-display text-on-surface leading-none">{{ figura.stats.apg }}</div>
              </div>
              <div class="bg-surface-container-lowest/95 rounded-lg p-space-md shadow-sm">
                <span class="font-label-meta text-label-meta text-secondary uppercase">3P%</span>
                <div class="font-stat-display text-stat-display text-on-surface leading-none">{{ figura.stats.tres_pct }}</div>
              </div>
            </div>
          </div>
          <div v-if="figura.foto_ruta" class="lg:w-80 flex items-center justify-center">
            <img :src="assetUrl(figura.foto_ruta)" :alt="figura.nombre_completo" class="w-full max-h-[420px] object-contain drop-shadow-[0_20px_35px_rgba(0,0,0,0.18)]" />
          </div>
        </div>
      </div>
    </section>

    <!-- Filtros -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop pt-space-xl pb-space-md">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-lg bg-surface-container-low p-space-md rounded-xl">
        <div class="flex items-center gap-space-xs overflow-x-auto py-space-2xs">
          <button
            v-for="pos in positionFilters"
            :key="pos"
            @click="activePosition = pos"
            :class="[
              'px-space-lg py-space-xs rounded-full font-label-pill text-label-pill uppercase shadow-sm transition-all whitespace-nowrap',
              activePosition === pos
                ? 'bg-primary-container text-on-primary-fixed font-semibold'
                : 'bg-surface-container-lowest text-secondary hover:text-on-surface'
            ]"
          >{{ pos }}</button>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-space-sm w-full lg:w-auto">
          <div class="relative w-full sm:w-72">
            <span class="material-symbols-outlined absolute left-space-md top-1/2 -translate-y-1/2 text-secondary text-[20px]">search</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar por atleta o dorsal..."
              class="w-full bg-surface-container-lowest pl-11 pr-space-md py-space-xs rounded-full text-on-surface font-body-sm text-body-sm placeholder:text-secondary outline-none focus:ring-2 focus:ring-primary shadow-sm"
            />
          </div>
          <div class="relative w-full sm:w-56">
            <select
              v-model="selectedTeam"
              class="w-full appearance-none bg-surface-container-lowest pl-space-md pr-10 py-space-xs rounded-full text-on-surface font-body-sm text-body-sm outline-none focus:ring-2 focus:ring-primary shadow-sm cursor-pointer"
            >
              <option value="all">Todas las franquicias</option>
              <option v-for="e in equiposDisponibles" :key="e" :value="e">{{ e }}</option>
            </select>
            <span class="material-symbols-outlined absolute right-space-md top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Grid -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop py-space-md mb-space-3xl">
      <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-md">Cargando jugadores…</div>
      <div v-else-if="!filteredJugadores.length" class="py-space-3xl text-center text-secondary font-body-md">No hay jugadores para mostrar.</div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
        <div
          v-for="player in filteredJugadores"
          :key="player.id"
          class="player-card group bg-surface-container-lowest rounded-lg p-space-md shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col"
        >
          <div class="flex items-center justify-between mb-space-xs">
            <div class="flex items-center gap-space-2xs">
              <span class="px-space-sm py-space-2xs rounded-full bg-surface-container-low font-label-meta text-label-meta uppercase text-secondary">{{ player.posicion || 'S/P' }}</span>
              <span v-if="player.dorsal != null" class="text-primary font-headline-md text-headline-md font-bold">#{{ player.dorsal }}</span>
            </div>
          </div>

          <div class="relative w-full h-56 rounded-lg bg-surface-container-low overflow-hidden flex items-end justify-center mb-space-md group-hover:bg-[#f2f8d8] transition-colors">
            <span v-if="player.dorsal != null" class="absolute top-3 left-3 font-stat-display text-stat-display text-surface-container-highest select-none">{{ player.dorsal }}</span>
            <img v-if="player.foto_ruta" :src="assetUrl(player.foto_ruta)" :alt="player.nombre_completo" class="relative z-10 max-h-52 object-contain group-hover:scale-105 transition-transform duration-300" />
            <span v-else class="material-symbols-outlined text-[72px] text-outline-variant relative z-10 mb-space-md">person</span>
            <div class="absolute bottom-2 right-2 px-space-sm py-space-2xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill shadow-sm">
              {{ player.stats.ppg }} PPG
            </div>
          </div>

          <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface tracking-tight leading-snug truncate">{{ player.nombre_completo }}</h3>
          <p class="font-body-sm text-body-sm text-secondary font-medium">{{ player.equipo_nombre || 'Sin equipo' }}</p>

          <div class="grid grid-cols-3 gap-space-2xs bg-surface-container-low/70 rounded-md p-space-xs mt-space-sm">
            <div class="text-center">
              <span class="block font-label-meta text-label-meta uppercase text-secondary">APG</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ player.stats.apg }}</span>
            </div>
            <div class="text-center">
              <span class="block font-label-meta text-label-meta uppercase text-secondary">RPG</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ player.stats.rpg }}</span>
            </div>
            <div class="text-center">
              <span class="block font-label-meta text-label-meta uppercase text-secondary">3P%</span>
              <span class="font-headline-md text-headline-md text-primary">{{ player.stats.tres_pct }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { assetUrl } from '../../services/assets';
import jugadoresService from '../../services/jugadoresService';

const activePosition = ref('Todos');
const searchQuery = ref('');
const selectedTeam = ref('all');
const loading = ref(true);
const jugadores = ref([]);
const total = ref(0);

const positionFilters = ['Todos', 'Base', 'Escolta', 'Alero', 'Ala-Pívot', 'Pívot'];

const equiposDisponibles = computed(() =>
  [...new Set(jugadores.value.map((j) => j.equipo_nombre).filter(Boolean))].sort()
);

const figura = computed(() => {
  if (!jugadores.value.length) return null;
  return [...jugadores.value].sort((a, b) => b.stats.ppg - a.stats.ppg)[0];
});

const filteredJugadores = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  return jugadores.value.filter((p) => {
    const matchesPos = activePosition.value === 'Todos' || p.posicion === activePosition.value;
    const matchesTeam = selectedTeam.value === 'all' || p.equipo_nombre === selectedTeam.value;
    const matchesSearch =
      !query ||
      p.nombre_completo.toLowerCase().includes(query) ||
      String(p.dorsal ?? '').includes(query);
    return matchesPos && matchesTeam && matchesSearch;
  });
});

onMounted(async () => {
  try {
    const res = await jugadoresService.list();
    jugadores.value = res.items;
    total.value = res.total;
  } catch {
    jugadores.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
