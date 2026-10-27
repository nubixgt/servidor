<template>
    <VistaHeader eyebrow="Gestión" titulo="Estados" desc="Estado actual, suspendidos, fallecidos, bloqueados y sus transiciones." />
    <div class="grid g2e"><EstadoCard /><ResCard /></div>
    <EvoCard :keys="['susp', 'fall']" title="Suspensiones y fallecimientos registrados" />
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import EstadoCard from '../../components/dashboard/EstadoCard.vue';
import EvoCard from '../../components/dashboard/EvoCard.vue';
import ResCard from '../../components/dashboard/ResCard.vue';
</script>
