<script setup>
import { computed, ref } from 'vue'
import { usePulse } from '../../composables/usePulse'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'
import PlayerCard from '../../components/pulse/PlayerCard.vue'

const { state } = usePulse()
const busqueda = ref('')
const equipo = ref('')
const posicion = ref('')
const orden = ref('goals')

const sinAcentos = (txt) => txt.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '')

const jugadores = computed(() =>
  state.players
    .filter(
      (j) =>
        (!equipo.value || +j.team_id === +equipo.value) &&
        (!posicion.value || j.position === posicion.value) &&
        sinAcentos(j.full_name).includes(sinAcentos(busqueda.value))
    )
    .sort((a, b) => (orden.value === 'name' ? a.full_name.localeCompare(b.full_name) : +b[orden.value] - +a[orden.value]))
)

const limpiar = () => {
  busqueda.value = ''
  equipo.value = ''
  posicion.value = ''
}
</script>

<template>
  <div class="page-enter">
    <div class="page-heading photo-heading photo-team">
      <FootballBackdrop scene="team" />
      <p class="eyebrow">EL TALENTO TIENE NOMBRE</p>
      <h1>Los protagonistas<span class="lime">.</span></h1>
      <p>Conoce a quienes hacen grande cada jornada.</p>
    </div>

    <div class="filter-bar">
      <label class="search-field">
        Buscar jugador
        <input v-model="busqueda" type="search" placeholder="Nombre del jugador…" />
      </label>
      <label>
        Equipo
        <select v-model="equipo">
          <option value="">Todos los equipos</option>
          <option v-for="t in state.teams" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </label>
      <label>
        Posición
        <select v-model="posicion">
          <option value="">Todas las posiciones</option>
          <option value="POR">Portero</option>
          <option value="DEF">Defensa</option>
          <option value="MED">Mediocampista</option>
          <option value="DEL">Delantero</option>
        </select>
      </label>
      <label>
        Ordenar por
        <select v-model="orden">
          <option value="goals">Más goles</option>
          <option value="assists">Más asistencias</option>
          <option value="name">Nombre A–Z</option>
        </select>
      </label>
    </div>

    <p class="results-count">{{ jugadores.length }} JUGADORES ENCONTRADOS</p>
    <div class="player-grid"><PlayerCard v-for="j in jugadores" :key="j.id" :player="j" /></div>
    <div v-if="!jugadores.length" class="empty panel">
      No encontramos jugadores con esos filtros.
      <button class="text-link" @click="limpiar">Limpiar filtros</button>
    </div>
  </div>
</template>
