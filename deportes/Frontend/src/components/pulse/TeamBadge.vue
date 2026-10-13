<script setup>
defineProps({
  team: { type: Object, required: true },
  size: { type: Number, default: 42 },
})
</script>

<template>
  <span class="team-badge" :style="{ '--team-color': team.primary_color || '#c6ff3b', width: size + 'px', height: size + 'px' }">
    <img v-if="team.logo_url" :src="team.logo_url" :alt="'Escudo de ' + team.name" @error="$event.target.style.display = 'none'" />
    <span v-else>{{ (team.short_name || team.name || 'FC').slice(0, 3) }}</span>
  </span>
</template>
