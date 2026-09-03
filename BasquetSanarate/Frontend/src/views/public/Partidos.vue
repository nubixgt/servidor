<template>
  <div class="relative w-full overflow-hidden">
    <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary-container/20 blur-3xl pointer-events-none"></div>
    <div class="absolute top-96 -left-32 w-80 h-80 rounded-full bg-tertiary-container/15 blur-3xl pointer-events-none"></div>
    <span class="absolute right-6 top-8 select-none font-display-hero text-[140px] leading-none uppercase text-surface-container-highest/40 font-bold tracking-tighter pointer-events-none">FIXTURE</span>

    <div class="max-w-[1280px] mx-auto px-gutter-desktop py-space-xl relative z-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md mb-space-xl">
        <div class="flex flex-col gap-space-2xs">
          <div class="flex items-center gap-space-xs">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-container animate-ping"></span>
            <span class="font-label-meta text-label-meta uppercase tracking-widest text-primary">Temporada Regular & Playoffs 2025</span>
          </div>
          <h1 class="font-headline-xl text-headline-xl uppercase tracking-tight text-on-surface">
            Calendario & <span class="text-primary font-bold">Fixture Oficial</span>
          </h1>
          <p class="font-body-md text-body-md text-secondary max-w-xl">
            Sigue cada enfrentamiento de la Liga Sanarateca. Marcadores en tiempo real, horarios confirmados y duela asignada.
          </p>
        </div>

        <!-- Filter Status Pills -->
        <div class="flex items-center gap-space-xs bg-surface-container-low p-space-2xs rounded-full shadow-sm">
          <button 
            v-for="filter in statusFilters" 
            :key="filter.id"
            @click="activeStatus = filter.id"
            :class="[
              'px-space-md py-space-2xs rounded-full font-label-pill text-label-pill transition-all duration-200',
              activeStatus === filter.id 
                ? 'bg-primary-container text-on-primary-fixed shadow-sm font-semibold' 
                : 'text-secondary hover:text-on-surface'
            ]"
          >
            {{ filter.label }}
            <span v-if="filter.id === 'live'" class="inline-block w-2 h-2 rounded-full bg-error ml-1"></span>
          </button>
        </div>
      </div>

      <!-- Rounds Bar -->
      <div class="relative w-full mb-space-2xl">
        <div class="flex items-center gap-space-xs overflow-x-auto pb-space-xs scrollbar-none">
          <button 
            v-for="jornada in jornadas"
            :key="jornada.id"
            @click="activeJornada = jornada.id"
            :class="[
              'whitespace-nowrap px-space-md py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-colors',
              activeJornada === jornada.id
                ? 'bg-inverse-surface text-surface shadow-md'
                : 'bg-surface-container-low text-secondary hover:bg-surface-container hover:text-on-surface'
            ]"
          >
            {{ jornada.label }}
          </button>
        </div>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-start">
        <div class="lg:col-span-8 flex flex-col gap-space-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-space-xs">
              <span class="material-symbols-outlined text-primary text-[20px]">sports_basketball</span>
              <h2 class="font-headline-md text-headline-md uppercase tracking-wide text-on-surface">Encuentros Seleccionados</h2>
            </div>
            <span class="font-label-meta text-label-meta text-secondary uppercase bg-surface-container px-space-sm py-space-2xs rounded-full">{{ filteredPartidos.length }} Juegos Encontrados</span>
          </div>

          <!-- Partido Item: Live -->
          <div 
            v-for="match in filteredPartidos" 
            :key="match.id"
            :class="[
              'rounded-xl bg-surface-container-lowest p-space-xl shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group',
              match.status === 'live' ? 'border-l-4 border-error' : ''
            ]"
          >
            <div class="flex flex-wrap items-center justify-between gap-space-sm mb-space-lg">
              <div class="flex items-center gap-space-xs">
                <span 
                  v-if="match.status === 'live'"
                  class="inline-flex items-center gap-1.5 px-space-sm py-1 rounded-full bg-error/10 text-error font-label-pill text-label-pill uppercase font-bold"
                >
                  <span class="w-2 h-2 rounded-full bg-error animate-ping"></span>
                  EN VIVO • {{ match.periodo }}
                </span>
                <span 
                  v-else-if="match.status === 'upcoming'"
                  class="px-space-sm py-1 rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase font-bold"
                >
                  PRÓXIMO • {{ match.fecha }}
                </span>
                <span 
                  v-else
                  class="px-space-sm py-1 rounded-full bg-surface-container-high text-secondary font-label-pill text-label-pill uppercase font-bold"
                >
                  FINALIZADO
                </span>
                <span class="font-label-meta text-label-meta text-secondary uppercase">{{ match.jornadaLabel }}</span>
              </div>
              <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span class="material-symbols-outlined text-[16px] text-primary">pin_drop</span>
                {{ match.cancha }}
              </div>
            </div>

            <!-- Teams & Score Display -->
            <div class="flex items-center justify-between my-space-md px-space-2xs sm:px-space-md">
              <!-- Team Local -->
              <div class="flex flex-col items-center gap-space-2xs flex-1 text-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center font-headline-lg text-headline-lg shadow-md group-hover:scale-105 transition-transform duration-200">
                  {{ match.localCode }}
                </div>
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface mt-space-2xs">{{ match.localNombre }}</h3>
                <span class="font-label-meta text-label-meta text-secondary">Local ({{ match.localRecord }})</span>
              </div>

              <!-- Score / Time -->
              <div class="flex flex-col items-center justify-center px-space-md flex-shrink-0">
                <div v-if="match.status !== 'upcoming'" class="flex items-center gap-space-sm">
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">{{ match.localScore }}</span>
                  <span class="font-headline-xl text-headline-xl text-outline-variant leading-none">:</span>
                  <span class="font-stat-display text-stat-display text-primary leading-none">{{ match.visitaScore }}</span>
                </div>
                <div v-else class="px-space-lg py-space-2xs rounded-full bg-primary-container/20 text-on-primary-fixed font-stat-display text-headline-xl leading-none">
                  {{ match.hora }}
                </div>
                <span class="mt-space-2xs px-space-sm py-0.5 rounded-full bg-surface-container font-label-meta text-label-meta text-on-surface uppercase">
                  {{ match.nota }}
                </span>
              </div>

              <!-- Team Visita -->
              <div class="flex flex-col items-center gap-space-2xs flex-1 text-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center shadow-md group-hover:scale-105 transition-transform duration-200">
                  {{ match.visitaCode }}
                </div>
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface mt-space-2xs">{{ match.visitaNombre }}</h3>
                <span class="font-label-meta text-label-meta text-secondary">Visita ({{ match.visitaRecord }})</span>
              </div>
            </div>

            <!-- Footer Card Info -->
            <div class="mt-space-lg pt-space-md bg-surface-container-low -mx-space-xl -mb-space-xl px-space-xl pb-space-lg flex flex-wrap items-center justify-between gap-space-md rounded-b-xl">
              <div class="flex items-center gap-space-sm text-secondary font-body-sm text-body-sm">
                <span class="material-symbols-outlined text-[18px]">stadium</span>
                {{ match.detalle }}
              </div>
              <div class="flex items-center gap-space-xs">
                <button class="px-space-md py-space-2xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase hover:scale-105 transition-all shadow-sm">
                  Ficha Técnica
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar Widget: Standings Snippet -->
        <div class="lg:col-span-4 flex flex-col gap-space-lg">
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
            <h3 class="font-headline-md text-headline-md uppercase text-on-surface mb-space-md">Tabla de Posiciones</h3>
            <div class="flex flex-col gap-space-xs">
              <div v-for="(pos, idx) in posiciones" :key="idx" class="flex items-center justify-between p-space-xs bg-surface-container-low rounded-lg">
                <div class="flex items-center gap-space-xs">
                  <span class="font-headline-md text-headline-md w-6 text-center text-on-surface">{{ idx + 1 }}</span>
                  <span class="font-body-sm text-body-sm font-semibold text-on-surface">{{ pos.equipo }}</span>
                </div>
                <span class="font-headline-md text-headline-md text-primary">{{ pos.record }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const activeStatus = ref('all');
const activeJornada = ref('j12');

const statusFilters = [
  { id: 'all', label: 'Todos' },
  { id: 'live', label: 'En Vivo' },
  { id: 'upcoming', label: 'Próximos' },
  { id: 'completed', label: 'Finalizados' }
];

const jornadas = [
  { id: 'j12', label: 'Jornada 12 (Actual)' },
  { id: 'j11', label: 'Jornada 11' },
  { id: 'j13', label: 'Jornada 13' },
  { id: 'playoffs', label: 'Playoffs' }
];

const partidos = ref([
  {
    id: 1,
    status: 'live',
    periodo: '4TO CUARTO (02:41)',
    jornadaLabel: 'Jornada 12',
    cancha: 'Duela Central Polideportivo Sanarate',
    localCode: 'TR',
    localNombre: 'Toros del Norte',
    localRecord: '14-1',
    localScore: '88',
    visitaCode: 'HC',
    visitaNombre: 'Halcones Sanarate',
    visitaRecord: '12-3',
    visitaScore: '82',
    nota: 'Clásico de la Liga',
    detalle: 'Entrada General: Q15.00 • Árbitro: C. Morales'
  },
  {
    id: 2,
    status: 'upcoming',
    fecha: 'HOY',
    hora: '07:30 PM',
    jornadaLabel: 'Jornada 12',
    cancha: 'Cancha Municipal Barrio El Centro',
    localCode: 'JG',
    localNombre: 'Jaguares Oriente',
    localRecord: '10-5',
    visitaCode: 'CL',
    visitaNombre: 'Club Lobos',
    visitaRecord: '9-6',
    nota: 'En 2 horas',
    detalle: 'Transmisión Oficial Sanarate TV'
  },
  {
    id: 3,
    status: 'completed',
    jornadaLabel: 'Jornada 12 • Ayer',
    cancha: 'Gimnasio Municipal Minerva',
    localCode: 'VI',
    localNombre: 'Vipers Sanarate',
    localRecord: '7-8',
    localScore: '64',
    visitaCode: 'RT',
    visitaNombre: 'Raptors del Valle',
    visitaRecord: '8-7',
    visitaScore: '79',
    nota: 'Victoria Visitante',
    detalle: 'MVP: M. Kiatipis (26 PTS, 11 REB)'
  }
]);

const posiciones = [
  { equipo: 'Toros del Norte', record: '14-1' },
  { equipo: 'Halcones Sanarate', record: '12-3' },
  { equipo: 'Cobras del Valle', record: '11-1' },
  { equipo: 'Jaguares Oriente', record: '10-5' },
  { equipo: 'Club Lobos', record: '9-6' }
];

const filteredPartidos = computed(() => {
  return partidos.value.filter(match => {
    if (activeStatus.value === 'all') return true;
    return match.status === activeStatus.value;
  });
});
</script>
