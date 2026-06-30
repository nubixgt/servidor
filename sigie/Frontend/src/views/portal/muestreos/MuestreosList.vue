<template>
    <div class="space-y-8 animate-fade-in">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Muestreos en Importaciones</h1>
                <p class="text-xs text-white/60 mt-1">Sugerencias algorítmicas, alarmas por volumen y asignaciones dirigidas manualmente.</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    v-if="auth.role === 'administrador'"
                    @click="openManualModal"
                    class="px-5 py-3 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded-md shadow-lg transition-all flex items-center justify-center gap-2 border border-slate-800 font-headline"
                >
                    <span class="material-symbols-outlined text-sm">assignment_add</span>
                    Muestreo Dirigido
                </button>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Estado</label>
                <select 
                    v-model="filters.estado" 
                    class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                >
                    <option value="">Todos los estados</option>
                    <option value="Sugerido">Sugerido</option>
                    <option value="Aprobado">Aprobado</option>
                    <option value="Ejecutado">Ejecutado</option>
                    <option value="Rechazado">Rechazado</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Importador</label>
                <select 
                    v-model="filters.importador_id" 
                    class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                >
                    <option value="">Todos los importadores</option>
                    <option v-for="imp in importers" :key="imp.id" :value="imp.id">{{ imp.nombre }}</option>
                </select>
            </div>
            <div v-if="auth.role === 'administrador'">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Inspector Asignado</label>
                <select 
                    v-model="filters.inspector_id" 
                    class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                >
                    <option value="">Todos los inspectores</option>
                    <option v-for="ins in inspectors" :key="ins.id" :value="ins.id">{{ ins.nombre }}</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha Desde</label>
                <input 
                    v-model="filters.fecha_inicio" 
                    type="date"
                    class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha Hasta</label>
                <input 
                    v-model="filters.fecha_fin" 
                    type="date"
                    class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
            <div class="sm:col-span-2 md:col-span-1 flex items-end">
                <button 
                    @click="fetchSamplings"
                    class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded transition-colors flex items-center justify-center gap-1.5"
                >
                    <span class="material-symbols-outlined text-sm">search</span> Buscar Muestreos
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div v-if="loading" class="py-16 text-center text-sm text-slate-400 glass-card rounded border border-white/10 shadow-lg">
            <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
            <p class="mt-2 font-bold">Cargando listado de muestreos...</p>
        </div>

        <div v-else-if="samplings.length === 0" class="py-20 text-center glass-card rounded border border-white/10 shadow-lg">
            <span class="material-symbols-outlined text-5xl text-slate-400">science</span>
            <p class="text-sm font-semibold text-white mt-4">No hay muestreos registrados</p>
            <p class="text-xs text-slate-400 mt-1">Intente ajustar los filtros de búsqueda o consulte al administrador.</p>
        </div>

        <!-- Muestreos Table (Desktop) & Cards (Mobile) -->
        <div v-else class="glass-card backdrop-blur-sm rounded-2xl border border-white/20 shadow-premium overflow-hidden">
            <!-- Desktop View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-4 py-4">Origen</th>
                            <th class="px-6 py-4">Importador</th>
                            <th class="px-6 py-4">Producto</th>
                            <th class="px-6 py-4">Fecha Programada</th>
                            <th class="px-6 py-4">Asignado</th>
                            <th class="px-4 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs">
                        <tr v-for="item in samplings" :key="item.id" class="hover:bg-black/20 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-gray-300">#{{ item.id }}</td>
                            <td class="px-4 py-4">
                                <span 
                                    :class="['px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase border',
                                             item.origen === 'Algoritmo' ? 'bg-sky-50 border-sky-200 text-sky-700' :
                                             item.origen === 'Alarma' ? 'bg-red-50 border-red-200 text-red-700' :
                                             'bg-purple-50 border-purple-200 text-purple-700']"
                                >
                                    {{ item.origen }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ item.importador_nombre }}</p>
                                <p class="text-[9px] text-slate-400 font-mono">NIT: {{ item.importador_nit }}</p>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-300">{{ item.tipo_producto }}</td>
                            <td class="px-6 py-4 font-mono text-gray-300">{{ item.fecha_programada }}</td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-300">{{ item.inspector_nombre }}</p>
                                <p class="text-[9px] text-slate-400 font-mono">{{ item.inspector_codigo }}</p>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span 
                                    :class="['px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase border',
                                             item.estado === 'Sugerido' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                             item.estado === 'Aprobado' ? 'bg-blue-50 border-blue-200 text-blue-700' :
                                             item.estado === 'Ejecutado' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' :
                                             'bg-red-50 border-red-200 text-red-700']"
                                >
                                    {{ item.estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <!-- General details -->
                                <router-link 
                                    :to="`/muestreos/${item.id}`" 
                                    class="px-2.5 py-1.5 bg-black/20 hover:bg-slate-100 text-gray-300 border border-white/10 rounded font-bold text-[9px] uppercase inline-flex items-center gap-0.5"
                                >
                                    Detalle
                                </router-link>

                                <!-- Admin Validations -->
                                <template v-if="auth.role === 'administrador' && item.estado === 'Sugerido'">
                                    <button 
                                        @click="validateSampling(item.id, 'Aprobado')"
                                        class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-700 rounded font-bold text-[9px] uppercase"
                                    >
                                        Aprobar
                                    </button>
                                    <button 
                                        @click="promptReject(item.id)"
                                        class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded font-bold text-[9px] uppercase"
                                    >
                                        Rechazar
                                    </button>
                                </template>

                                <template v-if="auth.role === 'administrador' && (item.estado === 'Sugerido' || item.estado === 'Aprobado')">
                                    <button 
                                        @click="openEditModal(item)"
                                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-gray-300 rounded font-bold text-[9px] uppercase"
                                    >
                                        Editar
                                    </button>
                                    <button 
                                        @click="deleteSampling(item.id)"
                                        class="px-2.5 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded font-bold text-[9px] uppercase"
                                    >
                                        Borrar
                                    </button>
                                </template>

                                <!-- Inspector Executions -->
                                <template v-if="auth.role === 'inspector' && item.estado === 'Aprobado'">
                                    <button 
                                        @click="openExecuteModal(item.id)"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[9px] uppercase inline-flex items-center gap-1"
                                    >
                                        <span class="material-symbols-outlined text-[10px]">task_alt</span>
                                        Ejecutar
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="block lg:hidden divide-y divide-slate-100 text-xs">
                <div v-for="item in samplings" :key="item.id" class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[9px] font-bold text-gray-300">Muestreo #{{ item.id }}</span>
                        <div class="flex items-center gap-1.5">
                            <span 
                                :class="['px-2 py-0.5 rounded-full text-[8px] font-black uppercase border',
                                         item.origen === 'Algoritmo' ? 'bg-sky-50 border-sky-200 text-sky-700' :
                                         item.origen === 'Alarma' ? 'bg-red-50 border-red-200 text-red-700' :
                                         'bg-purple-50 border-purple-200 text-purple-700']"
                            >
                                {{ item.origen }}
                            </span>
                            <span 
                                :class="['px-2 py-0.5 rounded-full text-[8px] font-black uppercase border',
                                         item.estado === 'Sugerido' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                         item.estado === 'Aprobado' ? 'bg-blue-50 border-blue-200 text-blue-700' :
                                         item.estado === 'Ejecutado' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' :
                                         'bg-red-50 border-red-200 text-red-700']"
                            >
                                {{ item.estado }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="font-bold text-white">{{ item.importador_nombre }}</p>
                        <p class="text-[10px] text-gray-300 font-semibold mt-0.5">{{ item.tipo_producto }} | {{ item.fecha_programada }}</p>
                        <p class="text-[9px] text-slate-400 font-mono mt-1">Inspector: {{ item.inspector_nombre }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/10">
                        <router-link 
                            :to="`/muestreos/${item.id}`" 
                            class="px-3 py-1.5 bg-black/20 hover:bg-slate-100 border border-white/10 rounded font-bold text-[9px] uppercase"
                        >
                            Detalle
                        </router-link>

                        <!-- Admin Actions -->
                        <template v-if="auth.role === 'administrador' && item.estado === 'Sugerido'">
                            <button 
                                @click="validateSampling(item.id, 'Aprobado')"
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[9px] uppercase"
                            >
                                Aprobar
                            </button>
                            <button 
                                @click="promptReject(item.id)"
                                class="px-3 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded font-bold text-[9px] uppercase"
                            >
                                Rechazar
                            </button>
                        </template>

                        <template v-if="auth.role === 'administrador' && (item.estado === 'Sugerido' || item.estado === 'Aprobado')">
                            <button 
                                @click="openEditModal(item)"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-gray-300 rounded font-bold text-[9px] uppercase"
                            >
                                Editar
                            </button>
                            <button 
                                @click="deleteSampling(item.id)"
                                class="px-3 py-1.5 bg-red-100 text-red-700 rounded font-bold text-[9px] uppercase"
                            >
                                Borrar
                            </button>
                        </template>

                        <!-- Inspector Actions -->
                        <template v-if="auth.role === 'inspector' && item.estado === 'Aprobado'">
                            <button 
                                @click="openExecuteModal(item.id)"
                                class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[9px] uppercase"
                            >
                                Ejecutar
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: REGISTRAR MUESTREO DIRIGIDO -->
        <div v-if="showManualModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-lg border border-white/10 shadow-xl max-w-md w-full overflow-hidden animate-fade-in animate-duration-150">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-headline font-bold text-sm uppercase tracking-wider">Asignar Muestreo Dirigido</h3>
                    <button @click="showManualModal = false" class="text-white hover:text-slate-300">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <form @submit.prevent="saveManualSampling" class="p-6 space-y-4">
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Empresa Importadora *</label>
                        <select 
                            v-model="manualForm.importador_id" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        >
                            <option value="">Seleccione el importador</option>
                            <option v-for="imp in importers" :key="imp.id" :value="imp.id">{{ imp.nombre }}</option>
                        </select>
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Tipo de Producto *</label>
                        <input 
                            v-model="manualForm.tipo_producto" 
                            type="text" 
                            required 
                            placeholder="Ej. Carne de ave deshuesada"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Inspector Asignado *</label>
                        <select 
                            v-model="manualForm.inspector_id" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        >
                            <option value="">Seleccione el inspector</option>
                            <option v-for="ins in inspectors" :key="ins.id" :value="ins.id">{{ ins.nombre }}</option>
                        </select>
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Fecha Programada *</label>
                        <input 
                            v-model="manualForm.fecha_programada" 
                            type="date" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>

                    <div class="pt-4 flex justify-end gap-3 text-xs">
                        <button 
                            type="button" 
                            @click="showManualModal = false" 
                            class="px-4 py-2 border border-white/10 text-gray-300 font-bold rounded hover:bg-black/20 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingManual"
                            class="px-5 py-2 bg-[#0a192f] hover:bg-[#122347] text-white font-bold rounded border border-slate-800 flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="savingManual">sync</span>
                            <span>Asignar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDITAR PROGRAMACION DE MUESTREO (ADMIN) -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-lg border border-white/10 shadow-xl max-w-md w-full overflow-hidden animate-fade-in animate-duration-150">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-headline font-bold text-sm uppercase tracking-wider">Modificar Muestreo</h3>
                    <button @click="showEditModal = false" class="text-white hover:text-slate-300">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <form @submit.prevent="saveEditSampling" class="p-6 space-y-4">
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Inspector Asignado *</label>
                        <select 
                            v-model="editForm.inspector_id" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        >
                            <option value="">Seleccione el inspector</option>
                            <option v-for="ins in inspectors" :key="ins.id" :value="ins.id">{{ ins.nombre }}</option>
                        </select>
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Fecha Programada *</label>
                        <input 
                            v-model="editForm.fecha_programada" 
                            type="date" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>

                    <div class="pt-4 flex justify-end gap-3 text-xs">
                        <button 
                            type="button" 
                            @click="showEditModal = false" 
                            class="px-4 py-2 border border-white/10 text-gray-300 font-bold rounded hover:bg-black/20 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingEdit"
                            class="px-5 py-2 bg-[#0a192f] hover:bg-[#122347] text-white font-bold rounded border border-slate-800 flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="savingEdit">sync</span>
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: REGISTRAR EJECUCION DE MUESTREO (INSPECTOR) -->
        <div v-if="showExecuteModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-lg border border-white/10 shadow-xl max-w-md w-full overflow-hidden animate-fade-in">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-headline font-bold text-sm uppercase tracking-wider">Reportar Ejecución de Muestreo</h3>
                    <button @click="showExecuteModal = false" class="text-white hover:text-slate-300">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <form @submit.prevent="saveExecution" class="p-6 space-y-4">
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Observaciones de la Muestra *</label>
                        <textarea 
                            v-model="executeForm.observaciones" 
                            required 
                            rows="4" 
                            placeholder="Escriba los detalles observados durante el muestreo..."
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        ></textarea>
                    </div>

                    <!-- Upload Support Documents -->
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-2">Adjuntar Fotografías / PDF de Soporte</label>
                        <div class="w-full py-6 rounded border border-dashed border-white/10-variant hover:border-primary/50 bg-black/20 hover:bg-[#0a192f]/5 transition-colors flex flex-col items-center justify-center text-center cursor-pointer relative mb-3">
                            <input 
                                type="file" 
                                multiple
                                accept="application/pdf,image/*" 
                                @change="onFilesSelected" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <span class="material-symbols-outlined text-2xl text-slate-400">upload_file</span>
                            <span class="font-bold text-gray-300 mt-1">Seleccionar Archivos</span>
                            <span class="text-[9px] text-slate-400 mt-0.5">PDF o imágenes JPG/PNG</span>
                        </div>

                        <!-- Previews list -->
                        <div v-if="filesPreviews.length > 0" class="space-y-2 max-h-32 overflow-y-auto pr-1">
                            <div v-for="(preview, idx) in filesPreviews" :key="idx" class="p-2 bg-black/20 border border-white/10 rounded flex items-center justify-between gap-2">
                                <p class="font-bold text-gray-300 truncate flex-1 font-mono text-[9px]">{{ preview.name }}</p>
                                <button 
                                    type="button" 
                                    @click="removeFile(idx)"
                                    class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center"
                                >
                                    <span class="material-symbols-outlined text-[10px] font-black">close</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 text-xs">
                        <button 
                            type="button" 
                            @click="showExecuteModal = false" 
                            class="px-4 py-2 border border-white/10 text-gray-300 font-bold rounded hover:bg-black/20 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingExecution"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded border border-emerald-700 flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="savingExecution">sync</span>
                            <span>Reportar Ejecución</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';

const auth = useAuthStore();

// Lists
const samplings = ref([]);
const importers = ref([]);
const inspectors = ref([]);
const loading = ref(true);

// Filters State
const filters = ref({
    estado: '',
    importador_id: '',
    inspector_id: '',
    fecha_inicio: '',
    fecha_fin: ''
});

// Manual form Modal
const showManualModal = ref(false);
const savingManual = ref(false);
const manualForm = ref({
    importador_id: '',
    inspector_id: '',
    fecha_programada: new Date().toISOString().substring(0, 10),
    tipo_producto: ''
});

// Edit form Modal
const showEditModal = ref(false);
const savingEdit = ref(false);
const editingSamplingId = ref(null);
const editForm = ref({
    inspector_id: '',
    fecha_programada: ''
});

// Execute Form Modal
const showExecuteModal = ref(false);
const savingExecution = ref(false);
const executingSamplingId = ref(null);
const executeForm = ref({
    observaciones: ''
});
const trackingFiles = ref([]);
const filesPreviews = ref([]);

// Fetch Samplings
const fetchSamplings = async () => {
    loading.value = true;
    try {
        const response = await api.get('/muestreos', { params: filters.value });
        if (response.data?.status === 'success') {
            samplings.value = response.data.data;
        }
    } catch (e) {
        console.error('Error al recuperar muestreos', e);
        Swal.fire('Error', 'No se pudieron recuperar los muestreos', 'error');
    } finally {
        loading.value = false;
    }
};

// Fetch Dropdowns (Importers & Inspectors)
const fetchDropdowns = async () => {
    try {
        // Importers
        const resImp = await api.get('/importadores');
        if (resImp.data?.status === 'success') importers.value = resImp.data.data;

        // Inspectors
        if (auth.role === 'administrador') {
            const resIns = await api.get('/programacion/inspectores');
            if (resIns.data?.status === 'success') inspectors.value = resIns.data.data;
        }
    } catch (e) {
        console.error('Error al recuperar dropdowns', e);
    }
};

// Validate Suggestion (Approve)
const validateSampling = async (id, state) => {
    try {
        const response = await api.put(`/muestreos/${id}/validar`, {
            estado: state
        });
        if (response.data?.status === 'success') {
            Swal.fire('Procesado', `Muestreo marcado como ${state} y notificado al inspector.`, 'success');
            fetchSamplings();
        }
    } catch (e) {
        console.error('Error al validar muestreo', e);
        Swal.fire('Error', 'No se pudo realizar la validación', 'error');
    }
};

// Reject Suggestion (Prompt reason)
const promptReject = (id) => {
    Swal.fire({
        title: 'Rechazar Sugerencia',
        text: 'Por favor, ingrese el motivo del rechazo del muestreo sugerido:',
        input: 'textarea',
        inputPlaceholder: 'Escriba la justificación del rechazo...',
        inputAttributes: {
            'aria-label': 'Escriba la justificación del rechazo'
        },
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Rechazar Muestreo',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return '¡Debe ingresar una justificación!';
            }
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await api.put(`/muestreos/${id}/validar`, {
                    estado: 'Rechazado',
                    motivo_rechazo: result.value
                });
                if (response.data?.status === 'success') {
                    Swal.fire('Rechazado', 'La sugerencia ha sido rechazada.', 'success');
                    fetchSamplings();
                }
            } catch (e) {
                console.error('Error al rechazar muestreo', e);
                Swal.fire('Error', 'No se pudo rechazar la sugerencia', 'error');
            }
        }
    });
};

