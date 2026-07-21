<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { LockClosedIcon } from '@heroicons/vue/24/outline';
import loginBg from '../../assets/images/LoginFondo.jpeg';
import logo from '../../assets/images/Logo.png';
import api from '../../services/api';
import { loginError } from '../../utils/alerts';

const router = useRouter();

const usuario = ref('');
const password = ref('');
const isAuthenticating = ref(false);
const error = ref('');

const handleSubmit = async () => {
  if (!usuario.value.trim() || !password.value.trim()) {
    error.value = 'Por favor completa todos los campos.';
    return;
  }
  error.value = '';
  isAuthenticating.value = true;

  try {
    const { data } = await api.post('/auth/login', {
      usuario: usuario.value.trim(),
      password: password.value,
    });

    localStorage.setItem('token', data.data.token);
    localStorage.setItem('usuario', JSON.stringify(data.data.usuario));

    router.push({ name: 'ModelDirectory' });
  } catch (err) {
    const message = err.response?.data?.message || 'No se pudo conectar con el servidor. Intenta de nuevo.';
    await loginError(message);
  } finally {
    isAuthenticating.value = false;
  }
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
          <img :src="logo" alt="Logo Aniversario CYD" class="h-20 md:h-24 w-auto drop-shadow-[0_4px_12px_rgba(0,0,0,0.5)]" />
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
            <!-- Usuario Field -->
            <div class="relative group">
              <label
                for="usuario"
                class="text-[10px] text-white/60 font-semibold uppercase tracking-[0.15em] mb-1 block group-focus-within:text-white group-focus-within:tracking-[0.2em] transition-all duration-300"
              >
                Usuario
              </label>
              <input
                id="usuario"
                type="text"
                placeholder="Ingresa tu usuario"
                v-model="usuario"
                required
                autocomplete="username"
                class="w-full bg-transparent border-t-0 border-x-0 border-b border-white/30 py-3 px-0 text-sm text-white focus:ring-0 focus:border-white transition-all duration-300 rounded-none placeholder:text-white/40"
              />
            </div>

            <!-- Password Field -->
            <div class="relative group">
              <label
                for="password"
                class="text-[10px] text-white/60 font-semibold uppercase tracking-[0.15em] mb-1 block group-focus-within:text-white group-focus-within:tracking-[0.2em] transition-all duration-300"
              >
                Contraseña
              </label>
              <input
                id="password"
                type="password"
                placeholder="••••••••"
                v-model="password"
                required
                autocomplete="current-password"
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
