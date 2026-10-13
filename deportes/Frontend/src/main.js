import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import { useAuth } from './composables/useAuth'
import { usePanel } from './composables/usePanel'

const app = createApp(App)
app.use(router)
app.mount('#app')

// El API respondió 401 (sesión vencida o inválida): cerrar sesión y volver al login.
window.addEventListener('sesion-expirada', () => {
  useAuth().logout()
  usePanel().reset()
  if (router.currentRoute.value.path !== '/login') router.push('/login')
})
