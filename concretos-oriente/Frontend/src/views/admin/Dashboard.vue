<template>
  <div class="min-h-screen bg-[#050f1a] text-white p-4 md:p-8 space-y-6 pb-24 font-sans">
    
    <!-- HEADER SECTION (Background Image + Welcome + KPI Overlap) -->
    <div class="relative w-full rounded-[40px] overflow-visible">
      <!-- Background Image -->
      <div class="absolute inset-0 rounded-[40px] overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-screen" style="background-image: url('/src/assets/images/dashboard_header.png');"></div>
        <!-- Gradients to blend -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050f1a] via-[#050f1a]/80 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050f1a] via-transparent to-transparent"></div>
      </div>

      <!-- Top Bar / Header Content -->
      <div class="relative z-10 p-10 pt-8 pb-32">
        <!-- Top Nav Items (Search, User, Weather) -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-16">
          
          <!-- Search Bar -->
          <div class="relative w-full max-w-md">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-white/40" />
            <input type="text" placeholder="Buscar proyectos, pagos, documentos, personal..." 
                   class="w-full bg-[#112236]/80 backdrop-blur-md border border-white/10 rounded-full py-3 pl-12 pr-12 text-sm text-white focus:outline-none focus:border-primary/50 shadow-lg placeholder:text-white/30" />
            <div class="absolute right-4 top-1/2 -translate-y-1/2 px-2 py-0.5 rounded-lg bg-white/10 text-[10px] font-mono text-white/50">⌘ K</div>
          </div>

          <!-- Right Actions -->
          <div class="flex flex-wrap items-center gap-6">
            <!-- Notifications -->
            <button class="relative p-3 rounded-full bg-[#112236]/80 backdrop-blur-md border border-white/10 hover:bg-white/10 transition">
              <BellIcon class="w-5 h-5 text-white/70" />
              <span class="absolute top-0 right-0 w-4 h-4 rounded-full bg-red-500 text-[9px] font-bold flex items-center justify-center border-2 border-[#112236]">3</span>
            </button>
            
            <!-- User Profile -->
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-sm shadow-[0_0_15px_rgba(59,130,246,0.4)]">
                EE
              </div>
              <div class="hidden sm:block">
                <p class="text-sm font-bold leading-tight">Elder Elias</p>
                <p class="text-[11px] text-white/50">Administrador</p>
              </div>
            </div>

            <!-- Date -->
            <div class="hidden lg:flex items-center gap-3 bg-[#112236]/80 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/10">
              <CalendarIcon class="w-5 h-5 text-white/50" />
              <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-white/50">Jueves</p>
                <p class="text-xs font-bold">11 de septiembre de 2026</p>
              </div>
            </div>

            <!-- Weather -->
            <div class="hidden xl:flex items-center gap-3 bg-[#112236]/80 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/10">
              <SunIcon class="w-6 h-6 text-yellow-500" />
              <div>
                <p class="text-xs font-bold">27°C</p>
                <p class="text-[10px] uppercase font-bold tracking-wider text-white/50">Sanarate, GT</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Welcome Message -->
        <div class="max-w-2xl relative">
          <h2 class="text-3xl font-light text-white/90 mb-2">¡Hola, <span class="font-bold text-white">Elder!</span></h2>
          <p class="text-sm text-white/60 mb-6 font-medium">Aquí tienes el estado actual de tus proyectos</p>
          
          <h1 class="text-5xl md:text-[64px] font-black leading-none tracking-tight mb-4 uppercase drop-shadow-2xl">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-white/70">Construyendo</span><br/>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-orange-400 to-yellow-600 drop-shadow-[0_0_15px_rgba(250,204,21,0.3)]">El Mañana</span>
          </h1>
          <p class="text-xs md:text-sm font-bold tracking-[0.3em] text-white/70 uppercase">
            Materiales que desarrollan Guatemala
          </p>
        </div>
      </div>
      
      <!-- KPI Cards (Overlapping) -->
      <div class="relative z-20 px-10 -mt-24 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Ingresos -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-emerald-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(16,185,129,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-emerald-500/20 text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                <BanknotesIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-emerald-500/70">Ingresos Totales</p>
                <h3 class="text-2xl font-black text-white mt-1">Q 4,200,000</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-xs font-bold text-emerald-400 flex items-center gap-1">
                <ArrowTrendingUpIcon class="w-3 h-3" /> +12.5%
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">vs. mes anterior</p>
            </div>
            <!-- Mini Sparkline (SVG Mock) -->
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,25 C20,25 30,10 50,15 C70,20 80,5 100,2" fill="none" stroke="#34d399" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(52,211,153,0.8)]" />
            </svg>
          </div>
        </div>

        <!-- Gastos -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(244,63,94,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-rose-500/20 text-rose-400 shadow-[0_0_15px_rgba(244,63,94,0.3)]">
                <DocumentTextIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-rose-500/70">Gastos Totales</p>
                <h3 class="text-2xl font-black text-white mt-1">Q 2,800,000</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-xs font-bold text-rose-400 flex items-center gap-1">
                <ArrowTrendingUpIcon class="w-3 h-3" /> +3.2%
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">vs. mes anterior</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,25 C20,20 30,28 50,18 C70,8 80,15 100,5" fill="none" stroke="#fb7185" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(251,113,133,0.8)]" />
            </svg>
          </div>
        </div>

        <!-- Flota -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-blue-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(59,130,246,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-blue-500/20 text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.3)]">
                <TruckIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-blue-500/70">Flota Activa</p>
                <h3 class="text-2xl font-black text-white mt-1">124</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-[11px] font-bold text-white/70">
                de 150 unidades
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">Disponibilidad</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,15 C20,15 30,25 50,20 C70,15 80,5 100,10" fill="none" stroke="#60a5fa" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(96,165,250,0.8)]" />
            </svg>
          </div>
        </div>

        <!-- Seguridad -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-yellow-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(234,179,8,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-yellow-500/20 text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.3)]">
                <ShieldCheckIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-yellow-500/70">Puntaje Seguridad</p>
                <h3 class="text-2xl font-black text-white mt-1">98.2</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-xs font-bold text-yellow-400">
                Excelente
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">Clasificación</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,10 C20,5 30,20 50,15 C70,10 80,18 100,5" fill="none" stroke="#facc15" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(250,204,21,0.8)]" />
            </svg>
          </div>
        </div>

      </div>
    </div>

    <!-- MIDDLE ROW: Charts & Map -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 pt-6">
      
      <!-- Resumen Financiero Chart -->
      <div class="xl:col-span-2 bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden">
        <!-- Glow effect -->
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-blue-500/10 blur-[100px] rounded-full pointer-events-none"></div>
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-10 relative z-10 gap-4">
          <div class="flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
              <ChartBarIcon class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-white">Resumen Financiero</h2>
              <p class="text-white/40 text-xs mt-1">Ingresos y gastos proyectados vs. reales (últimos 6 meses)</p>
            </div>
          </div>
          
          <div class="flex items-center gap-4">
            <select class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-blue-500 cursor-pointer appearance-none">
              <option>Últimos 6 meses</option>
              <option>Este Año</option>
            </select>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.6)]"></span>
              <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Ingresos</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-orange-400 shadow-[0_0_10px_rgba(251,146,60,0.6)]"></span>
              <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Gastos</span>
            </div>
          </div>
        </div>
        
        <!-- Mock Bar Chart -->
        <div class="h-[280px] w-full flex items-end justify-between gap-2 sm:gap-6 relative z-10 pl-10 pb-6 border-b border-white/10">
          <!-- Y Axis -->
          <div class="absolute left-0 top-0 bottom-6 flex flex-col justify-between text-[10px] text-white/30 font-mono">
            <span>Q 6.0M</span>
            <span>Q 4.0M</span>
            <span>Q 2.0M</span>
            <span>Q 0</span>
          </div>
          
          <!-- Bars -->
          <div v-for="(data, i) in financialData" :key="i" class="flex-1 flex flex-col items-center justify-end h-full group">
            <div class="flex items-end gap-1.5 w-full h-full justify-center relative">
              <!-- Tooltip on hover (simulated for May) -->
              <div v-if="i === 4" class="absolute -top-16 left-1/2 -translate-x-1/2 bg-[#112236] border border-white/10 rounded-xl p-3 shadow-2xl z-20 pointer-events-none whitespace-nowrap">
                <p class="text-xs font-bold mb-1">Mayo 2026</p>
                <div class="flex items-center gap-2 text-[10px]">
                  <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  <span class="text-white/70">Ingresos:</span> <span class="font-bold text-white">Q 4.8M</span>
                </div>
                <div class="flex items-center gap-2 text-[10px] mt-1">
                  <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                  <span class="text-white/70">Gastos:</span> <span class="font-bold text-white">Q 3.1M</span>
                </div>
              </div>
              
              <!-- Income Bar -->
              <div :style="{ height: `${data.income}%` }" class="w-[40%] max-w-[24px] bg-gradient-to-t from-blue-700 to-blue-400 rounded-t-lg transition-all duration-500 hover:brightness-125 shadow-[0_0_10px_rgba(59,130,246,0.3)]"></div>
              <!-- Expense Bar -->
              <div :style="{ height: `${data.expense}%` }" class="w-[40%] max-w-[24px] bg-gradient-to-t from-orange-600 to-orange-400 rounded-t-lg transition-all duration-500 hover:brightness-125 shadow-[0_0_10px_rgba(251,146,60,0.3)]"></div>
            </div>
            <span :class="`text-[10px] font-bold tracking-widest mt-4 uppercase ${i === 4 ? 'text-white' : 'text-white/40'}`">{{ data.month }}</span>
          </div>
        </div>
      </div>

      <!-- Estado de Proyectos (Map) -->
      <div class="bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden flex flex-col">
        <div class="flex justify-between items-start mb-6 z-10">
          <div class="flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
              <FolderIcon class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-white">Estado de Proyectos</h2>
              <p class="text-white/40 text-xs mt-1">Ubicación y estado actual</p>
            </div>
          </div>
          <button class="text-xs text-white/50 hover:text-white flex items-center gap-1 transition-colors">
            Ver todos <ChevronRightIcon class="w-3 h-3" />
          </button>
        </div>

        <div class="flex-1 flex flex-col relative z-10">
          <!-- Mock Map Image or styling -->
          <div class="flex-1 relative mb-6 rounded-2xl overflow-hidden border border-white/5 bg-[#050f1a]/50 flex items-center justify-center p-4">
            <!-- Simulated Map Dots -->
            <div class="relative w-full h-full max-w-[200px] mx-auto" style="background: radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 70%);">
              <!-- Pin 1 -->
              <div class="absolute top-[30%] left-[40%]">
                <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_15px_#10b981] animate-ping absolute"></div>
                <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_15px_#10b981] relative z-10"></div>
              </div>
              <!-- Pin 2 -->
              <div class="absolute top-[50%] left-[60%]">
                <div class="w-3 h-3 rounded-full bg-yellow-400 shadow-[0_0_15px_#facc15] relative z-10"></div>
              </div>
              <!-- Pin 3 -->
              <div class="absolute top-[70%] left-[30%]">
                <div class="w-3 h-3 rounded-full bg-blue-500 shadow-[0_0_15px_#3b82f6] relative z-10"></div>
              </div>
              <!-- Pin 4 -->
              <div class="absolute top-[20%] left-[70%]">
                <div class="w-3 h-3 rounded-full bg-rose-500 shadow-[0_0_15px_#f43f5e] relative z-10"></div>
              </div>
              <p class="text-center text-white/20 text-xs absolute inset-0 flex items-center justify-center pointer-events-none">Mapa Simulado</p>
            </div>
          </div>

          <!-- Legend -->
          <div class="space-y-3 bg-white/5 p-4 rounded-2xl">
            <div class="flex justify-between items-center text-xs">
              <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></span>
                <span class="text-white/80">En ejecución</span>
              </div>
              <span class="font-mono text-white/50">8</span>
            </div>
            <div class="flex justify-between items-center text-xs">
              <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_8px_#3b82f6]"></span>
                <span class="text-white/80">En planificación</span>
              </div>
              <span class="font-mono text-white/50">3</span>
            </div>
            <div class="flex justify-between items-center text-xs">
              <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 shadow-[0_0_8px_#facc15]"></span>
                <span class="text-white/80">En espera</span>
              </div>
              <span class="font-mono text-white/50">2</span>
            </div>
            <div class="flex justify-between items-center text-xs">
              <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_8px_#f43f5e]"></span>
                <span class="text-white/80">Finalizados</span>
              </div>
              <span class="font-mono text-white/50">2</span>
            </div>
          </div>
        </div>
      </div>
      
    </div>

    <!-- BOTTOM ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6 pt-2">
      
      <!-- Proyectos Destacados (Spans 2 cols) -->
      <div class="lg:col-span-2 bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden">
        <div class="flex justify-between items-center mb-8">
          <div class="flex items-center gap-4">
            <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
              <UserGroupIcon class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-white">Proyectos Destacados</h2>
              <p class="text-white/40 text-xs mt-1">Estado actual de los principales proyectos</p>
            </div>
          </div>
          <button class="text-xs text-white/50 hover:text-white flex items-center gap-1 transition-colors">
            Ver todos <ChevronRightIcon class="w-3 h-3" />
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Proj 1 -->
          <div class="bg-white/5 rounded-2xl p-4 border border-white/5 hover:border-blue-500/30 transition-colors group cursor-pointer relative overflow-hidden">
            <div class="h-24 bg-white/5 rounded-xl mb-4 relative overflow-hidden bg-[url('https://images.unsplash.com/photo-1541888081622-1f4f5d608d4b?auto=format&fit=crop&q=80&w=600')] bg-cover bg-center">
              <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors"></div>
              <div class="absolute top-2 left-2 bg-emerald-500/90 text-white text-[9px] font-bold px-2 py-1 rounded-md flex items-center gap-1 backdrop-blur-sm shadow-lg">
                <CheckCircleIcon class="w-3 h-3" /> En ejecución
              </div>
            </div>
            <h3 class="font-bold text-sm text-white mb-2 leading-tight">Mejoramiento Parque Central Conacaste</h3>
            <div class="w-full bg-black/30 h-1.5 rounded-full mb-1">
              <div class="bg-emerald-500 h-1.5 rounded-full shadow-[0_0_10px_#10b981]" style="width: 100%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-white/50 font-bold mb-4">
              <span>Q 100,000.00</span>
              <span class="text-emerald-400">100%</span>
            </div>
            <div class="flex justify-between items-center text-[9px] text-white/40">
              <span>Fecha fin: 30/09/2026</span>
              <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors">
                <ArrowRightIcon class="w-3 h-3" />
              </div>
            </div>
          </div>

          <!-- Proj 2 -->
          <div class="bg-white/5 rounded-2xl p-4 border border-white/5 hover:border-yellow-500/30 transition-colors group cursor-pointer relative overflow-hidden">
            <div class="h-24 bg-white/5 rounded-xl mb-4 relative overflow-hidden bg-[url('https://images.unsplash.com/photo-1518242007632-159e875080c3?auto=format&fit=crop&q=80&w=600')] bg-cover bg-center">
              <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors"></div>
              <div class="absolute top-2 left-2 bg-yellow-500/90 text-white text-[9px] font-bold px-2 py-1 rounded-md flex items-center gap-1 backdrop-blur-sm shadow-lg">
                <PlayIcon class="w-3 h-3" /> En ejecución
              </div>
            </div>
            <h3 class="font-bold text-sm text-white mb-2 leading-tight">Asfalto Ruta Municipal Sector Sur</h3>
            <div class="w-full bg-black/30 h-1.5 rounded-full mb-1">
              <div class="bg-yellow-400 h-1.5 rounded-full shadow-[0_0_10px_#facc15]" style="width: 45%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-white/50 font-bold mb-4">
              <span>Q 200,000.00</span>
              <span class="text-yellow-400">45%</span>
            </div>
            <div class="flex justify-between items-center text-[9px] text-white/40">
              <span>Fecha fin: 15/12/2026</span>
              <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors">
                <ArrowRightIcon class="w-3 h-3" />
              </div>
            </div>
          </div>

          <!-- Proj 3 -->
          <div class="bg-white/5 rounded-2xl p-4 border border-white/5 hover:border-blue-500/30 transition-colors group cursor-pointer relative overflow-hidden">
            <div class="h-24 bg-white/5 rounded-xl mb-4 relative overflow-hidden bg-[url('https://images.unsplash.com/photo-1541888081622-1f4f5d608d4b?auto=format&fit=crop&q=80&w=600')] bg-cover bg-center grayscale opacity-70">
              <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors"></div>
              <div class="absolute top-2 left-2 bg-blue-500/90 text-white text-[9px] font-bold px-2 py-1 rounded-md flex items-center gap-1 backdrop-blur-sm shadow-lg">
                <ClipboardDocumentIcon class="w-3 h-3" /> En planificación
              </div>
            </div>
            <h3 class="font-bold text-sm text-white mb-2 leading-tight">Planta de Concreto Sanarate</h3>
            <div class="w-full bg-black/30 h-1.5 rounded-full mb-1">
              <div class="bg-blue-500 h-1.5 rounded-full shadow-[0_0_10px_#3b82f6]" style="width: 15%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-white/50 font-bold mb-4">
              <span>Q 1,500,000.00</span>
              <span class="text-blue-400">15%</span>
            </div>
            <div class="flex justify-between items-center text-[9px] text-white/40">
              <span>Inicio: 20/02/2027</span>
              <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors">
                <ArrowRightIcon class="w-3 h-3" />
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Alertas Inventario -->
      <div class="bg-[#0b1727] rounded-[32px] p-8 border border-white/5">
        <div class="flex justify-between items-center mb-8">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-rose-500/20 text-rose-400">
              <BellAlertIcon class="w-5 h-5" />
            </div>
            <h2 class="text-sm font-bold text-white">Alertas de Inventario</h2>
          </div>
          <span class="bg-rose-500 text-white px-2 py-1 rounded-lg text-[9px] font-bold tracking-wider shadow-[0_0_10px_rgba(244,63,94,0.5)]">3 críticas</span>
        </div>

        <div class="space-y-4">
          <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors cursor-pointer group">
            <div class="w-8 h-8 rounded-full bg-rose-500/20 flex items-center justify-center shrink-0">
              <span class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_8px_#f43f5e]"></span>
            </div>
            <div class="flex-1">
              <h4 class="text-xs font-bold text-white/90">Acero Estructural (Viga H)</h4>
              <p class="text-[10px] text-white/40 mt-1">Stock por debajo del umbral (15%).</p>
            </div>
            <ChevronRightIcon class="w-4 h-4 text-white/20 group-hover:text-white/60 mt-2" />
          </div>

          <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors cursor-pointer group">
            <div class="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center shrink-0">
              <span class="w-2 h-2 rounded-full bg-yellow-500 shadow-[0_0_8px_#eab308]"></span>
            </div>
            <div class="flex-1">
              <h4 class="text-xs font-bold text-white/90">Concreto Premezclado</h4>
              <p class="text-[10px] text-white/40 mt-1">Entrega con posible retraso.</p>
            </div>
            <ChevronRightIcon class="w-4 h-4 text-white/20 group-hover:text-white/60 mt-2" />
          </div>

          <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors cursor-pointer group">
            <div class="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center shrink-0">
              <span class="w-2 h-2 rounded-full bg-yellow-500 shadow-[0_0_8px_#eab308]"></span>
            </div>
            <div class="flex-1">
              <h4 class="text-xs font-bold text-white/90">Ladrillos de Pavimento</h4>
              <p class="text-[10px] text-white/40 mt-1">Stock bajo (25% restante).</p>
            </div>
            <ChevronRightIcon class="w-4 h-4 text-white/20 group-hover:text-white/60 mt-2" />
          </div>
        </div>
        
        <button class="w-full mt-6 py-2.5 rounded-xl border border-white/10 text-xs font-bold text-white/60 hover:text-white hover:bg-white/5 transition-colors">
          Ver inventario
        </button>
      </div>

      <!-- Actividad Reciente -->
      <div class="bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden xl:col-span-1 lg:col-span-3">
        <div class="flex justify-between items-center mb-8">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-blue-500/20 text-blue-400">
              <ClockIcon class="w-5 h-5" />
            </div>
            <h2 class="text-sm font-bold text-white">Actividad Reciente</h2>
          </div>
          <button class="text-xs text-white/50 hover:text-white flex items-center gap-1 transition-colors">
            Ver todas <ChevronRightIcon class="w-3 h-3" />
          </button>
        </div>

        <div class="relative pl-4 space-y-6">
          <div class="absolute left-[27px] top-4 bottom-4 w-px bg-white/10"></div>
          
          <div class="flex gap-4 relative z-10">
            <div class="w-10 text-[9px] text-white/40 font-mono pt-1 shrink-0 text-right">Hoy 07:15</div>
            <div class="w-6 h-6 rounded-full bg-[#112236] border-2 border-emerald-500 flex items-center justify-center shrink-0 shadow-[0_0_10px_rgba(16,185,129,0.3)]">
              <BanknotesIcon class="w-3 h-3 text-emerald-500" />
            </div>
            <div class="pt-0.5 pb-2">
              <p class="text-xs font-bold text-white/90">Se registró un nuevo pago</p>
              <p class="text-[10px] text-emerald-400/70 mt-1">Proyecto Parque Central</p>
            </div>
          </div>

          <div class="flex gap-4 relative z-10">
            <div class="w-10 text-[9px] text-white/40 font-mono pt-1 shrink-0 text-right">Ayer 16:30</div>
            <div class="w-6 h-6 rounded-full bg-[#112236] border-2 border-blue-500 flex items-center justify-center shrink-0">
              <ChartBarIcon class="w-3 h-3 text-blue-500" />
            </div>
            <div class="pt-0.5 pb-2">
              <p class="text-xs font-bold text-white/90">Se actualizó avance de proyecto</p>
              <p class="text-[10px] text-blue-400/70 mt-1">Asfalto Ruta Municipal</p>
            </div>
          </div>
          
          <div class="flex gap-4 relative z-10">
            <div class="w-10 text-[9px] text-white/40 font-mono pt-1 shrink-0 text-right">Ayer 11:20</div>
            <div class="w-6 h-6 rounded-full bg-[#112236] border-2 border-slate-500 flex items-center justify-center shrink-0">
              <DocumentTextIcon class="w-3 h-3 text-slate-400" />
            </div>
            <div class="pt-0.5 pb-2">
              <p class="text-xs font-bold text-white/90">Nuevo documento cargado</p>
              <p class="text-[10px] text-slate-400/70 mt-1">Contrato - Proveedor XYZ</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { 
  BanknotesIcon, DocumentTextIcon, TruckIcon, ShieldCheckIcon, 
  ArrowTrendingUpIcon, ChartBarIcon, FolderIcon, ChevronRightIcon,
  UserGroupIcon, CheckCircleIcon, ArrowRightIcon, PlayIcon, 
  ClipboardDocumentIcon, BellAlertIcon, ClockIcon, MagnifyingGlassIcon,
  BellIcon, CalendarIcon, SunIcon
} from '@heroicons/vue/24/outline';

const financialData = [
  { month: "Ene", income: 40, expense: 30 },
  { month: "Feb", income: 55, expense: 35 },
  { month: "Mar", income: 45, expense: 40 },
  { month: "Abr", income: 70, expense: 50 },
  { month: "May", income: 85, expense: 60 },
  { month: "Jun", income: 65, expense: 45 },
];
</script>

<style scoped>
/* Optional custom scrollbar hides for specific divs */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}
</style>
