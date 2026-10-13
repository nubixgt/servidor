<script setup>
import { computed } from 'vue'
import Paginacion from './Paginacion.vue'
import { usePaginacion } from '../../composables/usePaginacion'
import { imgUrl } from '../../services/api'

const props = defineProps({
  players: { type: Array, default: () => [] },
  metric: { type: String, default: 'goals' },
  limit: { type: Number, default: 100 },
  pageSize: { type: Number, default: 0 }, // > 0: por páginas (la numeración # continúa)
})

const etiquetas = { goals: 'Goles', assists: 'Asistencias', yellow_cards: 'Amarillas', red_cards: 'Rojas' }

const ranking = computed(() =>
  [...props.players]
    .sort((a, b) => +b[props.metric] - +a[props.metric] || a.full_name.localeCompare(b.full_name))
    .slice(0, props.limit)
)

const pag = usePaginacion(ranking, { por: props.pageSize || Number.MAX_SAFE_INTEGER, reiniciarCon: [() => props.metric] })
const filas = computed(() => (props.pageSize ? pag.visibles.value : ranking.value))
const desplazamiento = computed(() => (props.pageSize ? (pag.pagina.value - 1) * props.pageSize : 0))
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
        <tr v-for="(p, i) in filas" :key="p.id">
          <td class="muted">{{ desplazamiento + i + 1 }}</td>
          <td>
            <RouterLink class="table-person" :to="`/jugadores/${p.id}`">
              <img v-if="p.photo_url" :src="imgUrl(p.photo_url)" alt="" />
              <span>{{ p.full_name }}<small>{{ [p.position, p.jersey_number ? '#' + p.jersey_number : ''].filter(Boolean).join(' · ') }}</small></span>
            </RouterLink>
          </td>
          <td class="hide-mobile muted">{{ p.team_name }}</td>
          <td class="num"><strong class="score-chip">{{ p[metric] }}</strong></td>
        </tr>
        <tr v-if="!filas.length"><td colspan="4" class="empty">Todavía no hay jugadores registrados.</td></tr>
      </tbody>
    </table>
  </div>
  <Paginacion v-if="pageSize" v-model:pagina="pag.pagina.value" :total-paginas="pag.totalPaginas.value" :desde="pag.desde.value" :hasta="pag.hasta.value" :total="pag.total.value" />
</template>
