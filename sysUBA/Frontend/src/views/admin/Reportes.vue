<template>
  <div class="animate-in fade-in duration-500 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h3 class="text-3xl font-extrabold font-headline tracking-tight text-on-surface">Panel de Reportes</h3>
        <p class="text-on-surface-variant mt-1">Análisis detallado de la gestión de bienestar animal a nivel nacional.</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="flex items-center gap-2 px-5 py-2.5 bg-surface-container-high text-on-surface rounded-full font-bold text-sm hover:bg-surface-container-highest transition-all">
          <span class="material-symbols-outlined text-lg">calendar_today</span>
          <span>Últimos 30 días</span>
        </button>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-secondary text-white rounded-full font-bold text-sm hover:opacity-90 transition-all shadow-md">
          <span class="material-symbols-outlined text-lg">description</span>
          <span>Exportar a PDF</span>
        </button>
        <button class="flex items-center gap-2 px-5 py-2.5 bg-tertiary-container text-on-tertiary-container rounded-full font-bold text-sm hover:opacity-90 transition-all shadow-md">
          <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1">table_chart</span>
          <span>Exportar a Excel</span>
        </button>
      </div>
    </div>

    <!-- Bento Grid of Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
            <span class="material-symbols-outlined">description</span>
          </div>
          <span class="text-xs font-bold text-tertiary bg-tertiary-fixed px-2 py-1 rounded-full">+12%</span>
        </div>
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Denuncias</p>
        <p class="text-2xl font-black text-on-surface mt-1">1,248</p>
      </div>
      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary">
            <span class="material-symbols-outlined">schedule</span>
          </div>
          <span class="text-xs font-bold text-on-error-container bg-error-container px-2 py-1 rounded-full">-4h</span>
        </div>
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tiempo Promedio</p>
        <p class="text-2xl font-black text-on-surface mt-1">3.2 días</p>
      </div>
      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-lg bg-tertiary/10 flex items-center justify-center text-tertiary">
            <span class="material-symbols-outlined">pets</span>
          </div>
          <span class="text-xs font-bold text-primary bg-primary-fixed px-2 py-1 rounded-full">85%</span>
        </div>
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Especies Domésticas</p>
        <p class="text-2xl font-black text-on-surface mt-1">1,061</p>
      </div>
      <div class="bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-lg bg-error/10 flex items-center justify-center text-error">
            <span class="material-symbols-outlined">priority_high</span>
          </div>
          <span class="text-xs font-bold text-on-surface-variant bg-surface-container-high px-2 py-1 rounded-full">Meta: 50</span>
        </div>
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Casos Críticos</p>
        <p class="text-2xl font-black text-on-surface mt-1">42</p>
      </div>
    </div>

    <!-- Analytical Visualizations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
      <!-- Chart: Denuncias per Month -->
      <div class="md:col-span-8 bg-surface-container-lowest p-8 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h4 class="text-lg font-bold text-on-surface">Denuncias por Mes</h4>
            <p class="text-sm text-on-surface-variant">Tendencia histórica del año actual</p>
          </div>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-primary"></span>
              <span class="text-xs font-medium">2023</span>
            </div>
            <div class="flex items-center gap-1.5 ml-4">
              <span class="w-3 h-3 rounded-full bg-secondary-container"></span>
              <span class="text-xs font-medium">2024</span>
            </div>
          </div>
        </div>
        <!-- Mock Line Chart -->
        <div class="relative h-64 w-full flex items-end justify-between pt-4">
          <div class="absolute inset-0 flex flex-col justify-between py-2 border-l border-b border-outline-variant/30">
            <div class="w-full border-t border-outline-variant/10"></div>
            <div class="w-full border-t border-outline-variant/10"></div>
            <div class="w-full border-t border-outline-variant/10"></div>
            <div class="w-full border-t border-outline-variant/10"></div>
          </div>
          <!-- Month Bars (Mocking a line chart trend with columns for visual interest) -->
          <div class="w-full flex items-end justify-around px-4 relative z-10">
            <div class="w-8 bg-primary/20 rounded-t-sm h-1/4"></div>
            <div class="w-8 bg-primary/40 rounded-t-sm h-2/4"></div>
            <div class="w-8 bg-primary/60 rounded-t-sm h-3/5"></div>
            <div class="w-8 bg-primary/80 rounded-t-sm h-4/5"></div>
            <div class="w-8 bg-primary rounded-t-sm h-full"></div>
            <div class="w-8 bg-primary/90 rounded-t-sm h-5/6"></div>
          </div>
        </div>
        <div class="flex justify-around mt-4 text-[10px] font-bold text-on-surface-variant uppercase tracking-tighter">
          <span>Ene</span><span>Feb</span><span>Mar</span><span>Abr</span><span>May</span><span>Jun</span>
        </div>
      </div>

      <!-- Chart: Frequent Species -->
      <div class="md:col-span-4 bg-surface-container-lowest p-8 rounded-xl shadow-sm border border-outline-variant/10">
        <h4 class="text-lg font-bold text-on-surface mb-2">Especies Frecuentes</h4>
        <p class="text-sm text-on-surface-variant mb-8">Distribución por tipo de animal</p>
        <div class="space-y-6">
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="font-medium">Perros</span>
              <span class="font-bold text-primary">65%</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
              <div class="bg-primary h-full w-[65%]"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="font-medium">Gatos</span>
              <span class="font-bold text-secondary">22%</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
              <div class="bg-secondary h-full w-[22%]"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="font-medium">Equinos</span>
              <span class="font-bold text-tertiary">8%</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
              <div class="bg-tertiary h-full w-[8%]"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="font-medium">Otros</span>
              <span class="font-bold text-on-surface-variant">5%</span>
            </div>
            <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
              <div class="bg-outline h-full w-[5%]"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart: Geographical Heatmap -->
      <div class="md:col-span-6 bg-surface-container-lowest p-8 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h4 class="text-lg font-bold text-on-surface">Mapa de Calor</h4>
            <p class="text-sm text-on-surface-variant">Concentración de casos por departamento</p>
          </div>
          <button class="text-primary text-sm font-bold flex items-center gap-1">
            Ver detalles <span class="material-symbols-outlined text-sm">chevron_right</span>
          </button>
        </div>
        <div class="h-80 rounded-xl overflow-hidden relative">
          <img alt="Map of Guatemala" class="w-full h-full object-cover grayscale opacity-50" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCXF8ZbpdKIFU7xRytE2zyj-fOaRexYbQz6LDCAa6Xhg6JqtG7rd24rL8FBifE2Kygt4cte5gkBla7S8IYW5K7UdCG9rrwhoizw3uJa9dJyw6IjT_iJHgKLrpYZLt-2c4q7ePmQDd-xEx0Sc_O2Pd12kRslv80_ntzmWtRzMPwYvZtJb8DxnSsDHjmFDSKGdYWyeT4T-KirzSyHYM5aAcqMywclj8BMfGt3LS_F6uQ2Yr_fojIUMiU2mObHnluECnFjZTxIXGtMRz6Q" />
          <!-- Simulated heat markers -->
          <div class="absolute top-1/4 left-1/3 w-16 h-16 bg-primary/30 rounded-full animate-pulse blur-xl"></div>
          <div class="absolute top-1/2 left-1/2 w-12 h-12 bg-secondary/30 rounded-full animate-pulse blur-lg"></div>
          <div class="absolute top-2/3 left-1/4 w-20 h-20 bg-primary/20 rounded-full animate-pulse blur-2xl"></div>
          <div class="absolute bottom-4 left-4 p-4 glass-card rounded-lg border border-white/20">
            <p class="text-[10px] font-bold text-on-surface-variant uppercase mb-2">Punto Crítico</p>
            <p class="text-sm font-bold text-blue-900 leading-none">Guatemala (Capital)</p>
            <p class="text-xs text-on-surface-variant mt-1">452 Reportes Activos</p>
          </div>
        </div>
      </div>

      <!-- Chart: Resolution Time per Stage -->
      <div class="md:col-span-6 bg-surface-container-lowest p-8 rounded-xl shadow-sm border border-outline-variant/10">
        <h4 class="text-lg font-bold text-on-surface mb-2">Tiempo de Resolución</h4>
        <p class="text-sm text-on-surface-variant mb-8">Promedio de días por etapa administrativa</p>
        <div class="grid grid-cols-2 gap-8 h-64">
          <div class="flex flex-col justify-end items-center gap-4">
            <div class="w-full flex flex-col gap-1 items-center">
              <span class="text-xs font-bold text-on-surface-variant">0.8d</span>
              <div class="w-16 bg-secondary-container rounded-t-xl h-12"></div>
            </div>
            <span class="text-[11px] font-bold text-on-surface-variant text-center uppercase">Recepción</span>
          </div>
          <div class="flex flex-col justify-end items-center gap-4">
            <div class="w-full flex flex-col gap-1 items-center">
              <span class="text-xs font-bold text-on-surface-variant">1.5d</span>
              <div class="w-16 bg-primary-fixed-dim rounded-t-xl h-24"></div>
            </div>
            <span class="text-[11px] font-bold text-on-surface-variant text-center uppercase">Inspección</span>
          </div>
          <div class="flex flex-col justify-end items-center gap-4">
            <div class="w-full flex flex-col gap-1 items-center">
              <span class="text-xs font-bold text-on-surface-variant">2.1d</span>
              <div class="w-16 bg-primary-container rounded-t-xl h-32"></div>
            </div>
            <span class="text-[11px] font-bold text-on-surface-variant text-center uppercase">Resolución</span>
          </div>
          <div class="flex flex-col justify-end items-center gap-4">
            <div class="w-full flex flex-col gap-1 items-center">
              <span class="text-xs font-bold text-on-surface-variant">0.5d</span>
              <div class="w-16 bg-tertiary-fixed rounded-t-xl h-8"></div>
            </div>
            <span class="text-[11px] font-bold text-on-surface-variant text-center uppercase">Cierre</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Table (Clean Info Layout) -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/10 overflow-hidden">
      <div class="p-6 border-b border-outline-variant/10 flex items-center justify-between">
        <h4 class="text-lg font-bold text-on-surface">Últimos Casos Reportados</h4>
        <button class="text-primary text-sm font-bold">Ver Historial Completo</button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-surface-container-low text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
              <th class="px-6 py-4">Expediente</th>
              <th class="px-6 py-4">Fecha</th>
              <th class="px-6 py-4">Especie</th>
              <th class="px-6 py-4">Ubicación</th>
              <th class="px-6 py-4">Estado</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            <tr class="hover:bg-surface-container-low/30 transition-colors">
              <td class="px-6 py-4 font-bold text-blue-900 text-sm">#UBA-2024-1052</td>
              <td class="px-6 py-4 text-sm text-on-surface-variant">24 May 2024</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">pets</span>
                  <span class="text-sm font-medium">Canino</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">Zona 10, Cd. Guatemala</td>
              <td class="px-6 py-4">
                <span class="bg-tertiary-fixed text-on-tertiary-fixed px-3 py-1 rounded-full text-xs font-bold">Procesado</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined text-lg">visibility</span></button>
              </td>
            </tr>
            <tr class="hover:bg-surface-container-low/30 transition-colors">
              <td class="px-6 py-4 font-bold text-blue-900 text-sm">#UBA-2024-1051</td>
              <td class="px-6 py-4 text-sm text-on-surface-variant">23 May 2024</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">pets</span>
                  <span class="text-sm font-medium">Equino</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">Escuintla, Cabecera</td>
              <td class="px-6 py-4">
                <span class="bg-primary-fixed text-on-primary-fixed px-3 py-1 rounded-full text-xs font-bold">En Revisión</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined text-lg">visibility</span></button>
              </td>
            </tr>
            <tr class="hover:bg-surface-container-low/30 transition-colors">
              <td class="px-6 py-4 font-bold text-blue-900 text-sm">#UBA-2024-1050</td>
              <td class="px-6 py-4 text-sm text-on-surface-variant">23 May 2024</td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">pets</span>
                  <span class="text-sm font-medium">Felino</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm">Mixco, Guatemala</td>
              <td class="px-6 py-4">
                <span class="bg-error-container text-on-error-container px-3 py-1 rounded-full text-xs font-bold">Urgente</span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined text-lg">visibility</span></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
