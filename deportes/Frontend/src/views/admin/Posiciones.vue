<script setup>
import { computed, onMounted } from 'vue'
import { usePulse } from '../../composables/usePulse'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import StandingsTable from '../../components/pulse/StandingsTable.vue'

const { state, load } = usePulse()
onMounted(load)

// `standings` ya viene ordenado (puntos → DG → GF → nombre); al filtrar por categoría se conserva el orden.
const categorias = computed(() => ['A', 'B'].map((c) => [c, state.standings.filter((f) => f.category === c)]))
</script>

<template>
  <div class="page-enter">
    <AdminHeading title="Posiciones" />
    <p v-if="state.error" class="notice error">{{ state.error }}</p>

    <section v-for="[cat, filas] in categorias" :key="cat" class="mb-6">
      <div class="section-heading"><h2>Categoría {{ cat }}</h2></div>
      <div class="panel"><StandingsTable :rows="filas" :page-size="20" /></div>
    </section>

    <p class="small muted mt-5">
      Solo partidos finalizados. Victoria: 3 pts · Empate: 1 pt. Desempate: puntos → DG → GF → nombre.
    </p>
  </div>
</template>
