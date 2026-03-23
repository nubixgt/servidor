<template>
  <div class="min-h-screen flex bg-surface">
    <!-- Left Side - Form -->
    <div class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-20 xl:px-24 bg-white">
      <div class="w-full max-w-sm">
        <div class="mb-10 text-center lg:text-left">
          <div class="w-12 h-12 bg-[var(--color-primary)] rounded-xl flex items-center justify-center shadow-sm mb-6 mx-auto lg:mx-0">
            <span class="text-white font-bold text-2xl">AL</span>
          </div>
          <h2 class="text-3xl font-sans font-bold text-on-surface tracking-tight">Bienvenido de nuevo</h2>
          <p class="mt-2 text-sm text-on-surface-variant">
            Ingresa tus credenciales para acceder al sistema.
          </p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-on-surface-variant mb-2">
              Usuario
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <UserIcon class="h-5 w-5 text-outline" />
              </div>
              <input
                type="text"
                required
                v-model="username"
                class="block w-full pl-10 pr-3 py-3 border border-outline-variant rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] sm:text-sm transition-all bg-[var(--color-surface-container-lowest)] outline-none"
                placeholder="nombre.apellido"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-on-surface-variant mb-2">
              Contraseña
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <LockClosedIcon class="h-5 w-5 text-outline" />
              </div>
              <input
                type="password"
                required
                v-model="password"
                class="block w-full pl-10 pr-3 py-3 border border-outline-variant rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] sm:text-sm transition-all bg-[var(--color-surface-container-lowest)] outline-none"
                placeholder="••••••••"
              />
            </div>
          </div>



          <div v-if="errorMsg" class="p-3 bg-error-container/30 text-[var(--color-error)] text-sm rounded-xl border border-[var(--color-error)]/20 text-center font-medium">
            {{ errorMsg }}
          </div>

          <div>
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-container)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] transition-all duration-200 disabled:opacity-70"
            >
              <span v-if="isLoading">Cargando...</span>
              <span v-else class="flex items-center gap-2">Iniciar Sesión <ArrowRightIcon class="w-4 h-4" /></span>
            </button>
          </div>
        </form>

        <div class="mt-8 text-center text-xs text-outline">
          <p>Tip: Usa 'admin' para Administrador, otro para Técnico</p>
        </div>
      </div>
    </div>

    <!-- Right Side - Image/Branding -->
    <div class="hidden lg:flex flex-1 bg-[var(--color-primary)] relative overflow-hidden">
      <!-- Decoratives are kept simple, using custom css logic from original with class mappings -->
      <div class="absolute inset-0 bg-gradient-to-br from-[var(--color-primary)] to-[var(--color-primary-container)] opacity-90"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-[var(--color-secondary)] rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
      <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-[var(--color-primary-fixed)] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse" style="animation-delay: 2s"></div>
      
      <div class="relative z-10 flex flex-col justify-center items-start p-20 text-white max-w-2xl">
        <h1 class="text-5xl font-sans font-bold tracking-tight mb-6 leading-tight">
          Gestión de Activos <br/>
          <span class="text-[var(--color-secondary-container)]">Inteligente y Segura</span>
        </h1>
        <p class="text-lg text-[var(--color-primary-fixed-dim)] font-body leading-relaxed mb-12">
          Control total sobre el inventario, locaciones y movimientos. 
          Diseñado para la eficiencia operativa y la trazabilidad absoluta.
        </p>
        
        <div class="grid grid-cols-2 gap-8 w-full">
          <div class="glass-panel bg-white/10 border-white/10 p-6 rounded-2xl">
            <div class="text-3xl font-bold text-emerald-700 mb-2">99.9%</div>
            <div class="text-sm text-slate-800 font-medium">Precisión de Inventario</div>
          </div>
          <div class="glass-panel bg-white/10 border-white/10 p-6 rounded-2xl">
            <div class="text-3xl font-bold text-emerald-700 mb-2">+5k</div>
            <div class="text-sm text-slate-800 font-medium">Activos Gestionados</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { UserIcon, LockClosedIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';
import api from '../services/api';

const router = useRouter();
const username = ref('');
const password = ref('');
const isLoading = ref(false);
const errorMsg = ref('');

const handleSubmit = async () => {
  if (!username.value || !password.value) return;
  
  isLoading.value = true;
  errorMsg.value = '';

  try {
    const res = await api.post('/auth/login', {
      username: username.value,
      password: password.value
    });
    
    const { token, user } = res.data.data;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    
    if (user.role === 'admin') {
      router.push('/admin');
    } else {
      router.push('/tech');
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.error || 'Credenciales inválidas. Intente nuevamente.';
  } finally {
    isLoading.value = false;
  }
};
</script>
