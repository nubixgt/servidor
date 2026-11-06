<template>
  <!-- Ajustar la fuerza de ESTA semana, sin IA (decisión del responsable, 2026-10-23): cambiar un ejercicio por
       otro del catálogo, añadir uno (al final o seguido de otro), quitar, ordenar y cambiar series, repeticiones,
       carga y descanso. Su programa no cambia: eso se hace desde «Tu fuerza» del perfil.
       También sirve para un entreno de fuerza nuevo que añade el atleta (`nuevo`). -->
  <Teleport to="body">
  <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-xl"
       role="dialog" aria-modal="true" aria-labelledby="fuerza-titulo">
    <div class="glass-panel border border-white/20 rounded-3xl max-w-2xl w-full p-4 sm:p-6 space-y-4 max-h-[92vh] overflow-y-auto hide-scrollbar">
      <div class="flex justify-between items-start gap-3">
        <div class="min-w-0">
          <h3 id="fuerza-titulo" class="text-lg font-black text-white">{{ nuevo ? 'Nuevo entreno de fuerza' : `Ajustar «${titulo}»` }}</h3>
          <p class="text-xs text-[#c5c9ac]">{{ subtitulo }}</p>
        </div>
        <button type="button" @click="cerrar" aria-label="Cerrar" class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <p class="text-[12px] text-[#c5c9ac] leading-relaxed rounded-xl bg-white/[0.04] px-3 py-2">
        Cambia {{ nuevo ? 'este entreno' : 'sólo esta sesión de esta semana' }}. Para cambiar tu programa de fuerza para
        todas las semanas, ve a <router-link to="/admin/perfil?tab=objetivos" class="underline font-bold text-white" @click="$emit('cerrar')">Perfil → Tu fuerza</router-link>.
      </p>

      <label v-if="nuevo" class="block space-y-1">
        <span class="text-[11px] font-bold text-[#c5c9ac]">Nombre del entreno</span>
        <input v-model="nombreNuevo" maxlength="60" class="campo w-full" placeholder="Fuerza" />
      </label>

      <p v-if="cargando" class="text-sm text-[#8f9378]">Cargando…</p>
      <p v-else-if="errorDeCarga" class="text-sm text-[#ffb4ab]">{{ errorDeCarga }}</p>

      <template v-else>
        <p v-if="!grupos.length" class="text-sm text-[#c5c9ac]">Todavía no hay ejercicios: añade el primero del catálogo.</p>
        <ol class="space-y-3">
          <li v-for="(g, gi) in grupos" :key="g.id" class="rounded-2xl border border-white/10 bg-white/[0.02] p-3 space-y-2.5">
            <div class="flex items-center justify-between gap-2">
              <span class="text-[11px] uppercase font-black tracking-wider text-[#caf300]">
                {{ letra(gi) }}<span v-if="g.ejercicios.length > 1" class="text-[#8f9378]"> · seguidos, ronda a ronda</span>
              </span>
              <div class="flex items-center gap-0.5">
                <button type="button" @click="moverGrupo(gi, -1)" :disabled="gi === 0" :aria-label="`Subir el grupo ${letra(gi)}`" class="boton-icono">
                  <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                </button>
                <button type="button" @click="moverGrupo(gi, 1)" :disabled="gi === grupos.length - 1" :aria-label="`Bajar el grupo ${letra(gi)}`" class="boton-icono">
                  <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                </button>
              </div>
            </div>

            <ul class="space-y-2.5">
              <li v-for="(e, ei) in g.ejercicios" :key="e._k" class="rounded-xl border border-white/10 bg-black/20 p-2.5 space-y-2">
                <div class="flex items-start gap-2.5">
                  <img v-if="imagen(e)" :src="imagen(e)" alt="" class="w-12 h-12 rounded-lg object-cover bg-white shrink-0" />
                  <span v-else class="w-12 h-12 rounded-lg bg-white/5 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[#8f9378]">fitness_center</span>
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="text-[14px] font-bold text-white leading-snug">{{ nombreDe(e) }}</p>
                    <p v-if="etiqueta(e)" class="text-[11px] font-bold text-[#ffd166]">{{ etiqueta(e) }}</p>
                  </div>
                  <div class="flex items-center gap-0.5 shrink-0">
                    <button type="button" @click="moverEjercicio(gi, ei, -1)" :disabled="gi === 0 && ei === 0"
                            :aria-label="`Subir ${nombreDe(e)}`" class="boton-icono"><span class="material-symbols-outlined text-[18px]">arrow_upward</span></button>
                    <button type="button" @click="moverEjercicio(gi, ei, 1)" :disabled="gi === grupos.length - 1 && ei === g.ejercicios.length - 1"
                            :aria-label="`Bajar ${nombreDe(e)}`" class="boton-icono"><span class="material-symbols-outlined text-[18px]">arrow_downward</span></button>
                    <button type="button" @click="quitar(gi, ei)" :aria-label="`Quitar ${nombreDe(e)}`" class="boton-icono hover:!text-[#ffb4ab]">
                      <span class="material-symbols-outlined text-[18px]">delete</span></button>
                  </div>
                </div>
                <!-- La carga es lo más largo que escribe el coach («Tensión que te deje 2-3 reps en recámara»):
                     va en su propia fila para que no se corte. En el teléfono, repeticiones también. -->
                <div class="grid gap-2" :class="g.ejercicios.length === 1
                  ? 'grid-cols-2 sm:grid-cols-[4.5rem_minmax(0,1fr)_6.5rem]'
                  : 'grid-cols-[4.5rem_minmax(0,1fr)]'">
                  <label class="space-y-0.5"><span class="etiqueta">Series</span>
                    <input v-model.number="e.series" type="number" min="1" max="10" class="campo w-full" /></label>
                  <label class="space-y-0.5 min-w-0" :class="g.ejercicios.length === 1 ? 'col-span-2 sm:col-span-1 order-3 sm:order-none' : ''">
                    <span class="etiqueta">Repeticiones</span>
                    <textarea v-model="e.reps" maxlength="60" class="campo w-full resize-none leading-snug" placeholder="8-10, 30 s…"
                              :rows="filas(e.reps, g.ejercicios.length === 1 ? 'ancho' : 'medio')" @keydown.enter.prevent></textarea></label>
                  <label v-if="g.ejercicios.length === 1" class="space-y-0.5 order-2 sm:order-none"><span class="etiqueta">Descanso (s)</span>
                    <input v-model.number="e.descanso" type="number" min="0" max="600" step="15" class="campo w-full" /></label>
                  <label class="space-y-0.5 min-w-0 order-4 sm:order-none" :class="g.ejercicios.length === 1 ? 'col-span-2 sm:col-span-3' : 'col-span-2'">
                    <span class="etiqueta">Carga</span>
                    <textarea v-model="e.carga" maxlength="60" class="campo w-full resize-none leading-snug" placeholder="RIR 2, 20 kg…"
                              :rows="filas(e.carga, 'ancho')" @keydown.enter.prevent></textarea></label>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <button type="button" @click="abrirCatalogo({ grupo: gi, ejercicio: ei })"
                          class="px-2.5 py-1.5 rounded-lg border border-[#a5eeff]/40 text-[11px] font-black uppercase tracking-wider text-[#a5eeff] hover:bg-[#a5eeff]/10 min-h-[34px]">
                    Cambiar por otro
                  </button>
                  <button type="button" @click="e._pista = !e._pista" class="px-2 py-1.5 text-[11px] font-bold text-[#8f9378] hover:text-white min-h-[34px]">
                    {{ e._pista ? 'Ocultar la nota' : (e.pista ? 'Ver la nota' : 'Añadir una nota') }}
                  </button>
                </div>
                <input v-if="e._pista" v-model="e.pista" maxlength="200" class="campo w-full" placeholder="Qué vigilar al hacerlo" aria-label="Nota del ejercicio" />
              </li>
            </ul>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
              <button type="button" @click="abrirCatalogo({ grupo: gi })"
                      class="px-2.5 py-1.5 glass-pill rounded-lg text-[11px] font-bold text-[#c5c9ac] hover:text-white min-h-[34px]">
                + Añadir seguido en {{ letra(gi) }}
              </button>
              <label v-if="g.ejercicios.length > 1" class="flex items-center gap-1.5 text-[11px] text-[#c5c9ac]">
                Descanso entre rondas
                <input v-model.number="g.descanso_rondas" type="number" min="0" max="600" step="15" class="campo w-20" aria-label="Descanso entre rondas, en segundos" />
                <span>s</span>
              </label>
            </div>
          </li>
        </ol>

        <button type="button" @click="abrirCatalogo({})"
                class="w-full px-3 py-3 rounded-2xl border border-dashed border-[#caf300]/50 text-[12px] font-black uppercase tracking-wider text-[#caf300] hover:bg-[#caf300]/5">
          + Añadir un ejercicio al final
        </button>

        <div v-if="vista" class="rounded-2xl border border-white/10 px-3.5 py-3 space-y-1.5">
          <p class="text-sm text-white">{{ vista.resumen }}</p>
          <ul v-if="vista.avisos?.length" class="space-y-1">
            <li v-for="(a, i) in vista.avisos" :key="i" class="text-[11px] text-[#ffd166] flex gap-1.5">
              <span class="material-symbols-outlined text-[14px]">info</span><span>{{ a }}</span>
            </li>
          </ul>
        </div>
        <p v-if="error" class="text-xs text-[#ffb4ab]">{{ error }}</p>

        <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
          <button type="button" @click="verComoQueda" :disabled="ocupado || !grupos.length"
                  class="px-4 py-2.5 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white disabled:opacity-50">
            Ver cómo queda
          </button>
          <button type="button" @click="guardar" :disabled="ocupado || !grupos.length"
                  class="px-4 py-2.5 rounded-xl bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-[11px] font-black uppercase tracking-wider disabled:opacity-50">
            {{ ocupado ? 'Un momento…' : (nuevo ? 'Añadir a mi semana' : 'Guardar') }}
          </button>
        </div>
        <p class="text-[11px] text-[#8f9378]">Tu reloj no cambia hasta que pulses «Enviar al reloj».</p>
      </template>
    </div>
  </div>

  <CatalogoDeEjercicios v-if="catalogo"
                        :titulo="catalogo.ejercicio !== undefined ? 'Cambiar por otro ejercicio' : 'Añadir un ejercicio'"
                        :sugerir="catalogo.sugerir" :en-vez-de="catalogo.enVezDe" :excluir="catalogo.excluir"
                        @elegir="alElegir" @cerrar="catalogo = null" />
  </Teleport>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { editarSesion } from '../../services/plan';
