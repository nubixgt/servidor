<template>
    <div>
        <div v-if="loading" class="py-12 text-center text-xs text-white/60">Cargando…</div>

        <template v-else>
            <div class="space-y-6 max-w-[1600px] mx-auto pb-4">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Bioindicadores y salud del suelo</h2>
                        <p class="text-xs sm:text-sm text-white/80 mt-0.5">Registro biológico de macroorganismos y actividad del suelo reportado por el equipo técnico.</p>
                    </div>
                    <router-link
                        :to="{ name: 'ParcelaNueva' }"
                        class="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-semibold tracking-wider uppercase transition-all shadow-[0_0_20px_rgba(34,197,94,0.4)]"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Registrar bioindicadores</span>
                    </router-link>
                </div>

                <!-- KPI overview cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Parcelas con registro</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#4ade80]"><Activity class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white">{{ conBio.length }}</div>
                            <p class="text-xs text-[#4ade80] font-semibold mt-1">{{ pct }}% del total de parcelas</p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Indicadores distintos</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#38bdf8]"><Bug class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white">{{ resumen.topBioindicadores.length }}</div>
                            <p class="text-xs text-white/80 mt-1">Términos únicos reportados</p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Profundidad prom. suelo</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#facc15]"><Ruler class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white">{{ resumen.diagnosticoFisico.profundidadProm.toFixed(1) }} cm</div>
                            <p class="text-xs text-white/80 mt-1">{{ resumen.diagnosticoFisico.muestras }} mediciones</p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Sin registro aún</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#f59e0b]"><CircleAlert class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white">{{ parcelas.length - conBio.length }}</div>
                            <p class="text-xs text-white/80 mt-1">Parcelas por diagnosticar</p>
                        </div>
                    </div>
                </div>

                <!-- Filter bar -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-4 space-y-3">
                    <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                        <div class="flex-1 relative group">
                            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-white transition-colors" />
                            <input
                                v-model="searchTerm"
                                placeholder="Buscar por parcela, código o técnico..."
                                class="w-full bg-white/5 border border-white/15 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/50 transition-all placeholder:text-white/40"
                            />
                        </div>
                    </div>

                    <div v-if="resumen.topBioindicadores.length" class="flex items-center gap-2 flex-wrap pt-1">
                        <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider flex items-center gap-1">
                            <Filter class="w-3 h-3" />
                            <span>Filtrar organismo:</span>
                        </span>
                        <button
                            v-for="b in resumen.topBioindicadores"
                            :key="b.nombre"
                            @click="toggleFilter(b.nombre)"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-all"
                            :class="selectedFilters.includes(b.nombre) ? 'bg-[#22c55e] text-black shadow-sm' : 'bg-black/30 text-white/80 hover:bg-white/10 border border-white/10'"
                        >
                            {{ b.nombre }} · {{ b.conteo }}
                        </button>
                        <button v-if="selectedFilters.length" @click="selectedFilters = []" class="text-[11px] text-[#f87171] hover:underline ml-2">Limpiar filtros</button>
                    </div>
                </div>

                <!-- Cards grid -->
                <div v-if="!filteredParcelas.length" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-10 text-center text-xs text-white/60">
                    No hay parcelas con bioindicadores que coincidan.
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="p in filteredParcelas" :key="p.id" class="bg-white/10 border border-white/20 hover:border-white/40 rounded-2xl p-5 flex flex-col justify-between transition-all duration-200">
                        <div>
                            <div class="flex justify-between items-start mb-2.5">
                                <span class="text-xs text-[#38bdf8] bg-[#38bdf8]/15 px-2.5 py-0.5 rounded-lg border border-[#38bdf8]/30">{{ p.codigo }}</span>
                                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30">{{ p.estado }}</span>
                            </div>

                            <h3 class="text-base font-bold text-white">{{ p.nombreParcela }}</h3>
                            <p class="text-xs text-white/60 mt-0.5">Técnico: <strong class="text-white/80">{{ p.tecnicoNombre }}</strong></p>

                            <div class="grid grid-cols-2 gap-2 mt-4 py-2.5 px-3 bg-black/30 rounded-xl border border-white/5 text-center">
                                <div>
                                    <span class="text-[10px] text-white/60 uppercase font-bold block">Departamento</span>
                                    <span class="text-xs font-bold text-white truncate block">{{ p.departamento }}</span>
                                </div>
                                <div class="border-l border-white/10">
                                    <span class="text-[10px] text-white/60 uppercase font-bold block">Registrado</span>
                                    <span class="text-xs font-bold text-white">{{ p.fechaRegistro }}</span>
                                </div>
                            </div>

                            <div class="mt-3.5">
                                <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider block mb-1.5">Bioindicadores observados:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="(b, i) in splitBio(p.bioindicadores)" :key="i" class="text-[10px] bg-white/10 border border-white/15 text-white px-2 py-0.5 rounded-md font-medium">{{ b }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-white/60 flex items-center gap-1">
                                <MapPin class="w-3 h-3" />
                                {{ p.municipio }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import parcelaService from '../../services/parcelaService';
import dashboardService from '../../services/dashboardService';
import { Plus, Activity, Bug, Ruler, CircleAlert, Search, Filter, MapPin } from '@lucide/vue';

const loading = ref(true);
const parcelas = ref([]);
const resumen = ref({ topBioindicadores: [], diagnosticoFisico: { profundidadProm: 0, muestras: 0 } });
const searchTerm = ref('');
const selectedFilters = ref([]);

const conBio = computed(() => parcelas.value.filter((p) => (p.bioindicadores || '').trim().length > 0));
const pct = computed(() => parcelas.value.length ? Math.round((conBio.value.length / parcelas.value.length) * 100) : 0);

function splitBio(text) {
    return (text || '').split(/[,;/]+/).map((s) => s.trim()).filter(Boolean);
}

function toggleFilter(nombre) {
    selectedFilters.value = selectedFilters.value.includes(nombre)
        ? selectedFilters.value.filter((f) => f !== nombre)
        : [...selectedFilters.value, nombre];
}

const filteredParcelas = computed(() => conBio.value.filter((p) => {
    const term = searchTerm.value.toLowerCase();
    const matchesSearch = !term
        || p.nombreParcela.toLowerCase().includes(term)
        || p.codigo.toLowerCase().includes(term)
        || (p.tecnicoNombre || '').toLowerCase().includes(term);
    const bioList = splitBio(p.bioindicadores).map((b) => b.toLowerCase());
    const matchesFilters = !selectedFilters.value.length
        || selectedFilters.value.every((f) => bioList.some((b) => b.includes(f.toLowerCase())));
    return matchesSearch && matchesFilters;
}));

(async () => {
    try {
        const [pRes, rRes] = await Promise.all([parcelaService.listar(), dashboardService.resumen()]);
        parcelas.value = pRes.data.parcelas;
        resumen.value = rRes.data;
    } finally {
        loading.value = false;
    }
})();
</script>
