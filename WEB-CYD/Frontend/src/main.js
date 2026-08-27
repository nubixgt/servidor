import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import { initCursorSpotlight, initSectionReveal } from '@/lib/effects.js'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')

// Inicializar efectos premium después del montaje
setTimeout(() => {
  initCursorSpotlight()
  initSectionReveal()
}, 500)
