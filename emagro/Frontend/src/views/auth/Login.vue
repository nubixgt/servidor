<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-[#f0f4f1]">
    <!-- Background Image & Overlay -->
    <div class="absolute inset-0 z-0">
      <img
        alt="Campo agrícola verde difuminado"
        class="w-full h-full object-cover opacity-20"
        src="https://images.unsplash.com/photo-1625246333195-58f21a408738?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
      />
      <div class="absolute inset-0 bg-gradient-to-br from-green-100/40 to-[#43a047]/10 backdrop-blur-[2px]"></div>
    </div>

    <!-- Decorative Blurs -->
    <div class="absolute top-[-50px] right-[-50px] w-64 h-64 bg-[#43a047]/20 rounded-full blur-3xl z-0 pointer-events-none"></div>
    <div class="absolute bottom-[-50px] left-[-50px] w-64 h-64 bg-[#43a047]/20 rounded-full blur-3xl z-0 pointer-events-none"></div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md p-8 bg-white rounded-2xl shadow-2xl border border-gray-100 transition-all duration-300">
      <div class="flex flex-col items-center mb-8">
        <div class="flex items-center justify-center mb-4">
          <div class="relative flex items-center">
            <svg class="w-10 h-10 text-[#43a047] mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 7.03,11.5 8.5,13.5C10.45,11.5 13,9.5 17,8Z" />
            </svg>
            <div class="flex flex-col items-start leading-none">
              <h1 class="text-3xl font-bold text-gray-800 tracking-tight">EMAGRO</h1>
              <span class="text-[0.6rem] text-[#43a047] font-semibold tracking-widest uppercase">
                Biotecnología para el Desarrollo
              </span>
            </div>
            <div class="absolute -top-2 -right-6 flex space-x-1">
              <div class="w-2 h-2 rounded-full bg-[#43a047] opacity-60"></div>
              <div class="w-3 h-3 rounded-full bg-[#43a047] opacity-80"></div>
              <div class="w-2 h-2 rounded-full bg-[#43a047] opacity-60"></div>
            </div>
          </div>
        </div>
        <h2 class="text-xl font-medium text-gray-600">Bienvenido de nuevo</h2>
        <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="username">
            Usuario
          </label>
          <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <input
              v-model="usuario"
              class="focus:ring-[#43a047] focus:border-[#43a047] block w-full pl-10 pr-3 py-3 sm:text-sm border border-gray-300 rounded-xl bg-white text-gray-900 placeholder-gray-400 outline-none transition-colors"
              id="usuario"
              name="usuario"
              placeholder="nombre.apellido"
              required
              type="text"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="password">
            Contraseña
          </label>
          <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <input
              v-model="contrasena"
              class="focus:ring-[#43a047] focus:border-[#43a047] block w-full pl-10 pr-10 py-3 sm:text-sm border border-gray-300 rounded-xl bg-white text-gray-900 placeholder-gray-400 outline-none transition-colors"
              id="contrasena"
              name="contrasena"
              placeholder="••••••••"
              required
              :type="showPassword ? 'text' : 'password'"
            />
            <div 
              class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 transition-colors"
              @click="showPassword = !showPassword"
            >
              <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                <line x1="2" y1="2" x2="22" y2="22"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              class="h-4 w-4 text-[#43a047] focus:ring-[#43a047] border-gray-300 rounded cursor-pointer"
              id="remember-me"
              name="remember-me"
              type="checkbox"
            />
            <label class="ml-2 block text-sm text-gray-700 cursor-pointer" for="remember-me">
              Recordarme
            </label>
          </div>
          <div class="text-sm">
            <a class="font-medium text-[#43a047] hover:text-[#2e7d32] transition-colors" href="#">
              ¿Olvidaste tu contraseña?
            </a>
          </div>
        </div>

        <div>
          <button
            :disabled="isLoading"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-medium text-white bg-[#43a047] hover:bg-[#2e7d32] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#43a047] transition-all duration-200 disabled:opacity-70"
            type="submit"
          >
            <span v-if="!isLoading">Ingresar</span>
            <svg v-else class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="errorMsg" class="p-3 mt-4 text-sm text-red-700 bg-red-100 rounded-lg">
          {{ errorMsg }}
        </div>
      </form>

      <div class="mt-8 text-center text-xs text-gray-500">
        <p>© 2026 EMAGRO Biotecnología. Todos los derechos reservados.</p>
        <div class="mt-2 flex justify-center space-x-4">
          <a class="hover:text-[#43a047] transition-colors" href="#">Privacidad</a>
          <span>•</span>
          <a class="hover:text-[#43a047] transition-colors" href="#">Términos</a>
          <span>•</span>
          <a class="hover:text-[#43a047] transition-colors" href="#">Soporte</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';

const usuario = ref('');
const contrasena = ref('');
const showPassword = ref(false);
const isLoading = ref(false);
const errorMsg = ref('');
const router = useRouter();

const handleLogin = async () => {
  isLoading.value = true;
  errorMsg.value = '';
  try {
    const response = await api.post('/login', {
      usuario: usuario.value,
      contrasena: contrasena.value
    });
    
    if (response.data.status === 'success') {
      localStorage.setItem('token', response.data.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.data.user));
      router.push('/dashboard');
    }
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Error de conexión con el servidor.';
  } finally {
    isLoading.value = false;
  }
};
</script>
