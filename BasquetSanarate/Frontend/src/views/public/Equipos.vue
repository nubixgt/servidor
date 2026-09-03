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
          <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
          <span class="font-label-pill text-label-pill text-on-surface-variant">Jornada 16</span>
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
              'px-space-lg py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-all duration-200',
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

    <!-- Grid de Equipos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg relative z-10">
      <div 
        v-for="team in filteredEquipos" 
        :key="team.id"
        class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between relative overflow-hidden group"
      >
        <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-primary-container/20 blur-2xl group-hover:bg-primary-container/30 transition-all"></div>
        <div>
          <div class="flex items-center justify-between mb-space-md relative z-10">
            <span class="font-headline-md text-headline-md text-on-surface bg-surface-container px-space-sm py-0.5 rounded-full">{{ team.rank }}</span>
            <div class="flex items-center gap-space-2xs bg-primary-container/30 text-on-primary-container px-space-sm py-1 rounded-full font-label-pill text-label-pill">
              <span class="w-2 h-2 rounded-full bg-primary"></span>
              {{ team.streak }}
            </div>
          </div>
          
          <div class="flex items-center gap-space-md mb-space-lg">
            <div class="w-16 h-16 rounded-full bg-inverse-surface flex items-center justify-center text-primary-container font-headline-lg text-headline-lg shadow-md flex-shrink-0">
              <span class="material-symbols-outlined text-[32px] text-primary-container">{{ team.icon }}</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-label-meta text-label-meta uppercase tracking-wider text-secondary">{{ team.sede }}</span>
              <h2 class="font-headline-lg text-headline-lg uppercase text-on-surface truncate leading-tight">{{ team.nombre }}</h2>
              <div class="flex items-center gap-1 mt-1">
                <span class="inline-block bg-surface-container-high text-secondary px-space-xs py-0.5 rounded-full font-label-meta text-label-meta uppercase">{{ team.categoria }}</span>
                <span class="inline-block bg-surface-container-high text-secondary px-space-xs py-0.5 rounded-full font-label-meta text-label-meta uppercase">{{ team.conferencia }}</span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-space-xs p-space-xs bg-surface-container rounded-lg mb-space-lg">
            <div class="flex flex-col items-center justify-center p-space-xs bg-surface-container-lowest rounded-DEFAULT text-center shadow-sm">
              <span class="font-label-meta text-label-meta uppercase text-secondary">Récord</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ team.record }}</span>
              <span class="font-label-meta text-[9px] text-primary">{{ team.pct }} PCT</span>
            </div>
            <div class="flex flex-col items-center justify-center p-space-xs bg-surface-container-lowest rounded-DEFAULT text-center shadow-sm">
              <span class="font-label-meta text-label-meta uppercase text-secondary">Ataque</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ team.ppg }}</span>
              <span class="font-label-meta text-[9px] text-secondary">PPG</span>
            </div>
            <div class="flex flex-col items-center justify-center p-space-xs bg-surface-container-lowest rounded-DEFAULT text-center shadow-sm">
              <span class="font-label-meta text-label-meta uppercase text-secondary">Defensa</span>
              <span class="font-headline-md text-headline-md text-on-surface">{{ team.oppg }}</span>
              <span class="font-label-meta text-[9px] text-secondary">OPP PPG</span>
            </div>
          </div>

          <div class="flex items-center justify-between py-space-xs px-space-sm bg-surface-container-low rounded-DEFAULT mb-space-lg">
            <div class="flex items-center gap-space-xs min-w-0">
              <div class="w-6 h-6 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed font-label-meta text-label-meta font-bold">#{{ team.capitanNum }}</div>
              <span class="font-body-sm text-body-sm text-on-surface truncate">{{ team.capitan }}</span>
            </div>
            <span class="font-label-pill text-label-pill text-primary">{{ team.capitanStat }}</span>
          </div>
        </div>

        <button class="w-full py-space-sm rounded-full bg-inverse-surface text-surface font-label-pill text-label-pill uppercase hover:bg-primary-container hover:text-on-primary-fixed hover:shadow-[0_4px_16px_rgba(204,255,0,0.35)] transition-all flex items-center justify-center gap-space-xs group-hover:scale-[1.01]">
          <span>Ver Plantel & Estadísticas</span>
          <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const activeFilter = ref('all');
const searchQuery = ref('');

