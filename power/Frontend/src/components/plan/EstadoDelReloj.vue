<template>
  <!-- De dónde salen los ✓/✗ y si se puede fiar de ellos ahora (revisión de UX del 2026-10-21: «es lo que
       quita la ansiedad, súbelo»). Si el reloj lleva 3 h o más sin sincronizar, se dice en amarillo. -->
  <div role="status" aria-live="polite" class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs leading-snug" :class="viejo && !sinAviso ? 'text-[#ffd166]' : 'text-white/75'">
    <template v-if="estado?.motivo === 'sin_clave'">
      <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">key</span>
        Guarda tu contraseña de Garmin y verás ✓ en lo que ya hiciste.</span>
      <router-link to="/admin/perfil?tab=identidad" class="font-bold text-[#a5eeff] hover:text-white">Ir a mi perfil</router-link>
    </template>
    <template v-else>
      <span v-if="cargando" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>Leyendo tu Garmin…</span>
      <span v-else-if="estado?.error" class="flex items-center gap-1.5 text-[#ffb4ab]"><span class="material-symbols-outlined text-[16px]">cloud_off</span>{{ estado.error }}</span>
      <span v-else-if="sincronizo" class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-[16px]">{{ viejo && !sinAviso ? 'sync_problem' : 'watch' }}</span>
        <template v-if="viejo && !sinAviso">Tu reloj sincronizó por última vez {{ sincronizo }}. Sincronízalo y pulsa «Leer mi reloj».</template>
        <template v-else>Tu reloj sincronizó {{ sincronizo }}.</template>
      </span>
      <button v-if="!cargando" type="button" @click="$emit('actualizar')"
              class="min-h-[40px] px-2.5 -my-1 rounded-lg flex items-center gap-1 font-bold text-[#a5eeff] hover:bg-[#a5eeff]/10 hover:text-white">
        <span class="material-symbols-outlined text-[16px]">refresh</span>Leer mi reloj
      </button>
      <span v-if="leyenda" class="flex items-center gap-3 text-white/70">
        <span class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded-full bg-[#caf300] text-[#121700] flex items-center justify-center"><span class="material-symbols-outlined text-[11px] font-black">check</span></span>hecha</span>
        <!-- La ✗, sólo si hay alguna en la pantalla (se explicaba una marca que no aparecía). -->
        <span v-if="hayNoHechas" class="flex items-center gap-1"><span class="w-3.5 h-3.5 rounded-full bg-[#ff8f83] text-[#121700] flex items-center justify-center"><span class="material-symbols-outlined text-[11px] font-black">close</span></span>sin hacer (no está en tu reloj)</span>
      </span>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { cuandoFue, MARGEN_HORAS } from '../../utils/sincronizacion';

const props = defineProps({
  estado: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
  leyenda: { type: Boolean, default: true },
  // El aviso en amarillo ya está en otro sitio de la pantalla (la tarjeta «Hoy»): aquí, sólo la hora.
  sinAviso: { type: Boolean, default: false },
});
defineEmits(['actualizar']);

const hayNoHechas = computed(() => Object.values(props.estado?.sesiones || {}).some((e) => e?.estado === 'no_hecha'));
const sincronizo = computed(() => cuandoFue(props.estado?.ultima_sincronizacion));
const viejo = computed(() => {
  const iso = props.estado?.ultima_sincronizacion;
  return Boolean(iso) && (Date.now() - new Date(iso).getTime()) / 3600000 >= MARGEN_HORAS;
});
</script>
