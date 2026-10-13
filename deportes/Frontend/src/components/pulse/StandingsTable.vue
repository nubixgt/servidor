<script setup>
import { computed, toRef } from 'vue'
import TeamBadge from './TeamBadge.vue'
import Paginacion from './Paginacion.vue'
import { usePaginacion } from '../../composables/usePaginacion'

// `pageSize` > 0: muestra la tabla por páginas (la numeración # continúa entre páginas).
const props = defineProps({
  rows: { type: Array, default: () => [] },
  compact: Boolean,
  pageSize: { type: Number, default: 0 },
})

const pag = usePaginacion(toRef(props, 'rows'), { por: props.pageSize || Number.MAX_SAFE_INTEGER })
const filas = computed(() => (props.pageSize ? pag.visibles.value : props.rows))
const desplazamiento = computed(() => (props.pageSize ? (pag.pagina.value - 1) * props.pageSize : 0))
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
        <tr v-for="(row, i) in filas" :key="row.team_id" :class="{ leader: desplazamiento + i === 0 }">
          <td>{{ desplazamiento + i + 1 }}</td>
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
  <Paginacion v-if="pageSize" v-model:pagina="pag.pagina.value" :total-paginas="pag.totalPaginas.value" :desde="pag.desde.value" :hasta="pag.hasta.value" :total="pag.total.value" />
</template>
