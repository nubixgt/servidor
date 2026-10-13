<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import EncargadoSidebar from '../components/admin/EncargadoSidebar.vue'
import { useAuth } from '../composables/useAuth'
import { usePanel } from '../composables/usePanel'
import VisorImagen from '../components/admin/VisorImagen.vue'
import { confirmarAccion, toastExito } from '../utils/alertas'

const router = useRouter()
const { logout } = useAuth()
const { state, load, reset } = usePanel()

onMounted(load)

async function salir() {
  const { isConfirmed } = await confirmarAccion({
    title: '¿Cerrar sesión?',
    text: 'Tendrás que iniciar sesión de nuevo para volver al panel.',
    confirmButtonText: 'Sí, cerrar sesión',
    icon: 'question',
  })
  if (!isConfirmed) return
  logout()
  reset()
  await router.push('/login')
  toastExito('Cerraste sesión correctamente')
}
</script>

<template>
  <div class="container">
    <div class="admin-shell">
      <EncargadoSidebar @salir="salir" />
      <section class="admin-content">
        <div v-if="state.error" class="error-banner" role="alert">
          <b>No pudimos cargar los datos.</b>
          <p>{{ state.error }}</p>
          <button class="btn btn-secondary" @click="load">Reintentar</button>
        </div>
        <div v-else-if="!state.loaded" class="loading-state"><div class="loader"></div>Cargando…</div>
        <RouterView v-else />
      </section>
    </div>
  </div>
  <VisorImagen />
</template>
