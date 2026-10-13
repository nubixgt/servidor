<script setup>
import TeamBadge from './TeamBadge.vue'
import { STATUS_LABEL, formatFecha } from '../../utils/pulseFormat'

defineProps({ match: { type: Object, required: true } })
</script>

<template>
  <RouterLink :to="`/partidos/${match.id}`" class="match-card">
    <div class="match-meta">
      <span>JORNADA {{ match.round_number }}</span>
      <span :class="{ live: match.status === 'live' }">{{ STATUS_LABEL[match.status] }}</span>
    </div>
    <div class="match-line">
      <div class="match-team">
        <TeamBadge :team="{ name: match.home_name, logo_url: match.home_logo, short_name: match.home_short, primary_color: match.home_color }" />
        <strong>{{ match.home_short }}</strong>
      </div>
      <div class="match-score">
        <template v-if="match.status !== 'scheduled'">{{ match.home_score }} <span>:</span> {{ match.away_score }}</template>
        <span v-else class="versus">VS</span>
      </div>
      <div class="match-team">
        <TeamBadge :team="{ name: match.away_name, logo_url: match.away_logo, short_name: match.away_short, primary_color: match.away_color }" />
        <strong>{{ match.away_short }}</strong>
      </div>
    </div>
    <div class="match-footer">{{ [formatFecha(match.kickoff_at, true), match.stadium].filter(Boolean).join(' · ') }}</div>
  </RouterLink>
</template>