import { cargarCatalogo, resolver } from '../../utils/ejercicios';
import swal from '../../utils/swal';
import CatalogoDeEjercicios from './CatalogoDeEjercicios.vue';

const props = defineProps({
  sessionKey: { type: String, default: '' },
  titulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' },
  planVersion: { type: String, default: '' },
  // Un entreno nuevo: { fecha }. Sin él, se ajusta la sesión `sessionKey`.
  nuevo: { type: Object, default: null },
});
const emit = defineEmits(['cerrar', 'guardado']);

const cargando = ref(true);
const errorDeCarga = ref('');
const grupos = ref([]);
const vista = ref(null);
const error = ref('');
const ocupado = ref(false);
const catalogo = ref(null);
const nombreNuevo = ref('Fuerza');
const tocado = ref(false);
let siguiente = 0;
const k = () => `e${siguiente += 1}`;

onMounted(async () => {
  await cargarCatalogo();
  try {
    const r = await editarSesion(props.nuevo
      ? { op: 'nuevo', deporte: 'strength', planVersion: props.planVersion }
      : { op: 'editor', sessionKey: props.sessionKey, planVersion: props.planVersion });
    grupos.value = (r.editor?.grupos || []).map((g) => ({ ...g, ejercicios: (g.ejercicios || []).map((e) => ({ ...e, _k: k() })) }));
  } catch (e) {
    errorDeCarga.value = e?.response?.data?.message || 'No se pudo abrir el editor.';
  } finally {
    cargando.value = false;
  }
  watch(grupos, () => { vista.value = null; error.value = ''; tocado.value = true; }, { deep: true });
});

