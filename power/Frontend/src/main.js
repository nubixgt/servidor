import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './style.css'
import App from './App.vue'
import { instalar as anotarErroresDelNavegador } from './utils/erroresDelNavegador'
import { capturarCodigo } from './utils/invitacion'

// Quien llega con el enlace de otro atleta (`?ref=`): se guarda hasta que entra (Beta 3.0).
capturarCodigo()

const app = createApp(App)

// Los errores de la pantalla, para «Pedir ayuda» y el panel de salud (Beta 3.0, F3).
anotarErroresDelNavegador(app)

app.use(createPinia())
app.use(router)

app.mount('#app')
