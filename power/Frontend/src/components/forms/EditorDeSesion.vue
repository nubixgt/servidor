<template>
  <!-- Ajustar los pasos de una sesión, sin IA (Beta 3.0, F7). Lo que se edita es lo que va al
       reloj: duración, repeticiones, recuperación y objetivo. El texto de lo que cambia lo
       escribe el servidor; lo que no se toca conserva el del coach. -->
  <!-- Al <body>: la vista de Plan anima con `transform`, y dentro de ella un `fixed` se
       colocaba respecto a la vista (alta como toda la semana) y no a la pantalla. -->
  <Teleport to="body">
  <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-xl"
       role="dialog" aria-labelledby="editor-titulo">
    <div class="glass-panel border border-white/20 rounded-3xl max-w-2xl w-full p-5 sm:p-6 space-y-4 max-h-[90vh] overflow-y-auto hide-scrollbar">
      <div class="flex justify-between items-start gap-3">
        <div>
          <h3 id="editor-titulo" class="text-lg font-black text-white">{{ nuevo ? `Nuevo entreno de ${NOMBRE_DEL_DEPORTE[nuevo.deporte] || 'resistencia'}` : `Ajustar «${titulo}»` }}</h3>
          <p class="text-xs text-[#c5c9ac]">{{ subtitulo }}</p>
        </div>
        <button type="button" @click="cerrar" aria-label="Cerrar" class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <label v-if="nuevo" class="block space-y-1">
        <span class="text-[11px] font-bold text-[#c5c9ac]">Nombre del entreno</span>
        <input v-model="nombreNuevo" maxlength="60" class="campo w-full" />
      </label>

      <p v-if="cargando" class="text-sm text-[#8f9378]">Cargando…</p>
      <p v-else-if="errorDeCarga" class="text-sm text-[#ffb4ab]">{{ errorDeCarga }}</p>

      <template v-else>
        <ol class="space-y-3">
          <li v-for="(e, i) in elementos" :key="i" class="rounded-2xl border border-white/10 bg-white/[0.02] p-3 space-y-2.5">
            <div class="flex items-center justify-between gap-2">
              <span class="text-[10px] uppercase font-black tracking-wider text-[#8f9378]">
                {{ i + 1 }} · {{ e.tipo === 'paso' ? 'Paso' : e.tipo === 'serie' ? 'Serie' : 'Bloque' }}
              </span>
              <div class="flex items-center gap-1">
                <button type="button" @click="subir(i)" :disabled="i === 0" aria-label="Subir" class="p-1 text-[#8f9378] hover:text-white disabled:opacity-30">
                  <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                </button>
                <button type="button" @click="bajar(i)" :disabled="i === elementos.length - 1" aria-label="Bajar" class="p-1 text-[#8f9378] hover:text-white disabled:opacity-30">
                  <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                </button>
                <button type="button" @click="quitar(i)" aria-label="Quitar este paso" class="p-1 text-[#8f9378] hover:text-[#ffb4ab]">
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </div>
            </div>

            <!-- Lo que dice hoy ese paso (el texto del coach, o el último guardado). -->
            <p v-if="e.descripcion && e.tipo !== 'bloque'" class="text-[12px] text-[#c5c9ac] leading-snug">{{ e.descripcion }}</p>

            <!-- Un paso -->
            <template v-if="e.tipo === 'paso'">
              <select v-model="e.step_type" class="campo" aria-label="Qué es este paso">
                <option v-for="(t, k) in TIPOS_DE_PASO" :key="k" :value="k">{{ t }}</option>
              </select>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <CampoDuracion v-model="e.duracion" :deporte="deporte" />
                <CampoObjetivo v-if="deporte !== 'swim'" v-model="e.objetivo" :deporte="deporte" />
              </div>
              <CampoNado v-if="deporte === 'swim'" v-model="e.extra" />
            </template>

            <!-- Una serie -->
            <template v-else-if="e.tipo === 'serie'">
              <label class="flex items-center gap-2 text-xs text-[#c5c9ac]">
                Repeticiones
                <input v-model.number="e.repeticiones" type="number" min="2" max="50" class="campo w-20" />
              </label>
              <p class="text-[10px] uppercase font-black tracking-wider text-[#8f9378]">Cada repetición</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <CampoDuracion v-model="e.trabajo.duracion" :deporte="deporte" />
                <CampoObjetivo v-if="deporte !== 'swim'" v-model="e.trabajo.objetivo" :deporte="deporte" />
              </div>
              <CampoNado v-if="deporte === 'swim'" v-model="e.trabajo.extra" />
              <div class="flex items-center justify-between gap-2">
                <p class="text-[10px] uppercase font-black tracking-wider text-[#8f9378]">Recuperación entre repeticiones</p>
                <button type="button" class="text-[10px] font-black uppercase tracking-wider text-[#a5eeff]"
                        @click="e.recuperacion = e.recuperacion ? null : recuperacionNueva()">
                  {{ e.recuperacion ? 'Quitar' : 'Añadir' }}
                </button>
              </div>
              <div v-if="e.recuperacion" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <CampoDuracion v-model="e.recuperacion.duracion" :deporte="deporte" :con-vuelta="true" />
                <CampoObjetivo v-if="deporte !== 'swim'" v-model="e.recuperacion.objetivo" :deporte="deporte" :solo-zona="true" />
              </div>
            </template>

            <!-- Un bloque con otra forma: sólo las repeticiones -->
            <template v-else>
              <p class="text-sm text-white">{{ e.descripcion }}</p>
              <label class="flex items-center gap-2 text-xs text-[#c5c9ac]">
                Repeticiones
                <input v-model.number="e.repeticiones" type="number" min="2" max="50" class="campo w-20" />
              </label>
              <p class="text-[11px] text-[#8f9378]">Este bloque tiene otra forma: aquí sólo cambian sus repeticiones.</p>
            </template>

            <!-- Qué es, en palabras del atleta: se añade al texto del paso (en la app y en el reloj). -->
            <div v-if="e.tipo !== 'bloque'" class="flex flex-wrap items-center gap-2">
              <input v-model="e.nota" maxlength="120" class="campo flex-1 min-w-[180px]"
                     :placeholder="deporte === 'swim' ? 'Nota: crol, patada con tabla, técnica…' : 'Nota: en cuesta, con amigos…'"
                     :aria-label="`Nota del paso ${i + 1}`" />
              <button v-if="['swim', 'run'].includes(deporte)" type="button" @click="drillPara = i"
                      class="px-2.5 py-1.5 rounded-lg border border-[#a5eeff]/40 text-[11px] font-black uppercase tracking-wider text-[#a5eeff] hover:bg-[#a5eeff]/10 min-h-[36px]">
                Elegir un drill
              </button>
            </div>
          </li>
        </ol>

        <div v-if="plantillas.length" class="flex flex-wrap items-center gap-2">
          <span class="text-[10px] uppercase font-black tracking-wider text-[#8f9378]">Añadir</span>
          <button v-for="(p, i) in plantillas" :key="i" type="button" @click="anadir(p)"
                  class="px-2.5 py-1.5 glass-pill rounded-lg text-[11px] font-bold text-[#c5c9ac] hover:text-white">
            + {{ p.nombre }}
          </button>
        </div>
        <p v-if="deporte === 'swim'" class="text-[11px] text-[#8f9378]">En natación tu reloj no lleva objetivo de ritmo ni de pulso: sólo distancia o tiempo.</p>

        <div v-if="vista" class="rounded-2xl border border-white/10 px-3.5 py-3 space-y-1.5">
          <p class="text-sm text-white">{{ vista.resumen }}</p>
          <ul v-if="vista.avisos?.length" class="space-y-1">
            <li v-for="(a, i) in vista.avisos" :key="i" class="text-[11px] text-[#ffd166] flex gap-1.5">
              <span class="material-symbols-outlined text-[14px]">info</span><span>{{ a }}</span>
            </li>
          </ul>
        </div>
        <p v-if="error" class="text-xs text-[#ffb4ab]">{{ error }}</p>

        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
          <span class="text-[11px] font-mono-data text-[#8f9378]">{{ minutosTexto }}</span>
          <div class="flex gap-2">
            <button type="button" @click="verComoQueda" :disabled="ocupado || !elementos.length"
                    class="px-4 py-2.5 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white disabled:opacity-50">
              Ver cómo queda
            </button>
            <button type="button" @click="guardar" :disabled="ocupado || !elementos.length"
                    class="px-4 py-2.5 rounded-xl bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-[11px] font-black uppercase tracking-wider disabled:opacity-50">
              {{ ocupado ? 'Un momento…' : (nuevo ? 'Añadir a mi semana' : 'Guardar') }}
            </button>
          </div>
        </div>
        <p class="text-[11px] text-[#8f9378]">Tu reloj no cambia hasta que pulses «Enviar al reloj».</p>
      </template>
    </div>
  </div>
  <CatalogoDeEjercicios v-if="drillPara !== null" titulo="Elige un drill"
                        :grupos="deporte === 'swim' ? ['natacion'] : ['carrera']"
                        @elegir="ponerDrill" @cerrar="drillPara = null" />
  </Teleport>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue';
