<!--
  La casilla de escribir de los dos chats: el entrenador y el asistente de salud.

  ## Por qué existe, y por qué es un <textarea>

  Los dos usaban `<input type="text">` dentro de un `<form>`. Un `<input>` no
  tiene concepto de salto de línea, así que CUALQUIER Enter envía — Shift+Enter
  incluido. No era un fallo de la tecla: era el elemento equivocado. El atleta
  no podía escribir dos frases en un mensaje sobre su propia salud.

  ## Las reglas del teclado

  - **Enter envía. Shift+Enter salta de línea.** Es lo que hace cualquier chat.
  - **En móvil, Enter NUNCA envía.** El teclado del teléfono no tiene Shift
    cómodo, y a mitad de una frase larga te manda el mensaje sin querer. Allí
    se envía con el botón, que además es más grande por eso mismo.
  - **Durante composición (IME, acentos, dictado) Enter no envía.** `isComposing`
    lo dice, y sin comprobarlo se corta la palabra que se estaba componiendo.

  ## Y por qué UN componente y no dos

  Este repo ya tiene la lección aprendida en varios sitios: dos copias del mismo
  comportamiento son dos oportunidades de que una se quede atrás. El chat del
  coach y el del asistente de salud escriben igual porque son la misma casilla.
-->
<template>
  <div class="w-full">
    <div
      class="flex items-end gap-2.5 glass-input rounded-2xl px-3 py-2 transition-all"
      :class="disabled ? 'opacity-60' : ''"
    >
      <textarea
        ref="areaRef"
        v-model="texto"
        :placeholder="placeholder"
        :disabled="disabled"
        rows="1"
        :aria-label="placeholder"
        class="flex-1 bg-transparent border-0 resize-none outline-none text-white leading-relaxed
               placeholder:text-[#8f9378] py-1.5 max-h-40 overflow-y-auto hide-scrollbar
               text-base sm:text-sm"
        @keydown="alPulsarTecla"
        @input="ajustarAlto"
      ></textarea>

      <button
        type="button"
        :disabled="!puedeEnviar"
        :aria-label="disabled ? 'Enviando…' : 'Enviar mensaje'"
        class="shrink-0 w-11 h-11 sm:w-10 sm:h-10 bg-[#caf300] hover:bg-[#bbf400] text-[#121700]
               rounded-xl transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)]
               disabled:opacity-30 disabled:shadow-none hover:scale-105 active:scale-95
               flex items-center justify-center"
        @click="enviar"
      >
        <span
          class="material-symbols-outlined text-[20px] leading-none"
          :class="disabled ? 'animate-spin' : ''"
        >{{ disabled ? 'progress_activity' : 'send' }}</span>
      </button>
    </div>

    <!-- La pista sólo donde el atajo existe. En un teléfono no hay Shift+Enter
         que valga y decirlo allí sólo sería ruido. -->
    <p v-if="!esTactil" class="mt-1.5 px-1 text-[10px] text-[#8f9378] select-none">
      <kbd class="font-mono-data">Enter</kbd> envía ·
      <kbd class="font-mono-data">Shift+Enter</kbd> salta de línea
    </p>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Escribe tu mensaje…' },
  disabled: { type: Boolean, default: false },
  autofocus: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'send']);

const areaRef = ref(null);

const texto = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});

const puedeEnviar = computed(() => !props.disabled && texto.value.trim().length > 0);

// Un teléfono o una tablet: puntero grueso y sin hover. Es la señal buena --
// "ancho de pantalla" no lo es, porque una ventana estrecha en un portátil
// sigue teniendo teclado físico y ahí Enter sí debe enviar.
const esTactil = ref(false);
onMounted(() => {
  try {
    esTactil.value = window.matchMedia('(pointer: coarse)').matches;
  } catch (e) {
    esTactil.value = false;
  }
  if (props.autofocus && !esTactil.value) areaRef.value?.focus();
  ajustarAlto();
});

// Crece con el texto en vez de hacer scroll dentro de una sola línea. El tope
// lo pone `max-h-40` en el CSS: a partir de ahí sí desplaza, para que la
// conversación no desaparezca detrás de lo que estás escribiendo.
const ajustarAlto = () => {
  const el = areaRef.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = `${el.scrollHeight}px`;
};

const alPulsarTecla = (e) => {
  if (e.key !== 'Enter') return;
  // Acentos, dictado, teclados asiáticos: el navegador está componiendo una
  // palabra y este Enter es para cerrarla, no para mandar el mensaje.
  if (e.isComposing || e.keyCode === 229) return;
  if (e.shiftKey || esTactil.value) return;   // salto de línea
  e.preventDefault();
  enviar();
};

const enviar = async () => {
  if (!puedeEnviar.value) return;
  const mensaje = texto.value.trim();
  texto.value = '';
  await nextTick();
  ajustarAlto();
  emit('send', mensaje);
};

defineExpose({ focus: () => areaRef.value?.focus() });
</script>
