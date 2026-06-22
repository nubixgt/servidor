<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">
                    {{ auth.role === 'administrador' ? 'Programación Mensual de Actividades' : 'Mi Programación de Actividades' }}
                </h1>
                <p class="text-sm text-on-surface-variant mt-1">
                    {{ auth.role === 'administrador' ? 'Gestione y monitoree la planificación mensual de inspecciones y visitas.' : 'Consulte sus actividades programadas para el mes y registre sus ejecuciones.' }}
                </p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                <!-- Spontaneous Activity (Inspector Only) -->
                <button 
                    v-if="auth.role === 'inspector'"
                    @click="openEspontaneaModal"
                    class="px-5 py-3 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded-md shadow-sm transition-all flex items-center justify-center gap-2 border border-primary-dim"
                >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Registrar Actividad Espontánea
                </button>
                <!-- Add Manual Activity (Admin Only, always visible) -->
                <button 
                    v-if="auth.role === 'administrador'"
                    @click="openExtraModal"
                    class="px-5 py-3 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded-md shadow-sm transition-all flex items-center justify-center gap-2 border border-primary-dim"
                >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    + Nueva Actividad
                </button>
            </div>
        </div>

        <!-- Toolbar: Month Selector & Inspector Filter -->
        <div class="bg-white p-6 rounded-md border border-surface-container shadow-sm mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="flex flex-wrap gap-4 items-start sm:items-center">
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Seleccionar Mes</label>
                    <input 
                        v-model="mesSeleccionado" 
                        type="month" 
                        class="bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface font-semibold"
                    />
                </div>
                <div v-if="auth.role === 'administrador'">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Filtrar por Inspector</label>
                    <select 
                        v-model="filtroInspector"
                        class="bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                    >
                        <option value="todos">Todos los Inspectores</option>
                        <option v-for="ins in inspectores" :key="ins.id" :value="ins.id">
                            {{ ins.nombre }} ({{ ins.codigo }})
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="bg-white rounded-md border border-surface-container shadow-sm overflow-hidden">
            <!-- Loading -->
            <div v-if="loading" class="py-16 text-center text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
                <p class="mt-2 font-bold">Cargando actividades...</p>
            </div>

            <!-- Empty State (No Programmed Activities) -->
            <div v-else-if="actividades.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant">calendar_today</span>
                <p class="text-sm font-semibold text-on-surface mt-4">No hay actividades programadas para este mes</p>
                <p class="text-xs text-on-surface-variant mt-1">
                    {{ auth.role === 'administrador' ? 'Utilice el botón "+ Nueva Actividad" para programar una inspección o visita.' : 'El administrador aún no ha cargado las actividades de este mes.' }}
                </p>
                <!-- Quick add button in empty state for Admin -->
                <button 
                    v-if="auth.role === 'administrador'"
                    @click="openExtraModal"
                    class="mt-4 px-5 py-2.5 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded-md shadow-sm transition-all inline-flex items-center gap-1.5 border border-primary-dim"
                >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Asignar Actividad
                </button>
            </div>

            <!-- Table & Cards Container (v-else) -->
            <div v-else>
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left border-collapse table-fixed text-[11px]">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-extrabold uppercase text-slate-700 tracking-wider">
                                <th v-if="auth.role === 'administrador'" class="px-4 py-4 w-[16%]">Inspector</th>
                                <th class="px-4 py-4" :class="auth.role === 'administrador' ? 'w-[10%]' : 'w-[12%]'">Fecha</th>
                                <th class="px-4 py-4" :class="auth.role === 'administrador' ? 'w-[11%]' : 'w-[13%]'">Código</th>
                                <th class="px-4 py-4" :class="auth.role === 'administrador' ? 'w-[20%]' : 'w-[23%]'">Tipo de Actividad</th>
                                <th class="px-4 py-4" :class="auth.role === 'administrador' ? 'w-[16%]' : 'w-[19%]'">Establecimiento</th>
                                <th class="px-4 py-4" :class="auth.role === 'administrador' ? 'w-[12%]' : 'w-[15%]'">Observaciones</th>
                                <th class="px-4 py-4 text-center w-[7%]">Tipo</th>
                                <th class="px-4 py-4 text-center w-[8%]">Estado</th>
                                <th class="px-4 py-4 text-right w-[8%]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            <tr v-for="act in filteredActividades" :key="act.id" class="hover:bg-slate-50 transition-colors">
                                <!-- Inspector (Admin Only) -->
                                <td v-if="auth.role === 'administrador'" class="px-4 py-3 truncate max-w-0" :title="act.inspector_nombre">
                                    <p class="font-bold text-on-surface truncate">{{ act.inspector_nombre }}</p>
                                    <p class="text-[9px] text-on-surface-variant font-mono truncate">Cod: {{ act.inspector_codigo }}</p>
                                </td>

                                <!-- Date -->
                                <td class="px-4 py-3 font-semibold text-on-surface-variant font-mono truncate max-w-0" :title="act.fecha_programada">
                                    {{ act.fecha_programada }}
                                </td>

                                <!-- Activity Code -->
                                <td class="px-4 py-3 font-mono font-bold text-primary truncate max-w-0" :title="act.codigo_actividad">
                                    {{ act.codigo_actividad }}
                                </td>

                                <!-- Activity Type -->
                                <td class="px-4 py-3 font-bold text-on-surface truncate max-w-0" :title="act.tipo_actividad">
                                    {{ act.tipo_actividad }}
                                </td>

                                <!-- Establishment -->
                                <td class="px-4 py-3 truncate max-w-0" :title="act.establecimiento">
                                    <p class="font-bold text-on-surface truncate">{{ act.establecimiento }}</p>
                                </td>

                                <!-- Observations -->
                                <td class="px-4 py-3 text-on-surface-variant truncate max-w-0" :title="act.observaciones || 'Sin observaciones'">
                                    {{ act.observaciones || '-' }}
                                </td>

                                <!-- Spontaneous / Programmed Badge -->
                                <td class="px-4 py-3 text-center">
                                    <span 
                                        :class="['px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wide border', 
                                                 act.es_programada ? 'bg-blue-50 border-blue-100 text-blue-600' : 'bg-purple-50 border-purple-100 text-purple-600']"
                                    >
                                        {{ act.es_programada ? 'Prog' : 'Espont' }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span 
                                            :class="['px-1.5 py-0.5 rounded-full text-[9px] font-bold border uppercase tracking-wide', 
                                                     act.estado === 'programada' ? 'bg-slate-100 border-slate-300 text-slate-600' :
                                                     act.estado === 'ejecutada' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' :
                                                     'bg-red-50 border-red-200 text-red-700']"
                                        >
                                            {{ act.estado === 'programada' ? 'Pend' : (act.estado === 'ejecutada' ? 'Ejec' : 'Incump') }}
                                        </span>
                                        <!-- Reason / Obs text -->
                                        <p 
                                            v-if="act.estado === 'no_ejecutada'" 
                                            class="text-[9px] text-red-500 font-semibold max-w-[120px] truncate"
                                            :title="act.motivo_incumplimiento"
                                        >
                                            Obs: {{ act.motivo_incumplimiento }}
                                        </p>
                                        <p 
                                            v-if="act.estado === 'ejecutada' && act.observaciones" 
                                            class="text-[9px] text-emerald-600 font-semibold max-w-[120px] truncate"
                                            :title="act.observaciones"
                                        >
                                            {{ act.observaciones }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <!-- Admin Actions -->
                                    <div v-if="auth.role === 'administrador'" class="flex justify-end gap-2">
                                        <button 
                                            @click="openEditModal(act)"
                                            class="text-blue-600 hover:text-blue-800 font-bold text-xs flex items-center gap-0.5"
                                        >
                                            <span class="material-symbols-outlined text-xs">edit</span> Editar
                                        </button>
                                        <button 
                                            @click="eliminarActividad(act.id)"
                                            class="text-red-600 hover:text-red-800 font-bold text-xs flex items-center gap-0.5"
                                        >
                                            <span class="material-symbols-outlined text-xs">delete</span> Quitar
                                        </button>
                                    </div>

                                    <!-- Inspector Actions -->
                                    <div v-if="auth.role === 'inspector' && act.estado === 'programada'" class="flex justify-end gap-1.5">
                                        <button 
                                            @click="openExecuteModal(act, 'ejecutada')"
                                            class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold transition-colors flex items-center gap-0.5 border border-emerald-700 shadow-sm"
                                        >
                                            <span class="material-symbols-outlined text-[10px]">done</span> Ejec
                                        </button>
                                        <button 
                                            @click="openExecuteModal(act, 'no_ejecutada')"
                                            class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-[10px] font-bold transition-colors flex items-center gap-0.5 border border-red-700 shadow-sm"
                                        >
                                            <span class="material-symbols-outlined text-[10px]">close</span> Incump
                                        </button>
                                    </div>
                                    <span v-else-if="auth.role === 'inspector'" class="text-slate-400 text-xs italic font-medium">Registrado</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <div class="lg:hidden p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="act in filteredActividades" 
                        :key="act.id" 
                        class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm hover:shadow transition-shadow flex flex-col justify-between"
                    >
                        <div>
                            <!-- Card Header: Code & Date + Status Badges -->
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <span class="font-mono font-bold text-primary text-xs">{{ act.codigo_actividad }}</span>
                                <div class="flex items-center gap-1.5">
                                    <span 
                                        :class="['px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wide border', 
                                                 act.es_programada ? 'bg-blue-50 border-blue-100 text-blue-600' : 'bg-purple-50 border-purple-100 text-purple-600']"
                                    >
                                        {{ act.es_programada ? 'Prog' : 'Espont' }}
                                    </span>
                                    <span 
                                        :class="['px-1.5 py-0.5 rounded-full text-[9px] font-bold border uppercase tracking-wide', 
                                                 act.estado === 'programada' ? 'bg-slate-100 border-slate-300 text-slate-600' :
                                                 act.estado === 'ejecutada' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' :
                                                 'bg-red-50 border-red-200 text-red-700']"
                                    >
                                        {{ act.estado === 'programada' ? 'Pend' : (act.estado === 'ejecutada' ? 'Ejec' : 'Incump') }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Card Body: Date, Type, Place -->
                            <h4 class="text-xs font-black text-on-surface mb-2 tracking-tight">{{ act.tipo_actividad }}</h4>
                            
                            <div class="space-y-1.5 text-[11px] text-on-surface-variant">
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-slate-400">calendar_today</span>
                                    <span class="font-semibold">{{ act.fecha_programada }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-slate-400">store</span>
                                    <span class="font-semibold">{{ act.establecimiento }}</span>
                                </div>
                                <div v-if="auth.role === 'administrador'" class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-slate-400">badge</span>
                                    <span>{{ act.inspector_nombre }} <span class="text-[9px] font-mono">({{ act.inspector_codigo }})</span></span>
                                </div>
                            </div>

                            <!-- Observations/Incumplimiento text boxes -->
                            <div v-if="act.observaciones" class="mt-2.5 bg-slate-50 p-2 rounded border border-slate-200 text-[10px] text-on-surface-variant italic">
                                <strong>Obs:</strong> {{ act.observaciones }}
                            </div>
                            <div v-if="act.estado === 'no_ejecutada'" class="mt-2 bg-red-50 p-2 rounded border border-red-100 text-[10px] text-red-700 font-semibold">
                                <strong>Incumplimiento:</strong> {{ act.motivo_incumplimiento }}
                            </div>
                        </div>

                        <!-- Card Footer: Actions -->
                        <div class="border-t border-slate-100 pt-3 mt-3 flex justify-end items-center gap-2">
                            <!-- Admin Actions -->
                            <div v-if="auth.role === 'administrador'" class="flex gap-2">
                                <button 
                                    @click="openEditModal(act)"
                                    class="px-2.5 py-1 text-blue-600 hover:bg-blue-50 rounded border border-blue-200 text-xs font-bold transition-colors flex items-center gap-0.5"
                                >
                                    <span class="material-symbols-outlined text-xs">edit</span> Editar
                                </button>
                                <button 
                                    @click="eliminarActividad(act.id)"
                                    class="px-2.5 py-1 text-red-600 hover:bg-red-50 rounded border border-red-200 text-xs font-bold transition-colors flex items-center gap-0.5"
                                >
                                    <span class="material-symbols-outlined text-xs">delete</span> Quitar
                                </button>
                            </div>

                            <!-- Inspector Actions -->
                            <div v-if="auth.role === 'inspector' && act.estado === 'programada'" class="flex gap-2 w-full sm:w-auto">
                                <button 
                                    @click="openExecuteModal(act, 'ejecutada')"
                                    class="flex-1 sm:flex-initial px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition-colors flex items-center justify-center gap-0.5 border border-emerald-700 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-xs">done</span> Ejecutada
                                </button>
                                <button 
                                    @click="openExecuteModal(act, 'no_ejecutada')"
                                    class="flex-1 sm:flex-initial px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-bold transition-colors flex items-center justify-center gap-0.5 border border-red-700 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-xs">close</span> Incumplida
                                </button>
                            </div>
                            <span v-else-if="auth.role === 'inspector'" class="text-slate-400 text-xs italic font-medium">Registrado</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODALS ================= -->

        <!-- MODAL: Assign Activity (Admin Only) or Edit Activity (Admin Only) -->
        <div v-if="showAdminFormModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-lg shadow-xl border border-surface-container max-w-lg w-full overflow-hidden animate-slide-up">
                <!-- Modal Title -->
                <div class="px-6 py-4 bg-slate-50 border-b border-surface-container flex items-center justify-between">
                    <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">{{ modalEditId ? 'edit_document' : 'assignment' }}</span>
                        {{ modalEditId ? 'Editar Actividad Programada' : 'Asignar Actividad' }}
                    </h3>
                    <button @click="showAdminFormModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <!-- Inspector Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Inspector Asignado *</label>
                        <select 
                            v-model="formModel.inspector_id"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        >
                            <option value="">Seleccione un inspector...</option>
                            <option v-for="ins in inspectores" :key="ins.id" :value="ins.id">
                                {{ ins.nombre }} ({{ ins.codigo }})
                            </option>
                        </select>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Programada *</label>
                        <input 
                            v-model="formModel.fecha_programada"
                            type="date"
                            :min="`${mesSeleccionado}-01`"
                            :max="maxDateForMonth"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        />
                    </div>

                    <!-- Activity Code -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Código de Actividad * (SOIC)</label>
                        <input 
                            v-model="formModel.codigo_actividad"
                            type="text"
                            placeholder="Ej. FORMA SOIC 0"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface font-mono"
                        />
                    </div>

                    <!-- Activity Type -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Actividad *</label>
                        <input 
                            v-model="formModel.tipo_actividad"
                            type="text"
                            placeholder="Texto libre de la actividad"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        />
                    </div>

                    <!-- Establishment / Location -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Establecimiento / Lugar *</label>
                        <input 
                            v-model="formModel.establecimiento"
                            type="text"
                            placeholder="Ej. Rastro Municipal Central"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        />
                    </div>

                    <!-- Observations -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones (Opcional)</label>
                        <textarea 
                            v-model="formModel.observaciones"
                            placeholder="Instrucciones o detalles de la visita..."
                            rows="3"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-surface-container flex items-center justify-end gap-3">
                    <button 
                        @click="showAdminFormModal = false"
                        class="px-4 py-2 border border-slate-300 hover:bg-slate-100 rounded text-xs font-bold text-slate-600 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="guardarActividadForm"
                        class="px-5 py-2 bg-primary hover:bg-primary-dim text-on-primary rounded text-xs font-bold transition-all shadow-sm flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-sm">check</span>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>


        <!-- MODAL: Register Execution / Non-compliance (Inspector Only) -->
        <div v-if="showExecutionModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-lg shadow-xl border border-surface-container max-w-md w-full overflow-hidden animate-slide-up">
                <!-- Modal Title -->
                <div class="px-6 py-4 bg-slate-50 border-b border-surface-container flex items-center justify-between">
                    <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span 
                            :class="['material-symbols-outlined', 
                                     executionForm.estado === 'ejecutada' ? 'text-emerald-600' : 'text-red-600']"
                        >
                            {{ executionForm.estado === 'ejecutada' ? 'check_circle' : 'cancel' }}
                        </span>
                        {{ executionForm.estado === 'ejecutada' ? 'Registrar Actividad Ejecutada' : 'Reportar Actividad Incumplida' }}
                    </h3>
                    <button @click="showExecutionModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div class="bg-slate-50 p-3.5 rounded border border-slate-200 text-xs">
                        <p class="font-bold text-on-surface">Actividad: <span class="font-medium text-slate-600">{{ executionForm.tipo_actividad }}</span></p>
                        <p class="font-bold text-on-surface mt-1">Establecimiento: <span class="font-medium text-slate-600">{{ executionForm.establecimiento }}</span></p>
                        <p class="font-bold text-on-surface mt-1">Fecha programada: <span class="font-medium text-slate-600 font-mono">{{ executionForm.fecha_programada }}</span></p>
                    </div>

                    <!-- Field based on action type -->
                    <div v-if="executionForm.estado === 'ejecutada'">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones de la Ejecución</label>
                        <textarea 
                            v-model="executionForm.observaciones"
                            placeholder="Detalles sobre los resultados de la inspección, hallazgos, etc."
                            rows="4"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface resize-none"
                        ></textarea>
                    </div>

                    <div v-else>
                        <label class="block text-[10px] font-bold text-red-600 uppercase tracking-wider mb-2">Motivo del Incumplimiento * (Requerido)</label>
                        <textarea 
                            v-model="executionForm.motivo_incumplimiento"
                            placeholder="Explique detalladamente por qué no se pudo ejecutar la actividad programada..."
                            rows="4"
                            class="w-full bg-slate-50 border border-red-300 focus:border-red-500 rounded px-3 py-2 text-xs outline-none transition-all text-on-surface resize-none"
                        ></textarea>
                        <p class="text-[10px] text-red-500 mt-1 font-semibold">Este campo es obligatorio para documentar la omisión.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-surface-container flex items-center justify-end gap-3">
                    <button 
                        @click="showExecutionModal = false"
                        class="px-4 py-2 border border-slate-300 hover:bg-slate-100 rounded text-xs font-bold text-slate-600 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="guardarEjecucion"
                        :class="['px-5 py-2 text-white rounded text-xs font-bold transition-all shadow-sm flex items-center gap-1', 
                                 executionForm.estado === 'ejecutada' ? 'bg-emerald-600 hover:bg-emerald-700 border border-emerald-700' : 'bg-red-600 hover:bg-red-700 border border-red-700']"
                    >
                        <span class="material-symbols-outlined text-sm">done</span>
                        Registrar
                    </button>
                </div>
            </div>
        </div>


        <!-- MODAL: Register Spontaneous Activity (Inspector Only) -->
        <div v-if="showEspontaneaModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-lg shadow-xl border border-surface-container max-w-lg w-full overflow-hidden animate-slide-up">
                <!-- Modal Title -->
                <div class="px-6 py-4 bg-slate-50 border-b border-surface-container flex items-center justify-between">
                    <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-purple-600">bolt</span>
                        Registrar Actividad Espontánea (No Programada)
                    </h3>
                    <button @click="showEspontaneaModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div class="bg-purple-50 p-3 text-[11px] text-purple-800 border border-purple-200 rounded font-medium">
                        Esta opción permite registrar inspecciones que se ejecutaron fuera de la programación mensual del administrador. Se guardará directamente con el estado "Ejecutada".
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha de Ejecución *</label>
                        <input 
                            v-model="espontaneaForm.fecha_programada"
                            type="date"
                            :min="`${mesSeleccionado}-01`"
                            :max="maxDateForMonth"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface font-semibold"
                        />
                    </div>

                    <!-- Activity Code -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Código de Actividad * (SOIC)</label>
                        <input 
                            v-model="espontaneaForm.codigo_actividad"
                            type="text"
                            placeholder="Ej. FORMA SOIC 0"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface font-mono"
                        />
                    </div>

                    <!-- Activity Type -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Actividad *</label>
                        <input 
                            v-model="espontaneaForm.tipo_actividad"
                            type="text"
                            placeholder="Detalle de la inspección espontánea"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        />
                    </div>

                    <!-- Establishment -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Establecimiento / Lugar *</label>
                        <input 
                            v-model="espontaneaForm.establecimiento"
                            type="text"
                            placeholder="Ej. Expendio de Carnes La Nueva"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        />
                    </div>

                    <!-- Observations -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones / Hallazgos</label>
                        <textarea 
                            v-model="espontaneaForm.observaciones"
                            placeholder="Detalle de la inspección, hallazgos u observaciones encontradas..."
                            rows="3"
                            class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-surface-container flex items-center justify-end gap-3">
                    <button 
                        @click="showEspontaneaModal = false"
                        class="px-4 py-2 border border-slate-300 hover:bg-slate-100 rounded text-xs font-bold text-slate-600 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="guardarEspontanea"
                        class="px-5 py-2 bg-purple-600 hover:bg-purple-700 border border-purple-700 text-white rounded text-xs font-bold transition-all shadow-sm flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-sm">bolt</span>
                        Registrar Ejecución
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';