// Open Manual creation modal
const openManualModal = () => {
    manualForm.value = {
        importador_id: '',
        inspector_id: '',
        fecha_programada: new Date().toISOString().substring(0, 10),
        tipo_producto: ''
    };
    showManualModal.value = true;
};

// Save Manual Directed sampling
const saveManualSampling = async () => {
    savingManual.value = true;
    try {
        const response = await api.post('/muestreos/manual', manualForm.value);
        if (response.data?.status === 'success') {
            Swal.fire('Completado', 'Muestreo dirigido registrado y asignado exitosamente', 'success');
            showManualModal.value = false;
            fetchSamplings();
        }
    } catch (e) {
        console.error('Error al asignar muestreo manual', e);
        const errorMsg = e.response?.data?.error || 'No se pudo asignar el muestreo dirigido';
        Swal.fire('Error', errorMsg, 'error');
    } finally {
        savingManual.value = false;
    }
};

// Open Edit modal
const openEditModal = (item) => {
    editingSamplingId.value = item.id;
    editForm.value = {
        inspector_id: item.inspector_id,
        fecha_programada: item.fecha_programada
    };
    showEditModal.value = true;
};

// Save Edit sampling
const saveEditSampling = async () => {
    savingEdit.value = true;
    try {
        const response = await api.put(`/muestreos/${editingSamplingId.value}`, editForm.value);
        if (response.data?.status === 'success') {
            Swal.fire('Modificado', 'Fecha e inspector modificados correctamente', 'success');
            showEditModal.value = false;
            fetchSamplings();
        }
    } catch (e) {
        console.error('Error al modificar muestreo', e);
        Swal.fire('Error', 'No se pudieron guardar las modificaciones', 'error');
    } finally {
        savingEdit.value = false;
    }
};

