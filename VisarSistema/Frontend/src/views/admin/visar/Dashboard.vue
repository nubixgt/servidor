<template>
  <div class="space-y-6 pb-12 animate-fade-in max-w-[1600px] mx-auto text-slate-800 dark:text-slate-100">
    
    <!-- Top Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/40 dark:bg-slate-900/40 backdrop-blur-md p-4 rounded-3xl border border-slate-200/50 dark:border-slate-800/50">
      <div>
        <h1 class="text-2xl font-black text-slate-950 dark:text-white leading-tight">Dashboard Ejecutivo</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
          Resumen general del desempeño institucional 
          <span class="inline-block w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse" title="Datos en tiempo real"></span>
          <span class="text-[10px] text-emerald-500 font-bold uppercase tracking-wider">Datos en tiempo real</span>
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Date Selector -->
        <div class="flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs font-semibold shadow-sm">
          <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="text-slate-600 dark:text-slate-300">1 May - 31 May 2025</span>
        </div>

        <!-- Filters Button -->
        <button class="flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/80 rounded-xl px-4 py-1.5 text-xs font-bold transition-all active:scale-95 shadow-sm">
          <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
          </svg>
          <span class="text-slate-700 dark:text-slate-200">Filtros</span>
          <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>
      </div>
    </div>

    <!-- Row 1: KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <!-- Card 1: ACTIVIDADES REALIZADAS -->
      <div class="bg-gradient-to-br from-emerald-50 to-white dark:from-slate-900/60 dark:to-slate-900/20 backdrop-blur-md p-5 rounded-3xl border border-emerald-100 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-start">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Actividades Realizadas</p>
            <h3 class="text-2xl font-black text-slate-950 dark:text-white">8,742</h3>
          </div>
          <div class="p-2.5 bg-emerald-500/10 rounded-2xl text-emerald-600 dark:text-emerald-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
        </div>
        <div class="flex items-center gap-1.5 mt-4 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
          <span>↑ 15.3%</span>
          <span class="text-slate-400 font-medium ml-0.5">vs mes anterior</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400 opacity-60"></div>
      </div>

      <!-- Card 2: PROGRAMAS ACTIVOS -->
      <div class="bg-gradient-to-br from-blue-50 to-white dark:from-slate-900/60 dark:to-slate-900/20 backdrop-blur-md p-5 rounded-3xl border border-blue-100 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-start">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Programas Activos</p>
            <h3 class="text-2xl font-black text-slate-950 dark:text-white">27</h3>
          </div>
          <div class="p-2.5 bg-blue-500/10 rounded-2xl text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
        </div>
        <div class="flex items-center gap-1.5 mt-4 text-[10px] font-bold text-blue-600 dark:text-blue-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
          <span>↑ 8.0%</span>
          <span class="text-slate-400 font-medium ml-0.5">vs mes anterior</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-400 opacity-60"></div>
      </div>

      <!-- Card 3: TÉCNICOS EN CAMPO -->
      <div class="bg-gradient-to-br from-cyan-50 to-white dark:from-slate-900/60 dark:to-slate-900/20 backdrop-blur-md p-5 rounded-3xl border border-cyan-100 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-start">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Técnicos en Campo</p>
            <h3 class="text-2xl font-black text-slate-950 dark:text-white">316</h3>
          </div>
          <div class="p-2.5 bg-cyan-500/10 rounded-2xl text-cyan-600 dark:text-cyan-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
        </div>
        <div class="flex items-center gap-1.5 mt-4 text-[10px] font-bold text-cyan-600 dark:text-cyan-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
          <span>↑ 5.2%</span>
          <span class="text-slate-400 font-medium ml-0.5">vs mes anterior</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-cyan-500 to-sky-400 opacity-60"></div>
      </div>

      <!-- Card 4: COBERTURA NACIONAL -->
      <div class="bg-gradient-to-br from-purple-50 to-white dark:from-slate-900/60 dark:to-slate-900/20 backdrop-blur-md p-5 rounded-3xl border border-purple-100 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-start">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Cobertura Nacional</p>
            <h3 class="text-2xl font-black text-slate-950 dark:text-white">92%</h3>
          </div>
          <div class="p-2.5 bg-purple-500/10 rounded-2xl text-purple-600 dark:text-purple-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
        </div>
        <div class="flex items-center gap-1.5 mt-4 text-[10px] font-bold text-purple-600 dark:text-purple-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
          <span>En municipios</span>
          <span class="text-slate-400 font-medium ml-0.5">activos</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-purple-500 to-fuchsia-400 opacity-60"></div>
      </div>

      <!-- Card 5: BENEFICIARIOS ATENDIDOS -->
      <div class="bg-gradient-to-br from-pink-50 to-white dark:from-slate-900/60 dark:to-slate-900/20 backdrop-blur-md p-5 rounded-3xl border border-pink-100 dark:border-slate-800/50 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-start">
          <div class="space-y-1">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Beneficiarios Atendidos</p>
            <h3 class="text-2xl font-black text-slate-950 dark:text-white">124,568</h3>
          </div>
          <div class="p-2.5 bg-pink-500/10 rounded-2xl text-pink-600 dark:text-pink-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="flex items-center gap-1.5 mt-4 text-[10px] font-bold text-pink-600 dark:text-pink-400">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
          <span>↑ 18.7%</span>
          <span class="text-slate-400 font-medium ml-0.5">vs mes anterior</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-pink-500 to-rose-400 opacity-60"></div>
      </div>
    </div>

    <!-- Row 2: Mid Charts Grid (Cumplimiento + Actividades + Mapa) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Cumplimiento de Metas -->
      <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-sm font-black uppercase tracking-wider text-slate-400 mb-4">Cumplimiento de Metas</h3>
          
          <div class="flex justify-center items-center py-6 relative">
            <!-- Radial Gauge -->
            <VueApexCharts type="radialBar" height="220" :options="radialOptions" :series="[78]" />
            <div class="absolute text-center mt-6">
              <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest block">Promedio</span>
              <span class="text-[8px] font-bold text-slate-400 block mt-0.5">general</span>
            </div>
          </div>
        </div>

        <div class="space-y-2 border-t border-slate-100 dark:border-slate-800/50 pt-4">
          <div class="flex justify-between items-center text-xs">
            <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Cumplidas
            </span>
            <span class="font-bold">78% (21)</span>
          </div>
          <div class="flex justify-between items-center text-xs">
            <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> En Proceso
            </span>
            <span class="font-bold">15% (4)</span>
          </div>
          <div class="flex justify-between items-center text-xs">
            <span class="flex items-center gap-2 font-medium text-slate-500 dark:text-slate-400">
              <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span> Pendientes
            </span>
            <span class="font-bold">7% (2)</span>
          </div>
        </div>
      </div>

      <!-- Actividades por Dirección -->
      <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Actividades por Dirección</h3>
            <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold uppercase rounded-lg px-2 py-0.5">
              <span>Este mes</span>
              <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
          </div>
          
          <div class="h-64">
            <VueApexCharts type="bar" height="100%" :options="barOptions" :series="barSeries" />
          </div>
        </div>
      </div>

      <!-- Cobertura Geográfica (Vector Map of Guatemala) -->
      <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between relative overflow-hidden">
        <div class="z-10">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Cobertura Geográfica</h3>
            <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold uppercase rounded-lg px-2.5 py-0.5 text-slate-700 dark:text-slate-350">
              <span>Nacional</span>
              <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
          </div>
        </div>

        <!-- Guatemala Map Container -->
        <div class="relative w-full h-[320px] bg-slate-950/20 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-inner">
          <div ref="mapContainer" class="w-full h-full"></div>
          
          <!-- Legend Box Overlay (Matching mockup exactly) -->
          <div class="absolute bottom-4 right-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 p-3 rounded-xl shadow-xl z-[1000] space-y-2 text-[9px] font-bold uppercase">
            <p class="text-slate-400 tracking-wider mb-1.5 border-b border-slate-100 dark:border-slate-800 pb-1">Nivel de cobertura</p>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <span class="text-slate-700 dark:text-slate-300">Alta (> 80%)</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
              <span class="text-slate-700 dark:text-slate-300">Media (50%-80%)</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
              <span class="text-slate-700 dark:text-slate-300">Baja (< 50%)</span>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Row 3: Donut Charts + Alerts Feed + Top Municipios -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Estado de Programas -->
      <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-sm font-black uppercase tracking-wider text-slate-400 mb-4">Estado de Programas</h3>
          <div class="flex justify-center py-4 relative">
            <VueApexCharts type="donut" height="220" :options="donutProgramasOptions" :series="donutProgramasSeries" />
          </div>
        </div>
      </div>

      <!-- Tipo de Actividades -->
      <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
        <div>
          <h3 class="text-sm font-black uppercase tracking-wider text-slate-400 mb-4">Tipo de Actividades</h3>
          <div class="flex justify-center py-4">
            <VueApexCharts type="donut" height="220" :options="donutActividadesOptions" :series="donutActividadesSeries" />
          </div>
        </div>
      </div>

      <!-- Alertas y Notificaciones -->
      <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
        <div class="h-full flex flex-col">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Alertas y Notificaciones</h3>
            <router-link to="/admin/audit" class="text-xs text-emerald-500 font-bold hover:underline">Ver todas</router-link>
          </div>

          <div class="space-y-3.5 flex-1 overflow-y-auto pr-1">
            <div v-for="alert in alerts" :key="alert.id" class="flex gap-3 p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/60 hover:shadow-sm transition-all duration-200">
              <div :class="[
                'p-2 rounded-xl shrink-0 h-fit text-xs font-bold uppercase',
                alert.type === 'error' ? 'bg-red-500/10 text-red-500' :
                alert.type === 'warning' ? 'bg-amber-500/10 text-amber-500' :
                'bg-blue-500/10 text-blue-500'
              ]">
                <span v-if="alert.type === 'error'">⚠️</span>
                <span v-else-if="alert.type === 'warning'">⚡</span>
                <span v-else>ℹ️</span>
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate leading-snug">{{ alert.title }}</h4>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ alert.message }}</p>
                <div class="flex items-center gap-2 mt-2 text-[9px] text-slate-400 font-semibold uppercase">
                  <span>{{ alert.time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Municipios con Mayor Actividad -->
      <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Top Municipios</h3>
            <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold uppercase rounded-lg px-2 py-0.5">
              <span>Este mes</span>
              <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
          </div>

          <div class="space-y-4 pt-2">
            <div v-for="muni in topMunicipios" :key="muni.name" class="space-y-1.5">
              <div class="flex justify-between text-xs font-bold">
                <span class="text-slate-700 dark:text-slate-350">{{ muni.rank }}. {{ muni.name }}</span>
                <span class="text-slate-900 dark:text-white">{{ muni.value }}</span>
              </div>
              <!-- Custom progress bar -->
              <div class="w-full h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-1000" :style="{ width: muni.percent + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Row 4: Tendencia de Actividades + KPI Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Tendencia de Actividades (Line/Area Chart) -->
      <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-5 rounded-3xl shadow-sm">
        <div class="flex justify-between items-center mb-4">
          <div class="space-y-0.5">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Tendencia de Actividades</h3>
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">(Últimos 6 meses)</p>
          </div>
          <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold uppercase rounded-lg px-2 py-0.5">
            <span>Mensual</span>
            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
          </div>
        </div>

        <div class="h-64">
          <VueApexCharts type="area" height="100%" :options="areaOptions" :series="areaSeries" />
        </div>
      </div>

      <!-- KPI Widgets Bottom Grid -->
      <div class="lg:col-span-5 grid grid-cols-2 md:grid-cols-3 gap-4">
        
        <!-- Tiempo de Atencion -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-4 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
          <div class="space-y-1">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Tiempo Promedio</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">De atención</p>
            <h4 class="text-2xl font-black text-slate-950 dark:text-white mt-3">2.4 <span class="text-xs font-bold text-slate-400 uppercase">días</span></h4>
          </div>
          <div class="flex justify-between items-center mt-3 border-t border-slate-50 dark:border-slate-800/50 pt-2 text-[9px] font-bold text-emerald-500">
            <span>-0.6 vs mes ant</span>
            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
        </div>

        <!-- Inspecciones -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-4 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
          <div class="space-y-1">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Inspecciones</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Realizadas</p>
            <h4 class="text-2xl font-black text-slate-950 dark:text-white mt-3">4,256</h4>
          </div>
          <div class="flex justify-between items-center mt-3 border-t border-slate-50 dark:border-slate-800/50 pt-2 text-[9px] font-bold text-emerald-500">
            <span>↑ 14.2%</span>
            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
        </div>

        <!-- Licencias -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-4 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
          <div class="space-y-1">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Licencias</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Emitidas</p>
            <h4 class="text-2xl font-black text-slate-950 dark:text-white mt-3">2,985</h4>
          </div>
          <div class="flex justify-between items-center mt-3 border-t border-slate-50 dark:border-slate-800/50 pt-2 text-[9px] font-bold text-emerald-500">
            <span>↑ 11.3%</span>
            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
        </div>

        <!-- Certificados -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-4 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
          <div class="space-y-1">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Certificados</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Emitidos</p>
            <h4 class="text-2xl font-black text-slate-950 dark:text-white mt-3">1,856</h4>
          </div>
          <div class="flex justify-between items-center mt-3 border-t border-slate-50 dark:border-slate-800/50 pt-2 text-[9px] font-bold text-emerald-500">
            <span>↑ 9.8%</span>
            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 009 11.5a13.978 13.978 0 00-4.5-9.75M9 11.5a13.978 13.978 0 01-4.5 9.75M3 16.5l.092-.046A23.905 23.905 0 013 11.5c0-2.227.303-4.384.872-6.43M3 16.5L4 18.5m-.128-13.43L4 3.5m4 0H7.5M12 11a14.053 14.053 0 00-2.753-5.57M9 5.5a14.053 14.053 0 012.753 5.57M12 11h2.5m10-4.5l-.054.09A13.916 13.916 0 0021 11.5a13.978 13.978 0 004.5 9.75M21 11.5a13.978 13.978 0 014.5-9.75M27 16.5l-.092-.046a23.905 23.905 0 010-5c0-2.227-.303-4.384-.872-6.43M27 16.5l-1 2m.128-13.43L26 3.5m-4 0h.5m-8.5 2a14.053 14.053 0 002.753 5.57M15 5.5a14.053 14.053 0 01-2.753 5.57M15 11h-2.5" /></svg>
          </div>
        </div>

        <!-- Muestras -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 p-4 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
          <div class="space-y-1">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Muestras</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Tomadas</p>
            <h4 class="text-2xl font-black text-slate-950 dark:text-white mt-3">3,254</h4>
          </div>
          <div class="flex justify-between items-center mt-3 border-t border-slate-50 dark:border-slate-800/50 pt-2 text-[9px] font-bold text-emerald-500">
            <span>↑ 13.6%</span>
            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import L from 'leaflet'
import * as topojson from 'topojson-client'
import 'leaflet/dist/leaflet.css'
import VueApexCharts from 'vue3-apexcharts'
import api from '../../../services/api'

// Leaflet map elements
const mapContainer = ref(null)
let map = null
let deptosLayer = null

// Guatemala Bounds & Coordinates
const guatemalaBounds = [[13.737, -92.235], [17.825, -88.225]]
const guatemalaCenter = [15.6, -90.23]

// Mock stats for geographic departments matching colors in the mockup
const deptCoverage = {
  'GUATEMALA': 'alta',
  'ESCUINTLA': 'alta',
  'PETEN': 'alta',
  'QUETZALTENANGO': 'alta',
  'SACATEPEQUEZ': 'alta',
  'CHIMALTENANGO': 'alta',
  'EL PROGRESO': 'media',
  'JALAPA': 'media',
  'SANTA ROSA': 'media',
  'RETAHULEHU': 'media',
  'SUCHITEPEQUEZ': 'media',
  'IZABAL': 'media',
  'ZACAPA': 'media',
  'CHIQUIMULA': 'media',
  'ALTA VERAPAZ': 'baja',
  'BAJA VERAPAZ': 'baja',
  'EL QUICHE': 'baja',
  'HUEHUETENANGO': 'baja',
  'SAN MARCOS': 'baja',
  'SOLOLA': 'baja',
  'TOTONICAPAN': 'baja',
  'JUTIAPA': 'media'
}

// Helpers
const normalizeText = (text) => {
  if (!text) return ''
  return text.toString().normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toUpperCase()
    .trim()
}

// Color scale mapping matching Nivel de Cobertura
const getGeoColor = (deptName) => {
  const normName = normalizeText(deptName)
  const coverage = deptCoverage[normName] || 'media'
  
  if (coverage === 'alta') return 'rgba(34, 197, 94, 0.75)'  // Emerald
  if (coverage === 'media') return 'rgba(251, 191, 36, 0.75)' // Amber
  return 'rgba(239, 68, 68, 0.75)'                           // Rose/Red
}

// GeoJSON Layer Styling
const deptStyle = (feature) => {
  const name = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre || ''
  return {
    fillColor: getGeoColor(name),
    weight: 2,
    opacity: 1,
    color: '#0f172a', // Slate border matching mockup card aesthetics
    fillOpacity: 0.7
  }
}

// Map Event handlers
const highlightFeature = (e) => {
  const layer = e.target
  layer.setStyle({
    weight: 3.5,
    color: '#34d399', // Emerald glow on hover
    fillOpacity: 0.95
  })
  layer.bringToFront()
}

const resetHighlight = (e) => {
  if (deptosLayer) {
    deptosLayer.resetStyle(e.target)
  }
}

const getTooltipContent = (name) => {
  const normName = normalizeText(name)
  const coverage = deptCoverage[normName] || 'media'
  const capCoverage = coverage.charAt(0).toUpperCase() + coverage.slice(1)
  const colorClass = coverage === 'alta' ? 'text-emerald-400' : coverage === 'media' ? 'text-amber-400' : 'text-rose-400'
  
  return `
    <div class="text-[10px] font-black uppercase mb-1 border-b border-white/20 pb-1">${name}</div>
    <div class="flex items-center gap-1.5 mt-1">
      <span class="opacity-70 font-medium">Cobertura:</span>
      <span class="font-extrabold ${colorClass}">${capCoverage}</span>
    </div>
  `
}

// Apex charts configurations

// 1. Compliance radial progress gauge
const radialOptions = computed(() => ({
  chart: {
    type: 'radialBar',
    sparkline: { enabled: true }
  },
  colors: ['#10b981'],
  plotOptions: {
    radialBar: {
      startAngle: -90,
      endAngle: 90,
      track: {
        background: '#e2e8f0',
        strokeWidth: '97%',
        margin: 5,
        dropShadow: {
          enabled: false
        }
      },
      dataLabels: {
        name: { show: false },
        value: {
          offsetY: -2,
          fontSize: '32px',
          fontWeight: '900',
          color: undefined,
          formatter: function (val) {
            return val + "%";
          }
        }
      }
    }
  },
  grid: {
    padding: {
      top: -10
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      type: 'horizontal',
      shadeIntensity: 0.5,
      gradientToColors: ['#34d399'],
      inverseColors: true,
      opacityFrom: 1,
      opacityTo: 1,
      stops: [0, 100]
    }
  },
  labels: ['Cumplimiento general']
}))

// 2. Bar chart - Activities by Department
const barSeries = ref([{
  name: 'Actividades',
  data: [2156, 1842, 1325, 1987, 1432]
}])

const barOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
    sparkline: { enabled: false }
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      columnWidth: '55%',
      distributed: true,
      dataLabels: { position: 'top' }
    }
  },
  colors: ['#22c55e', '#3b82f6', '#a855f7', '#f59e0b', '#06b6d4'],
  dataLabels: {
    enabled: true,
    formatter: (val) => new Intl.NumberFormat('en-US').format(val),
    offsetY: -20,
    style: {
      fontSize: '10px',
      fontWeight: 'bold',
      colors: ['#475569']
    }
  },
  legend: { show: false },
  xaxis: {
    categories: ['Sanidad Animal', 'Sanidad Vegetal', 'Inocuidad', 'Regulaciones', 'Cuarentena / Vig.'],
    labels: {
      style: {
        fontSize: '9px',
        fontWeight: 'bold',
        colors: '#64748b'
      }
    },
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: { show: false },
  grid: { show: false },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (val) => `${val} actividades`
    }
  }
}))

