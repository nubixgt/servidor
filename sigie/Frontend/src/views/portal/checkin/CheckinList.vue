<template>
    <div class="animate-fade-in">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 font-headline">Historial de Check-ins</h1>
                <p class="text-xs text-slate-500 mt-1">Consulta y descarga los registros de geolocalización de inspectores en campo.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <router-link
                    v-if="auth.role === 'inspector'"
                    to="/checkin"
                    class="px-4 py-2.5 bg-[#0a192f] hover:bg-[#0f224b] text-white font-extrabold text-xs rounded-xl shadow transition-colors flex items-center gap-2 border border-slate-900"
                >
                    <span class="material-symbols-outlined text-sm">add</span>
                    Nuevo Check-in
                </router-link>
            </div>
        </div>

        <!-- KPI Cards Grid with line charts -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card 1: Check-ins Hoy -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Check-ins Hoy</span>
                        <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ totalCheckinsCount }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-blue-50 border border-blue-100 rounded-xl flex items-center justify-center text-blue-600">
                        <span class="material-symbols-outlined text-lg">calendar_month</span>
                    </div>
                </div>
                <div class="mt-4">
                    <!-- SVG Wave Sparkline -->
                    <div class="h-8 w-full flex items-center">
                        <svg class="h-6 w-full text-blue-600" viewBox="0 0 200 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M 0,25 C 30,15 60,35 90,20 C 120,5 150,30 180,15 L 200,22" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5 mt-2">
                        <span class="material-symbols-outlined text-[10px] font-black">trending_up</span>
                        +12% <span class="text-[9px] text-slate-400 font-normal ml-0.5">vs. ayer</span>
                    </span>
                </div>
            </div>

            <!-- Card 2: Sin Novedades -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Sin Novedades</span>
                        <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ sinNovedadesCount }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-center text-emerald-600">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                    </div>
                </div>
                <div class="mt-4">
                    <!-- SVG Wave Sparkline Green -->
                    <div class="h-8 w-full flex items-center">
                        <svg class="h-6 w-full text-emerald-500" viewBox="0 0 200 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M 0,30 C 40,25 80,35 120,20 C 160,5 180,15 200,8" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5 mt-2">
                        <span class="material-symbols-outlined text-[10px] font-black">check</span>
                        90.1% <span class="text-[9px] text-slate-400 font-normal ml-0.5">de efectividad</span>
                    </span>
                </div>
            </div>

            <!-- Card 3: Alertas Críticas -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Alertas Críticas</span>
                        <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ conNovedadesCount }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-red-50 border border-red-100 rounded-xl flex items-center justify-center text-red-600">
                        <span class="material-symbols-outlined text-lg">error</span>
                    </div>
                </div>
                <div class="mt-4">
                    <!-- SVG Wave Sparkline Red -->
                    <div class="h-8 w-full flex items-center">
                        <svg class="h-6 w-full text-red-500" viewBox="0 0 200 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M 0,20 C 40,25 80,15 120,30 C 160,40 180,25 200,35" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-red-600 flex items-center gap-0.5 mt-2">
                        <span class="material-symbols-outlined text-[10px] font-black font-extrabold">warning</span>
                        Acción Requerida
                    </span>
                </div>
            </div>

            <!-- Card 4: Tiempo Promedio -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Tiempo Promedio</span>
                        <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">8.4m</h3>
                    </div>
                    <div class="w-9 h-9 bg-sky-50 border border-sky-100 rounded-xl flex items-center justify-center text-sky-600">
                        <span class="material-symbols-outlined text-lg">schedule</span>
                    </div>
                </div>
                <div class="mt-4">
                    <!-- SVG Wave Sparkline Indigo -->
                    <div class="h-8 w-full flex items-center">
                        <svg class="h-6 w-full text-sky-500" viewBox="0 0 200 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M 0,25 C 30,28 60,18 90,22 C 120,26 150,15 180,24 L 200,20" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-sky-600 flex items-center gap-0.5 mt-2">
                        Consistencia operativa estable
                    </span>
                </div>
            </div>
        </div>

        <!-- Filters and Actions Container -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium mb-8">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                <!-- Search inputs aligned in references -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                    <div class="relative w-full sm:w-72">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                        <input 
                            v-model="filterInspector" 
                            type="text" 
                            placeholder="Buscar por Inspector o Lote..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-semibold focus:border-blue-600 focus:bg-white outline-none transition-all text-slate-800"
                        />
                    </div>
                    <button 
                        @click="toggleAdvancedFilters = !toggleAdvancedFilters"
                        class="px-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl border border-slate-200 flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined text-base">tune</span>
                        Filtros Avanzados
                    </button>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button 
                        @click="exportToExcel" 
                        :disabled="checkins.length === 0"
                        class="px-4 py-2.5 bg-white hover:bg-slate-50 text-emerald-600 hover:text-emerald-700 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-2 border border-emerald-200 disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto"
                    >
                        <span class="material-symbols-outlined text-base">download_for_offline</span>
                        Exportar a Excel
                    </button>
                </div>
            </div>

            <!-- Expandable Advanced Filters -->
            <div v-if="toggleAdvancedFilters" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5 pt-5 border-t border-slate-100">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Establecimiento</label>
                    <input 
                        v-model="filterEstablecimiento" 
                        type="text" 
                        placeholder="Establecimiento..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold focus:border-blue-600 focus:bg-white outline-none transition-all text-slate-800"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Estado</label>
                    <select 
                        v-model="filterEstado"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold focus:border-blue-600 focus:bg-white outline-none transition-all text-slate-800"
                    >
                        <option value="todos">Todos los Estados</option>
                        <option value="exitoso">Sin Novedades (Exitoso)</option>
                        <option value="con_novedades">Con Novedades / Alertas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Fecha</label>
                    <input 
                        v-model="filterFecha" 
                        type="date" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold focus:border-blue-600 focus:bg-white outline-none transition-all text-slate-800"
                    />
                </div>
            </div>
        </div>

        <!-- Check-ins Table Container -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-premium overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-xs text-slate-400">
                <span class="material-symbols-outlined text-3xl animate-spin text-blue-600 mb-1 block">sync</span>
                <p class="font-bold">Cargando historial de registros...</p>
            </div>

            <div v-else-if="filteredCheckins.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-300">assignment_turned_in</span>
                <p class="text-sm font-bold text-slate-800 mt-4">No se encontraron registros de check-in</p>
                <p class="text-xs text-slate-400 mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                                <th class="px-6 py-4">Fecha / Hora</th>
                                <th class="px-6 py-4">Inspector</th>
                                <th class="px-6 py-4">Lote No.</th>
                                <th class="px-6 py-4">Establecimiento</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                                <th class="px-6 py-4 text-center no-print">Evidencias</th>
                                <th class="px-6 py-4 text-center no-print">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-700">
                            <tr v-for="c in paginatedCheckins" :key="c.id" class="hover:bg-slate-50/40 transition-colors">
                                <!-- Date Time -->
                                <td class="px-6 py-4 text-slate-900 font-extrabold whitespace-nowrap">
                                    {{ formatDateTimeCustom(c.fecha_hora) }}
                                </td>

                                <!-- Inspector details -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-100 text-blue-700 flex items-center justify-center font-extrabold text-[10px]">
                                            {{ getInitials(c.inspector_nombre) }}
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-slate-800 leading-tight">{{ c.inspector_nombre }}</p>
                                            <p class="text-[9px] text-slate-400 mt-0.5">ID: {{ c.inspector_codigo }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Lote No -->
                                <td class="px-6 py-4 font-mono text-slate-500 font-bold whitespace-nowrap">
                                    LT-2026-X{{ c.id }}
                                </td>

                                <!-- Visit details -->
                                <td class="px-6 py-4">
                                    <p class="font-extrabold text-slate-900 leading-tight">{{ c.establecimiento || 'Visita Libre' }}</p>
                                    <p class="text-[9px] text-slate-400 mt-0.5 flex items-center gap-1 font-medium">
                                        <span class="material-symbols-outlined text-[10px] text-slate-400">place</span>
                                        {{ c.direccion || 'Ubicación registrada en mapa' }}
                                    </p>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        :class="['inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black border', 
                                                 c.estado === 'exitoso' 
                                                    ? 'bg-emerald-50 border-emerald-100 text-emerald-700' 
                                                    : 'bg-red-50 border-red-100 text-red-700']"
                                    >
                                        <span :class="['w-1 h-1 rounded-full', c.estado === 'exitoso' ? 'bg-emerald-500' : 'bg-red-500']"></span>
                                        {{ c.estado === 'exitoso' ? 'Sin novedades' : 'Alerta' }}
                                    </span>
                                </td>

                                <!-- Photo and Signature previews -->
                                <td class="px-6 py-4 text-center no-print">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Photo button -->
                                        <button 
                                            v-if="c.foto_path" 
                                            @click="verImagen(resolverRuta(c.foto_path), 'Evidencia Fotográfica')"
                                            class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 hover:border-blue-600 transition-colors inline-flex items-center justify-center bg-slate-50 shadow-sm"
                                            title="Ver Foto"
                                        >
                                            <img :src="resolverRuta(c.foto_path)" class="w-full h-full object-cover" />
                                        </button>
                                        
                                        <!-- Signature button -->
                                        <button 
                                            v-if="c.firma_path" 
                                            @click="verImagen(resolverRuta(c.firma_path), 'Firma del Inspector')"
                                            class="w-9 h-7 rounded-lg border border-slate-200 hover:border-blue-600 hover:scale-105 transition-all inline-flex items-center justify-center bg-slate-50 p-0.5 shadow-sm"
                                            title="Ver Firma"
                                        >
                                            <img :src="resolverRuta(c.firma_path)" class="max-h-full max-w-full object-contain" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Detailed Actions -->
                                <td class="px-6 py-4 text-center no-print">
                                    <button 
                                        @click="verDetalles(c)"
                                        class="text-blue-600 hover:text-blue-700 font-extrabold text-xs transition-colors inline-flex items-center gap-1"
                                    >
                                        <span class="material-symbols-outlined text-sm font-black">visibility</span>
                                        Detalles
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination and Online Badge -->
                <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 font-headline">
                    <!-- Text info -->
                    <span class="text-xs font-bold text-slate-500">
                        Mostrando <span class="text-slate-800">{{ rangeStart }} - {{ rangeEnd }}</span> de <span class="text-slate-800">{{ filteredCheckins.length }}</span> check-ins
                    </span>

                    <!-- Pagination buttons -->
                    <div class="flex items-center gap-1.5">
                        <button 
                            @click="prevPage" 
                            :disabled="currentPage === 1"
                            class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed text-slate-600"
                        >
                            <span class="material-symbols-outlined text-lg font-black">chevron_left</span>
                        </button>
                        
                        <button 
                            v-for="p in totalPages" 
                            :key="p" 
                            @click="setPage(p)"
                            :class="['w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all', 
                                     currentPage === p 
                                        ? 'bg-[#0a192f] text-white shadow-sm' 
                                        : 'border border-slate-200 text-slate-600 hover:bg-slate-50']"
                        >
                            {{ p }}
                        </button>
                        
                        <button 
                            @click="nextPage" 
                            :disabled="currentPage === totalPages"
                            class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed text-slate-600"
                        >
                            <span class="material-symbols-outlined text-lg font-black">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network status footer banner -->
        <div class="mt-6 flex justify-end">
            <div class="inline-flex items-center gap-4 bg-white px-5 py-2.5 rounded-full border border-slate-100 shadow-premium">
                <span class="inline-flex items-center gap-1.5 text-[10px] font-black text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100 uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                    Sistema en Línea
                </span>
                <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">sync</span>
                    Última sincronización: hace 2 min
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api, { getBackendBaseUrl } from '../../../services/api.js';
import { useAuthStore } from '../../../stores/authStore.js';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

