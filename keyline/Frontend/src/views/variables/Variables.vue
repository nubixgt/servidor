<template>
    <div class="space-y-6 max-w-[1600px] mx-auto pb-4">
        <!-- Header -->
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Variables técnicas</h2>
            <p class="text-xs sm:text-sm text-white/80 mt-0.5">Catálogo de referencia rápida de las variables que se capturan al registrar una parcela keyline.</p>
        </div>

        <!-- KPI overview cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Grupos de diagnóstico</span>
                    <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#4ade80]"><LayoutGrid class="w-4 h-4" /></div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-white">{{ GRUPOS.length }}</div>
                    <p class="text-xs text-white/80 mt-1">Categorías del formulario de registro</p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Variables catalogadas</span>
                    <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#38bdf8]"><ListChecks class="w-4 h-4" /></div>
                </div>
                <div class="mt-3">
                    <div class="text-3xl font-bold text-white">{{ totalVariables }}</div>
                    <p class="text-xs text-white/80 mt-1">Términos de referencia técnica</p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Grupo más extenso</span>
                    <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#facc15]"><Mountain class="w-4 h-4" /></div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-bold text-white">{{ grupoMasGrande.label }}</div>
                    <p class="text-xs text-white/80 mt-1">{{ grupoMasGrande.variables.length }} variables</p>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Uso</span>
                    <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#4ade80]"><BookOpen class="w-4 h-4" /></div>
                </div>
                <div class="mt-3">
                    <p class="text-xs text-white/80">Guía de apoyo en campo: recuerda qué observar y registrar en cada grupo del formulario.</p>
                </div>
            </div>
        </div>

        <!-- Search bar -->
        <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-4">
            <div class="relative group">
                <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-white transition-colors" />
                <input
                    v-model="searchTerm"
                    placeholder="Buscar una variable (ej. pH, encharcamiento, curvas de nivel...)"
                    class="w-full bg-white/5 border border-white/15 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/50 transition-all placeholder:text-white/40"
                />
            </div>
        </div>

        <!-- Glossary groups -->
        <div v-if="!gruposFiltrados.length" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-10 text-center text-xs text-white/60">
            Ninguna variable coincide con "{{ searchTerm }}".
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="g in gruposFiltrados" :key="g.id" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 hover:border-white/40 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 transition-all duration-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-black/30 border border-white/15 flex items-center justify-center flex-shrink-0" :class="g.color">
                            <component :is="g.icon" class="w-4 h-4" />
                        </div>
                        <h4 class="text-sm font-bold text-white">{{ g.label }}</h4>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border bg-black/30 text-white/60 border-white/10">{{ g.variables.length }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="v in g.variables" :key="v" class="text-[10px] bg-white/10 border border-white/15 text-white/80 px-2.5 py-1 rounded-lg font-medium">{{ v }}</span>
                </div>

                <div class="mt-4 pt-3 border-t border-white/10">
                    <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider flex items-center gap-1 mb-1.5">
                        <ChartNoAxesCombined class="w-3 h-3" />
                        <span>Datos reales registrados</span>
                    </span>
                    <ul v-if="loadingParcelas" class="text-[11px] text-white/50">Cargando…</ul>
                    <ul v-else-if="groupStats[g.id]?.length" class="space-y-1">
                        <li v-for="(s, i) in groupStats[g.id]" :key="i" class="text-[11px] text-white/80">{{ s }}</li>
                    </ul>
                    <p v-else class="text-[11px] text-white/50">Aún no hay parcelas con este dato registrado.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import parcelaService from '../../services/parcelaService';
import { Mountain, Droplets, CloudRain, AlertOctagon, Sprout, Compass, LayoutGrid, ListChecks, BookOpen, Search, ChartNoAxesCombined } from '@lucide/vue';

const GRUPOS = [
    { id: 'soil', label: 'Suelo', icon: Mountain, color: 'text-[#facc15]', variables: ['Profundidad de suelo', 'Tipo / textura', 'Estructura', 'Compactación', 'Materia orgánica', 'Infiltración', 'Riesgo de erosión', 'Color', 'pH', 'Humedad'] },
    { id: 'water', label: 'Agua', icon: Droplets, color: 'text-[#38bdf8]', variables: ['Escorrentía superficial', 'Tasa de infiltración', 'Encharcamiento', 'Drenaje', 'Fuentes de agua', 'Ríos / quebradas', 'Nacimientos', 'Reservorios', 'Sistema de riego'] },
    { id: 'rainfall', label: 'Lluvia', icon: CloudRain, color: 'text-[#38bdf8]', variables: ['Lluvia anual acumulada', 'Lluvia mensual', 'Evento máximo', 'Días de lluvia', 'Fuente del dato', 'Fecha de captura'] },
    { id: 'limitantes', label: 'Limitantes de uso', icon: AlertOctagon, color: 'text-[#ef4444]', variables: ['Talpetate o capa endurecida', 'Suelo poco profundo', 'Pendiente pronunciada', 'Erosión', 'Pedregosidad', 'Compactación', 'Baja infiltración', 'Drenaje deficiente', 'Encharcamiento', 'Escasez de agua', 'Baja fertilidad', 'Salinidad'] },
    { id: 'bioindicators', label: 'Bioindicadores', icon: Sprout, color: 'text-[#4ade80]', variables: ['Lombrices', 'Insectos', 'Hongos', 'Raíces', 'Plantas nativas', 'Organismos del suelo', 'Residuo orgánico / hojarasca'] },
    { id: 'topography', label: 'Topografía', icon: Compass, color: 'text-[#4ade80]', variables: ['Elevación', 'Pendiente', 'Orientación', 'Curvas de nivel', 'Punto clave (keypoint)', 'Línea keyline', 'Cresta', 'Valle'] },
];

