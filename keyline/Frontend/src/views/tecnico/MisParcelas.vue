<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Mis parcelas</h1>
                <p class="text-sm text-slate-500">Parcelas que has registrado.</p>
            </div>
            <router-link :to="{ name: 'ParcelaNueva' }" class="btn-primary">+ Nueva parcela</router-link>
        </div>

        <div v-if="loading" class="py-12 text-center text-slate-400">Cargando…</div>

        <div v-else-if="!parcelas.length" class="bg-white rounded-lg shadow p-10 text-center">
            <p class="text-slate-600 mb-4">Aún no has registrado ninguna parcela.</p>
            <router-link :to="{ name: 'ParcelaNueva' }" class="btn-primary">+ Registrar mi primera parcela</router-link>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <router-link
                v-for="p in parcelas"
                :key="p.id"
                :to="{ name: 'ParcelaEditar', params: { id: p.id } }"
                class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow block"
            >
                <h3 class="font-bold text-slate-800">{{ p.nombreParcela }}</h3>
                <p class="text-xs text-slate-500 mt-1">{{ p.codigo }} · {{ p.departamento }} / {{ p.municipio }}</p>
                <p class="text-xs text-slate-500">{{ p.areaHa }} ha · Registrado {{ p.fechaRegistro }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ p.fotos?.length ? `📷 ${p.fotos.length} foto(s)` : 'Sin fotos aún' }}</p>
                <div class="flex gap-2 mt-3">
                    <span class="tag" :class="ESTADO_COLORS[p.estado]">{{ p.estado }}</span>
                    <span class="tag" :class="VALIDACION_COLORS[p.estadoValidacion]">{{ p.estadoValidacion }}</span>
                </div>
                <p v-if="p.comentarioSupervisor" class="text-xs text-slate-500 mt-2">💬 {{ p.comentarioSupervisor }}</p>
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import parcelaService from '../../services/parcelaService';
import { ESTADO_COLORS, VALIDACION_COLORS } from '../../constants/keyline';

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

<style scoped>
.btn-primary { @apply px-4 py-2 bg-primary-500 text-white rounded-md text-sm font-semibold hover:bg-primary-600; }
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full; }
</style>
