<template>
  <!-- A <body>: la vista de Plan anima con `transform` y un `fixed` dentro se coloca respecto a
       ella, no a la pantalla (le pasó al editor de sesión en F7). -->
  <Teleport to="body">
  <div v-if="hoja" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm"
       @click.self="$emit('cancelar')" @keydown.esc="$emit('cancelar')">
    <div role="dialog" aria-modal="true" aria-labelledby="hoja-mover-titulo"
         class="w-full sm:max-w-md glass-panel border border-white/20 rounded-t-3xl sm:rounded-3xl shadow-[0_-10px_60px_rgba(0,0,0,0.6)] animate-fade-in-up max-h-[88vh] max-h-[88dvh] flex flex-col">
      <div class="overflow-y-auto p-5 sm:p-6 space-y-3">
        <div class="mx-auto w-10 h-1 rounded-full bg-white/20 sm:hidden" aria-hidden="true"></div>
        <h3 id="hoja-mover-titulo" class="text-lg font-black text-white">Mover «{{ corta(hoja.origen.titulo) }}»</h3>

        <!-- De → a. Cada tarjeta dice cómo queda SU día: la de antes, sólo lo que se va; la nueva, lo que
             llega y qué pasa con lo que ya había (revisión de UX, ronda 2: se repetía tres veces). -->
        <!-- En el teléfono, una encima de la otra: lado a lado no caben los nombres. -->
        <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-stretch gap-2">
          <div class="min-w-0 rounded-2xl border border-[#a5eeff]/40 bg-[#a5eeff]/5 px-3 py-2.5 space-y-1">
            <p class="text-[10px] uppercase font-black tracking-wide text-[#a5eeff] whitespace-nowrap truncate">Ahora · {{ largo(hoja.origen.fecha) }}</p>
            <Ficha :f="hoja.origen" />
          </div>
          <span class="self-center justify-self-center material-symbols-outlined text-[#caf300] rotate-90 sm:rotate-0" aria-hidden="true">{{ intercambio ? 'swap_horiz' : 'arrow_forward' }}</span>
          <div class="min-w-0 rounded-2xl border border-[#caf300]/50 bg-[#caf300]/10 px-3 py-2.5 space-y-1">
            <p class="text-[10px] uppercase font-black tracking-wide text-[#caf300] whitespace-nowrap truncate">Nuevo día · {{ largo(hoja.destino.fecha) }}</p>
            <Ficha :f="hoja.origen" />
            <p v-if="hoja.destino.fichas.length" class="text-[12px] text-white/60 leading-snug">
              {{ hoja.modo === 'doble'
                ? `+ ${corta(hoja.destino.fichas[0].titulo)} se queda (2 sesiones)`
                : `⇄ ${corta(hoja.destino.fichas[0].titulo)} pasa al ${corto(hoja.origen.fecha)}` }}
            </p>
          </div>
        </div>

        <!-- Si el día nuevo ya tiene algo: qué pasa con eso. -->
        <fieldset v-if="hoja.destino.sesiones.length" class="space-y-2">
          <legend class="text-xs text-[#c5c9ac] mb-1.5">
            El {{ corto(hoja.destino.fecha) }} ya tiene <b class="text-white">{{ corta(hoja.destino.sesiones[0]) }}</b>. ¿Qué hacemos?
          </legend>
          <label v-for="op in opciones" :key="op.valor"
                 class="flex items-start gap-3 rounded-xl border px-3 py-2.5 cursor-pointer transition-colors"
                 :class="[hoja.modo === op.valor ? 'border-[#caf300] bg-[#caf300]/10' : 'border-white/15 hover:border-white/30',
                          op.deshabilitada ? 'opacity-40 cursor-not-allowed' : '']">
            <input type="radio" name="modo-mover" class="mt-0.5 accent-[#caf300]" :value="op.valor"
                   :checked="hoja.modo === op.valor" :disabled="op.deshabilitada" @change="$emit('modo', op.valor)" />
            <span>
              <span class="block text-sm font-bold text-white">{{ op.titulo }}</span>
              <span class="block text-[11px] text-[#c5c9ac] leading-snug">{{ op.detalle }}</span>
            </span>
          </label>
        </fieldset>

        <p v-if="hoja.cargando" class="text-xs text-[#c5c9ac] flex items-center gap-2">
          <span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span> Comprobando el cambio…
        </p>
        <p v-else-if="hoja.error" class="text-sm text-[#ffb4ab] font-bold" role="alert">{{ hoja.error }}</p>
        <!-- Lo que conviene saber, en un solo bloque. El resumen del servidor ya lo dicen las tarjetas. -->
        <div v-else-if="(hoja.vista?.avisos || []).length" class="rounded-xl border border-[#ffd166]/30 bg-[#ffd166]/5 p-3">
          <p class="text-[10px] uppercase font-black tracking-wider text-[#ffd166] mb-1.5">Para que lo sepas</p>
          <ul class="space-y-1 list-disc pl-4 marker:text-[#ffd166]">
            <li v-for="a in hoja.vista.avisos" :key="a" class="text-[12px] text-[#f1f0ee] leading-snug">{{ a }}</li>
          </ul>
        </div>
        <p class="text-[11px] text-[#c5c9ac] flex items-start gap-1.5">
          <span class="material-symbols-outlined text-[14px] shrink-0 text-[#a5eeff]">watch</span>
          Tu reloj no cambia hasta que pulses «Enviar al reloj».
        </p>
      </div>

      <!-- Pie fijo: los botones siempre a la vista, por encima de la barra del teléfono. -->
      <div class="relative sticky bottom-0 flex gap-2 px-5 sm:px-6 pt-3 pb-[max(16px,env(safe-area-inset-bottom))] border-t border-white/10 bg-[#14171b]/95 rounded-b-none sm:rounded-b-3xl">
        <!-- Un degradado sobre el pie: si hay más arriba, se nota. -->
        <span class="pointer-events-none absolute -top-6 inset-x-0 h-6 bg-gradient-to-b from-transparent to-[#14171b]/95" aria-hidden="true"></span>
        <button type="button" @click="$emit('cancelar')"
                class="flex-1 h-12 sm:h-11 rounded-xl glass-pill text-xs font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white">
          Cancelar
        </button>
        <button type="button" @click="$emit('confirmar')" :disabled="hoja.cargando || !!hoja.error || !hoja.vista || hoja.guardando"
                class="flex-[2] h-12 sm:h-11 rounded-xl bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-xs font-black uppercase tracking-wider disabled:opacity-40 active:scale-95 transition-all">
          {{ hoja.guardando ? 'Moviendo…' : `Mover al ${corto(hoja.destino.fecha)}` }}
        </button>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
