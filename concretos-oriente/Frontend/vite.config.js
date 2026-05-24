import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    base: '/concretos-oriente/',
    server: {
        proxy: {
            '/concretos-oriente/Backend': {
                target: 'http://m.nubix.gt',
                changeOrigin: true
            }
        }
    }
})
