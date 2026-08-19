<template>
    <div class="space-y-5 max-w-[1600px] mx-auto pb-4">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Todas las parcelas</h2>
                <p class="text-xs sm:text-sm text-white/60 mt-0.5">Gestión centralizada de polígonos, curvas a nivel y beneficiarios.</p>
            </div>
            <router-link :to="{ name: 'ParcelaNueva' }" class="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold transition-all shadow-md">
                <Plus class="w-4 h-4" />
                <span>Registrar parcela</span>
            </router-link>
        </div>

        <!-- Filter and search bar -->
        <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            <div class="flex-1 relative group">
                <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-white transition-colors" />
                <input
                    v-model="filters.q"
                    @input="debouncedLoad"
                    placeholder="Buscar por código, nombre, productor, municipio..."
                    class="w-full bg-white/5 border border-white/15 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/50 transition-all placeholder:text-white/40"
                />
            </div>

            <div class="flex flex-wrap gap-2.5 items-center">
                <div class="flex items-center gap-2 bg-white/5 border border-white/15 rounded-xl px-3 py-1.5">
                    <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider">Depto:</span>
                    <select v-model="filters.departamento" @change="load" class="bg-transparent text-xs text-white focus:outline-none cursor-pointer">
                        <option value="">Todos</option>
                        <option v-for="d in DEPARTAMENTOS" :key="d" :value="d" class="bg-white/10 text-white">{{ d }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 bg-white/5 border border-white/15 rounded-xl px-3 py-1.5">
                    <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider">Estado:</span>
                    <select v-model="filters.estado" @change="load" class="bg-transparent text-xs text-white focus:outline-none cursor-pointer">
                        <option value="">Cualquiera</option>
                        <option v-for="e in ESTADOS_PROCESO" :key="e" :value="e" class="bg-white/10 text-white">{{ e }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 bg-white/5 border border-white/15 rounded-xl px-3 py-1.5">
                    <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider">Validación:</span>
                    <select v-model="filters.estadoValidacion" @change="load" class="bg-transparent text-xs text-white focus:outline-none cursor-pointer">
                        <option value="">Cualquiera</option>
                        <option v-for="e in ESTADOS_VALIDACION" :key="e" :value="e" class="bg-white/10 text-white">{{ e }}</option>
                    </select>
                </div>

                <button
                    @click="exportCsv"
                    title="Exportar archivo CSV"
                    class="flex items-center gap-1.5 px-3.5 py-2 bg-white/10 hover:bg-white/15 border border-white/20 hover:border-white/40 text-white rounded-xl text-xs font-medium transition-all"
                >
                    <Download class="w-3.5 h-3.5 text-[#22c55e]" />
                    <span class="hidden sm:inline">CSV</span>
                </button>
            </div>
        </div>

        <div v-if="loading" class="py-12 text-center text-xs text-white/60">Cargando…</div>
        <div v-else-if="!parcelas.length" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-10 text-center text-xs text-white/60">
            No hay parcelas que coincidan con los filtros actuales.
        </div>

        <!-- Grid of plot cards -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="p in parcelas"
                :key="p.id"
                class="bg-white/10 border border-white/20 hover:border-white/40 rounded-2xl overflow-hidden transition-all duration-200 flex flex-col justify-between group"
            >
                <div>
                    <!-- Image header -->
                    <div class="relative h-40 w-full overflow-hidden bg-white/10 flex items-center justify-center cursor-pointer" @click="openDetail(p)">
                        <img v-if="firstFoto(p)" :src="firstFoto(p)" :alt="p.nombreParcela" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <Sprout v-else class="w-10 h-10 text-white/20" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                        <div class="absolute top-3 left-3 right-3 flex justify-between items-center gap-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-black/70 border border-white/20 text-white backdrop-blur-md truncate">{{ p.codigo }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold whitespace-nowrap" :class="ESTADO_BADGE[p.estado]">{{ p.estado }}</span>
                        </div>

                        <div class="absolute bottom-2 right-3 bg-black/70 border border-white/15 px-2.5 py-1 rounded-lg text-xs font-bold text-white backdrop-blur-md">{{ p.areaHa }} ha</div>
                    </div>

                    <!-- Body -->
                    <div class="p-4 space-y-3">
                        <div>
                            <h3 class="text-base font-bold text-white group-hover:text-[#22c55e] transition-colors truncate">{{ p.nombreParcela }}</h3>
                            <p class="text-xs text-white/60 truncate mt-0.5">Productor: <span class="text-white/80">{{ p.propietario || 'N/D' }}</span></p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-[11px] text-white/80">
                            <div class="flex items-center gap-1.5 truncate">
                                <MapPin class="w-3.5 h-3.5 text-[#22c55e] flex-shrink-0" />
                                <span class="truncate">{{ p.municipio }}, {{ p.departamento }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 truncate">
                                <Calendar class="w-3.5 h-3.5 text-white/40 flex-shrink-0" />
                                <span>{{ p.fechaRegistro }}</span>
                            </div>
                        </div>

                        <div class="bg-white/10 rounded-xl p-2.5 border border-white/20 text-[11px] text-white/80 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-white/60">Suelo:</span>
                                <span class="font-semibold text-white truncate max-w-[170px]">{{ p.tipoSuelo || 'N/D' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-white/60">Validación:</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold" :class="VALIDACION_BADGE[p.estadoValidacion]">{{ p.estadoValidacion }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer actions -->
                <div class="p-4 pt-0 border-t border-white/15 flex items-center justify-between mt-2">
                    <span class="text-[11px] text-white/60 truncate">Técnico: <span class="text-white/80">{{ p.tecnicoNombre?.split(' ')[0] }}</span></span>

                    <div class="flex items-center gap-1.5">
                        <router-link :to="{ name: 'ParcelaEditar', params: { id: p.id } }" class="p-1.5 rounded-lg bg-white/10 hover:bg-white/15 border border-white/20 text-white/60 hover:text-white transition-colors" title="Editar">
                            <Pencil class="w-3.5 h-3.5" />
                        </router-link>
                        <button @click="openReview(p)" class="p-1.5 rounded-lg bg-white/10 hover:bg-[#eab308]/20 border border-white/20 text-white/60 hover:text-[#eab308] transition-colors" title="Revisar">
                            <ShieldCheck class="w-3.5 h-3.5" />
                        </button>
                        <button v-if="auth.role === 'administrador'" @click="eliminar(p)" class="p-1.5 rounded-lg bg-white/10 hover:bg-[#ef4444]/20 border border-white/20 text-white/60 hover:text-[#ef4444] transition-colors" title="Eliminar">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                        <button @click="openDetail(p)" class="flex items-center gap-1.5 px-3 py-1.5 bg-white/15 hover:bg-[#1a4f3a] text-[#22c55e] border border-[#22c55e]/30 rounded-xl text-xs font-semibold transition-colors">
                            <Eye class="w-3.5 h-3.5" />
                            <span>Ver detalle</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal detalle -->
        <div v-if="detail" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto" @click.self="detail = null">
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fadeIn">
                <div class="sticky top-0 bg-white/10/95 backdrop-blur-2xl border-b border-white/15 p-4 sm:p-6 flex justify-between items-start z-20">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#4ade80] flex-shrink-0">
                            <Trees class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs bg-[#06b6d4]/15 text-[#38bdf8] px-2 py-0.5 rounded border border-[#38bdf8]/30">{{ detail.codigo }}</span>
                                <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold border" :class="VALIDACION_BADGE[detail.estadoValidacion]">{{ detail.estadoValidacion }}</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white mt-1">{{ detail.nombreParcela }}</h2>
                            <p class="text-xs text-white/80 flex items-center gap-1 mt-0.5">
                                <MapPin class="w-3.5 h-3.5 text-[#38bdf8]" />
                                <span>{{ detail.comunidad ? detail.comunidad + ', ' : '' }}{{ detail.municipio }}, {{ detail.departamento }}</span>
                            </p>
                        </div>
                    </div>
                    <button @click="detail = null" class="p-2 text-white/80 hover:text-white bg-white/10 hover:bg-white/15 rounded-lg transition-colors flex-shrink-0">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 sm:p-6 space-y-6">
                    <!-- Key metrics -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase font-bold text-white/60 block">Área total</span>
                            <span class="text-xl font-bold text-white">{{ detail.areaHa }} ha</span>
                        </div>
                        <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase font-bold text-white/60 block">Estado del proceso</span>
                            <span class="text-xl font-bold text-white">{{ detail.estado }}</span>
                        </div>
                        <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase font-bold text-white/60 block">Profundidad de suelo</span>
                            <span class="text-xl font-bold text-[#4ade80]">{{ detail.profundidadSuelo || 'N/D' }} cm</span>
                        </div>
                        <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase font-bold text-white/60 block">Coordenadas GPS</span>
                            <span class="text-xs font-bold text-[#38bdf8]">{{ detail.latitud !== '' && detail.latitud !== null ? `${detail.latitud}, ${detail.longitud}` : 'No registrado' }}</span>
                        </div>
                    </div>

                    <!-- Diagnostics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                                <Compass class="w-4 h-4 text-[#4ade80]" />
                                <span>Suelo y agua</span>
                            </h3>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between"><span class="text-white/60">Uso actual del suelo:</span><span class="text-white font-medium">{{ detail.usoActual || 'N/D' }}</span></div>
                                <div class="flex justify-between"><span class="text-white/60">Tipo de suelo:</span><span class="text-white font-medium">{{ detail.tipoSuelo || 'N/D' }}</span></div>
                                <div class="flex justify-between"><span class="text-white/60">Disponibilidad de agua:</span><span class="text-white">{{ detail.agua || 'N/D' }}</span></div>
                                <div class="flex justify-between"><span class="text-white/60">Lluvia anual:</span><span class="text-white">{{ detail.lluviaAnual !== '' && detail.lluviaAnual !== null ? detail.lluviaAnual + ' mm' : 'N/D' }}</span></div>
                            </div>
                        </div>

                        <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                                <Mountain class="w-4 h-4 text-[#facc15]" />
                                <span>Diagnóstico físico del terreno</span>
                            </h3>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between"><span class="text-white/60">Presencia de talpetate:</span><span class="text-white font-medium">{{ detail.talpetate || 'N/D' }}</span></div>
                                <div class="flex justify-between"><span class="text-white/60">Encharcamiento:</span><span class="text-[#38bdf8] font-medium">{{ detail.encharca || 'N/D' }}</span></div>
                                <div class="flex justify-between"><span class="text-white/60">Riesgo de erosión:</span><span class="text-[#fca5a5] font-medium">{{ detail.riesgoErosion || 'N/D' }}</span></div>
                                <div class="flex justify-between"><span class="text-white/60">Pendiente:</span><span class="text-white">{{ detail.pendiente !== '' && detail.pendiente !== null ? detail.pendiente + '%' : 'N/D' }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Interventions -->
                    <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Trees class="w-4 h-4 text-[#4ade80]" />
                            <span>Bioindicadores e intervenciones</span>
                        </h3>
                        <p class="text-xs text-white/80">{{ detail.bioindicadores || 'Sin bioindicadores registrados' }}</p>
                        <p class="text-xs text-white/80">{{ detail.intervenciones || 'Sin intervenciones registradas' }}</p>
                    </div>

                    <div v-if="detail.observaciones" class="bg-black/30 p-5 rounded-xl border border-white/10">
                        <h3 class="text-sm font-bold text-white mb-2">Observaciones</h3>
                        <p class="text-xs text-white/80">{{ detail.observaciones }}</p>
                    </div>

                    <div v-if="detail.comentarioSupervisor" class="bg-[#eab308]/10 p-5 rounded-xl border border-[#eab308]/30">
                        <h3 class="text-sm font-bold text-[#fbbf24] mb-2">Comentario del supervisor</h3>
                        <p class="text-xs text-[#fde68a]">{{ detail.comentarioSupervisor }}</p>
                    </div>

                    <!-- Photos -->
                    <div v-if="detail.fotos?.length">
                        <h3 class="text-sm font-bold text-white mb-3">Evidencia fotográfica de campo</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div v-for="f in detail.fotos" :key="f.id" class="h-32 rounded-xl overflow-hidden border border-white/15 group relative bg-black/40">
                                <img :src="fotoUrl(detail.id, f.miniatura || f.archivo)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pt-4 border-t border-white/10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-xs">
                        <div class="space-y-0.5 text-white/60">
                            <p>Productor: <strong class="text-white">{{ detail.propietario || 'N/D' }}</strong></p>
                            <p>Técnico responsable: <strong class="text-white">{{ detail.tecnicoNombre }}</strong> · Registrado el {{ detail.fechaRegistro }}</p>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button @click="detail = null" class="flex-1 sm:flex-initial px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal revisión -->
        <div v-if="reviewing" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn" @click.self="reviewing = null">
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex justify-between items-center pb-2 border-b border-white/15">
                    <h3 class="text-base font-bold text-white">Revisar parcela</h3>
                    <button @click="reviewing = null" class="text-white/60 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <p class="text-xs text-white/60">{{ reviewing.nombreParcela }} · {{ reviewing.codigo }}</p>
                <div>
                    <label class="text-xs text-white/80 block mb-1">Estado de validación</label>
                    <select v-model="reviewForm.estadoValidacion" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50">
                        <option v-for="e in ESTADOS_VALIDACION" :key="e" :value="e">{{ e }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-white/80 block mb-1">Comentario para el técnico</label>
                    <textarea v-model="reviewForm.comentario" rows="3" placeholder="Observaciones, correcciones solicitadas..." class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white resize-none focus:outline-none focus:border-white/50"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-white/15">
                    <button @click="reviewing = null" class="px-4 py-2 bg-white/10 hover:bg-white/15 text-xs text-white/80 rounded-xl">Cancelar</button>
                    <button @click="saveReview" :disabled="savingReview" class="px-4 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-xs font-bold text-white rounded-xl shadow-lg disabled:opacity-60 flex items-center gap-1.5">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        <span>Guardar revisión</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import parcelaService from '../../services/parcelaService';
import { parcelaFotoUrl } from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { toastSuccess, toastInfo, alertError, confirmDialog } from '../../utils/alerts';
import {
    DEPARTAMENTOS, ESTADOS_PROCESO, ESTADOS_VALIDACION,
} from '../../constants/keyline';
import {
    Plus, Search, Download, Eye, MapPin, Calendar, Trash2, Sprout, Pencil, ShieldCheck,
    X, Trees, Compass, Mountain, CheckCircle2,
} from '@lucide/vue';

const ESTADO_BADGE = {
    Levantamiento: 'bg-[#38bdf8]/15 border-[#38bdf8]/30 text-[#38bdf8]',
    'Diseño': 'bg-[#eab308]/15 border-[#eab308]/30 text-[#eab308]',
    Implementado: 'bg-[#22c55e]/15 border-[#22c55e]/30 text-[#22c55e]',
    Pendiente: 'bg-[#ef4444]/15 border-[#ef4444]/30 text-[#ef4444]',
};
const VALIDACION_BADGE = {
    'Pendiente de revisión': 'bg-[#eab308]/15 border-[#eab308]/30 text-[#eab308]',
    Validado: 'bg-[#22c55e]/15 border-[#22c55e]/30 text-[#22c55e]',
    'Requiere corrección': 'bg-[#ef4444]/15 border-[#ef4444]/30 text-[#ef4444]',
};

const auth = useAuthStore();
const parcelas = ref([]);
const loading = ref(true);
const detail = ref(null);
const reviewing = ref(null);
const savingReview = ref(false);
const reviewForm = reactive({ estadoValidacion: '', comentario: '' });

const filters = reactive({ q: '', departamento: '', estado: '', estadoValidacion: '' });

let debounceTimer = null;
function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(load, 300);
}

async function load() {
    loading.value = true;
    try {
        const params = {};
        Object.entries(filters).forEach(([k, v]) => { if (v) params[k] = v; });
        const { data } = await parcelaService.listar(params);
        parcelas.value = data.parcelas;
    } finally {
        loading.value = false;
    }
}
load();

function fotoUrl(parcelaId, name) {
    return parcelaFotoUrl(parcelaId, name);
}

function firstFoto(p) {
    if (!p.fotos?.length) return null;
    const f = p.fotos[0];
    return fotoUrl(p.id, f.miniatura || f.archivo);
}

async function openDetail(p) {
    const { data } = await parcelaService.obtener(p.id);
    detail.value = data.parcela;
}

function openReview(p) {
    reviewing.value = p;
    reviewForm.estadoValidacion = p.estadoValidacion;
    reviewForm.comentario = p.comentarioSupervisor || '';
}

async function saveReview() {
    savingReview.value = true;
    try {
        await parcelaService.revisar(reviewing.value.id, reviewForm.estadoValidacion, reviewForm.comentario);
        reviewing.value = null;
        toastSuccess('Revisión guardada.');
        await load();
    } catch (err) {
        alertError(err.message || 'No se pudo guardar la revisión.');
    } finally {
        savingReview.value = false;
    }
}

async function eliminar(p) {
    const ok = await confirmDialog('Esta acción eliminará la parcela y sus fotos de forma permanente.', { title: '¿Eliminar parcela?', danger: true, confirmText: 'Eliminar' });
    if (!ok) return;
    await parcelaService.eliminar(p.id);
    toastInfo('Parcela eliminada.');
    await load();
}

function exportCsv() {
    if (!parcelas.value.length) { alertError('No hay datos para exportar.'); return; }
    const cols = ['codigo', 'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'areaHa', 'estado', 'estadoValidacion', 'usoActual', 'tipoSuelo', 'pendiente', 'altitud', 'agua', 'fuenteAgua', 'riesgoErosion', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'observaciones', 'tecnicoNombre', 'fechaRegistro', 'latitud', 'longitud'];
    const esc = (v) => { const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s; };
    const rows = [cols.join(',')].concat(parcelas.value.map((p) => cols.map((c) => esc(p[c])).join(',')));
    const blob = new Blob(['﻿' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `parcelas_keyline_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
