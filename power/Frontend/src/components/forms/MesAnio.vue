<template>
  <!-- «Cuándo» de una marca: mes y año en dos selectores. Era texto libre y salía «Septiembre 2026», «abril 2026»…
       (revisión del perfil, 2026-10-21). Se sigue guardando como texto («septiembre 2026»), así que no hay migración
       y lo que ya estaba escrito y no se entiende NO se borra: se enseña y se conserva hasta que elija un mes. -->
  <div>
    <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] gap-2">
      <select :value="partes.mes" @change="cambiar('mes', $event.target.value)" :class="claseInput" :aria-label="`${etiqueta}: mes`">
        <option value="" class="bg-[#17191e] text-white">Mes</option>
        <option v-for="m in MESES" :key="m" :value="m" class="bg-[#17191e] text-white">{{ m }}</option>
      </select>
      <select :value="partes.anio" @change="cambiar('anio', $event.target.value)" :class="claseInput" :aria-label="`${etiqueta}: año`">
        <option value="" class="bg-[#17191e] text-white">Año</option>
        <option v-for="a in anios" :key="a" :value="String(a)" class="bg-[#17191e] text-white">{{ a }}</option>
      </select>
    </div>
    <p v-if="noSeEntiende" class="text-[12px] text-[#ffd166] mt-1">
      Guardado como «{{ modelValue }}». Elige mes y año para cambiarlo, o déjalo así.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  etiqueta: { type: String, default: 'Cuándo' },
  claseInput: { type: [String, Array, Object], default: '' },
});
const emit = defineEmits(['update:modelValue']);

const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre',
  'octubre', 'noviembre', 'diciembre'];
const esteAnio = new Date().getFullYear();
const anios = Array.from({ length: 21 }, (_, i) => esteAnio + 1 - i);

// «Septiembre 2026», «sept 2026», «2026» o «mayo» se leen; lo demás se conserva tal cual.
const leer = (texto) => {
  const t = String(texto || '').trim().toLowerCase();
  if (!t) return { mes: '', anio: '' };
  const anio = (t.match(/\b(19|20)\d{2}\b/) || [''])[0];
  const mes = MESES.find((m) => new RegExp(`\\b${m.slice(0, 3)}`).test(t)) || '';
  const resto = t.replace(anio, '').replace(new RegExp(`\\b${mes ? mes.slice(0, 3) : '@@'}[a-zñ.]*`), '').replace(/\bde(l)?\b/g, '').trim();
  return (mes || anio) && !resto ? { mes, anio } : null;
};
const partes = computed(() => leer(props.modelValue) || { mes: '', anio: '' });
const noSeEntiende = computed(() => !!String(props.modelValue || '').trim() && !leer(props.modelValue));

const cambiar = (campo, valor) => {
  const p = { ...partes.value, [campo]: valor };
  emit('update:modelValue', [p.mes, p.anio].filter(Boolean).join(' '));
};
</script>
