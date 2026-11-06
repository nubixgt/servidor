<template>
  <!-- El programa de fuerza en su propia pantalla (decisión del responsable, 2026-10-21). Desplegado dentro del
       perfil eran ~3.160 px seguidos en el teléfono: las tres sesiones, la progresión y el glosario. Aquí se ve UNA
       sesión cada vez, por bloques, y lo demás está a un toque. Va a `body`: dentro de una vista con `transform`,
       un `fixed` se coloca respecto a ella (ver MoverSesionHoja). -->
  <Teleport to="body">
    <div class="fixed inset-0 z-[70] bg-[#0f1114] flex flex-col" role="dialog" aria-modal="true" aria-labelledby="titulo-programa">
      <header class="shrink-0 border-b border-white/10 bg-[#14161a] px-4 py-3 flex items-center gap-3">
        <button ref="botonVolver" type="button" @click="$emit('cerrar')" aria-label="Volver al perfil"
                class="w-11 h-11 rounded-xl border border-white/20 flex items-center justify-center text-white hover:border-white/40 shrink-0">
          <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </button>
        <div class="min-w-0">
          <h2 id="titulo-programa" class="text-base font-black text-white leading-tight">Tu programa de fuerza</h2>
          <p class="text-[12px] text-white/60">{{ subtitulo }}</p>
        </div>
      </header>

      <nav class="shrink-0 flex gap-2 px-4 pt-3 pb-1 overflow-x-auto" role="tablist" aria-label="Sesiones">
        <button v-for="(s, i) in rutina.sessions" :key="s.id || i" type="button" role="tab"
                :aria-selected="String(vista === i)" @click="vista = i"
                class="px-3 py-2.5 min-h-[44px] rounded-xl border text-[13px] font-black whitespace-nowrap"
                :class="vista === i ? 'bg-[#caf300] text-[#121700] border-[#caf300]' : 'border-white/15 text-white/90 hover:border-white/30'">
          {{ nombreCorto(s.name) }}
        </button>
      </nav>

      <main ref="cuerpo" class="flex-1 overflow-y-auto px-4 pt-2 pb-6">
        <template v-if="sesion">
          <p class="text-[15px] font-bold text-white">{{ sesion.name }}<span v-if="sesion.minutes" class="font-normal text-white/60"> · {{ sesion.minutes }} min</span></p>
          <p v-if="sesion.why" class="text-[14px] text-white/80 leading-relaxed mt-1">{{ sesion.why }}</p>
          <p class="text-[12px] text-white/60 leading-snug mt-2 mb-3">
            Los ejercicios de un bloque van seguidos, sin descanso entre ellos; se descansa al acabar la ronda.
            «4 × 6-8» son 4 series de 6 a 8 repeticiones.
          </p>
          <section v-for="(g, gi) in sesion.groups" :key="gi" class="rounded-2xl border border-white/10 bg-[#1a1d22] px-3.5 py-2.5 mb-2.5">
            <h3 class="flex items-baseline justify-between gap-2 text-[12px] font-black text-white/60 tracking-wide mb-1">
              <span>BLOQUE {{ String.fromCharCode(65 + gi) }}</span>
              <span v-if="g.rest_between_rounds_s" class="font-bold normal-case tracking-normal">descanso {{ descanso(g.rest_between_rounds_s) }} entre rondas</span>
            </h3>
            <div v-for="(ej, ei) in g.exercises" :key="ej.id || `${gi}-${ei}`" class="py-2 border-t border-white/[0.06] first-of-type:border-t-0">
              <div class="flex justify-between gap-3 text-[14px]">
                <span class="text-white min-w-0">
                  {{ ej.name }}
                  <button v-if="definicionDe(ej.name)" type="button" @click="alternarDefinicion(gi, ei)"
                          :aria-expanded="String(abierta === `${gi}-${ei}`)"
                          class="ml-1 text-[12px] text-[#a5eeff] underline decoration-dotted underline-offset-2 min-h-[32px]">¿qué es?</button>
                </span>
                <span class="text-white/65 text-right shrink-0 tabular-nums">{{ dosis(ej) }}</span>
              </div>
              <p v-if="abierta === `${gi}-${ei}`" class="text-[13px] text-white/75 leading-snug mt-1">{{ definicionDe(ej.name) }}</p>
            </div>
          </section>
        </template>

        <template v-else-if="vista === 'progresar'">
          <p class="text-[15px] font-bold text-white mb-1">Cómo progresar semana a semana</p>
          <div class="text-[14px] text-white/85 leading-relaxed notas-del-programa" v-html="notasHtml"></div>
          <dl v-if="siglas.length" class="mt-4 border-t border-white/10 pt-3 space-y-1.5 text-[13px]">
            <dt class="text-[12px] font-bold text-white/60">Qué significa cada palabra</dt>
            <div v-for="g in siglas" :key="g.sigla"><dt class="inline font-bold text-white">{{ g.sigla }}</dt>
              <dd class="inline text-white/75">: {{ g.texto }}</dd></div>
          </dl>
        </template>

        <div class="mt-4"><ProgresoDeFuerza /></div>
      </main>

      <footer class="shrink-0 border-t border-white/10 bg-[#14161a] px-4 py-3 flex gap-2">
        <!-- «Cómo progresar» abajo y no como cuarta pestaña: a 375 px no cabía. Se cierra con la flecha de arriba. -->
        <button v-if="notasHtml" type="button" @click="vista = vista === 'progresar' ? 0 : 'progresar'"
                class="flex-1 min-h-[44px] rounded-xl border text-[13px] font-bold flex items-center justify-center gap-1.5"
                :class="vista === 'progresar' ? 'border-[#caf300] text-[#caf300]' : 'border-white/20 text-white hover:border-white/40'">
          <span class="material-symbols-outlined text-[16px]">{{ vista === 'progresar' ? 'list' : 'trending_up' }}</span>
          {{ vista === 'progresar' ? 'Ver las sesiones' : 'Cómo progresar' }}
        </button>
        <button type="button" @click="$emit('revisar')"
                class="flex-1 min-h-[44px] rounded-xl border border-white/20 text-[13px] font-bold text-white flex items-center justify-center gap-1.5 hover:border-white/40">
          <span class="material-symbols-outlined text-[16px]">edit</span>Revisar y editar
        </button>
      </footer>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ProgresoDeFuerza from './ProgresoDeFuerza.vue';

