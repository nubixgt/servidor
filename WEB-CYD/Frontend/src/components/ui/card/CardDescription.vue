<script setup>
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  class: {
    type: [String, Object, Array],
    default: '',
  }
})
</script>

<template>
  <div :class="cn('text-muted-foreground text-sm', props.class)" v-bind="$attrs">
    <slot />
  </div>
</template>
