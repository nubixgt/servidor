<template>
  <section class="glass-card rounded-3xl p-6 sm:p-8 space-y-6 border border-white/10">
    <h3 class="text-xl font-black text-white tracking-tight border-b border-white/10 pb-4 flex items-center gap-2.5">
      <span class="material-symbols-outlined text-[#caf300]">fitness_center</span>
      <span>Tu fuerza</span>
      <span :class="marcaOpcional">opcional</span>
    </h3>
    <p class="text-sm text-[#c5c9ac] leading-relaxed">
      Cuéntanos cómo entrenas la fuerza y tus sesiones de gimnasio se escriben con eso.
      <span class="text-white font-bold">Si lo dejas así, tu entrenador elige los ejercicios cada semana.</span>
      Los días los marcas tú en tu tabla (fila Fuerza).
    </p>

    <!-- Cómo funciona y qué significa cada etiqueta del plan: plegado, para quien lo busque. -->
    <details class="group rounded-2xl border border-white/10 bg-white/[0.02] px-4 py-3">
      <summary class="cursor-pointer list-none flex items-center gap-1.5 text-[12px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#caf300]">
        <span class="material-symbols-outlined text-[16px] transition-transform group-open:rotate-90">chevron_right</span>
        ¿Cómo funciona?
      </summary>
      <ol class="mt-3 space-y-1.5 text-[13px] text-[#e3e6cf] list-decimal pl-5">
        <li>Pegas tu rutina (o el coach te escribe una) y la <b>confirmas</b>. Sin confirmar no se usa.</li>
        <li>Cada semana elegimos qué sesión va cada día mirando tus sesiones de correr, bici o nadar: la de
          pierna lejos de tus días clave.</li>
        <li>Tus ejercicios no cambian. Si esa semana conviene menos (una carrera, una molestia), se ajusta y se
          te dice por qué.</li>
      </ol>
      <p class="mt-3 text-[11px] font-black uppercase tracking-wider text-[#8f9378]">Lo que verás en tu plan</p>
      <ul class="mt-1.5 space-y-1 text-[12px] text-[#c5c9ac]">
        <li><span class="px-1.5 rounded bg-[#ffd166]/10 border border-[#ffd166]/30 text-[#ffd166] font-bold">Dosis reducida / mínima</span>
          menos series esa semana, la misma carga.</li>
        <li><span class="px-1.5 rounded bg-[#a5eeff]/10 border border-[#a5eeff]/30 text-[#a5eeff] font-bold">−1 serie · en vez de… · nuevo esta semana</span>
          un cambio en ese ejercicio. Tócalo para ver el porqué.</li>
        <li><span class="text-[#ffd166] font-bold">⚠</span> dónde cae la sesión respecto a tus días clave.</li>
        <li><b class="text-white">Registrar lo que hiciste</b>: apunta series, repeticiones y kilos; la próxima vez verás
          «La última vez».</li>
      </ul>
    </details>
    <p v-if="!diasDeFuerza" class="text-[13px] text-[#c5c9ac] glass-card rounded-2xl p-3.5 border border-white/10">
      <span class="material-symbols-outlined text-[16px] align-middle text-[#8f9378]">info</span>
      No tienes ningún día de Fuerza en tu tabla: esto se usará cuando marques alguno.
    </p>

    <div role="radiogroup" aria-label="Cómo quieres entrenar la fuerza" class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <button
        v-for="opcion in opcionesDeModo" :key="opcion.valor"
        type="button" role="radio" :aria-checked="perfil.mode === opcion.valor"
        @click="perfil.mode = opcion.valor"
        class="text-left rounded-2xl p-4 border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300]"
        :class="perfil.mode === opcion.valor
          ? 'border-[#caf300] bg-[#caf300]/10'
          : 'border-white/10 hover:border-white/30'"
      >
        <span class="flex items-center gap-2 text-sm font-black" :class="perfil.mode === opcion.valor ? 'text-[#caf300]' : 'text-white'">
          <!-- El círculo de una opción única: sólo con el color, la elegida no se distinguía bien. -->
          <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0"
                :class="perfil.mode === opcion.valor ? 'border-[#caf300]' : 'border-white/40'">
            <span v-if="perfil.mode === opcion.valor" class="w-2.5 h-2.5 rounded-full bg-[#caf300]"></span>
          </span>
          <span class="material-symbols-outlined text-[18px]">{{ opcion.icono }}</span>
          {{ opcion.titulo }}
        </span>
        <span class="block text-[12px] text-[#c5c9ac] mt-1.5 leading-snug">{{ opcion.texto }}</span>
      </button>
    </div>

    <template v-if="perfil.mode">
      <!-- Con un programa del coach ya confirmado, lo primero es el programa; el formulario es para pedir
           otro y sale al pulsar «Pedir un programa nuevo» (revisión del perfil, 2026-10-21). -->
      <div v-if="formularioVisible" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label :class="labelClass" for="fuerza-objetivo">Para qué la haces</label>
          <select id="fuerza-objetivo" v-model="perfil.goal" :class="inputClass">
            <option value="" :class="optClass">— Seleccionar —</option>
            <option v-for="(texto, clave) in OBJETIVOS_DE_FUERZA" :key="clave" :value="clave" :class="optClass">{{ texto }}</option>
          </select>
        </div>
        <div>
          <label :class="labelClass" for="fuerza-equipo">Con qué entrenas</label>
          <select id="fuerza-equipo" v-model="perfil.equipment" :class="inputClass">
            <option value="" :class="optClass">— Seleccionar —</option>
            <option v-for="(texto, clave) in EQUIPOS_DE_FUERZA" :key="clave" :value="clave" :class="optClass">{{ texto }}</option>
          </select>
        </div>
      </div>

      <!-- La experiencia en concreto y no autoevaluada: "nivel avanzado" no le
           dice al coach si puede pedir un peso muerto; esto sí. -->
      <div v-if="formularioVisible && perfil.equipment === 'gimnasio'">
        <p :class="labelClass">Levantamientos con barra que ya haces con buena técnica</p>
        <p class="text-[12px] text-white/70 -mt-1 mb-2">Marca sólo los que ya dominas: el coach no te pedirá los demás con barra.</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(texto, clave) in LEVANTAMIENTOS_CON_BARRA" :key="clave"
            type="button" :aria-pressed="perfil.barbell_lifts.includes(clave)"
            @click="alternarLevantamiento(clave)"
            class="flex items-center gap-1.5 px-3 py-2.5 min-h-[44px] rounded-xl border text-[13px] font-bold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300]"
            :class="perfil.barbell_lifts.includes(clave)
              ? 'border-[#caf300] bg-[#caf300]/10 text-[#caf300]'
              : 'border-white/15 text-[#c5c9ac] hover:border-white/30'"
          >
            <span class="material-symbols-outlined text-[15px]">{{ perfil.barbell_lifts.includes(clave) ? 'check' : 'add' }}</span>
            {{ texto }}
          </button>
        </div>
      </div>

      <div v-if="formularioVisible && perfil.mode === 'coach'" class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
          <label :class="labelClass" for="fuerza-estructura">Cómo repartirla</label>
          <select id="fuerza-estructura" v-model="perfil.structure" :class="inputClass">
            <option value="" :class="optClass">Que decida el coach</option>
            <option v-for="(texto, clave) in ESTRUCTURAS_DE_FUERZA" :key="clave" :value="clave" :class="optClass">{{ texto }}</option>
          </select>
        </div>
        <div>
          <label :class="labelClass" for="fuerza-seguidos">Ejercicios seguidos, sin descanso entre ellos</label>
          <select id="fuerza-seguidos" v-model="perfil.exercises_per_group" :class="inputClass">
            <option value="" :class="optClass">Que decida el coach</option>
            <option :value="1" :class="optClass">Ninguno: uno a uno, con descanso</option>
            <option :value="2" :class="optClass">De dos en dos (superserie)</option>
            <option :value="3" :class="optClass">De tres en tres (triserie)</option>
          </select>
          <p class="text-[12px] text-white/70 mt-1.5">Juntarlos ahorra tiempo en el gimnasio.</p>
        </div>
        <div>
          <label :class="labelClass" for="fuerza-minutos">Minutos por sesión</label>
          <input
            id="fuerza-minutos" type="number" inputmode="numeric"
            :min="MINUTOS_POR_SESION[0]" :max="MINUTOS_POR_SESION[1]" step="5"
            v-model.number="perfil.session_minutes" :class="inputClass" placeholder="60"
          />
          <p v-if="minutosFueraDeRango" class="text-[11px] text-[#ffb4ab] mt-1">
            Entre {{ MINUTOS_POR_SESION[0] }} y {{ MINUTOS_POR_SESION[1] }} minutos; si no, no se guarda.
          </p>
        </div>
      </div>

      <div v-if="perfil.mode === 'rutina'" class="space-y-4">
        <div class="space-y-2">
          <label :class="labelClass" for="fuerza-rutina">Pega tu rutina</label>
          <textarea
            id="fuerza-rutina" v-model="texto" rows="9"
            :class="inputClass" class="font-mono-data leading-relaxed resize-y"
            :placeholder="ejemploDeRutina"
          ></textarea>
          <p class="text-[12px] text-[#c5c9ac] leading-relaxed">
            Tal cual la tengas: de tus notas, de WhatsApp o de tu entrenador de gimnasio. La ordenamos en sesiones y
            ejercicios y te la enseñamos para que la confirmes antes de usarla.
          </p>
        </div>

        <!-- Lo que el texto casi nunca dice y cambia cómo se lee: se pregunta, no se adivina. -->
        <div class="grid grid-cols-1 md:grid-cols-[1fr_14rem] gap-4">
          <div>
            <label :class="labelClass" for="fuerza-agrupacion">Los ejercicios de una misma línea…</label>
            <select id="fuerza-agrupacion" v-model="agrupacion" :class="inputClass">
              <option value="por_linea" :class="optClass">Los hago seguidos (superserie)</option>
              <option value="uno_a_uno" :class="optClass">Los hago uno a uno</option>
              <option value="texto" :class="optClass">Mi texto indica cómo van</option>
            </select>
          </div>
          <div>
            <label :class="labelClass" for="fuerza-descanso">Descanso entre rondas (seg)</label>
            <input id="fuerza-descanso" type="number" min="0" max="900" step="15" inputmode="numeric"
                   v-model.number="descanso" :class="inputClass" placeholder="120" />
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button type="button" @click="ordenar" :disabled="ordenando || !texto.trim()"
                  class="flex items-center gap-1.5 px-4 py-2.5 glass-pill text-[#caf300] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300]">
            <span class="material-symbols-outlined text-[16px]" :class="ordenando ? 'animate-spin' : ''">
              {{ ordenando ? 'progress_activity' : 'format_list_numbered' }}
            </span>
            {{ ordenando ? 'Ordenando tu rutina…' : (rutina.confirmed_at && !cambioElTexto ? 'Ordenarla de nuevo' : 'Ordenar mi rutina') }}
          </button>
          <p v-if="error" class="text-[12px] text-[#ffb4ab]" role="alert">{{ error }}</p>
        </div>

      </div>

      <!-- Modo coach (S6): el programa se escribe UNA vez por bloque y se confirma igual. -->
      <!-- Con un programa del coach ya confirmado, pedir otro va dentro de su resumen, debajo del programa. -->
      <div v-if="formularioVisible && perfil.mode === 'coach'" class="space-y-3">
        <p class="text-[12px] text-[#c5c9ac] leading-relaxed">
          Tu entrenador escribe tu programa con lo que elegiste arriba y con tus días de Fuerza. Lo revisas, lo
          confirmas, y desde ahí tus semanas usan siempre esos ejercicios.
        </p>
        <p v-if="!perfil.goal || !perfil.equipment" class="text-[12px] text-[#ffd166]">
          Elige para qué la haces y con qué entrenas: con eso se escribe el programa.
        </p>
        <div class="flex flex-wrap items-center gap-3">
          <button type="button" @click="pedirElPrograma" :disabled="escribiendo || !perfil.goal || !perfil.equipment"
                  class="flex items-center gap-1.5 px-4 py-2.5 glass-pill text-[#caf300] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300]">
            <span class="material-symbols-outlined text-[16px]" :class="escribiendo ? 'animate-spin' : ''">
              {{ escribiendo ? 'progress_activity' : 'edit_note' }}
            </span>
            {{ escribiendo ? 'Escribiendo tu programa…'
               : (rutina.confirmed_at ? 'Escribirlo otra vez' : 'Que el coach escriba mi programa') }}
          </button>
          <p v-if="errorDelPrograma" class="text-[12px] text-[#ffb4ab]" role="alert">{{ errorDelPrograma }}</p>
        </div>
        <p v-if="rutina.confirmed_at" class="text-[11px] text-[#8f9378]">
          Escribirlo otra vez reemplaza el que ya confirmaste, y tendrás que confirmar el nuevo.
        </p>
      </div>

      <!-- La revisión y el resumen, iguales para los dos modos. -->
      <RevisarRutina
        v-if="borrador" :key="claveDeRevision" :borrador="borrador" :guardando="guardando"
        @confirmar="confirmar" @descartar="borrador = null"
      />
      <template v-else>
        <p v-if="perfil.mode === 'rutina' && cambioElTexto" class="text-[12px] text-[#ffb4ab]">
          Cambiaste el texto: ordénala otra vez para confirmarla. Si guardas así, tu entrenador no la usará hasta
          que la confirmes.
        </p>
        <!-- El resumen; el programa entero se abre en su propia pantalla (ProgramaDeFuerzaHoja, decisión del
             responsable del 2026-10-21): desplegado aquí eran ~3.160 px dentro de un formulario. -->
        <div v-else-if="rutina.confirmed_at && rutina.sessions.length"
             class="rounded-2xl border border-[#caf300]/30 bg-[#caf300]/5 p-4 space-y-3">
          <div class="flex items-start gap-2">
            <span class="material-symbols-outlined text-[#caf300] text-[20px] mt-px">verified</span>
            <div class="min-w-0 flex-1">
              <p class="text-[14px] text-white font-bold">
                {{ rutina.source === 'coach' ? 'Programa confirmado el' : 'Rutina confirmada el' }} {{ fechaCorta(rutina.confirmed_at) }}
              </p>
              <p class="text-[12px] text-white/70">{{ resumenDelPrograma }}</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button type="button" @click="verPrograma = true" :class="botonSecundario">
              <span class="material-symbols-outlined text-[16px]">menu_book</span>Ver programa
            </button>
            <button type="button" @click="revisarLaGuardada" :class="botonSecundario">
              <span class="material-symbols-outlined text-[16px]">edit</span>Revisar y editar
            </button>
          </div>
          <ProgramaDeFuerzaHoja
            v-if="verPrograma"
            :rutina="rutina" :subtitulo="`Confirmado el ${fechaCorta(rutina.confirmed_at)} · ${resumenCorto}`"
            :notas-html="rutina.notes ? notasEnLista : ''" :siglas="siglasDeLasNotas"
            @cerrar="verPrograma = false" @revisar="verPrograma = false; revisarLaGuardada()"
          />
          <div v-if="programaDelCoachConfirmado" class="border-t border-white/10 pt-3 space-y-1.5">
            <button type="button" @click="pidiendoNuevo = !pidiendoNuevo" :aria-expanded="String(pidiendoNuevo)" :class="botonSecundario">
              <span class="material-symbols-outlined text-[16px]">{{ pidiendoNuevo ? 'close' : 'edit_note' }}</span>
              {{ pidiendoNuevo ? 'Mejor me quedo con éste' : 'Pedir un programa nuevo' }}
            </button>
            <p class="text-[12px] text-white/60">{{ pidiendoNuevo
              ? 'Cambia arriba lo que quieras y pide el nuevo. Éste sigue mientras no confirmes el otro.'
              : 'Sólo se reemplaza cuando confirmes el nuevo.' }}</p>
          </div>
        </div>
      </template>
      <p v-if="perfil.mode !== 'rutina' && rutina.original_text" class="text-[12px] text-[#8f9378]">
        Tu rutina pegada sigue guardada; sólo se usa con «Tengo mi rutina».
      </p>
    </template>
  </section>