const auth = useAuthStore();

const checkins = ref([]);
const loading = ref(false);
const toggleAdvancedFilters = ref(false);

// Filter fields
const filterInspector = ref('');
const filterEstablecimiento = ref('');
const filterEstado = ref('todos');
const filterFecha = ref('');

// Client-side pagination
const currentPage = ref(1);
const itemsPerPage = 10;

const resolverRuta = (rutaRelativa) => {
    return `${getBackendBaseUrl()}/${rutaRelativa}`;
};

const fetchHistorial = async () => {
    loading.value = true;
    try {
        const response = await api.get('/checkin/historial');
        if (response.data?.status === 'success') {
            checkins.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar historial', error);
        Swal.fire('Error', 'No se pudieron recuperar los registros del servidor.', 'error');
    } finally {
        loading.value = false;
    }
};

// Client-side filtering logic
const filteredCheckins = computed(() => {
    return checkins.value.filter(c => {
        const inspectorMatch = !filterInspector.value || 
            c.inspector_nombre.toLowerCase().includes(filterInspector.value.toLowerCase()) ||
            c.inspector_codigo.toLowerCase().includes(filterInspector.value.toLowerCase());
            
        const establecimientoMatch = !filterEstablecimiento.value || 
            (c.establecimiento && c.establecimiento.toLowerCase().includes(filterEstablecimiento.value.toLowerCase()));
            
        const estadoMatch = filterEstado.value === 'todos' || c.estado === filterEstado.value;
        
        const fechaMatch = !filterFecha.value || c.fecha_hora.startsWith(filterFecha.value);

        return inspectorMatch && establecimientoMatch && estadoMatch && fechaMatch;
    });
});

// Reset page on filter changes
watch([filterInspector, filterEstablecimiento, filterEstado, filterFecha], () => {
    currentPage.value = 1;
});

// Pagination details
const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredCheckins.value.length / itemsPerPage));
});

