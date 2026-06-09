import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    base: '/horus/',
    server: {
        proxy: {
            '/horus/Backend': {
                target: 'http://m.nubix.gt',
                changeOrigin: true
            }
        }
    }
})