/** Confirmar un cambio de día (decisión del responsable, 2026-10-21: «que se vea muy bien»). */
import { computed, h } from 'vue';
import { COLOR_DEL_DEPORTE, ICONO_DEL_DEPORTE } from '../../utils/deportes';

const props = defineProps({ hoja: { type: Object, default: null } });
defineEmits(['modo', 'confirmar', 'cancelar']);

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sept', 'oct', 'nov', 'dic'];
// El nombre corto de una sesión: hasta el primer paréntesis («Cuerpo completo (cadena…)» → «Cuerpo completo»).
const corta = (t) => String(t || '').split(' (')[0];
// «miércoles 21 oct», en minúscula como el resto de la app.
const largo = (f) => {
  const d = new Date(`${f}T12:00:00`);
  return `${d.toLocaleDateString('es', { weekday: 'long' })} ${d.getDate()} ${MESES[d.getMonth()]}`;
};
const corto = (f) => {
  const d = new Date(`${f}T12:00:00`);
  return `${d.toLocaleDateString('es', { weekday: 'long' })} ${d.getDate()}`;
};

const intercambio = computed(() => props.hoja?.modo === 'intercambiar' && (props.hoja?.destino?.fichas || []).length > 0);

// Una sesión en una línea: icono de su deporte, nombre y minutos.
const Ficha = (p) => {
  const f = p.f || {};
  return h('p', { class: 'flex items-center gap-1.5 text-[13px] leading-tight text-white' }, [
    h('span', { class: 'material-symbols-outlined text-[16px] shrink-0', style: { color: COLOR_DEL_DEPORTE[f.sport] || '#c5c9ac' } },
      ICONO_DEL_DEPORTE[f.sport] || 'exercise'),
    h('span', { class: 'min-w-0 truncate font-bold' }, corta(f.titulo)),
    f.minutos ? h('span', { class: 'ml-auto shrink-0 font-mono-data text-[11px] text-white/60' }, `${f.minutos}'`) : null,
  ]);
};
Ficha.props = ['f'];

const opciones = computed(() => {
  const x = props.hoja;
  if (!x) return [];
  return [
    { valor: 'intercambiar', titulo: 'Intercambiarlas',
      detalle: x.origen.pasado
        ? 'No se puede: la otra pasaría a un día que ya pasó.'
        : `La otra pasa al ${corto(x.origen.fecha)}.`,
      deshabilitada: x.origen.pasado },
    { valor: 'doble', titulo: 'Hacer las dos ese día', detalle: 'Mañana y tarde.',
      deshabilitada: false },
  ];
});
</script>
