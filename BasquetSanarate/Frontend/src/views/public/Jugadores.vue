<template>
  <div class="flex flex-col w-full">
    <!-- Subheader & Quick Overview -->
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
            <span class="font-label-pill text-label-pill text-on-surface">148 Atletas</span>
          </div>
          <div class="flex items-center gap-space-xs px-space-md py-space-2xs rounded-full bg-surface-container-lowest shadow-sm">
            <span class="material-symbols-outlined text-primary text-[18px]">verified</span>
            <span class="font-label-pill text-label-pill text-on-surface">12 Franquicias</span>
          </div>
        </div>
      </div>
    </section>

    <!-- MVP SPOTLIGHT CARD -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop my-space-md">
      <div class="relative w-full rounded-xl overflow-hidden bg-gradient-to-br from-[#ebfadc] via-surface-container-low to-[#e2f5b8] shadow-md p-space-lg lg:p-space-2xl">
        <div class="absolute -right-10 top-1/4 select-none pointer-events-none opacity-[0.06] text-inverse-surface font-headline-xl text-[140px] font-black uppercase leading-none tracking-tighter">
          BASKETBALL
        </div>
        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-center">
          <div class="lg:col-span-6 flex flex-col justify-between h-full">
            <div>
              <div class="inline-flex items-center gap-space-xs px-space-md py-space-2xs rounded-full bg-surface-container-lowest/90 backdrop-blur-sm shadow-sm mb-space-md">
                <span class="w-2 h-2 rounded-full bg-primary-container"></span>
                <span class="font-label-meta text-label-meta uppercase tracking-wider text-on-surface">Jugador de la Semana • MVP Ronda 12</span>
              </div>
              <p class="font-body-md text-body-md text-secondary uppercase font-semibold tracking-wider">Alero Titular</p>
              <h2 class="font-headline-xl text-display-hero uppercase text-on-surface tracking-tight leading-none mt-space-2xs">
                Mateo Kiatipis
              </h2>
              <div class="flex items-center gap-space-md mt-space-xs text-secondary">
                <span class="font-headline-md text-headline-md text-primary font-bold">#11</span>
                <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                <span class="font-body-md text-body-md font-semibold text-on-surface">Halcones de Sanarate</span>
                <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                <span class="font-label-pill text-label-pill text-secondary">2.01 m • 96 kg</span>
              </div>
            </div>

            <!-- Soft Stat Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-2 gap-space-md mt-space-xl">
              <div class="bg-surface-container-lowest/95 backdrop-blur-sm rounded-lg p-space-lg shadow-sm flex flex-col justify-between hover:scale-[1.02] transition-transform duration-200">
                <div class="flex items-center justify-between">
                  <span class="font-label-meta text-label-meta text-secondary uppercase">Win Rate</span>
                  <span class="material-symbols-outlined text-primary text-[18px]">verified</span>
                </div>
                <div class="my-space-xs">
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">84%</span>
                </div>
                <div class="flex items-center gap-space-2xs text-secondary pt-space-2xs">
                  <span class="material-symbols-outlined text-[14px] text-primary">auto_awesome</span>
                  <span class="font-body-sm text-body-sm leading-tight text-on-surface-variant">Pilar ofensivo de la escuadra</span>
                </div>
              </div>

              <div class="bg-surface-container-lowest/95 backdrop-blur-sm rounded-lg p-space-lg shadow-sm flex flex-col justify-between hover:scale-[1.02] transition-transform duration-200">
                <div class="flex items-center justify-between">
                  <span class="font-label-meta text-label-meta text-secondary uppercase">3-Point Accuracy</span>
                  <span class="material-symbols-outlined text-primary text-[18px]">radar</span>
                </div>
                <div class="my-space-xs">
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">48%</span>
                </div>
                <div class="flex items-center gap-space-2xs text-secondary pt-space-2xs">
                  <span class="material-symbols-outlined text-[14px] text-primary">auto_awesome</span>
                  <span class="font-body-sm text-body-sm leading-tight text-on-surface-variant">Peligro perimetral absoluto</span>
                </div>
              </div>

              <div class="bg-surface-container-lowest/95 backdrop-blur-sm rounded-lg p-space-lg shadow-sm flex flex-col justify-between hover:scale-[1.02] transition-transform duration-200">
                <div class="flex items-center justify-between">
                  <span class="font-label-meta text-label-meta text-secondary uppercase">Promedio Anotación</span>
                  <span class="material-symbols-outlined text-primary text-[18px]">trending_up</span>
                </div>
                <div class="my-space-xs">
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">28.4</span>
                  <span class="font-label-pill text-label-pill text-secondary uppercase ml-space-2xs">PPG</span>
                </div>
                <div class="flex items-center gap-space-2xs text-secondary pt-space-2xs">
                  <span class="material-symbols-outlined text-[14px] text-primary">local_fire_department</span>
                  <span class="font-body-sm text-body-sm leading-tight text-on-surface-variant">Líder goleador del torneo</span>
                </div>
              </div>

              <div class="bg-surface-container-lowest/95 backdrop-blur-sm rounded-lg p-space-lg shadow-sm flex flex-col justify-between hover:scale-[1.02] transition-transform duration-200">
                <div class="flex items-center justify-between">
                  <span class="font-label-meta text-label-meta text-secondary uppercase">Rebotes & Asistencias</span>
                  <span class="material-symbols-outlined text-primary text-[18px]">diversity_3</span>
                </div>
                <div class="my-space-xs">
                  <span class="font-headline-lg text-headline-lg text-on-surface">9.2 <span class="text-secondary text-body-sm font-body-sm">RPG</span></span>
                  <span class="mx-space-2xs text-outline-variant">|</span>
                  <span class="font-headline-lg text-headline-lg text-on-surface">6.5 <span class="text-secondary text-body-sm font-body-sm">APG</span></span>
                </div>
                <div class="flex items-center gap-space-2xs text-secondary pt-space-2xs">
                  <span class="material-symbols-outlined text-[14px] text-primary">sports_handball</span>
                  <span class="font-body-sm text-body-sm leading-tight text-on-surface-variant">Doble-doble en 9 encuentros</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-space-md mt-space-xl">
              <button class="inline-flex items-center gap-space-xs rounded-full bg-inverse-surface text-surface px-space-xl py-space-sm font-label-pill text-label-pill uppercase hover:bg-on-surface transition-all shadow-md">
                <span>Ver Ficha Técnica Completa</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
              </button>
            </div>
          </div>

          <div class="lg:col-span-6 relative flex items-center justify-center min-h-[460px] lg:min-h-[520px]">
            <div class="absolute w-72 h-72 lg:w-96 lg:h-96 rounded-full bg-primary-container/40 blur-3xl -z-0"></div>
            <div class="relative z-10 w-full max-w-[420px] flex items-center justify-center">
              <img class="w-full h-auto max-h-[500px] object-contain drop-shadow-[0_20px_35px_rgba(0,0,0,0.18)]" alt="Mateo Kiatipis" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBrn4dUxjZQBna7Hr1yy-1_C-6KKPpkoGEgnScghD27htxaEyMxqaVlKPePSIqlfOL8HUNmKGk8S72R-pwRUoczrG71uFEdaWPrCgMb8MTkokAGFdhBuB47dpZYd2XBObxR3TQGGxLoHTsp2TLXwtfcRRcyMO3sgXn3DDI1ds2PvdGpQ8O6Of_Hh3SuK8LtP4JLZYBY91dLO5PfhJlIk-y4alXvJSj8uk61FLdrsSQq6c1kmUqE-EtoBg"/>
            </div>
            <div class="absolute top-4 right-4 bg-surface-container-lowest/90 backdrop-blur-md rounded-full px-space-md py-space-xs shadow-md flex items-center gap-space-xs">
              <span class="w-2.5 h-2.5 rounded-full bg-primary animate-ping"></span>
              <span class="font-label-meta text-label-meta uppercase text-on-surface font-bold">Activo en Vivo</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters & Search -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop pt-space-xl pb-space-md">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-lg bg-surface-container-low p-space-md rounded-xl">
        <div class="flex items-center gap-space-xs overflow-x-auto py-space-2xs">
          <button 
            v-for="pos in positionFilters" 
            :key="pos.id"
            @click="activePosition = pos.id"
            :class="[
              'px-space-lg py-space-xs rounded-full font-label-pill text-label-pill uppercase shadow-sm transition-all whitespace-nowrap',
              activePosition === pos.id 
                ? 'bg-primary-container text-on-primary-fixed font-semibold' 
                : 'bg-surface-container-lowest text-secondary hover:text-on-surface'
            ]"
          >
            {{ pos.label }}
          </button>
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
              <option value="all">Todas las Franquicias</option>
              <option value="Halcones de Sanarate">Halcones de Sanarate</option>
              <option value="Toros del Norte">Toros del Norte</option>
              <option value="Jaguares Oriente">Jaguares Oriente</option>
              <option value="Titanes de Sanarate">Titanes de Sanarate</option>
            </select>
            <span class="material-symbols-outlined absolute right-space-md top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Player Grid -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop py-space-md mb-space-3xl">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
        <div 
          v-for="player in filteredJugadores" 
          :key="player.id"
          class="player-card group bg-surface-container-lowest rounded-lg p-space-md shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between mb-space-xs">
              <div class="flex items-center gap-space-2xs">
                <span class="px-space-sm py-space-2xs rounded-full bg-surface-container-low font-label-meta text-label-meta uppercase text-secondary">
                  {{ player.posicionLabel }}
                </span>
                <span class="text-primary font-headline-md text-headline-md font-bold">#{{ player.dorsal }}</span>
              </div>
              <div class="w-8 h-8 rounded-full bg-surface-container-low flex items-center justify-center text-secondary group-hover:bg-primary-container group-hover:text-on-primary-fixed transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-[16px]">favorite</span>
              </div>
            </div>

            <div class="relative w-full h-56 rounded-lg bg-surface-container-low overflow-hidden flex items-end justify-center mb-space-md group-hover:bg-[#f2f8d8] transition-colors">
              <span class="absolute top-3 left-3 font-headline-xl text-stat-display text-surface-container-highest select-none font-bold">{{ player.dorsal }}</span>
              <img :src="player.foto" :alt="player.nombre" class="relative z-10 max-h-52 object-contain group-hover:scale-105 transition-transform duration-300"/>
              <div class="absolute bottom-2 right-2 px-space-sm py-space-2xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill shadow-sm">
                {{ player.ppg }} PPG
              </div>
            </div>

            <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface tracking-tight leading-snug">{{ player.nombre }}</h3>
            <p class="font-body-sm text-body-sm text-secondary font-medium">{{ player.equipo }}</p>

            <div class="grid grid-cols-3 gap-space-2xs bg-surface-container-low/70 rounded-md p-space-xs mt-space-sm">
              <div class="text-center">
                <span class="block font-label-meta text-label-meta uppercase text-secondary">APG</span>
                <span class="font-headline-md text-headline-md text-on-surface">{{ player.apg }}</span>
              </div>
              <div class="text-center">
                <span class="block font-label-meta text-label-meta uppercase text-secondary">RPG</span>
                <span class="font-headline-md text-headline-md text-on-surface">{{ player.rpg }}</span>
              </div>
              <div class="text-center">
                <span class="block font-label-meta text-label-meta uppercase text-secondary">3P%</span>
                <span class="font-headline-md text-headline-md text-primary">{{ player.pct3p }}</span>
              </div>
            </div>
          </div>

          <button class="mt-space-md w-full py-space-xs rounded-full bg-surface-container text-on-surface font-label-pill text-label-pill uppercase hover:bg-primary-container hover:text-on-primary-fixed transition-colors">
            Ver Perfil
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const activePosition = ref('all');
const searchQuery = ref('');
const selectedTeam = ref('all');

