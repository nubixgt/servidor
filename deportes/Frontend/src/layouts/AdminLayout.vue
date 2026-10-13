<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminSidebar from '../components/admin/AdminSidebar.vue'
import { useAuth } from '../composables/useAuth'
import { usePanel } from '../composables/usePanel'

const router = useRouter()
const { logout } = useAuth()
const { state, load, reset } = usePanel()

onMounted(load)

function salir() {
  logout()
  reset()
  router.push('/login')
}
</script>

<template>
  <div class="container">
    <div class="admin-shell">
      <AdminSidebar @salir="salir" />
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
</template>
