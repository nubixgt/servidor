<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePulse } from '../../composables/usePulse'
import TeamBadge from '../../components/pulse/TeamBadge.vue'
import PlayerCard from '../../components/pulse/PlayerCard.vue'
import MatchCard from '../../components/pulse/MatchCard.vue'

const { state } = usePulse()
const route = useRoute()

const equipo = computed(() => state.teams.find((t) => +t.id === +route.params.id))
const plantilla = computed(() => state.players.filter((j) => +j.team_id === +route.params.id))
const partidos = computed(() =>
  state.matches.filter((p) => +p.home_team_id === +route.params.id || +p.away_team_id === +route.params.id)
)
const fila = computed(() => state.standings.find((f) => +f.team_id === +route.params.id))

const resumen = [
  ['points', 'Puntos'],
  ['played', 'Partidos jugados'],
  ['goals_for', 'Goles a favor'],
  ['goals_against', 'Goles en contra'],
]
</script>

<template>
  <div v-if="equipo" class="page-enter">
    <RouterLink to="/equipos" class="back-link">← Todos los equipos</RouterLink>

    <section class="profile-header" :style="{ '--team-color': equipo.primary_color }">
      <TeamBadge :team="equipo" :size="135" />
      <div>
        <p class="eyebrow">{{ [equipo.city, equipo.founded_year ? 'DESDE ' + equipo.founded_year : ''].filter(Boolean).join(' · ') || 'EQUIPO DE LA LIGA' }}</p>
        <h1>{{ equipo.name }}</h1>
        <p v-if="equipo.stadium || equipo.coach" class="muted">{{ [equipo.stadium, equipo.coach ? 'Entrenador: ' + equipo.coach : ''].filter(Boolean).join(' · ') }}</p>
      </div>
    </section>

    <div class="stat-grid">
      <div v-for="[campo, etiqueta] in resumen" :key="campo" class="stat-box">
        <strong>{{ fila?.[campo] || 0 }}</strong>
        <span>{{ etiqueta }}</span>
      </div>
    </div>

    <section class="section">
      <div class="section-heading">
        <h2>La plantilla<span class="lime">.</span><small>{{ plantilla.length }} jugadores</small></h2>
      </div>
      <div class="player-grid"><PlayerCard v-for="j in plantilla" :key="j.id" :player="j" /></div>
      <p v-if="!plantilla.length" class="empty panel">Este equipo aún no tiene jugadores.</p>
    </section>

    <section class="section">
      <h2 class="mb-6">Sus partidos</h2>
      <div class="match-grid"><MatchCard v-for="p in partidos" :key="p.id" :match="p" /></div>
      <p v-if="!partidos.length" class="empty panel">No hay partidos asignados.</p>
    </section>
  </div>
  <div v-else class="empty">Equipo no encontrado.</div>
</template>