</template>

<script setup>
/**
 * La sección "Tu fuerza" del perfil (Beta 2.1, paso S3; Database/025).
 *
 * Tres modos, y el primero es no contestar: el plan sigue exactamente como
 * hasta hoy. Los valores y cómo se guardan viven en `utils/fuerza.js`, espejo de
 * `PaginaLocal/services/ai/perfil_de_fuerza.py`.
 */
import { computed, ref } from 'vue';
import { renderMarkdown } from '../../utils/markdown';
import RevisarRutina from './RevisarRutina.vue';
import ProgramaDeFuerzaHoja from './ProgramaDeFuerzaHoja.vue';
import { ordenarRutina, programaDelCoach } from '../../services/profile.js';
import {
  EQUIPOS_DE_FUERZA,
  ESTRUCTURAS_DE_FUERZA,
  LEVANTAMIENTOS_CON_BARRA,
  MINUTOS_POR_SESION,
  OBJETIVOS_DE_FUERZA,
  rutinaConfirmada,
  textoCambiado,
} from '../../utils/fuerza.js';

const props = defineProps({
  perfil: { type: Object, required: true },
  // La rutina GUARDADA. El texto del recuadro es `texto`, aparte: así se sabe si
  // el atleta lo cambió después de confirmarla.
  rutina: { type: Object, required: true },
  diasDeFuerza: { type: Number, default: 0 },
  // Guarda la rutina confirmada con el perfil; devuelve true si se guardó.
  guardarRutina: { type: Function, required: true },
});
const texto = defineModel('texto', { type: String, default: '' });

