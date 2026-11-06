<template>
  <!-- «Cuenta» en la tuerca (revisión del 2026-10-21). «Restablecer mi perfil» vivía al final de Avanzado, una
       pestaña opcional de ajustes finos, y borraba TODO el perfil: los dos evaluadores lo marcaron grave. Aquí va en
       una zona de peligro y hay que escribir BORRAR. Borra el perfil, no la cuenta (ni el acceso, ni los pagos). -->
  <div class="space-y-5">
    <div class="space-y-1">
      <p class="text-[12px] font-bold text-white/60">Tu cuenta</p>
      <p class="text-sm text-white">{{ store.athlete?.name || store.authUser?.name || 'Atleta' }}</p>
      <p v-if="store.authUser?.email" class="text-[13px] text-white/70">{{ store.authUser.email }}</p>
      <!-- Lo normal de una cuenta, antes de la zona de peligro (revisión del 2026-10-22: sólo había el botón rojo). -->
      <button type="button" @click="cerrarSesion"
              class="mt-2 flex items-center gap-1.5 px-3 min-h-[44px] rounded-xl border border-white/20 text-[13px] font-bold text-white/85 hover:text-white">
        <span class="material-symbols-outlined text-[18px]">logout</span>Cerrar sesión
      </button>
    </div>

    <section class="rounded-2xl border border-[#ffb4ab]/30 bg-[#93000a]/10 p-4 space-y-3">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-[#ffb4ab] text-[20px]">warning</span>
        <p class="text-sm font-black text-[#ffb4ab]">Borrar mi perfil y empezar de cero</p>
      </div>
      <p class="text-[13px] text-white/85 leading-relaxed">Se borran:</p>
      <!-- Todo lo que de verdad borra (ProfileRepository::deleteByUserId, 2026-10-22): la lista callaba el plan, el
           historial, el cuaderno y los check-in, que también se van. -->
      <ul class="text-[13px] text-white/80 list-disc pl-5 space-y-0.5">
        <li>tus carreras, tus días de entrenamiento y tus marcas;</li>
        <li>tu fuerza, tus ritmos y tus pruebas de umbral;</li>
        <li>tus lesiones y lo que declaraste de salud;</li>
        <li>tu plan, tus planes anteriores, tu cuaderno y tus check-in.</li>
      </ul>
      <p class="text-[13px] text-white/70 leading-relaxed">Tu cuenta y tu acceso no se tocan. Tendrás que volver a
        conectar tu Garmin. Los entrenos que ya están en tu reloj se quedan ahí: puedes borrarlos desde Garmin Connect.</p>
      <label class="block">
        <span class="text-[13px] font-bold text-white/80">Para confirmar, escribe BORRAR</span>
        <input v-model="confirmacion" type="text" autocomplete="off" autocapitalize="characters"
               class="mt-1.5 w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none" />
      </label>
      <button type="button" @click="restablecer" :disabled="confirmacion.trim().toUpperCase() !== 'BORRAR' || borrando"
              class="w-full min-h-[44px] rounded-xl border border-[#ffb4ab]/50 text-[13px] font-black text-[#ffb4ab] hover:bg-[#93000a]/30 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1.5">
        <span class="material-symbols-outlined text-[18px]">{{ borrando ? 'sync' : 'delete' }}</span>
        {{ borrando ? 'Borrando…' : 'Borrar mi perfil' }}
      </button>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';

const store = useAppStore();
const router = useRouter();
const confirmacion = ref('');
const borrando = ref(false);

const cerrarSesion = () => {
  store.isSettingsOpen = false;
  store.logout();
  router.push('/login');
};

const restablecer = async () => {
  borrando.value = true;
  try {
    await store.resetProfileData();
    confirmacion.value = '';
    store.isSettingsOpen = false;
    store.showToast('Perfil restablecido.');
    router.push({ path: '/admin/perfil', query: { tab: 'identidad' } });
  } catch (e) {
    store.showToast('No se pudo restablecer el perfil.');
  } finally {
    borrando.value = false;
  }
};
</script>
