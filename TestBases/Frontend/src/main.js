import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import { registrarCierreAutomatico } from './stores/auth'
import './style.css'
import App from './App.vue'

const app = createApp(App)

app.use(createPinia())
registrarCierreAutomatico(router)
app.use(router)

app.mount('#app')