import { editarSesion } from '../../services/plan';
import swal from '../../utils/swal';
import CatalogoDeEjercicios from './CatalogoDeEjercicios.vue';

const props = defineProps({
  sessionKey: { type: String, default: '' },
  titulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' },
  planVersion: { type: String, default: '' },
  // Un entreno nuevo que añade el atleta (2026-10-23): { fecha, deporte }. Sin él, se ajusta `sessionKey`.
  nuevo: { type: Object, default: null },
});
const emit = defineEmits(['cerrar', 'guardado']);

const TIPOS_DE_PASO = { warmup: 'Calentamiento', main: 'Principal', interval: 'Trabajo', recovery: 'Recuperación', cooldown: 'Enfriamiento' };
const NOMBRE_DEL_DEPORTE = { run: 'carrera', bike: 'bici', swim: 'natación', walk: 'caminata' };
const TITULO_POR_DEFECTO = { run: 'Carrera', bike: 'Bici', swim: 'Natación', walk: 'Caminata' };

const cargando = ref(true);
const errorDeCarga = ref('');
const elementos = ref([]);
const plantillas = ref([]);
const deporte = ref(props.nuevo?.deporte || '');
const vista = ref(null);
const error = ref('');
const ocupado = ref(false);
const nombreNuevo = ref(TITULO_POR_DEFECTO[props.nuevo?.deporte] || '');
const tocado = ref(false);
const drillPara = ref(null);

