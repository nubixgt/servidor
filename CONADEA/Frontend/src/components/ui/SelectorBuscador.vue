<template>
  <div class="selector-buscador" ref="raiz">
    <button
      type="button"
      class="selector-trigger"
      :class="{ deshabilitado: disabled }"
      :disabled="disabled"
      @click="alternar"
    >
      <span class="selector-icono">🔍</span>
      <span class="selector-texto" :class="{ vacio: !etiquetaSeleccionada }">
        {{ etiquetaSeleccionada || placeholder }}
      </span>
      <span class="selector-chevron" :class="{ abierto }">▾</span>
    </button>

    <div v-if="abierto" class="selector-panel">
      <div class="selector-buscar">
        <input
          ref="inputBusqueda"
          v-model="busqueda"
          type="text"
          placeholder="Escribe para buscar..."
          @keydown.esc="cerrar"
        />
      </div>
      <ul class="selector-lista">
        <li v-if="filtradas.length === 0" class="selector-vacio">
          No se encontraron resultados.
        </li>
        <li
          v-for="op in filtradas"
          :key="op.id"
          class="selector-opcion"
          :class="{ activa: op.id === modelValue }"
          @click="seleccionar(op)"
        >
          {{ op.nombre }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';

// Equivalente web del selector-buscador de la app (ver
// app_conadea/lib/features/auth/login_screen.dart, _CampoBuscador +
// _HojaBuscador): un <select> nativo no se puede vestir con el estilo
// glass porque el desplegable lo dibuja el sistema operativo, así que
// esto abre un panel propio con buscador en vivo.
const props = defineProps({
  modelValue: { type: [Number, String], default: '' },
  opciones: { type: Array, default: () => [] }, // [{ id, nombre }]
  placeholder: { type: String, default: 'Selecciona una opción' },
  disabled: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue', 'change']);

const raiz = ref(null);
const inputBusqueda = ref(null);
const abierto = ref(false);
const busqueda = ref('');

const etiquetaSeleccionada = computed(() => {
  const op = props.opciones.find((o) => o.id === props.modelValue);
  return op?.nombre || '';
});

const filtradas = computed(() => {
  const q = busqueda.value.trim().toLowerCase();
  if (!q) return props.opciones;
  return props.opciones.filter((o) => o.nombre.toLowerCase().includes(q));
});

async function alternar() {
  if (props.disabled) return;
  abierto.value = !abierto.value;
  if (abierto.value) {
    busqueda.value = '';
    await nextTick();
    inputBusqueda.value?.focus();
  }
}

function cerrar() {
  abierto.value = false;
}

function seleccionar(op) {
  emit('update:modelValue', op.id);
  emit('change', op);
  cerrar();
}

function onClickFuera(evento) {
  if (abierto.value && raiz.value && !raiz.value.contains(evento.target)) {
    cerrar();
  }
}

onMounted(() => document.addEventListener('click', onClickFuera));
onBeforeUnmount(() => document.removeEventListener('click', onClickFuera));
</script>

<style scoped>
.selector-buscador {
  position: relative;
}

.selector-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.07);
  color: var(--texto);
  text-align: left;
  transition: all 0.2s ease;
}

.selector-trigger:hover:not(.deshabilitado) {
  border-color: rgba(74, 222, 128, 0.4);
  background: rgba(255, 255, 255, 0.1);
}

.selector-trigger.deshabilitado {
  opacity: 0.5;
  cursor: not-allowed;
}

.selector-icono {
  font-size: 0.85rem;
  opacity: 0.55;
}

.selector-texto {
  flex: 1;
  font-size: 0.92rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.selector-texto.vacio {
  color: rgba(255, 255, 255, 0.4);
}

.selector-chevron {
  font-size: 0.8rem;
  opacity: 0.6;
  transition: transform 0.2s ease;
}

.selector-chevron.abierto {
  transform: rotate(180deg);
}

.selector-panel {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 40;
  background: rgba(11, 37, 48, 0.97);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid var(--borde-claro);
  border-radius: 14px;
  box-shadow: 0 16px 44px rgba(0, 0, 0, 0.45);
  overflow: hidden;
  animation: selectorIn 0.15s ease;
}

@keyframes selectorIn {
  from { opacity: 0; transform: translateY(-4px); }
  to   { opacity: 1; transform: translateY(0); }
}

.selector-buscar {
  padding: 10px;
  border-bottom: 1px solid var(--borde);
}

.selector-buscar input {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.07);
  color: var(--texto);
  font-size: 0.86rem;
  outline: none;
}

.selector-buscar input:focus {
  border-color: var(--verde);
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.2);
}

.selector-lista {
  max-height: 220px;
  overflow-y: auto;
  list-style: none;
}

.selector-opcion {
  padding: 10px 16px;
  font-size: 0.88rem;
  color: var(--texto);
  cursor: pointer;
  transition: background 0.15s ease;
}

.selector-opcion:hover {
  background: rgba(74, 222, 128, 0.12);
}

.selector-opcion.activa {
  background: rgba(74, 222, 128, 0.2);
  color: var(--verde);
  font-weight: 700;
}

.selector-vacio {
  padding: 16px;
  font-size: 0.82rem;
  color: var(--texto-suave);
  text-align: center;
}
</style>
