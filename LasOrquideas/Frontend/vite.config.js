import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    base: '/',
    server: {
        proxy: {
            '/LasOrquideas/Backend': {
                target: 'https://m.nubix.gt',
                changeOrigin: true,
                secure: true
            }
        }
    }
})
