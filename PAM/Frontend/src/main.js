import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import { hooks } from './services/api'
import { useAuthStore } from './stores/auth'

// Tema claro/oscuro guardado por el usuario (se aplica antes de pintar)
try {
    const tema = localStorage.getItem('pam-tema');
    if (tema) document.documentElement.dataset.theme = tema;
} catch (e) { /* sin almacenamiento */ }

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Si la renovación de la sesión falla, se vuelve al login
hooks.alExpirar = () => {
    useAuthStore().expirar();
    router.push('/login');
}

app.mount('#app')