// Los dos objetos son del formulario reactivo de ProfileView y se editan en sitio,
// como el resto de sus secciones. Se leen siempre por `props`: al recargar el
// perfil el formulario los reemplaza, y una copia local se quedaría con los viejos.

// Ordenar y revisar (S4). El borrador vive aquí hasta confirmarlo: nada se guarda antes.
const agrupacion = ref('por_linea');
const descanso = ref('');
const ordenando = ref(false);
const guardando = ref(false);
const error = ref('');
const borrador = ref(null);
const claveDeRevision = ref(0);
const textoOrdenado = ref('');

const cambioElTexto = computed(() => textoCambiado(props.rutina, texto.value));

const abrirRevision = (datos, textoDeOrigen) => {
  borrador.value = JSON.parse(JSON.stringify(datos));
  textoOrdenado.value = textoDeOrigen;
  claveDeRevision.value += 1;
};

const ordenar = async () => {
  error.value = '';
  ordenando.value = true;
  try {
    const segundos = Number(descanso.value) > 0 ? Number(descanso.value) : null;
    const datos = await ordenarRutina({ text: texto.value, grouping: agrupacion.value, rest_between_rounds_s: segundos });
    abrirRevision(datos, texto.value);
  } catch (e) {
    error.value = e?.response?.data?.message || 'No pudimos ordenar tu rutina ahora. Inténtalo en un momento.';
  } finally {
    ordenando.value = false;
  }
};

