<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto flex flex-col md:flex-row gap-8 bg-panel min-h-[600px] shadow-2xl relative">
      
      <!-- Left Sidebar (Lime Green) -->
      <div class="w-full md:w-16 bg-primary flex md:flex-col items-center justify-between py-6 px-4 md:px-0 z-10 shrink-0">
        <div class="w-8 h-8 rounded-full border border-black flex items-center justify-center text-black">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        </div>
        
        <div class="hidden md:block transform -rotate-90 text-black font-bold tracking-widest text-xs whitespace-nowrap uppercase">
          REGISTRO DE EQUIPO {{ new Date().getFullYear() }}
        </div>
        
        <div class="w-px h-16 bg-black hidden md:block"></div>
      </div>
      
      <!-- Form Content -->
      <div class="flex-grow p-8 md:p-12 relative z-10">
        <div class="mb-8">
          <h2 class="text-primary font-bold text-xs tracking-widest mb-1 flex items-center gap-2 uppercase">
            Pro Performance <span class="w-8 h-px bg-gray-700 block"></span>
          </h2>
          <h1 class="text-2xl font-black uppercase italic tracking-wide">Inscripción Técnica</h1>
        </div>
        
        <form @submit.prevent="submitForm">
          <!-- Image Upload -->
          <div class="mb-8 relative w-32">
            <div class="w-32 h-32 border border-dashed border-gray-600 rounded-lg flex flex-col items-center justify-center text-gray-500 overflow-hidden relative cursor-pointer" @click="triggerFileInput">
              <img v-if="previewUrl" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
              <svg v-else class="w-10 h-10 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
              
              <input type="file" ref="fileInput" class="hidden" accept="image/png, image/jpeg" @change="onFileChange" />
            </div>
            
            <button type="button" class="absolute -bottom-3 -right-3 w-8 h-8 bg-primary text-black rounded-full flex items-center justify-center hover:bg-[#aacc00] transition-colors" @click="triggerFileInput">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </button>
          </div>
          
          <div class="mb-8">
            <h3 class="text-xs font-bold text-gray-300 uppercase mb-1">Escudo del Equipo</h3>
            <p class="text-xs text-gray-500">Formato PNG o JPG. Máximo 5MB.</p>
          </div>

          <!-- Form Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
              <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Nombre del equipo <span class="text-primary">*</span></label>
              <input v-model="form.nombre" type="text" class="w-full bg-[#161616] border border-transparent focus:border-primary text-white p-3 rounded-none outline-none text-sm placeholder-gray-600" placeholder="Ej. Los Halcones" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Representante <span class="text-primary">*</span></label>
              <input v-model="form.representante" type="text" class="w-full bg-[#161616] border border-transparent focus:border-primary text-white p-3 rounded-none outline-none text-sm placeholder-gray-600" placeholder="Nombre completo" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Teléfono <span class="text-primary">*</span></label>
              <div class="flex">
                <div class="bg-[#121212] border-r border-[#1e1e1e] px-4 py-3 text-sm text-gray-400 font-bold">+502</div>
                <input v-model="form.telefono" type="text" class="flex-grow bg-[#161616] border border-transparent focus:border-primary text-white p-3 rounded-none outline-none text-sm placeholder-gray-600" placeholder="0000 0000" required>
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-300 uppercase mb-2">DPI / Identificación <span class="text-primary">*</span></label>
              <input v-model="form.dpi" type="text" class="w-full bg-[#161616] border border-transparent focus:border-primary text-white p-3 rounded-none outline-none text-sm placeholder-gray-600 tracking-widest" placeholder="#### ##### ####" required>
            </div>
          </div>
          
          <div class="mb-8">
            <h3 class="text-primary font-bold text-xs tracking-widest mb-4 flex items-center gap-2 uppercase">
              Confirmación Final <span class="w-16 h-px bg-gray-700 block"></span>
            </h3>
            <div class="bg-[#161616] p-4 flex items-start gap-4 border border-transparent hover:border-gray-800 transition-colors cursor-pointer" @click="accepted = !accepted">
              <div class="mt-1 w-5 h-5 border border-gray-600 flex items-center justify-center shrink-0" :class="{'bg-primary border-primary text-black': accepted}">
                <svg v-if="accepted" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
              <p class="text-xs text-gray-400">Acepto el reglamento institucional y el compromiso de fair play. Entiendo que los datos serán verificados con la institución oficial.</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-6 items-center">
            <router-link to="/" class="text-xs font-bold text-gray-400 hover:text-white uppercase tracking-widest">
              Cancelar
            </router-link>
            <button type="submit" :disabled="!accepted || isLoading" class="bg-primary hover:bg-[#aacc00] text-black font-bold py-3 px-8 text-sm uppercase flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
              <span v-if="isLoading">Guardando...</span>
              <template v-else>
                Guardar Equipo
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </template>
            </button>
          </div>
          
          <div v-if="error" class="mt-4 p-3 bg-red-900/30 border border-red-500 text-red-400 text-sm">
            {{ error }}
          </div>
          <div v-if="success" class="mt-4 p-3 bg-green-900/30 border border-green-500 text-green-400 text-sm">
            ¡Equipo registrado con éxito!
          </div>

        </form>
      </div>
      
      <!-- Right Decorative Info -->
      <div class="hidden md:flex flex-col justify-center items-center p-8 border-l border-gray-800 shrink-0 min-w-[120px]">
        <div class="text-center mb-16">
          <div class="text-gray-500 text-[10px] uppercase font-bold tracking-widest mb-4">Latency</div>
          <div class="w-px h-12 bg-primary/30 mx-auto mb-4"></div>
          <div class="text-primary font-bold text-xs">12ms</div>
        </div>
        <div class="text-center">
          <div class="text-gray-500 text-[10px] uppercase font-bold tracking-widest mb-4">Secure</div>
          <svg class="w-5 h-5 text-primary mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

const router = useRouter()
const fileInput = ref(null)
const previewUrl = ref(null)
const accepted = ref(false)
const isLoading = ref(false)
const error = ref('')
const success = ref(false)

const form = reactive({
  nombre: '',
  representante: '',
  telefono: '',
  dpi: '',
  foto: null
})

const triggerFileInput = () => {
  fileInput.value.click()
}

const onFileChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  if (file.size > 5 * 1024 * 1024) {
    error.value = 'El archivo supera el tamaño máximo de 5MB.'
    return
  }
  
  form.foto = file
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target.result
  }
  reader.readAsDataURL(file)
  error.value = ''
}

const submitForm = async () => {
  if (!form.foto) {
    error.value = 'Debes subir el escudo del equipo.'
    return
  }
  
  isLoading.value = true
  error.value = ''
  success.value = false
  
  const formData = new FormData()
  formData.append('nombre', form.nombre)
  formData.append('representante', form.representante)
  formData.append('telefono', form.telefono)
  formData.append('dpi', form.dpi.replace(/\s/g, ''))
  formData.append('foto', form.foto)

  try {
    const response = await api.post('/equipos', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    success.value = true
    setTimeout(() => {
      router.push('/listado')
    }, 2000)
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el equipo'
  } finally {
    isLoading.value = false
  }
}
</script>
