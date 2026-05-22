<template>
    <div class="space-y-10">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 animate-in fade-in duration-300">
            <div>
                <h2 class="text-2xl font-extrabold text-on-surface tracking-tight font-headline">Dashboard Administrador</h2>
                <p class="text-on-surface-variant/60 text-sm font-medium mt-1">Vista global de la gestión legislativa y territorial</p>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <button @click="exportReport" class="flex-1 md:flex-none px-5 py-2.5 bg-surface text-on-surface-variant border border-outline-variant/50 rounded-lg font-bold text-[11px] flex items-center justify-center gap-2 hover:bg-background transition-all shadow-sm uppercase tracking-wider">
                    <span class="material-symbols-outlined text-base">download</span> Exportar Reporte
                </button>
                <button @click="openCreateTaskModal" class="flex-1 md:flex-none px-5 py-2.5 bg-primary text-white rounded-lg font-bold text-[11px] flex items-center justify-center gap-2 hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 uppercase tracking-wider">
                    <span class="material-symbols-outlined text-base">add</span> Nueva Tarea
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <!-- Tareas Pendientes -->
            <div class="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] hover:-translate-y-1 transition-all duration-300">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Tareas Pendientes</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-on-surface font-mono">{{ kpis.tareasPendientes }}</span>
                    <span class="text-[10px] text-primary font-bold">Activas</span>
                </div>
                <div class="mt-6 w-full bg-background h-1 rounded-full overflow-hidden">
                    <div class="bg-primary h-full rounded-full transition-all duration-500" :style="{ width: calculatePercent(kpis.tareasPendientes, kpis.tareasPendientes + kpis.tareasVencidas) + '%' }"></div>
                </div>
            </div>

            <!-- Tareas Vencidas -->
            <div class="bg-error-container/20 p-6 rounded-xl border border-error/10 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] hover:-translate-y-1 transition-all duration-300">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-error/60 mb-4">Tareas Vencidas</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-error font-mono">{{ kpis.tareasVencidas }}</span>
                    <span v-if="kpis.tareasVencidas > 0" class="text-[10px] text-error font-semibold animate-pulse">Crítico</span>
                    <span v-else class="text-[10px] text-on-surface-variant/40 font-medium">Al día</span>
                </div>
                <div class="mt-6 w-full bg-error/10 h-1 rounded-full overflow-hidden">
                    <div class="bg-error h-full rounded-full transition-all duration-500" :style="{ width: (kpis.tareasVencidas > 0 ? 100 : 0) + '%' }"></div>
                </div>
            </div>

            <!-- Próximos 7 Días -->
            <div class="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] hover:-translate-y-1 transition-all duration-300">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Próximos 7 Días</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-on-surface font-mono">{{ kpis.proximos7Dias }}</span>
                    <span class="text-[10px] text-on-surface-variant/40 font-medium">Eventos</span>
                </div>
                <div class="mt-6 flex -space-x-1.5">
                    <div class="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center text-[8px] font-bold border-2 border-surface ring-1 ring-outline-variant/10 text-on-primary-container">EV</div>
                    <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[8px] font-bold border-2 border-surface ring-1 ring-outline-variant/10 text-on-secondary-container">CI</div>
                </div>
            </div>

            <!-- Fiscalizaciones -->
            <div class="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] hover:-translate-y-1 transition-all duration-300">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Fiscalizaciones</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-on-surface font-mono">{{ kpis.fiscalizacionesActivas }}</span>
                    <span class="text-[10px] text-primary/70 font-bold">Personal</span>
                </div>
                <div class="mt-6 text-[10px] text-on-surface-variant/50 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-primary">check_circle</span> Personal Activo
                </div>
            </div>

            <!-- Atrasos de Prensa -->
            <div class="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] border-b-primary border-b-2 hover:-translate-y-1 transition-all duration-300">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Atrasos de Prensa</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-primary font-mono">{{ kpis.atrasosPrensa }}</span>
                    <span class="text-[10px] text-on-surface-variant/40 font-medium">Publicaciones</span>
                </div>
                <div class="mt-6 flex gap-1">
                    <span class="px-2 py-0.5 bg-background border border-outline-variant/30 text-on-surface-variant/60 text-[8px] font-bold rounded uppercase">Redes</span>
                </div>
            </div>
        </div>

        <!-- Middle Section -->
        <div class="grid grid-cols-12 gap-10">
            <!-- Left Column -->
            <div class="col-span-12 lg:col-span-8 space-y-10">
                <!-- Activity Trend -->
                <div class="bg-surface p-10 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
                    <div class="flex justify-between items-center mb-10">
                        <h3 class="text-lg font-bold text-on-surface tracking-tight font-headline">Tendencia de Actividad Semanal</h3>
                        <div class="flex gap-4">
                            <span class="flex items-center gap-2 text-[10px] font-bold text-on-surface-variant/70"><span class="w-2 h-2 rounded-full bg-primary"></span> Legislativo</span>
                            <span class="flex items-center gap-2 text-[10px] font-bold text-on-surface-variant/40"><span class="w-2 h-2 rounded-full bg-outline-variant"></span> Territorial</span>
                        </div>
                    </div>
                    <div class="h-48 flex items-end justify-between gap-6 px-4">
                        <div v-for="(day, index) in ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom']" :key="index" class="flex-1 flex flex-col items-center gap-3">
                            <div class="w-full h-32 flex items-end justify-center gap-1.5">
                                <!-- Legislativo bar -->
                                <div class="w-2.5 bg-primary rounded-t transition-all duration-700" :style="{ height: getBarHeight(tendencia.legislativo[index], maxTrendValue) }" :title="'Legislativo: ' + tendencia.legislativo[index]"></div>
                                <!-- Territorial bar -->
                                <div class="w-2.5 bg-outline-variant rounded-t transition-all duration-700" :style="{ height: getBarHeight(tendencia.territorial[index], maxTrendValue) }" :title="'Territorial: ' + tendencia.territorial[index]"></div>
                            </div>
                            <span class="text-[10px] font-bold text-on-surface-variant/40 uppercase font-mono">{{ day }}</span>
                        </div>
                    </div>
                </div>

                <!-- Performance & Compromisos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <!-- Member performance -->
                    <div class="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
                        <h3 class="text-sm font-bold text-on-surface mb-8 uppercase tracking-[0.1em] font-headline">Tareas por Miembro</h3>
                        <div class="space-y-6">
                            <div v-for="m in miembros" :key="m.id" class="space-y-2">
                                <div class="flex justify-between text-[10px] font-bold uppercase tracking-tight text-on-surface-variant/60">
                                    <span>{{ m.nombre_completo }}</span>
                                    <span class="text-primary font-mono">{{ m.efectividad }}% EFECTIVIDAD</span>
                                </div>
                                <div class="h-1.5 w-full bg-background rounded-full overflow-hidden">
                                    <div class="bg-primary h-full rounded-full transition-all duration-500" :style="{ width: m.efectividad + '%' }"></div>
                                </div>
                            </div>
                            <div v-if="miembros.length === 0" class="text-center py-4 text-xs text-on-surface-variant/50">
                                No hay tareas asignadas a miembros activos.
                            </div>
                        </div>
                    </div>

                    <!-- Compromisos breakdown -->
                    <div class="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
                        <h3 class="text-sm font-bold text-on-surface mb-8 uppercase tracking-[0.1em] font-headline">Compromisos</h3>
                        <div class="flex items-center gap-8">
                            <div class="relative w-28 h-28">
                                <svg class="w-full h-full rotate-[-90deg]" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#F1F3F4" stroke-width="2.5"></circle>
                                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#005D6B" stroke-dasharray="70 100" stroke-linecap="round" stroke-width="2.5" :style="{ strokeDasharray: getStrokeDashArray(compromisos['En Proceso'], compromisos.Total) }"></circle>
                                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#859ba6" stroke-linecap="round" stroke-width="2.5" :style="{ strokeDasharray: getStrokeDashArray(compromisos.Completados, compromisos.Total), strokeDashoffset: getStrokeDashOffset(compromisos['En Proceso'], compromisos.Total) }"></circle>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center flex-col leading-none">
                                    <span class="text-2xl font-black text-on-surface font-headline font-mono">{{ compromisos.Total }}</span>
                                    <span class="text-[7px] font-bold text-on-surface-variant/50 uppercase tracking-widest mt-1">Total</span>
                                </div>
                            </div>
                            <div class="space-y-3 flex-1">
                                <div class="flex items-center justify-between gap-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                                        <span class="text-[10px] font-bold text-on-surface-variant/60">En Proceso</span>
                                    </div>
                                    <span class="text-xs font-bold font-mono text-on-surface">{{ compromisos['En Proceso'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#859ba6]"></span>
                                        <span class="text-[10px] font-bold text-on-surface-variant/60">Completados</span>
                                    </div>
                                    <span class="text-xs font-bold font-mono text-on-surface">{{ compromisos.Completados }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-background border border-outline-variant/30"></span>
                                        <span class="text-[10px] font-bold text-on-surface-variant/60">Pendientes</span>
                                    </div>
                                    <span class="text-xs font-bold font-mono text-on-surface">{{ compromisos.Pendientes }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-span-12 lg:col-span-4 space-y-10">
                <!-- Alerts Panel -->
                <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] overflow-hidden">
                    <div class="bg-error-container/10 p-5 flex items-center justify-between border-b border-error/5">
                        <h3 class="text-xs font-bold text-error/80 flex items-center gap-2 uppercase tracking-widest font-headline">
                            <span class="material-symbols-outlined text-base animate-bounce">warning</span> Alertas Críticas
                        </h3>
                        <span class="text-[9px] font-black bg-error text-white px-2 py-0.5 rounded-full font-mono">{{ alertas.length }}</span>
                    </div>
                    <div class="p-6 divide-y divide-background">
                        <div v-for="a in alertas" :key="a.id" class="py-3 flex gap-4 first:pt-0 last:pb-0">
                            <div class="w-1.5 h-auto bg-error/20 rounded-full"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-on-surface leading-tight truncate">{{ a.titulo }}</p>
                                <p class="text-[9px] text-on-surface-variant/50 font-medium mt-1">Límite: {{ a.fecha_limite }} • {{ a.asignado_nombre || 'Sin asignar' }}</p>
                            </div>
                        </div>
                        <div v-if="alertas.length === 0" class="text-center py-6 text-xs text-on-surface-variant/50">
                            No hay alertas ni atrasos críticos detectados.
                        </div>
                    </div>
                </div>

                <!-- Agenda Mensual -->
                <div class="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-[10px] font-bold text-on-surface-variant/70 uppercase tracking-[0.2em] font-headline">Agenda Mensual</h3>
                        <span class="text-[10px] font-bold text-primary">{{ currentMonthName }}</span>
                    </div>
                    <div class="space-y-4">
                        <div v-for="event in agenda" :key="event.id" class="flex items-center gap-4 group cursor-pointer">
                            <div class="w-9 h-9 bg-background rounded-lg flex items-center justify-center flex-col leading-none border border-outline-variant/20 transition-colors group-hover:border-primary/30">
                                <span class="text-xs font-bold text-on-surface font-mono">{{ getDayFromDate(event.date) }}</span>
                                <span class="text-[6px] font-bold uppercase text-on-surface-variant/40">{{ getMonthLabel(event.date) }}</span>
                            </div>
                            <div class="text-[11px] flex-1 min-w-0">
                                <p class="font-bold text-on-surface group-hover:text-primary transition-colors truncate">{{ event.title }}</p>
                                <p class="text-on-surface-variant/50 text-[10px] truncate">{{ event.category }} • {{ event.description || 'Sin descripción' }}</p>
                            </div>
                        </div>
                        <div v-if="agenda.length === 0" class="text-center py-6 text-xs text-on-surface-variant/50">
                            No hay eventos ni citaciones agendadas para este mes.
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
                    <h3 class="text-[10px] font-bold text-on-surface-variant/70 uppercase tracking-[0.2em] mb-8 font-headline">Feed Reciente</h3>
                    <div class="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[1px] before:bg-background">
                        <div v-for="(f, i) in feed" :key="i" class="flex gap-4 relative z-10">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center shadow-sm" :class="f.tipo === 'check' ? 'bg-primary-container text-on-primary-container' : 'bg-background border border-outline-variant/20 text-on-surface-variant'">
                                <span class="material-symbols-outlined text-[10px]">{{ f.tipo }}</span>
                            </div>
                            <div class="text-[10px] flex-1">
                                <p class="font-bold text-on-surface">{{ f.titulo }}</p>
                                <p class="text-on-surface-variant/50">{{ f.descripcion }}</p>
                                <p class="text-[8px] text-on-surface-variant/30 mt-1 uppercase font-bold tracking-tighter font-mono">{{ formatTimeAgo(f.fecha) }}</p>
                            </div>
                        </div>
                        <div v-if="feed.length === 0" class="text-center py-4 text-xs text-on-surface-variant/50">
                            No hay actividad reciente registrada en el portal.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Gestión de Tareas Organizadas (CRUD Completo) -->
        <div class="bg-surface-container-lowest p-8 rounded-2xl border border-outline-variant/20 shadow-sm animate-in fade-in duration-500">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-on-surface font-headline flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">assignment_turned_in</span>
                        Tablero de Tareas Organizativas
                    </h3>
                    <p class="text-xs text-on-surface-variant/60">Asigna, monitorea y gestiona las tareas organizativas de todo el personal</p>
                </div>
                <div class="flex flex-wrap gap-3 w-full md:w-auto">
                    <div class="relative flex-1 md:flex-none">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-xs text-on-surface-variant/50">search</span>
                        <input v-model="searchQuery" type="text" placeholder="Buscar tarea..." class="pl-8 pr-4 py-1.5 bg-background text-xs border border-outline-variant/40 rounded-lg outline-none w-full md:w-48 text-on-surface focus:ring-1 focus:ring-primary/30" />
                    </div>
                    <select v-model="filterPriority" class="px-3 py-1.5 bg-background text-xs border border-outline-variant/40 rounded-lg outline-none text-on-surface">
                        <option value="Todos">Todas Prioridades</option>
                        <option value="Baja">Prioridad Baja</option>
                        <option value="Media">Prioridad Media</option>
                        <option value="Alta">Prioridad Alta</option>
                        <option value="Crítica">Prioridad Crítica</option>
                    </select>
                    <select v-model="filterStatus" class="px-3 py-1.5 bg-background text-xs border border-outline-variant/40 rounded-lg outline-none text-on-surface">
                        <option value="Todos">Todos Estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Proceso">En Proceso</option>
                        <option value="Completada">Completada</option>
                    </select>
                </div>
            </div>

            <!-- Task List Table -->
            <div class="w-full overflow-x-auto rounded-xl border border-outline-variant/10">
                <table class="w-full text-left border-collapse min-w-[750px]">
                    <thead>
                        <tr class="bg-surface text-on-surface-variant text-[10px] uppercase tracking-widest font-bold border-b border-outline-variant/10">
                            <th class="px-6 py-4">Tarea</th>
                            <th class="px-6 py-4">Asignado A</th>
                            <th class="px-6 py-4">Prioridad</th>
                            <th class="px-6 py-4">Fecha Límite</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in paginatedTareas" :key="t.id" class="group hover:bg-background/40 transition-colors border-b border-outline-variant/10 last:border-0" :class="{ 'opacity-60': t.estado === 'Completada' }">
                            <td class="px-6 py-4">
                                <p class="text-xs font-bold text-on-surface" :class="{ 'line-through': t.estado === 'Completada' }">{{ t.titulo }}</p>
                                <p class="text-[10px] text-on-surface-variant/60 font-medium line-clamp-1 mt-0.5">{{ t.descripcion || 'Sin descripción' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container text-[8px] font-black uppercase shadow-sm">
                                        {{ getInitials(t.asignado_nombre) }}
                                    </div>
                                    <span class="text-xs text-on-surface-variant font-medium">{{ t.asignado_nombre || 'Sin asignar' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-full uppercase tracking-wider shadow-sm border" :class="getPriorityClass(t.prioridad)">
                                    {{ t.prioridad }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-mono font-medium" :class="isOverdue(t.fecha_limite, t.estado) ? 'text-error font-bold' : 'text-on-surface-variant'">
                                    {{ t.fecha_limite }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <select :value="t.estado" @change="updateTaskStatus(t.id, $event.target.value)" class="text-[10px] font-bold rounded-lg border-none px-2 py-1 bg-surface-container text-on-surface cursor-pointer focus:ring-1 focus:ring-primary/20">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Completada">Completada</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1.5 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditTaskModal(t)" class="p-1.5 text-on-surface-variant hover:text-primary transition-colors rounded hover:bg-background" title="Editar tarea">
                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                    </button>
                                    <button @click="confirmDeleteTask(t.id)" class="p-1.5 text-on-surface-variant hover:text-error transition-colors rounded hover:bg-error-container/20" title="Eliminar tarea">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginatedTareas.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-xs text-on-surface-variant/50">
                                No se encontraron tareas organizativas en el tablero con los filtros aplicados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Table -->
            <div class="flex items-center justify-between mt-4 px-2">
                <span class="text-[11px] text-on-surface-variant/60 font-medium">
                    Mostrando {{ filteredTareas.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1 }} a {{ Math.min(currentPage * itemsPerPage, filteredTareas.length) }} de {{ filteredTareas.length }} tareas
                </span>
                <div class="flex items-center gap-2">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="px-2.5 py-1 text-[10px] font-bold bg-background hover:bg-surface text-on-surface rounded border border-outline-variant/30 disabled:opacity-30 transition-all">Anterior</button>
                    <span class="text-xs font-bold text-on-surface font-mono">{{ currentPage }} / {{ totalPages || 1 }}</span>
                    <button @click="currentPage++" :disabled="currentPage >= totalPages" class="px-2.5 py-1 text-[10px] font-bold bg-background hover:bg-surface text-on-surface rounded border border-outline-variant/30 disabled:opacity-30 transition-all">Siguiente</button>
                </div>
            </div>
        </div>

        <!-- Task Creation/Edition Modal -->
        <div v-if="showTaskModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
                <div class="p-5 border-b border-outline-variant/10 flex items-center justify-between">
                    <h3 class="text-md font-bold text-on-surface font-headline">{{ editMode ? 'Editar Tarea Organizativa' : 'Nueva Tarea Organizativa' }}</h3>
                    <button @click="closeTaskModal" class="w-7 h-7 flex items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto flex-1">
                    <form id="taskFormSubmit" @submit.prevent="submitTask" class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-on-surface-variant mb-1 block">Título de la Tarea</label>
                            <input v-model="taskForm.titulo" required type="text" placeholder="Ej. Redactar minuta de comisión" class="w-full px-3 py-2 bg-background border border-outline-variant/40 rounded-lg text-xs text-on-surface placeholder:text-on-surface-variant/40 focus:ring-1 focus:ring-primary/20 outline-none" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-on-surface-variant mb-1 block">Descripción (Opcional)</label>
                            <textarea v-model="taskForm.descripcion" rows="3" placeholder="Detalles de la tarea..." class="w-full px-3 py-2 bg-background border border-outline-variant/40 rounded-lg text-xs text-on-surface placeholder:text-on-surface-variant/40 focus:ring-1 focus:ring-primary/20 outline-none resize-none"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-on-surface-variant mb-1 block">Prioridad</label>
                                <select v-model="taskForm.prioridad" class="w-full px-3 py-2 bg-background border border-outline-variant/40 rounded-lg text-xs text-on-surface focus:ring-1 focus:ring-primary/20 outline-none font-medium">
                                    <option value="Baja">Baja</option>
                                    <option value="Media">Media</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Crítica">Crítica</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-on-surface-variant mb-1 block">Estado</label>
                                <select v-model="taskForm.estado" class="w-full px-3 py-2 bg-background border border-outline-variant/40 rounded-lg text-xs text-on-surface focus:ring-1 focus:ring-primary/20 outline-none font-medium">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Completada">Completada</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-on-surface-variant mb-1 block">Fecha Límite</label>
                                <input v-model="taskForm.fecha_limite" required type="date" class="w-full px-3 py-2 bg-background border border-outline-variant/40 rounded-lg text-xs text-on-surface focus:ring-1 focus:ring-primary/20 outline-none font-mono" />
                            </div>
                            <div>
                                <label class="text-xs font-bold text-on-surface-variant mb-1 block">Asignado A</label>
                                <select v-model="taskForm.asignado_a" class="w-full px-3 py-2 bg-background border border-outline-variant/40 rounded-lg text-xs text-on-surface focus:ring-1 focus:ring-primary/20 outline-none text-xs font-medium">
                                    <option value="">Sin asignar</option>
                                    <option v-for="u in usersList" :key="u.id" :value="u.id">{{ u.nombre_completo }}</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="p-5 border-t border-outline-variant/10 bg-surface-container flex justify-end gap-3">
                    <button @click="closeTaskModal" type="button" class="px-4 py-2 bg-surface-container-high text-on-surface text-xs font-bold rounded-lg transition-colors hover:bg-surface-container-highest">Cancelar</button>
                    <button type="submit" form="taskFormSubmit" :disabled="loading" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg flex items-center gap-1.5 shadow-md transition-all hover:bg-primary/95 disabled:opacity-50">
                        <span v-if="loading" class="material-symbols-outlined animate-spin text-[14px]">progress_activity</span>
                        <span>{{ editMode ? 'Actualizar' : 'Guardar' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Swal from 'sweetalert2';
import api, { getApiBaseUrl } from '../../../services/api.js';

// KPIs and Lists
const kpis = ref({
    tareasPendientes: 0,
    tareasVencidas: 0,
    proximos7Dias: 0,
    fiscalizacionesActivas: 0,
    atrasosPrensa: 0
});
const tendencia = ref({
    legislativo: [0, 0, 0, 0, 0, 0, 0],
    territorial: [0, 0, 0, 0, 0, 0, 0]
});
const miembros = ref([]);
const compromisos = ref({
    'En Proceso': 0,
    'Completados': 0,
    'Pendientes': 0,
    'Total': 0
});
const alertas = ref([]);
const agenda = ref([]);
const feed = ref([]);

// Tasks List and Management
const listadoTareas = ref([]);
const usersList = ref([]);
const loading = ref(false);
const showTaskModal = ref(false);
const editMode = ref(false);
const currentTaskId = ref(null);

const taskForm = ref({
    titulo: '',
    descripcion: '',
    asignado_a: '',
    fecha_limite: '',
    prioridad: 'Media',
    estado: 'Pendiente'
});

// Filters and Pagination
const searchQuery = ref('');
const filterPriority = ref('Todos');
const filterStatus = ref('Todos');
const currentPage = ref(1);
const itemsPerPage = 8;

// Time and date formats
const currentMonthName = computed(() => {
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return months[new Date().getMonth()] + ' ' + new Date().getFullYear();
});

const maxTrendValue = computed(() => {
    const legislativoMax = Math.max(...tendencia.value.legislativo);
    const territorialMax = Math.max(...tendencia.value.territorial);
    return Math.max(legislativoMax, territorialMax, 1);
});

// Helper Calculations
const calculatePercent = (value, total) => {
    if (!total || total <= 0) return 0;
    return Math.round((value / total) * 100);
};

const getBarHeight = (val, max) => {
    if (!max || max <= 0) return '0.4rem';
    const percent = (val / max) * 100;
    return `${Math.max(percent, 4)}%`;
};

const getStrokeDashArray = (value, total) => {
    if (!total || total <= 0) return '0 100';
    const fill = Math.round((value / total) * 100);
    return `${fill} 100`;
};

const getStrokeDashOffset = (prevValue, total) => {
    if (!total || total <= 0) return '0';
    const offset = Math.round((prevValue / total) * 100);
    return `-${offset}`;
};

const getDayFromDate = (dateStr) => {
    if (!dateStr) return '00';
    return dateStr.split('-')[2] || '00';
};

const getMonthLabel = (dateStr) => {
    if (!dateStr) return 'MES';
    const parts = dateStr.split('-');
    const m = parseInt(parts[1]) - 1;
    const monthsShort = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return monthsShort[m] || 'MES';
};

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return 'HACE POCO';
    const now = new Date();
    const eventTime = new Date(dateStr.replace(/-/g, '/').replace(' ', 'T'));
    const diffMs = now - eventTime;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Hace unos momentos';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
    return dateStr.substring(0, 10);
};

const getPriorityClass = (priority) => {
    switch (priority) {
        case 'Crítica': return 'bg-error-container/20 text-error border-error/20';
        case 'Alta': return 'bg-warning-container/20 text-warning border-warning/20';
        case 'Media': return 'bg-primary-container/20 text-primary border-primary/20';
        default: return 'bg-surface-container-highest text-on-surface-variant border-outline-variant/30';
    }
};

const isOverdue = (dateStr, status) => {
    if (status === 'Completada' || !dateStr) return false;
    const limit = new Date(dateStr + 'T23:59:59');
    return limit < new Date();
};

const getInitials = (name) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ').filter(p => p.length > 0);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

// Data Fetching
const fetchSummary = async () => {
    try {
        const res = await api.get('/dashboard/summary');
        if (res.data && res.data.success) {
            const payload = res.data.data;
            kpis.value = payload.kpis;
            tendencia.value = payload.tendencia;
            miembros.value = payload.miembros || [];
            compromisos.value = payload.compromisos || { 'En Proceso': 0, 'Completados': 0, 'Pendientes': 0, 'Total': 0 };
            alertas.value = payload.alertas || [];
            agenda.value = payload.agenda || [];
            feed.value = payload.feed || [];
        }
    } catch (e) {
        console.error('Error fetching dashboard summary:', e);
    }
};

const fetchTareas = async () => {
    try {
        const res = await api.get('/dashboard/tareas');
        if (res.data && res.data.success) {
            listadoTareas.value = res.data.data || [];
        }
    } catch (e) {
        console.error('Error fetching tasks list:', e);
    }
};

const fetchUsers = async () => {
    try {
        const res = await api.get('/usuarios');
        if (res.data && res.data.status === 'success') {
            usersList.value = res.data.data || [];
        }
    } catch (e) {
        console.error('Error fetching users:', e);
    }
};

// Reset pagination on filter change
watch([searchQuery, filterPriority, filterStatus], () => {
    currentPage.value = 1;
});

// Computed filters and pagination
const filteredTareas = computed(() => {
    return listadoTareas.value.filter(t => {
        const matchesSearch = t.titulo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                              (t.descripcion && t.descripcion.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                              (t.asignado_nombre && t.asignado_nombre.toLowerCase().includes(searchQuery.value.toLowerCase()));
        
        const matchesPriority = filterPriority.value === 'Todos' || t.prioridad === filterPriority.value;
        const matchesStatus = filterStatus.value === 'Todos' || t.estado === filterStatus.value;
        
        return matchesSearch && matchesPriority && matchesStatus;
    });
});

const totalPages = computed(() => Math.ceil(filteredTareas.value.length / itemsPerPage) || 1);

const paginatedTareas = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredTareas.value.slice(start, start + itemsPerPage);
});

// Actions
const exportReport = () => {
    const API_URL = getApiBaseUrl();
    window.open(`${API_URL}/dashboard/export`, '_blank');
};

const openCreateTaskModal = () => {
    editMode.value = false;
    currentTaskId.value = null;
    taskForm.value = {
        titulo: '',
        descripcion: '',
        asignado_a: '',
        fecha_limite: new Date().toISOString().substring(0, 10),
        prioridad: 'Media',
        estado: 'Pendiente'
    };
    showTaskModal.value = true;
};

const openEditTaskModal = (task) => {
    editMode.value = true;
    currentTaskId.value = task.id;
    taskForm.value = {
        titulo: task.titulo,
        descripcion: task.descripcion || '',
        asignado_a: task.asignado_a || '',
        fecha_limite: task.fecha_limite,
        prioridad: task.prioridad,
        estado: task.estado
    };
    showTaskModal.value = true;
};

const closeTaskModal = () => {
    showTaskModal.value = false;
};

const submitTask = async () => {
    loading.value = true;
    try {
        let res;
        if (editMode.value) {
            res = await api.put(`/dashboard/tareas/${currentTaskId.value}`, taskForm.value);
        } else {
            res = await api.post('/dashboard/tareas', taskForm.value);
        }

        if (res.data && res.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: editMode.value ? 'Tarea actualizada' : 'Tarea creada',
                showConfirmButton: false,
                timer: 3000
            });
            closeTaskModal();
            fetchTareas();
            fetchSummary();
        } else {
            Swal.fire('Atención', res.data.error || 'No se pudo guardar la tarea', 'warning');
        }
    } catch (e) {
        Swal.fire('Error', 'Hubo un error de conexión con el servidor', 'error');
    } finally {
        loading.value = false;
    }
};

const updateTaskStatus = async (id, newStatus) => {
    try {
        const res = await api.put(`/dashboard/tareas/${id}`, { estado: newStatus });
        if (res.data && res.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Estado de tarea actualizado',
                showConfirmButton: false,
                timer: 2000
            });
            fetchTareas();
            fetchSummary();
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudo actualizar el estado de la tarea', 'error');
    }
};

const confirmDeleteTask = (id) => {
    Swal.fire({
        title: '¿Eliminar tarea?',
        text: 'Estás a punto de borrar esta tarea de forma permanente. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#BA1A1A',
        cancelButtonColor: '#40484C',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await api.delete(`/dashboard/tareas/${id}`);
                if (res.data && res.data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Tarea eliminada correctamente',
                        showConfirmButton: false,
                        timer: 2500
                    });
                    fetchTareas();
                    fetchSummary();
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo eliminar la tarea', 'error');
            }
        }
    });
};

onMounted(() => {
    fetchSummary();
    fetchTareas();
    fetchUsers();
});
</script>