const escribiendo = ref(false);
const errorDelPrograma = ref('');

const pedirElPrograma = async () => {
  errorDelPrograma.value = '';
  escribiendo.value = true;
  try {
    // El programa del coach se revisa y se confirma igual que una rutina pegada: por eso
    // llega con la misma forma y no hay una segunda pantalla que mantener.
    abrirRevision(await programaDelCoach(JSON.parse(JSON.stringify(props.perfil))), '');
  } catch (e) {
    errorDelPrograma.value = e?.response?.data?.message
      || 'No pudimos escribir tu programa ahora. Inténtalo en un momento.';
  } finally {
    escribiendo.value = false;
  }
};

const revisarLaGuardada = () => abrirRevision(
  { ...props.rutina, doubts: [], unread_lines: [] }, props.rutina.original_text);

const confirmar = async () => {
  guardando.value = true;
  error.value = '';
  try {
    const ok = await props.guardarRutina(rutinaConfirmada(borrador.value, textoOrdenado.value));
    if (ok) { borrador.value = null; pidiendoNuevo.value = false; }
    else error.value = 'No se pudo guardar la rutina. Vuelve a intentarlo.';
  } finally {
    guardando.value = false;
  }
};

const opcionesDeModo = [
  { valor: '', icono: 'autorenew', titulo: 'Sin programa fijo',
    texto: 'Tu entrenador elige los ejercicios cada semana para acompañar tu deporte.' },
  { valor: 'rutina', icono: 'content_paste', titulo: 'Tengo mi rutina',
    texto: 'La usamos de base. Si el coach cambia algo, te dice por qué.' },
  { valor: 'coach', icono: 'edit_note', titulo: 'Que el coach me arme una',
    texto: 'Con tu objetivo, tu equipo y el tiempo que tienes.' },
];

