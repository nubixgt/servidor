<template>
  <div
    id="login-screen"
    class="min-h-screen w-full flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden select-none bg-[#07080a]"
  >
    <!-- Background Image -->
    <div class="absolute inset-0 pointer-events-none z-0 overflow-hidden select-none">
      <img
        :src="bgImage"
        alt="Fondo Power Coach AI"
        class="absolute inset-0 w-full h-full object-cover opacity-95 scale-100"
      />
      <!-- Soft transparent overlay -->
      <div class="absolute inset-0 bg-black/25" />
      <div class="absolute top-1/4 -left-32 w-96 h-96 bg-[#caf300]/15 rounded-full blur-[120px] animate-pulse-glow" />
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-md relative z-10 space-y-6 animate-fade-in-up">
      <!-- Brand Header -->
      <div class="text-center space-y-3">
        <div class="w-16 h-16 rounded-2xl bg-[#caf300]/20 border border-[#caf300]/60 mx-auto flex items-center justify-center text-[#caf300] shadow-[0_0_30px_rgba(202,243,0,0.4)] backdrop-blur-md animate-float">
          <span class="material-symbols-outlined text-[36px]">bolt</span>
        </div>

        <div>
          <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight drop-shadow-md">
            Power Coach <span class="text-[#caf300]">AI</span>
          </h1>
          <p class="text-xs sm:text-sm font-bold text-[#c5c9ac] tracking-widest uppercase mt-1 drop-shadow-sm">
            ELITE PERFORMANCE LAB • ACCESO ATLETA
          </p>
        </div>
      </div>

      <!-- Central Glass Card (Transparent Glassmorphism & Smooth Entrance) -->
      <div
        id="login-main-card"
        class="glass-card rounded-3xl p-6 sm:p-8 space-y-6 shadow-2xl border border-white/20 animate-scale-in delay-100"
      >
        <div v-if="errorMsg" class="p-3 bg-[#ffb4ab]/20 border border-[#ffb4ab]/40 rounded-xl flex items-center gap-2 text-[#ffb4ab] text-xs backdrop-blur-md animate-fade-in-up">
          <span class="material-symbols-outlined text-base">error</span>
          <span>{{ errorMsg }}</span>
        </div>

        <div v-if="statusMsg" class="p-3 bg-[#caf300]/10 border border-[#caf300]/30 rounded-xl flex items-center gap-2 text-[#caf300] text-xs backdrop-blur-md animate-fade-in-up">
          <span class="material-symbols-outlined text-base">hourglass_top</span>
          <span>{{ statusMsg }}</span>
        </div>

        <div class="text-center space-y-5">
          <!-- La regla vigente (2026-10-20): cuenta al conectar un Garmin que nunca pasó por la app. -->
          <p v-if="invitacion" class="text-xs text-[#caf300] font-bold">
            Vienes invitado por otro atleta (código {{ invitacion }}). Cuando conectes tu Garmin, si es una cuenta
            que nunca se ha usado en la app, los dos ganan semanas gratis.
          </p>
          <!-- Quien vio el código en una tarjeta compartida no tiene enlace: lo escribe aquí, ANTES de
               entrar con Google, porque sólo cuenta al crear la cuenta (ReferralService). -->
          <div v-else class="space-y-2">
            <button v-if="!escribirCodigo" type="button" @click="escribirCodigo = true"
                    class="text-[11px] font-bold text-[#c5c9ac] hover:text-[#caf300] underline underline-offset-2">
              ¿Tienes un código de invitación?
            </button>
            <form v-else class="flex gap-2 justify-center" @submit.prevent="usarCodigo">
              <input v-model="codigoEscrito" maxlength="16" autocomplete="off" aria-label="Código de invitación"
                     placeholder="Código" class="w-36 px-3 py-2 rounded-xl bg-black/40 border border-white/20 text-white text-sm uppercase tracking-wider focus:outline-none focus:border-[#caf300]" />
              <button type="submit" class="px-3 py-2 rounded-xl bg-[#caf300] text-[#121700] text-xs font-black uppercase">Usar</button>
            </form>
            <p v-if="errorCodigo" class="text-[11px] text-[#ffb4ab]">{{ errorCodigo }}</p>
          </div>
          <p class="text-xs text-[#c5c9ac]">
            Acceso solo para atletas en la prueba beta. Inicia sesión con tu cuenta de Google —
            si es tu primera vez, tu acceso quedará pendiente de aprobación.
          </p>

          <div id="google-signin-button" class="flex justify-center min-h-[44px]"></div>

          <div v-if="isLoading" class="text-xs text-[#c5c9ac] flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-base animate-spin">progress_activity</span>
            Verificando acceso...
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { codigoGuardado, guardarCodigo, olvidarCodigo } from '../../utils/invitacion';
import { escribirCodigo as reclamarCodigo } from '../../services/invitaciones';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import { loginWithGoogle } from '../../services/auth';
import swal from '../../utils/swal';
import bgImage from '../../assets/images/FondoPagina.jpeg';

