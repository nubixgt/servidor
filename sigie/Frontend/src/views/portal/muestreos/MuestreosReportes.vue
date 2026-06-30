<template>
    <div class="space-y-8 animate-fade-in text-xs">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Planificación y Cobertura de Muestreos</h1>
                <p class="text-xs text-white/60 mt-1">Configuración de metas anuales, ejecución del algoritmo proporcional y reportes de cobertura.</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    @click="exportToExcel" 
                    :disabled="coverageData.length === 0"
                    class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-md shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 border border-emerald-700 font-headline"
                >
                    <span class="material-symbols-outlined text-sm">download</span>
                    Exportar a Excel
                </button>
            </div>
        </div>

        <!-- Year Selector and Tab Controls -->
        <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium flex flex-wrap items-end justify-between gap-4">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Seleccionar Año de Planificación</label>
                <input 
                    v-model.number="selectedYear" 
                    type="number" 
                    min="2020" 
                    max="2035"
                    class="bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white font-semibold"
                />
            </div>
            
            <div class="flex gap-2 border-b border-white/10 pb-1">
                <button 
                    @click="activeTab = 'cobertura'"
                    :class="['px-4 py-2 font-bold rounded', activeTab === 'cobertura' ? 'bg-slate-900 text-white' : 'text-gray-300 hover:bg-slate-100']"
                >
                    Indicadores de Cobertura
                </button>
                <button 
                    @click="activeTab = 'algoritmo'"
                    :class="['px-4 py-2 font-bold rounded', activeTab === 'algoritmo' ? 'bg-slate-900 text-white' : 'text-gray-300 hover:bg-slate-100']"
                >
                    Algoritmo Proporcional
                </button>
                <button 
                    @click="activeTab = 'config'"
                    :class="['px-4 py-2 font-bold rounded', activeTab === 'config' ? 'bg-slate-900 text-white' : 'text-gray-300 hover:bg-slate-100']"
                >
                    Configurar Metas y Umbrales
                </button>
            </div>
        </div>

        <!-- TAB 1: INDICADORES DE COBERTURA -->
        <div v-if="activeTab === 'cobertura'" class="space-y-8">
            <div v-if="loadingCoverage" class="py-16 text-center text-sm text-slate-400 glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
                <p class="mt-2 font-bold">Cargando indicadores de cobertura...</p>
            </div>

            <div v-else-if="coverageData.length === 0" class="py-16 text-center glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-5xl text-slate-400">analytics</span>
                <p class="text-sm font-semibold text-white mt-4">No hay metas configuradas para el año {{ selectedYear }}</p>
                <p class="text-xs text-slate-400 mt-1">Por favor configure metas anuales en la pestaña respectiva para comenzar.</p>
            </div>

            <div v-else class="space-y-6">
                <!-- KPI Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass-card p-6 rounded-md border border-white/10 shadow-ambient flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Meta de Muestreos Planificada</p>
                            <h3 class="text-3xl font-black text-white mt-1">{{ totalMeta }}</h3>
                        </div>
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center border border-blue-200">
                            <span class="material-symbols-outlined text-xl">flag</span>
                        </div>
                    </div>
                    <div class="glass-card p-6 rounded-md border border-white/10 shadow-ambient flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Muestreos Asignados</p>
                            <h3 class="text-3xl font-black text-amber-600 mt-1">{{ totalAsignados }}</h3>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center border border-amber-200">
                            <span class="material-symbols-outlined text-xl">assignment</span>
                        </div>
                    </div>
                    <div class="glass-card p-6 rounded-md border border-white/10 shadow-ambient flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">Muestreos Ejecutados</p>
                            <h3 class="text-3xl font-black text-emerald-600 mt-1">{{ totalEjecutados }}</h3>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center border border-emerald-200">
                            <span class="material-symbols-outlined text-xl">done_all</span>
                        </div>
                    </div>
                </div>

                <!-- Coverage Table -->
                <div class="glass-card backdrop-blur-sm rounded-2xl border border-white/20 shadow-premium overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-white/10 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                    <th class="px-6 py-4">Tipo de Producto</th>
                                    <th class="px-4 py-4 text-center">Meta Anual</th>
                                    <th class="px-4 py-4 text-center">Sugeridos (Borrador)</th>
                                    <th class="px-4 py-4 text-center">Asignados (Aprobados)</th>
                                    <th class="px-4 py-4 text-center">Rechazados</th>
                                    <th class="px-4 py-4 text-center">Ejecutados Efectivos</th>
                                    <th class="px-6 py-4 w-60">% Cobertura Realizada</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-xs">
                                <tr v-for="item in coverageData" :key="item.id" class="hover:bg-black/20 transition-colors">
                                    <td class="px-6 py-4 font-bold text-white">{{ item.tipo_producto }}</td>
                                    <td class="px-4 py-4 text-center font-mono font-bold text-gray-300">{{ item.meta_muestreo_anual }}</td>
                                    <td class="px-4 py-4 text-center font-mono font-semibold text-amber-600">{{ item.total_sugeridos }}</td>
                                    <td class="px-4 py-4 text-center font-mono font-semibold text-blue-600">{{ item.total_asignados }}</td>
                                    <td class="px-4 py-4 text-center font-mono font-semibold text-red-600">{{ item.total_rechazados }}</td>
                                    <td class="px-4 py-4 text-center font-mono font-black text-emerald-600 bg-emerald-50/10">{{ item.total_ejecutados }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden border border-white/10">
                                                <div 
                                                    class="h-full rounded-full transition-all duration-500 bg-emerald-500"
                                                    :style="{ width: `${getCoberturaPercentage(item)}%` }"
                                                ></div>
                                            </div>
                                            <span class="font-mono font-black text-emerald-700 w-12 text-right">
                                                {{ getCoberturaPercentage(item) }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: ALGORITMO PROPORCIONAL -->
        <div v-if="activeTab === 'algoritmo'" class="space-y-6">
            <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Producto a Sugerir</label>
                    <select 
                        v-model="algoForm.tipo_producto" 
                        @change="onAlgoProductChange"
                        class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                    >
                        <option value="">Seleccione el tipo de producto</option>
                        <option v-for="cfg in configs" :key="cfg.id" :value="cfg.tipo_producto">{{ cfg.tipo_producto }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Meta Anual Definida</label>
                    <input 
                        v-model.number="algoForm.meta_muestreo_anual" 
                        type="number"
                        readonly
                        placeholder="Configure una meta primero"
                        class="w-full bg-slate-100 border border-slate-300 rounded px-3 py-2 text-xs outline-none text-gray-300 font-bold"
                    />
                </div>
                <button 
                    @click="previewSuggestions"
                    :disabled="!algoForm.tipo_producto || !algoForm.meta_muestreo_anual"
                    class="w-full py-2.5 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded shadow-lg transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-sm">rotate_left</span> Calcular Muestreo Proporcional
                </button>
            </div>

            <!-- Suggestion draft preview -->
            <div v-if="loadingAlgo" class="py-16 text-center text-sm text-slate-400 glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
                <p class="mt-2 font-bold">Analizando importaciones del año anterior ({{ selectedYear - 1 }})...</p>
            </div>

            <div v-else-if="showPreview && suggestions.length === 0" class="py-16 text-center glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-5xl text-slate-400">warning</span>
                <p class="text-sm font-semibold text-white mt-4">No hay importaciones registradas en el año {{ selectedYear - 1 }} para este producto</p>
                <p class="text-xs text-slate-400 mt-1">El algoritmo requiere historial del año anterior para distribuir proporcionalmente.</p>
            </div>

            <div v-else-if="showPreview" class="space-y-6">
                <div class="glass-card backdrop-blur-sm rounded-2xl border border-white/20 shadow-premium overflow-hidden animate-fade-in">
                    <div class="p-4 bg-black/20 border-b border-white/10 flex justify-between items-center">
                        <h4 class="font-bold text-white text-xs">Propuesta de Distribución Proporcional de Muestras</h4>
                        <span class="text-[10px] font-bold text-gray-300 uppercase">Año de referencia del volumen: {{ selectedYear - 1 }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100/50 border-b border-white/10 text-[10px] font-extrabold uppercase text-gray-300 tracking-wider">
                                    <th class="px-6 py-4">Importador</th>
                                    <th class="px-6 py-4 text-right">Volumen Importado ({{ selectedYear - 1 }})</th>
                                    <th class="px-6 py-4 text-center">% del Volumen Total (Top 10)</th>
                                    <th class="px-6 py-4 text-center w-48">Muestras Asignadas Proporcionalmente</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-xs">
                                <tr v-for="sug in suggestions" :key="sug.importador_id" class="hover:bg-black/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-white">{{ sug.importador_nombre }}</p>
                                        <p class="text-[9px] text-slate-400 font-mono">NIT: {{ sug.importador_nit }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-gray-300">{{ formatVolume(sug.volumen_total) }} kg</td>
                                    <td class="px-6 py-4 text-center font-mono font-semibold text-gray-300">{{ sug.volumen_porcentaje }}%</td>
                                    <td class="px-6 py-4 text-center bg-blue-50/10">
                                        <input 
                                            v-model.number="sug.muestras_sugeridas" 
                                            type="number" 
                                            min="0"
                                            class="glass-card border border-slate-300 rounded px-3 py-1.5 text-xs text-center w-24 focus:border-blue-600 outline-none transition-all font-mono font-black"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-6 bg-black/20 border-t border-white/10 flex justify-between items-center">
                        <div class="text-xs font-semibold text-gray-300">
                            Total Planificado Sugerido: <span class="font-black text-white text-sm">{{ totalSugerenciasCalculadas }}</span> de {{ algoForm.meta_muestreo_anual }} metas.
                        </div>
                        <button 
                            @click="saveSuggestions"
                            :disabled="savingAlgo"
                            class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded shadow-lg flex items-center justify-center gap-1.5 border border-emerald-700"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="savingAlgo">sync</span>
                            <span>Guardar Sugerencias en Borrador</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: CONFIGURAR METAS Y UMBRALES -->
        <div v-if="activeTab === 'config'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Settings Form -->
            <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium h-fit">
                <h3 class="font-bold text-white text-sm border-b pb-2 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-white text-sm">settings</span>
                    Definir Metas y Alarmas
                </h3>
                <form @submit.prevent="saveConfiguration" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Tipo de Producto *</label>
                        <input 
                            v-model="configForm.tipo_producto" 
                            type="text" 
                            required 
                            placeholder="Ej. Cárnico de ave, Lácteos"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2.5 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Meta Anual de Muestras (Cantidad) *</label>
                        <input 
                            v-model.number="configForm.meta_muestreo_anual" 
                            type="number" 
                            min="1" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2.5 focus:border-blue-600 focus:glass-card outline-none transition-all text-white font-mono"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Umbral de Alarma por Volumen (Kilos) *</label>
                        <input 
                            v-model.number="configForm.umbral_volumen" 
                            type="number" 
                            step="0.01"
                            min="1" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2.5 focus:border-blue-600 focus:glass-card outline-none transition-all text-white font-mono"
                        />
                        <span class="text-[9px] text-slate-400 block mt-1">Al acumular esta cantidad de kilos por importador, se generará una alerta de muestreo automática.</span>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Inspector por Defecto para Alertas *</label>
                        <select 
                            v-model="configForm.inspector_id" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2.5 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        >
                            <option value="">Seleccione el inspector</option>
                            <option v-for="ins in inspectors" :key="ins.id" :value="ins.id">{{ ins.nombre }}</option>
                        </select>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="savingConfig"
                        class="w-full py-3 bg-[#0a192f] hover:bg-[#122347] text-white font-bold rounded border border-slate-800 shadow-lg flex items-center justify-center gap-1.5"
                    >
                        <span class="material-symbols-outlined text-sm animate-spin" v-if="savingConfig">sync</span>
                        <span>Guardar Planificación</span>
                    </button>
                </form>
            </div>

            <!-- Config List -->
            <div class="lg:col-span-2 glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                <h3 class="font-bold text-white text-sm border-b pb-2 mb-4">Planificaciones Configuradas ({{ selectedYear }})</h3>
                
                <div v-if="configs.length === 0" class="py-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-3xl">info</span>
                    <p class="mt-2">No hay planificaciones creadas para este año.</p>
                </div>
                
                <div v-else class="space-y-4">
                    <div 
                        v-for="cfg in configs" 
                        :key="cfg.id" 
                        class="p-4 border border-white/10 rounded-md bg-black/20/50 hover:bg-black/20 transition-colors flex justify-between items-start"
                    >
                        <div class="space-y-1">
                            <h4 class="font-bold text-white text-sm">{{ cfg.tipo_producto }}</h4>
                            <p class="text-gray-300 text-[10px] font-semibold">Meta de muestreo: <span class="font-black text-gray-300 font-mono">{{ cfg.meta_muestreo_anual }} muestras</span></p>
                            <p class="text-gray-300 text-[10px] font-semibold">Umbral de alarma: <span class="font-black text-gray-300 font-mono">{{ formatVolume(cfg.umbral_volumen) }} kg</span></p>
                            <p class="text-slate-400 text-[9px] font-mono">Inspector asignado a alarmas: {{ cfg.inspector_nombre }}</p>
                        </div>
                        <button 
                            @click="loadConfigToEdit(cfg)"
                            class="px-2.5 py-1 glass-card hover:bg-slate-100 text-gray-300 border border-white/10 rounded font-bold text-[9px] uppercase"
                        >
                            Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

// Tab State
const activeTab = ref('cobertura');
const selectedYear = ref(new Date().getFullYear());

const loadingCoverage = ref(true);
const loadingAlgo = ref(false);
const savingAlgo = ref(false);
const savingConfig = ref(false);

const coverageData = ref([]);
const configs = ref([]);
const inspectors = ref([]);
const suggestions = ref([]);
const showPreview = ref(false);

// Forms States
const configForm = ref({
    tipo_producto: '',
    meta_muestreo_anual: 25,
    umbral_volumen: 66000.00,
    inspector_id: ''
});

const algoForm = ref({
    tipo_producto: '',
    meta_muestreo_anual: null,
    default_inspector_id: null
});

// Watch selectedYear
watch(selectedYear, () => {
    fetchCoverage();
    fetchConfigs();
});

// Computed KPI Metrics
const totalMeta = computed(() => {
    return coverageData.value.reduce((sum, item) => sum + parseInt(item.meta_muestreo_anual), 0);
});
const totalAsignados = computed(() => {
    return coverageData.value.reduce((sum, item) => sum + parseInt(item.total_asignados), 0);
});
const totalEjecutados = computed(() => {
    return coverageData.value.reduce((sum, item) => sum + parseInt(item.total_ejecutados), 0);
});
const totalSugerenciasCalculadas = computed(() => {
    return suggestions.value.reduce((sum, item) => sum + parseInt(item.muestras_sugeridas || 0), 0);
});

// Helper for coverage percentage
const getCoberturaPercentage = (item) => {
    const meta = parseInt(item.meta_muestreo_anual);
    if (meta === 0) return 0;
    const rate = (parseInt(item.total_ejecutados) / meta) * 100;
    return Math.round(rate * 10) / 10;
};

// Fetch coverage indicators
const fetchCoverage = async () => {
    loadingCoverage.value = true;
    try {
        const response = await api.get('/muestreos/reportes/cobertura', {
            params: { anio: selectedYear.value }
        });
        if (response.data?.status === 'success') {
            coverageData.value = response.data.data;
        }
    } catch (e) {
        console.error('Error al recuperar indicadores de cobertura', e);
    } finally {
        loadingCoverage.value = false;
    }
};

// Fetch configurations
const fetchConfigs = async () => {
    try {
        const response = await api.get('/muestreos/config', {
            params: { anio: selectedYear.value }
        });
        if (response.data?.status === 'success') {
            configs.value = response.data.data;
        }
    } catch (e) {
        console.error('Error al recuperar configuraciones', e);
    }
};

// Fetch inspectors
const fetchInspectors = async () => {
    try {
        const response = await api.get('/programacion/inspectores');
        if (response.data?.status === 'success') {
            inspectors.value = response.data.data;
        }
    } catch (e) {
        console.error('Error al recuperar inspectores', e);
    }
};

// Config Form Change
const loadConfigToEdit = (cfg) => {
    configForm.value = {
        tipo_producto: cfg.tipo_producto,
        meta_muestreo_anual: parseInt(cfg.meta_muestreo_anual),
        umbral_volumen: parseFloat(cfg.umbral_volumen),
        inspector_id: cfg.inspector_id
    };
    window.scrollTo({ top: 300, behavior: 'smooth' });
};

// Save metas and thresholds configuration
const saveConfiguration = async () => {
    savingConfig.value = true;
    try {
        const response = await api.post('/muestreos/config', {
            ...configForm.value,
            anio: selectedYear.value
        });
        if (response.data?.status === 'success') {
            Swal.fire('Guardado', 'Planificación anual guardada exitosamente.', 'success');
            configForm.value = { tipo_producto: '', meta_muestreo_anual: 25, umbral_volumen: 66000.00, inspector_id: '' };
            fetchConfigs();
            fetchCoverage();
        }
    } catch (e) {
        console.error('Error al guardar configuración', e);
        Swal.fire('Error', 'No se pudo guardar la configuración', 'error');
    } finally {
        savingConfig.value = false;
    }
};

// Algo Form updates on product selection
const onAlgoProductChange = () => {
    const selected = configs.value.find(c => c.tipo_producto === algoForm.value.tipo_producto);
    if (selected) {
        algoForm.value.meta_muestreo_anual = parseInt(selected.meta_muestreo_anual);
        algoForm.value.default_inspector_id = selected.inspector_id;
    } else {
        algoForm.value.meta_muestreo_anual = null;
        algoForm.value.default_inspector_id = null;
    }
    showPreview.value = false;
    suggestions.value = [];
};

// Preview suggestion proportionally
const previewSuggestions = async () => {
    loadingAlgo.value = true;
    showPreview.value = false;
    try {
        const response = await api.get('/muestreos/sugerir/previsualizar', {
            params: {
                anio: selectedYear.value,
                tipo_producto: algoForm.value.tipo_producto,
                meta_muestreo_anual: algoForm.value.meta_muestreo_anual
            }
        });
        if (response.data?.status === 'success') {
            suggestions.value = response.data.data;
            showPreview.value = true;
        }
    } catch (e) {
        console.error('Error al obtener previsualización', e);
        Swal.fire('Error', 'No se pudo realizar el cálculo de previsualización', 'error');
    } finally {
        loadingAlgo.value = false;
    }
};

// Save draft suggestions to bulk
const saveSuggestions = async () => {
    savingAlgo.value = true;
    try {
        const response = await api.post('/muestreos/sugerir', {
            anio: selectedYear.value,
            tipo_producto: algoForm.value.tipo_producto,
            meta_muestreo_anual: algoForm.value.meta_muestreo_anual,
            default_inspector_id: algoForm.value.default_inspector_id,
            sugerencias: suggestions.value
        });
        if (response.data?.status === 'success') {
            Swal.fire('Sugerido', 'Sugerencias guardadas exitosamente como borrador en la lista general.', 'success');
            suggestions.value = [];
            showPreview.value = false;
            algoForm.value = { tipo_producto: '', meta_muestreo_anual: null, default_inspector_id: null };
            fetchCoverage();
        }
    } catch (e) {
        console.error('Error al guardar sugerencias', e);
        Swal.fire('Error', 'No se pudieron guardar las sugerencias', 'error');
    } finally {
        savingAlgo.value = false;
    }
};

// Export to Excel with SheetJS (2 sheets)
const exportToExcel = async () => {
    try {
        // Fetch all samplings of the selected year for detailed sheets
        const resDetail = await api.get('/muestreos', {
            params: { fecha_inicio: `${selectedYear.value}-01-01`, fecha_fin: `${selectedYear.value}-12-31` }
        });
        const details = resDetail.data?.status === 'success' ? resDetail.data.data : [];

        // Sheet 1: Coverage Summary
        const summaryData = coverageData.value.map(c => ({
            'Tipo de Producto': c.tipo_producto,
            'Meta Anual (Muestras)': parseInt(c.meta_muestreo_anual),
            'Sugeridos (Borrador)': parseInt(c.total_sugeridos),
            'Asignados (Aprobados)': parseInt(c.total_asignados),
            'Rechazados': parseInt(c.total_rechazados),
            'Ejecutados': parseInt(c.total_ejecutados),
            '% Cobertura': `${getCoberturaPercentage(c)}%`
        }));

        // Sheet 2: Samplings Detail
        const detailsData = details.map(d => ({
            'ID Muestreo': d.id,
            'Origen': d.origen,
            'Importador': d.importador_nombre,
            'NIT Importador': d.importador_nit,
            'Inspector Asignado': d.inspector_nombre,
            'Código Inspector': d.inspector_codigo,
            'Producto': d.tipo_producto,
            'Fecha Programada': d.fecha_programada,
            'Estado': d.estado,
            'Observaciones Ejecución': d.observaciones_ejecucion || '',
            'Fecha de Ejecución': d.fecha_ejecucion || ''
        }));

        const wb = XLSX.utils.book_new();

        // Sheet 1 append
        const wsSummary = XLSX.utils.json_to_sheet(summaryData);
        wsSummary['!cols'] = [{ wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 15 }, { wch: 15 }, { wch: 20 }];
        XLSX.utils.book_append_sheet(wb, wsSummary, 'Resumen Cobertura');

        // Sheet 2 append
        const wsDetails = XLSX.utils.json_to_sheet(detailsData);
        wsDetails['!cols'] = [
            { wch: 12 }, { wch: 12 }, { wch: 25 }, { wch: 15 }, { wch: 22 }, 
            { wch: 16 }, { wch: 20 }, { wch: 16 }, { wch: 12 }, { wch: 35 }, { wch: 20 }
        ];
        XLSX.utils.book_append_sheet(wb, wsDetails, 'Detalle de Muestreos');

        XLSX.writeFile(wb, `Reporte_Cobertura_Muestreos_${selectedYear.value}.xlsx`);
    } catch (e) {
        console.error('Error al exportar reporte', e);
        Swal.fire('Error', 'No se pudieron exportar los datos de reporte a Excel', 'error');
    }
};

// Format helpers
const formatVolume = (val) => {
    if (!val) return '0.00';
    return parseFloat(val).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
    fetchInspectors();
    fetchConfigs();
    fetchCoverage();
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
