<template>
  <!-- Tu progreso (Beta 3.0, F8): la racha semanal, el nivel, las medallas y los récords. Todo
       sale de tus semanas cerradas (services/ai/progreso.py), sin IA. -->
  <section v-if="progreso" class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <article class="glass-card rounded-3xl p-5 sm:p-6 border border-[#caf300]/30 md:col-span-1">
        <p class="text-[11px] font-black text-white/70 uppercase tracking-widest">Tu racha</p>
        <!-- Con la racha en 0, lo que toca ahora y no un «0» enorme (revisión del 2026-10-21). -->
        <div v-if="r.actual" class="flex items-end gap-2 mt-1">
          <span class="font-mono-data text-4xl font-black text-white">{{ r.actual }}</span>
          <span class="text-[13px] font-bold text-white/80 pb-1.5">{{ r.actual === 1 ? 'semana seguida' : 'semanas seguidas' }}</span>
        </div>
        <p v-else class="text-xl font-black text-white mt-1">Empiezas una racha nueva</p>
        <p v-if="r.mejor" class="text-[12px] text-white/70 mt-0.5">Tu mejor racha: {{ r.mejor }} {{ r.mejor === 1 ? 'semana' : 'semanas' }}</p>
        <!-- Por qué está así (revisión del 2026-10-21): un «0» enorme con «2 semanas cumplidas» al lado, sin causa. -->
        <p class="text-[13px] text-white/85 mt-2 leading-snug">{{ porQueLaRacha }}</p>
        <p class="text-[12px] text-white/65 mt-1 leading-snug">Una semana suma a tu racha si haces el 70 % de sus sesiones.</p>
        <details class="group mt-1">
          <summary class="cursor-pointer list-none inline-flex items-center gap-1 text-[13px] text-white/75 hover:text-white min-h-[44px]">
            <span class="material-symbols-outlined text-[15px] transition-transform group-open:rotate-90">chevron_right</span>¿Cómo se cuenta?
          </summary>
          <p class="text-[12px] text-white/70 leading-snug">Cuentan las sesiones, no las horas. Si anotas una molestia,
            esa semana queda en pausa: ni suma ni rompe. La racha son semanas seguidas; el nivel, todas las que has cumplido.</p>
        </details>
      </article>

      <article class="glass-card rounded-3xl p-5 sm:p-6 border border-white/15">
        <p class="text-[11px] font-black text-white/70 uppercase tracking-widest">Nivel {{ r.nivel }}</p>
        <!-- «Racha 0» junto a «2 cumplidas» no se entendía: éstas son todas, seguidas o no. -->
        <p class="text-sm text-white mt-1">{{ r.semanas_constantes }}{{ NIVELES[r.nivel + 1] ? ` de ${NIVELES[r.nivel + 1]}` : '' }} {{ r.semanas_constantes === 1 ? 'semana cumplida' : 'semanas cumplidas' }}</p>
        <p class="text-[12px] text-white/65">en total, seguidas o no</p>
        <div v-if="r.siguiente_nivel_en" class="mt-3">
          <div class="h-2 rounded-full bg-white/10 overflow-hidden">
            <div class="h-full bg-[#caf300]" :style="{ width: `${avanceDeNivel}%` }"></div>
          </div>
          <p class="text-[12px] text-white/70 mt-1.5">Te {{ r.siguiente_nivel_en === 1 ? 'falta 1 semana' : `faltan ${r.siguiente_nivel_en} semanas` }} para el nivel {{ r.nivel + 1 }}.</p>
        </div>
      </article>

      <article class="glass-card rounded-3xl p-5 sm:p-6 border border-white/15">
        <p class="text-[11px] font-black text-white/70 uppercase tracking-widest">Este mes · {{ nombreDelMes }}</p>
        <p class="font-mono-data text-3xl font-black text-white mt-1">{{ num(progreso.mes?.horas || 0) }} h</p>
        <p class="text-[12px] text-white/65">entrenadas, también la fuerza</p>
        <!-- Texto, no píldoras: parecían botones y no hacían nada. -->
        <p v-if="Object.keys(progreso.mes?.km_por_deporte || {}).length" class="text-[13px] text-white/85 mt-2 leading-relaxed">
          <template v-for="(km, d, i) in progreso.mes?.km_por_deporte || {}" :key="d">{{ i ? ' · ' : '' }}{{ d }} <b class="text-white">{{ num(km) }} km</b></template>
        </p>
        <p v-if="!progreso.mes?.horas" class="text-[12px] text-white/70 mt-1">Cuenta cuando cierres tu primera semana del mes.</p>
      </article>
    </div>

    <article class="glass-card rounded-3xl p-5 sm:p-6 border border-white/15 space-y-3">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <p class="text-[11px] font-black text-white/70 uppercase tracking-widest">Medallas · {{ conseguidas.length }} de {{ (progreso.medallas || []).length }}</p>
        <button type="button" @click="compartir" :disabled="compartiendo"
                class="flex items-center gap-1.5 px-3.5 min-h-[40px] rounded-xl border border-[#caf300]/50 text-[#caf300] text-[12px] font-bold disabled:opacity-50">
          <span class="material-symbols-outlined text-[16px]">ios_share</span>
          {{ compartiendo ? 'Preparando…' : 'Compartir mis medallas' }}
        </button>
      </div>
      <!-- Las conseguidas, a la vista; las que faltan, plegadas (revisión del 2026-10-21: nueve filas iguales
           empujaban los datos del entrenamiento hacia abajo). -->
      <ul class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <li v-for="m in conseguidas" :key="m.id" class="rounded-2xl bg-[#caf300]/10 px-3 py-2.5">
          <span class="material-symbols-outlined text-[22px] text-[#caf300]">{{ iconoDe(m) }}</span>
          <span class="block text-[13px] font-bold text-white leading-snug mt-0.5">{{ m.nombre }}</span>
          <span class="block text-[12px] text-white/65 leading-snug">{{ m.descripcion }}</span>
        </li>
      </ul>
      <details v-if="porConseguir.length" class="group">
        <summary class="cursor-pointer list-none inline-flex items-center gap-1 text-[13px] font-bold text-white/80 hover:text-white min-h-[44px]">
          <span class="material-symbols-outlined text-[16px] transition-transform group-open:rotate-90">chevron_right</span>
          Por conseguir ({{ porConseguir.length }})
        </summary>
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
          <li v-for="m in porConseguir" :key="m.id" class="flex items-start gap-2.5 rounded-2xl border border-white/10 px-3 py-2.5">
            <span class="material-symbols-outlined text-[22px] text-white/50">lock</span>
            <span class="min-w-0">
              <span class="block text-sm font-bold text-white/85">{{ m.nombre }}</span>
              <span class="block text-[12px] text-white/65">{{ m.progreso || m.descripcion }}</span>
            </span>
          </li>
        </ul>
      </details>
    </article>

    <article v-if="hayRecords" class="glass-card rounded-3xl p-5 sm:p-6 border border-white/15">
      <p class="text-[11px] font-black text-white/70 uppercase tracking-widest">Lo máximo que has hecho en una semana</p>
      <p class="text-[13px] text-white/85 mt-2 leading-relaxed">
        <template v-if="progreso.records?.horas_en_una_semana"><b class="text-white">{{ num(progreso.records.horas_en_una_semana) }} h</b> entrenadas</template>
        <template v-for="(km, d) in progreso.records?.km_en_una_semana || {}" :key="d"> · {{ d }} <b class="text-white">{{ num(km) }} km</b></template>
      </p>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import swal from '../../utils/swal';