// 3. Donut - Program States
const donutProgramasSeries = ref([17, 6, 4])
const donutProgramasOptions = computed(() => ({
  chart: {
    type: 'donut',
  },
  labels: ['En Ejecución', 'En Proceso', 'Pendientes'],
  colors: ['#10b981', '#fbbf24', '#f87171'],
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: {
          show: true,
          name: { show: true, fontSize: '10px', fontWeight: 'bold', color: '#64748b', offsetY: -5 },
          value: { show: true, fontSize: '20px', fontWeight: '900', color: '#0f172a', offsetY: 5 },
          total: {
            show: true,
            label: 'Total',
            fontSize: '10px',
            fontWeight: 'bold',
            color: '#64748b',
            formatter: () => '27'
          }
        }
      }
    }
  },
  dataLabels: { enabled: false },
  legend: {
    position: 'bottom',
    fontSize: '10px',
    fontWeight: 'bold',
    labels: { colors: '#64748b' }
  },
  tooltip: { theme: 'dark' }
}))

// 4. Donut - Activity Types
const donutActividadesSeries = ref([3256, 2147, 1536, 1128, 675])
const donutActividadesOptions = computed(() => ({
  chart: {
    type: 'donut',
  },
  labels: ['Inspección', 'Supervisión', 'Monitoreo', 'Muestreo', 'Otras'],
  colors: ['#3b82f6', '#06b6d4', '#fbbf24', '#f472b6', '#a78bfa'],
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: {
          show: true,
          name: { show: true, fontSize: '10px', fontWeight: 'bold', color: '#64748b', offsetY: -5 },
          value: { show: true, fontSize: '20px', fontWeight: '900', color: '#0f172a', offsetY: 5 },
          total: {
            show: true,
            label: 'Total',
            fontSize: '10px',
            fontWeight: 'bold',
            color: '#64748b',
            formatter: () => '8,742'
          }
        }
      }
    }
  },
  dataLabels: { enabled: false },
  legend: {
    position: 'bottom',
    fontSize: '10px',
    fontWeight: 'bold',
    labels: { colors: '#64748b' }
  },
  tooltip: { theme: 'dark' }
}))