// Delete sampling
const deleteSampling = (id) => {
    Swal.fire({
        title: '¿Está seguro de eliminar?',
        text: 'Se removerá el registro de la programación por completo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await api.delete(`/muestreos/${id}`);
                if (response.data?.status === 'success') {
                    Swal.fire('Eliminado', 'Registro de muestreo removido.', 'success');
                    fetchSamplings();
                }
            } catch (e) {
                console.error('Error al eliminar muestreo', e);
                Swal.fire('Error', 'No se pudo eliminar el registro', 'error');
            }
        }
    });
};

// Open execution modal
const openExecuteModal = (id) => {
    executingSamplingId.value = id;
    executeForm.value = { observaciones: '' };
    trackingFiles.value = [];
    filesPreviews.value = [];
    showExecuteModal.value = true;
};

const onFilesSelected = (e) => {
    const files = Array.from(e.target.files);
    files.forEach(f => {
        trackingFiles.value.push(f);
        filesPreviews.value.push({ name: f.name });
    });
};

const removeFile = (idx) => {
    trackingFiles.value.splice(idx, 1);
    filesPreviews.value.splice(idx, 1);
};

// Save Execution (Inspector)
const saveExecution = async () => {
    savingExecution.value = true;
    try {
        const formData = new FormData();
        formData.append('observaciones', executeForm.value.observaciones);
        trackingFiles.value.forEach(file => {
            formData.append('documentos[]', file);
        });

        // La API requiere un PUT multipart que emula POST enviando FormData
        const response = await api.post(`/muestreos/${executingSamplingId.value}/ejecutar`, formData);
        if (response.data?.status === 'success') {
            Swal.fire('Ejecutado', 'Muestreo ejecutado y reportado correctamente.', 'success');
            showExecuteModal.value = false;
            fetchSamplings();
        }
    } catch (e) {
        console.error('Error al registrar ejecución del muestreo', e);
        const errorMsg = e.response?.data?.error || 'No se pudo reportar la ejecución';
        Swal.fire('Error', errorMsg, 'error');
    } finally {
        savingExecution.value = false;
    }
};

onMounted(() => {
    fetchDropdowns();
    fetchSamplings();
});
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