// Un ejemplo con la forma de una rutina real, marcado como ejemplo.
const ejemploDeRutina = 'Ejemplo:\nTorso A — 60 min\nPress de banca 4x8 + Elevaciones laterales 4x12, 2\' entre rondas\n'
  + 'Dominadas 4x10 + Curl con mancuernas 4x10\n\nPierna A — 60 min\nSentadilla 4x6, 2\' de descanso\n...';

const alternarLevantamiento = (clave) => {
  const lista = props.perfil.barbell_lifts;
  const i = lista.indexOf(clave);
  if (i === -1) lista.push(clave);
  else lista.splice(i, 1);
};

const minutosFueraDeRango = computed(() => {
  const v = props.perfil.session_minutes;
  if (v === '' || v == null) return false;
  return !Number.isInteger(v) || v < MINUTOS_POR_SESION[0] || v > MINUTOS_POR_SESION[1];
});

const fechaCorta = (iso) => {
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' });
};

const labelClass = 'block text-[13px] font-bold text-white/80 mb-1.5';
const botonSecundario = 'flex items-center gap-1.5 px-3 py-2.5 min-h-[44px] rounded-xl border border-white/20 text-[13px] font-bold '
  + 'text-white/85 hover:text-white hover:border-white/40 disabled:opacity-40 disabled:cursor-not-allowed '
  + 'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300]';

