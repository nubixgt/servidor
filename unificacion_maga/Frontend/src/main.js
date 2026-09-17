import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'

import VueApexCharts from 'vue3-apexcharts'

// Si el navegador tiene abierto un build anterior y el servidor ya publicó
// uno nuevo, los chunks precargados pueden apuntar a archivos que ya no
// existen. Vite dispara este evento en ese caso; recargamos una sola vez
// para obtener la versión actual.
window.addEventListener('vite:preloadError', () => {
    if (!sessionStorage.getItem('chunk-reload-attempted')) {
        sessionStorage.setItem('chunk-reload-attempted', '1');
        window.location.reload();
    }
});

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(VueApexCharts)

app.mount('#app')
