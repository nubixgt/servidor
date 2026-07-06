<template>
    <div class="animate-fade-in w-full max-w-7xl mx-auto pb-12">
        
        <!-- Welcome Header -->
        <div class="mb-8 p-6 rounded-3xl bg-white dark:bg-[#1E293B] border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-brand font-black text-brand-dark dark:text-white tracking-wide">
                    Dashboard Ministerial
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Sistema de control y monitoreo</p>
            </div>
            <div class="flex items-center gap-4 mt-4 md:mt-0">
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 px-4 py-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <UserCircleIcon class="w-6 h-6 text-primary" />
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Ministra</span>
                </div>
                <button @click="handleLogout" class="bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 p-2.5 rounded-xl hover:bg-red-100 transition-colors" title="Cerrar Sesión">
                    <ArrowRightOnRectangleIcon class="w-5 h-5" />
                </button>
            </div>
        </div>


        <!-- Section 1: Estadisticas Generales -->
        <div class="mb-10">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-2">
                <ChartBarSquareIcon class="w-6 h-6 text-primary" />
                Estadísticas Generales
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Estado y métricas de todos los módulos activos</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Card 1: Actividades Despacho -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">Actividades Despacho</h3>
                            <p class="text-xs text-gray-500">Ministerial</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                            <BriefcaseIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-bold mb-6 self-start">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto">
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.despacho.total }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Total Act.</span>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/10 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-red-600 dark:text-red-400 block">{{ stats.despacho.criticas }}</span>
                            <span class="text-[10px] text-red-500 uppercase">Críticas</span>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/10 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-blue-600 dark:text-blue-400 block">{{ stats.despacho.en_progreso }}</span>
                            <span class="text-[10px] text-blue-500 uppercase">En Progreso</span>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/10 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 block">{{ stats.despacho.completadas }}</span>
                            <span class="text-[10px] text-emerald-500 uppercase">Completadas</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Votaciones -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">Votaciones</h3>
                            <p class="text-xs text-gray-500">Congreso</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                            <BuildingLibraryIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-bold mb-6 self-start">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto">
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.votaciones.eventos }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Eventos</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.votaciones.congresistas }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Congresistas</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.votaciones.bloques }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Bloques</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.votaciones.votos_reg) }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Votos Reg.</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: VISAR -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl -mr-10 -mt-10"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">VISAR</h3>
                            <p class="text-xs text-gray-500">Viceministerio</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <GlobeAmericasIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-bold mb-6 self-start relative z-10">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto relative z-10">
                        <div class="bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-2xl">
                            <span class="flex items-center gap-1 text-[10px] text-blue-600 mb-1"><BriefcaseIcon class="w-3 h-3" /> Exportaciones</span>
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visar.exportaciones) }}</span>
                            <span class="text-[9px] text-emerald-600 font-bold block mt-1">${{ stats.visar.valor_fob }} FOB</span>
                        </div>
                        <div class="bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-2xl">
                            <span class="flex items-center gap-1 text-[10px] text-blue-600 mb-1"><ShoppingCartIcon class="w-3 h-3" /> Importaciones</span>
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visar.importaciones) }}</span>
                            <span class="text-[9px] text-emerald-600 font-bold block mt-1">${{ stats.visar.valor_cif }} CIF</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl">
                            <span class="flex items-center gap-1 text-[10px] text-gray-500 mb-1"><TruckIcon class="w-3 h-3" /> Lic. Transporte</span>
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visar.lic_transporte) }}</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl">
                             <span class="flex items-center gap-1 text-[10px] text-gray-500 mb-1"><ShieldCheckIcon class="w-3 h-3" /> Lic. Fitosanitar.</span>
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visar.lic_fitosanitar) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: VISAN -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-shadow">
                     <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">VISAN</h3>
                            <p class="text-xs text-gray-500">Viceministerio</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center">
                            <HandRaisedIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-bold mb-6 self-start">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto">
                        <div class="bg-orange-50/50 dark:bg-orange-900/10 p-3 rounded-2xl text-center">
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visan.asistencia_alimentaria || 0) }}</span>
                            <span class="text-[10px] text-orange-600 uppercase">Asistencia Alimentaria</span>
                        </div>
                        <div class="bg-green-50/50 dark:bg-green-900/10 p-3 rounded-2xl text-center">
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visan.alimentos_por_acciones || 0) }}</span>
                            <span class="text-[10px] text-green-600 uppercase">Alimentos por Acciones</span>
                        </div>
                        <div class="bg-blue-50/50 dark:bg-blue-900/10 p-3 rounded-2xl text-center">
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visan.total_raciones_aa || 0) }}</span>
                            <span class="text-[10px] text-blue-600 uppercase">Total Raciones AA</span>
                        </div>
                        <div class="bg-purple-50/50 dark:bg-purple-900/10 p-3 rounded-2xl text-center">
                             <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.visan.reserva_estrategica || 0) }}</span>
                            <span class="text-[10px] text-purple-600 uppercase">Reserva Estratégica G.B.</span>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Reg. Climatologico -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-shadow">
                     <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">Registro Climatológico</h3>
                            <p class="text-xs text-gray-500">Monitoreo</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center">
                            <CloudIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-bold mb-6 self-start">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto">
                        <div class="bg-cyan-50/50 dark:bg-cyan-900/10 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.registro_climatologico.alertas_total }}</span>
                            <span class="text-[10px] text-cyan-600 uppercase">Alertas Total</span>
                        </div>
                        <div class="bg-red-50/50 dark:bg-red-900/10 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-red-600 dark:text-red-400 block">{{ stats.registro_climatologico.activas }}</span>
                            <span class="text-[10px] text-red-500 uppercase">Activas</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                            <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.registro_climatologico.usuarios }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Usuarios</span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-2xl text-center">
                             <span class="text-xl font-black text-gray-800 dark:text-white block">{{ stats.registro_climatologico.regiones_monit }}</span>
                            <span class="text-[10px] text-gray-500 uppercase">Regiones Monit.</span>
                        </div>
                    </div>
                </div>

                <!-- Card 6: VIDER -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-shadow">
                     <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100">VIDER</h3>
                            <p class="text-xs text-gray-500">Desarrollo Rural</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <ChartBarSquareIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2.5 py-1 rounded-full text-xs font-bold mb-6 self-start">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto">
                        <div class="bg-indigo-50/50 dark:bg-indigo-900/10 p-3 rounded-2xl text-center">
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.vider.beneficiarios) }}</span>
                            <span class="text-[10px] text-indigo-600 uppercase">Beneficiarios</span>
                        </div>
                        <div class="bg-purple-50/50 dark:bg-purple-900/10 p-3 rounded-2xl text-center">
                            <span class="text-lg font-black text-gray-800 dark:text-white block">{{ formatNumber(stats.vider.ejecutado) }}</span>
                            <span class="text-[10px] text-purple-600 uppercase">Ejecutado (Físico)</span>
                        </div>
                        <div class="bg-emerald-50/50 dark:bg-emerald-900/10 p-3 rounded-2xl text-center">
                            <span class="text-lg font-black text-gray-800 dark:text-white block">Q{{ formatNumber(stats.vider.financiero) }}</span>
                            <span class="text-[10px] text-emerald-600 uppercase">Ejecución Finan.</span>
                        </div>
                        <div class="bg-cyan-50/50 dark:bg-cyan-900/10 p-3 rounded-2xl text-center">
                             <span class="text-lg font-black text-gray-800 dark:text-white block">{{ stats.vider.departamentos }}</span>
                            <span class="text-[10px] text-cyan-600 uppercase">Deptos Cubiertos</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Section 2: Areas Transversales -->
        <div>
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-2">
                <Squares2X2Icon class="w-6 h-6 text-primary" />
                Áreas Transversales
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Sistemas integrados para gestión institucional</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4">
                
                <!-- System Link Factory -->
                <RouterLink to="/admin/actividades-despacho" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-500 text-white flex items-center justify-center shadow-sm">
                            <BriefcaseIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Actividades Despacho</h4>
                        <p class="text-xs text-gray-500 mb-3">Ministerial</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/votaciones" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center shadow-sm">
                            <BuildingLibraryIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Votaciones</h4>
                        <p class="text-xs text-gray-500 mb-3">Congreso</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/sanidad" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-sm">
                            <ShieldCheckIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Sanidad Agropecuaria</h4>
                        <p class="text-xs text-gray-500 mb-3">VISAR (Fito/Zoo)</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>
                
                <RouterLink to="/admin/visar/dashboard" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-400 text-white flex items-center justify-center shadow-sm">
                            <GlobeAmericasIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">VISAR</h4>
                        <p class="text-xs text-gray-500 mb-3">Viceministerio</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/visan/dashboard" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-sm">
                            <HandRaisedIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">VISAN</h4>
                        <p class="text-xs text-gray-500 mb-3">Viceministerio</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/clima/dashboard" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500 text-white flex items-center justify-center shadow-sm">
                            <CloudIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Registro Climatológico</h4>
                        <p class="text-xs text-gray-500 mb-3">Monitoreo</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>

                <a href="#" class="bg-white/70 dark:bg-gray-800/70 p-5 rounded-3xl border border-white/80 dark:border-gray-700 backdrop-blur-md hover:bg-white dark:hover:bg-gray-800 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] opacity-70">
                    <div class="absolute inset-0 bg-gradient-to-br from-pink-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-pink-500/60 text-white flex items-center justify-center shadow-sm">
                            <SparklesIcon class="w-5 h-5" />
                        </div>
                        <WrenchScrewdriverIcon class="w-5 h-5 text-gray-300" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">VIPETEN</h4>
                        <p class="text-xs text-gray-500 mb-3">Viceministerio</p>
                        <div class="inline-flex items-center gap-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> En desarrollo
                        </div>
                    </div>
                </a>

                <RouterLink to="/admin/vider/dashboard" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] block">
                    <div class="absolute inset-0 bg-gradient-to-br from-teal-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-teal-500 text-white flex items-center justify-center shadow-sm">
                            <ShareIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">VIDER</h4>
                        <p class="text-xs text-gray-500 mb-3">Viceministerio</p>
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div> Óptimo
                        </div>
                    </div>
                </RouterLink>

                <a href="#" class="bg-white/70 dark:bg-gray-800/70 p-5 rounded-3xl border border-white/80 dark:border-gray-700 backdrop-blur-md hover:bg-white dark:hover:bg-gray-800 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[160px] opacity-70">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/60 text-white flex items-center justify-center shadow-sm">
                            <ShieldExclamationIcon class="w-5 h-5" />
                        </div>
                        <WrenchScrewdriverIcon class="w-5 h-5 text-gray-300" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">DICORER</h4>
                        <p class="text-xs text-gray-500 mb-3">Dirección</p>
                        <div class="inline-flex items-center gap-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 px-2 py-1 rounded-md text-[10px] font-bold">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div> En desarrollo
                        </div>
                    </div>
                </a>

            </div>
        </div>

        <!-- Section 3: Gestión Agrícola y Territorial -->
        <div class="mt-12">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-2">
                <UsersIcon class="w-6 h-6 text-primary" />
                Gestión Agrícola y Territorial
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Padrón de productores, asistencia técnica y seguridad alimentaria</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                
                <RouterLink to="/admin/productores" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[140px] block">
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
                            <UsersIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Registro Productores</h4>
                        <p class="text-xs text-gray-500">Padrón Único</p>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/extension" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[140px] block">
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-sm">
                            <MapIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Extensión Rural</h4>
                        <p class="text-xs text-gray-500">Asistencia Técnica</p>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/seguridad-alimentaria" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[140px] block">
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center shadow-sm">
                            <HeartIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-300 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Seguridad Alimentaria</h4>
                        <p class="text-xs text-gray-500">Monitoreo Nutricional</p>
                    </div>
                </RouterLink>

                <RouterLink to="/admin/presupuesto" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden flex flex-col justify-between min-h-[140px] block">
                    <div class="flex justify-between items-start relative z-10 w-full mb-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-700 text-white flex items-center justify-center shadow-sm">
                            <ChartBarIcon class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon class="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors" />
                    </div>
                    <div class="relative z-10">
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary transition-colors">Ejecución Presupuestaria</h4>
                        <p class="text-xs text-gray-500">Finanzas</p>
                    </div>
                </RouterLink>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DashboardService from '@/services/dashboard/DashboardService';