const searchTerm = ref('');
const parcelas = ref([]);
const loadingParcelas = ref(true);

const totalVariables = computed(() => GRUPOS.reduce((sum, g) => sum + g.variables.length, 0));
const grupoMasGrande = computed(() => GRUPOS.reduce((max, g) => (g.variables.length > max.variables.length ? g : max), GRUPOS[0]));

function fmtNum(n) {
    return Number(n || 0).toLocaleString('es-GT', { maximumFractionDigits: 1 });
}

function toNum(v) {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : null;
}

function avgOf(field) {
    const vals = parcelas.value.map((p) => toNum(p[field])).filter((n) => n !== null);
    return vals.length ? { avg: vals.reduce((a, b) => a + b, 0) / vals.length, n: vals.length } : null;
}

function modeOf(field) {
    const counts = {};
    parcelas.value.forEach((p) => {
        const v = (p[field] || '').toString().trim();
        if (v) counts[v] = (counts[v] || 0) + 1;
    });
    const entries = Object.entries(counts).sort((a, b) => b[1] - a[1]);
    return entries.length ? { value: entries[0][0], count: entries[0][1] } : null;
}

function countWhere(field, value) {
    return parcelas.value.filter((p) => (p[field] || '').toString().trim() === value).length;
}

function topTokens(field, limit = 3) {
    const counts = {};
    parcelas.value.forEach((p) => {
        (p[field] || '').split(/[,;/]+/).map((s) => s.trim()).filter(Boolean).forEach((b) => {
            counts[b] = (counts[b] || 0) + 1;
        });
    });
    return Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, limit).map(([nombre, conteo]) => ({ nombre, conteo }));
}

const groupStats = computed(() => {
    const total = parcelas.value.length;
    const prof = avgOf('profundidadSuelo');
    const riesgo = modeOf('riesgoErosion');
    const claseTextural = modeOf('claseTextural');
    const fuenteAgua = modeOf('fuenteAguaPrincipal');
    const conEncharca = countWhere('encharca', 'Sí');
    const evalEncharca = conEncharca + countWhere('encharca', 'No');
    const lluvia = avgOf('lluviaAnual');
    const fuenteLluvia = modeOf('lluviaFuente');
    const conLimitantes = parcelas.value.filter((p) => (p.limitantesUso || '').trim()).length;
    const conBio = parcelas.value.filter((p) => (p.bioindicadores || '').trim()).length;
    const pendiente = avgOf('pendiente');
    const altitud = avgOf('altitud');

    return {
        soil: [
            prof && `Profundidad promedio: ${prof.avg.toFixed(1)} cm (${prof.n} parcelas)`,
            riesgo && `Riesgo de erosión más común: ${riesgo.value} (${riesgo.count} parcelas)`,
            claseTextural && `Clase textural más común: ${claseTextural.value} (${claseTextural.count} parcelas)`,
        ].filter(Boolean),
        water: [
            fuenteAgua && `Fuente principal más común: ${fuenteAgua.value} (${fuenteAgua.count} parcelas)`,
            evalEncharca > 0 && `Con encharcamiento: ${conEncharca} de ${evalEncharca} evaluadas`,
        ].filter(Boolean),
        rainfall: [
            lluvia && `Lluvia anual promedio: ${fmtNum(lluvia.avg)} mm (${lluvia.n} parcelas)`,
            fuenteLluvia && `Fuente del dato más común: ${fuenteLluvia.value} (${fuenteLluvia.count} parcelas)`,
        ].filter(Boolean),
        limitantes: [
            total > 0 && `${conLimitantes} de ${total} parcelas con limitantes de uso registradas`,
            ...topTokens('limitantesUso').map((l) => `${l.nombre}: ${l.conteo} parcela(s)`),
        ].filter(Boolean),
        bioindicators: [
            total > 0 && `${conBio} de ${total} parcelas con bioindicadores registrados`,
            ...topTokens('bioindicadores').map((b) => `${b.nombre}: ${b.conteo} parcela(s)`),
        ].filter(Boolean),
        topography: [
            pendiente && `Pendiente promedio: ${pendiente.avg.toFixed(1)}% (${pendiente.n} parcelas)`,
            altitud && `Altitud promedio: ${fmtNum(altitud.avg)} msnm (${altitud.n} parcelas)`,
        ].filter(Boolean),
    };
});

(async () => {
    try {
        const { data } = await parcelaService.listar();
        parcelas.value = data.parcelas;
    } finally {
        loadingParcelas.value = false;
    }
})();

const gruposFiltrados = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    if (!term) return GRUPOS;
    return GRUPOS
        .map((g) => ({ ...g, variables: g.variables.filter((v) => v.toLowerCase().includes(term)) }))
        .filter((g) => g.variables.length > 0 || g.label.toLowerCase().includes(term));
});
</script>