const router = useRouter();
const store = useAppStore();

const isLoading = ref(false);
const errorMsg = ref('');
const statusMsg = ref('');

const invitacion = ref(codigoGuardado());
const escribirCodigo = ref(false);
const codigoEscrito = ref('');
const errorCodigo = ref('');
const usarCodigo = () => {
  errorCodigo.value = '';
  if (!guardarCodigo(codigoEscrito.value)) {
    errorCodigo.value = 'Un código son de 4 a 16 letras o números, sin espacios.';
    return;
  }
  invitacion.value = codigoGuardado();
};

const handleCredentialResponse = async (response) => {
  isLoading.value = true;
  errorMsg.value = '';
  statusMsg.value = '';

  try {
    const codigo = codigoGuardado();
    const result = await loginWithGoogle(response.credential, codigo);
    // Ya viajó con el login que crea la cuenta (o la cuenta ya existía): no se vuelve a mandar.
    if (result.status === 'success' || result.status === 'pending') olvidarCodigo();

    if (result.status === 'success') {
      store.setSession(result.token, result.user);
      // La cuenta ya existía: el código cuenta igual si todavía no conectó su Garmin (2026-10-21).
      // Si ya lo conectó o ya tenía quien le invitó, el servidor lo dice y no pasa nada.
      if (codigo) reclamarCodigo(codigo).catch(() => {});
      store.loadProfile();
      await swal.fire({
        icon: 'success',
        title: `¡Bienvenido, ${result.user.name}!`,
        timer: 1500,
        showConfirmButton: false,
      });
      router.push('/admin/dashboard');
    } else if (result.status === 'pending') {
      statusMsg.value = 'Tu acceso está en revisión. Te contactaremos por correo cuando esté aprobado.';
      swal.fire({
        icon: 'info',
        title: 'Acceso en revisión',
        text: 'Tu solicitud ya quedó registrada. Te contactaremos por correo cuando esté aprobada.',
      });
    } else if (result.status === 'rejected') {
      errorMsg.value = 'Tu solicitud de acceso no fue aprobada. Contacta al equipo si crees que es un error.';
      swal.fire({
        icon: 'error',
        title: 'Acceso no aprobado',
        text: 'Tu solicitud de acceso no fue aprobada. Contacta al equipo si crees que es un error.',
      });
    } else {
      errorMsg.value = result.message || 'No se pudo iniciar sesión con Google.';
      swal.fire({
        icon: 'error',
        title: 'No se pudo iniciar sesión',
        text: result.message || 'Intenta de nuevo con tu cuenta de Google.',
      });
    }
  } catch (e) {
    errorMsg.value = 'Error de conexión con el servidor. Intenta de nuevo.';
    swal.fire({
      icon: 'error',
      title: 'Error de conexión',
      text: 'No se pudo contactar al servidor. Intenta de nuevo.',
    });
  } finally {
    isLoading.value = false;
  }
};

const renderGoogleButton = () => {
  if (!window.google?.accounts?.id) {
    setTimeout(renderGoogleButton, 200);
    return;
  }

  window.google.accounts.id.initialize({
    client_id: import.meta.env.VITE_GOOGLE_CLIENT_ID,
    callback: handleCredentialResponse,
  });

  window.google.accounts.id.renderButton(document.getElementById('google-signin-button'), {
    theme: 'filled_black',
    size: 'large',
    shape: 'pill',
    width: 300,
    locale: 'es',
  });
};

onMounted(renderGoogleButton);
</script>