const copiar = (x) => JSON.parse(JSON.stringify(x));

onMounted(async () => {
  try {
    const r = await editarSesion(props.nuevo
      ? { op: 'nuevo', deporte: props.nuevo.deporte, planVersion: props.planVersion }
      : { op: 'editor', sessionKey: props.sessionKey, planVersion: props.planVersion });
    elementos.value = r.editor?.elementos || [];
    plantillas.value = r.editor?.plantillas || [];
    deporte.value = r.editor?.deporte || deporte.value;
  } catch (e) {
    errorDeCarga.value = e?.response?.data?.message || 'No se pudo abrir el editor.';
  } finally {
    cargando.value = false;
  }
  // Cualquier cambio deja vieja la vista previa (y avisa al cerrar).
  watch(elementos, () => { vista.value = null; error.value = ''; tocado.value = true; }, { deep: true });
});

// Un drill del catálogo: su nombre va a la nota del paso; en natación, además, el paso es de técnica
// (el reloj lo lleva como tal: `drill` en garmin_vocabulary.swim_fields).
const ponerDrill = (c) => {
  const e = elementos.value[drillPara.value];
  drillPara.value = null;
  if (!e) return;
  e.nota = c.es;
  if (deporte.value === 'swim') {
    const objetivo = e.tipo === 'serie' ? e.trabajo : e;
    objetivo.extra = { ...(objetivo.extra || {}), drill: 'drill' };
  }
};