const filters = [
  { id: 'all', label: 'Todos (6)' },
  { id: 'masculino', label: 'Masculina Mayor' },
  { id: 'femenino', label: 'Femenina Libre' },
  { id: 'norte', label: 'Conf. Norte' },
  { id: 'sur', label: 'Conf. Sur' }
];

const equipos = ref([
  {
    id: 1,
    rank: '#1',
    nombre: 'Toros de Sanarate',
    sede: 'Sanarate Central',
    categoria: 'Masculino Mayor',
    categoriaId: 'masculino',
    conferencia: 'Norte',
    conferenciaId: 'norte',
    streak: '6V SEGUIDAS',
    icon: 'sports_basketball',
    record: '14 - 1',
    pct: '.933',
    ppg: '88.4',
    oppg: '71.2',
    capitan: 'M. Morales (Capitán)',
    capitanNum: '23',
    capitanStat: '24.2 PPG'
  },
  {
    id: 2,
    rank: '#2',
    nombre: 'Halcones Dorados',
    sede: 'El Barranco',
    categoria: 'Masculino Mayor',
    categoriaId: 'masculino',
    conferencia: 'Norte',
    conferenciaId: 'norte',
    streak: '3V SEGUIDAS',
    icon: 'military_tech',
    record: '12 - 3',
    pct: '.800',
    ppg: '82.1',
    oppg: '73.8',
    capitan: 'J. Aldana (Base)',
    capitanNum: '07',
    capitanStat: '8.4 APG'
  },
  {
    id: 3,
    rank: '#1 FEM',
    nombre: 'Cobras del Valle',
    sede: 'Valle de la Cruz',
    categoria: 'Femenino Libre',
    categoriaId: 'femenino',
    conferencia: 'Sur',
    conferenciaId: 'sur',
    streak: '8V SEGUIDAS',
    icon: 'bolt',
    record: '11 - 1',
    pct: '.916',
    ppg: '76.3',
    oppg: '59.8',
    capitan: 'S. Cifuentes (Escolta)',
    capitanNum: '11',
    capitanStat: '19.8 PPG'
  },
  {
    id: 4,
    rank: '#3',
    nombre: 'Jaguares Oriente',
    sede: 'San Antonio Oriente',
    categoria: 'Masculino Mayor',
    categoriaId: 'masculino',
    conferencia: 'Sur',
    conferenciaId: 'sur',
    streak: '1V SEGUIDA',
    icon: 'cruelty_free',
    record: '10 - 5',
    pct: '.666',
    ppg: '79.5',
    oppg: '75.1',
    capitan: 'R. Estrada (Alero)',
    capitanNum: '14',
    capitanStat: '16.5 PPG'
  },
  {
    id: 5,
    rank: '#4',
    nombre: 'Titanes del Barrio',
    sede: 'Barrio San Jerónimo',
    categoria: 'Masculino Mayor',
    categoriaId: 'masculino',
    conferencia: 'Norte',
    conferenciaId: 'norte',
    streak: '2D SEGUIDAS',
    icon: 'shield',
    record: '8 - 7',
    pct: '.533',
    ppg: '74.2',
    oppg: '76.0',
    capitan: 'K. López (Pívot)',
    capitanNum: '33',
    capitanStat: '11.2 RPG'
  },
  {
    id: 6,
    rank: '#2 FEM',
    nombre: 'Leonas de Sanarate',
    sede: 'Gimnasio Municipal',
    categoria: 'Femenino Libre',
    categoriaId: 'femenino',
    conferencia: 'Norte',
    conferenciaId: 'norte',
    streak: '4V SEGUIDAS',
    icon: 'workspace_premium',
    record: '9 - 3',
    pct: '.750',
    ppg: '71.0',
    oppg: '62.4',
    capitan: 'A. Méndez (Base)',
    capitanNum: '05',
    capitanStat: '15.4 PPG'
  }
]);

const filteredEquipos = computed(() => {
  return equipos.value.filter(team => {
    // Filtro por categoría / conferencia
    const matchesFilter = 
      activeFilter.value === 'all' || 
      team.categoriaId === activeFilter.value || 
      team.conferenciaId === activeFilter.value;

    // Filtro por texto de búsqueda
    const query = searchQuery.value.toLowerCase().trim();
    const matchesSearch = 
      !query || 
      team.nombre.toLowerCase().includes(query) || 
      team.sede.toLowerCase().includes(query);

    return matchesFilter && matchesSearch;
  });
});
</script>
