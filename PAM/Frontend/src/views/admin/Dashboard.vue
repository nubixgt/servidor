<template>
    <VistaHeader eyebrow="Programa del Adulto Mayor" titulo="Centro de Monitoreo Nacional" />
    <KpiRow />
    <div class="grid g2"><MapCard /><EstadoCard /></div>
    <div class="grid g2e"><FlowCard /><EvoCard /></div>
    <TimesCard />
    <div class="grid g2e"><AntigCard /><AgeCard /></div>
    <div class="grid g2e"><AlertsCard /><TerrCard /></div>
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import AgeCard from '../../components/dashboard/AgeCard.vue';
import AlertsCard from '../../components/dashboard/AlertsCard.vue';
import AntigCard from '../../components/dashboard/AntigCard.vue';
import EstadoCard from '../../components/dashboard/EstadoCard.vue';
import EvoCard from '../../components/dashboard/EvoCard.vue';
import FlowCard from '../../components/dashboard/FlowCard.vue';
import KpiRow from '../../components/dashboard/KpiRow.vue';
import MapCard from '../../components/dashboard/MapCard.vue';
import TerrCard from '../../components/dashboard/TerrCard.vue';
import TimesCard from '../../components/dashboard/TimesCard.vue';
</script>