const auth = useAuthStore();

// States
const mesSeleccionado = ref(new Date().toISOString().substring(0, 7)); // YYYY-MM
const actividades = ref([]);
const inspectores = ref([]);
const loading = ref(false);

// Filters
const filtroInspector = ref('todos');

// Modals State - Admin form (add/edit)
const showAdminFormModal = ref(false);
const modalEditId = ref(null);
const formModel = ref({
    inspector_id: '',
    fecha_programada: '',
    codigo_actividad: '',
    tipo_actividad: '',
    establecimiento: '',
    observaciones: ''
});

// Modals State - Inspector execution
const showExecutionModal = ref(false);
const executionForm = ref({
    id: null,
    tipo_actividad: '',
    establecimiento: '',
    fecha_programada: '',
    estado: 'ejecutada',
    observaciones: '',
    motivo_incumplimiento: ''
});

// Modals State - Inspector spontaneous
const showEspontaneaModal = ref(false);
const espontaneaForm = ref({
    fecha_programada: '',
    codigo_actividad: 'FORMA SOIC 0',
    tipo_actividad: '',
    establecimiento: '',
    observaciones: ''
});

// Max date calculated to prevent input beyond the chosen month
const maxDateForMonth = computed(() => {
    if (!mesSeleccionado.value) return '';
    const [year, month] = mesSeleccionado.value.split('-');
    const lastDay = new Date(year, month, 0).getDate();
    return `${mesSeleccionado.value}-${String(lastDay).padStart(2, '0')}`;
});

