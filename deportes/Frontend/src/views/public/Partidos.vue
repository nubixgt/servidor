<script setup>
import { computed, ref } from 'vue'
import { usePulse } from '../../composables/usePulse'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'
import MatchCard from '../../components/pulse/MatchCard.vue'

const { state } = usePulse()
const estado = ref('')
const equipo = ref('')
const jornada = ref('')

const jornadas = computed(() => [...new Set(state.matches.map((p) => +p.round_number))].sort((a, b) => a - b))

const partidos = computed(() =>
  state.matches
    .filter(
      (p) =>
        (!estado.value || p.status === estado.value) &&
        (!equipo.value || +p.home_team_id === +equipo.value || +p.away_team_id === +equipo.value) &&
        (!jornada.value || +p.round_number === +jornada.value)
    )
    .sort((a, b) => (estado.value === 'scheduled' ? a.kickoff_at.localeCompare(b.kickoff_at) : b.kickoff_at.localeCompare(a.kickoff_at)))
)
</script>

<template>
  <div class="page-enter">
    <div class="page-heading photo-heading photo-ball">
      <FootballBackdrop scene="ball" />
      <p class="eyebrow">NOS VEMOS EN LA CANCHA</p>
      <h1>El calendario<span class="lime">.</span></h1>
      <p>Próximos encuentros y resultados. Horario de Guatemala (UTC−6).</p>
    </div>

    <div class="filter-bar">
      <label>
        Estado
        <select v-model="estado">
          <option value="">Todos los partidos</option>
          <option value="scheduled">Programados</option>
          <option value="live">En juego</option>
          <option value="finished">Finalizados</option>
        </select>
      </label>
      <label>
        Equipo
        <select v-model="equipo">
          <option value="">Todos los equipos</option>
          <option v-for="t in state.teams" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </label>
      <label>
        Jornada
        <select v-model="jornada">
          <option value="">Todas las jornadas</option>
          <option v-for="j in jornadas" :key="j" :value="j">Jornada {{ j }}</option>
        </select>
      </label>
      <span class="muted">{{ partidos.length }} partidos</span>
    </div>

    <div class="match-grid"><MatchCard v-for="p in partidos" :key="p.id" :match="p" /></div>
    <p v-if="!partidos.length" class="empty panel">No hay partidos que coincidan con esos filtros.</p>
  </div>
</template>
