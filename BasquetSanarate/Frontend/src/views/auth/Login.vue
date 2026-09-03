<template>
  <div class="min-h-screen w-full bg-surface text-on-surface font-body-md text-body-md antialiased flex items-center justify-center relative overflow-hidden py-space-2xl px-gutter-desktop">
    <!-- Ambient Court Accents -->
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-primary-container/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-tertiary-container/20 blur-3xl pointer-events-none"></div>

    <!-- Giant Typographic Watermark -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 select-none pointer-events-none opacity-[0.035] text-on-surface font-display-hero text-[180px] sm:text-[240px] tracking-tighter whitespace-nowrap leading-none font-bold uppercase">
      SANARATE
    </div>

    <!-- Central Card -->
    <div class="w-full max-w-[440px] relative z-10">
      <div class="bg-surface-container-lowest rounded-xl shadow-xl px-space-md sm:px-space-xl py-space-xl flex flex-col items-center text-center relative overflow-hidden">
        <!-- Court Decorative Ribbon -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary via-primary-container to-tertiary-fixed"></div>

        <!-- Official League Emblem -->
        <router-link to="/" class="mb-space-md flex items-center justify-center">
          <div class="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center shadow-sm relative transition-transform hover:scale-105 duration-200">
            <img :src="logoUrl" alt="Logo Liga Sanarateca" class="w-12 h-12 object-contain" />
          </div>
        </router-link>

        <!-- Header Typography -->
        <div class="mb-space-sm">
          <span class="inline-flex items-center px-space-sm py-space-2xs rounded-full bg-surface-container text-on-surface-variant font-label-meta text-label-meta tracking-wider uppercase mb-space-2xs">
            Consola Administrativa
          </span>
          <h1 class="font-headline-lg text-headline-lg sm:text-headline-xl text-on-surface uppercase tracking-tight">
            PANEL DE GESTIÓN
          </h1>
        </div>
        <p class="font-body-sm text-body-sm text-secondary max-w-sm mb-space-lg leading-relaxed">
          Acceso exclusivo para la administración de la Liga de Baloncesto Sanarateca.
        </p>

        <!-- Authentication Form -->
        <form @submit.prevent="handleLogin" class="w-full space-y-space-md text-left">
          <div class="flex flex-col space-y-space-2xs">
            <label class="font-label-pill text-label-pill text-on-surface" for="usuario">Usuario</label>
            <div class="relative flex items-center">
              <span class="absolute left-4 text-secondary flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-[20px]">person_outline</span>
              </span>
              <input
                id="usuario"
                v-model="usuario"
                type="text"
                required
                autocomplete="username"
                placeholder="admin"
                class="w-full pl-11 pr-4 py-3.5 bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md rounded-lg outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary transition-all duration-200"
              />
            </div>
          </div>

          <div class="flex flex-col space-y-space-2xs">
            <label class="font-label-pill text-label-pill text-on-surface" for="password">Contraseña</label>
            <div class="relative flex items-center">
              <span class="absolute left-4 text-secondary flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-[20px]">lock_outline</span>
              </span>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••••••"
                class="w-full pl-11 pr-11 py-3.5 bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md rounded-lg outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary transition-all duration-200"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3.5 text-secondary hover:text-on-surface p-1 rounded-full focus:outline-none transition-colors"
              >
                <span class="material-symbols-outlined text-[20px]">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
          </div>

          <p v-if="errorMsg" class="flex items-center gap-1.5 font-body-sm text-body-sm text-error">
            <span class="material-symbols-outlined text-[18px]">error</span>
            {{ errorMsg }}
          </p>

          <div class="pt-space-xs">
            <button
              type="submit"
              :disabled="loading"
              class="w-full py-4 px-6 rounded-full bg-primary-container hover:bg-primary-fixed text-on-primary-fixed font-headline-md text-headline-md tracking-wider uppercase transition-all duration-200 transform active:scale-[0.99] shadow-lg hover:shadow-xl flex items-center justify-center gap-2 group disabled:opacity-75 cursor-pointer"
            >
              <span v-if="loading" class="material-symbols-outlined animate-spin text-[22px]">progress_activity</span>
              <span>{{ loading ? 'ACCEDIENDO...' : 'INGRESAR' }}</span>
              <span v-if="!loading" class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
          </div>
        </form>
      </div>

      <div class="mt-space-md flex flex-wrap items-center justify-between text-secondary font-label-meta text-label-meta px-space-xs gap-2">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-primary"></span>
          <span>Sanarate, El Progreso</span>
        </div>
        <router-link to="/" class="hover:text-on-surface transition-colors">Volver al sitio público</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useAlert } from '../../composables/useAlert';
import logoUrl from '../../assets/images/logo.png';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const alert = useAlert();

const usuario = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const errorMsg = ref('');

onMounted(() => {
  if (route.query.logout) {
    alert.flash('Sesión cerrada', 'Cerraste sesión correctamente.');
    router.replace({ path: '/login', query: {} });
  }
});

const handleLogin = async () => {
  if (!usuario.value.trim() || !password.value) {
    errorMsg.value = 'Ingresa usuario y contraseña.';
    alert.error('Datos incompletos', 'Ingresa tu usuario y contraseña.');
    return;
  }
  loading.value = true;
  errorMsg.value = '';
  try {
    const user = await auth.login({ usuario: usuario.value, password: password.value });
    await alert.flash('Ingreso correcto', `Bienvenido, ${user?.nombre || 'Administrador'}`);
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/admin';
    router.push(redirect);
  } catch (err) {
    errorMsg.value =
      err?.response?.data?.message || 'No se pudo iniciar sesión. Verifica tus credenciales.';
    alert.error('No se pudo iniciar sesión', errorMsg.value);
  } finally {
    loading.value = false;
  }
};
</script>