// Watchers
watch(mesSeleccionado, () => {
    fetchActividades();
});

// Methods

// Load activities from DB
const fetchActividades = async () => {
    loading.value = true;
    try {
        const response = await api.get(`/programacion`, {
            params: { mes: mesSeleccionado.value }
        });
        if (response.data?.status === 'success') {
            actividades.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar la programación de actividades', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron recuperar las actividades del servidor.',
            confirmButtonColor: 'var(--color-primary)'
        });
    } finally {
        loading.value = false;
    }
};

// Load inspectores (Only needed for admin dropdowns)
const fetchInspectores = async () => {
    if (auth.role !== 'administrador') return;
    try {
        const response = await api.get(`/programacion/inspectores`);
        if (response.data?.status === 'success') {
            inspectores.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar inspectores activos', error);
    }
};

// Filtering computed
const filteredActividades = computed(() => {
    if (filtroInspector.value === 'todos') {
        return actividades.value;
    }
    return actividades.value.filter(a => a.inspector_id === parseInt(filtroInspector.value));
});

// Delete single saved activity
const eliminarActividad = (id) => {
    Swal.fire({
        title: '¿Está seguro de quitar esta actividad?',
        text: 'La actividad será eliminada definitivamente de la programación de este mes.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#b91c1c',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            loading.value = true;
            try {
                const response = await api.delete(`/programacion/${id}`);
                if (response.data?.status === 'success') {
                    Swal.fire('Eliminado', 'La actividad ha sido removida.', 'success');
                    fetchActividades();
                }
            } catch (error) {
                console.error('Error al eliminar', error);
                Swal.fire('Error', 'No se pudo eliminar la actividad.', 'error');
            } finally {
                loading.value = false;
            }
        }
    });
};

