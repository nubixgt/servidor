import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import { useAuth } from './composables/useAuth'
import { usePanel } from './composables/usePanel'
import { alertaAdvertencia } from './utils/alertas'

const app = createApp(App)
app.use(router)
app.mount('#app')

// Tras un deploy, una pestaña abierta con la versión vieja puede pedir un archivo que ya no existe
// (por ejemplo al abrir el menú o cambiar de página): se recarga una sola vez para traer la versión nueva.
window.addEventListener('vite:preloadError', () => {
  if (sessionStorage.getItem('recarga-version')) return
  sessionStorage.setItem('recarga-version', '1')
  window.location.reload()
})
window.addEventListener('load', () => setTimeout(() => sessionStorage.removeItem('recarga-version'), 5000))

// El API respondió 401 (sesión vencida o inválida): cerrar sesión y volver al login.
window.addEventListener('sesion-expirada', () => {
  useAuth().logout()
  usePanel().reset()
  if (router.currentRoute.value.path !== '/login') {
    router.push('/login')
    alertaAdvertencia('Tu sesión expiró', 'Inicia sesión de nuevo para continuar.')
  }
})
