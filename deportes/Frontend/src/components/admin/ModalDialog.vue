<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { XIcon } from 'lucide-vue-next'

const props = defineProps({ title: String, busy: Boolean })
const emit = defineEmits(['close'])

const caja = ref(null)
let previo
let overflowPrevio

const cerrar = () => {
  if (!props.busy) emit('close')
}

const SELECTOR = 'button:not([disabled]),a[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex="0"]'

function teclado(e) {
  // Si hay una alerta de SweetAlert encima, ella maneja el teclado.
  if (document.querySelector('.swal2-container')) return
  if (e.key === 'Escape') {
    e.preventDefault()
    cerrar()
  }
  if (e.key === 'Tab') {
    const foco = [...caja.value.querySelectorAll(SELECTOR)].filter((el) => el.offsetParent !== null)
    if (!foco.length) return e.preventDefault()
    if (e.shiftKey && document.activeElement === foco[0]) {
      e.preventDefault()
      foco.at(-1).focus()
    } else if (!e.shiftKey && document.activeElement === foco.at(-1)) {
      e.preventDefault()
      foco[0].focus()
    }
  }
}

onMounted(async () => {
  previo = document.activeElement
  overflowPrevio = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  await nextTick()
  caja.value.querySelector('input,select,button')?.focus()
  document.addEventListener('keydown', teclado)
})

onBeforeUnmount(() => {
  document.body.style.overflow = overflowPrevio
  document.removeEventListener('keydown', teclado)
  previo?.focus()
})
</script>

<template>
  <Teleport to="body">
    <div class="modal-backdrop" @click.self="cerrar">
      <section ref="caja" class="modal" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <header class="modal-header">
          <h2 id="dialog-title">{{ title }}</h2>
          <button class="icon-btn" type="button" aria-label="Cerrar ventana" :disabled="busy" @click="cerrar"><XIcon :size="19" /></button>
        </header>
        <div class="modal-body"><slot /></div>
      </section>
    </div>
  </Teleport>
</template>
