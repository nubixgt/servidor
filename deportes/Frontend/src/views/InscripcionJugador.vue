<template>
  <div class="min-h-screen bg-background text-on-background flex flex-col relative overflow-x-hidden">
    <!-- TopNavBar -->
    <header class="bg-background/80 backdrop-blur-lg flex justify-between items-center px-container-margin py-4 w-full sticky top-0 z-50 border-b border-white/10">
      <span class="text-headline-lg-mobile font-display-lg text-primary-fixed tracking-tighter uppercase leading-none">DEPORTES</span>
      <router-link to="/mi-equipo" class="text-on-surface-variant hover:text-primary-fixed transition-colors text-label-sm font-label-sm uppercase tracking-wider flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver a Mi Equipo
      </router-link>
    </header>

    <main class="flex-grow px-container-margin py-stack-md md:py-stack-lg max-w-2xl mx-auto w-full flex flex-col gap-stack-lg">
      <form @submit.prevent="submitForm" class="glass-panel rounded-xl p-6 md:p-8 flex flex-col gap-stack-md relative overflow-hidden">
        <!-- Background glow accent -->
        <div class="absolute -top-20 -right-20 w-40 h-40 bg-primary-fixed rounded-full blur-[100px] opacity-20 pointer-events-none"></div>

        <div class="mb-2">
          <h2 class="text-headline-lg font-headline-lg text-primary-fixed mb-2 uppercase">Registro de Jugador</h2>
          <p class="text-body-md font-body-md text-on-surface-variant">La precisión en los datos define el rendimiento en el campo. Ingresa la información oficial del atleta.</p>
        </div>

        <!-- Photo Upload -->
        <div class="flex flex-col gap-3 items-center mb-4">
          <div class="relative w-28 h-28 rounded-full bg-surface-container flex items-center justify-center border-2 border-dashed border-white/20 cursor-pointer hover:border-primary-fixed transition-colors overflow-hidden group" @click="triggerFileInput">
            <img v-if="previewUrl" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
            <span v-else class="material-symbols-outlined text-4xl text-on-surface-variant group-hover:text-primary-fixed transition-colors">add_a_photo</span>
            <input type="file" ref="fileInput" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" accept="image/png, image/jpeg" @change="onFileChange" />
          </div>
          <span class="text-label-sm font-label-sm text-on-surface-variant uppercase">Subir foto de perfil</span>
        </div>

        <!-- Form Fields -->
        <div class="flex flex-col gap-6">
          <div class="flex flex-col gap-2">
            <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Nombre completo del jugador</label>
            <input v-model="form.nombre" type="text" class="input-dark w-full py-3 px-2 text-body-md font-body-md" placeholder="Ej. Carlos Alberto Ruiz" required>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">DPI / CUI</label>
            <input v-model="form.dpi" type="text" minlength="13" maxlength="13" pattern="[0-9]{13}" title="Debe contener exactamente 13 dígitos" class="input-dark w-full py-3 px-2 text-body-md font-body-md tracking-widest" placeholder="13 dígitos sin espacios" required>
            <span class="text-[10px] text-on-tertiary-container mt-1">Formato de 13 dígitos.</span>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Teléfono de contacto</label>
            <div class="flex">
              <span class="bg-surface-container py-3 px-4 text-body-md font-body-md text-on-surface-variant border-b border-white/10 rounded-tl-md">+502</span>
              <input v-model="form.telefono" type="text" minlength="8" maxlength="8" pattern="[0-9]{8}" title="Debe contener exactamente 8 dígitos" class="input-dark w-full py-3 px-3 text-body-md font-body-md" placeholder="00000000" required>
            </div>
          </div>

          <!-- Position Selector -->
          <div class="flex flex-col gap-3 mt-2">
            <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Posición Principal</label>
            <div class="grid grid-cols-4 gap-3">
              <div class="chip rounded-full py-2 px-4 text-center text-label-sm font-label-sm" :class="{ active: form.posicion === 'Defensa' }" @click="form.posicion = 'Defensa'">DEF</div>
              <div class="chip rounded-full py-2 px-4 text-center text-label-sm font-label-sm" :class="{ active: form.posicion === 'Mediocampo' }" @click="form.posicion = 'Mediocampo'">MED</div>
              <div class="chip rounded-full py-2 px-4 text-center text-label-sm font-label-sm" :class="{ active: form.posicion === 'Delantero' }" @click="form.posicion = 'Delantero'">DEL</div>
              <div class="chip rounded-full py-2 px-4 text-center text-label-sm font-label-sm" :class="{ active: form.posicion === 'Portero' }" @click="form.posicion = 'Portero'">POR</div>
            </div>
          </div>

          <div v-if="error" class="p-3 bg-error-container/20 border border-error/50 text-error text-sm rounded-lg">
            {{ error }}
          </div>
          <div v-if="success" class="p-3 bg-primary-fixed/10 border border-primary-fixed/50 text-primary-fixed text-sm rounded-lg">
            ¡Jugador registrado con éxito!
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-6 items-center pt-4 border-t border-white/10">
            <router-link to="/mi-equipo" class="text-label-sm font-label-sm text-on-surface-variant hover:text-white uppercase tracking-widest">
              Cancelar
            </router-link>
            <button type="submit" :disabled="isLoading" class="bg-primary-fixed text-on-primary-fixed py-4 px-10 rounded-lg font-title-md text-title-md hover:bg-white hover:text-black transition-all duration-300 flex items-center justify-center gap-2 group shadow-[0_0_15px_rgba(185,246,63,0.3)] disabled:opacity-50">
              <span v-if="isLoading">Guardando...</span>
              <template v-else>
                <span>Guardar Jugador</span>
                <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">arrow_forward</span>
              </template>
            </button>
          </div>
        </div>
      </form>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

const router = useRouter()
const fileInput = ref(null)
const previewUrl = ref(null)
const isLoading = ref(false)
const error = ref('')
const success = ref(false)

const form = reactive({
  nombre: '',
  dpi: '',
  telefono: '',
  posicion: 'Mediocampo',
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
    error.value = 'Debes subir la foto del jugador.'
    return
  }
  
  if (form.telefono.length !== 8 || !/^\d{8}$/.test(form.telefono)) {
    error.value = 'El teléfono debe tener exactamente 8 dígitos numéricos.'
    return
  }

  const dpiClean = form.dpi.replace(/\s/g, '')
  if (dpiClean.length !== 13 || !/^\d{13}$/.test(dpiClean)) {
    error.value = 'El DPI debe tener exactamente 13 dígitos numéricos.'
    return
  }
  
  isLoading.value = true
  error.value = ''
  success.value = false
  
  const formData = new FormData()
  formData.append('nombre', form.nombre)
  formData.append('dpi', form.dpi.replace(/\s/g, ''))
  formData.append('telefono', form.telefono)
  formData.append('posicion', form.posicion)
  formData.append('foto', form.foto)

  try {
    const token = localStorage.getItem('deportes_token')
    await api.post('/jugadores', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${token}`
      }
    })
    success.value = true
    setTimeout(() => {
      router.push('/mi-equipo')
    }, 2000)
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el jugador'
  } finally {
    isLoading.value = false
  }
}
</script>
