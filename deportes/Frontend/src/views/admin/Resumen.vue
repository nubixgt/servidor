<script setup>
import { computed } from 'vue'
import { usePanel } from '../../composables/usePanel'
import { useAuth } from '../../composables/useAuth'
import AdminHeading from '../../components/admin/AdminHeading.vue'

const { state } = usePanel()
const { sesion } = useAuth()

const r = computed(() => state.resumen)
const esAdmin = computed(() => sesion.rol === 'admin')
const tarjetas = computed(() => [
  [esAdmin.value ? 'Equipos' : 'Mi equipo', r.value.teams],
  [esAdmin.value ? 'Jugadores activos' : 'Mis jugadores activos', r.value.players],
  [esAdmin.value ? 'Partidos' : 'Mis partidos', r.value.matches],
  ['Finalizados', r.value.finished],
])
</script>

<template>
  <div class="page-enter">
    <AdminHeading title="Resumen" />

    <div class="stat-grid">
      <div v-for="[nombre, valor] in tarjetas" :key="nombre" class="stat-box"><strong>{{ valor }}</strong><span>{{ nombre }}</span></div>
    </div>

    <p class="notice info">
      <template v-if="esAdmin">Registra los goles y tarjetas desde Partidos → Eventos. El marcador es automático; al finalizar el partido se actualizan los rankings.</template>
      <template v-else>Registra el resultado de tus partidos y sus eventos desde «Mis partidos». El marcador se calcula solo a partir de los goles.</template>
    </p>

    <div class="section-heading">
      <h2>Goleadores de la liga</h2>
      <RouterLink to="/estadisticas" class="text-link">Ver vista pública ↗</RouterLink>
    </div>
    <div class="panel table-scroll">
      <table class="data-table">
        <thead><tr><th>#</th><th>Jugador</th><th class="hide-mobile">Equipo</th><th class="num">Goles</th></tr></thead>
        <tbody>
          <tr v-for="(j, i) in r.top_scorers" :key="j.id">
            <td class="muted">{{ i + 1 }}</td>
            <td>{{ j.full_name }}</td>
            <td class="hide-mobile muted">{{ j.team_name }}</td>
            <td class="num"><strong class="score-chip">{{ j.goals }}</strong></td>
          </tr>
          <tr v-if="!r.top_scorers.length"><td colspan="4" class="empty">Aún no hay goles en partidos finalizados.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
