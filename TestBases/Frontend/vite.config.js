import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    // la app se publica en https://m.nubix.gt/TestBases/
    base: '/TestBases/',
    plugins: [vue()],
})
