<template>
  <!-- EL SCROLL LO LLEVA EL FONDO, Y EL CENTRADO UNA CAPA APARTE.
       Antes el fondo era a la vez `flex items-center` y `overflow-y-auto`.
       Con un formulario mas alto que la pantalla -- en el movil siempre, en
       un portatil casi siempre -- `items-center` lo centra y la parte de
       arriba queda en una zona NEGATIVA a la que el scroll no llega: la
       cabecera y el boton de cerrar desaparecian y no habia forma de
       arrastrar hasta ellos. La capa intermedia con `min-h-full` crece con el
       contenido, asi que el fondo empieza a desplazarse desde arriba, y solo
       centra cuando de verdad cabe. -->
  <div
    v-if="store.isCheckinOpen"
    id="checkin-modal-backdrop"
    class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-black/75 backdrop-blur-xl animate-scale-in"
  >
    <div class="flex min-h-full items-start sm:items-center justify-center p-3 sm:p-6">
    <div class="glass-panel border border-white/20 rounded-3xl max-w-xl w-full my-3 sm:my-6 p-5 sm:p-8 space-y-6 shadow-[0_0_60px_rgba(0,0,0,0.6)] relative animate-scale-in">
      <!-- Header -->
      <div class="flex justify-between items-start border-b border-white/15 pb-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.25)]">
            <span class="material-symbols-outlined text-[26px]">fact_check</span>
          </div>
          <div>
            <h3 class="text-xl font-black text-white">{{ store.isAwaitingCheckin ? 'Antes de escribir tu semana' : '¿Cómo estás hoy?' }}</h3>
            <p class="text-xs text-white/75 font-medium">
              Lo que tu reloj no ve: con esto tu coach ajusta la semana. Tarda un minuto y todo es opcional.
            </p>
          </div>
        </div>

        <button
          @click="store.closeCheckin(false)"
          class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 active:scale-95 transition-all"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <!-- ¿Su reloj ya subió lo último que entrenó? (José, 2026-10-19). Lo que no está en Garmin
           Connect al generar no se puede contar como hecho; la app lo deja pendiente y lo mide la
           semana siguiente, pero la decisión de ESTA semana ya no lo ve. Se avisa antes; no impide. -->
      <div v-if="avisoDeSincronizacion"
           class="flex items-start gap-2.5 rounded-xl border border-[#ffd166]/40 bg-[#ffd166]/10 px-3.5 py-3 text-xs text-[#ffe6a3] leading-relaxed">
        <span class="material-symbols-outlined text-[18px] text-[#ffd166] shrink-0">sync_problem</span>
        <p>
          Tu reloj sincronizó con Garmin Connect por última vez <b>{{ avisoDeSincronizacion }}</b>.
          Si has entrenado después, sincronízalo (abre Garmin Connect en el móvil) antes de generar:
          lo que todavía no esté en Garmin no se puede contar como hecho.
        </p>
      </div>

      <!-- Last check-in summary -->
      <div
        v-if="store.checkin"
        class="p-4 glass-card rounded-2xl border border-white/15 font-mono-data text-xs flex items-center justify-between"
      >
        <div>
          <span class="text-[#8f9378] block text-[10px] uppercase font-black">Último check-in</span>
          <span class="text-white font-black">{{ formatDate(store.checkin.date) }}</span>
        </div>
        <div v-if="store.checkin.hooper_total != null && store.checkinBaseline" class="text-right">
          <span class="text-[#8f9378] block text-[10px] uppercase font-black">Cómo llegabas</span>
          <span class="text-[#caf300] font-black">{{ comoLlegabas }}</span>
        </div>
      </div>

      <div class="space-y-5 animate-fade-in-up delay-100">
        <!-- Hooper items. Cada escala va en UNA fila tambien en el movil:
             partida en 4 + 3 el 5 caia debajo del 1 y la escala dejaba de
             leerse como escala. Siete botones de ~44 px caben en 320. -->
        <div
          v-for="item in hooperItems"
          :key="item.key"
          class="space-y-2"
        >
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-baseline gap-0.5">
            <label class="text-xs font-black text-[#8f9378] uppercase tracking-wider">{{ item.label }}</label>
            <span class="text-[12px] text-white/70">1 = {{ item.uno }} · 7 = {{ item.siete }}</span>
          </div>
          <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
            <button
              v-for="n in 7"
              :key="n"
              type="button"
              @click="form[item.key] = form[item.key] === n ? null : n"
              class="py-2 min-h-[44px] rounded-lg text-sm sm:text-xs font-black transition-all"
              :class="form[item.key] === n
                ? 'bg-[#caf300] text-[#121700] shadow-[0_0_12px_rgba(202,243,0,0.35)]'
                : 'glass-pill text-[#c5c9ac] hover:text-white'"
            >
              {{ n }}
            </button>
          </div>
          <div class="flex justify-between text-[11px] font-bold -mt-1" aria-hidden="true">
            <span class="text-[#caf300]">← mejor</span><span class="text-white/50">peor →</span>
          </div>
        </div>

        <!-- Motivation -->
        <div class="space-y-2">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-baseline gap-0.5">
            <label class="text-xs font-black text-[#8f9378] uppercase tracking-wider">Ganas de entrenar</label>
            <span class="text-[12px] text-white/70">7 = muchas · 1 = ninguna</span>
          </div>
          <!-- Lo bueno a la izquierda, como en las otras cuatro (revisión del 2026-10-22): iba de 1 a 7 «al revés» y
               una respuesta rápida quedaba al contrario. El valor que se guarda no cambia: 7 sigue siendo «muchas». -->
          <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
            <button
              v-for="n in [7, 6, 5, 4, 3, 2, 1]"
              :key="n"
              type="button"
              @click="form.motivation = form.motivation === n ? null : n"
              class="py-2 min-h-[44px] rounded-lg text-sm sm:text-xs font-black transition-all"
              :class="form.motivation === n
                ? 'bg-[#caf300] text-[#121700] shadow-[0_0_12px_rgba(202,243,0,0.35)]'
                : 'glass-pill text-[#c5c9ac] hover:text-white'"
            >
              {{ n }}
            </button>
          </div>
          <div class="flex justify-between text-[11px] font-bold -mt-1" aria-hidden="true">
            <span class="text-[#caf300]">← mejor</span><span class="text-white/50">peor →</span>
          </div>
        </div>

        <!-- Niggle -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="sm:col-span-2">
            <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">Molestia localizada (opcional)</label>
            <input
              type="text"
              v-model="form.niggle_location"
              class="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors"
              placeholder="Ej. rozadura tendón de Aquiles izq."
            />
          </div>
          <div>
            <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">Dolor de 0 a 10</label>
            <input
              type="number"
              min="0"
              max="10"
              v-model="form.niggle_pain"
              :disabled="!form.niggle_location"
              placeholder="0 = nada"
              class="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors disabled:opacity-40"
            />
          </div>
        </div>

        <!-- La semana que se CIERRA.
             ==================================================================
             Va aquí porque este momento -- generar la siguiente -- es
             exactamente cuando la anterior se acaba, y el atleta ya está
             contestando. Preguntarlo en otro sitio sería otra pantalla que
             nadie abre.

             Tres opciones y no una escala: con cuatro atletas, "a medias en
             tres de cuatro semanas" es un número que se puede sumar; un 4
             sobre 7 no dice si sobró volumen o faltó tiempo. El porqué va en
             la línea de abajo, que ya existía. -->
        <div v-if="store.plan && store.plan.weekly_plan">
          <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">
            ¿La semana que termina salió como esperabas?
          </label>
          <div class="grid grid-cols-3 gap-1.5">
            <button
              v-for="o in comoEsperabas"
              :key="o.valor"
              type="button"
              @click="form.week_as_expected = form.week_as_expected === o.valor ? '' : o.valor"
              class="py-2 min-h-[44px] rounded-lg text-sm sm:text-xs font-black transition-all"
              :class="form.week_as_expected === o.valor
                ? 'bg-[#caf300] text-[#121700] shadow-[0_0_12px_rgba(202,243,0,0.35)]'
                : 'glass-pill text-[#c5c9ac] hover:text-white'"
            >{{ o.etiqueta }}</button>
          </div>
        </div>

        <!-- Last week -->
        <div>
          <!-- Sin semana anterior no hay "¿salió como esperabas?" arriba, y "¿Qué se atravesó?"
               quedaba colgando (auditoría del 2026-09-24). -->
          <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">
            {{ store.plan && store.plan.weekly_plan ? '¿Algo la complicó? (opcional)' : '¿Cómo te fueron estos últimos días? (opcional)' }}
          </label>
          <textarea
            rows="2"
            v-model="form.last_week_note"
            class="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors resize-none"
            placeholder="Ej. semana dura en el trabajo, dormí mal martes y miércoles..."
          ></textarea>
        </div>

        <!-- Week ahead -->
        <div>
          <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">¿Cómo viene {{ laSemana }}? (opcional)</label>
          <select
            v-model="form.week_ahead"
            class="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors"
          >
            <option value="" class="bg-[#17191e] text-white">Elige: normal, viaje, mucho trabajo…</option>
            <option value="normal" class="bg-[#17191e] text-white">Semana normal</option>
            <option value="viaje" class="bg-[#17191e] text-white">Viaje</option>
            <option value="carga_laboral" class="bg-[#17191e] text-white">Carga laboral alta</option>
            <option value="poco_sueno" class="bg-[#17191e] text-white">Poco sueño previsto</option>
          </select>
        </div>

        <!-- Lo que el atleta quiere que se tenga en cuenta ESTA semana: lo que
             no es una competicion del macrociclo pero cambia la semana. Vale
             para una semana y por eso va aqui y no en el cuaderno. Ver
             Database/021. -->
        <div>
          <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">¿Algo más que quieras que se tenga en cuenta {{ laSemana }}?</label>
          <textarea
            rows="2"
            v-model="form.week_note"
            class="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors resize-none"
            placeholder="Ej. el sábado salgo en bici con amigos, el jueves corro una carrera familiar de 5K..."
          ></textarea>
        </div>

        <!-- ESTA semana, empezando por lo que dice su perfil (Database/022 y 024).
             Primero sólo se podía decir qué días NO (Fernando). Jessie, el
             2026-09-15, no entrena los sábados y quería su activación el sábado
             antes de su carrera: no había dónde decir "este sábado sí", y la
             semana le dio descanso. Un toque cambia el día; los deportes se ven
             sólo del día tocado, para que la rejilla no se llene de texto. -->
        <div>
          <label class="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-1">¿Qué días entrenas {{ laSemana }}?</label>
          <p class="text-[11px] text-[#8f9378] mb-2">Viene como tu perfil. Toca un día para cambiarlo sólo {{ laSemana }}.</p>
          <div class="grid grid-cols-5 gap-1.5" :class="proximosDias.length > 7 ? 'sm:grid-cols-9' : 'sm:grid-cols-7'">
            <button
              v-for="d in proximosDias"
              :key="d.fecha"
              type="button"
              :aria-label="`${d.largo}: ${TEXTO_DEL_ESTADO[estadoDelDia(d)]}`"
              @click="tocarDia(d)"
              class="relative py-1.5 min-h-[48px] rounded-lg border text-xs font-black transition-all flex flex-col items-center justify-center active:scale-95"
              :class="[claseDelDia(d), seleccionado === d.fecha ? 'ring-2 ring-white/60' : '']"
            >
              <span :class="estadoDelDia(d) === 'no' ? 'line-through decoration-2' : ''">{{ d.dia }}</span>
              <span class="font-mono-data text-[10px] opacity-80">{{ d.num }}</span>
              <span
                v-if="estadoDelDia(d) === 'abierto'"
                class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-[#caf300] text-[#121700] text-[11px] leading-4 font-black"
                aria-hidden="true"
              >+</span>
              <span
                v-else-if="estadoDelDia(d) === 'carrera'"
                class="material-symbols-outlined absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-[#caf300] text-[#121700] text-[12px] leading-4"
                aria-hidden="true"
              >flag</span>
            </button>
          </div>

          <!-- El día tocado: qué hace y, si lo acaba de abrir, con qué. -->
          <div
            v-if="diaElegido"
            class="mt-2.5 rounded-xl border border-white/10 bg-white/[0.03] px-3.5 py-3 space-y-2"
            aria-live="polite"
          >
            <p class="text-xs font-black text-white">
              {{ diaElegido.largo }}
              <span class="font-medium text-[#c5c9ac]"> · {{ TEXTO_DEL_ESTADO[estadoDelDia(diaElegido)] }}</span>
            </p>

            <template v-if="estadoDelDia(diaElegido) === 'entrena'">
              <div v-if="diaElegido.fijos.length || diaElegido.posibles.length" class="flex flex-wrap gap-1.5">
                <span
                  v-for="dep in diaElegido.fijos"
                  :key="`f-${dep}`"
                  title="Lo quieres sí o sí"
                  class="px-2 py-0.5 rounded-md text-[11px] font-black bg-[#a5eeff]/15 text-[#a5eeff]"
                >{{ dep }}</span>
                <span
                  v-for="dep in diaElegido.posibles"
                  :key="`p-${dep}`"
                  title="Puedes; decide el coach"
                  class="px-2 py-0.5 rounded-md text-[11px] font-black bg-[#caf300]/15 text-[#caf300]"
                >{{ dep }}</span>
              </div>
              <p class="text-[11px] text-[#8f9378]">¿No puedes? Toca el día otra vez.</p>
            </template>

            <template v-else-if="estadoDelDia(diaElegido) === 'abierto'">
              <p class="text-[11px] text-[#c5c9ac]">¿Qué podrás hacer ese día?</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="dep in deportesParaAbrir"
                  :key="dep"
                  type="button"
                  role="checkbox"
                  :aria-checked="form.open_dates[diaElegido.fecha].includes(dep)"
                  @click="alternarDeporte(diaElegido.fecha, dep)"
                  class="flex items-center gap-1 pl-1.5 pr-2.5 py-1 min-h-[36px] rounded-lg border text-xs font-black transition-all active:scale-95"
                  :class="form.open_dates[diaElegido.fecha].includes(dep)
                    ? 'bg-[#caf300]/15 border-[#caf300]/60 text-[#caf300]'
                    : 'border-white/15 text-[#c5c9ac] hover:text-white hover:border-white/30'"
                >
                  <span class="material-symbols-outlined text-[16px]">
                    {{ form.open_dates[diaElegido.fecha].includes(dep) ? 'check_box' : 'check_box_outline_blank' }}
                  </span>
                  {{ dep }}
                </button>
              </div>
              <p v-if="!form.open_dates[diaElegido.fecha].length" class="text-[11px] text-[#ffd27a]">
                Elige al menos uno: sin deporte, el día sigue de descanso.
              </p>
              <p v-else class="text-[11px] text-[#8f9378]">Lo que hagas lo decide tu coach. Tócalo otra vez para cerrarlo.</p>
            </template>

            <p v-else-if="estadoDelDia(diaElegido) === 'carrera'" class="text-[11px] text-[#c5c9ac]">
              {{ diaElegido.carrera }}: ese día está disponible para tu carrera aunque tu perfil lo tenga de
              descanso. ¿No la vas a correr? Toca el día otra vez.
            </p>

            <p v-else-if="estadoDelDia(diaElegido) === 'no'" class="text-[11px] text-[#8f9378]">
              Ese día será descanso. Tócalo otra vez para recuperarlo.
            </p>
            <p v-else class="text-[11px] text-[#8f9378]">
              ¿Ese día sí puedes {{ laSemana }}? Toca el día para abrirlo.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-[10px] text-[#8f9378]">
            <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-[#caf300]/40 border border-[#caf300]/70"></i>Entrenas</span>
            <span v-if="proximosDias.some((d) => d.carrera)" class="flex items-center gap-1"><span class="material-symbols-outlined text-[12px] text-[#caf300]" aria-hidden="true">flag</span>Compites</span>
            <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-[#ffb4ab]/40 border border-[#ffb4ab]/70"></i>No puedes</span>
            <span class="flex items-center gap-1"><i class="w-2.5 h-2.5 rounded-sm bg-white/5 border border-white/20"></i>Descanso</span>
            <span class="ml-auto">Tu perfil no cambia.</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <!-- Lo que pasa al pulsar, dicho (revisión del 2026-10-22): «Guardar check-in» y «Saltar» no decían que lo
           siguiente es escribir la semana. -->
      <div class="pt-3 flex flex-col-reverse md:flex-row md:justify-between md:items-center gap-3 border-t border-white/15">
        <span class="text-[12px] text-white/60">
          Se guarda uno por día: si vuelves hoy, se reemplaza.
        </span>
        <div class="flex flex-col-reverse md:flex-row md:items-center gap-2 md:shrink-0">
          <button
            v-if="store.isAwaitingCheckin"
            @click="store.closeCheckin('saltado')"
            :disabled="store.isCheckinSaving"
            class="px-4 min-h-[44px] glass-pill text-[#c5c9ac] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap transition-all disabled:opacity-50 active:scale-95"
          >
            Generar sin responder
          </button>
          <button
            @click="handleSubmit"
            :disabled="store.isCheckinSaving || (!hasAnything && !store.isAwaitingCheckin)"
            class="flex items-center justify-center gap-2 px-6 min-h-[44px] bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider whitespace-nowrap transition-all shadow-[0_0_15px_rgba(202,243,0,0.3)] disabled:bg-white/10 disabled:text-white/40 disabled:shadow-none hover:scale-105 active:scale-95"
          >
            <span class="material-symbols-outlined text-[18px]">
              {{ store.isCheckinSaving ? 'sync' : 'check' }}
            </span>
            <span>{{ store.isCheckinSaving ? 'Guardando…' : (store.isAwaitingCheckin ? 'Guardar y generar mi semana' : 'Guardar') }}</span>
          </button>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { WEEK_DAYS, GRID_SPORTS, deportesDelDia } from '../../utils/profile';
import { fechasDeLaProximaSemana } from '../../utils/semana';
import { getUltimaSincronizacion } from '../../services/plan';
import { cuandoSincronizo } from '../../utils/sincronizacion';

const store = useAppStore();

/**
 * Cada escala dice sus PROPIOS polos.
 *
 * Las cuatro decian lo mismo -- "1 = muy bien / 7 = muy mal" -- y en tres de
 * ellas eso no significa nada obvio: "Fatiga: muy bien" no se sabe si es mucha
 * o poca. El indice de Hooper va todo en el mismo sentido (1 = mejor estado,
 * 7 = peor), pero eso hay que decirlo con las palabras de cada cosa, no con una
 * leyenda generica repetida cuatro veces.
 *
 * OJO con "Ganas de entrenar", que va abajo y en DIRECCION CONTRARIA (7 = mucha
 * gana). No es un despiste: asi esta en la migracion 005 y asi lo lee el prompt
 * (`services/ai/checkin.py`, que se lo explica al modelo). Lo que faltaba era
 * decirselo al atleta, y por eso va separada del bloque de arriba.
 */
const hooperItems = [
  { key: 'sleep', label: 'Calidad de sueño', uno: 'dormí muy bien', siete: 'dormí fatal' },
  { key: 'fatigue', label: 'Fatiga', uno: 'fresco', siete: 'agotado' },
  { key: 'stress', label: 'Estrés', uno: 'tranquilo', siete: 'muy estresado' },
  { key: 'soreness', label: 'Dolor muscular', uno: 'sin agujetas', siete: 'muy dolorido' },
];

const emptyForm = () => ({
  sleep: null,
  fatigue: null,
  stress: null,
  soreness: null,
  motivation: null,
  niggle_location: '',
  niggle_pain: '',
  last_week_note: '',
  week_as_expected: '',
  week_ahead: '',
  week_note: '',
  unavailable_dates: [],
  // {fecha: [deportes]}: días que su tabla no le da y esta semana sí (Database/024).
  open_dates: {},
});

/** La rejilla deporte x día del perfil, si la contestó. */
const rejilla = computed(() => {
  const r = store.profile && store.profile.sport_days;
  if (!r || typeof r !== 'object') return null;
  return GRID_SPORTS.some((dep) => r[dep] && Object.keys(r[dep]).length) ? r : null;
});

/** Lo que su perfil dice de un día de la semana ("Sábado"). */
const perfilDelDia = (nombre) => {
  if (rejilla.value) {
    const { fijos, posibles } = deportesDelDia(rejilla.value, nombre);
    return { entrena: fijos.length + posibles.length > 0, fijos, posibles };
  }
  // Un perfil sin rejilla: sus días de siempre, o todos si no hay perfil.
  const dias = store.profile && store.profile.available_days;
  const entrena = Array.isArray(dias) && dias.length ? dias.includes(nombre) : true;
  return { entrena, fijos: [], posibles: [] };
};

/** Con qué se puede abrir un día: los deportes que ya usa en su tabla. */
const deportesParaAbrir = computed(() => {
  if (!rejilla.value) return GRID_SPORTS;
  const suyos = GRID_SPORTS.filter((dep) => rejilla.value[dep] && Object.keys(rejilla.value[dep]).length);
  return suyos.length ? suyos : GRID_SPORTS;
});

/**
 * Las fechas de la semana que se va a generar (`utils/semana.js`, las mismas que
 * calcula el servidor). Antes eran los 9 días desde hoy: un día marcado fuera de
 * la semana que se generaba no hacía nada, y nadie se enteraba.
 */
// De qué semana se habla: "esta semana" si empieza hoy o ya empezó, "la semana del lunes 28"
// si es la siguiente. El modal decía "la semana pasada" y "esta semana" mientras enseñaba las
// fechas de la siguiente (auditoría del 2026-09-24).
const laSemana = computed(() => {
  const primero = proximosDias.value[0];
  if (!primero) return 'esta semana';
  const hoy = new Date().toLocaleDateString('sv');
  if (primero.fecha <= hoy) return 'esta semana';
  const [y, m, n] = primero.fecha.split('-').map(Number);
  const dia = new Date(y, m - 1, n).toLocaleDateString('es', { weekday: 'long' });
  return `la semana del ${dia} ${n}`;
});

/**
 * El nombre de la carrera de esa fecha, o ''. El día de una carrera está disponible para
 * ella aunque su perfil lo tenga de descanso (Hugo, 2026-10-09: su trail del domingo y los
 * domingos de descanso; la semana le quitó la carrera). Lo mismo que hace el servidor
 * (`plan_validator.dias_de_carrera`); tocarlo lo marca como "no puedes", que allí también gana.
 */
const carreraDelDia = (fecha) => {
  const nombres = (store.profile?.competitions || [])
    .filter((c) => String(c?.date || '').slice(0, 10) === fecha)
    .map((c) => `«${c.name || 'Competencia'}»`);
  return nombres.join(' y ');
};

const proximosDias = computed(() => {
  const cortos = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
  const fechas = store.checkinDates && store.checkinDates.length
    ? store.checkinDates
    : fechasDeLaProximaSemana(store.plan && store.plan.weekly_plan, store.profile && store.profile.week_start_day);
  return fechas.map((fecha) => {
    const [y, m, n] = fecha.split('-').map(Number);
    const d = new Date(y, m - 1, n, 12);
    const nombre = WEEK_DAYS[(d.getDay() + 6) % 7];
    return {
      fecha: d.toLocaleDateString('sv'),
      dia: cortos[d.getDay()],
      num: d.getDate(),
      largo: `${nombre} ${d.getDate()}`,
      carrera: carreraDelDia(d.toLocaleDateString('sv')),
      ...perfilDelDia(nombre),
    };
  });
});

const TEXTO_DEL_ESTADO = {
  carrera: 'compites',
  entrena: 'entrenas',
  abierto: 'lo abres esta semana',
  no: 'no puedes',
  descanso: 'descanso',
};

const CLASE_DEL_ESTADO = {
  carrera: 'bg-[#caf300]/15 border-[#caf300]/50 text-[#caf300]',
  entrena: 'bg-[#caf300]/15 border-[#caf300]/50 text-[#caf300]',
  abierto: 'bg-[#caf300]/15 border-[#caf300]/50 text-[#caf300]',
  no: 'bg-[#ffb4ab]/15 border-[#ffb4ab]/50 text-[#ffb4ab]',
  descanso: 'bg-white/[0.02] border-white/10 text-[#8f9378] hover:text-white hover:border-white/25',
};

/** Abierto sin ningún deporte todavía no entrena: se ve pendiente, no verde. */
const claseDelDia = (d) => {
  const estado = estadoDelDia(d);
  if (estado === 'abierto' && !(form.open_dates[d.fecha] || []).length) {
    return 'border-dashed border-[#caf300]/60 text-[#caf300] bg-transparent';
  }
  return CLASE_DEL_ESTADO[estado];
};

const seleccionado = ref(null);
const diaElegido = computed(() => proximosDias.value.find((d) => d.fecha === seleccionado.value) || null);

const estadoDelDia = (d) => {
  if (form.unavailable_dates.includes(d.fecha)) return 'no';
  if (Object.prototype.hasOwnProperty.call(form.open_dates, d.fecha)) return 'abierto';
  if (d.carrera) return 'carrera';
  return d.entrena ? 'entrena' : 'descanso';
};

/**
 * Un día que entrena se apaga al primer toque y vuelve al segundo. Uno de
 * descanso se abre al primer toque y pregunta con qué; ya abierto, el primer
 * toque sólo lo elige (para cambiar sus deportes) y el siguiente lo cierra.
 */
const tocarDia = (d) => {
  const estado = estadoDelDia(d);
  const yaElegido = seleccionado.value === d.fecha;
  seleccionado.value = d.fecha;
  if (estado === 'entrena' || estado === 'carrera') {
    form.unavailable_dates.push(d.fecha);
  } else if (estado === 'no') {
    form.unavailable_dates.splice(form.unavailable_dates.indexOf(d.fecha), 1);
  } else if (estado === 'descanso') {
    form.open_dates = { ...form.open_dates, [d.fecha]: [] };
  } else if (yaElegido) {
    const resto = { ...form.open_dates };
    delete resto[d.fecha];
    form.open_dates = resto;
  }
};

const alternarDeporte = (fecha, deporte) => {
  const actuales = form.open_dates[fecha] || [];
  const nuevos = actuales.includes(deporte)
    ? actuales.filter((x) => x !== deporte)
    : GRID_SPORTS.filter((x) => x === deporte || actuales.includes(x));
  form.open_dates = { ...form.open_dates, [fecha]: nuevos };
};

/** Sólo los días abiertos con algún deporte: sin deporte, el día sigue de descanso. */
const abiertosParaEnviar = () =>
  Object.fromEntries(Object.entries(form.open_dates).filter(([, deportes]) => deportes.length));

/** Las tres respuestas contables. Ver Database/016. */
const comoEsperabas = [
  { valor: 'si', etiqueta: 'Sí' },
  { valor: 'a_medias', etiqueta: 'A medias' },
  { valor: 'no', etiqueta: 'No' },
];

const form = reactive(emptyForm());

// En BLANCO cada vez que se abre, y los días como su perfil. Antes se rellenaba
// con el check-in de hoy: al volver a generar, Jessie (2026-09-15) encontró la
// nota de su activación, el viernes cerrado y el sábado abierto de la semana
// anterior ya marcados. Cada generación pregunta de nuevo; lo guardado sigue en
// MySQL y el resumen de arriba enseña el último.
const reiniciar = () => {
  Object.assign(form, emptyForm());
  seleccionado.value = null;
};

// La última sincronización del reloj: se pregunta al abrir y no espera a nadie. Si no se sabe
// (sin contraseña guardada, Garmin no contesta) no se dice nada.
const ultimaSincronizacion = ref(null);
const avisoDeSincronizacion = computed(() => cuandoSincronizo(ultimaSincronizacion.value, new Date()));
const preguntarSincronizacion = async () => {
  ultimaSincronizacion.value = null;
  try {
    ultimaSincronizacion.value = await getUltimaSincronizacion();
  } catch (e) {
    ultimaSincronizacion.value = null;
  }
};

watch(
  () => store.isCheckinOpen,
  (open) => {
    if (open) {
      reiniciar();
      preguntarSincronizacion();
    }
  }
);

const hasAnything = computed(() =>
  form.sleep || form.fatigue || form.stress || form.soreness || form.motivation
  || form.niggle_location.trim() || form.last_week_note.trim() || form.week_ahead
  || form.week_as_expected || form.week_note.trim() || form.unavailable_dates.length
  || Object.keys(abiertosParaEnviar()).length
);

const formatDate = (iso) => {
  if (!iso) return '';
  try {
    return new Date(`${iso}T00:00:00`).toLocaleDateString('es', { day: '2-digit', month: 'long', year: 'numeric' });
  } catch (e) {
    return iso;
  }
};

// «Algo mejor / peor que de costumbre» en vez de «12 de 28 · tu media 6,5».
const comoLlegabas = computed(() => {
  const t = Number(store.checkin?.hooper_total);
  const m = Number(store.checkinBaseline?.mean);
  if (!Number.isFinite(t) || !Number.isFinite(m)) return '';
  if (t <= m - 2) return 'mejor que de costumbre';
  if (t >= m + 2) return 'peor que de costumbre';
  return 'como de costumbre';
});

const handleSubmit = async () => {
  if (store.isCheckinSaving) return;
  // Sin ninguna respuesta, «Guardar y generar» genera igual: es lo mismo que «Generar sin responder».
  if (!hasAnything.value) {
    if (store.isAwaitingCheckin) store.closeCheckin('saltado');
    return;
  }
  try {
    await store.submitCheckin({
      sleep: form.sleep,
      fatigue: form.fatigue,
      stress: form.stress,
      soreness: form.soreness,
      motivation: form.motivation,
      niggle_location: form.niggle_location.trim(),
      niggle_pain: form.niggle_location.trim() && form.niggle_pain !== '' ? Number(form.niggle_pain) : null,
      last_week_note: form.last_week_note.trim(),
      week_as_expected: form.week_as_expected,
      week_ahead: form.week_ahead,
      week_note: form.week_note.trim(),
      unavailable_dates: [...form.unavailable_dates],
      open_dates: abiertosParaEnviar(),
    });
    store.closeCheckin('guardado');
  } catch (e) {
    // toast shown by the store
  }
};
</script>
