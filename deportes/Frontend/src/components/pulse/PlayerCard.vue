<script setup>
import { ArrowUpRightIcon } from 'lucide-vue-next'
import { imgUrl } from '../../services/api'

defineProps({
  player: { type: Object, required: true },
  featured: Boolean,
})
</script>

<template>
  <RouterLink
    :to="`/jugadores/${player.id}`"
    class="player-card"
    :class="{ featured }"
    :style="{ '--team-color': player.primary_color }"
    :aria-label="`Ver perfil de ${player.full_name}`"
  >
    <div class="card-art">
      <div class="card-grid"></div>
      <span class="card-season">PULSE / 26</span>
      <div class="card-number">
        <strong>{{ player.jersey_number }}</strong>
        <span>{{ player.position }}</span>
      </div>
      <img v-if="player.photo_url" :src="imgUrl(player.photo_url)" :alt="player.full_name" loading="lazy" @error="$event.target.style.visibility = 'hidden'" />
      <div v-else class="portrait-fallback">{{ player.full_name.split(' ').map((n) => n[0]).slice(0, 2).join('') }}</div>
      <span class="card-club">{{ player.short_name }}</span>
    </div>
    <div class="card-info">
      <p class="eyebrow">{{ player.team_name }}</p>
      <h3>{{ player.full_name }}</h3>
      <div class="card-stats">
        <span><b>{{ player.goals }}</b>GOL</span>
        <span><b>{{ player.assists }}</b>ASI</span>
        <span><b>{{ player.age }}</b>AÑOS</span>
      </div>
      <div class="card-bottom">
        <span>
          {{ player.nationality }} ·
          <span title="Tarjetas amarillas">🟨 {{ player.yellow_cards }}</span>
          <span title="Tarjetas rojas">🟥 {{ player.red_cards }}</span>
        </span>
        <ArrowUpRightIcon :size="17" />
      </div>
    </div>
  </RouterLink>
</template>
