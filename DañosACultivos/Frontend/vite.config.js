import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// La ruta pública es m.nubix.gt/DañosACultivos. La ñ va codificada (%C3%B1) porque
// es como la envía el navegador; así el router y los assets coinciden con location.pathname.
export default defineConfig({
    plugins: [vue()],
    base: '/Da%C3%B1osACultivos/',
    server: {
        proxy: {
            '/Da%C3%B1osACultivos/Backend': {
                target: 'https://m.nubix.gt',
                changeOrigin: true,
                secure: true
            }
        }
    }
})
