<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminSidebar from '../components/admin/AdminSidebar.vue'

import { useAuth } from '../composables/useAuth'
import { usePulse } from '../composables/usePulse'

const router = useRouter()
const { logout } = useAuth()
const { state, load } = usePulse()

onMounted(load)

function salir() {
  logout()
  router.push('/login')
}
</script>

<template>
  <div class="container">
    <div class="admin-shell">
      <AdminSidebar @salir="salir" />
      <section class="admin-content"><RouterView v-if="state.loaded" /></section>
    </div>
  </div>
</template>
