import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    // La app vive en https://m.nubix.gt/power/ (servidor/power). El .htaccess de public/ usa la misma base.
    base: '/power/',
    plugins: [vue()],
})
