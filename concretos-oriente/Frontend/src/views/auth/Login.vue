<template>
  <div class="min-h-screen bg-app-scenic flex flex-col items-center justify-center p-6 relative overflow-hidden">
    <!-- Decorative Glows -->
    <div class="absolute top-1/4 left-1/4 w-[600px] h-[600px] bg-primary/20 blur-[150px] rounded-full animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-tertiary/10 blur-[130px] rounded-full"></div>

    <transition name="scale-fade" appear>
      <div class="w-full max-w-[520px] glass-card rounded-[48px] p-12 relative z-10 border border-white/20 shadow-[0_32px_64px_-12px_rgba(0,0,0,0.5)] bg-slate-950/40 backdrop-blur-3xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="text-center mb-12">
          <div class="w-20 h-20 bg-primary/20 rounded-[28px] flex items-center justify-center mx-auto mb-6 shadow-xl border border-white/20">
            <WrenchScrewdriverIcon class="w-10 h-10 text-primary shadow-[0_0_15px_rgba(99,102,241,0.4)]" />
          </div>
          <h1 class="text-4xl font-black text-white tracking-tighter mb-1 uppercase italic">CONSTRUCTPRO</h1>
          <p class="text-white/40 text-[10px] font-bold tracking-[0.4em] uppercase">Gestión Empresarial</p>
        </div>

        <form class="space-y-8" @submit.prevent="handleSubmit">
          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 block ml-3">Usuario</label>
              <div class="relative group">
                <UserIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 group-focus-within:text-primary transition-colors" />
                <input 
                  type="text" 
                  v-model="username"
                  placeholder="admin, supervisor o tecnico"
                  class="w-full glass-input rounded-2xl py-5 pl-14 pr-6 focus:ring-2 focus:ring-primary/40 transition-all outline-none placeholder:text-white/10 font-medium"
                  required
                />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 block ml-3">Contraseña</label>
              <div class="relative group">
                <LockClosedIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 group-focus-within:text-primary transition-colors" />
                <input 
                  type="password" 
                  v-model="password"
                  placeholder="••••••••"
                  class="w-full glass-input rounded-2xl py-5 pl-14 pr-12 focus:ring-2 focus:ring-primary/40 transition-all outline-none placeholder:text-white/10 font-medium"
                  required
                />
                <button type="button" class="absolute right-5 top-1/2 -translate-y-1/2 text-white/20 hover:text-white transition-colors">
                  <EyeIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
            
            <p v-if="errorMessage" class="text-red-400 text-sm font-bold text-center mt-2">{{ errorMessage }}</p>
          </div>

          <button 
            type="submit"
            :disabled="isLoading"
            class="w-full glass-button-primary text-white py-5 rounded-2xl font-black text-lg flex items-center justify-center gap-3 shadow-[0_20px_40px_-5px_rgba(99,102,241,0.3)] hover:shadow-[0_20px_40px_-5px_rgba(99,102,241,0.5)] hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] active:translate-y-0 transition-all uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isLoading">Verificando...</span>
            <template v-else>
              Sincronizar Acceso
              <ArrowRightIcon class="w-6 h-6" />
            </template>
          </button>
        </form>

        <div class="mt-12 pt-8 border-t border-white/5">
          <!-- Botones de prueba eliminados a petición del usuario -->
        </div>
      </div>
    </transition>

    <transition name="fade-in" appear>
      <p class="mt-12 text-[10px] font-bold text-white/20 uppercase tracking-[0.3em] relative z-10" style="transition-delay: 0.5s">
        ConstructPro © 2024 • Asegurado por Enterprise Shield™
      </p>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import Swal from 'sweetalert2';
import { 
  WrenchScrewdriverIcon, UserIcon, LockClosedIcon, EyeIcon, ArrowRightIcon, 
  ShieldCheckIcon, UserGroupIcon, WrenchIcon, PlusIcon 
} from '@heroicons/vue/24/outline';

const username = ref("");
const password = ref("");
const errorMessage = ref("");
const isLoading = ref(false);
const router = useRouter();
const authStore = useAuthStore();

const autofill = (u, p) => {
  username.value = u;
  password.value = p;
  errorMessage.value = "";
};

const handleSubmit = async () => {
  errorMessage.value = "";
  isLoading.value = true;
  
  try {
    const data = await authStore.login(username.value, password.value);
    
    // Configuración de SweetAlert Toast
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      background: '#0f172a',
      color: '#fff'
    });

    Toast.fire({
      icon: 'success',
      title: `Bienvenido, ${data.user.nombre}`
    });
    
    // Redirect based on role
    if (data.user.rol === "tecnico") {
      router.push("/tech-machinery");
    } else {
      router.push("/dashboard");
    }
  } catch (error) {
    errorMessage.value = error.message;
    Swal.fire({
      background: '#0f172a', 
      color: '#fff', 
      icon: 'error', 
      title: '¡Error!',
      text: error.message || 'Usuario o contraseña incorrectos'
    });
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.scale-fade-enter-active {
  transition: all 0.5s ease;
}
.scale-fade-enter-from {
  opacity: 0;
  transform: scale(0.95);
}
.scale-fade-enter-to {
  opacity: 1;
  transform: scale(1);
}
.fade-in-enter-active {
  transition: opacity 0.5s ease;
}
.fade-in-enter-from {
  opacity: 0;
}
.fade-in-enter-to {
  opacity: 1;
}
</style>
