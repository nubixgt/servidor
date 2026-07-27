<template>
  <div class="min-h-screen bg-[#0a0a0a] flex flex-col items-center justify-center p-4 relative overflow-hidden">
    <!-- Abstract Background -->
    <div class="absolute inset-0 z-0 bg-black">
      <!-- Image Background -->
      <img src="../assets/fondo-estadio.jpg" class="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-luminosity" alt="Background" />
      
      <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-[#121212]/90 via-[#0a0a0a]/80 to-[#050505]/95"></div>
      <div class="absolute top-1/4 right-1/4 w-96 h-96 bg-[#ccff00] rounded-full mix-blend-overlay filter blur-[120px] opacity-10"></div>
    </div>

    <div class="z-10 w-full max-w-md">
      <!-- Back button -->
      <router-link to="/" class="inline-flex items-center text-gray-400 hover:text-[#ccff00] mb-8 transition-colors">
        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
        Volver al inicio
      </router-link>

      <div class="bg-[#121212] border border-gray-800 rounded-2xl p-8 shadow-2xl">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-black text-white italic tracking-tight mb-2">INICIAR SESIÓN</h2>
          <p class="text-gray-400 text-sm">Ingresa con tus credenciales de encargado</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div v-if="error" class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm text-center">
            {{ error }}
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Usuario (DPI)</label>
            <input 
              v-model="form.usuario" 
              type="text" 
              class="w-full bg-[#1e1e1e] border border-gray-700 focus:border-[#ccff00] text-white p-3 rounded-lg outline-none text-sm transition-colors"
              placeholder="Ingresa el DPI sin espacios"
              required
            >
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Contraseña (Teléfono)</label>
            <input 
              v-model="form.password" 
              type="password" 
              class="w-full bg-[#1e1e1e] border border-gray-700 focus:border-[#ccff00] text-white p-3 rounded-lg outline-none text-sm transition-colors"
              placeholder="Ingresa el número de teléfono"
              required
            >
          </div>

          <button 
            type="submit" 
            class="w-full bg-[#ccff00] text-black font-bold py-3 px-6 rounded-lg flex justify-center items-center hover:bg-[#b3e600] transition-colors disabled:opacity-50"
            :disabled="isLoading"
          >
            <span v-if="isLoading">INICIANDO...</span>
            <span v-else>INGRESAR</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const isLoading = ref(false)
const error = ref('')

const form = reactive({
  usuario: '',
  password: ''
})

const handleLogin = async () => {
  isLoading.value = true
  error.value = ''
  
  try {
    const response = await api.post('/login', {
      usuario: form.usuario,
      password: form.password
    })
    
    if (response.data && response.data.token) {
      localStorage.setItem('deportes_token', response.data.token)
      localStorage.setItem('deportes_equipo', JSON.stringify(response.data.equipo))
      router.push('/mi-equipo')
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'Error de conexión. Intenta de nuevo.'
  } finally {
    isLoading.value = false
  }
}
</script>