const props = defineProps({
  rutina: { type: Object, required: true },
  subtitulo: { type: String, default: '' },
  notasHtml: { type: String, default: '' },
  siglas: { type: Array, default: () => [] },
});
const emit = defineEmits(['cerrar', 'revisar']);

const vista = ref(0);
const abierta = ref('');
const cuerpo = ref(null);
const botonVolver = ref(null);
const sesion = computed(() => (typeof vista.value === 'number' ? props.rutina.sessions[vista.value] : null));
watch(vista, () => { abierta.value = ''; nextTick(() => cuerpo.value?.scrollTo({ top: 0 })); });

// «Pierna A (cuádriceps + estabilidad)» → «Pierna A»: en la pestaña sólo cabe el nombre.
const nombreCorto = (n) => String(n || '').replace(/\s*\(.*\)\s*$/, '').trim() || n;
const dosis = (ej) => [ej.sets ? `${ej.sets} ×` : '', ej.reps || ''].filter(Boolean).join(' ');
const descanso = (s) => (s >= 60 && s % 60 === 0 ? `${s / 60}'` : `${s} s`);

// Sólo lo que sabemos explicar bien; un ejercicio sin entrada no lleva «¿qué es?».
const DEFINICIONES = [
  [/pallof/i, 'Aguantas sin girar el tronco mientras una banda o polea tira de ti hacia un lado.'],
  [/face pull/i, 'Tiras de una cuerda en polea alta hacia la cara, abriendo los codos: trabaja la parte de atrás del hombro.'],
  [/pull-?apart/i, 'Con los brazos estirados delante, abres una banda elástica hasta tocar el pecho.'],
  [/dead ?bug/i, 'Boca arriba, bajas a la vez un brazo y la pierna contraria sin despegar la zona lumbar del suelo.'],
  [/hip thrust/i, 'Con la espalda apoyada en un banco, subes la cadera empujando con los glúteos.'],
  [/peso muerto rumano/i, 'Bajas la barra pegada a las piernas, con las rodillas casi estiradas, hasta notar la parte de atrás del muslo.'],
  [/gato-?camello/i, 'A cuatro patas, arqueas y redondeas la espalda despacio: movilidad de columna.'],
  [/b[uú]lgara/i, 'Zancada con el pie de atrás apoyado en un banco.'],
  [/exc[eé]ntric/i, 'Lo importante es la bajada: lenta y controlada.'],
  [/paseo del granjero/i, 'Caminas con un peso en cada mano, erguido.'],
  [/jal[oó]n/i, 'Tiras de una barra en polea alta hacia el pecho, sentado.'],
];
const definicionDe = (nombre) => (DEFINICIONES.find(([re]) => re.test(nombre || '')) || [])[1] || '';
const alternarDefinicion = (gi, ei) => { abierta.value = abierta.value === `${gi}-${ei}` ? '' : `${gi}-${ei}`; };

// Escape cierra, y la página de debajo no se desplaza mientras está abierta.
const alPulsar = (e) => { if (e.key === 'Escape') emit('cerrar'); };
let overflowAntes = '';
onMounted(() => {
  overflowAntes = document.body.style.overflow;
  document.body.style.overflow = 'hidden';
  window.addEventListener('keydown', alPulsar);
  nextTick(() => botonVolver.value?.focus());
});
onBeforeUnmount(() => {
  document.body.style.overflow = overflowAntes;
  window.removeEventListener('keydown', alPulsar);
});
</script>

<style scoped>
.notas-del-programa :deep(ul) { list-style: disc; padding-left: 1.1rem; }
.notas-del-programa :deep(li) { margin: 0.3rem 0; }
.notas-del-programa :deep(p) { margin: 0.35rem 0; }
</style>
