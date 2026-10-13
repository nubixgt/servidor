<script setup>
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next'

const props = defineProps({
  pagina: { type: Number, required: true },
  totalPaginas: { type: Number, required: true },
  desde: Number,
  hasta: Number,
  total: Number,
})
const emit = defineEmits(['update:pagina'])

// 1 … 4 5 [6] 7 8 … 20: siempre la primera, la última y las vecinas de la actual.
const numeros = computed(() => {
  const n = props.totalPaginas
  const p = props.pagina
  const set = new Set([1, n, p - 1, p, p + 1].filter((x) => x >= 1 && x <= n))
  const orden = [...set].sort((a, b) => a - b)
  const salida = []
  orden.forEach((x, i) => {
    if (i && x - orden[i - 1] > 1) salida.push('…')
    salida.push(x)
  })
  return salida
})

const ir = (n) => emit('update:pagina', Math.min(Math.max(1, n), props.totalPaginas))
</script>

<template>
  <nav v-if="total" class="pagination" aria-label="Paginación">
    <span class="muted small">Mostrando {{ desde }}–{{ hasta }} de {{ total }}</span>
    <div v-if="totalPaginas > 1" class="pagination-pages">
      <button class="icon-btn" :disabled="pagina === 1" aria-label="Página anterior" @click="ir(pagina - 1)"><ChevronLeftIcon :size="15" /></button>
      <template v-for="(x, i) in numeros" :key="i">
        <span v-if="x === '…'" class="pagination-gap">…</span>
        <button v-else :class="{ active: x === pagina }" :aria-current="x === pagina ? 'page' : undefined" :aria-label="'Página ' + x" @click="ir(x)">{{ x }}</button>
      </template>
      <button class="icon-btn" :disabled="pagina === totalPaginas" aria-label="Página siguiente" @click="ir(pagina + 1)"><ChevronRightIcon :size="15" /></button>
    </div>
  </nav>
</template>
