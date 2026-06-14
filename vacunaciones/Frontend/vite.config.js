import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    base: '/vacunaciones/',
    server: {
        proxy: {
            '/vacunaciones/Backend': {
                target: 'http://m.nubix.gt',
                changeOrigin: true
            }
        }
    }
})
