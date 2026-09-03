<template>
  <div class="w-full max-w-[1280px] mx-auto flex flex-col gap-space-2xl">
    <!-- DASHBOARD TOP BAR / CONTROLS -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-space-lg">
      <div class="flex flex-col gap-space-2xs">
        <div class="flex items-center gap-space-xs">
          <span class="inline-flex items-center gap-1 px-space-sm py-0.5 rounded-full bg-primary-container text-on-primary-fixed font-label-meta text-label-meta uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-on-primary-fixed animate-ping"></span>
            Liga Oficial Sanarate
          </span>
          <span class="text-secondary font-label-meta text-label-meta uppercase">Edición Apertura/Clausura</span>
        </div>
        <h1 class="font-headline-xl text-headline-xl uppercase text-on-surface tracking-tight">Panel General de la Temporada</h1>
        <div class="flex items-center gap-space-sm">
          <div class="relative inline-flex items-center">
            <select v-model="selectedSeason" class="appearance-none bg-surface-container-low text-on-surface font-label-pill text-label-pill py-space-xs pl-space-md pr-space-xl rounded-full focus:outline-none cursor-pointer hover:bg-surface-container transition-colors shadow-sm">
              <option value="c2025">Temporada Clausura 2025</option>
              <option value="a2024">Temporada Apertura 2024</option>
              <option value="c2024">Temporada Clausura 2024</option>
            </select>
            <span class="material-symbols-outlined pointer-events-none absolute right-2.5 text-secondary text-[20px]">expand_more</span>
          </div>
          <span class="text-secondary font-body-sm text-body-sm">Fase Regular • Jornada 8 de 14</span>
        </div>
      </div>

      <!-- EXPORT ACTIONS & QUICK CONTROLS -->
      <div class="flex items-center gap-space-xs flex-wrap sm:flex-nowrap">
        <button @click="printReport" class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-low hover:bg-surface-container text-on-surface font-label-pill text-label-pill uppercase shadow-sm transition-all duration-150 active:scale-95">
          <span class="material-symbols-outlined text-[18px]">print</span>
          <span>Imprimir PDF</span>
        </button>
        <button class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-low hover:bg-surface-container text-on-surface font-label-pill text-label-pill uppercase shadow-sm transition-all duration-150 active:scale-95">
          <span class="material-symbols-outlined text-[18px] text-primary">table_view</span>
          <span>Bajar a Excel</span>
        </button>
        <router-link to="/partidos" class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase shadow-[0_4px_16px_rgba(204,255,0,0.35)] hover:shadow-[0_6px_22px_rgba(204,255,0,0.5)] transition-all duration-150 active:scale-95">
          <span class="material-symbols-outlined text-[18px]">add_circle</span>
          <span>Programar Partido</span>
        </router-link>
      </div>
    </div>

    <!-- METRICS RIBBON (Bento-style 4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-secondary font-label-pill text-label-pill uppercase">Partidos Jugados</span>
          <div class="w-9 h-9 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[20px]">sports_basketball</span>
          </div>
        </div>
        <div class="my-space-sm">
          <div class="flex items-baseline gap-space-2xs">
            <span class="font-stat-display text-stat-display text-on-surface leading-none">34</span>
            <span class="font-headline-md text-headline-md text-secondary">/ 50</span>
          </div>
          <div class="w-full bg-surface-container-highest h-2 rounded-full mt-space-sm overflow-hidden">
            <div class="bg-primary-container h-full rounded-full" style="width: 68%;"></div>
          </div>
        </div>
        <span class="text-secondary font-label-meta text-label-meta">68% completado del torneo</span>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-secondary font-label-pill text-label-pill uppercase">Puntos x Partido</span>
          <div class="w-9 h-9 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[20px]">analytics</span>
          </div>
        </div>
        <div class="my-space-sm flex items-baseline gap-space-xs">
          <span class="font-stat-display text-stat-display text-on-surface leading-none">78.4</span>
          <span class="inline-flex items-center text-primary font-label-meta text-label-meta">
            <span class="material-symbols-outlined text-[14px]">trending_up</span> +3.2%
          </span>
        </div>
        <span class="text-secondary font-label-meta text-label-meta">Eficiencia ofensiva global</span>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-secondary font-label-pill text-label-pill uppercase">Asistencia Total</span>
          <div class="w-9 h-9 rounded-full bg-surface-container-low flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[20px]">groups</span>
          </div>
        </div>
        <div class="my-space-sm flex items-baseline gap-space-xs">
          <span class="font-stat-display text-stat-display text-on-surface leading-none">3,420</span>
          <span class="text-secondary font-body-sm text-body-sm">fans</span>
        </div>
        <span class="text-secondary font-label-meta text-label-meta">Gimnasio Municipal Sanarate</span>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <span class="text-secondary font-label-pill text-label-pill uppercase">Líder Triples</span>
          <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center">
            <span class="material-symbols-outlined text-[20px]">workspace_premium</span>
          </div>
        </div>
        <div class="my-space-sm flex items-baseline gap-space-xs">
          <span class="font-stat-display text-stat-display text-on-surface leading-none">46</span>
          <span class="text-secondary font-label-meta text-label-meta uppercase">C. Morales (#07)</span>
        </div>
        <div class="flex items-center justify-between text-secondary font-label-meta text-label-meta">
          <span>Toros de Sanarate</span>
          <span class="text-primary font-semibold">52% 3P</span>
        </div>
      </div>
    </div>

    <!-- MAIN BODY: 2-COLUMN SPLIT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl">
      <!-- LEFT SECTION: MATCHES (Col 8) -->
      <div class="lg:col-span-8 flex flex-col gap-space-xl">
        <!-- PARTIDO EN VIVO -->
        <div class="flex flex-col gap-space-sm">
          <div class="flex items-center justify-between px-space-xs">
            <div class="flex items-center gap-space-xs">
              <span class="font-headline-lg text-headline-lg uppercase text-on-surface">Partido en Vivo</span>
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-error"></span>
              </span>
            </div>
            <span class="text-secondary font-label-meta text-label-meta uppercase tracking-wider">Corte al Instante</span>
          </div>

          <div class="relative bg-surface-container-lowest rounded-xl p-space-lg sm:p-space-xl shadow-md overflow-hidden">
            <div class="flex flex-col gap-space-lg relative z-10">
              <div class="flex items-center justify-between flex-wrap gap-space-xs">
                <div class="inline-flex items-center gap-space-xs px-space-md py-1 rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase shadow-sm">
                  <span class="material-symbols-outlined text-[16px] animate-pulse">timer</span>
                  <span>EN VIVO • 4TO CUARTO</span>
                </div>
                <div class="flex items-center gap-space-xs text-secondary font-label-meta text-label-meta uppercase">
                  <span class="material-symbols-outlined text-[16px]">location_on</span>
                  <span>Duela Principal Sanarate</span>
                </div>
              </div>

              <div class="grid grid-cols-7 items-center gap-space-2xs sm:gap-space-sm text-center py-space-xs">
                <div class="col-span-3 flex flex-col items-center gap-space-xs">
                  <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center font-headline-lg text-headline-lg shadow-inner">
                    TR
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface line-clamp-1">Toros de Sanarate</span>
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">88</span>
                </div>

                <div class="col-span-1 flex flex-col items-center justify-center gap-space-2xs">
                  <div class="px-space-sm py-space-2xs rounded-full bg-inverse-surface text-surface-bright font-headline-md text-headline-md tracking-wider">
                    02:45
                  </div>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">Q4</span>
                </div>

                <div class="col-span-3 flex flex-col items-center gap-space-xs">
                  <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-headline-lg text-headline-lg shadow-inner">
                    HC
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface line-clamp-1">Halcones Sanarate</span>
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">82</span>
                </div>
              </div>

              <div class="bg-surface-container-low rounded-lg p-space-md flex flex-col gap-space-xs">
                <div class="flex items-center justify-between text-secondary font-label-meta text-label-meta uppercase">
                  <span class="font-semibold text-on-surface">Faltas: 3</span>
                  <span class="text-primary font-bold">Posesión de Balón: Toros</span>
                  <span class="font-semibold text-on-surface">Faltas: 4 (Bonus)</span>
                </div>
                <div class="w-full bg-surface-container-highest h-2 rounded-full flex overflow-hidden">
                  <div class="bg-primary-container h-full" style="width: 55%;"></div>
                  <div class="bg-surface-dim h-full" style="width: 45%;"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ÚLTIMOS RESULTADOS -->
        <div class="flex flex-col gap-space-md">
          <div class="flex items-center justify-between px-space-xs">
            <h2 class="font-headline-lg text-headline-lg uppercase text-on-surface">Últimos Resultados</h2>
            <router-link to="/partidos" class="text-primary font-label-pill text-label-pill uppercase hover:underline">Ver Todos</router-link>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between gap-space-md">
              <div class="flex items-center justify-between">
                <span class="font-label-meta text-label-meta uppercase text-secondary">Ayer • 19:30 hrs</span>
                <span class="px-space-sm py-0.5 rounded-full bg-surface-container font-label-meta text-label-meta text-on-surface-variant uppercase">Finalizado</span>
              </div>
              <div class="flex items-center justify-between py-space-xs">
                <div class="flex flex-col items-center gap-1 w-2/5 text-center">
                  <span class="font-headline-md text-headline-md uppercase text-on-surface">Halcones</span>
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">88</span>
                </div>
                <span class="font-bold text-secondary">VS</span>
                <div class="flex flex-col items-center gap-1 w-2/5 text-center">
                  <span class="font-headline-md text-headline-md uppercase text-on-surface">Jaguares</span>
                  <span class="font-stat-display text-stat-display text-primary leading-none">92</span>
                </div>
              </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between gap-space-md">
              <div class="flex items-center justify-between">
                <span class="font-label-meta text-label-meta uppercase text-secondary">Viernes • 21:00 hrs</span>
                <span class="px-space-sm py-0.5 rounded-full bg-surface-container font-label-meta text-label-meta text-on-surface-variant uppercase">Finalizado</span>
              </div>
              <div class="flex items-center justify-between py-space-xs">
                <div class="flex flex-col items-center gap-1 w-2/5 text-center">
                  <span class="font-headline-md text-headline-md uppercase text-on-surface">Leones</span>
                  <span class="font-stat-display text-stat-display text-primary leading-none">76</span>
                </div>
                <span class="font-bold text-secondary">VS</span>
                <div class="flex flex-col items-center gap-1 w-2/5 text-center">
                  <span class="font-headline-md text-headline-md uppercase text-on-surface">Caimanes</span>
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">71</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT PANEL: STANDINGS & NEWS (Col 4) -->
      <div class="lg:col-span-4 flex flex-col gap-space-xl">
        <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm">
          <div class="flex items-center justify-between mb-space-md">
            <h3 class="font-headline-md text-headline-md uppercase text-on-surface">Tabla de Posiciones</h3>
            <router-link to="/equipos" class="font-label-meta text-label-meta text-primary uppercase hover:underline">Ver Completa</router-link>
          </div>
          <div class="flex flex-col gap-space-xs">
            <div v-for="(team, idx) in tabla" :key="idx" class="flex items-center justify-between p-space-xs bg-surface-container-low rounded-lg">
              <div class="flex items-center gap-space-xs">
                <span class="font-headline-md text-headline-md w-6 text-center text-on-surface">{{ idx + 1 }}</span>
                <span class="font-body-sm text-body-sm font-semibold text-on-surface">{{ team.nombre }}</span>
              </div>
              <div class="flex items-center gap-space-xs">
                <span class="font-headline-md text-headline-md text-primary">{{ team.record }}</span>
                <span class="font-label-meta text-label-meta text-secondary">{{ team.pct }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const selectedSeason = ref('c2025');

const tabla = [
  { nombre: 'Toros de Sanarate', record: '14 - 1', pct: '.933' },
  { nombre: 'Halcones Dorados', record: '12 - 3', pct: '.800' },
  { nombre: 'Cobras del Valle', record: '11 - 1', pct: '.916' },
  { nombre: 'Jaguares Oriente', record: '10 - 5', pct: '.666' },
  { nombre: 'Titanes del Barrio', record: '8 - 7', pct: '.533' }
];

const printReport = () => {
  window.print();
};
</script>
