<template>
  <!-- «Invitar a un atleta» (Beta 3.0, Database/033). Antes era una puerta falsa; ahora es su
       código, su enlace y lo que lleva ganado. La lógica está en ReferralService (PHP). -->
  <div
    v-if="store.invitarAbierto"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-xl animate-scale-in"
    @keydown.esc="cerrar"
  >
    <div class="glass-panel border border-white/20 rounded-3xl max-w-lg w-full p-5 sm:p-7 space-y-5 shadow-[0_0_60px_rgba(0,0,0,0.6)] max-h-[88vh] overflow-y-auto hide-scrollbar"
         role="dialog" aria-labelledby="invitar-titulo">
      <div class="flex justify-between items-start gap-3">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300]">
            <span class="material-symbols-outlined text-[24px]">person_add</span>
          </div>
          <div>
            <h3 id="invitar-titulo" class="text-lg font-black text-white">Invitar a un atleta</h3>
            <p class="text-xs text-[#c5c9ac]">Entrenen los dos con su coach.</p>
          </div>
        </div>
        <button type="button" @click="cerrar" aria-label="Cerrar" class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <p v-if="cargando" class="text-sm text-[#c5c9ac]">Cargando…</p>
      <p v-else-if="!datos?.disponible" class="text-sm text-[#c5c9ac]">
        Las invitaciones todavía no están abiertas. Si quieres traer a alguien, dínoslo desde «Ayuda».
      </p>

      <template v-else>
        <p class="text-sm text-[#e5e2e1] leading-relaxed">
          Cuando alguien entre con tu enlace y conecte su Garmin,
          <b class="text-[#caf300]">tú ganas {{ semanas(datos.semanas_por_invitado) }}</b> y
          <b class="text-[#caf300]">él o ella {{ semanas(datos.semanas_para_el_invitado) }}</b> gratis, además de
          sus {{ semanas(datos.semanas_de_prueba || 2) }} de prueba. Se suman por cada persona nueva; cuenta una vez
          por cuenta de Garmin.
        </p>

        <div class="rounded-2xl border border-[#caf300]/30 bg-[#caf300]/5 p-4 space-y-3">
          <div class="flex items-center justify-between gap-3">
            <span class="text-[10px] uppercase tracking-widest font-black text-[#8f9378]">Tu código</span>
            <span class="font-mono-data text-xl font-black text-[#caf300] tracking-[0.2em]">{{ datos.codigo }}</span>
          </div>
          <p class="font-mono-data text-xs text-[#c5c9ac] break-all">{{ enlace }}</p>
          <div class="flex gap-2 flex-wrap">
            <button type="button" @click="copiar" class="flex-1 min-w-[120px] py-2.5 bg-[#caf300] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider">
              Copiar enlace
            </button>
            <button v-if="puedeCompartir" type="button" @click="compartir" class="flex-1 min-w-[120px] py-2.5 glass-pill rounded-xl text-xs font-black uppercase tracking-wider text-white">
              Compartir
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="glass-card rounded-2xl border border-white/10 p-3">
            <p class="text-[10px] uppercase font-black text-[#8f9378]">Invitados que ya cuentan</p>
            <p class="text-2xl font-black text-white tabular-nums">{{ cuentan }}<span class="text-sm text-[#8f9378]"> de {{ datos.invitados.length }}</span></p>
          </div>
          <div class="glass-card rounded-2xl border border-white/10 p-3">
            <p class="text-[10px] uppercase font-black text-[#8f9378]">Tus semanas gratis</p>
            <p class="text-2xl font-black text-[#caf300] tabular-nums">{{ datos.saldo ? datos.saldo.total : datos.semanas_ganadas }}</p>
            <p v-if="datos.saldo && datos.saldo.total" class="text-[10px] text-[#8f9378] leading-snug">
              {{ [datos.saldo.prueba ? `${datos.saldo.prueba} de prueba` : '', datos.saldo.invitador ? `${datos.saldo.invitador} por invitar` : '', datos.saldo.invitado ? `${datos.saldo.invitado} por entrar invitado` : ''].filter(Boolean).join(' · ') }}
            </p>
          </div>
        </div>
        <p class="text-[11px] text-[#8f9378] leading-relaxed">
          Las semanas gratis se gastan por días desde que se ganan. Después, Power Coach AI cuesta
          <b class="text-white">{{ datos.precio?.texto || 'Q60 (8 USD) al mes' }}</b> (condiciones de uso, punto 8).
        </p>

        <ul v-if="datos.invitados.length" class="space-y-1.5">
          <li v-for="(i, n) in datos.invitados" :key="n" class="flex items-center justify-between gap-3 text-sm rounded-xl bg-white/5 px-3 py-2">
            <span class="text-white font-semibold">{{ i.nombre || 'Un atleta' }}</span>
            <span class="text-[11px]" :class="i.cuenta ? 'text-[#caf300]' : 'text-[#8f9378]'">
              {{ i.cuenta ? `+${semanas(i.semanas)}` : i.garmin_repetido ? 'su Garmin ya estaba en la app: no cuenta' : i.esperando_aprobacion ? 'esperando su acceso' : 'aún sin conectar su Garmin' }}
            </span>
          </li>
        </ul>
        <p v-if="datos.invitado_por" class="text-[11px] text-[#8f9378]">
          Tú entraste invitado por {{ datos.invitado_por.nombre }}.
        </p>

        <!-- ¿Te invitó alguien? Se puede escribir hasta conectar el Garmin, que es cuando cuenta
             (decisión del responsable, 2026-10-21; ReferralService::reclamar). -->
        <form v-else-if="datos.puede_escribir_codigo" @submit.prevent="usarCodigo"
              class="rounded-2xl border border-[#a5eeff]/30 bg-[#a5eeff]/5 p-4 space-y-2">
          <label for="codigo-de-quien-invito" class="block text-xs font-bold text-white">¿Te invitó alguien?</label>
          <p class="text-[11px] text-[#c5c9ac] leading-snug">
            Escribe su código antes de conectar tu Garmin: al conectarlo ganan semanas gratis los dos.
          </p>
          <div class="flex gap-2">
            <input id="codigo-de-quien-invito" v-model="codigoEscrito" maxlength="16" autocomplete="off"
                   placeholder="Su código"
                   class="flex-1 min-w-0 px-3 py-2 rounded-xl bg-black/40 border border-white/20 text-white text-sm uppercase tracking-wider focus:outline-none focus:border-[#a5eeff]" />
            <button type="submit" :disabled="enviando || !codigoEscrito.trim()"
                    class="px-4 py-2 rounded-xl bg-[#a5eeff] text-[#121700] text-xs font-black uppercase disabled:opacity-50">
              {{ enviando ? '…' : 'Usar' }}
            </button>
          </div>
          <p v-if="errorCodigo" class="text-[11px] text-[#ffb4ab]" role="alert">{{ errorCodigo }}</p>
        </form>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { escribirCodigo, misInvitaciones } from '../../services/invitaciones';
