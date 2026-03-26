<script setup>
</script>

<template>
  <div class="animate-in fade-in duration-500 space-y-8">
    <!-- Hero Greeting -->
    <section>
      <h2 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight">Panel de Control General</h2>
      <p class="text-on-surface-variant mt-1">Bienvenido de nuevo, Admin. Aquí está el resumen de bienestar animal hoy.</p>
    </section>

    <!-- Global Stats: Bento Grid Layout -->
    <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Stat 1 -->
      <div class="glass-card p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
          <div class="p-2 bg-primary-fixed rounded-lg text-primary">
            <span class="material-symbols-outlined filled">inventory_2</span>
          </div>
          <span class="text-xs font-bold text-tertiary bg-tertiary-fixed px-2 py-1 rounded-full">+12% hoy</span>
        </div>
        <div>
          <p class="text-on-surface-variant text-xs font-medium uppercase tracking-wider">Total Denuncias</p>
          <p class="text-3xl font-headline font-extrabold text-on-surface">1,284</p>
        </div>
      </div>

      <!-- Stat 2 -->
      <div class="glass-card p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
          <div class="p-2 bg-secondary-fixed/50 rounded-lg text-secondary">
            <span class="material-symbols-outlined filled">pending_actions</span>
          </div>
          <span class="text-xs font-medium text-on-surface-variant">En proceso</span>
        </div>
        <div>
          <p class="text-on-surface-variant text-xs font-medium uppercase tracking-wider">Casos Abiertos</p>
          <p class="text-3xl font-headline font-extrabold text-on-surface">342</p>
        </div>
      </div>

      <!-- Stat 3: Urgent Focus -->
      <div class="bg-gradient-to-br from-error-container to-[#ffb4ab] p-6 rounded-xl shadow-sm flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
          <div class="p-2 bg-white/40 rounded-lg text-error">
            <span class="material-symbols-outlined filled">warning</span>
          </div>
          <div class="flex -space-x-2">
            <div class="w-6 h-6 rounded-full border-2 border-white bg-slate-200"></div>
            <div class="w-6 h-6 rounded-full border-2 border-white bg-slate-300"></div>
          </div>
        </div>
        <div>
          <p class="text-on-error-container text-xs font-bold uppercase tracking-wider">Urgentes (&gt;5 días)</p>
          <p class="text-3xl font-headline font-extrabold text-on-error-container">28</p>
        </div>
      </div>

      <!-- Stat 4 -->
      <div class="glass-card p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
          <div class="p-2 bg-tertiary-fixed rounded-lg text-tertiary">
            <span class="material-symbols-outlined filled">task_alt</span>
          </div>
        </div>
        <div>
          <p class="text-on-surface-variant text-xs font-medium uppercase tracking-wider">Procesados Hoy</p>
          <p class="text-3xl font-headline font-extrabold text-on-surface">45</p>
        </div>
      </div>
    </section>

    <!-- Middle Section: Activity and Roles -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Recent Activities (Editorial Glass) -->
      <div class="lg:col-span-2 glass-card rounded-xl p-8 border border-white/10">
        <div class="flex justify-between items-center mb-8">
          <h3 class="font-headline text-xl font-bold">Actividad Reciente</h3>
          <button class="text-primary text-sm font-semibold flex items-center gap-1 hover:underline">
            Ver todo <span class="material-symbols-outlined text-sm">arrow_forward</span>
          </button>
        </div>
        <div class="space-y-6">
          <!-- Activity Item -->
          <div class="flex gap-4 items-start p-4 rounded-lg hover:bg-surface-container-low transition-colors duration-200">
            <img alt="Technician" class="w-10 h-10 rounded-full object-cover flex-shrink-0" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBS43EbCeMq_TDPquwVH1uefUBVwZ7uvNkYgebu90FjEvRxSan-4gUOHGEmD5l3D5gobCJRSpltuboqabXrBRd14c0w_Q1lANafvjJD7bBVNa5QW7Ebv6j_sLaRQhtgOLDu_djrF1GRhsVaEyB5K4e0W-edkqrHfXbHi-tMx7SW6SPY-c4rhVYL5NTVDaRGxcpp_4pr4DeYe5UggHOP-yjuIyOenZQILaJ6JG8GGcGorEAyyHkn8Pw1gLt0BMsRwGc3dOsT_IAd48Jb" />
            <div class="flex-1">
              <div class="flex justify-between items-center">
                <p class="text-sm font-bold text-on-surface">Carlos Mendoza <span class="font-normal text-on-surface-variant">actualizó el estado de</span> #DEN-992</p>
                <span class="text-[10px] text-outline">Hace 12 min</span>
              </div>
              <p class="text-xs text-on-surface-variant mt-1">Cambio de "En Espera" a "Visita Programada" para rescate canino en Zona 10.</p>
              <div class="mt-2 flex gap-2">
                <span class="text-[10px] bg-tertiary-fixed text-on-tertiary-fixed px-2 py-0.5 rounded-full font-bold">Rescate</span>
                <span class="text-[10px] bg-primary-fixed text-on-primary-fixed px-2 py-0.5 rounded-full font-bold">Zona 10</span>
              </div>
            </div>
          </div>
          <!-- Activity Item -->
          <div class="flex gap-4 items-start p-4 rounded-lg hover:bg-surface-container-low transition-colors duration-200">
            <img alt="Technician" class="w-10 h-10 rounded-full object-cover flex-shrink-0" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCrZgXS3JH0QHBURvKB0kngyCeThNuv1gRFO_qWaYv4fZ6eXmDgXb_-gvaEV9twgvdF0MdSMHIL-J8w23GsXUdQQektR3885wAO6t0JAQC6f20sUieWLq2F8ZVI3gmBPwRFguL_2u8JLdD7uapGZU0ir4H9LjbGRypqH37dZVjQnwmROIOAO6Nh2ouQMvVRBIdJYp6wlqrR3XYK0NLqBJdiT8W_nul0r0PgmT9bGBcH_h2tnsf8GeeINMptG3eG10FFW2JQGWoj-BYK" />
            <div class="flex-1">
              <div class="flex justify-between items-center">
                <p class="text-sm font-bold text-on-surface">Ana Lucia Ruiz <span class="font-normal text-on-surface-variant">cerró el caso</span> #DEN-845</p>
                <span class="text-[10px] text-outline">Hace 45 min</span>
              </div>
              <p class="text-xs text-on-surface-variant mt-1">Resolución satisfactoria: Adopción confirmada tras denuncia por abandono.</p>
              <div class="mt-2">
                <span class="text-[10px] bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded-full font-bold">Adoptado</span>
              </div>
            </div>
          </div>
          <!-- Activity Item -->
          <div class="flex gap-4 items-start p-4 rounded-lg hover:bg-surface-container-low transition-colors duration-200">
            <div class="w-10 h-10 rounded-full bg-surface-container-highest flex items-center justify-center text-outline">
              <span class="material-symbols-outlined">system_update_alt</span>
            </div>
            <div class="flex-1">
              <div class="flex justify-between items-center">
                <p class="text-sm font-bold text-on-surface">Sistema <span class="font-normal text-on-surface-variant">generó reporte mensual</span></p>
                <span class="text-[10px] text-outline">Hace 2 horas</span>
              </div>
              <p class="text-xs text-on-surface-variant mt-1">El reporte de impacto de servicios de Octubre ya está disponible para revisión.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Role Management Shortcut -->
      <div class="glass-card rounded-xl p-8 border border-white/10 flex flex-col h-full">
        <h3 class="font-headline text-xl font-bold mb-6">Carga por Técnico</h3>
        <div class="space-y-6 flex-1">
          <!-- Tech 1 -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs font-bold uppercase tracking-tight">
              <span class="text-on-surface">Carlos Mendoza</span>
              <span class="text-primary">12 Casos</span>
            </div>
            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
              <div class="h-full bg-primary rounded-full" style="width: 80%"></div>
            </div>
          </div>
          <!-- Tech 2 -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs font-bold uppercase tracking-tight">
              <span class="text-on-surface">Ana Lucia Ruiz</span>
              <span class="text-primary">8 Casos</span>
            </div>
            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
              <div class="h-full bg-primary rounded-full" style="width: 55%"></div>
            </div>
          </div>
          <!-- Tech 3 -->
          <div class="space-y-2">
            <div class="flex justify-between text-xs font-bold uppercase tracking-tight">
              <span class="text-on-surface">Roberto Gomez</span>
              <span class="text-primary">15 Casos</span>
            </div>
            <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
              <div class="h-full bg-error rounded-full" style="width: 95%"></div>
            </div>
          </div>
        </div>
        <button class="w-full py-4 mt-8 rounded-xl bg-surface-container-high text-on-surface font-bold text-sm hover:bg-surface-container-highest transition-colors">
          Gestionar Personal
        </button>
      </div>
    </div>

    <!-- News & Services Grid -->
    <section class="space-y-6">
      <div class="flex justify-between items-end">
        <div>
          <h3 class="font-headline text-xl font-bold text-on-surface">Gestión de Contenido App</h3>
          <p class="text-sm text-on-surface-variant">Controla lo que los usuarios ven en la aplicación móvil.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- News Management Card -->
        <div class="group glass-card rounded-xl overflow-hidden border border-white/20 hover:shadow-xl transition-all duration-300">
          <div class="h-32 bg-slate-200 overflow-hidden relative">
            <img alt="News Preview" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBF8BuKR49DpxNbjeD5bzENNN0daOdCKYquXSiT1wn1N1Qbst7O05YKkZBQ1FjHYX46CkgM47J_ioMJ-7usFk6Ho7FxoZLcudPWglhoynwuHvNLRBanf2IWeEhTVM0wdlE8Rn9qo95k-u6ehyW-PCB9FP3R3borWo0x9Ev2XsDVUMVhd5UJCCaWsCkFkAmhP2Urhi__eEc_pdjwQkReedtv1S7mV6CjNvUTtebx7yeLhOlMiCx8Xd0hiTACjARSdVqrEbo5JMBJpbxx" />
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute top-3 left-3 bg-white/90 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">Noticias</div>
          </div>
          <div class="p-5">
            <h4 class="font-bold text-sm text-on-surface mb-2">Campaña de Vacunación 2024</h4>
            <p class="text-xs text-on-surface-variant line-clamp-2 mb-4">Iniciamos la jornada nacional en conjunto con salud pública...</p>
            <button class="w-full flex items-center justify-center gap-2 py-2 text-primary font-bold text-xs border border-primary/20 rounded-lg hover:bg-primary/5 transition-colors">
              <span class="material-symbols-outlined text-sm">edit</span> Editar Noticia
            </button>
          </div>
        </div>
        <!-- Services Management Card -->
        <div class="group glass-card rounded-xl overflow-hidden border border-white/20 hover:shadow-xl transition-all duration-300">
          <div class="h-32 bg-slate-200 overflow-hidden relative">
            <img alt="Services Preview" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD3nfzo3vwjREzQ1gZZAr-YyilplEFJb4TuTfa0rxnuSVuP0Tu5-AYMLtA0vqoN7AdDC00W5F2OLfSTopbXhStvL1yLvCl4Vj0d94fCgS6IyNpo59OZo386bMRqWcLOo3PHGpp2iEuGPjJj_JWTE-wnkEY_mpxWTI1HgGbFdm-xTPhwx6Q269qtH9Df01XiFGp67KRlkLl-2_29gN-iz8R9f4wIEV4sKEZEc4X_0rEYWdF9_fSPcye9xHVCZJ81e3uK8KNb3HWp5lNx" />
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="absolute top-3 left-3 bg-white/90 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">Servicios</div>
          </div>
          <div class="p-5">
            <h4 class="font-bold text-sm text-on-surface mb-2">Clínicas Móviles - Chimaltenango</h4>
            <p class="text-xs text-on-surface-variant line-clamp-2 mb-4">Actualización de horarios y ubicaciones para el mes de Noviembre.</p>
            <button class="w-full flex items-center justify-center gap-2 py-2 text-primary font-bold text-xs border border-primary/20 rounded-lg hover:bg-primary/5 transition-colors">
              <span class="material-symbols-outlined text-sm">edit</span> Editar Servicio
            </button>
          </div>
        </div>
        <!-- Rapid Access Actions -->
        <div class="lg:col-span-2 glass-card rounded-xl border border-white/20 p-6 flex flex-col justify-center bg-gradient-to-br from-white/40 to-secondary-container/10">
          <h4 class="font-headline text-lg font-bold mb-4">Acciones Rápidas de Admin</h4>
          <div class="grid grid-cols-2 gap-4">
            <button class="flex items-center gap-3 p-4 bg-white/50 rounded-xl hover:bg-white transition-all shadow-sm">
              <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">post_add</span>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold">Nueva Noticia</p>
                <p class="text-[10px] text-outline">Publicar contenido</p>
              </div>
            </button>
            <button class="flex items-center gap-3 p-4 bg-white/50 rounded-xl hover:bg-white transition-all shadow-sm">
              <div class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center text-tertiary">
                <span class="material-symbols-outlined">add_task</span>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold">Nuevo Servicio</p>
                <p class="text-[10px] text-outline">Actualizar red</p>
              </div>
            </button>
            <button class="flex items-center gap-3 p-4 bg-white/50 rounded-xl hover:bg-white transition-all shadow-sm">
              <div class="w-10 h-10 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined">person_add</span>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold">Alta Técnico</p>
                <p class="text-[10px] text-outline">Gestionar equipo</p>
              </div>
            </button>
            <button class="flex items-center gap-3 p-4 bg-white/50 rounded-xl hover:bg-white transition-all shadow-sm">
              <div class="w-10 h-10 rounded-full bg-surface-container-highest flex items-center justify-center text-outline">
                <span class="material-symbols-outlined">description</span>
              </div>
              <div class="text-left">
                <p class="text-sm font-bold">Reporte PDF</p>
                <p class="text-[10px] text-outline">Exportar datos</p>
              </div>
            </button>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Contextual FAB -->
    <button class="fixed bottom-8 right-8 w-14 h-14 rounded-full bg-gradient-to-br from-primary to-primary-container text-white shadow-2xl flex items-center justify-center group hover:scale-110 active:scale-95 transition-all z-50">
      <span class="material-symbols-outlined scale-125 filled">add</span>
      <div class="absolute right-full mr-4 bg-inverse-surface text-inverse-on-surface px-3 py-1.5 rounded-lg text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
        Nueva Alerta Crítica
      </div>
    </button>
  </div>
</template>
