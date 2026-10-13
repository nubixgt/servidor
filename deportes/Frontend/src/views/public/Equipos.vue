<script setup>
import { computed, ref } from 'vue'
import { ArrowUpRightIcon, MapPinIcon } from 'lucide-vue-next'
import { usePulse } from '../../composables/usePulse'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'
import TeamBadge from '../../components/pulse/TeamBadge.vue'

const { state } = usePulse()
const busqueda = ref('')

const equipos = computed(() =>
  state.teams.filter((t) => `${t.name} ${t.city || ''}`.toLowerCase().includes(busqueda.value.toLowerCase()))
)
const jugadoresDe = (id) => state.players.filter((j) => +j.team_id === +id).length
const puntosDe = (id) => state.standings.find((f) => +f.team_id === +id)?.points || 0
</script>

<template>
  <div class="page-enter">
    <div class="page-heading photo-heading photo-team">
      <FootballBackdrop scene="team" />
      <p class="eyebrow">LOS COLORES QUE NOS REPRESENTAN</p>
      <h1>Nuestros equipos<span class="lime">.</span></h1>
      <p>{{ state.teams.length }} clubes. Una misma pasión por el fútbol.</p>
    </div>

    <div class="filter-bar">
      <label class="search-field">
        Buscar equipo
        <input v-model="busqueda" placeholder="Nombre o ciudad…" type="search" />
      </label>
      <span class="muted">{{ equipos.length }} resultados</span>
    </div>

    <div class="team-grid">
      <RouterLink v-for="t in equipos" :key="t.id" :to="`/equipos/${t.id}`" class="club-card" :style="{ '--team-color': t.primary_color }">
        <div class="club-top"><span>{{ t.founded_year ? 'EST. ' + t.founded_year : 'EQUIPO' }}</span><ArrowUpRightIcon :size="22" /></div>
        <div class="club-emblem">
          <span>{{ t.short_name }}</span>
          <TeamBadge :team="t" :size="112" />
        </div>
        <h2>{{ t.name }}</h2>
        <p v-if="t.city" class="inline-row muted"><MapPinIcon :size="14" />{{ t.city }}</p>
        <div class="club-bottom">
          <span>{{ jugadoresDe(t.id) }} jugadores</span>
          <span>{{ puntosDe(t.id) }} puntos</span>
        </div>
      </RouterLink>
    </div>
    <div v-if="!equipos.length" class="empty panel">No hay equipos que coincidan con tu búsqueda.</div>
  </div>
</template>
