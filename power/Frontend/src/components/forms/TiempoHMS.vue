<template>
  <div>
    <!-- Cada campo cabe sus dos cifras en un teléfono: con el relleno del resto de campos (16 px por lado) «26» se
         veía «2(» (revisión del perfil, 2026-10-21). -->
    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto_minmax(0,1fr)_auto] items-center gap-1.5 max-w-[18rem]">
      <input
        type="number" min="0" max="99" inputmode="numeric"
        :value="horasVisibles" @input="cambiar('h', $event.target.value)"
        :class="claseInput" class="tiempo-campo text-center placeholder:text-white/30" placeholder="0" :aria-label="`${etiqueta}: horas`"
      />
      <span class="text-[12px] text-white/70 font-bold">h</span>
      <input
        type="number" min="0" max="59" inputmode="numeric"
        :value="partes.m" @input="cambiar('m', $event.target.value)"
        :class="claseInput" class="tiempo-campo text-center placeholder:text-white/30" placeholder="0" :aria-label="`${etiqueta}: minutos`"
      />
      <span class="text-[12px] text-white/70 font-bold">min</span>
      <input
        type="number" min="0" max="59" inputmode="numeric"
        :value="partes.s" @input="cambiar('s', $event.target.value)"
        :class="claseInput" class="tiempo-campo text-center placeholder:text-white/30" placeholder="0" :aria-label="`${etiqueta}: segundos`"
      />
      <span class="text-[12px] text-white/70 font-bold">s</span>
    </div>
    <!-- Un valor guardado que no es un tiempo (una marca vieja escrita a mano,
         "245W") NO se borra en silencio: se enseña y se conserva hasta que el
         atleta escriba un tiempo encima. -->
    <p v-if="noEsTiempo" class="text-[11px] text-[#ffb4ab] mt-1">
      Guardado como «{{ modelValue }}», que no es un tiempo. Escríbelo en horas, minutos y segundos o déjalo así.
    </p>
  </div>
</template>

<script setup>
/**
 * Un tiempo en TRES campos: horas, minutos y segundos.
 *
 * Existe por el atleta Fernando (2026-09-13): escribió "3:30" como meta de su
 * maratón y el cerebro lo leyó como 3 minutos 30 segundos -- su informe dijo que
 * la meta estaba "fuera de rango" y exigía "0:05/km". Con un campo de texto la
 * duda es inevitable; con tres, no hay nada que interpretar.
 *
 * Se guarda como texto "HH:MM:SS" (siempre tres partes), que es lo que ya leen
 * `goal_realism.parse_time_to_seconds`, el panel y los prompts: ni migración ni
 * cambio de formato aguas abajo.
 */
import { computed } from 'vue';
import { leerTiempo, escribirTiempo } from '../../utils/tiempo';

const props = defineProps({
  modelValue: { type: String, default: '' },
  etiqueta: { type: String, default: 'Tiempo' },
  claseInput: { type: [String, Array, Object], default: '' },
});
const emit = defineEmits(['update:modelValue']);

const partes = computed(() => leerTiempo(props.modelValue) || { h: '', m: '', s: '' });
// Una hora a cero se enseña vacía (su «0» de ejemplo, atenuado): «0 h» brillante parecía un valor escrito.
const horasVisibles = computed(() => {
  const p = partes.value;
  return Number(p.h) === 0 && (p.m !== '' || p.s !== '') ? '' : p.h;
});
const noEsTiempo = computed(() => !!(props.modelValue || '').trim() && !leerTiempo(props.modelValue));

const cambiar = (campo, valor) => {
  const actual = { ...partes.value, [campo]: valor };
  emit('update:modelValue', escribirTiempo(actual));
};
</script>

<style scoped>
.tiempo-campo {
  padding-left: 0.25rem !important;
  padding-right: 0.25rem !important;
  min-width: 0;
  font-variant-numeric: tabular-nums;
  -moz-appearance: textfield;
  appearance: textfield;
}
.tiempo-campo::-webkit-outer-spin-button,
.tiempo-campo::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
