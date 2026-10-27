<template>
    <VistaHeader eyebrow="Alertas" titulo="Requieren atención" :desc="`${RULES.length} reglas automáticas. Cada una abre el listado de casos con el filtro aplicado.`" />
    <div class="card"><AlertRows /></div>
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import AlertRows from '../../components/dashboard/AlertRows.vue';
import { RULES } from '../../utils/reglas';
</script>