const verPrograma = ref(false);
const pidiendoNuevo = ref(false);
const formularioVisible = computed(() => !programaDelCoachConfirmado.value || !!borrador.value || pidiendoNuevo.value);
const programaDelCoachConfirmado = computed(() => props.perfil.mode === 'coach' && props.rutina.source === 'coach'
  && !!props.rutina.confirmed_at && (props.rutina.sessions || []).length > 0);
// En la pantalla del programa, sin los nombres: ya están en sus pestañas.
const resumenCorto = computed(() => resumenDelPrograma.value.split(' · ').slice(0, 2).join(' · '));
const resumenDelPrograma = computed(() => {
  const ses = props.rutina.sessions || [];
  const min = [...new Set(ses.map((x) => x.minutes).filter(Boolean))];
  return [`${ses.length} ${ses.length === 1 ? 'sesión' : 'sesiones'}`, min.length === 1 ? `${min[0]} min cada una` : '',
    ses.map((x) => x.name).join(', ')].filter(Boolean).join(' · ');
});
// Las notas del coach llegan como un párrafo con guiones pegados; se leen como lista.
const notasEnLista = computed(() => {
  // Sin su primera línea si es un título («Cómo progresar (semanas):»): la pantalla ya lo pone.
  const t = String(props.rutina.notes || '').replace(/^\s*c[oó]mo progresar[^\n:]*:\s*/i, '')
    .replace(/\s+-\s+(?=[A-ZÁÉÍÓÚÑ¿])/g, '\n- ');
  return renderMarkdown(t);
});
// Las siglas que use el texto, explicadas debajo. Sólo las que aparecen.
const SIGLAS = [
  ['RIR', 'repeticiones que te quedan en reserva al acabar la serie (RIR 2 = podrías hacer 2 más)'],
  ['RPE', 'esfuerzo percibido, de 1 a 10'],
  ['DOMS', 'agujetas: el dolor muscular de uno o dos días después'],
  ['RDL', 'peso muerto rumano'],
  ['AMRAP', 'tantas repeticiones como puedas'],
  ['doble progresión', 'primero subes repeticiones hasta el máximo del rango; después subes el peso y vuelves al mínimo'],
  ['tope', 'el número más alto del rango de repeticiones (en 8-10, el 10)'],
  ['triserie', 'tres ejercicios seguidos sin descanso entre ellos; se descansa al acabar la ronda'],
  ['superserie', 'dos ejercicios seguidos sin descanso entre ellos'],
  ['accesorios', 'los ejercicios de apoyo, después de los principales'],
  ['hipertrofia', 'ganar músculo'],
  ['hip thrust', 'empuje de cadera con la espalda apoyada en un banco'],
  ['tren inferior', 'piernas y glúteos'],
  ['cadena posterior', 'la parte de atrás: isquiotibiales, glúteos y espalda baja'],
  ['unilateral', 'con una pierna o un brazo cada vez'],
  ['descarga', 'una semana más suave para recuperar'],
];
const siglasDeLasNotas = computed(() => {
  const t = [props.rutina.notes, ...(props.rutina.sessions || []).map((x) => x.why)].filter(Boolean).join(' ');
  return SIGLAS.filter(([sigla]) => new RegExp(`(^|[^\\p{L}])${sigla}([^\\p{L}]|$)`, 'iu').test(t))
    .map(([sigla, texto]) => ({ sigla, texto }));
});
const inputClass = 'w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors';
const optClass = 'bg-[#17191e] text-white';
const marcaOpcional = 'ml-1.5 inline-block px-1.5 py-0.5 rounded-md align-middle '
  + 'bg-white/5 border border-white/15 text-[#8f9378] text-[9px] font-black tracking-wide uppercase';
</script>

