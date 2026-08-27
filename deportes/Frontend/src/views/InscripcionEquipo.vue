<template>
  <div class="min-h-screen bg-background font-body-md antialiased flex flex-col">
    <!-- TopNavBar -->
    <header class="bg-background/80 backdrop-blur-lg flex justify-between items-center px-container-margin py-4 w-full sticky top-0 z-50 border-b border-white/10">
      <router-link to="/" class="text-headline-lg-mobile font-display-lg text-primary-fixed tracking-tighter uppercase leading-none">DEPORTES</router-link>
      <router-link to="/" class="text-on-surface-variant hover:text-primary-fixed transition-colors text-label-sm font-label-sm uppercase tracking-wider flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Volver al inicio
      </router-link>
    </header>

    <main class="flex-1 flex items-center justify-center p-container-margin md:p-stack-lg">
      <div class="max-w-4xl w-full mx-auto glass-panel rounded-2xl shadow-2xl relative overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr] ">
          <!-- Left accent strip -->
          <div class="w-full md:w-14 bg-primary-fixed flex md:flex-col items-center justify-between py-6 px-4 md:px-0 shrink-0">
            <span class="material-symbols-outlined text-on-primary">shield</span>
            <div class="hidden md:block transform -rotate-90 text-on-primary font-bold tracking-widest text-label-sm font-label-sm whitespace-nowrap uppercase">
              Registro de Equipo {{ new Date().getFullYear() }}
            </div>
            <div class="w-px h-16 bg-on-primary/40 hidden md:block"></div>
          </div>

          <!-- Form Content -->
          <div class="p-container-margin md:p-stack-lg">
            <div class="mb-stack-md">
              <h2 class="text-primary-fixed font-label-sm text-label-sm tracking-widest mb-1 flex items-center gap-2 uppercase">
                Pro Performance <span class="w-8 h-px bg-white/20 block"></span>
              </h2>
              <h1 class="text-headline-lg-mobile md:text-headline-lg font-headline-lg-mobile md:font-headline-lg uppercase italic tracking-wide text-white">Inscripción Técnica</h1>
            </div>

            <form @submit.prevent="submitForm" class="space-y-stack-lg">
              <!-- Images Upload -->
              <div class="flex flex-col md:flex-row gap-gutter">
                <div>
                  <div class="upload-area relative w-32 h-32 rounded-lg flex flex-col items-center justify-center overflow-hidden cursor-pointer group" @click="triggerFileInput">
                    <img v-if="previewUrl" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover" />
                    <template v-else>
                      <span class="material-symbols-outlined text-3xl text-on-surface-variant group-hover:text-primary-fixed mb-1 transition-colors">add_photo_alternate</span>
                      <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-white transition-colors text-center px-2">Subir escudo</span>
                    </template>
                    <input type="file" ref="fileInput" class="hidden" accept="image/png, image/jpeg" @change="onFileChange" />
                  </div>
                  <h3 class="text-label-sm font-label-sm text-on-surface uppercase mt-2 mb-1 tracking-wider">Escudo del Equipo <span class="text-error">*</span></h3>
                  <p class="text-label-sm text-on-surface-variant">Formato PNG o JPG. Máximo 5MB.</p>
                </div>

                <div>
                  <div class="upload-area relative w-32 h-32 rounded-lg flex flex-col items-center justify-center overflow-hidden cursor-pointer group" @click="triggerFileInputRep">
                    <img v-if="previewUrlRep" :src="previewUrlRep" class="absolute inset-0 w-full h-full object-cover" />
                    <template v-else>
                      <span class="material-symbols-outlined text-3xl text-on-surface-variant group-hover:text-primary-fixed mb-1 transition-colors">person_add</span>
                      <span class="text-label-sm font-label-sm text-on-surface-variant group-hover:text-white transition-colors text-center px-2">Foto rep.</span>
                    </template>
                    <input type="file" ref="fileInputRep" class="hidden" accept="image/png, image/jpeg" @change="onFileChangeRep" />
                  </div>
                  <h3 class="text-label-sm font-label-sm text-on-surface uppercase mt-2 mb-1 tracking-wider">Foto del Representante</h3>
                  <p class="text-label-sm text-on-surface-variant">Opcional. Formato PNG o JPG.</p>
                </div>
              </div>

              <!-- Team Details -->
              <div class="glass-panel p-stack-md rounded-xl space-y-stack-md relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-background via-primary-fixed to-background opacity-50"></div>
                <h2 class="text-title-md font-title-md text-white flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary-fixed">shield</span>
                  Información del Equipo
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
                  <div class="flex flex-col gap-1">
                    <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Nombre del equipo <span class="text-error">*</span></label>
                    <input v-model="form.nombre" type="text" class="input-dark w-full px-0 py-3 text-body-md font-body-md focus:ring-0" placeholder="Ej. Los Halcones" required>
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Representante <span class="text-error">*</span></label>
                    <input v-model="form.representante" type="text" class="input-dark w-full px-0 py-3 text-body-md font-body-md focus:ring-0" placeholder="Nombre completo" required>
                  </div>
                </div>
              </div>

              <!-- Representative Details -->
              <div class="glass-panel p-stack-md rounded-xl space-y-stack-md relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-background via-surface-variant to-background opacity-50"></div>
                <h2 class="text-title-md font-title-md text-white flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary-fixed">badge</span>
                  Detalles de Contacto
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
                  <div class="flex flex-col gap-1">
                    <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Teléfono (+502) <span class="text-error">*</span></label>
                    <div class="flex items-end">
                      <span class="text-on-surface-variant mr-2 pb-3 border-b border-white/10">+502</span>
                      <input v-model="form.telefono" type="text" minlength="8" maxlength="8" pattern="[0-9]{8}" title="Debe contener exactamente 8 dígitos" class="input-dark w-full px-0 py-3 text-body-md font-body-md focus:ring-0" placeholder="00000000" required>
                    </div>
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">DPI / Identificación <span class="text-error">*</span></label>
                    <input v-model="form.dpi" type="text" minlength="13" maxlength="13" pattern="[0-9]{13}" title="Debe contener exactamente 13 dígitos" class="input-dark w-full px-0 py-3 text-body-md font-body-md focus:ring-0 tracking-widest" placeholder="13 dígitos sin espacios" required>
                  </div>
                </div>
              </div>

              <!-- Confirmation -->
              <div>
                <h3 class="text-primary-fixed font-label-sm text-label-sm tracking-widest mb-4 flex items-center gap-2 uppercase">
                  Confirmación Final <span class="w-16 h-px bg-white/20 block"></span>
                </h3>
                <div class="glass-panel rounded-lg p-4 flex items-start gap-4 cursor-pointer hover:border-white/30 transition-colors" @click="accepted = !accepted">
                  <div class="mt-1 w-5 h-5 rounded border border-outline flex items-center justify-center shrink-0" :class="{'bg-primary-fixed border-primary-fixed text-on-primary': accepted}">
                    <span v-if="accepted" class="material-symbols-outlined text-[16px] leading-none">check</span>
                  </div>
                  <p class="text-label-sm text-on-surface-variant">Acepto el reglamento institucional y el compromiso de fair play. Entiendo que los datos serán verificados con la institución oficial.</p>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-6 items-center pt-2">
                <router-link to="/" class="text-label-sm font-label-sm text-on-surface-variant hover:text-white uppercase tracking-widest">
                  Cancelar
                </router-link>
                <button type="submit" :disabled="!accepted || isLoading" class="btn-primary rounded-lg py-3 px-8 font-bold text-title-md font-title-md flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                  <span v-if="isLoading">Guardando...</span>
                  <template v-else>
                    Guardar Equipo
                    <span class="material-symbols-outlined">arrow_forward</span>
                  </template>
                </button>
              </div>

              <div v-if="error" class="p-3 bg-error-container/20 border border-error/50 text-error text-sm rounded-lg text-center">
                {{ error }}
              </div>
            </form>

            <!-- Success Credentials Card -->
            <div v-if="success" class="absolute inset-0 bg-background/95 backdrop-blur-md z-50 flex flex-col items-center justify-center p-stack-lg text-center">
              <div class="glass-panel glow-card p-stack-lg rounded-2xl flex flex-col items-center text-center max-w-sm w-full">
                <div class="w-16 h-16 rounded-full bg-primary-fixed/20 flex items-center justify-center mb-6">
                  <span class="material-symbols-outlined text-4xl text-primary-fixed">check_circle</span>
                </div>
                <h2 class="text-headline-lg-mobile font-headline-lg-mobile text-white uppercase tracking-wider mb-2">¡Equipo Registrado!</h2>
                <p class="text-on-surface-variant text-body-md font-body-md mb-8">Guarda estas credenciales. Las necesitarás para iniciar sesión y registrar a tus jugadores.</p>

                <div class="w-full bg-surface-container rounded-xl p-4 border border-white/5 space-y-4 mb-8 text-left">
                  <div>
                    <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider block mb-1">Usuario (DPI)</span>
                    <div class="text-white font-mono text-title-md bg-surface p-2 rounded">{{ credentials?.usuario }}</div>
                  </div>
                  <div>
                    <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider block mb-1">Contraseña (Teléfono)</span>
                    <div class="text-primary-fixed font-mono text-title-md bg-surface p-2 rounded">{{ credentials?.password }}</div>
                  </div>
                </div>

                <router-link to="/login" class="w-full btn-primary rounded-lg py-3 px-6 font-bold text-body-md font-body-md text-center uppercase">
                  Ir a Iniciar Sesión
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
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
const credentials = ref(null)

const fileInputRep = ref(null)
const previewUrlRep = ref(null)

const form = reactive({
  nombre: '',
  representante: '',
  telefono: '',
  dpi: '',
  foto: null,
  foto_representante: null
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

const triggerFileInputRep = () => {
  fileInputRep.value.click()
}

const onFileChangeRep = (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  if (file.size > 5 * 1024 * 1024) {
    error.value = 'La foto del representante supera el tamaño máximo de 5MB.'
    return
  }
  
  form.foto_representante = file
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrlRep.value = e.target.result
  }
  reader.readAsDataURL(file)
  error.value = ''
}

const submitForm = async () => {
  if (!form.foto) {
    error.value = 'Debes subir el escudo del equipo.'
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
  formData.append('representante', form.representante)
  formData.append('telefono', form.telefono)
  formData.append('dpi', form.dpi.replace(/\s/g, ''))
  formData.append('foto', form.foto)
  
  if (form.foto_representante) {
    formData.append('foto_representante', form.foto_representante)
  }

  try {
    const response = await api.post('/equipos', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    success.value = true
    credentials.value = response.data.credentials
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el equipo'
  } finally {
    isLoading.value = false
  }
}
</script>
