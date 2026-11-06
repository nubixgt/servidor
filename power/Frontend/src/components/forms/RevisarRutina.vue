<template>
  <div class="space-y-5">
    <div>
      <h4 class="text-base font-black text-white">{{ delCoach ? 'Revisa tu programa' : 'Revisa tu rutina' }}</h4>
      <p class="text-[13px] text-[#c5c9ac] leading-relaxed mt-1">
        {{ delCoach
          ? 'Así te lo escribe tu coach. Cambia lo que quieras: tus semanas usarán lo que confirmes aquí.'
          : 'Así la entendimos. Corrige lo que haga falta: tu entrenador partirá de lo que confirmes aquí.' }}
      </p>
    </div>

    <!-- Lo que el coach explica del programa (cómo progresarlo, cómo se coloca). Se guarda con él;
         aquí va plegado para que la revisión sea de los ejercicios. -->
    <details v-if="borrador.notes" class="group rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3">
      <summary class="cursor-pointer list-none flex items-center gap-1.5 text-[12px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#caf300]">
        <span class="material-symbols-outlined text-[16px] transition-transform group-open:rotate-90">chevron_right</span>
        Cómo progresarlo
      </summary>
      <p class="mt-2 text-[13px] text-[#e3e6cf] leading-relaxed whitespace-pre-line">{{ borrador.notes }}</p>
    </details>

    <!-- Lo que el código no pudo dar por bueno. Son COMENTARIOS, no una puerta:
         marcar casillas para poder confirmar era pedirle al atleta que hiciera
         de validador de nuestro propio código (2026-09-17). -->
    <div v-if="pendientes.length" class="rounded-2xl border border-[#ffb4ab]/35 bg-[#ffb4ab]/5 p-4 space-y-2">
      <p class="text-[11px] font-black uppercase tracking-widest text-[#ffb4ab]">Échale un ojo a esto</p>
      <ul class="space-y-1.5">
        <li v-for="p in pendientes" :key="p.clave" class="flex items-start gap-2 text-[13px] text-[#e3e6cf] leading-snug">
          <span class="material-symbols-outlined text-[15px] text-[#ffb4ab] mt-0.5 shrink-0">chat_bubble</span>
          <span>{{ p.texto }}</span>
        </li>
      </ul>
      <p class="text-[12px] text-[#c5c9ac]">
        Cámbialo aquí abajo si crees que está mal. Si está bien, confirma y ya.
      </p>
    </div>

    <div v-for="(sesion, si) in borrador.sessions" :key="sesion.id" class="rounded-2xl border border-white/10 p-3 sm:p-4 space-y-4">
      <div class="grid grid-cols-2 sm:grid-cols-[1fr_11rem_6rem] gap-3 items-end">
        <div class="col-span-2 sm:col-span-1">
          <label :class="labelClass" :for="`ses-${sesion.id}`">Sesión {{ si + 1 }}</label>
          <input :id="`ses-${sesion.id}`" v-model="sesion.name" :class="inputClass" />
        </div>
        <!-- Qué carga decide en qué día cae, entre sus sesiones de correr o nadar. -->
        <div>
          <label :class="labelClass" :for="`enf-${sesion.id}`">Qué carga</label>
          <select :id="`enf-${sesion.id}`" v-model="sesion.emphasis" :class="inputClass">
            <option value="" :class="optClass">— Sin decir —</option>
            <option v-for="(texto, clave) in ENFASIS_DE_SESION" :key="clave" :value="clave" :class="optClass">
              {{ texto }}
            </option>
          </select>
        </div>
        <div>
          <label :class="labelClass" :for="`min-${sesion.id}`">Minutos</label>
          <input :id="`min-${sesion.id}`" type="number" min="0" inputmode="numeric" v-model.number="sesion.minutes" :class="inputClass" />
        </div>
      </div>
      <p v-if="sesion.why" class="text-[12px] text-[#c5c9ac] leading-snug -mt-2">{{ sesion.why }}</p>
      <p v-if="!sesion.emphasis" class="text-[11px] text-[#c5c9ac] -mt-2">
        Si dices qué carga, tu entrenador la coloca lejos de tu día de calidad y de tu tirada larga.
      </p>

      <div v-for="(grupo, gi) in sesion.groups" :key="grupo.id" class="rounded-xl bg-white/[0.03] border border-white/10 p-2.5 sm:p-3 space-y-3">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
          <span class="text-sm font-black text-[#caf300] font-mono-data">{{ letraDeGrupo(gi) }}</span>
          <span class="text-[12px] text-[#c5c9ac]">
            {{ grupo.exercises.length > 1 ? `${grupo.exercises.length} seguidos` : 'Solo' }}
          </span>
          <label class="flex items-center gap-1.5 text-[12px] text-[#c5c9ac]">
            Descanso entre rondas
            <input type="number" min="0" step="15" inputmode="numeric" v-model.number="grupo.rest_between_rounds_s"
                   class="w-20 glass-input rounded-lg px-2 py-1.5 text-white text-[13px] font-mono-data" :aria-label="`Descanso del grupo ${letraDeGrupo(gi)} en segundos`" />
            s
          </label>
          <span class="flex-1"></span>
          <button v-if="grupo.exercises.length > 1" type="button" :class="botonChico" @click="separarGrupo(sesion, gi)">
            <span class="material-symbols-outlined text-[15px]">call_split</span>Separar
          </button>
          <button v-if="gi > 0" type="button" :class="botonChico" @click="unirConElAnterior(sesion, gi)">
            <span class="material-symbols-outlined text-[15px]">merge</span>Unir con {{ letraDeGrupo(gi - 1) }}
          </button>
        </div>

        <div v-for="(ej, ei) in grupo.exercises" :key="ej.id" class="space-y-1.5">
          <div class="grid grid-cols-[1.5rem_1fr] sm:grid-cols-[2.25rem_minmax(0,1fr)_4rem_5.5rem_7rem_2rem] gap-2 items-center">
            <span class="text-[12px] font-black text-[#8f9378] font-mono-data">{{ letraDeGrupo(gi) }}{{ ei + 1 }}</span>
            <input :value="ej.name" @input="renombrarEjercicio(ej, $event.target.value)" :class="inputChico" aria-label="Ejercicio" />
            <div class="col-span-2 sm:col-span-1 grid grid-cols-[3rem_4rem_minmax(0,1fr)_1.75rem] sm:contents gap-2">
              <input type="number" min="1" inputmode="numeric" v-model.number="ej.sets" :class="inputChico" aria-label="Series" placeholder="series" />
              <input v-model="ej.reps" :class="inputChico" aria-label="Repeticiones" placeholder="reps" />
              <input v-model="ej.load" :class="inputChico" aria-label="Carga" placeholder="carga" />
              <button type="button" class="text-[#8f9378] hover:text-[#ffb4ab] rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffb4ab]"
                      :aria-label="`Quitar ${ej.name}`" @click="quitar(sesion, gi, ei)">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>
          </div>
          <p class="pl-8 sm:pl-11 text-[11px] text-[#8f9378] leading-snug">
            <span v-if="ej.source_line">línea {{ ej.source_line }}</span>
            <span v-if="ej.technique"> · {{ ej.technique }}</span>
            <span v-if="nombreEnElReloj(ej)"> · en el reloj: {{ nombreEnElReloj(ej) }}</span>
          </p>
        </div>
      </div>
    </div>

    <p v-if="!borrador.sessions.length" class="text-[13px] text-[#ffb4ab]">
      No encontramos ninguna sesión en el texto. Revisa lo que pegaste y vuelve a ordenarla.
    </p>

    <div class="flex flex-wrap items-center gap-3">
      <button type="button" :disabled="!sePuedeConfirmar || guardando" @click="$emit('confirmar')"
              class="flex items-center gap-1.5 px-5 py-3 rounded-xl text-xs font-black uppercase tracking-wider transition-colors
                     bg-[#caf300] text-[#17191e] disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
        <span class="material-symbols-outlined text-[16px]">task_alt</span>
        {{ guardando ? 'Guardando…' : 'Confirmar' }}
      </button>
      <button type="button" @click="$emit('descartar')" :class="botonChico">Descartar</button>
    </div>
  </div>
</template>

<script setup>
/**
 * La revisión de la rutina ordenada (Beta 2.1, S4).
 *
 * La IA sólo LEE el texto; lo que el código no pudo dar por bueno (una línea sin
 * leer, un nombre que no está en su línea, la «R» de "4×15R", «Geelos») llega como
 * COMENTARIO: se enseña, no se exige. La primera versión pedía marcar una casilla por
 * duda para poder confirmar, y eso era pedirle al atleta que validara nuestro código
 * (2026-09-17). Editar el nombre de un ejercicio le quita el nombre del reloj que era
 * del viejo (`renombrarEjercicio`).
 */
import { computed } from 'vue';
import {
  ENFASIS_DE_SESION,
  letraDeGrupo,
  nombreEnElReloj,
  pendientesDeRevisar,
  renombrarEjercicio,
  separarGrupo,
  unirConElAnterior,
} from '../../utils/fuerza.js';

const props = defineProps({
  // Se edita en sitio: es la copia de trabajo del borrador, no la rutina guardada.
  borrador: { type: Object, required: true },
  guardando: { type: Boolean, default: false },
});
defineEmits(['confirmar', 'descartar']);

// Los comentarios se fijan al abrir la revisión: quitar un ejercicio no puede
// hacer desaparecer lo que había que mirar de él. NO bloquean la confirmación.
const pendientes = pendientesDeRevisar(props.borrador);

const delCoach = computed(() => props.borrador.source === 'coach');

const sePuedeConfirmar = computed(
  () => props.borrador.sessions.some((s) => s.groups.some((g) => g.exercises.length)));

const quitar = (sesion, gi, ei) => {
  const grupo = sesion.groups[gi];
  grupo.exercises.splice(ei, 1);
  if (!grupo.exercises.length) sesion.groups.splice(gi, 1);
};

const labelClass = 'block text-[13px] font-bold text-white/80 mb-1.5';
const inputClass = 'w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors';
const inputChico = 'w-full min-w-0 glass-input rounded-lg px-2.5 py-2 text-white text-[13px] focus:outline-none';
const optClass = 'bg-[#17191e] text-white';
const botonChico = 'flex items-center gap-1 px-3 py-2 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider '
  + 'text-[#c5c9ac] hover:text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300]';
</script>