// Opens Modal to Add Single Activity
const openExtraModal = () => {
    modalEditId.value = null;
    formModel.value = {
        inspector_id: inspectores.value.length > 0 ? inspectores.value[0].id : '',
        fecha_programada: `${mesSeleccionado.value}-01`,
        codigo_actividad: '',
        tipo_actividad: '',
        establecimiento: '',
        observaciones: ''
    };
    showAdminFormModal.value = true;
};

// Opens Modal to Edit Single Saved Activity
const openEditModal = (act) => {
    modalEditId.value = act.id;
    formModel.value = {
        inspector_id: act.inspector_id,
        fecha_programada: act.fecha_programada,
        codigo_actividad: act.codigo_actividad,
        tipo_actividad: act.tipo_actividad,
        establecimiento: act.establecimiento,
        observaciones: act.observaciones || ''
    };
    showAdminFormModal.value = true;
};

// Saves form (individual add or edit)
const guardarActividadForm = async () => {
    const f = formModel.value;
    if (!f.inspector_id || !f.fecha_programada || !f.codigo_actividad.trim() || !f.tipo_actividad.trim() || !f.establecimiento.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor, complete todos los campos obligatorios del formulario.',
            confirmButtonColor: 'var(--color-primary)'
        });
        return;
    }

    loading.value = true;
    try {
        if (modalEditId.value) {
            // Edit
            const response = await api.put(`/programacion/${modalEditId.value}`, f);
            if (response.data?.status === 'success') {
                Swal.fire('Guardado', 'La actividad programada ha sido actualizada.', 'success');
                showAdminFormModal.value = false;
                fetchActividades();
            }
        } else {
            // Add manual activity (sends single POST /programacion)
            const response = await api.post(`/programacion`, f);
            if (response.data?.status === 'success') {
                Swal.fire('Asignada', 'La actividad ha sido asignada correctamente.', 'success');
                showAdminFormModal.value = false;
                fetchActividades();
            }
        }
    } catch (error) {
        console.error('Error al guardar formulario de actividad', error);
        Swal.fire('Error', error.response?.data?.error || 'No se pudo completar la operación.', 'error');
    } finally {
        loading.value = false;
    }
};