// 5. Activity Tendency (Area/Line Chart)
const areaSeries = ref([{
  name: 'Actividades',
  data: [4256, 5126, 6014, 7125, 7580, 8742]
}])

const areaOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false }
  },
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: 3,
    colors: ['#10b981']
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.45,
      opacityTo: 0.05,
      stops: [0, 100],
      colorStops: [
        { offset: 0, color: '#10b981', opacity: 0.4 },
        { offset: 100, color: '#34d399', opacity: 0.01 }
      ]
    }
  },
  colors: ['#10b981'],
  xaxis: {
    categories: ['Dic 2024', 'Ene 2025', 'Feb 2025', 'Mar 2025', 'Abr 2025', 'May 2025'],
    labels: {
      style: {
        fontSize: '10px',
        fontWeight: 'bold',
        colors: '#64748b'
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '10px',
        fontWeight: 'bold',
        colors: '#64748b'
      },
      formatter: (val) => new Intl.NumberFormat('en-US').format(val)
    }
  },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4,
    xaxis: { lines: { show: true } }
  },
  tooltip: {
    theme: 'dark',
    y: {
      formatter: (val) => new Intl.NumberFormat('en-US').format(val)
    }
  }
}))

// 6. Top Municipios Data (Progress bars)
const topMunicipios = ref([
  { rank: 1, name: 'Guatemala', value: '1,256', percent: 100 },
  { rank: 2, name: 'Escuintla', value: '987', percent: 78 },
  { rank: 3, name: 'Quetzaltenango', value: '785', percent: 62 },
  { rank: 4, name: 'Petén', value: '642', percent: 51 },
  { rank: 5, name: 'Chimaltenango', value: '589', percent: 46 }
])

