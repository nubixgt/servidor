import { computed, ref, watch } from 'vue'

// Pagina una lista (computed/ref) de `por` en `por`. `reiniciarCon`: fuentes (filtros) que devuelven a la página 1.
export function usePaginacion(lista, { por = 20, reiniciarCon = [] } = {}) {
  const pagina = ref(1)
  const total = computed(() => lista.value.length)
  const totalPaginas = computed(() => Math.max(1, Math.ceil(total.value / por)))

  watch(reiniciarCon, () => (pagina.value = 1))
  // Si al borrar queda menos de una página, vuelve a la última que exista.
  watch(totalPaginas, (n) => {
    if (pagina.value > n) pagina.value = n
  })

  const visibles = computed(() => lista.value.slice((pagina.value - 1) * por, pagina.value * por))
  const desde = computed(() => (total.value ? (pagina.value - 1) * por + 1 : 0))
  const hasta = computed(() => Math.min(pagina.value * por, total.value))

  return { pagina, totalPaginas, visibles, desde, hasta, total }
}
