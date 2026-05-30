<template>
  <div class="min-h-screen flex flex-col items-center justify-center py-12 px-6 md:px-8 bg-slate-50">
    <transition name="fade-up" appear>
      <div 
        class="w-full max-w-[420px] bg-white border border-[#cbd5e1] p-8 shadow-sm flex flex-col gap-6"
      >
        <header class="text-center flex flex-col items-center">
          <img src="@/assets/images/image.png" alt="Cooitzá Logo" class="h-24 w-auto mb-3 mx-auto object-contain" />
          <h1 class="font-display text-2xl font-bold text-[#0054A3] tracking-tight">
            Cooitzá Control
          </h1>
          <p class="font-mono-label text-xs text-slate-500 font-semibold tracking-wider mt-1 uppercase">
            Sistemas de Control Industrial
          </p>
        </header>

        <div v-if="errorMessage" class="bg-red-50 border-l-4 border-red-500 p-3 text-xs text-red-700 font-medium font-sans">
          {{ errorMessage }}
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
          <div class="flex flex-col gap-1">
            <label class="text-slate-600 block text-xs font-bold uppercase tracking-wider" for="username">
              Usuario
            </label>
            <div class="relative">
              <User class="absolute left-3 top-1/2 -translate-y-1/2 text-[#0054A3] w-5 h-5 pointer-events-none" />
              <input
                id="username"
                type="text"
                v-model="username"
                class="w-full pl-10 pr-3 py-2.5 border border-[#cbd5e1] focus:border-[#0054A3] outline-none font-sans text-sm focus:ring-1 focus:ring-[#FFD200]"
                placeholder="Ej: tecnico o admin"
                :disabled="isLoading"
              />
            </div>
          </div>

          <div class="flex flex-col gap-1">
            <div class="flex justify-between items-end">
              <label class="text-slate-600 block text-xs font-bold uppercase tracking-wider" for="password">
                Contraseña
              </label>
              <span class="font-display text-[10px] text-[#0054A3] hover:underline cursor-pointer">
                ¿Olvidaste tu clave?
              </span>
            </div>
            <div class="relative">
              <Lock class="absolute left-3 top-1/2 -translate-y-1/2 text-[#0054A3] w-5 h-5 pointer-events-none" />
              <input
                id="password"
                type="password"
                v-model="password"
                class="w-full pl-10 pr-3 py-2.5 border border-[#cbd5e1] focus:border-[#0054A3] outline-none font-sans text-sm focus:ring-1 focus:ring-[#FFD200]"
                placeholder="••••••••"
                :disabled="isLoading"
              />
            </div>
          </div>



          <button
            type="submit"
            :disabled="isLoading"
            class="w-full bg-[#0054A3] hover:bg-[#004586] disabled:bg-[#cbd5e1] text-white py-3 font-display text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 cursor-pointer transition-all active:scale-[0.98]"
          >
            <template v-if="isLoading">
              <Loader2 class="w-4 h-4 animate-spin" />
              <span>Autenticando...</span>
            </template>
            <template v-else>
              <span>Ingresar</span>
              <ArrowRight class="w-4 h-4" />
            </template>
          </button>
        </form>

        <!-- Access Help Card -->
        <div class="bg-slate-50 p-4 border border-[#cbd5e1] text-[11px] font-mono flex flex-col gap-1.5 text-slate-600">
          <span class="font-sans font-bold uppercase tracking-wider text-xs text-[#0054A3]">Credenciales de Base de Datos</span>
          <div><strong class="text-[#0054A3]">Admin:</strong> <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">admin</code> / <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">123</code></div>
          <div><strong class="text-[#0054A3]">Técnico Piloto:</strong> <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">piloto1</code> / <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">123</code></div>
          <div><strong class="text-[#0054A3]">Técnico Dashboard:</strong> <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">analista</code> / <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">123</code></div>
        </div>

        <footer class="pt-4 border-t border-[#cbd5e1] text-center flex flex-col gap-2">
          <p class="font-display text-[11px] text-slate-500 font-semibold tracking-wider uppercase">
            Protocolo de Acceso Seguro v4.2.0
          </p>
          <div class="flex justify-center gap-4">
            <span class="flex items-center gap-1 font-display text-[10px] text-slate-600 font-bold uppercase">
              <span class="w-2 h-2 rounded-full bg-[#4CAF50] animate-pulse"></span>
              Sistemas Online
            </span>
            <span class="flex items-center gap-1 font-display text-[10px] text-slate-600 font-bold uppercase">
              <Shield class="w-3.5 h-3.5 text-[#0054A3]" /> Cifrado SSL
            </span>
          </div>
        </footer>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { User, Lock, ArrowRight, Shield, Loader2 } from 'lucide-vue-next';

import { useRouter } from 'vue-router';

const router = useRouter();

const username = ref('');
const password = ref('');
const isLoading = ref(false);
const errorMessage = ref('');


const handleSubmit = async () => {
  errorMessage.value = '';

  if (!username.value.trim() || !password.value.trim()) {
    errorMessage.value = 'Por favor, complete todos los campos.';
    return;
  }

  isLoading.value = true;

  try {
    const response = await fetch('/maquinaria-cooitza/Backend/api/v1/usuarios/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        username: username.value.trim(),
        password: password.value.trim()
      })
    });

    const data = await response.json();

    if (!response.ok || !data.token) {
      errorMessage.value = data.message || "Credenciales incorrectas o error en el servidor.";
      return;
    }

    // Save token and user info
    localStorage.setItem('cooitza_token', data.token);
    localStorage.setItem('cooitza_user', JSON.stringify(data.user));

    const role = data.user.role;
    
    if (role === 'admin') {
      router.push('/admin');
    } else if (role === 'tecnico_dashboard') {
      router.push('/tecnico');
    } else if (role === 'tecnico_piloto') {
      router.push('/piloto');
    } else {
      router.push('/');
    }

  } catch (error) {
    console.error("Login error:", error);
    errorMessage.value = "Error de red. Verifique que el Backend de PHP esté funcionando.";
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.fade-up-enter-active,
.fade-up-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(15px);
}
</style>
