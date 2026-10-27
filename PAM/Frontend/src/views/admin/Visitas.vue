<template>
    <VistaHeader eyebrow="Gestión" titulo="Visitas" desc="Visitas registradas y tiempos del proceso. Las visitas se registran desde el Expediente 360°." />
    <div class="grid g2e"><EvoCard :keys="['vpos', 'vneg']" title="Visitas registradas" /><TimesCard /></div>
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import EvoCard from '../../components/dashboard/EvoCard.vue';
import TimesCard from '../../components/dashboard/TimesCard.vue';
</script>
