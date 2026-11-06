<template>
  <div
    v-if="store.isHealthOpen"
    id="health-modal-backdrop"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-xl animate-scale-in select-none"
  >
    <div class="glass-panel border border-white/20 rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-[0_0_60px_rgba(0,0,0,0.6)] relative animate-scale-in max-h-[85vh] overflow-y-auto hide-scrollbar">
      <div class="flex justify-between items-start border-b border-white/15 pb-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.25)]">
            <span class="material-symbols-outlined text-[26px]">favorite</span>
          </div>
          <div>
            <h3 class="text-xl font-black text-white">Tu forma</h3>
            <p class="text-xs text-[#c5c9ac] font-medium">Lo último que registró tu reloj</p>
          </div>
        </div>

        <button
          @click="store.isHealthOpen = false"
          class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 active:scale-95 transition-all"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <p v-if="store.chartsLoading" class="text-xs font-mono-data text-[#8f9378]">Leyendo tus datos…</p>

      <!--
        Aqui habia cuatro cifras INVENTADAS presentadas como del atleta: la VFC
        y la FC maxima salian de mockData.js (68 ms, 188 ppm) y NADA las
        sobreescribia nunca; el SpO2 (97.8%) y el sueno profundo (1h 48m) eran
        literales escritos en la plantilla. Un usuario recien creado, sin un
        solo dato, veia su "biometria" completa.

        Es la misma regla que ya se aplico a las notificaciones inventadas: un
        numero falso sobre el cuerpo del atleta es peor que no tener el dato,
        porque se toman decisiones con el. Ahora solo se muestra lo que existe
        de verdad, y lo que no, se dice.
      -->
      <div v-if="hayAlgo" class="space-y-4 font-mono-data text-xs animate-fade-in-up delay-100">
        <!-- VFC, pulso en reposo medido y ACWR: sólo el administrador (Beta 3.0, F4). -->
        <div
          v-if="hrv && store.isAdmin"
          class="p-4 glass-card rounded-2xl border border-white/15 flex justify-between items-center"
        >
          <div>
            <span class="text-[#8f9378] block text-[10px] uppercase font-black">Variabilidad cardiaca (VFC)</span>
            <span class="text-lg font-black text-[#caf300]">
              {{ fmt(hrv.current) }} {{ hrv.unit || 'ms' }}<span v-if="hrv.status"> · {{ estadoDeGarmin(hrv.status) }}</span>
            </span>
            <span v-if="hrv.band?.length === 2" class="block text-[10px] text-[#8f9378] mt-0.5">
              tu rango normal: {{ fmt(hrv.band[0]) }}–{{ fmt(hrv.band[1]) }} ms
            </span>
          </div>
          <span class="material-symbols-outlined text-[#caf300] text-3xl">ecg_heart</span>
        </div>

        <div class="grid grid-cols-2 gap-3.5 animate-fade-in-up delay-200">
          <div v-if="rhr && store.isAdmin" class="p-4 glass-card rounded-2xl border border-white/15">
            <span class="text-[#8f9378] block text-[10px] uppercase font-black">Pulso en reposo</span>
            <span class="text-base font-black text-white mt-1 block">{{ fmt(rhr.current) }} bpm</span>
            <span v-if="rhr.band?.length === 2" class="block text-[10px] text-[#8f9378] mt-0.5">
              normal {{ fmt(rhr.band[0]) }}–{{ fmt(rhr.band[1]) }}
            </span>
          </div>
          <div v-if="forma" class="p-4 glass-card rounded-2xl border border-white/15">
            <span class="text-[#8f9378] block text-[10px] uppercase font-black">Forma (VO₂máx est.)</span>
            <span class="text-base font-black text-white mt-1 block">
              {{ fmt(forma.current) }} {{ forma.unit || '' }}
            </span>
            <span v-if="forma.change != null" class="block text-[10px] text-[#8f9378] mt-0.5">
              {{ forma.change >= 0 ? '+' : '' }}{{ forma.change }} en la ventana
            </span>
          </div>
          <div v-if="acwr && store.isAdmin" class="p-4 glass-card rounded-2xl border border-white/15">
            <span class="text-[#8f9378] block text-[10px] uppercase font-black">Carga aguda:crónica</span>
            <span class="text-base font-black text-white mt-1 block">{{ fmt(acwr.current) }}</span>
            <span v-if="acwr.garmin_status" class="block text-[10px] text-[#8f9378] mt-0.5">
              Garmin lo llama {{ estadoDeGarmin(acwr.garmin_status).toLowerCase() }}
            </span>
          </div>
          <!-- El pulso en reposo tampoco lo ve el atleta, ni el que declaró (decisión del 2026-10-11). -->
          <div v-if="store.profile?.resting_heart_rate && store.isAdmin" class="p-4 glass-card rounded-2xl border border-white/15">
            <span class="text-[#8f9378] block text-[10px] uppercase font-black">FC reposo (tu ficha)</span>
            <span class="text-base font-black text-white mt-1 block">
              {{ store.profile.resting_heart_rate }} bpm
            </span>
            <span class="block text-[10px] text-[#8f9378] mt-0.5">lo que declaraste</span>
          </div>
        </div>
      </div>

      <div v-else-if="!store.chartsLoading" class="glass-card border border-white/10 rounded-2xl p-5 space-y-2">
        <p class="text-sm text-white font-bold">Todavía no hay datos de tu reloj.</p>
        <p class="text-xs text-[#c5c9ac] leading-relaxed">
          Estos números salen de tus propias lecturas de Garmin y aparecen cuando generas
          tu primer plan. No se muestra nada hasta entonces.
        </p>
      </div>

      <div class="pt-3 flex flex-wrap gap-3 justify-between items-center border-t border-white/15">
        <span class="text-[11px] font-mono-data text-[#8f9378]">
          {{ store.chartsMeta?.last_refresh ? `Última lectura: ${fecha(store.chartsMeta.last_refresh)}` : 'Sin lecturas todavía' }}
        </span>
        <router-link
          to="/admin/analytics"
          @click="store.isHealthOpen = false"
          class="flex items-center gap-2 px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(202,243,0,0.3)] active:scale-95"
        >
          <span class="material-symbols-outlined text-[18px]">monitoring</span>
          <span>Ver la evolución</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { estadoDeGarmin, num } from '../../utils/garmin';

const store = useAppStore();

const hrv = computed(() => store.charts?.recovery?.hrv || null);
const rhr = computed(() => store.charts?.recovery?.rhr || null);
const forma = computed(() => store.charts?.fitness || null);
const acwr = computed(() => store.charts?.load || null);
const hayAlgo = computed(() => !!(forma.value || store.profile?.resting_heart_rate
  || (store.isAdmin && (hrv.value || rhr.value || acwr.value))));

// Con coma decimal, como el resto de la app ("0,9" y no "0.9").
const fmt = (n) => num(n);
const fecha = (iso) => {
  const d = new Date(String(iso).replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('es', { day: 'numeric', month: 'short' });
};

// Se cargan al ABRIR y no al montar: es una consulta que cruza el puente, y no
// tiene por qué pagarla cada carga de página quien nunca abre este panel.
// El botón anterior ("Actualizar Sensores") llamaba a /api/garmin/sync, una
// ruta que NO EXISTE en este backend, y su catch mostraba igualmente
// "Sincronización con Garmin completada" — un éxito anunciado sobre una
// petición que siempre fallaba.
watch(() => store.isHealthOpen, (abierto) => {
  if (abierto) store.loadCharts();
});
</script>
