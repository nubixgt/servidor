import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import AOS from 'aos'
import 'aos/dist/aos.css'

const app = createApp(App)

// Inicializar AOS
AOS.init({
  duration: 800, // Duración de la animación en ms
  once: false, // Animación solo la primera vez que se hace scroll? (false para que repita)
  offset: 100, // Desplazamiento desde el trigger original
})

// Interceptar peticiones fetch globalmente para agregar el JWT
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    let [resource, config] = args;
    const token = localStorage.getItem('token');
    
    if (token) {
        config = config || {};
        // Manejar tanto Headers object como un plain object
        if (config.headers instanceof Headers) {
            config.headers.append('Authorization', `Bearer ${token}`);
        } else {
            config.headers = {
                ...config.headers,
                'Authorization': `Bearer ${token}`
            };
        }
    }
    
    return originalFetch(resource, config);
};

app.use(createPinia())
app.use(router)

app.mount('#app')
