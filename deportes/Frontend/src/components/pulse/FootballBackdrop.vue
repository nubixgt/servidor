<script setup>
import stadium from '../../assets/images/stadium.webp'
import ball from '../../assets/images/ball.webp'
import team from '../../assets/images/team.webp'

const props = defineProps({ scene: { type: String, default: 'stadium' } })
const scenes = { stadium, ball, team }
</script>

<template>
  <span class="football-backdrop" aria-hidden="true">
    <img :src="scenes[props.scene] || stadium" :data-football-scene="props.scene" alt="" width="1942" height="809" decoding="async" />
  </span>
</template>