const paginatedCheckins = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredCheckins.value.slice(start, start + itemsPerPage);
});

const rangeStart = computed(() => {
    return filteredCheckins.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage + 1;
});

const rangeEnd = computed(() => {
    return Math.min(filteredCheckins.value.length, currentPage.value * itemsPerPage);
});

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++;
};

const setPage = (page) => {
    currentPage.value = page;
};

// Calculated KPIs
const totalCheckinsCount = computed(() => filteredCheckins.value.length);
const sinNovedadesCount = computed(() => filteredCheckins.value.filter(c => c.estado === 'exitoso').length);
const conNovedadesCount = computed(() => filteredCheckins.value.filter(c => c.estado !== 'exitoso').length);

const getInitials = (name) => {
    if (!name) return 'US';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const getFormattedDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatDateTimeCustom = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    // Format "2024-05-14 08:45:00" to "14 May 2024 - 08:45 AM"
    const parts = dateTimeStr.split(' ');
    const dateParts = parts[0].split('-');
    if (dateParts.length < 3) return dateTimeStr;
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    let timeStr = '';
    if (parts.length >= 2) {
        const timeParts = parts[1].split(':');
        const hour = parseInt(timeParts[0]);
        const min = timeParts[1];
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12;
        timeStr = ` - ${String(formattedHour).padStart(2, '0')}:${min} ${ampm}`;
    }
    
    return `${dateParts[2]} ${months[parseInt(dateParts[1]) - 1]} ${dateParts[0]}${timeStr}`;
};