import { num } from '../../utils/garmin';
import { useAppStore } from '../../stores/appStore';
import { FORMATOS, compartirTarjeta, marcarVistas, medallasNuevas, tarjetaDeProgreso } from '../../utils/compartir';
import { enlaceDeInvitacion } from '../../utils/invitacion';
import { misInvitaciones } from '../../services/invitaciones';
import api from '../../services/api';

const props = defineProps({ progreso: { type: Object, default: null } });
const store = useAppStore();
const compartiendo = ref(false);

const r = computed(() => props.progreso?.racha || {});
const conseguidas = computed(() => (props.progreso?.medallas || []).filter((m) => m.conseguida));
// De las pendientes, las primeras (la siguiente de cada camino).
// Todas: «5 de 12» con cuatro a la vista no cuadraba.
const porConseguir = computed(() => (props.progreso?.medallas || []).filter((m) => !m.conseguida));
// Un icono por tipo de medalla: cinco trofeos iguales no se distinguían.
const ICONOS = { primera: 'flag', racha: 'local_fire_department', todas: 'bolt', km: 'route', fuerza: 'fitness_center',
  checkin: 'fact_check', test: 'speed', carrera: 'emoji_events', vo2max: 'favorite' };
const iconoDe = (m) => ICONOS[Object.keys(ICONOS).find((k) => String(m.id || '').startsWith(k))] || 'military_tech';
const nombreDelMes = new Date().toLocaleDateString('es', { month: 'long' });
const fechaCorta = (iso) => new Date(`${iso}T12:00:00`).toLocaleDateString('es', { day: 'numeric', month: 'short' });
const porQueLaRacha = computed(() => {
  const u = r.value.ultima_semana;
  if (!u) return 'Empieza a contar cuando cierres tu primera semana.';
  // «del 12 al 18 oct», como en el Historial: el mes una vez si no cambia.
  const mismoMes = String(u.desde).slice(0, 7) === String(u.hasta).slice(0, 7);
  const semana = `La semana del ${mismoMes ? Number(String(u.desde).slice(8, 10)) : fechaCorta(u.desde)} al ${fechaCorta(u.hasta)}`;
  if (u.cuenta === 'congelada') return `${semana} quedó en pausa por la molestia que anotaste: ni suma ni rompe.`;
  if (u.cuenta === 'neutra') return `${semana} pasó de las horas previstas: ni suma ni rompe.`;
  if (u.cuenta === 'rompe') return `${semana} hiciste ${u.hechas} de ${u.evaluadas} sesiones; para sumar hacían falta ${Math.ceil(u.evaluadas * 0.7)}.`;
  if (u.tras_un_hueco && r.value.actual <= 1) return 'Hubo una semana sin plan entre medias, así que la racha empezó de nuevo.';
  return `${semana} sumó. Esta semana se suma si haces el 70 % de lo previsto.`;
});
const hayRecords = computed(() => props.progreso?.records?.horas_en_una_semana
  || Object.keys(props.progreso?.records?.km_en_una_semana || {}).length);
