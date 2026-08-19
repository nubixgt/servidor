<template>
    <div class="space-y-5 max-w-[1600px] mx-auto pb-4">
        <div class="flex justify-end">
            <router-link :to="{ name: 'ParcelaNueva' }" class="flex items-center gap-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold transition-all shadow-md">
                <Plus class="w-4 h-4" />
                <span>Registrar parcela</span>
            </router-link>
        </div>

        <div v-if="loading" class="py-12 text-center text-xs text-white/60">Cargando…</div>

        <div v-else-if="!parcelas.length" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-10 text-center">
            <p class="text-xs text-white/60 mb-4">Aún no has registrado ninguna parcela.</p>
            <router-link :to="{ name: 'ParcelaNueva' }" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold transition-all">
                <Plus class="w-4 h-4" />
                <span>Registrar mi primera parcela</span>
            </router-link>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <router-link
                v-for="p in parcelas"
                :key="p.id"
                :to="{ name: 'ParcelaEditar', params: { id: p.id } }"
                class="block bg-white/10 border border-white/20 hover:border-white/40 rounded-2xl p-4 transition-all duration-200"
            >
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h4 class="text-base font-bold text-white truncate">{{ p.nombreParcela }}</h4>
                    <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold whitespace-nowrap" :class="ESTADO_BADGE[p.estado]">{{ p.estado }}</span>
                </div>
                <p class="text-xs text-white/60">
                    {{ p.codigo }} · {{ p.departamento }} / {{ p.municipio }}<br />
                    {{ p.areaHa }} ha · Registrado {{ p.fechaRegistro }}<br />
                    {{ p.fotos?.length ? `${p.fotos.length} foto(s)` : 'Sin fotos aún' }}
                </p>
                <div class="mt-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full border font-semibold" :class="VALIDACION_BADGE[p.estadoValidacion]">{{ p.estadoValidacion }}</span>
                </div>
                <p v-if="p.comentarioSupervisor" class="text-xs text-white/60 mt-3 bg-white/5 border border-white/15 rounded-xl p-2.5 flex items-start gap-1.5">
                    <MessageSquare class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" />
                    <span>{{ p.comentarioSupervisor }}</span>
                </p>
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import parcelaService from '../../services/parcelaService';
import { Plus, MessageSquare } from '@lucide/vue';

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

const parcelas = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await parcelaService.listar();
        parcelas.value = data.parcelas;
    } finally {
        loading.value = false;
    }
});
</script>