// Repeticiones y carga son texto del coach («Tensión que te deje 2-3 reps en recámara»): el campo crece a dos
// o tres líneas antes que cortarlo. Cuántas letras caben por línea, a ojo, en el teléfono y en pantalla ancha.
const estrecho = window.matchMedia('(max-width: 639px)').matches;
const CABEN = { ancho: estrecho ? 32 : 64, medio: estrecho ? 20 : 52 };
const filas = (texto, sitio) => Math.min(3, Math.max(1, Math.ceil(String(texto || '').length / CABEN[sitio])));
const letra = (i) => String.fromCharCode(65 + (i % 26)) + (i >= 26 ? String(Math.floor(i / 26)) : '');
const delCatalogo = (e) => resolver(e.nombre, e.slug);
const nombreDe = (e) => (e.origen === 'coach' ? e.nombre : (delCatalogo(e)?.es || e.nombre || 'Ejercicio'));
const imagen = (e) => { const c = delCatalogo(e); return c?.img ? `${import.meta.env.BASE_URL}ejercicios/${c.img}` : null; };
const etiqueta = (e) => {
  if (e.origen !== 'coach') return e.en_vez_de ? `En vez de ${e.en_vez_de}` : 'Lo añades tú';
  return '';
};

const quitar = (gi, ei) => {
  const g = grupos.value[gi];
  g.ejercicios.splice(ei, 1);
  if (!g.ejercicios.length) grupos.value.splice(gi, 1);
};
const moverGrupo = (gi, d) => {
  const l = grupos.value;
  [l[gi], l[gi + d]] = [l[gi + d], l[gi]];
};
// Un ejercicio sube o baja dentro de su grupo; en el borde, pasa al grupo de al lado como uno suelto.
const moverEjercicio = (gi, ei, d) => {
  const l = grupos.value;
  const g = l[gi];
  const destino = ei + d;
  if (destino >= 0 && destino < g.ejercicios.length) {
    [g.ejercicios[ei], g.ejercicios[destino]] = [g.ejercicios[destino], g.ejercicios[ei]];
    return;
  }
  const [e] = g.ejercicios.splice(ei, 1);
  const suelto = { id: `n${k()}`, descanso_rondas: null, descanso_ejercicios: null, ejercicios: [e] };
  const vacio = !g.ejercicios.length;
  if (d < 0) l.splice(gi, vacio ? 1 : 0, suelto);
  else l.splice(gi + 1, 0, suelto);
  if (vacio && d > 0) l.splice(gi, 1);
};