const NIVELES = [0, 1, 3, 6, 10, 16, 24, 36];
const avanceDeNivel = computed(() => {
  const desde = NIVELES[r.value.nivel] ?? 0;
  const hasta = NIVELES[r.value.nivel + 1];
  if (!hasta) return 100;
  return Math.round(((r.value.semanas_constantes - desde) / (hasta - desde)) * 100);
});

onMounted(() => {
  const nuevas = medallasNuevas(props.progreso?.medallas);
  if (nuevas.length) store.showToast(`Nueva medalla: ${nuevas.map((m) => m.nombre).join(', ')}`);
  marcarVistas(props.progreso?.medallas);
});

const compartir = async () => {
  // Su código de invitación va en la tarjeta (si la migración 033 está); sin él sale igual.
  let invitacion = null;
  try { invitacion = await misInvitaciones(); } catch (e) { invitacion = null; }
  const codigo = invitacion?.disponible ? invitacion.codigo : '';
  const nombre = (store.athlete?.name || store.authUser?.name || '').trim().split(/\s+/)[0] || '';
  const q = await swal.fire({
    title: 'Compartir tu progreso',
    html: '<p style="font-size:13px">Una imagen con tu racha, tu nivel y tus medallas'
      + (codigo ? ', y tu código para que quien la vea entre invitado por ti' : '')
      + '. <b>Ningún dato de salud.</b></p>'
      + (nombre ? `<label style="display:flex;gap:8px;justify-content:center;align-items:center;font-size:13px;margin-top:10px"><input id="con-nombre" type="checkbox" checked> Poner mi nombre (${nombre})</label>` : ''),
    input: 'radio',
    inputOptions: Object.fromEntries(Object.entries(FORMATOS).map(([k, f]) => [k, `${f.etiqueta} (${f.ancho}×${f.alto})`])),
    inputValue: 'publicacion',
    showCancelButton: true, confirmButtonText: 'Crear imagen', cancelButtonText: 'Cancelar',
    preConfirm: (formato) => ({ formato, conNombre: !!document.getElementById('con-nombre')?.checked }),
  });
  if (!q.isConfirmed) return;
  compartiendo.value = true;
  try {
    const blob = await tarjetaDeProgreso(props.progreso, q.value.formato, {
      nombre: q.value.conNombre ? nombre : '',
      codigo,
      enlace: codigo ? enlaceDeInvitacion(codigo) : '',
    });
    const como = await compartirTarjeta(blob);
    if (como !== 'cancelada') {
      api.post('/telemetry/share', { format: q.value.formato, how: como }).catch(() => {});
      store.showToast(como === 'compartida' ? '¡Compartida!' : 'Imagen descargada.');
    }
  } finally {
    compartiendo.value = false;
  }
};
</script>
