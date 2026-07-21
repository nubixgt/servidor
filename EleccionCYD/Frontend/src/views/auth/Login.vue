<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { LockClosedIcon } from '@heroicons/vue/24/outline';
import loginBg from '../../assets/images/LoginFondo.jpeg';
import logo from '../../assets/images/Logo.png';

const router = useRouter();

const judgeId = ref('JUDGE-04');
const accessCode = ref('PARIS2024');
const isAuthenticating = ref(false);
const error = ref('');

const handleSubmit = () => {
  if (!judgeId.value.trim() || !accessCode.value.trim()) {
    error.value = 'Por favor completa todos los campos.';
    return;
  }
  error.value = '';
  isAuthenticating.value = true;

  setTimeout(() => {
    isAuthenticating.value = false;
    router.push({ name: 'ModelDirectory' });
  }, 1200);
};
</script>

<template>
  <div class="relative min-h-screen w-full flex flex-col items-center justify-center overflow-hidden font-sans select-none selection:bg-white selection:text-black">
    <!-- Background Wrapper -->
    <div class="absolute inset-0 w-full h-full z-0">
      <div
        class="w-full h-full bg-cover bg-center"
        :style="{ backgroundImage: `url('${loginBg}')` }"
      ></div>
      <div class="absolute inset-0 bg-black/25"></div>
    </div>

    <!-- Main Content -->
    <main class="relative z-10 flex flex-col items-center justify-center px-6 md:px-16 py-12 w-full max-w-lg">
      <!-- Header Branding -->
      <Transition appear name="fade-down" style="transition-delay: 0.1s">
        <div class="mb-10 text-center flex flex-col items-center">
          <img :src="logo" alt="Logo Aniversario CYD" class="h-20 md:h-24 w-auto mb-4 drop-shadow-[0_4px_12px_rgba(0,0,0,0.5)]" />
          <h1 class="font-serif text-3xl md:text-4xl tracking-[0.25em] text-white font-normal uppercase drop-shadow-[0_2px_8px_rgba(0,0,0,0.6)]">
            EleccionCYD
          </h1>
          <p class="text-xs text-white/80 mt-2 tracking-[0.3em] font-semibold uppercase drop-shadow-[0_2px_8px_rgba(0,0,0,0.6)]">
            PORTAL DEL JURADO
          </p>
        </div>
      </Transition>

      <!-- Central Login Card (glassmorphism) -->
      <Transition appear name="fade-up" style="transition-delay: 0.3s">
        <div class="w-full bg-white/10 backdrop-blur-2xl border border-white/25 p-8 md:p-12 shadow-[0_8px_40px_rgba(0,0,0,0.35)] rounded-3xl">
          <header class="mb-10">
            <h2 class="text-3xl text-white font-light tracking-tight mb-2">Iniciar sesión</h2>
            <p class="text-white/70 text-sm">Por favor autentícate para acceder al panel de evaluación.</p>
          </header>

          <form @submit.prevent="handleSubmit" class="space-y-8">
            <!-- Judge ID Field -->
            <div class="relative group">
              <label
                for="judge_id"
                class="text-[10px] text-white/60 font-semibold uppercase tracking-[0.15em] mb-1 block group-focus-within:text-white group-focus-within:tracking-[0.2em] transition-all duration-300"
              >
                ID de Jurado
              </label>
              <input
                id="judge_id"
                type="text"
                placeholder="Ingresa tu número de identificación"
                v-model="judgeId"
                required
                class="w-full bg-transparent border-t-0 border-x-0 border-b border-white/30 py-3 px-0 text-sm text-white focus:ring-0 focus:border-white transition-all duration-300 rounded-none placeholder:text-white/40"
              />
            </div>

            <!-- Access Code Field -->
            <div class="relative group">
              <label
                for="access_code"
                class="text-[10px] text-white/60 font-semibold uppercase tracking-[0.15em] mb-1 block group-focus-within:text-white group-focus-within:tracking-[0.2em] transition-all duration-300"
              >
                Código de Acceso
              </label>
              <input
                id="access_code"
                type="password"
                placeholder="••••••••"
                v-model="accessCode"
                required
                class="w-full bg-transparent border-t-0 border-x-0 border-b border-white/30 py-3 px-0 text-sm text-white focus:ring-0 focus:border-white transition-all duration-300 rounded-none placeholder:text-white/40"
              />
            </div>

            <p v-if="error" class="text-red-300 text-xs tracking-wide">{{ error }}</p>

            <!-- Action Button -->
            <div class="pt-4">
              <button
                type="submit"
                :disabled="isAuthenticating"
                class="w-full bg-white text-black py-4 text-xs font-semibold tracking-[0.25em] uppercase hover:bg-white/90 active:scale-[0.99] transition-all duration-300 rounded-xl cursor-pointer disabled:opacity-75"
              >
                {{ isAuthenticating ? 'Autenticando...' : 'Iniciar sesión' }}
              </button>
            </div>
          </form>

          <footer class="mt-12 text-center">
            <div class="flex items-center justify-center gap-2 text-white/60">
              <LockClosedIcon class="w-3.5 h-3.5 opacity-80" />
              <span class="text-[10px] uppercase tracking-[0.2em]">Entorno de Evaluación Seguro</span>
            </div>
          </footer>
        </div>
      </Transition>

      <!-- Footer Decoration -->
      <Transition appear name="fade-in" style="transition-delay: 0.5s">
        <div class="mt-12 flex items-center gap-4 opacity-70">
          <span class="w-8 h-[1px] bg-white/50"></span>
          <span class="text-[10px] text-white uppercase tracking-[0.25em] drop-shadow-[0_2px_6px_rgba(0,0,0,0.6)]">Edición 2026</span>
          <span class="w-8 h-[1px] bg-white/50"></span>
        </div>
      </Transition>
    </main>

    <!-- Subtle Grain Overlay for Texture -->
    <div class="fixed inset-0 pointer-events-none opacity-[0.025] z-50" style="background-image: url('https://grainy-gradients.vercel.app/noise.svg')"></div>
  </div>
</template>

<style scoped>
.fade-down-enter-active,
.fade-up-enter-active,
.fade-in-enter-active {
  transition: all 0.8s ease;
}

.fade-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.fade-down-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.fade-up-enter-from {
  opacity: 0;
  transform: translateY(15px);
}
.fade-up-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.fade-in-enter-from {
  opacity: 0;
}
.fade-in-enter-to {
  opacity: 0.6;
}
</style>