// Opens Modal for inspector to check-in/report status
const openExecuteModal = (act, estado) => {
    executionForm.value = {
        id: act.id,
        tipo_actividad: act.tipo_actividad,
        establecimiento: act.establecimiento,
        fecha_programada: act.fecha_programada,
        estado: estado,
        observaciones: '',
        motivo_incumplimiento: ''
    };
    showExecutionModal.value = true;
};

// Inspector registers execution/non-compliance in DB
const guardarEjecucion = async () => {
    const ef = executionForm.value;
    
    if (ef.estado === 'no_ejecutada' && !ef.motivo_incumplimiento.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Motivo Obligatorio',
            text: 'Debe explicar el motivo por el cual no se cumplió la actividad.',
            confirmButtonColor: '#b91c1c'
        });
        return;
    }

    loading.value = true;
    try {
        const response = await api.put(`/programacion/${ef.id}/ejecutar`, {
            estado: ef.estado,
            observaciones: ef.observaciones,
            motivo_incumplimiento: ef.motivo_incumplimiento
        });
        if (response.data?.status === 'success') {
            Swal.fire('Registrado', 'El reporte ha sido enviado y archivado.', 'success');
            showExecutionModal.value = false;
            fetchActividades();
        }
    } catch (error) {
        console.error('Error al guardar ejecución', error);
        Swal.fire('Error', error.response?.data?.error || 'No se pudo guardar el estado.', 'error');
    } finally {
        loading.value = false;
    }
};

