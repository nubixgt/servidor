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
    <div class="w-full max-w-[480px] relative z-10">
      <div class="bg-surface-container-lowest rounded-xl shadow-xl px-space-md sm:px-space-xl py-space-xl flex flex-col items-center text-center relative overflow-hidden">
        <!-- Court Decorative Ribbon -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary via-primary-container to-tertiary-fixed"></div>

        <!-- Official League Emblem -->
        <router-link to="/" class="mb-space-md flex items-center justify-center">
          <div class="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center shadow-sm relative group transition-transform hover:scale-105 duration-200">
            <img :src="logoUrl" alt="Logo Liga Sanarateca" class="w-12 h-12 object-contain" />
            <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
              <span class="relative inline-flex rounded-full h-4 w-4 bg-primary"></span>
            </span>
          </div>
        </router-link>

        <!-- Header Typography -->
        <div class="mb-space-sm">
          <span class="inline-flex items-center px-space-sm py-space-2xs rounded-full bg-surface-container text-on-surface-variant font-label-meta text-label-meta tracking-wider uppercase mb-space-2xs">
            Torneo Oficial 2025 • Apertura
          </span>
          <h1 class="font-headline-lg text-headline-lg sm:text-headline-xl text-on-surface uppercase tracking-tight">
            PORTAL DE GESTIÓN DEPORTIVA
          </h1>
        </div>
        <p class="font-body-sm text-body-sm text-secondary max-w-sm mb-space-lg leading-relaxed">
          Acceso para delegados de franquicia, árbitros colegiados, oficiales de mesa técnica y atletas inscritos.
        </p>

        <!-- Role Selector -->
        <div class="w-full mb-space-lg">
          <label class="block text-left font-label-pill text-label-pill text-on-surface mb-space-xs">
            Selecciona tu rol
          </label>
          <div class="grid grid-cols-3 gap-space-xs p-space-2xs bg-surface-container rounded-lg">
            <button 
              v-for="role in roles"
              :key="role.id"
              type="button"
              @click="selectedRole = role.id"
              :class="[
                'font-label-pill text-label-pill py-2.5 px-space-xs rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5',
                selectedRole === role.id 
                  ? 'bg-primary-container text-on-primary-fixed shadow-sm font-semibold' 
                  : 'text-secondary hover:text-on-surface'
              ]"
            >
              <span class="material-symbols-outlined text-[16px]">{{ role.icon }}</span>
              <span class="truncate">{{ role.label }}</span>
            </button>
          </div>
        </div>

        <!-- Authentication Form -->
        <form @submit.prevent="handleLogin" class="w-full space-y-space-md text-left">
          <div class="flex flex-col space-y-space-2xs">
            <label class="font-label-pill text-label-pill text-on-surface flex justify-between items-center" for="identifier">
              <span>Correo Electrónico o DPI/CUI</span>
              <span class="font-label-meta text-label-meta text-secondary uppercase">Oficial Renap</span>
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-4 text-secondary flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-[20px]">person_outline</span>
              </span>
              <input 
                id="identifier"
                v-model="identifier"
                type="text" 
                required 
                placeholder="ejemplo@baloncesto.gt o 2548..."
                class="w-full pl-11 pr-4 py-3.5 bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md rounded-lg outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary transition-all duration-200"
              />
            </div>
          </div>

          <div class="flex flex-col space-y-space-2xs">
            <div class="flex items-center justify-between">
              <label class="font-label-pill text-label-pill text-on-surface" for="password">Contraseña</label>
              <a href="#" class="font-label-meta text-label-meta text-primary hover:underline transition-colors">
                ¿Olvidaste tu contraseña?
              </a>
            </div>
            <div class="relative flex items-center">
              <span class="absolute left-4 text-secondary flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-[20px]">lock_outline</span>
              </span>
              <input 
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required 
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

          <div class="flex items-center justify-between pt-space-2xs">
            <label class="inline-flex items-center cursor-pointer select-none">
              <input v-model="rememberMe" type="checkbox" class="w-4 h-4 rounded text-primary accent-primary bg-surface-container-low cursor-pointer"/>
              <span class="ml-2 font-body-sm text-body-sm text-on-surface-variant">Mantener sesión iniciada</span>
            </label>
            <span class="inline-flex items-center gap-1 font-label-meta text-label-meta text-secondary">
              <span class="material-symbols-outlined text-[14px] text-primary">verified_user</span>
              256-bit SSL
            </span>
          </div>

          <div class="pt-space-xs">
            <button 
              type="submit"
              :disabled="loading"
              class="w-full py-4 px-6 rounded-full bg-primary-container hover:bg-primary-fixed text-on-primary-fixed font-headline-md text-headline-md tracking-wider uppercase transition-all duration-200 transform active:scale-[0.99] shadow-lg hover:shadow-xl flex items-center justify-center gap-2 group disabled:opacity-75 cursor-pointer"
            >
              <span v-if="loading" class="material-symbols-outlined animate-spin text-[22px]">progress_activity</span>
              <span>{{ loading ? 'ACCEDIENDO...' : 'INGRESAR AL SISTEMA' }}</span>
              <span v-if="!loading" class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
          </div>
        </form>

        <div class="mt-space-lg pt-space-md w-full bg-surface-container-low/70 rounded-lg p-space-md flex flex-col gap-2">
          <p class="font-body-sm text-body-sm text-on-surface">
            ¿Tu equipo aún no está inscrito en la temporada?
          </p>
          <a href="#" class="inline-flex items-center justify-center font-label-pill text-label-pill text-primary hover:text-on-primary-container transition-colors group">
            <span>Solicitar registro de franquicia deportiva</span>
            <span class="material-symbols-outlined text-[16px] ml-1 group-hover:translate-x-0.5 transition-transform">open_in_new</span>
          </a>
        </div>
      </div>

      <div class="mt-space-md flex flex-wrap items-center justify-between text-secondary font-label-meta text-label-meta px-space-xs gap-2">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-primary"></span>
          <span>Sanarate, El Progreso • Gimnasio Municipal</span>
        </div>
        <div class="flex items-center gap-4">
          <router-link to="/" class="hover:text-on-surface transition-colors">Sitio Público</router-link>
          <span>•</span>
          <a href="#" class="hover:text-on-surface transition-colors">Soporte Técnico</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import logoUrl from '../../assets/images/logo.png';

const router = useRouter();

const selectedRole = ref('delegado');
const identifier = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(true);
const loading = ref(false);

const roles = [
  { id: 'delegado', label: 'Delegado', icon: 'badge' },
  { id: 'arbitro', label: 'Oficial', icon: 'sports' },
  { id: 'jugador', label: 'Atleta', icon: 'person' }
];

const handleLogin = () => {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
    router.push('/dashboard');
  }, 800);
};
</script>
