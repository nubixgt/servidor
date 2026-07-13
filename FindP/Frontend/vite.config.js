import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig(({ command }) => {
    return {
        plugins: [vue()],
        base: command === 'build' ? '/FindP/Frontend/dist/' : '/',
        server: {
            proxy: {
                '/FindP/Backend': {
                    target: 'http://m.nubix.gt',
                    changeOrigin: true
                }
            }
        }
    }
})