// Photo / Signature modal lightbox
const verImagen = (url, titulo) => {
    Swal.fire({
        title: titulo,
        imageUrl: url,
        imageAlt: titulo,
        confirmButtonColor: '#0a192f',
        confirmButtonText: 'Cerrar Vista'
    });
};

// Details Modal
const verDetalles = (c) => {
    let htmlContent = `
        <div class="text-left text-xs space-y-4">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 font-headline">
                <p><strong>Inspector:</strong> ${c.inspector_nombre} (${c.inspector_codigo})</p>
                <p><strong>Área:</strong> ${c.inspector_area}</p>
                <p><strong>Establecimiento:</strong> ${c.establecimiento || 'N/A'}</p>
                <p><strong>Dirección:</strong> ${c.direccion || 'N/A'}</p>
                <p><strong>Tipo Inspección:</strong> ${c.tipo_inspeccion || 'N/A'}</p>
                <p><strong>Fecha y Hora:</strong> ${formatDateTimeCustom(c.fecha_hora)}</p>
                <p><strong>GPS Coordenadas:</strong> Latitud ${c.latitud}, Longitud ${c.longitud}</p>
                <p><strong>Estado:</strong> ${c.estado === 'exitoso' ? '<span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">Sin novedades</span>' : '<span class="text-red-700 font-bold bg-red-50 px-2 py-0.5 rounded border border-red-100">Con novedades</span>'}</p>
                <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200 font-mono text-[11px]">
                    <p><strong>Hora Ingreso:</strong> <span class="font-bold text-blue-600">${c.hora_ingreso || 'N/A'}</span></p>
                    <p><strong>Hora Salida:</strong> <span class="font-bold text-amber-600">${c.hora_salida || 'N/A'}</span></p>
                </div>
            </div>
            <div>
                <h4 class="font-black text-slate-800 mb-2 uppercase tracking-widest text-[9px]">Observaciones del Inspector:</h4>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white font-sans text-slate-600 text-[11px] leading-relaxed max-h-40 overflow-y-auto italic">
                    ${c.observaciones ? c.observaciones : 'Ninguna observación registrada.'}
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                ${c.foto_path ? `
                <div class="text-center">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Evidencia Foto</span>
                    <img src="${resolverRuta(c.foto_path)}" class="w-full aspect-[4/3] object-cover rounded-xl border border-slate-200 shadow-sm" />
                </div>
                ` : ''}
                ${c.firma_path ? `
                <div class="text-center">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Firma Inspector</span>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 h-full flex items-center justify-center shadow-sm">
                        <img src="${resolverRuta(c.firma_path)}" class="max-h-24 max-w-full object-contain" />
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `;

    Swal.fire({
        title: 'Detalles del Check-in',
        html: htmlContent,
        width: '600px',
        confirmButtonColor: '#0a192f',
        confirmButtonText: 'Cerrar'
    });
};

// SheetJS Excel Exporter
const exportToExcel = () => {
    const dataToExport = filteredCheckins.value.map(c => ({
        'Inspector': c.inspector_nombre,
        'Código Inspector': c.inspector_codigo,
        'Área Inspector': c.inspector_area,
        'Establecimiento': c.establecimiento || 'N/A',
        'Dirección': c.direccion || 'N/A',
        'Tipo Inspección': c.tipo_inspeccion || 'N/A',
        'Fecha y Hora Check-in': c.fecha_hora,
        'Hora de Ingreso': c.hora_ingreso || 'N/A',
        'Hora de Salida': c.hora_salida || 'N/A',
        'Latitud': c.latitud,
        'Longitud': c.longitud,
        'Estado': c.estado === 'exitoso' ? 'Exitoso' : 'Con Novedades',
        'Observaciones': c.observaciones || ''
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Registros Check-in');
    
    // Set columns widths automatically
    const max_width = dataToExport.reduce((w, r) => Math.max(w, r.Inspector.length), 10);
    worksheet['!cols'] = [ { wch: max_width + 4 } ];

    XLSX.writeFile(workbook, 'Reporte_Checkins_SIGIE.xlsx');
};

onMounted(() => {
    fetchHistorial();
});
</script>
