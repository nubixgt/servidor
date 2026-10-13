<script setup>
import { computed } from 'vue'

const props = defineProps({
  players: { type: Array, default: () => [] },
  metric: { type: String, default: 'goals' },
  limit: { type: Number, default: 100 },
})

const etiquetas = { goals: 'Goles', assists: 'Asistencias', yellow_cards: 'Amarillas', red_cards: 'Rojas' }

const ranking = computed(() =>
  [...props.players]
    .sort((a, b) => +b[props.metric] - +a[props.metric] || a.full_name.localeCompare(b.full_name))
    .slice(0, props.limit)
)
</script>

<template>
  <div class="table-scroll">
    <table class="data-table">
      <caption class="sr-only">Ranking de {{ etiquetas[metric] }}</caption>
      <thead>
        <tr>
          <th>#</th>
          <th>Jugador</th>
          <th class="hide-mobile">Equipo</th>
          <th class="num">{{ etiquetas[metric] }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(p, i) in ranking" :key="p.id">
          <td class="muted">{{ i + 1 }}</td>
          <td>
            <RouterLink class="table-person" :to="`/jugadores/${p.id}`">
              <img v-if="p.photo_url" :src="p.photo_url" alt="" />
              <span>{{ p.full_name }}<small>{{ p.position }} · #{{ p.jersey_number }}</small></span>
            </RouterLink>
          </td>
          <td class="hide-mobile muted">{{ p.team_name }}</td>
          <td class="num"><strong class="score-chip">{{ p[metric] }}</strong></td>
        </tr>
        <tr v-if="!ranking.length"><td colspan="4" class="empty">Todavía no hay jugadores registrados.</td></tr>
      </tbody>
    </table>
  </div>
</template>
