<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import { alertaError, toastExito } from '../../utils/alertas'
import { ShieldCheckIcon, ArrowUpRightIcon, EyeIcon, EyeOffIcon } from 'lucide-vue-next'
import FootballBackdrop from '../../components/pulse/FootballBackdrop.vue'

const usuario = ref('')
const clave = ref('')
const verClave = ref(false)

const router = useRouter()
const { login } = useAuth()
const enviando = ref(false)

async function entrar() {
  enviando.value = true
  try {
    const destino = await login(usuario.value, clave.value)
    await router.push(destino)
    toastExito('Inicio de sesión correcto')
  } catch (e) {
    alertaError('No se pudo iniciar sesión', e.message)
  } finally {
    enviando.value = false
  }
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
      <p>Acceso para administradores y encargados de equipo.</p>

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

      <button class="btn btn-primary" :disabled="enviando">{{ enviando ? 'Accediendo…' : 'Entrar al panel' }} <ArrowUpRightIcon :size="19" /></button>
      <p class="login-note">¿No tienes acceso? Solicita una cuenta a la persona responsable de la liga.</p>
      <RouterLink to="/" class="text-link">← Volver a la liga</RouterLink>
    </form>
  </section>
</template>
