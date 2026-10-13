<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePulse } from '../../composables/usePulse'
import { EVENT_LABEL } from '../../utils/pulseFormat'
import MatchCard from '../../components/pulse/MatchCard.vue'

const { state, eventosDePartido } = usePulse()
const route = useRoute()

const partido = computed(() => state.matches.find((p) => +p.id === +route.params.id))
const eventos = computed(() => eventosDePartido(route.params.id))
</script>

<template>
  <div class="page-enter">
    <RouterLink to="/partidos" class="back-link">← Calendario</RouterLink>

    <template v-if="partido">
      <div class="match-detail">
        <p class="eyebrow">CENTRO DEL PARTIDO</p>
        <h1>{{ partido.home_name }} <span class="muted">vs</span> {{ partido.away_name }}</h1>
        <MatchCard :match="partido" />
      </div>

      <section class="section panel timeline">
        <h2>Así se vivió el partido<span class="lime">.</span></h2>
        <div v-for="e in eventos" :key="e.id" class="timeline-event">
          <strong>{{ e.minute }}′</strong>
          <span class="event-dot" :class="e.type"></span>
          <div>
            <b>{{ EVENT_LABEL[e.type] }} · <RouterLink :to="`/jugadores/${e.player_id}`">{{ e.player_name }}</RouterLink></b>
            <p class="muted small">{{ e.team_name }}<template v-if="e.assist_name"> · Asistencia: {{ e.assist_name }}</template></p>
          </div>
        </div>
        <p v-if="!eventos.length" class="empty">
          {{ partido.status === 'scheduled' ? 'El partido todavía no comienza.' : 'Aún no se han registrado eventos.' }}
        </p>
      </section>
    </template>
    <p v-else class="empty">Partido no encontrado.</p>
  </div>
</template>
