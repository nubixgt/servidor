import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  base: '/InteligenciaTerritorial/',
  server: {
    port: 5174
  }
})
