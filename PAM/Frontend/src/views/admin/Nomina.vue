<template>
    <VistaHeader eyebrow="Gestión" titulo="Nómina" desc="Beneficiarios activos, ingresos y salidas por período, distribución territorial y por edad." />
    <div class="grid g2e"><EvoCard :keys="['ing', 'sal']" title="Ingresos y salidas de nómina" /><AgeCard /></div>
    <TerrCard />
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import AgeCard from '../../components/dashboard/AgeCard.vue';
import EvoCard from '../../components/dashboard/EvoCard.vue';
import TerrCard from '../../components/dashboard/TerrCard.vue';
</script>
