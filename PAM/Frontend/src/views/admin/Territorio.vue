<template>
    <VistaHeader eyebrow="Territorio" titulo="Análisis territorial" desc="Nacional → departamento → municipio. Seleccione un departamento en el mapa o en el ranking." />
    <MapCard big />
    <TerrCard />
</template>

<script setup>
import VistaHeader from '../../components/common/VistaHeader.vue';
import MapCard from '../../components/dashboard/MapCard.vue';
import TerrCard from '../../components/dashboard/TerrCard.vue';
</script>
