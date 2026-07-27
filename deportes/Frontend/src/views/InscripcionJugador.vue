<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
      
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end">
        <div>
          <h2 class="text-primary font-bold text-xs tracking-widest mb-1 flex items-center gap-2 uppercase">
            <span class="w-8 h-px bg-primary block"></span> Atleta Élite
          </h2>
          <h1 class="text-5xl md:text-6xl font-black uppercase italic tracking-tighter leading-none">Registro de<br/><span class="text-primary">Jugador</span></h1>
        </div>
        <div class="mt-4 md:mt-0 max-w-xs text-right">
          <p class="text-[10px] text-gray-400 uppercase tracking-widest leading-relaxed">
            La precisión en los datos define el rendimiento en el campo. Ingrese la información oficial del atleta.
          </p>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="flex flex-col md:flex-row gap-8">
        
        <!-- Left Photo Area -->
        <div class="w-full md:w-80 shrink-0">
          <div class="relative h-[450px] bg-[#161616] border-2 border-dashed border-gray-700 flex flex-col items-center justify-center text-center overflow-hidden cursor-pointer group" @click="triggerFileInput">
            <div class="absolute top-4 left-4 z-20">
              <span class="bg-black/50 text-[10px] font-bold text-white px-2 py-1 tracking-widest">ID: TMP-001</span>
            </div>
            
            <img v-if="previewUrl" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover z-10 opacity-80" />
            
            <div class="relative z-20 transition-transform group-hover:scale-105">
              <div class="w-16 h-16 bg-primary/20 border-2 border-primary rounded-lg flex items-center justify-center text-primary mx-auto mb-4 backdrop-blur-sm">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
              </div>
              <h3 class="text-xl font-bold uppercase tracking-wider text-white">Subir Fotografía</h3>
              <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-2">Formato JPG o PNG • Máx 5MB</p>
            </div>
            
            <input type="file" ref="fileInput" class="hidden" accept="image/png, image/jpeg" @change="onFileChange" />
          </div>
          
          <div class="mt-4 border-l-2 border-primary pl-4 py-2 bg-panel">
            <div class="flex items-center gap-2 text-primary font-bold text-xs mb-1">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
              VALIDACIÓN DE DATOS
            </div>
            <p class="text-[10px] text-gray-400">El DPI será verificado contra la base de datos nacional para torneos oficiales.</p>
          </div>
        </div>
        
        <!-- Right Form Area -->
        <div class="flex-grow bg-panel p-8 md:p-10">
          
          <!-- Section 1 -->
          <div class="mb-10">
            <h3 class="text-2xl font-black italic uppercase tracking-wider mb-6 flex items-center gap-4">
              <span class="text-gray-600">01</span> Afiliación
            </h3>
            <div class="mb-6">
              <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Seleccionar Equipo</label>
              <div class="relative">
                <select v-model="form.equipo_id" class="w-full bg-[#252525] border border-transparent focus:border-primary text-white p-4 appearance-none rounded-none outline-none text-sm cursor-pointer" required>
                  <option value="" disabled selected>Elija un club...</option>
                  <option v-for="equipo in equipos" :key="equipo.id" :value="equipo.id">{{ equipo.nombre }}</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                  <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Section 2 -->
          <div class="mb-10">
            <h3 class="text-2xl font-black italic uppercase tracking-wider mb-6 flex items-center gap-4">
              <span class="text-gray-600">02</span> Información Personal
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Nombre completo del jugador</label>
                <input v-model="form.nombre" type="text" class="w-full bg-[#252525] border border-transparent focus:border-primary text-white p-4 rounded-none outline-none text-sm placeholder-gray-500" placeholder="Ej. Carlos Alberto Ruiz" required>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-300 uppercase mb-2">DPI / CUI</label>
                <input v-model="form.dpi" type="text" maxlength="13" class="w-full bg-[#252525] border border-transparent focus:border-primary text-white p-4 rounded-none outline-none text-sm placeholder-gray-500 tracking-widest" placeholder="0000 00000 0000" required>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Teléfono de contacto</label>
                <div class="flex">
                  <div class="bg-[#1e1e1e] border-r border-[#2a2a2a] px-4 py-4 text-sm text-gray-400 font-bold">+502</div>
                  <input v-model="form.telefono" type="text" maxlength="8" class="flex-grow bg-[#252525] border border-transparent focus:border-primary text-white p-4 rounded-none outline-none text-sm placeholder-gray-500" placeholder="5555 5555" required>
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-300 uppercase mb-2">Posición en el campo</label>
                <div class="flex bg-[#252525] h-full">
                  <button type="button" @click="form.posicion = 'DEF'" :class="{'bg-gray-700 text-white': form.posicion === 'DEF', 'text-gray-400 hover:text-white hover:bg-[#303030]': form.posicion !== 'DEF'}" class="flex-1 border-r border-[#1e1e1e] text-xs font-bold transition-colors">DEF</button>
                  <button type="button" @click="form.posicion = 'MED'" :class="{'bg-gray-700 text-white': form.posicion === 'MED', 'text-gray-400 hover:text-white hover:bg-[#303030]': form.posicion !== 'MED'}" class="flex-1 border-r border-[#1e1e1e] text-xs font-bold transition-colors">MED</button>
                  <button type="button" @click="form.posicion = 'DEL'" :class="{'bg-gray-700 text-white': form.posicion === 'DEL', 'text-gray-400 hover:text-white hover:bg-[#303030]': form.posicion !== 'DEL'}" class="flex-1 border-r border-[#1e1e1e] text-xs font-bold transition-colors">DEL</button>
                  <button type="button" @click="form.posicion = 'POR'" :class="{'bg-gray-700 text-white': form.posicion === 'POR', 'text-gray-400 hover:text-white hover:bg-[#303030]': form.posicion !== 'POR'}" class="flex-1 text-xs font-bold transition-colors">POR</button>
                </div>
              </div>
            </div>
            
          </div>
          
          <div v-if="error" class="mb-6 p-3 bg-red-900/30 border border-red-500 text-red-400 text-sm">
            {{ error }}
          </div>
          <div v-if="success" class="mb-6 p-3 bg-green-900/30 border border-green-500 text-green-400 text-sm">
            ¡Jugador registrado con éxito!
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-6 items-center mt-12 pt-8 border-t border-gray-800">
            <router-link to="/" class="text-xs font-bold text-gray-400 hover:text-white uppercase tracking-widest">
              Cancelar
            </router-link>
            <button type="submit" :disabled="isLoading" class="bg-primary hover:bg-[#aacc00] text-black font-bold py-4 px-10 text-sm uppercase flex items-center gap-3 disabled:opacity-50 transition-colors shadow-[0_0_15px_rgba(204,255,0,0.3)]">
              <span v-if="isLoading">Guardando...</span>
              <template v-else>
                Guardar Jugador
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
              </template>
            </button>
          </div>

        </div>
      </form>
      
      <!-- Bottom Stats row -->
      <div class="mt-8 grid grid-cols-3 gap-4">
        <div class="bg-panel p-4 flex flex-col items-center justify-center">
          <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest mb-1">Registrados Hoy</div>
          <div class="text-3xl font-black flex items-baseline gap-1">24 <span class="text-primary text-sm">↑</span></div>
        </div>
        <div class="bg-panel p-4 flex flex-col items-center justify-center border-l border-r border-gray-800">
          <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest mb-1">Cupos Disponibles</div>
          <div class="text-3xl font-black">156</div>
        </div>
        <div class="bg-panel p-4 flex flex-col items-center justify-center">
          <div class="text-[10px] text-gray-500 uppercase font-bold tracking-widest mb-1">Tiempo de Proceso</div>
          <div class="text-3xl font-black flex items-baseline gap-1">2.4 <span class="text-gray-400 text-sm">min</span></div>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

const router = useRouter()
const equipos = ref([])
const fileInput = ref(null)
const previewUrl = ref(null)
const isLoading = ref(false)
const error = ref('')
const success = ref(false)

const form = reactive({
  equipo_id: '',
  nombre: '',
  dpi: '',
  telefono: '',
  posicion: 'MED',
  foto: null
})

onMounted(async () => {
  try {
    const response = await api.get('/equipos')
    equipos.value = response.data
  } catch (err) {
    console.error('Error al cargar equipos', err)
  }
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
  
  isLoading.value = true
  error.value = ''
  success.value = false
  
  const formData = new FormData()
  formData.append('equipo_id', form.equipo_id)
  formData.append('nombre', form.nombre)
  formData.append('dpi', form.dpi.replace(/\s/g, ''))
  formData.append('telefono', form.telefono)
  formData.append('posicion', form.posicion)
  formData.append('foto', form.foto)

  try {
    await api.post('/jugadores', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    success.value = true
    setTimeout(() => {
      router.push('/listado')
    }, 2000)
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al guardar el jugador'
  } finally {
    isLoading.value = false
  }
}
</script>
