<template>
    <div class="max-w-4xl mx-auto p-8 glass-card text-black font-serif leading-relaxed">
        <div v-if="loading" class="py-20 text-center text-sm font-sans text-gray-300 no-print">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
            <p class="mt-2">Cargando reporte para impresión...</p>
        </div>

        <div v-else-if="!supervision" class="py-20 text-center no-print">
            <p class="text-red-500 font-bold">Error al cargar los datos de supervisión.</p>
        </div>

        <div v-else class="space-y-6">
            <!-- Institutional Header -->
            <div class="border-b-4 border-slate-900 pb-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 flex items-center justify-center glass-card rounded border border-slate-300 p-1">
                        <img :src="logoUrl" alt="SIGIE Logo" class="max-w-full max-h-full object-contain" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight font-sans text-white uppercase">SIGIE</h1>
                        <p class="text-[9px] font-sans font-bold uppercase tracking-widest text-gray-300">Sistema de Gestión de Inspecciones</p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-sm font-sans font-extrabold uppercase text-white">Acta de Supervisión</h2>
                    <p class="font-mono text-xs font-bold text-white">Nº ACTA-SUP-{{ supervision.id.toString().padStart(5, '0') }}</p>
                    <p class="text-[10px] text-gray-300 font-sans mt-0.5">Fecha Emisión: {{ new Date().toLocaleDateString('es-ES') }}</p>
                </div>
            </div>

            <!-- General Metadata Block -->
            <div class="grid grid-cols-2 gap-6 bg-black/20 p-4 border border-white/10 rounded-md font-sans text-xs">
                <div>
                    <h3 class="font-extrabold text-[10px] uppercase text-gray-300 tracking-wider mb-2">1. Datos del Establecimiento</h3>
                    <p class="text-sm font-bold text-white">{{ supervision.establecimiento }}</p>
                    <p class="text-gray-300 mt-1">Fecha de Supervisión: <span class="font-bold text-white">{{ supervision.fecha_supervision }}</span></p>
                </div>
                <div class="border-l border-white/10 pl-6">
                    <h3 class="font-extrabold text-[10px] uppercase text-gray-300 tracking-wider mb-2">2. Inspector Responsable</h3>
                    <p class="text-sm font-bold text-white">{{ supervision.inspector_nombre }}</p>
                    <p class="text-gray-300 mt-1">Código: <span class="font-mono font-bold">{{ supervision.inspector_codigo }}</span> | Área: <span class="font-bold">{{ supervision.inspector_area }}</span></p>
                </div>
            </div>

            <!-- Body Details -->
            <div class="space-y-6">
                <!-- Hallazgos -->
                <div>
                    <h3 class="font-sans font-extrabold text-xs uppercase text-white tracking-wider border-b border-slate-300 pb-1 mb-2">3. Hallazgos Sanitarios Detectados</h3>
                    <div class="text-xs whitespace-pre-line leading-relaxed text-justify px-2">
                        {{ supervision.hallazgos_detectados }}
                    </div>
                </div>

                <!-- Normativa -->
                <div v-if="supervision.norma_especifica">
                    <h3 class="font-sans font-extrabold text-xs uppercase text-white tracking-wider border-b border-slate-300 pb-1 mb-2">4. Normativa Específica Infraccionada / Asociada</h3>
                    <p class="text-xs font-bold text-white bg-slate-100 p-3 rounded-md border border-white/10 inline-block font-sans">
                        {{ supervision.norma_especifica }}
                    </p>
                </div>

                <!-- Seguimiento y Cierre -->
                <div>
                    <h3 class="font-sans font-extrabold text-xs uppercase text-white tracking-wider border-b border-slate-300 pb-1 mb-2">5. Estado de Seguimiento y Acciones Correctivas</h3>
                    <div class="grid grid-cols-2 gap-4 text-xs font-sans mt-2">
                        <div>
                            <span class="text-[10px] text-gray-300 block uppercase font-bold">Estado del Hallazgo</span>
                            <span class="text-xs font-extrabold uppercase border px-2 py-0.5 rounded inline-block mt-1"
                                :class="[supervision.estado_hallazgo === 'Abierto' ? 'border-red-300 text-red-700 bg-red-50' : 
                                         supervision.estado_hallazgo === 'En proceso' ? 'border-amber-300 text-amber-700 bg-amber-50' : 
                                         'border-emerald-300 text-emerald-700 bg-emerald-50']"
                            >
                                {{ supervision.estado_hallazgo }}
                            </span>
                        </div>
                        <div v-if="supervision.fecha_cumplimiento">
                            <span class="text-[10px] text-gray-300 block uppercase font-bold">Fecha Cumplimiento</span>
                            <span class="text-xs font-bold text-white mt-1 block">{{ supervision.fecha_cumplimiento }}</span>
                        </div>
                    </div>

                    <!-- Verificación oficial -->
                    <div class="mt-4" v-if="supervision.verificacion_oficial">
                        <span class="text-[10px] text-gray-300 block uppercase font-bold font-sans">Detalle de la Verificación Realizada</span>
                        <div class="text-xs whitespace-pre-line leading-relaxed text-justify mt-1 p-3 border border-white/10 rounded-md bg-black/20">
                            {{ supervision.verificacion_oficial }}
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div v-if="supervision.observaciones">
                    <h3 class="font-sans font-extrabold text-xs uppercase text-white tracking-wider border-b border-slate-300 pb-1 mb-2">6. Observaciones Generales</h3>
                    <div class="text-xs italic text-gray-300 px-2">
                        {{ supervision.observaciones }}
                    </div>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="pt-20">
                <div class="grid grid-cols-2 gap-12 font-sans text-xs">
                    <div class="text-center">
                        <div class="border-t border-slate-400 mx-auto w-48 mb-2"></div>
                        <p class="font-bold text-white">{{ supervision.inspector_nombre }}</p>
                        <p class="text-[10px] text-gray-300">Inspector Responsable (Firma)</p>
                        <p class="text-[9px] text-slate-400 font-mono mt-0.5">Código: {{ supervision.inspector_codigo }}</p>
                    </div>
                    <div class="text-center">
                        <div class="border-t border-slate-400 mx-auto w-48 mb-2"></div>
                        <p class="font-bold text-white">Representante del Establecimiento</p>
                        <p class="text-[10px] text-gray-300">Responsable / Encargado (Firma)</p>
                        <p class="text-[9px] text-slate-400 mt-0.5">Fecha: ____/____/________</p>
                    </div>
                </div>
            </div>

            <!-- Print Prompt Hint (Visible only on screen, hidden on print) -->
            <div class="mt-12 bg-sky-50 border border-sky-100 p-4 rounded-md text-center text-xs text-sky-800 no-print flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">info</span>
                <span>Si el diálogo de impresión no se abre automáticamente, presione <strong>CTRL + P</strong> o haga clic en el botón de su navegador para guardar como PDF o imprimir físicamente.</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../services/api.js';

const getLogoUrl = () => {
    const path = window.location.pathname.toLowerCase();
    const isViteDev = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';
    if (isViteDev) {
        return '/sigie/logo.png';
    }
    const distIndex = path.indexOf('/frontend/dist');
    if (distIndex !== -1) {
        return window.location.pathname.substring(0, distIndex) + '/Frontend/dist/logo.png';
    }
    const sigieIndex = path.indexOf('/sigie');
    if (sigieIndex !== -1) {
        return window.location.pathname.substring(0, sigieIndex) + '/sigie/logo.png';
    }
    return '/logo.png';
};
const logoUrl = getLogoUrl();

const route = useRoute();
const supervision = ref(null);
const loading = ref(true);

const fetchDetalle = async () => {
    loading.value = true;
    try {
        const id = route.params.id;
        const response = await api.get(`/supervisiones/${id}`);
        if (response.data?.status === 'success') {
            supervision.value = response.data.data;
            
            // Trigger print dialog once DOM has loaded the content
            setTimeout(() => {
                window.print();
            }, 500);
        }
    } catch (error) {
        console.error('Error al cargar reporte de supervisión', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDetalle();
});
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