const cerrar = async () => {
  if (tocado.value) {
    const r = await swal.fire({ title: '¿Salir sin guardar?', text: 'Los cambios de este editor se pierden.',
      showCancelButton: true, confirmButtonText: 'Salir', cancelButtonText: 'Seguir editando' });
    if (!r.isConfirmed) return;
  }
  emit('cerrar');
};

const subir = (i) => { const l = elementos.value; [l[i - 1], l[i]] = [l[i], l[i - 1]]; };
const bajar = (i) => { const l = elementos.value; [l[i + 1], l[i]] = [l[i], l[i + 1]]; };
const quitar = (i) => elementos.value.splice(i, 1);
// Lo añadido va a su sitio (prueba del 2026-10-23: «Series al umbral» y «Series de 100 m» quedaban DESPUÉS
// del enfriamiento): un calentamiento tras los calentamientos, un enfriamiento al final y lo demás antes del
// enfriamiento. Después se puede mover con las flechas.
const esDeTipo = (el, tipo) => el?.tipo === 'paso' && el.step_type === tipo;
const anadir = (p) => {
  const { nombre, ...e } = copiar(p);
  const l = elementos.value;
  if (esDeTipo(e, 'cooldown')) { l.push(e); return; }
  if (esDeTipo(e, 'warmup')) {
    let i = 0;
    while (i < l.length && esDeTipo(l[i], 'warmup')) i += 1;
    l.splice(i, 0, e);
    return;
  }
  let i = l.length;
  while (i > 0 && esDeTipo(l[i - 1], 'cooldown')) i -= 1;
  l.splice(i, 0, e);
};
const recuperacionNueva = () => ({ step_type: 'recovery', duracion: { tipo: 'time', valor: 60 }, objetivo: { tipo: 'hr_zone', zona: 1 } });

// Lo que suma: los minutos de lo que va por tiempo y los metros de lo que va por distancia, cada uno en su
// unidad (en natación decía «5' + lo que va por distancia», y lo que el nadador cuenta son los metros).
const minutosTexto = computed(() => {
  let s = 0;
  let metros = 0;
  let sinCuenta = false;
  const sumar = (p, n = 1) => {
    const valor = n * (Number(p?.duracion?.valor) || 0);
    if (p?.duracion?.tipo === 'time') s += valor;
    else if (p?.duracion?.tipo === 'distance') metros += valor;
    else sinCuenta = true;
  };
  for (const e of elementos.value) {
    if (e.tipo === 'paso') sumar(e);
    else if (e.tipo === 'serie') { sumar(e.trabajo, e.repeticiones || 0); if (e.recuperacion) sumar(e.recuperacion, e.repeticiones || 0); }
    else sinCuenta = true;
  }
  const partes = [];
  if (metros) partes.push(`${metros.toLocaleString('es-ES')} m`);
  if (s) partes.push(`${Math.round(s / 60)}'`);
  if (sinCuenta) partes.push('lo que va hasta pulsar vuelta o por bloques');
  return partes.join(' + ') || "0'";
});

const pedir = (dryRun) => editarSesion(props.nuevo
  ? { op: 'anadir', toDate: props.nuevo.fecha, deporte: deporte.value, titulo: nombreNuevo.value,
    planVersion: props.planVersion, elementos: elementos.value, dryRun }
  : { op: 'ajustar', sessionKey: props.sessionKey, planVersion: props.planVersion, elementos: elementos.value, dryRun });

const verComoQueda = async () => {
  ocupado.value = true;
  error.value = '';
  try {
    vista.value = (await pedir(true)).preview;
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo calcular.';
  } finally {
    ocupado.value = false;
  }
};

const guardar = async () => {
  ocupado.value = true;
  error.value = '';
  try {
    const r = await pedir(false);
    emit('guardado', r);
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo guardar.';
  } finally {
    ocupado.value = false;
  }
};