const abrirCatalogo = ({ grupo, ejercicio }) => {
  const actual = grupo !== undefined && ejercicio !== undefined ? grupos.value[grupo].ejercicios[ejercicio] : null;
  catalogo.value = { grupo, ejercicio, sugerir: actual ? (delCatalogo(actual)?.sub || '') : '', enVezDe: actual ? nombreDe(actual) : '',
    excluir: actual ? (delCatalogo(actual)?.slug || '') : '' };
};
// Lo que se sostiene (una plancha, un estiramiento) va por tiempo: en el reloj real del atleta 2 (2026-10-23)
// una «Plancha frontal» añadida con «10» llegó como 10 repeticiones.
const SE_SOSTIENE = /plancha|sostenid|isom[eé]tric|hollow|pared|colgad|copenhague/i;
const repsPorDefecto = (c) => (c.grupo === 'movilidad' || SE_SOSTIENE.test(c.es) ? '30 s' : '10');
const alElegir = (c) => {
  const { grupo, ejercicio } = catalogo.value;
  catalogo.value = null;
  const base = { _k: k(), origen: 'nuevo', slug: c.slug, nombre: c.es, series: 3, reps: repsPorDefecto(c), carga: '', descanso: 90, pista: '' };
  if (grupo !== undefined && ejercicio !== undefined) {
    const viejo = grupos.value[grupo].ejercicios[ejercicio];
    // Cambiar uno por otro conserva sus series, repeticiones y carga: es lo que estaba haciendo.
    grupos.value[grupo].ejercicios[ejercicio] = {
      ...base, series: viejo.series || 3, reps: viejo.reps || '10', carga: viejo.carga || '', descanso: viejo.descanso ?? 90,
      // Si ya lo habías cambiado tú, sigue siendo «en vez de» el del coach, no del que pusiste antes.
      en_vez_de: viejo.origen === 'coach'
        ? (viejo.por_ti && viejo.cambio?.startsWith('en vez de ') ? viejo.cambio.slice('en vez de '.length) : viejo.nombre)
        : (viejo.en_vez_de || ''),
    };
  } else if (grupo !== undefined) {
    grupos.value[grupo].ejercicios.push(base);
  } else {
    grupos.value.push({ id: `n${k()}`, descanso_rondas: null, descanso_ejercicios: null, ejercicios: [base] });
  }
};

const cuerpo = () => grupos.value.map((g) => ({
  id: g.id, descanso_rondas: g.descanso_rondas, descanso_ejercicios: g.descanso_ejercicios,
  ejercicios: g.ejercicios.map(({ _k, _pista, ...e }) => e),
}));
const pedir = (dryRun) => editarSesion(props.nuevo
  ? { op: 'anadir', toDate: props.nuevo.fecha, deporte: 'strength', titulo: nombreNuevo.value, grupos: cuerpo(), planVersion: props.planVersion, dryRun }
  : { op: 'ajustar', sessionKey: props.sessionKey, grupos: cuerpo(), planVersion: props.planVersion, dryRun });

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
    emit('guardado', await pedir(false));
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo guardar.';
  } finally {
    ocupado.value = false;
  }
};
// Cerrar con cambios pregunta antes: en el móvil, un toque fuera del botón no puede tirar diez minutos.
const cerrar = async () => {
  if (tocado.value) {
    const r = await swal.fire({ title: '¿Salir sin guardar?', text: 'Los cambios de este editor se pierden.',
      showCancelButton: true, confirmButtonText: 'Salir', cancelButtonText: 'Seguir editando' });
    if (!r.isConfirmed) return;
  }
  emit('cerrar');
};
</script>

<style scoped>
:deep(.campo) {
  background: #1b1d22;
  color: #f1f0ee;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 0.75rem;
  padding: 0.45rem 0.6rem;
  font-size: 0.85rem;
  min-height: 38px;
}
:deep(.campo:focus) {
  outline: none;
  border-color: rgba(202, 243, 0, 0.7);
}
.etiqueta {
  display: block;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8f9378;
}
.boton-icono {
  padding: 0.4rem;
  min-width: 34px;
  min-height: 34px;
  border-radius: 0.6rem;
  color: #8f9378;
}
.boton-icono:hover:not(:disabled) {
  color: #fff;
  background: rgba(255, 255, 255, 0.06);
}
.boton-icono:disabled {
  opacity: 0.3;
}
</style>
