<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const username = ref('');
const password = ref('');

const handleLogin = () => {
  const role = username.value.toLowerCase().trim();
  localStorage.setItem('userRole', role);

  if (role === 'admin') {
    router.push('/admin/dashboard');
  } else if (role === 'tecnico') {
    router.push('/tech/dashboard');
  } else {
    localStorage.setItem('userRole', 'admin');
    router.push('/admin/dashboard');
  }
};
</script>

<template>
  <div class="min-h-screen bg-surface flex items-center justify-center p-4 relative overflow-hidden font-body">
    <!-- Ethereal Background Elements -->
    <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] rounded-full bg-primary-fixed/30 blur-[120px] -z-10"></div>
    <div class="absolute bottom-[-20%] right-[-10%] w-[50%] h-[50%] rounded-full bg-secondary-fixed/20 blur-[100px] -z-10"></div>

    <div class="w-full max-w-[1000px] h-[600px] glass-card rounded-3xl shadow-ambient flex overflow-hidden relative z-10">
      
      <!-- Left Side: Branding & Visual -->
      <div class="hidden md:flex w-1/2 primary-gradient p-12 flex-col justify-between relative overflow-hidden text-white">
        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')] opacity-20 mix-blend-overlay object-cover"></div>
        <div class="relative z-10">
          <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-white/30">
            <span class="material-symbols-outlined text-4xl">pets</span>
          </div>
          <h1 class="font-headline font-extrabold text-4xl tracking-tight leading-tight mb-4">
            Protección y Bienestar Animal
          </h1>
          <p class="text-primary-fixed text-lg font-medium">
            Plataforma de gestión integral para el Ministerio de Agricultura, Ganadería y Alimentación.
          </p>
        </div>
        <div class="relative z-10">
          <p class="text-sm font-bold tracking-widest uppercase opacity-80">AppUBA v2.0</p>
        </div>
      </div>

      <!-- Right Side: Login Form -->
      <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center bg-white/50">
        <div class="max-w-sm mx-auto w-full">
          <div class="mb-8 text-center md:text-left">
            <h2 class="font-headline font-extrabold text-3xl text-on-surface mb-2">Bienvenido</h2>
            <p class="text-on-surface-variant">Ingresa tus credenciales para acceder al sistema.</p>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
              <label class="block text-xs font-bold text-outline uppercase tracking-widest mb-2">Usuario</label>
              <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">person</span>
                <input 
                  type="text" 
                  v-model="username"
                  placeholder="Ej: admin o tecnico" 
                  class="w-full bg-surface-container-lowest border border-outline-variant/50 rounded-xl py-3 pl-12 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50 transition-all"
                  required
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-outline uppercase tracking-widest mb-2">Contraseña</label>
              <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
                <input 
                  type="password" 
                  v-model="password"
                  placeholder="••••••••" 
                  class="w-full bg-surface-container-lowest border border-outline-variant/50 rounded-xl py-3 pl-12 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50 transition-all"
                  required
                />
                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors">
                  <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                </button>
              </div>
            </div>

            <button type="submit" class="w-full primary-gradient text-white font-bold py-3.5 rounded-xl shadow-md hover:shadow-lg hover:opacity-95 transition-all mt-8 flex items-center justify-center gap-2">
              Ingresar al Sistema
              <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
            </button>
          </form>

          <div class="mt-8 text-center">
            <p class="text-xs text-outline font-medium">
              Uso exclusivo del personal autorizado del MAGA.<br/>
              Todos los accesos son monitoreados.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