const positionFilters = [
  { id: 'all', label: 'Todos (6)' },
  { id: 'Base', label: 'Base (PG)' },
  { id: 'Escolta', label: 'Escolta (SG)' },
  { id: 'Alero', label: 'Alero (SF)' },
  { id: 'Pívot', label: 'Pívot (C)' }
];

const jugadores = ref([
  {
    id: 1,
    nombre: 'Carlos Mendoza',
    dorsal: '04',
    posicion: 'Base',
    posicionLabel: 'Base (PG)',
    equipo: 'Toros del Norte',
    ppg: '24.8',
    apg: '8.4',
    rpg: '4.2',
    pct3p: '42%',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCsn1E_RRHnnEqprx_v9gqtNXxwGd04gMleGhkbFleI79La60vgpuZ3qk7v8kKDEMgej-3xBuzOqBiaZ70cq2KiOKpI83EPgzypN87UVNSt2wapvIkrLz9iFv2j3pOhFvMAhxFQD597vuoRvonIis_WQy6eecYeqf_FFYDmE0kbFDPP1a5pJqXYTp25T_u0He2QKiLqQncKiDubx6nCFdjxQApNwlllik2FVL7BLkGjvTvyQgnSCRVRQg'
  },
  {
    id: 2,
    nombre: 'Mateo Kiatipis',
    dorsal: '11',
    posicion: 'Alero',
    posicionLabel: 'Alero (SF)',
    equipo: 'Halcones de Sanarate',
    ppg: '28.4',
    apg: '6.5',
    rpg: '9.2',
    pct3p: '48%',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBrn4dUxjZQBna7Hr1yy-1_C-6KKPpkoGEgnScghD27htxaEyMxqaVlKPePSIqlfOL8HUNmKGk8S72R-pwRUoczrG71uFEdaWPrCgMb8MTkokAGFdhBuB47dpZYd2XBObxR3TQGGxLoHTsp2TLXwtfcRRcyMO3sgXn3DDI1ds2PvdGpQ8O6Of_Hh3SuK8LtP4JLZYBY91dLO5PfhJlIk-y4alXvJSj8uk61FLdrsSQq6c1kmUqE-EtoBg'
  },
  {
    id: 3,
    nombre: 'David Alvarado',
    dorsal: '15',
    posicion: 'Pívot',
    posicionLabel: 'Pívot (C)',
    equipo: 'Toros del Norte',
    ppg: '18.2',
    apg: '2.1',
    rpg: '14.8',
    pct3p: '28%',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD5oqBzyoi5QCGtg9c5XKMBDh_H0H1EOC3jv4wvTt1vioXmr9thRgTfYoilKJeCmBoqHCZI8tML5v8rMGxFyatIMlNU5isWa6VFbju8MBxa2dTPQqkvoDtqcpIeOVgYaRmD7x1xSBAoivhi-wl-A1J6bVDN3jZ4gFRD5wIsnUOtd8efqyJp9iWU1vWmftmS-Lwi3eTnbk2UC4iagpfe6LWhVl5K0HUeOsoI9LfXkAKbJKV946Wsb7O1XQ'
  },
  {
    id: 4,
    nombre: 'Carlos Morales',
    dorsal: '07',
    posicion: 'Escolta',
    posicionLabel: 'Escolta (SG)',
    equipo: 'Titanes de Sanarate',
    ppg: '31.2',
    apg: '5.2',
    rpg: '4.8',
    pct3p: '52%',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBIyvXZ_Fc1MvjhswePhohHbScVXd81uslD4SpwBjFjz2jFEiyyszMfwPrfF8QreLGBss5943YDyn0hu0KOdn6vty7KIi-1GpFTzAxHWY0NPlWM1y-ZquTW169t7iBS2bdppFgPuRhSd-XnistJqgapWy1xT2cPJAK4JdQ-_BNHLgHizddRkcvHoE7EN5MPshl5RBQ4jz1T2SHygDhbm61DqufqVOEWwlZmlQ1zkL2zaC77jyjLXJZtAA'
  },
  {
    id: 5,
    nombre: 'Rodrigo Estrada',
    dorsal: '14',
    posicion: 'Alero',
    posicionLabel: 'Alero (SF)',
    equipo: 'Jaguares Oriente',
    ppg: '16.5',
    apg: '4.1',
    rpg: '7.4',
    pct3p: '39%',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCsn1E_RRHnnEqprx_v9gqtNXxwGd04gMleGhkbFleI79La60vgpuZ3qk7v8kKDEMgej-3xBuzOqBiaZ70cq2KiOKpI83EPgzypN87UVNSt2wapvIkrLz9iFv2j3pOhFvMAhxFQD597vuoRvonIis_WQy6eecYeqf_FFYDmE0kbFDPP1a5pJqXYTp25T_u0He2QKiLqQncKiDubx6nCFdjxQApNwlllik2FVL7BLkGjvTvyQgnSCRVRQg'
  },
  {
    id: 6,
    nombre: 'Javier Aldana',
    dorsal: '07',
    posicion: 'Base',
    posicionLabel: 'Base (PG)',
    equipo: 'Halcones de Sanarate',
    ppg: '14.6',
    apg: '8.4',
    rpg: '3.8',
    pct3p: '41%',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBIyvXZ_Fc1MvjhswePhohHbScVXd81uslD4SpwBjFjz2jFEiyyszMfwPrfF8QreLGBss5943YDyn0hu0KOdn6vty7KIi-1GpFTzAxHWY0NPlWM1y-ZquTW169t7iBS2bdppFgPuRhSd-XnistJqgapWy1xT2cPJAK4JdQ-_BNHLgHizddRkcvHoE7EN5MPshl5RBQ4jz1T2SHygDhbm61DqufqVOEWwlZmlQ1zkL2zaC77jyjLXJZtAA'
  }
]);

const filteredJugadores = computed(() => {
  return jugadores.value.filter(player => {
    const matchesPosition = activePosition.value === 'all' || player.posicion === activePosition.value;
    const matchesTeam = selectedTeam.value === 'all' || player.equipo === selectedTeam.value;
    const query = searchQuery.value.toLowerCase().trim();
    const matchesSearch = !query || player.nombre.toLowerCase().includes(query) || player.dorsal.includes(query);

    return matchesPosition && matchesTeam && matchesSearch;
  });
});
</script>