// Alerts Data Feed
const alerts = ref([
  { id: 1, type: 'error', title: 'Brote detectado en aves', message: 'Caso sospechoso de Newcastle en aves de patio, Huehuetenango.', time: 'Hoy, 08:45 AM' },
  { id: 2, type: 'warning', title: 'Retraso en entrega de informe', message: 'Técnicos de San Marcos reportan demora en carga de muestras.', time: 'Hoy, 07:30 AM' },
  { id: 3, type: 'info', title: 'Vencimiento de licencia', message: 'El permiso de transporte para la placa C-128BKK expira mañana.', time: 'Ayer, 05:15 PM' }
])

// Life Cycle hook - Setup Leaflet Map
onMounted(async () => {
  // Initialize the Map without tile layer to show clean polygons floating on background
  map = L.map(mapContainer.value, {
    center: guatemalaCenter,
    zoom: 7,
    minZoom: 6,
    maxZoom: 9,
    maxBounds: [[12.5, -93.5], [18.5, -87.0]],
    maxBoundsViscosity: 1.0,
    zoomControl: false,
    attributionControl: false,
    dragging: true,
    scrollWheelZoom: false,
    doubleClickZoom: false
  })

  // Optional: A very subtle base tile for guidance, or transparent grid
  // In our case we will render ONLY the vector polygons on a transparent background
  
  try {
    const response = await fetch('/assets/vider/maps/deptos.json')
    if (response.ok) {
      const topoData = await response.json()
      const key = Object.keys(topoData.objects)[0]
      const geoData = topojson.feature(topoData, topoData.objects[key])
      
      deptosLayer = L.geoJSON(geoData, {
        style: deptStyle,
        onEachFeature: (feature, layer) => {
          layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight
          })
          const name = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre || ''
          layer.bindTooltip(getTooltipContent(name), { className: 'custom-leaflet-tooltip', sticky: true })
        }
      }).addTo(map)
      
      map.fitBounds(guatemalaBounds)
    }
  } catch (error) {
    console.error('Error loading Guatemala departments GeoJSON:', error)
  }

  // Load real notifications from Backend to populate alert feeds if possible
  try {
    const { data } = await api.get('/notifications')
    if (data && data.success && data.data && data.data.length > 0) {
      // Map API notifications to Alert items
      alerts.value = data.data.slice(0, 3).map((item, idx) => {
        return {
          id: item.id || idx,
          type: item.type || 'info',
          title: item.title || 'Notificación',
          message: item.message || '',
          time: 'Reciente'
        }
      })
    }
  } catch (e) {
    console.log('Utilizando alertas mockeadas debido a falta de conexion de base de datos activa.')
  }
})

onUnmounted(() => {
  if (map) {
    map.remove()
  }
})
</script>

<style>
/* Leaflet custom tooltip theme matching dark background premium look */
.custom-leaflet-tooltip {
  background: rgba(15, 23, 42, 0.9) !important;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  border-radius: 12px !important;
  color: white !important;
  padding: 8px 12px !important;
  font-weight: bold !important;
  font-size: 10px !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4) !important;
}

.leaflet-container {
  background: transparent !important;
}

.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
