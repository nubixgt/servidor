<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import { ShieldCheckIcon, ArrowUpRightIcon, EyeIcon, EyeOffIcon } from 'lucide-vue-next'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'

const usuario = ref('')
const clave = ref('')
const verClave = ref(false)
const mensaje = ref('')

const router = useRouter()
const { login } = useAuth()

// Sin backend: cualquier usuario y contraseña no vacíos entran (se conectará al API en una etapa posterior).
function entrar() {
  if (!usuario.value || !clave.value) {
    mensaje.value = 'Escribe tu usuario y contraseña.'
    return
  }
  login(usuario.value)
  router.push('/admin')
}
</script>

<template>
  <section class="login-layout page-enter">
    <div class="login-intro football-login">
      <FootballBackdrop scene="stadium" />
      <p class="eyebrow"><ShieldCheckIcon :size="19" class="inline mr-2 lime" /> ÁREA DE ADMINISTRACIÓN</p>
      <h1>EL EQUIPO<br />DETRÁS<br />DEL <span class="lime">JUEGO.</span></h1>
      <p>Gestiona tu liga desde un solo lugar. Equipos, jugadores, partidos y cada momento que hace la diferencia.</p>
    </div>

    <form class="login-panel panel" @submit.prevent="entrar">
      <p class="eyebrow lime">BIENVENIDO A PULSE</p>
      <h2>Inicia sesión</h2>
      <p>Acceso exclusivo para administradores de la liga.</p>

      <label>
        Usuario
        <input v-model.trim="usuario" type="text" required maxlength="190" autocomplete="username" placeholder="Tu usuario" />
      </label>
      <label>
        Contraseña
        <div class="password-wrap">
          <input v-model="clave" :type="verClave ? 'text' : 'password'" required maxlength="72" autocomplete="current-password" placeholder="Tu contraseña" />
          <button type="button" :aria-label="verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="verClave = !verClave">
            <EyeOffIcon v-if="verClave" :size="17" />
            <EyeIcon v-else :size="17" />
          </button>
        </div>
      </label>

      <p v-if="mensaje" class="error-text" role="alert">{{ mensaje }}</p>

      <button class="btn btn-primary">Entrar al panel <ArrowUpRightIcon :size="19" /></button>
      <p class="login-note">¿No tienes acceso? Solicita una cuenta a la persona responsable de la liga.</p>
      <RouterLink to="/" class="text-link">← Volver a la liga</RouterLink>
    </form>
  </section>
</template>