// ---------------------------------------------------------------- campos
const clase = 'campo';
const mmss = (s) => (s ? `${Math.floor(s / 60)}:${String(Math.round(s % 60)).padStart(2, '0')}` : '');
const deMmss = (t) => {
  const m = /^\s*(\d{1,2}):([0-5]?\d)\s*$/.exec(String(t || ''));
  return m ? Number(m[1]) * 60 + Number(m[2]) : null;
};

const CampoDuracion = defineComponent({
  props: { modelValue: { type: Object, required: true }, deporte: String, conVuelta: Boolean },
  emits: ['update:modelValue'],
  setup(p, { emit: e }) {
    const poner = (cambio) => e('update:modelValue', { ...p.modelValue, ...cambio });
    return () => {
      const d = p.modelValue || {};
      const hijos = [h('select', {
        class: clase, value: d.tipo, 'aria-label': 'Cómo termina',
        onChange: (ev) => poner({ tipo: ev.target.value, valor: ev.target.value === 'lap_button' ? null : (d.valor || (ev.target.value === 'distance' ? 400 : 60)) }),
      }, [h('option', { value: 'time' }, 'Por tiempo'), h('option', { value: 'distance' }, 'Por distancia'),
        h('option', { value: 'lap_button' }, 'Hasta pulsar vuelta')])];
      if (d.tipo === 'time') {
        const s = Number(d.valor) || 0;
        hijos.push(h('div', { class: 'flex items-center gap-1' }, [
          h('input', { class: `${clase} w-16`, type: 'number', min: 0, max: 360, value: Math.floor(s / 60), 'aria-label': 'Minutos',
            onInput: (ev) => poner({ valor: (Number(ev.target.value) || 0) * 60 + (s % 60) }) }),
          h('span', { class: 'text-xs text-[#8f9378]' }, 'min'),
          h('input', { class: `${clase} w-16`, type: 'number', min: 0, max: 59, value: Math.round(s % 60), 'aria-label': 'Segundos',
            onInput: (ev) => poner({ valor: Math.floor(s / 60) * 60 + (Number(ev.target.value) || 0) }) }),
          h('span', { class: 'text-xs text-[#8f9378]' }, 's'),
        ]));
      } else if (d.tipo === 'distance') {
        hijos.push(h('div', { class: 'flex items-center gap-1' }, [
          h('input', { class: `${clase} w-24`, type: 'number', min: 1, step: p.deporte === 'swim' ? 25 : 100, value: d.valor, 'aria-label': 'Metros',
            onInput: (ev) => poner({ valor: Number(ev.target.value) || null }) }),
          h('span', { class: 'text-xs text-[#8f9378]' }, 'm'),
        ]));
      }
      return h('div', { class: 'flex flex-wrap items-center gap-2' }, hijos);
    };
  },
});

// Natación: tipo (nado completo, patada, brazos, técnica), estilo y material. Son campos que el reloj lleva
// como suyos (garmin_vocabulary.swim_fields) y que el texto del paso dice en palabras (describir_paso).
const OPCIONES_DE_NADO = {
  drill: ['Tipo', [['', 'Nado completo'], ['kick', 'Patada'], ['pull', 'Brazos'], ['drill', 'Técnica']]],
  stroke: ['Estilo', [['', 'Sin indicar'], ['free', 'Crol'], ['backstroke', 'Espalda'], ['breaststroke', 'Braza'],
    ['fly', 'Mariposa'], ['individual_medley', 'Estilos'], ['any_stroke', 'Libre']]],
  equipment: ['Material', [['', 'Sin material'], ['pull_buoy', 'Pull buoy'], ['paddles', 'Palas'], ['fins', 'Aletas'],
    ['kickboard', 'Tabla'], ['snorkel', 'Tubo']]],
};
const CampoNado = defineComponent({
  props: { modelValue: { type: Object, default: null } },
  emits: ['update:modelValue'],
  setup(p, { emit: e }) {
    const poner = (k, v) => {
      const nuevo = { ...(p.modelValue || {}) };
      if (v) nuevo[k] = v; else delete nuevo[k];
      e('update:modelValue', nuevo);
    };
    return () => h('div', { class: 'grid grid-cols-1 sm:grid-cols-3 gap-2' }, Object.entries(OPCIONES_DE_NADO).map(([k, [nombre, ops]]) => (
      h('label', { class: 'space-y-0.5' }, [
        h('span', { class: 'block text-[10px] font-black uppercase tracking-wider text-[#8f9378]' }, nombre),
        h('select', { class: `${clase} w-full`, value: (p.modelValue || {})[k] || '', onChange: (ev) => poner(k, ev.target.value) },
          ops.map(([v, t]) => h('option', { value: v }, t))),
      ])
    )));
  },
});

