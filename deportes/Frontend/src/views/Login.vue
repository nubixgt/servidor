<template>
  <div class="bg-background text-on-surface min-h-screen flex flex-col font-body-md overflow-x-hidden selection:bg-primary-fixed selection:text-on-primary-fixed relative">
    <!-- Background Atmospheric Image -->
    <div class="absolute inset-0 z-0">
      <img :src="fondoEstadio" class="absolute inset-0 w-full h-full object-cover opacity-30" alt="Background" />
      <div class="absolute inset-0 bg-gradient-to-t from-background via-background/80 to-transparent"></div>
    </div>

    <!-- Main Content Container -->
    <main class="flex-grow flex items-center justify-center p-container-margin z-10 relative">
      <div class="w-full max-w-md">
        <router-link to="/" class="inline-flex items-center gap-1 text-on-surface-variant hover:text-primary-fixed mb-stack-md transition-colors font-label-sm text-label-sm uppercase">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span>
          Volver al inicio
        </router-link>

        <!-- Login Card -->
        <div class="glass-panel w-full rounded-xl p-stack-md flex flex-col gap-stack-lg shadow-2xl">
          <!-- Header Section -->
          <div class="text-center flex flex-col gap-base">
            <h1 class="font-display-lg text-headline-lg-mobile md:text-display-lg text-primary-fixed uppercase tracking-tighter">DEPORTES</h1>
            <p class="text-on-surface-variant font-body-md text-body-md">Acceso Administrativo</p>
          </div>

          <!-- Login Form -->
          <form @submit.prevent="handleLogin" class="flex flex-col gap-stack-md">
            <!-- Input Group: Usuario / DPI -->
            <div class="flex flex-col gap-base">
              <label class="font-label-sm text-label-sm text-on-surface uppercase" for="usuario">Usuario / DPI</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant" style="font-variation-settings: 'FILL' 0;">badge</span>
                <input
                  v-model="form.usuario"
                  id="usuario"
                  type="text"
                  minlength="3"
                  maxlength="50"
                  required
                  placeholder="Ingresa tu usuario (Ej: DPI o admin)"
                  class="w-full bg-[#1A1A1A] text-on-surface border-0 border-b border-white/10 rounded-t-DEFAULT pl-10 pr-3 py-3 focus:ring-0 input-glow transition-all duration-200 outline-none font-body-md"
                />
              </div>
            </div>

            <!-- Input Group: Password -->
            <div class="flex flex-col gap-base">
              <label class="font-label-sm text-label-sm text-on-surface uppercase" for="password">Contraseña</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant" style="font-variation-settings: 'FILL' 0;">lock</span>
                <input
                  v-model="form.password"
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  minlength="3"
                  maxlength="50"
                  required
                  placeholder="••••••••"
                  class="w-full bg-[#1A1A1A] text-on-surface border-0 border-b border-white/10 rounded-t-DEFAULT pl-10 pr-10 py-3 focus:ring-0 input-glow transition-all duration-200 outline-none font-body-md"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary-fixed transition-colors"
                >
                  <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div class="pt-2">
              <button
                type="submit"
                :disabled="isLoading"
                class="w-full bg-primary-fixed text-on-primary-fixed font-title-md text-title-md py-3 rounded-DEFAULT transition-all duration-200 btn-glow uppercase flex items-center justify-center gap-2 disabled:opacity-60"
              >
                <span v-if="isLoading" class="material-symbols-outlined animate-spin">sync</span>
                <span>{{ isLoading ? 'Verificando...' : 'Ingresar' }}</span>
                <span v-if="!isLoading" class="material-symbols-outlined">arrow_forward</span>
              </button>
            </div>
          </form>

          <!-- Support Footer -->
          <div class="text-center mt-auto pt-stack-sm border-t border-white/5">
            <p class="text-on-surface-variant font-label-sm text-label-sm">
              Plataforma institucional de gestión de fútbol amateur
            </p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import fondoEstadio from '../assets/fondo-estadio.jpg'
import { alertaExito, alertaError } from '../utils/alertas'

const router = useRouter()
const isLoading = ref(false)
const showPassword = ref(false)

const form = reactive({
  usuario: '',
  password: ''
})

const handleLogin = async () => {
  isLoading.value = true

  try {
    const response = await api.post('/login', {
      usuario: form.usuario,
      password: form.password
    })

    if (response.data && response.data.token) {
      localStorage.setItem('deportes_token', response.data.token)
      localStorage.setItem('deportes_equipo', JSON.stringify(response.data.equipo))
      localStorage.setItem('deportes_rol', response.data.rol)

      await alertaExito('¡Bienvenido!', 'Sesión iniciada correctamente.')

      if (response.data.rol === 'admin') {
        router.push('/admin')
      } else {
        router.push('/mi-equipo')
      }
    }
  } catch (err) {
    const mensaje = err.response
      ? 'Usuario o contraseña incorrectos.'
      : 'Error de conexión. Intenta de nuevo.'
    alertaError('No se pudo iniciar sesión', mensaje)
  } finally {
    isLoading.value = false
  }
}
</script>
