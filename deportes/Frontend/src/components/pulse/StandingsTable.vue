<script setup>
import TeamBadge from './TeamBadge.vue'

defineProps({
  rows: { type: Array, default: () => [] },
  compact: Boolean,
})
</script>

<template>
  <div class="table-scroll">
    <table class="data-table standings">
      <caption class="sr-only">Tabla de posiciones: puntos, diferencia de goles y goles a favor</caption>
      <thead>
        <tr>
          <th>#</th>
          <th>Equipo</th>
          <th>PJ</th>
          <template v-if="!compact">
            <th>PG</th><th>PE</th><th>PP</th><th>GF</th><th>GC</th>
          </template>
          <th>DG</th>
          <th class="num">Pts</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, i) in rows" :key="row.team_id" :class="{ leader: i === 0 }">
          <td>{{ i + 1 }}</td>
          <td>
            <RouterLink :to="`/equipos/${row.team_id}`" class="table-person">
              <TeamBadge :team="row" :size="30" />
              <b>{{ row.name }}</b>
            </RouterLink>
          </td>
          <td>{{ row.played }}</td>
          <template v-if="!compact">
            <td>{{ row.won }}</td><td>{{ row.drawn }}</td><td>{{ row.lost }}</td><td>{{ row.goals_for }}</td><td>{{ row.goals_against }}</td>
          </template>
          <td>{{ +row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}</td>
          <td class="num"><strong>{{ row.points }}</strong></td>
        </tr>
        <tr v-if="!rows.length"><td colspan="10" class="empty">Aún no hay equipos.</td></tr>
      </tbody>
    </table>
  </div>
</template>
