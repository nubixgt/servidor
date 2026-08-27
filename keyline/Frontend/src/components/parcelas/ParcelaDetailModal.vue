<template>
    <div class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto" @click.self="$emit('close')">
        <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fadeIn">
            <div class="sticky top-0 bg-[#0c1e17]/80 backdrop-blur-2xl border-b border-white/15 p-4 sm:p-6 flex justify-between items-start z-20">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#4ade80] flex-shrink-0">
                        <Trees class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs bg-[#06b6d4]/15 text-[#38bdf8] px-2 py-0.5 rounded border border-[#38bdf8]/30">{{ parcela.codigo }}</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold border" :class="VALIDACION_BADGE[parcela.estadoValidacion]">{{ parcela.estadoValidacion }}</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white mt-1">{{ parcela.nombreParcela }}</h2>
                        <p class="text-xs text-white/80 flex items-center gap-1 mt-0.5">
                            <MapPin class="w-3.5 h-3.5 text-[#38bdf8]" />
                            <span>{{ parcela.comunidad ? parcela.comunidad + ', ' : '' }}{{ parcela.municipio }}, {{ parcela.departamento }}</span>
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 text-white/80 hover:text-white bg-white/10 hover:bg-white/15 rounded-lg transition-colors flex-shrink-0">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-6">
                <!-- Key metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                        <span class="text-[10px] uppercase font-bold text-white/60 block">Área total</span>
                        <span class="text-xl font-bold text-white">{{ parcela.areaHa }} ha</span>
                    </div>
                    <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                        <span class="text-[10px] uppercase font-bold text-white/60 block">Altitud</span>
                        <span class="text-xl font-bold text-white">{{ parcela.altitud || 'N/D' }} <span v-if="parcela.altitud" class="text-xs font-normal text-white/60">msnm</span></span>
                    </div>
                    <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                        <span class="text-[10px] uppercase font-bold text-white/60 block">Estado del proceso</span>
                        <span class="text-xl font-bold text-white">{{ parcela.estado }}</span>
                    </div>
                    <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                        <span class="text-[10px] uppercase font-bold text-white/60 block">Profundidad de suelo</span>
                        <span class="text-xl font-bold text-[#4ade80]">{{ parcela.profundidadSuelo || 'N/D' }} cm</span>
                    </div>
                    <div class="bg-black/30 p-3.5 rounded-xl border border-white/10">
                        <span class="text-[10px] uppercase font-bold text-white/60 block">Coordenadas GPS</span>
                        <span class="text-xs font-bold text-[#38bdf8]">{{ parcela.latitud !== '' && parcela.latitud !== null ? `${parcela.latitud}, ${parcela.longitud}` : 'No registrado' }}</span>
                        <span v-if="parcela.gpsPrecision" class="text-[10px] text-white/50 block mt-0.5">±{{ parcela.gpsPrecision }} m</span>
                    </div>
                </div>

                <!-- Identificación y responsable -->
                <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                        <User class="w-4 h-4 text-[#4ade80]" />
                        <span>Identificación y responsable</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-white/60">Productor / responsable:</span><span class="text-white font-medium">{{ parcela.propietario || 'N/D' }}</span></div>
                        <div class="flex justify-between"><span class="text-white/60">Teléfono / contacto:</span><span class="text-white font-medium">{{ parcela.telefono || 'N/D' }}</span></div>
                        <div class="flex justify-between"><span class="text-white/60">Tenencia de la tierra:</span><span class="text-white font-medium">{{ parcela.tenenciaTierra || 'N/D' }}</span></div>
                        <div class="flex justify-between"><span class="text-white/60">Familias beneficiadas:</span><span class="text-white font-medium">{{ parcela.numFamiliasBeneficiadas || 'N/D' }}</span></div>
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
                            <div class="flex justify-between"><span class="text-white/60">Uso actual del suelo:</span><span class="text-white font-medium">{{ parcela.usoActual || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Cultivo principal:</span><span class="text-white font-medium">{{ parcela.cultivoPrincipal || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Clase textural del suelo:</span><span class="text-white font-medium">{{ parcela.claseTextural || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Fuente original de agua:</span><span class="text-white">{{ parcela.fuenteAguaPrincipal || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Fuentes secundarias de agua:</span><span class="text-white">{{ parcela.fuenteAguaSecundaria || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Sistema de riego:</span><span class="text-white">{{ parcela.sistemaRiego || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Lluvia anual:</span><span class="text-white">{{ parcela.lluviaAnual !== '' && parcela.lluviaAnual !== null ? parcela.lluviaAnual + ' mm' : 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Fuente del dato de lluvia:</span><span class="text-white">{{ parcela.lluviaFuente || 'N/D' }}</span></div>
                        </div>
                    </div>

                    <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                            <Mountain class="w-4 h-4 text-[#facc15]" />
                            <span>Diagnóstico físico del terreno</span>
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div><span class="text-white/60 block mb-0.5">Limitantes de uso:</span><span class="text-white font-medium">{{ parcela.limitantesUso || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Encharcamiento:</span><span class="text-[#38bdf8] font-medium">{{ parcela.encharca || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Riesgo de erosión:</span><span class="text-[#fca5a5] font-medium">{{ parcela.riesgoErosion || 'N/D' }}</span></div>
                            <div class="flex justify-between"><span class="text-white/60">Pendiente:</span><span class="text-white">{{ parcela.pendiente !== '' && parcela.pendiente !== null ? parcela.pendiente + '%' : 'N/D' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Interventions & follow-up -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                            <Trees class="w-4 h-4 text-[#4ade80]" />
                            <span>Bioindicadores e intervenciones</span>
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div><span class="text-white/60 block mb-0.5">Bioindicadores:</span><span class="text-white">{{ parcela.bioindicadores || 'Sin registro' }}</span></div>
                            <div><span class="text-white/60 block mb-0.5">Intervenciones:</span><span class="text-white">{{ parcela.intervenciones || 'Sin detalle' }}</span></div>
                            <div><span class="text-white/60 block mb-0.5">Especies de reforestación:</span><span class="text-white">{{ parcela.especiesReforestacion || 'Sin registro' }}</span></div>
                        </div>
                    </div>

                    <div class="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                            <CalendarClock class="w-4 h-4 text-[#38bdf8]" />
                            <span>Seguimiento</span>
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between"><span class="text-white/60">Próxima visita:</span><span class="text-white font-medium">{{ parcela.fechaProximaVisita || 'Sin programar' }}</span></div>
                            <div class="flex justify-between items-center">
                                <span class="text-white/60">Consentimiento del productor:</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold" :class="parcela.consentimientoProductor ? 'bg-[#22c55e]/15 border-[#22c55e]/30 text-[#22c55e]' : 'bg-white/10 border-white/20 text-white/60'">
                                    {{ parcela.consentimientoProductor ? 'Autorizado' : 'No registrado' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="parcela.observaciones" class="bg-black/30 p-5 rounded-xl border border-white/10">
                    <h3 class="text-sm font-bold text-white mb-2">Observaciones</h3>
                    <p class="text-xs text-white/80">{{ parcela.observaciones }}</p>
                </div>

                <div v-if="parcela.comentarioSupervisor" class="bg-[#eab308]/10 p-5 rounded-xl border border-[#eab308]/30">
                    <h3 class="text-sm font-bold text-[#fbbf24] mb-2">Comentario del supervisor</h3>
                    <p class="text-xs text-[#fde68a]">{{ parcela.comentarioSupervisor }}</p>
                </div>

                <!-- Photos -->
                <div v-if="parcela.fotos?.length">
                    <h3 class="text-sm font-bold text-white mb-3">Evidencia fotográfica de campo</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div v-for="f in parcela.fotos" :key="f.id" class="h-32 rounded-xl overflow-hidden border border-white/15 group relative bg-black/40">
                            <img :src="fotoUrl(parcela.id, f.miniatura || f.archivo)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-white/10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-xs">
                    <div class="space-y-0.5 text-white/60">
                        <p>Técnico responsable: <strong class="text-white">{{ parcela.tecnicoNombre }}</strong> · Registrado el {{ parcela.fechaRegistro }}</p>
                        <p v-if="parcela.comunidad">Comunidad: <strong class="text-white">{{ parcela.comunidad }}</strong></p>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button @click="$emit('close')" class="flex-1 sm:flex-initial px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { parcelaFotoUrl } from '../../services/api';
import { Trees, MapPin, X, User, Compass, Mountain, CalendarClock } from '@lucide/vue';

defineProps({
    parcela: { type: Object, required: true },
});
defineEmits(['close']);

const VALIDACION_BADGE = {
    'Pendiente de revisión': 'bg-[#eab308]/15 border-[#eab308]/30 text-[#eab308]',
    Validado: 'bg-[#22c55e]/15 border-[#22c55e]/30 text-[#22c55e]',
    'Requiere corrección': 'bg-[#ef4444]/15 border-[#ef4444]/30 text-[#ef4444]',
};

function fotoUrl(parcelaId, name) {
    return parcelaFotoUrl(parcelaId, name);
}
</script>
