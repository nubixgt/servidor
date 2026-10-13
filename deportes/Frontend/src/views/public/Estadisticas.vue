<script setup>
import { computed, ref } from 'vue'
import { usePulse } from '../../composables/usePulse'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'
import TeamBadge from '../../components/pulse/TeamBadge.vue'
import StandingsTable from '../../components/pulse/StandingsTable.vue'
import ScorersTable from '../../components/pulse/ScorersTable.vue'

const { state } = usePulse()
const tab = ref('standings')

const tabs = [
  ['standings', 'Posiciones'],
  ['goals', 'Goleadores'],
  ['assists', 'Asistencias'],
  ['yellow_cards', 'Amarillas'],
  ['red_cards', 'Rojas'],
]

const mejorAtaque = computed(() => [...state.standings].sort((a, b) => b.goals_for - a.goals_for)[0])
const mejorDefensa = computed(() =>
  [...state.standings].filter((f) => +f.played > 0).sort((a, b) => a.goals_against - b.goals_against)[0]
)
const records = computed(() => [
  [mejorAtaque.value, 'MEJOR ATAQUE', 'goals_for'],
  [mejorDefensa.value, 'MENOS GOLES RECIBIDOS', 'goals_against'],
])
</script>

<template>
  <div class="page-enter">
    <div class="page-heading photo-heading photo-stadium">
      <FootballBackdrop scene="stadium" />
      <p class="eyebrow">EL FÚTBOL, EN NÚMEROS</p>
      <h1>Cada dato cuenta<span class="lime">.</span></h1>
      <p>Posiciones, goleadores y los mejores de la temporada.</p>
    </div>

    <div class="records-grid">
      <div v-for="[fila, titulo, campo] in records" :key="campo" class="record panel">
        <TeamBadge v-if="fila" :team="fila" :size="64" />
        <div>
          <p class="eyebrow">{{ titulo }}</p>
          <h3>{{ fila?.name || 'Por definir' }}</h3>
          <p class="muted small">{{ fila?.played || 0 }} partidos jugados</p>
        </div>
        <strong>{{ fila?.[campo] ?? '—' }}</strong>
      </div>
    </div>

    <div class="tabs" aria-label="Tipo de ranking">
      <button v-for="[clave, nombre] in tabs" :key="clave" :class="{ active: tab === clave }" :aria-pressed="tab === clave" @click="tab = clave">
        {{ nombre }}
      </button>
    </div>

    <div class="panel">
      <StandingsTable v-if="tab === 'standings'" :rows="state.standings" />
      <ScorersTable v-else :players="state.players" :metric="tab" />
    </div>
    <p class="small muted mt-5">
      Solo partidos finalizados. Victoria: 3 pts · Empate: 1 pt. Desempate: puntos → DG → GF → nombre (orden provisional si persiste la igualdad).
    </p>
  </div>
</template>
