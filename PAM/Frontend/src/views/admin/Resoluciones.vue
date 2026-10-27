<template>
    <VistaHeader eyebrow="Gestión" titulo="Resoluciones" desc="Última resolución por familia y resoluciones emitidas por período." />
    <div class="grid g2e"><ResCard /><EvoCard :keys="['res']" title="Resoluciones emitidas" /></div>
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import EvoCard from '../../components/dashboard/EvoCard.vue';
import ResCard from '../../components/dashboard/ResCard.vue';
</script>