// Opens Modal to Add Spontaneous Unplanned Activity
const openEspontaneaModal = () => {
    espontaneaForm.value = {
        fecha_programada: new Date().toISOString().substring(0, 10),
        codigo_actividad: 'FORMA SOIC 0',
        tipo_actividad: '',
        establecimiento: '',
        observaciones: ''
    };
    showEspontaneaModal.value = true;
};

// Inspector saves spontaneous activity in DB
const guardarEspontanea = async () => {
    const f = espontaneaForm.value;
    if (!f.fecha_programada || !f.codigo_actividad.trim() || !f.tipo_actividad.trim() || !f.establecimiento.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos vacíos',
            text: 'Debe rellenar la fecha, el código, el tipo de actividad y el establecimiento.',
            confirmButtonColor: 'var(--color-primary)'
        });
        return;
    }

    loading.value = true;
    try {
        const response = await api.post(`/programacion/espontanea`, f);
        if (response.data?.status === 'success') {
            Swal.fire('Registrada', 'La actividad espontánea ha sido registrada como ejecutada.', 'success');
            showEspontaneaModal.value = false;
            fetchActividades();
        }
    } catch (error) {
        console.error('Error al registrar espontánea', error);
        Swal.fire('Error', error.response?.data?.error || 'No se pudo registrar la actividad.', 'error');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchActividades();
    fetchInspectores();
});
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
.animate-slide-up {
    animation: slideUp 0.25s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
