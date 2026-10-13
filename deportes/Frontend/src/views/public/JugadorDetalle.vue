<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePulse } from '../../composables/usePulse'
import PlayerCard from '../../components/pulse/PlayerCard.vue'

const { state } = usePulse()
const route = useRoute()

const jugador = computed(() => state.players.find((j) => +j.id === +route.params.id))

const resumen = [
  ['goals', 'Goles'],
  ['assists', 'Asistencias'],
  ['yellow_cards', 'Amarillas'],
  ['red_cards', 'Rojas'],
]
</script>

<template>
  <div v-if="jugador" class="page-enter">
    <RouterLink to="/jugadores" class="back-link">← Todos los jugadores</RouterLink>

    <section class="player-profile">
      <PlayerCard :player="jugador" featured />
      <div class="profile-details">
        <p class="eyebrow">{{ jugador.position }} · DORSAL {{ jugador.jersey_number }}</p>
        <h1>{{ jugador.full_name }}<span class="lime">.</span></h1>
        <RouterLink class="text-link" :to="`/equipos/${jugador.team_id}`">{{ jugador.team_name }} ↗</RouterLink>

        <div class="stat-grid">
          <div v-for="[campo, etiqueta] in resumen" :key="campo" class="stat-box">
            <strong>{{ jugador[campo] }}</strong>
            <span>{{ etiqueta }}</span>
          </div>
        </div>

        <dl class="bio-grid">
          <div><dt>Nacionalidad</dt><dd>{{ jugador.nationality }}</dd></div>
          <div><dt>Edad</dt><dd>{{ jugador.age }} años</dd></div>
          <div><dt>Nacimiento</dt><dd>{{ jugador.birth_date.split('-').reverse().join('/') }}</dd></div>
          <div><dt>Altura</dt><dd>{{ jugador.height_cm }} cm</dd></div>
          <div><dt>Peso</dt><dd>{{ jugador.weight_kg }} kg</dd></div>
          <div><dt>Pie hábil</dt><dd>{{ jugador.preferred_foot }}</dd></div>
        </dl>
        <p class="muted small">Estadísticas oficiales de partidos finalizados en {{ state.league.season }}.</p>
      </div>
    </section>
  </div>
  <div v-else class="empty">Jugador no encontrado.</div>
</template>
