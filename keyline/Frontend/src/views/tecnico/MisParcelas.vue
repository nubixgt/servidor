<template>
    <div>
        <div style="display: flex; justify-content: flex-end;">
            <router-link :to="{ name: 'ParcelaNueva' }" class="btn btn-primary">+ Nueva parcela</router-link>
        </div>

        <div v-if="loading" class="py-12 text-center hint">Cargando…</div>

        <div v-else-if="!parcelas.length" class="panel glass empty-state">
            <p style="margin-bottom: 16px;">Aún no has registrado ninguna parcela.</p>
            <router-link :to="{ name: 'ParcelaNueva' }" class="btn btn-primary">+ Registrar mi primera parcela</router-link>
        </div>

        <div v-else class="parcela-card-list">
            <router-link
                v-for="p in parcelas"
                :key="p.id"
                :to="{ name: 'ParcelaEditar', params: { id: p.id } }"
                class="parcela-card"
            >
                <h4>{{ p.nombreParcela }}</h4>
                <div class="pc-meta">
                    {{ p.codigo }} · {{ p.departamento }} / {{ p.municipio }}<br />
                    {{ p.areaHa }} ha · Registrado {{ p.fechaRegistro }}<br />
                    {{ p.fotos?.length ? `📷 ${p.fotos.length} foto(s)` : 'Sin fotos aún' }}
                </div>
                <div class="pc-tags">
                    <span class="tag" :class="ESTADO_COLORS[p.estado]">{{ p.estado }}</span>
                    <span class="tag" :class="VALIDACION_COLORS[p.estadoValidacion]">{{ p.estadoValidacion }}</span>
                </div>
                <p v-if="p.comentarioSupervisor" class="hint" style="margin-top: 10px;">💬 {{ p.comentarioSupervisor }}</p>
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
