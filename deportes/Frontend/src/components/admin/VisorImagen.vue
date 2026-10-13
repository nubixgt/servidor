<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { XIcon, ZoomInIcon, ZoomOutIcon, RotateCcwIcon } from 'lucide-vue-next'
import { useVisorImagen } from '../../composables/useVisorImagen'

// Visor a pantalla completa estilo WhatsApp: rueda/botones/pellizco para zoom, arrastrar para mover,
// doble clic para acercar, Esc o clic fuera de la foto para cerrar.
const { visor, cerrarVisor } = useVisorImagen()

const MIN = 1
const MAX = 5
const escala = ref(1)
const x = ref(0)
const y = ref(0)
const arrastrando = ref(false)
const fallo = ref(false)
const cerrarBtn = ref(null)

const limitar = (v) => Math.min(MAX, Math.max(MIN, v))
function fijarEscala(v) {
  escala.value = limitar(v)
  if (escala.value === MIN) x.value = y.value = 0
}
const reiniciar = () => {
  escala.value = 1
  x.value = y.value = 0
}
const acercar = () => fijarEscala(escala.value * 1.4)
const alejar = () => fijarEscala(escala.value / 1.4)

const rueda = (e) => fijarEscala(escala.value * (e.deltaY < 0 ? 1.15 : 1 / 1.15))
const dobleClic = () => (escala.value > 1 ? reiniciar() : fijarEscala(2.5))

// Punteros (ratón y táctil): uno arrastra, dos hacen pellizco.
const punteros = new Map()
let inicio = null
let distancia0 = 0
let escala0 = 1
const distancia = () => {
  const [a, b] = [...punteros.values()]
  return Math.hypot(a.x - b.x, a.y - b.y)
}

function bajar(e) {
  e.target.setPointerCapture?.(e.pointerId)
  punteros.set(e.pointerId, { x: e.clientX, y: e.clientY })
  if (punteros.size === 2) {
    distancia0 = distancia()
    escala0 = escala.value
  } else {
    inicio = { sx: e.clientX, sy: e.clientY, ox: x.value, oy: y.value }
    arrastrando.value = escala.value > 1
  }
}
function mover(e) {
  if (!punteros.has(e.pointerId)) return
  punteros.set(e.pointerId, { x: e.clientX, y: e.clientY })
  if (punteros.size === 2) {
    fijarEscala(escala0 * (distancia() / distancia0))
  } else if (inicio && escala.value > 1) {
    x.value = inicio.ox + (e.clientX - inicio.sx)
    y.value = inicio.oy + (e.clientY - inicio.sy)
  }
}
function soltar(e) {
  punteros.delete(e.pointerId)
  if (!punteros.size) {
    inicio = null
    arrastrando.value = false
  }
}

const estilo = computed(() => ({
  transform: `translate(${x.value}px, ${y.value}px) scale(${escala.value})`,
  cursor: escala.value > 1 ? (arrastrando.value ? 'grabbing' : 'grab') : 'zoom-in',
  transition: arrastrando.value || punteros.size ? 'none' : 'transform .18s ease',
}))

function teclado(e) {
  if (e.key === 'Escape') cerrarVisor()
  else if (e.key === '+' || e.key === '=') acercar()
  else if (e.key === '-') alejar()
  else if (e.key === '0') reiniciar()
}

let overflowPrevio = ''
watch(
  () => visor.abierta,
  (abierta) => {
    if (abierta) {
      reiniciar()
      fallo.value = false
      overflowPrevio = document.body.style.overflow
      document.body.style.overflow = 'hidden'
      document.addEventListener('keydown', teclado)
      setTimeout(() => cerrarBtn.value?.focus(), 0)
    } else {
      document.body.style.overflow = overflowPrevio
      document.removeEventListener('keydown', teclado)
    }
  }
)
onBeforeUnmount(() => {
  document.removeEventListener('keydown', teclado)
  if (visor.abierta) document.body.style.overflow = overflowPrevio
})
</script>

<template>
  <Teleport to="body">
    <div v-if="visor.abierta" class="visor-imagen" role="dialog" aria-modal="true" :aria-label="visor.titulo || 'Foto'">
      <header class="visor-barra">
        <strong>{{ visor.titulo }}</strong>
        <div class="visor-acciones">
          <button type="button" aria-label="Alejar" title="Alejar (−)" :disabled="escala <= MIN" @click="alejar"><ZoomOutIcon :size="19" /></button>
          <span class="visor-nivel">{{ Math.round(escala * 100) }}%</span>
          <button type="button" aria-label="Acercar" title="Acercar (+)" :disabled="escala >= MAX" @click="acercar"><ZoomInIcon :size="19" /></button>
          <button type="button" aria-label="Restablecer zoom" title="Restablecer (0)" :disabled="escala === 1" @click="reiniciar"><RotateCcwIcon :size="18" /></button>
          <button ref="cerrarBtn" type="button" aria-label="Cerrar" title="Cerrar (Esc)" @click="cerrarVisor"><XIcon :size="22" /></button>
        </div>
      </header>
      <div class="visor-escenario" @click.self="cerrarVisor" @wheel.prevent="rueda">
        <p v-if="fallo" class="visor-error">No se pudo cargar la imagen.</p>
        <img
          v-else
          :src="visor.url"
          :alt="visor.titulo || 'Foto ampliada'"
          :style="estilo"
          draggable="false"
          @error="fallo = true"
          @dblclick="dobleClic"
          @pointerdown="bajar"
          @pointermove="mover"
          @pointerup="soltar"
          @pointercancel="soltar"
        />
      </div>
    </div>
  </Teleport>
</template>