import { enlaceDeInvitacion } from '../../utils/invitacion';

const store = useAppStore();
const datos = ref(null);
const cargando = ref(false);

const enlace = computed(() => (datos.value?.codigo ? enlaceDeInvitacion(datos.value.codigo) : ''));
const cuentan = computed(() => (datos.value?.invitados || []).filter((i) => i.cuenta).length);
const puedeCompartir = typeof navigator !== 'undefined' && !!navigator.share;
const semanas = (n) => (n === 1 ? '1 semana' : `${n} semanas`);

const cargar = async () => {
  cargando.value = true;
  try {
    datos.value = await misInvitaciones();
  } catch (e) {
    datos.value = { disponible: false };
  } finally {
    cargando.value = false;
  }
};

watch(() => store.invitarAbierto, (abierto) => { if (abierto) cargar(); });

const cerrar = () => { store.invitarAbierto = false; };

const codigoEscrito = ref('');
const errorCodigo = ref('');
const enviando = ref(false);
const usarCodigo = async () => {
  errorCodigo.value = '';
  enviando.value = true;
  try {
    datos.value = await escribirCodigo(codigoEscrito.value);
    codigoEscrito.value = '';
    store.showToast(`Listo: te invitó ${datos.value.invitado_por?.nombre || 'otro atleta'}. Al conectar tu Garmin cuenta para los dos.`);
  } catch (e) {
    errorCodigo.value = e?.response?.data?.message || 'No se pudo guardar el código.';
  } finally {
    enviando.value = false;
  }
};

const mensaje = () => `Entreno con Power Coach AI: un entrenador que lee mi reloj Garmin y me escribe la semana. Entra con mi enlace: ${enlace.value}`;

const copiar = async () => {
  try {
    await navigator.clipboard.writeText(enlace.value);
    store.showToast('Enlace copiado.');
  } catch (e) {
    store.showToast(`Tu enlace: ${enlace.value}`);
  }
};

const compartir = async () => {
  try {
    await navigator.share({ title: 'Power Coach AI', text: mensaje(), url: enlace.value });
  } catch (e) { /* cancelado */ }
};
</script>
