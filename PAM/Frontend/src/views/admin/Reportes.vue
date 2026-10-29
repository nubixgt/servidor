<template>
    <VistaHeader eyebrow="Control" titulo="Reportes" desc="Reportes por DPI y por departamento y municipio en Excel, dentro de su ámbito territorial. Toda descarga queda en la bitácora." />
    <div class="card">
        <div class="subtabs">
            <button v-for="[k, l] in tabs" :key="k" :class="{ on: tab === k }" @click="tab = k">{{ l }}</button>
        </div>
        <ReporteDpi v-if="tab === 'dpi'" />
        <ReporteGeografico v-else-if="tab === 'geografico'" />
        <ReporteBitacora v-else-if="tab === 'bitacora'" />
        <ReporteTablero v-else-if="tab === 'tablero'" />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import VistaHeader from '../../components/common/VistaHeader.vue';
import ReporteBitacora from '../../components/reportes/ReporteBitacora.vue';
import ReporteDpi from '../../components/reportes/ReporteDpi.vue';
import ReporteGeografico from '../../components/reportes/ReporteGeografico.vue';
import ReporteTablero from '../../components/reportes/ReporteTablero.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const tab = ref('dpi');
const tabs = computed(() => [
    ['dpi', 'Reporte por DPI'],
    ['geografico', 'Reporte por Departamento y Municipio'],
    ['tablero', 'Reportes del tablero'],
    ...(auth.puede('auditoria.ver') ? [['bitacora', 'Bitácora']] : [])
]);
</script>
