<template>
  <div
    v-if="store.isSettingsOpen"
    id="settings-modal-backdrop"
    class="fixed inset-0 z-50 flex items-start justify-center p-3 sm:p-4 pt-[6vh] bg-black/75 backdrop-blur-xl animate-scale-in select-none"
  >
    <!-- Anclado arriba: centrado, la barra de pestañas saltaba de sitio al cambiar de pestaña (revisión del 2026-10-22). -->
    <div class="glass-panel border border-white/20 rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-[0_0_60px_rgba(0,0,0,0.6)] relative animate-scale-in max-h-[85vh] overflow-y-auto hide-scrollbar">
      <div class="flex justify-between items-start border-b border-white/15 pb-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.25)]">
            <span class="material-symbols-outlined text-[26px]">{{ pestanaActual.icono }}</span>
          </div>
          <div>
            <h3 class="text-xl font-black text-white">{{ pestanaActual.nombre }}</h3>
            <p class="text-xs text-[#c5c9ac] font-medium">{{ pestanaActual.subtitulo }}</p>
          </div>
        </div>

        <button
          @click="store.isSettingsOpen = false"
          class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 active:scale-95 transition-all"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <!-- Las pestañas de la tuerca: «Precios» desde el 2026-10-21 (decisión del responsable). Todos los
           «Quiero suscribirme» de la app abren aquí (`store.abrirPrecios`). -->
      <div role="tablist" class="flex gap-1 p-1 rounded-xl bg-black/30 border border-white/10">
        <button
          v-for="t in pestanas"
          :key="t.id"
          type="button"
          role="tab"
          :aria-selected="store.settingsTab === t.id"
          @click="store.settingsTab = t.id"
          class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-black uppercase tracking-wider transition-colors"
          :class="store.settingsTab === t.id ? 'bg-[#caf300] text-[#121700]' : 'text-[#c5c9ac] hover:text-white hover:bg-white/10'"
        >
          <span class="material-symbols-outlined text-[16px]">{{ t.icono }}</span>{{ t.nombre }}
        </button>
      </div>

      <PreciosPanel v-if="store.settingsTab === 'precios'" />
      <CuentaPanel v-else-if="store.settingsTab === 'cuenta'" />

      <template v-else>
      <!-- Foto de perfil -->
      <div class="space-y-3 animate-fade-in-up delay-100">
        <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Tu foto de perfil</span>
        <div class="flex items-center gap-4">
          <div class="w-20 h-20 rounded-2xl overflow-hidden border border-white/20 bg-black/40 shrink-0 shadow-[0_0_15px_rgba(0,0,0,0.4)]">
            <img
              v-if="store.athleteAvatarSrc"
              :src="store.athleteAvatarSrc"
              alt="Tu foto"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-[#8f9378]">
              <span class="material-symbols-outlined text-[32px]">person</span>
            </div>
          </div>

          <div class="flex-1 min-w-0 space-y-2">
            <div class="flex flex-wrap gap-2">
              <button
                @click="abrirSelector"
                :disabled="cargando"
                class="px-4 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all active:scale-95 disabled:opacity-50 flex items-center gap-1.5"
              >
                <span class="material-symbols-outlined text-[18px]">{{ cargando ? 'progress_activity' : 'upload' }}</span>
                <span>{{ cargando ? 'Procesando...' : 'Subir foto' }}</span>
              </button>
              <button
                v-if="store.settings.athletePhoto"
                @click="quitarFoto"
                class="px-4 py-2.5 glass-pill text-[#ff8f83] rounded-xl text-xs font-bold uppercase tracking-wider transition-all active:scale-95"
              >
                Quitar
              </button>
            </div>
            <p v-if="errorFoto" class="text-[11px] font-bold text-[#ffb4ab] leading-snug">{{ errorFoto }}</p>
            <p v-else class="text-[11px] text-[#8f9378] leading-snug">
              Se recorta cuadrada y se guarda en este navegador.
            </p>
          </div>
        </div>
        <input
          ref="fileRef"
          type="file"
          :accept="tiposAceptados"
          class="hidden"
          @change="elegirFoto"
        />
      </div>

      <!-- Fondo de la app -->
      <div class="space-y-3 animate-fade-in-up delay-150">
        <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Fondo de la app</span>
        <div class="grid grid-cols-3 gap-3.5">
          <button
            v-for="bg in backgrounds"
            :key="bg.id"
            @click="store.updateSettings({ backgroundId: bg.id })"
            class="relative rounded-2xl overflow-hidden aspect-square border transition-all hover:scale-[1.03] active:scale-95"
            :class="store.settings.backgroundId === bg.id
              ? 'border-[#caf300] ring-2 ring-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.35)]'
              : 'border-white/15 hover:border-white/40'"
          >
            <img :src="bg.src" :alt="bg.label" class="w-full h-full object-cover" />
            <span
              v-if="store.settings.backgroundId === bg.id"
              class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-[#caf300] text-[#121700] flex items-center justify-center shadow"
            >
              <span class="material-symbols-outlined text-[14px]">check</span>
            </span>
          </button>
        </div>
      </div>

      <!-- Entrenador -->
      <div class="space-y-3 animate-fade-in-up delay-200">
        <div class="flex items-baseline justify-between gap-3">
          <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Tu entrenador</span>
          <span class="text-[10px] text-[#8f9378]">Cambia cómo te habla en el chat</span>
        </div>
        <div class="grid grid-cols-2 gap-3.5">
          <button
            v-for="coach in coaches"
            :key="coach.id"
            @click="store.updateSettings({ coachId: coach.id })"
            class="relative rounded-2xl overflow-hidden border transition-all hover:scale-[1.03] active:scale-95 text-left"
            :class="store.settings.coachId === coach.id
              ? 'border-[#caf300] ring-2 ring-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.35)]'
              : 'border-white/15 hover:border-white/40'"
          >
            <div class="aspect-square bg-black/40">
              <img
                v-if="coach.src"
                :src="coach.src"
                :alt="coach.name"
                class="w-full h-full object-cover object-top"
              />
              <!-- Sin retrato todavia: la inicial, no un hueco roto. -->
              <div
                v-else
                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#caf300]/25 to-transparent text-[#caf300] font-black text-4xl"
              >
                {{ coach.name.charAt(0) }}
              </div>
            </div>
            <div class="px-2.5 py-2 bg-black/70 backdrop-blur-sm">
              <p class="text-xs font-black text-white leading-none">{{ coach.name }}</p>
              <p class="text-[10px] text-[#caf300] font-bold mt-1 leading-none">{{ coach.toneLabel }}</p>
            </div>
            <span
              v-if="store.settings.coachId === coach.id"
              class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-[#caf300] text-[#121700] flex items-center justify-center shadow"
            >
              <span class="material-symbols-outlined text-[14px]">check</span>
            </span>
          </button>
        </div>
        <p v-if="store.coach" class="text-[11px] text-[#c5c9ac] leading-snug">
          <span class="font-bold text-white">{{ store.coach.name }}:</span> mi método es {{ store.coach.metodo }}.
        </p>
      </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { BACKGROUNDS, COACHES } from '../../utils/appearance';
import { aFotoDePerfil, TIPOS_ACEPTADOS } from '../../utils/imagen';
import PreciosPanel from '../precios/PreciosPanel.vue';
import CuentaPanel from '../cuenta/CuentaPanel.vue';

const store = useAppStore();
const backgrounds = BACKGROUNDS;
const coaches = COACHES;
const tiposAceptados = TIPOS_ACEPTADOS;
const pestanas = [
  { id: 'apariencia', nombre: 'Apariencia', icono: 'palette', subtitulo: 'Tu foto, el fondo y tu entrenador' },
  { id: 'precios', nombre: 'Precios', icono: 'sell', subtitulo: 'Tu plan de Power Coach AI' },
  { id: 'cuenta', nombre: 'Cuenta', icono: 'person', subtitulo: 'Tu cuenta y restablecer tu perfil' },
];
const pestanaActual = computed(() => pestanas.find((x) => x.id === store.settingsTab) || pestanas[0]);

const fileRef = ref(null);
const cargando = ref(false);
const errorFoto = ref('');

const abrirSelector = () => {
  if (fileRef.value) fileRef.value.click();
};

const elegirFoto = async (event) => {
  const file = event.target.files && event.target.files[0];
  // El input se limpia siempre: si no, elegir DOS VECES la misma foto no
  // dispara el change la segunda vez y parece que el boton se rompio.
  event.target.value = '';
  if (!file) return;

  cargando.value = true;
  errorFoto.value = '';
  try {
    store.setAthletePhoto(await aFotoDePerfil(file));
  } catch (e) {
    errorFoto.value = (e && e.message) || 'No se pudo procesar la imagen.';
  } finally {
    cargando.value = false;
  }
};

const quitarFoto = () => {
  errorFoto.value = '';
  store.setAthletePhoto(null);
};
</script>
