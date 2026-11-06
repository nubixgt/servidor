<template>
  <!--
    Los dos botones flotantes, en UNA columna.

    Antes cada uno se posicionaba por su cuenta ("fixed bottom-8 right-8" el
    Coach, "fixed bottom-5 right-5" el de Reportar) y se pisaban: en un movil
    el circulo del Coach caia justo encima del boton de Reportar y no se leia
    ninguno de los dos. Apilarlos en un contenedor con flex es lo que hace que
    eso no pueda volver a pasar aunque cambien de tamano.

    El Coach va ABAJO, pegado a la esquina: es la accion principal y es donde
    cae el pulgar. Reportar va encima, mas pequeno.

    El padding de abajo lleva safe-area para que en un iPhone no queden por
    debajo de la barra de gestos.

    En el teléfono son sólo círculos, y en el alta no salen: con texto tapaban la
    tarjeta de Natasha y el botón "Continuar" de elegir entrenador, así que el
    toque abría el chat en vez de seguir (2026-09-24). `main` deja sitio abajo
    para que el final de cada pantalla suba por encima de ellos.
  -->
  <!-- En el teléfono se apartan al bajar por la pantalla y vuelven al subir: tapaban el domingo de la
       semana y el texto de los pasos (revisión de UX del 2026-10-21). -->
  <div
    class="fixed bottom-0 right-0 z-40 flex flex-col items-end gap-3 p-5 pointer-events-none transition-transform duration-300"
    :class="ocultos ? 'translate-y-[160%] sm:translate-y-0' : ''"
    style="padding-bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px))"
  >
    <!-- En el teléfono «Ayuda» vive en la barra de arriba (TopHeader): dos círculos flotando tapaban
         contenido (revisión de UX del 2026-10-21). -->
    <FeedbackButton class="pointer-events-auto hidden sm:flex" />

    <button
      id="btn-coach-flotante"
      @click="store.isAiCoachOpen = true"
      class="pointer-events-auto flex items-center gap-2.5 p-1.5 sm:pr-5 rounded-full
             bg-[#caf300] hover:bg-[#bbf400] text-[#121700]
             shadow-[0_0_30px_rgba(202,243,0,0.45)] hover:scale-105 active:scale-95
             transition-all select-none"
      :title="`Hablar con ${store.coachName}`"
      :aria-label="`Hablar con ${store.coachName}`"
    >
      <span class="w-11 h-11 rounded-full overflow-hidden bg-white flex items-center justify-center shrink-0 shadow-inner">
        <img
          v-if="store.coachAvatarSrc"
          :src="store.coachAvatarSrc"
          :alt="store.coachName"
          class="w-full h-full object-cover object-top"
        />
        <!-- Sin retrato (nadie ha elegido, o el elegido aun no tiene gif). -->
        <span v-else class="material-symbols-outlined text-[26px] text-[#121700]">smart_toy</span>
      </span>
      <span class="hidden sm:inline font-black text-lg tracking-tight pr-0.5">Coach</span>
    </button>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useAppStore } from '../../stores/appStore';
import FeedbackButton from './FeedbackButton.vue';

const store = useAppStore();

// Bajando (más de 12 px) se ocultan; subiendo, o arriba del todo, vuelven.
const ocultos = ref(false);
let ultimo = 0;
const alDesplazar = () => {
  const y = window.scrollY || 0;
  if (y < 80) ocultos.value = false;
  else if (y > ultimo + 12) ocultos.value = true;
  else if (y < ultimo - 12) ocultos.value = false;
  if (Math.abs(y - ultimo) > 12) ultimo = y;
};
onMounted(() => window.addEventListener('scroll', alDesplazar, { passive: true }));
onUnmounted(() => window.removeEventListener('scroll', alDesplazar));
</script>
