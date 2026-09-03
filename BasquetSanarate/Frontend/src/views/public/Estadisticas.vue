<template>
  <div class="relative w-full overflow-hidden">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[720px] h-[360px] bg-primary-container/20 rounded-full blur-[100px] pointer-events-none"></div>
    
    <div class="max-w-[1280px] mx-auto px-gutter-desktop pt-space-xl pb-space-3xl relative z-10 flex flex-col gap-space-2xl">
      <!-- Header -->
      <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-space-lg">
        <div class="flex flex-col gap-space-xs max-w-2xl">
          <div class="flex items-center gap-space-xs">
            <span class="inline-flex items-center gap-1.5 px-space-sm py-1 bg-primary-container text-on-primary-fixed rounded-full font-label-meta text-label-meta uppercase tracking-widest shadow-sm">
              <span class="w-1.5 h-1.5 rounded-full bg-on-primary-fixed animate-pulse"></span>
              Temporada Oficial 2025
            </span>
            <span class="text-secondary font-label-meta text-label-meta uppercase tracking-wider">Jornada 15 / 18</span>
          </div>
          <h1 class="font-display-hero text-display-hero uppercase tracking-tight text-on-surface leading-none">
            Estadísticas Oficiales <span class="text-primary">& Líderes</span>
          </h1>
          <p class="font-body-md text-body-md text-secondary max-w-xl">
            Métricas de rendimiento en tiempo real, tablas de eficiencia analítica y marcas individuales de la Liga de Baloncesto Sanarateca.
          </p>
        </div>

        <!-- Filter Segmented Control -->
        <div class="flex flex-wrap items-center gap-space-xs bg-surface-container-high/60 backdrop-blur-md p-1.5 rounded-full self-start lg:self-end">
          <button 
            @click="activeCategory = 'masc'"
            :class="[
              'px-space-lg py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-all duration-200',
              activeCategory === 'masc' ? 'bg-primary-container text-on-primary-fixed shadow-md' : 'text-secondary hover:text-on-surface'
            ]"
          >
            Masculina Mayor
          </button>
          <button 
            @click="activeCategory = 'fem'"
            :class="[
              'px-space-lg py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-all duration-200',
              activeCategory === 'fem' ? 'bg-primary-container text-on-primary-fixed shadow-md' : 'text-secondary hover:text-on-surface'
            ]"
          >
            Femenina Libre
          </button>
        </div>
      </section>

      <!-- Podio de Rendimiento Individual -->
      <section class="flex flex-col gap-space-md">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-space-sm">
            <div class="w-2.5 h-6 bg-primary-container rounded-full"></div>
            <h2 class="font-headline-lg text-headline-lg uppercase tracking-wide text-on-surface">Podio de Rendimiento Individual</h2>
          </div>
          <span class="font-label-meta text-label-meta uppercase text-secondary tracking-widest hidden sm:inline-block">Actualizado constantemente</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
          <!-- Líder 1: PPG -->
          <div class="group relative bg-surface-container-lowest rounded-xl p-space-xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden">
            <span class="absolute -right-4 -bottom-6 font-stat-display text-[130px] font-bold text-surface-container-high/50 select-none pointer-events-none leading-none">01</span>
            <div class="flex items-start justify-between relative z-10">
              <div class="flex items-center gap-space-xs">
                <span class="w-8 h-8 rounded-full bg-primary-container/40 flex items-center justify-center text-on-primary-container">
                  <span class="material-symbols-outlined text-[18px]">local_fire_department</span>
                </span>
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Líder Anotación</span>
              </div>
              <span class="px-space-sm py-1 bg-primary-container text-on-primary-fixed rounded-full font-label-pill text-label-pill shadow-sm">
                PPG #1
              </span>
            </div>

            <div class="my-space-lg flex items-center gap-space-md relative z-10">
              <div class="relative w-20 h-20 shrink-0 rounded-full overflow-hidden bg-surface-container shadow-inner">
                <img class="w-full h-full object-cover" alt="Carlos Morales" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAC6lNL6FO5zyW3C2GPId5V0rAudsDf-M-lejjNL3_VRMB0m4G4e7WmCxSHOlCwAX1GuyPtmh5GrBuVTKqcX6z-QtU2tkRM9MWsq1TLLF5JRHHmxz1_FGA-MTHwo3l1Mw93LJfH3i2gxNdaRM5h6UoFJPE76ZwWUdxzQWJz_CHkd7GU1J--ea7kRlvHnD1tXK_EdjAZ6DbaVBYUE0lAXPN7V_6nDhFPN6WFd-aBooZ4_96mB6kFB09S6A"/>
              </div>
              <div class="flex flex-col min-w-0">
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface truncate leading-tight">Carlos Morales</h3>
                <div class="flex items-center gap-1.5 text-secondary font-body-sm text-body-sm">
                  <span class="w-2 h-2 rounded-full bg-primary"></span>
                  <span class="truncate">Toros del Norte</span>
                  <span class="text-surface-variant">•</span>
                  <span>#7 Escolta</span>
                </div>
              </div>
            </div>

            <div class="relative z-10 flex items-end justify-between pt-space-md bg-surface-container-low/60 p-space-md rounded-lg">
              <div>
                <span class="font-stat-display text-stat-display text-on-surface leading-none block">31.2</span>
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Puntos por Juego</span>
              </div>
              <div class="flex flex-col items-end">
                <span class="font-headline-md text-headline-md text-primary leading-tight">56.4%</span>
                <span class="font-label-meta text-label-meta uppercase text-secondary">Tiro de Campo</span>
              </div>
            </div>
          </div>

          <!-- Líder 2: RPG -->
          <div class="group relative bg-surface-container-lowest rounded-xl p-space-xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden">
            <span class="absolute -right-4 -bottom-6 font-stat-display text-[130px] font-bold text-surface-container-high/50 select-none pointer-events-none leading-none">02</span>
            <div class="flex items-start justify-between relative z-10">
              <div class="flex items-center gap-space-xs">
                <span class="w-8 h-8 rounded-full bg-secondary-container/60 flex items-center justify-center text-on-secondary-container">
                  <span class="material-symbols-outlined text-[18px]">sports_handball</span>
                </span>
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Amo del Tablero</span>
              </div>
              <span class="px-space-sm py-1 bg-surface-container-high text-on-surface rounded-full font-label-pill text-label-pill">
                RPG #1
              </span>
            </div>

            <div class="my-space-lg flex items-center gap-space-md relative z-10">
              <div class="relative w-20 h-20 shrink-0 rounded-full overflow-hidden bg-surface-container shadow-inner">
                <img class="w-full h-full object-cover" alt="David Alvarado" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBBk57THHLajiNoBjORexj8locZwOxV1N9lWFNMHCQbRPL21SmwAD1k1fAHj5PyqPOV8UqEf6N4XGvPq5VRneqO6RHlGeWrNE5H3l1dj6J6RKGeJ5f5uj20zFXs5F68Z2yiNt8xkddNRdQvn2UJm-deAHGx14O1hPSlmr4oz-LVhepTBbeZZUWEJCnHX6WkzeFNif7f3-eUJFgnzmNjTXtPIAWyc806O6MkAlti3kF3zk17R9Izt1hffw"/>
              </div>
              <div class="flex flex-col min-w-0">
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface truncate leading-tight">David Alvarado</h3>
                <div class="flex items-center gap-1.5 text-secondary font-body-sm text-body-sm">
                  <span class="w-2 h-2 rounded-full bg-secondary"></span>
                  <span class="truncate">Halcones Sanarate</span>
                  <span class="text-surface-variant">•</span>
                  <span>#15 Pívot</span>
                </div>
              </div>
            </div>

            <div class="relative z-10 flex items-end justify-between pt-space-md bg-surface-container-low/60 p-space-md rounded-lg">
              <div>
                <span class="font-stat-display text-stat-display text-on-surface leading-none block">14.8</span>
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Rebotes Totales</span>
              </div>
              <div class="flex flex-col items-end">
                <span class="font-headline-md text-headline-md text-secondary leading-tight">4.2</span>
                <span class="font-label-meta text-label-meta uppercase text-secondary">Ofensivos / PJ</span>
              </div>
            </div>
          </div>

          <!-- Líder 3: APG -->
          <div class="group relative bg-surface-container-lowest rounded-xl p-space-xl shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden">
            <span class="absolute -right-4 -bottom-6 font-stat-display text-[130px] font-bold text-surface-container-high/50 select-none pointer-events-none leading-none">03</span>
            <div class="flex items-start justify-between relative z-10">
              <div class="flex items-center gap-space-xs">
                <span class="w-8 h-8 rounded-full bg-tertiary-container/60 flex items-center justify-center text-on-tertiary-container">
                  <span class="material-symbols-outlined text-[18px]">share_location</span>
                </span>
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Visión de Juego</span>
              </div>
              <span class="px-space-sm py-1 bg-surface-container-high text-on-surface rounded-full font-label-pill text-label-pill">
                APG #1
              </span>
            </div>

            <div class="my-space-lg flex items-center gap-space-md relative z-10">
              <div class="relative w-20 h-20 shrink-0 rounded-full overflow-hidden bg-surface-container shadow-inner">
                <img class="w-full h-full object-cover" alt="Luis Gómez" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD2jbGx_kpEcZdrdeG8djqXVek55vuA5g3uY8_NSG5e8QW5PQir92G-4XIqn8FmGdgCBQSm89hMQPnjiy-0M5CBdb3kBtYVMos3doHSyLdp77eTsrk2GyfLdCIyUr42-1T5L5r4iHGZX460Tvr-2cWpBdrzE7mKpGefSTdjYKXMn2j8_bdcRqiQ4Wfa8Wgn3UBPndx3Z-hPI6pafsx1fTXhkmvicdb6Bxq85zNdnL81oKoEE3IxPkg_eQ"/>
              </div>
              <div class="flex flex-col min-w-0">
                <h3 class="font-headline-md text-headline-md uppercase text-on-surface truncate leading-tight">Luis Gómez</h3>
                <div class="flex items-center gap-1.5 text-secondary font-body-sm text-body-sm">
                  <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                  <span class="truncate">Jaguares Oriente</span>
                  <span class="text-surface-variant">•</span>
                  <span>#3 Armador</span>
                </div>
              </div>
            </div>

            <div class="relative z-10 flex items-end justify-between pt-space-md bg-surface-container-low/60 p-space-md rounded-lg">
              <div>
                <span class="font-stat-display text-stat-display text-on-surface leading-none block">9.4</span>
                <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Asistencias / PJ</span>
              </div>
              <div class="flex flex-col items-end">
                <span class="font-headline-md text-headline-md text-tertiary leading-tight">3.1</span>
                <span class="font-label-meta text-label-meta uppercase text-secondary">Ratio Ast/Pérdida</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const activeCategory = ref('masc');
</script>
