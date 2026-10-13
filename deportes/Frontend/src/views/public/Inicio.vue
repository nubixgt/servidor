<script setup>
import { computed } from 'vue'
import { ArrowRightIcon, ArrowUpRightIcon, CalendarDaysIcon, TargetIcon, TrophyIcon } from 'lucide-vue-next'
import { usePulse } from '../../composables/usePulse'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'
import TeamBadge from '../../components/pulse/TeamBadge.vue'
import MatchCard from '../../components/pulse/MatchCard.vue'
import PlayerCard from '../../components/pulse/PlayerCard.vue'
import StandingsTable from '../../components/pulse/StandingsTable.vue'

const { state } = usePulse()

const goleadores = computed(() =>
  [...state.players].sort((a, b) => b.goals - a.goals || b.assists - a.assists).slice(0, 4)
)
const ultimos = computed(() => state.matches.filter((p) => p.status === 'finished').slice(0, 3))
const proximo = computed(() =>
  state.matches.filter((p) => p.status === 'scheduled').sort((a, b) => a.kickoff_at.localeCompare(b.kickoff_at))[0]
)
const totalGoles = computed(() => state.standings.reduce((suma, f) => suma + +f.goals_for, 0))
const lider = computed(() => state.standings[0])
</script>

<template>
  <div class="page-enter">
    <section class="hero football-hero">
      <FootballBackdrop scene="stadium" />
      <div class="hero-copy">
        <p class="eyebrow">
          <span class="status-dot"></span> {{ state.league.season }} <span class="eyebrow-sep">/</span> TEMPORADA EN MARCHA
        </p>
        <h1>EL JUEGO<br />NOS <span>UNE.</span></h1>
        <p class="hero-description">Cada jornada, una nueva historia.<br />Todos los equipos. Todos los protagonistas. Tu liga.</p>
        <div class="hero-actions">
          <RouterLink to="/partidos" class="btn btn-primary">Explorar partidos <ArrowUpRightIcon :size="20" /></RouterLink>
          <RouterLink to="/estadisticas" class="text-link">Ver posiciones <ArrowRightIcon :size="17" /></RouterLink>
        </div>
        <div class="hero-facts">
          <span><b>{{ state.teams.length.toString().padStart(2, '0') }}</b> EQUIPOS</span>
          <span><b>{{ state.players.length }}</b> JUGADORES</span>
          <span><b>{{ totalGoles }}</b> GOLES</span>
        </div>
      </div>

      <div class="hero-scene">
        <span class="scene-caption">BAJO LAS LUCES. CON TODA LA PASIÓN.</span>
        <RouterLink v-if="proximo" :to="`/partidos/${proximo.id}`" class="matchday-ticket">
          <span class="eyebrow"><span class="status-dot"></span> PRÓXIMA JORNADA</span>
          <strong>{{ proximo.home_short }} <span>VS</span> {{ proximo.away_short }}</strong>
          <span class="ticket-bottom">El próximo capítulo de nuestra liga <ArrowUpRightIcon :size="19" /></span>
        </RouterLink>
        <span v-else class="scene-wordmark" aria-hidden="true">MATCHDAY.</span>
      </div>
    </section>

    <section class="quick-strip">
      <div>
        <TrophyIcon :size="22" />
        <span>LÍDER DE LA LIGA<strong>{{ lider?.name || 'Por definir' }}</strong></span>
        <TeamBadge v-if="lider" :team="lider" :size="42" />
      </div>
      <div>
        <TargetIcon :size="22" />
        <span>MÁXIMO GOLEADOR<strong>{{ goleadores[0]?.full_name || 'Por definir' }}</strong></span>
        <b class="lime">{{ goleadores[0]?.goals || 0 }} <small>GOLES</small></b>
      </div>
      <div>
        <CalendarDaysIcon :size="22" />
        <span>PRÓXIMO ENCUENTRO<strong>{{ proximo ? `${proximo.home_short} vs ${proximo.away_short}` : 'Sin partidos programados' }}</strong></span>
        <RouterLink to="/partidos" class="circle-link" aria-label="Ver calendario"><ArrowUpRightIcon :size="20" /></RouterLink>
      </div>
    </section>

    <section class="section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">EL BALÓN NO SE DETIENE</p>
          <h2>Últimos resultados<span class="lime">.</span></h2>
        </div>
        <RouterLink to="/partidos" class="text-link">Todos los partidos <ArrowUpRightIcon :size="18" /></RouterLink>
      </div>
      <div class="match-grid">
        <MatchCard v-for="p in ultimos" :key="p.id" :match="p" />
        <p v-if="!ultimos.length" class="empty panel">Los resultados aparecerán al finalizar los partidos.</p>
      </div>
    </section>

    <section class="section football-stories" aria-label="Vive el fútbol de nuestra liga">
      <RouterLink to="/equipos" class="football-story story-team">
        <FootballBackdrop scene="team" />
        <div class="story-topline"><span>01 / NUESTRA GENTE</span><ArrowUpRightIcon :size="24" /></div>
        <div class="story-copy">
          <p class="eyebrow">MUCHO MÁS QUE ONCE JUGADORES</p>
          <h2>Los colores se llevan.<br />La pasión se comparte.</h2>
          <span class="story-link">Conoce a los equipos <ArrowRightIcon :size="17" /></span>
        </div>
      </RouterLink>
      <RouterLink to="/partidos" class="football-story story-ball">
        <FootballBackdrop scene="ball" />
        <div class="story-topline"><span>02 / EL DÍA DEL PARTIDO</span><ArrowUpRightIcon :size="24" /></div>
        <div class="story-copy">
          <p class="eyebrow">TODO EMPIEZA CON UN BALÓN</p>
          <h2>La siguiente historia<br />se juega en la cancha.</h2>
          <span class="story-link">Explora la jornada <ArrowRightIcon :size="17" /></span>
        </div>
      </RouterLink>
    </section>

    <section class="section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">TALENTO DE NUESTRA LIGA</p>
          <h2>Los protagonistas<span class="lime">.</span></h2>
        </div>
        <RouterLink to="/jugadores" class="text-link">Todos los jugadores <ArrowUpRightIcon :size="18" /></RouterLink>
      </div>
      <div class="player-grid">
        <PlayerCard v-for="(j, i) in goleadores" :key="j.id" :player="j" :featured="i === 0" />
      </div>
    </section>

    <section class="section standings-layout">
      <div class="section-side">
        <p class="eyebrow">CADA PUNTO CUENTA</p>
        <h2>La carrera<br />por la cima<span class="lime">.</span></h2>
        <p class="muted">Una liga. Una pasión.<br />Así se mueve la clasificación.</p>
        <RouterLink to="/estadisticas" class="btn btn-secondary">Estadísticas completas <ArrowUpRightIcon :size="18" /></RouterLink>
      </div>
      <div class="panel"><StandingsTable :rows="state.standings" compact /></div>
    </section>

    <div class="end-banner football-finale">
      <FootballBackdrop scene="team" />
      <span>TU EQUIPO. TU GENTE.<br /><b>TU LIGA.</b></span>
      <RouterLink to="/equipos" class="btn btn-primary">Conoce los equipos <ArrowUpRightIcon :size="20" /></RouterLink>
    </div>
  </div>
</template>