const CampoObjetivo = defineComponent({
  props: { modelValue: { type: Object, default: () => ({ tipo: 'none' }) }, deporte: String, soloZona: Boolean },
  emits: ['update:modelValue'],
  setup(p, { emit: e }) {
    const poner = (cambio) => e('update:modelValue', { ...p.modelValue, ...cambio });
    return () => {
      const o = p.modelValue || { tipo: 'none' };
      const opciones = [h('option', { value: 'none' }, 'Sin objetivo'), h('option', { value: 'hr_zone' }, 'Zona de pulso')];
      if (!p.soloZona && p.deporte === 'run') opciones.push(h('option', { value: 'pace' }, 'Ritmo'));
      if (!p.soloZona && p.deporte === 'bike') opciones.push(h('option', { value: 'power' }, 'Potencia'));
      const hijos = [h('select', {
        class: clase, value: o.tipo || 'none', 'aria-label': 'Objetivo',
        onChange: (ev) => poner({ tipo: ev.target.value, zona: ev.target.value === 'hr_zone' ? (o.zona || 2) : null, bajo: null, alto: null }),
      }, opciones)];
      if (o.tipo === 'hr_zone') {
        hijos.push(h('select', { class: clase, value: o.zona, 'aria-label': 'Zona',
          onChange: (ev) => poner({ zona: Number(ev.target.value) }) },
        [1, 2, 3, 4, 5].map((z) => h('option', { value: z }, `Z${z}`))));
      } else if (o.tipo === 'pace') {
        const campo = (clave, etiqueta) => h('input', { class: `${clase} w-20`, placeholder: 'm:ss', value: mmss(o[clave]), 'aria-label': etiqueta,
          onChange: (ev) => poner({ [clave]: deMmss(ev.target.value) }) });
        hijos.push(h('div', { class: 'flex items-center gap-1' }, [campo('bajo', 'Ritmo rápido'), h('span', { class: 'text-xs text-[#8f9378]' }, '–'),
          campo('alto', 'Ritmo lento'), h('span', { class: 'text-xs text-[#8f9378]' }, '/km')]));
      } else if (o.tipo === 'power') {
        const campo = (clave, etiqueta) => h('input', { class: `${clase} w-20`, type: 'number', min: 1, value: o[clave], 'aria-label': etiqueta,
          onInput: (ev) => poner({ [clave]: Number(ev.target.value) || null }) });
        hijos.push(h('div', { class: 'flex items-center gap-1' }, [campo('bajo', 'Vatios mínimos'), h('span', { class: 'text-xs text-[#8f9378]' }, '–'),
          campo('alto', 'Vatios máximos'), h('span', { class: 'text-xs text-[#8f9378]' }, 'W')]));
      }
      return h('div', { class: 'flex flex-wrap items-center gap-2' }, hijos);
    };
  },
});
</script>

<style scoped>
:deep(.campo) {
  background: #1b1d22;
  color: #f1f0ee;
  /* Bordes de los campos a 3:1 contra el fondo, como en el resto de la app (revisión del 2026-10-21). */
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 0.75rem;
  padding: 0.4rem 0.6rem;
  font-size: 0.8rem;
  min-height: 36px;
}
:deep(.campo:focus) {
  outline: none;
  border-color: rgba(202, 243, 0, 0.6);
}
</style>