import { 
    UserCircleIcon,
    ArrowRightOnRectangleIcon,
    ChartBarSquareIcon,
    BriefcaseIcon,
    BuildingLibraryIcon,
    GlobeAmericasIcon,
    HandRaisedIcon,
    CloudIcon,
    ShoppingCartIcon,
    TruckIcon,
    ShieldCheckIcon,
    Squares2X2Icon,
    ArrowTopRightOnSquareIcon,
    WrenchScrewdriverIcon,
    SparklesIcon,
    ShareIcon,
    ShieldExclamationIcon,
    UsersIcon,
    MapIcon,
    HeartIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const stats = ref({
    despacho: { total: 0, criticas: 0, en_progreso: 0, completadas: 0 },
    votaciones: { eventos: 0, congresistas: 0, bloques: 0, votos_reg: 0 },
    visar: { exportaciones: 0, importaciones: 0, lic_transporte: 0, lic_fitosanitar: 0 },
    visan: { asistencia_alimentaria: 0, alimentos_por_acciones: 0, total_raciones_aa: 0, reserva_estrategica: 0 },
    vider: { beneficiarios: 0, ejecutado: 0, departamentos: 0, financiero: 0 },
    registro_climatologico: { alertas_total: 0, activas: 0, usuarios: 0, regiones_monit: 0 }
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US').format(num);
};

onMounted(async () => {
    try {
        const response = await DashboardService.getStats();
        if (response.status === 'success') {
            stats.value = response.data;
        }
    } catch (error) {
        console.error('Error fetching dashboard stats:', error);
    }
});

const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('isAuthenticated');
    router.push('/login');
};
</script>
