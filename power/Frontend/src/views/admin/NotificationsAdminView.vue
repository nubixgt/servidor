<template>
  <div class="space-y-6 animate-fade-in-up">
    <header class="flex items-center gap-3.5">
      <div class="w-12 h-12 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.25)] shrink-0">
        <span class="material-symbols-outlined text-[26px]">campaign</span>
      </div>
      <div>
        <h2 class="text-2xl font-black text-white tracking-tight">Avisos</h2>
        <p class="text-xs text-[#c5c9ac] font-medium">
          Lo que escribas aquí le aparece en la campana a todos los atletas
        </p>
      </div>
    </header>

    <!-- Redactar -->
    <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
      <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
        <span class="material-symbols-outlined text-[#caf300] text-[20px]">edit_note</span>
        <span>Nuevo aviso</span>
      </h3>

      <div class="space-y-3">
        <div>
          <label class="block text-[10px] uppercase font-black text-[#8f9378] tracking-wider mb-1.5">Título</label>
          <input
            v-model="titulo"
            type="text"
            :maxlength="MAX_TITULO"
            placeholder="Mantenimiento del domingo"
            class="w-full bg-black/40 border border-white/15 focus:border-[#caf300] rounded-xl px-4 py-2.5 text-sm text-white placeholder-[#8f9378]/60 outline-none transition-colors"
          />
        </div>
        <div>
          <label class="block text-[10px] uppercase font-black text-[#8f9378] tracking-wider mb-1.5">Mensaje</label>
          <textarea
            v-model="cuerpo"
            rows="3"
            :maxlength="MAX_CUERPO"
            placeholder="El domingo de 8 a 10 la app estará en mantenimiento y no se podrán generar planes."
            class="w-full bg-black/40 border border-white/15 focus:border-[#caf300] rounded-xl px-4 py-2.5 text-sm text-white placeholder-[#8f9378]/60 outline-none transition-colors resize-y"
          ></textarea>
          <p class="text-[10px] font-mono-data text-[#8f9378] mt-1 text-right">
            {{ cuerpo.length }}/{{ MAX_CUERPO }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3 flex-wrap">
        <button
          @click="enviar"
          :disabled="!puedeEnviar"
          class="px-5 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_15px_rgba(202,243,0,0.3)] transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span class="material-symbols-outlined text-[18px]">{{ enviando ? 'progress_activity' : 'send' }}</span>
          <span>{{ enviando ? 'Enviando…' : 'Enviar a todos' }}</span>
        </button>
        <p v-if="error" class="text-[11px] font-bold text-[#ffb4ab]">{{ error }}</p>
      </div>
    </section>

    <!-- Enviados -->
    <section class="space-y-3">
      <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
        <span class="material-symbols-outlined text-[#caf300] text-[20px]">history</span>
        <span>Enviados</span>
        <span v-if="avisos.length" class="text-[#8f9378] font-mono-data text-xs normal-case">({{ avisos.length }})</span>
      </h3>

      <p v-if="cargando" class="text-xs font-mono-data text-[#8f9378]">Cargando…</p>

      <p v-else-if="!avisos.length" class="glass-card border border-white/10 rounded-2xl p-5 text-sm text-[#c5c9ac]">
        Todavía no has mandado ninguno.
      </p>

      <article
        v-for="aviso in avisos"
        :key="aviso.id"
        class="glass-card border border-white/10 rounded-2xl p-4 flex gap-3 items-start animate-fade-in-up"
      >
        <span class="material-symbols-outlined text-[#caf300] text-[20px] mt-0.5 shrink-0">campaign</span>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-white leading-snug">{{ aviso.title }}</p>
          <p class="text-xs text-[#c5c9ac] leading-relaxed mt-1 whitespace-pre-wrap break-words">{{ aviso.body }}</p>
          <p class="text-[10px] font-mono-data text-[#8f9378] mt-2">
            {{ fecha(aviso.createdAt) }}
            <span v-if="aviso.author"> · {{ aviso.author }}</span>
            <span v-if="aviso.dismissedCount"> · {{ aviso.dismissedCount }} lo han quitado de su bandeja</span>
          </p>
        </div>
        <button
          @click="retirar(aviso)"
          class="p-2 text-[#8f9378] hover:text-[#ffb4ab] rounded-xl hover:bg-white/10 active:scale-95 transition-all shrink-0"
          title="Retirar para todos"
        >
          <span class="material-symbols-outlined text-[20px]">delete</span>
        </button>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppStore } from '../../stores/appStore';
import {
  getSentNotifications,
  sendNotification,
  deleteNotification,
} from '../../services/notifications';
import swal from '../../utils/swal';

// Los mismos topes que la columna aguanta (Database/011_create_notifications.sql).
// Puestos aquí también para que el aviso no llegue recortado sin avisar.
const MAX_TITULO = 120;
const MAX_CUERPO = 1000;

const store = useAppStore();

const titulo = ref('');
const cuerpo = ref('');
const avisos = ref([]);
const cargando = ref(true);
const enviando = ref(false);
const error = ref('');

const puedeEnviar = computed(
  () => !enviando.value && titulo.value.trim() !== '' && cuerpo.value.trim() !== ''
);

const fecha = (iso) => {
  if (!iso) return '';
  const d = new Date(String(iso).replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleString([], { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const cargar = async () => {
  cargando.value = true;
  try {
    avisos.value = await getSentNotifications();
  } catch (e) {
    error.value = 'No se pudieron cargar los avisos enviados.';
  } finally {
    cargando.value = false;
  }
};

const enviar = async () => {
  if (!puedeEnviar.value) return;
  enviando.value = true;
  error.value = '';
  try {
    await sendNotification({ title: titulo.value.trim(), body: cuerpo.value.trim() });
    titulo.value = '';
    cuerpo.value = '';
    await cargar();
    // El administrador también es un atleta con bandeja: si no se recarga, su
    // propio aviso no le aparece hasta el siguiente refresco de la página.
    await store.loadNotifications();
    store.showToast('Aviso enviado a todos los atletas.');
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo enviar el aviso.';
  } finally {
    enviando.value = false;
  }
};

const retirar = async (aviso) => {
  const result = await swal.fire({
    icon: 'warning',
    title: '¿Retirar este aviso?',
    // Se dice lo que de verdad pasa: no es "quitarlo de tu bandeja", es
    // borrarlo de la de todos, y no se puede deshacer.
    text: `"${aviso.title}" desaparecerá de la bandeja de todos los atletas. No se puede deshacer.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, retirar',
    cancelButtonText: 'Cancelar',
  });
  if (!result.isConfirmed) return;

  try {
    await deleteNotification(aviso.id);
    avisos.value = avisos.value.filter((a) => a.id !== aviso.id);
    await store.loadNotifications();
    store.showToast('Aviso retirado.');
  } catch (e) {
    store.showToast('No se pudo retirar el aviso.');
  }
};

onMounted(cargar);
</script>
