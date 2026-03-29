<template>
  <div class="max-w-2xl mx-auto px-4 py-0">
    <!-- Header con imagen de fondo -->
    <div
      class="relative rounded-b-3xl overflow-hidden mb-8"
      :style="`background-image: url('${base}images/BannerFormulario2.png'); background-size: cover; background-position: top;`"
    >
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="relative z-10 px-6 pt-8 pb-6">
        <div class="flex items-center gap-2 mb-6">
          <router-link to="/" class="text-white">
            <span class="material-symbols-outlined">arrow_back</span>
          </router-link>
          <h1 class="text-white text-xl font-extrabold">Nueva Denuncia</h1>
        </div>
        <!-- Progress steps -->
        <div class="flex justify-between mb-3">
          <div
            v-for="n in 4"
            :key="n"
            class="flex-1 mx-1"
          >
            <div class="flex items-center gap-1">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 border-2 transition-all"
                :class="n < paso ? 'bg-white text-[#0f2a4a] border-white' : n === paso ? 'bg-[#0f2a4a] text-white border-white' : 'bg-white/20 text-white border-white/40'"
              >
                <span v-if="n < paso" class="material-symbols-outlined text-[16px]">check</span>
                <span v-else>{{ n }}</span>
              </div>
              <div v-if="n < 4" class="flex-1 h-0.5 rounded" :class="n < paso ? 'bg-white' : 'bg-white/30'"></div>
            </div>
          </div>
        </div>
        <p class="text-white font-bold text-lg">{{ stepTitles[paso - 1] }}</p>
      </div>
    </div>

    <!-- Contenido del paso -->
    <div class="px-4 pb-10">
      <!-- PASO 1: Información Personal -->
      <div v-if="paso === 1" class="flex flex-col gap-4">
        <div>
          <label class="label">Nombre completo <span class="text-red-500">*</span></label>
          <input v-model="form.nombre" type="text" class="input" placeholder="Juan Pérez García" />
        </div>
        <div>
          <label class="label">DPI (13 dígitos) <span class="text-red-500">*</span></label>
          <input v-model="form.dpi" type="text" maxlength="15" class="input" placeholder="0000 00000 0000" @input="formatDpi" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Edad</label>
            <input v-model="form.edad" type="number" min="0" max="120" class="input" placeholder="25" />
          </div>
          <div>
            <label class="label">Género</label>
            <select v-model="form.genero" class="input">
              <option value="">Seleccionar</option>
              <option>Masculino</option>
              <option>Femenino</option>
              <option>Prefiero no decirlo</option>
            </select>
          </div>
        </div>
        <div>
          <label class="label">Celular</label>
          <input v-model="form.celular" type="tel" class="input" placeholder="+502 0000 0000" />
        </div>
        <div>
          <label class="label">Correo electrónico</label>
          <input v-model="form.correo" type="email" class="input" placeholder="correo@ejemplo.com" />
        </div>
      </div>

      <!-- PASO 2: Ubicación del Hecho -->
      <div v-if="paso === 2" class="flex flex-col gap-4">
        <div>
          <label class="label">Nombre del responsable</label>
          <input v-model="form.responsable" type="text" class="input" placeholder="Nombre del responsable del hecho" />
        </div>
        <div>
          <label class="label">Dirección del hecho <span class="text-red-500">*</span></label>
          <input v-model="form.direccion" type="text" class="input" placeholder="Zona 1, calle..." />
        </div>
        <div>
          <label class="label">Departamento <span class="text-red-500">*</span></label>
          <select v-model="form.departamento" @change="onDepartamentoChange" class="input">
            <option value="">Seleccionar departamento</option>
            <option v-for="d in departamentos" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>
        <div>
          <label class="label">Municipio <span class="text-red-500">*</span></label>
          <select v-model="form.municipio" class="input" :disabled="!form.departamento">
            <option value="">Seleccionar municipio</option>
            <option v-for="m in municipiosDisponibles" :key="m" :value="m">{{ m }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Color de la casa</label>
            <input v-model="form.colorCasa" type="text" class="input" placeholder="Amarillo" />
          </div>
          <div>
            <label class="label">Color de la puerta</label>
            <input v-model="form.colorPuerta" type="text" class="input" placeholder="Rojo" />
          </div>
        </div>
        <!-- Coordenadas manuales (sin Maps) -->
        <div class="bg-[#f2f4f6] rounded-2xl p-4">
          <p class="font-bold text-sm text-[#0f2a4a] mb-3">
            <span class="material-symbols-outlined text-[16px] align-middle mr-1">location_on</span>
            Coordenadas (opcional)
          </p>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="label text-xs">Latitud</label>
              <input v-model="form.latitud" type="number" step="any" class="input text-sm" placeholder="14.6349" />
            </div>
            <div>
              <label class="label text-xs">Longitud</label>
              <input v-model="form.longitud" type="number" step="any" class="input text-sm" placeholder="-90.5069" />
            </div>
          </div>
        </div>
      </div>

      <!-- PASO 3: Detalles del Animal -->
      <div v-if="paso === 3" class="flex flex-col gap-4">
        <div>
          <label class="label">Especie <span class="text-red-500">*</span></label>
          <div class="grid grid-cols-4 gap-2 mt-1">
            <button
              v-for="esp in especies" :key="esp.label"
              @click="form.especie = esp.label"
              class="flex flex-col items-center rounded-2xl border-2 p-2 transition-all overflow-hidden"
              :class="form.especie === esp.label ? 'border-[#0f2a4a] bg-[#0f2a4a]/5' : 'border-gray-200'"
            >
              <img :src="esp.img" :alt="esp.label" class="w-full h-14 object-cover rounded-lg mb-1" />
              <span class="text-[10px] font-bold" :class="form.especie === esp.label ? 'text-[#0f2a4a]' : 'text-gray-500'">{{ esp.label }}</span>
            </button>
          </div>
        </div>
        <div v-if="form.especie === 'Otros'">
          <label class="label">Especificar especie</label>
          <input v-model="form.especieOtros" type="text" class="input" placeholder="Ej: Iguana, serpiente..." />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Cantidad de animales</label>
            <input v-model="form.cantidad" type="number" min="1" class="input" placeholder="1" />
          </div>
          <div>
            <label class="label">Raza</label>
            <input v-model="form.raza" type="text" class="input" placeholder="Labrador..." />
          </div>
        </div>
        <div>
          <label class="label">Descripción del caso <span class="text-red-500">*</span></label>
          <textarea v-model="form.descripcion" class="input resize-none" rows="4" placeholder="Describe detalladamente lo que observas..."></textarea>
          <p class="text-xs text-gray-400 mt-1">Mínimo 10 caracteres ({{ form.descripcion.length }})</p>
        </div>
        <div>
          <label class="label">Infracciones <span class="text-red-500">*</span></label>
          <div class="flex flex-col gap-2 mt-1">
            <label
              v-for="inf in infracciones" :key="inf"
              class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
              :class="form.infraccionesSeleccionadas.includes(inf) ? 'border-[#0f2a4a] bg-[#0f2a4a]/5' : 'border-gray-200'"
            >
              <input
                type="checkbox"
                :value="inf"
                v-model="form.infraccionesSeleccionadas"
                class="accent-[#0f2a4a] w-4 h-4"
              />
              <span class="text-sm font-medium">{{ inf }}</span>
            </label>
          </div>
        </div>
        <div v-if="form.infraccionesSeleccionadas.includes('Otros')">
          <label class="label">Especificar otra infracción</label>
          <input v-model="form.infraccionesOtros" type="text" class="input" placeholder="Descripción de la infracción..." />
        </div>
      </div>

      <!-- PASO 4: Documentos y Confirmación -->
      <div v-if="paso === 4" class="flex flex-col gap-5">
        <!-- Archivos DPI -->
        <div>
          <label class="label">Archivos del DPI <span class="text-red-500">*</span> (máx. 2)</label>
          <p class="text-xs text-gray-400 mb-2">Adjunta frente y reverso del DPI (PNG, JPG o PDF)</p>
          <div class="flex gap-3">
            <label
              v-for="n in 2" :key="n"
              class="flex-1 border-2 border-dashed border-gray-300 rounded-2xl p-4 flex flex-col items-center gap-2 cursor-pointer hover:border-[#0f2a4a] transition-colors"
              :class="fotosDpi[n-1] ? 'border-[#0f2a4a] bg-[#0f2a4a]/5' : ''"
            >
              <input type="file" accept=".png,.jpg,.jpeg,.pdf" class="hidden" @change="e => onFotoDpi(e, n-1)" />
              <img v-if="fotosDpi[n-1] && fotosDpi[n-1].preview" :src="fotosDpi[n-1].preview" class="w-full h-20 object-cover rounded-xl" />
              <span v-else class="material-symbols-outlined text-gray-400 text-4xl">badge</span>
              <span class="text-xs text-gray-500 font-medium text-center truncate w-full text-center">{{ fotosDpi[n-1] ? fotosDpi[n-1].name : (n === 1 ? 'Frente' : 'Reverso') }}</span>
            </label>
          </div>
        </div>

        <!-- Archivo Fachada -->
        <div>
          <label class="label">Foto de la Fachada <span class="text-red-500">*</span> (PNG, JPG o PDF)</label>
          <label class="block border-2 border-dashed border-gray-300 rounded-2xl p-4 flex flex-col items-center gap-2 cursor-pointer hover:border-[#0f2a4a] transition-colors" :class="fotoFachada ? 'border-[#0f2a4a] bg-[#0f2a4a]/5' : ''">
            <input type="file" accept=".png,.jpg,.jpeg,.pdf" class="hidden" @change="onFotoFachada" />
            <img v-if="fotoFachada && fotoFachada.preview" :src="fotoFachada.preview" class="w-full h-28 object-cover rounded-xl" />
            <span v-else class="material-symbols-outlined text-gray-400 text-4xl">home</span>
            <span class="text-xs text-gray-500 font-medium">{{ fotoFachada ? fotoFachada.name : 'Adjuntar imagen del lugar del hecho' }}</span>
          </label>
        </div>

        <!-- Evidencia Fotográfica -->
        <div>
          <label class="label">Evidencia Fotográfica (máx. 5 — PNG o JPG)</label>
          <div class="flex flex-wrap gap-2 mb-2">
            <div v-for="(f, i) in fotosEvidencia" :key="i" class="relative">
              <img v-if="f.preview" :src="f.preview" class="w-20 h-20 object-cover rounded-xl" />
              <div v-else class="w-20 h-20 bg-gray-100 rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-400">image</span>
              </div>
              <button @click="fotosEvidencia.splice(i, 1)" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">✕</button>
            </div>
            <label v-if="fotosEvidencia.length < 5" class="w-20 h-20 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer hover:border-[#0f2a4a] transition-colors">
              <input type="file" accept=".png,.jpg,.jpeg" class="hidden" @change="onFotoEvidencia" />
              <span class="material-symbols-outlined text-gray-400 text-2xl">add</span>
            </label>
          </div>
        </div>

        <!-- Archivos Evidencia -->
        <div>
          <label class="label">Archivos de Evidencia (máx. 5, 20MB c/u — PNG, JPG o PDF)</label>
          <label class="block border-2 border-dashed border-gray-300 rounded-2xl p-4 cursor-pointer hover:border-[#0f2a4a] transition-colors">
            <input type="file" multiple accept=".png,.jpg,.jpeg,.pdf" class="hidden" @change="onArchivosEvidencia" />
            <div class="flex items-center gap-3">
              <span class="material-symbols-outlined text-gray-400 text-3xl">attach_file</span>
              <span class="text-sm text-gray-500">Adjuntar archivos de evidencia</span>
            </div>
          </label>
          <div class="flex flex-col gap-1 mt-2">
            <div v-for="(f, i) in archivosEvidencia" :key="i" class="flex items-center justify-between text-xs bg-gray-50 rounded-lg px-3 py-2">
              <span class="truncate max-w-[200px]">{{ f.name }}</span>
              <button @click="archivosEvidencia.splice(i, 1)" class="text-red-400 ml-2">✕</button>
            </div>
          </div>
        </div>

        <!-- Declaración -->
        <label class="flex items-start gap-3 p-4 bg-[#f2f4f6] rounded-2xl cursor-pointer">
          <input type="checkbox" v-model="form.aceptaDeclaracion" class="accent-[#0f2a4a] w-4 h-4 mt-0.5 flex-shrink-0" />
          <span class="text-sm text-[#424654]">
            Declaro que la información proporcionada es verdadera y que soy consciente de las implicaciones legales de presentar una denuncia falsa.
          </span>
        </label>
      </div>

      <!-- Error message -->
      <div v-if="errorMsg" class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        {{ errorMsg }}
      </div>

      <!-- Botones de navegación -->
      <div class="flex gap-3 mt-8">
        <button
          v-if="paso > 1"
          @click="paso--; errorMsg = ''"
          class="flex-1 border-2 border-[#0f2a4a] text-[#0f2a4a] font-bold py-4 rounded-2xl hover:bg-[#0f2a4a]/5 transition-colors"
        >
          Anterior
        </button>
        <button
          v-if="paso < 4"
          @click="siguientePaso"
          class="flex-1 bg-[#0f2a4a] text-white font-bold py-4 rounded-2xl hover:bg-[#1a3d6b] transition-colors"
        >
          Siguiente
        </button>
        <button
          v-if="paso === 4"
          @click="enviarDenuncia"
          :disabled="enviando"
          class="flex-1 bg-[#8B0000] text-white font-bold py-4 rounded-2xl hover:bg-[#6d0000] transition-colors disabled:opacity-60 flex items-center justify-center gap-2"
        >
          <span v-if="enviando" class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span>
          <span>{{ enviando ? 'Enviando...' : 'Enviar Denuncia' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.js'

const router = useRouter()
const base = import.meta.env.BASE_URL

const paso = ref(1)
const enviando = ref(false)
const errorMsg = ref('')

const stepTitles = [
  'Información Personal',
  'Ubicación del Hecho',
  'Detalles del Animal',
  'Documentos y Confirmación',
]

const form = reactive({
  nombre: '',
  dpi: '',
  edad: '',
  genero: '',
  celular: '',
  correo: '',
  responsable: '',
  direccion: '',
  departamento: '',
  municipio: '',
  colorCasa: '',
  colorPuerta: '',
  latitud: '',
  longitud: '',
  especie: 'Caninos',
  especieOtros: '',
  cantidad: '',
  raza: '',
  descripcion: '',
  infraccionesSeleccionadas: [],
  infraccionesOtros: '',
  aceptaDeclaracion: false,
})

// Archivos
const fotosDpi = ref([null, null])
const fotoFachada = ref(null)
const fotosEvidencia = ref([])
const archivosEvidencia = ref([])

const especies = [
  { label: 'Caninos', img: base + 'images/Canino.png' },
  { label: 'Felinos', img: base + 'images/Felino.png' },
  { label: 'Équidos', img: base + 'images/Eqino.png' },
  { label: 'Otros', img: base + 'images/Otros.png' },
]

const infracciones = [
  'Actos de Crueldad',
  'Abandono',
  'No garantizar condiciones de bienestar',
  'Maltrato físico',
  'Mutilaciones',
  'Envenenar o intoxicar a un animal',
  'Peleas de perros',
  'Técnicas de adiestramiento que causen sufrimiento',
  'Otros',
]

const departamentos = [
  'Guatemala', 'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula',
  'El Progreso', 'Escuintla', 'Huehuetenango', 'Izabal', 'Jalapa',
  'Jutiapa', 'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu',
  'Sacatepéquez', 'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez',
  'Totonicapán', 'Zacapa',
]

const municipiosPorDepartamento = {
  'Guatemala': ['Guatemala', 'Santa Catarina Pinula', 'San José Pinula', 'San José del Golfo', 'Palencia', 'Chinautla', 'San Pedro Ayampuc', 'Mixco', 'San Pedro Sacatepéquez', 'San Juan Sacatepéquez', 'San Raymundo', 'Chuarrancho', 'Fraijanes', 'Amatitlán', 'Villa Nueva', 'Villa Canales', 'San Miguel Petapa'],
  'Alta Verapaz': ['Cobán', 'Santa Cruz Verapaz', 'San Cristóbal Verapaz', 'Tactic', 'Tamahú', 'Tucurú', 'Panzós', 'Senahú', 'San Pedro Carchá', 'San Juan Chamelco', 'Lanquín', 'Santa María Cahabón', 'Chisec', 'Chahal', 'Fray Bartolomé de las Casas', 'Santa Catalina La Tinta', 'Raxruhá'],
  'Baja Verapaz': ['Salamá', 'San Miguel Chicaj', 'Granados', 'Santa Cruz el Chol', 'San Jerónimo', 'Purulhá', 'Rabinal', 'Cubulco'],
  'Chimaltenango': ['Chimaltenango', 'San José Poaquil', 'San Martín Jilotepeque', 'Santa Apolonia', 'Tecpán Guatemala', 'Patzún', 'Pochuta', 'Patzicía', 'Santa Cruz Balanyá', 'Acatenango', 'Yepocapa', 'San Andrés Itzapa', 'Parramos', 'Zaragoza', 'El Tejar'],
  'Chiquimula': ['Chiquimula', 'San José la Arada', 'San Juan Ermita', 'Jocotán', 'Camotán', 'Olopa', 'Esquipulas', 'Quetzaltepeque', 'San Jacinto', 'Ipala', 'Concepción Las Minas'],
  'El Progreso': ['Guastatoya', 'Morazán', 'San Agustín Acasaguastlán', 'San Cristóbal Acasaguastlán', 'El Jícaro', 'Sansare', 'Sanarate', 'San Antonio La Paz'],
  'Escuintla': ['Escuintla', 'Santa Lucía Cotzumalguapa', 'La Democracia', 'Siquinalá', 'Masagua', 'Tiquisate', 'La Gomera', 'Guanagazapa', 'San José', 'Iztapa', 'Palín', 'San Vicente Pacaya', 'Nueva Concepción'],
  'Huehuetenango': ['Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nentón', 'San Pedro Necta', 'Jacaltenango', 'Soloma', 'Ixtahuacán', 'Libertad', 'San Miguel Acatán', 'San Rafael La Independencia', 'Todos Santos Cuchumatán', 'San Juan Atitán', 'Santa Eulalia', 'San Mateo Ixtatán', 'Colotenango', 'San Sebastián Huehuetenango', 'Tectitán', 'Concepción Huista', 'San Juan Ixcoy', 'San Antonio Huista', 'San Sebastián Coatán', 'Barillas', 'Aguacatán', 'San Rafael Petzal', 'San Gaspar Ixchil', 'Santiago Chimaltenango', 'Santa Ana Huista', 'Unión Cantinil', 'Petatán'],
  'Izabal': ['Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates'],
  'Jalapa': ['Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparrón', 'San Carlos Alzatate', 'Monjas', 'Mataquescuintla'],
  'Jutiapa': ['Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asunción Mita', 'Yupiltepeque', 'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlán', 'Comapa', 'Jalpatagua', 'Conguaco', 'Moyuta', 'Pasaco', 'San José Acatempa', 'Quesada'],
  'Petén': ['Flores', 'San José', 'San Benito', 'San Andrés', 'La Libertad', 'San Francisco', 'Santa Ana', 'Dolores', 'San Luis', 'Sayaxché', 'Melchor de Mencos', 'Poptún', 'Las Cruces', 'El Chal'],
  'Quetzaltenango': ['Quetzaltenango', 'Salcajá', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabricán', 'Cajolá', 'San Miguel Sigüila', 'Ostuncalco', 'San Mateo', 'Concepción Chiquirichapa', 'San Martín Sacatepéquez', 'Almolonga', 'Cantel', 'Huitán', 'Zunil', 'Colomba', 'San Francisco La Unión', 'El Palmar', 'Coatepeque', 'Génova', 'Flores Costa Cuca', 'La Esperanza', 'Palestina de Los Altos'],
  'Quiché': ['Santa Cruz del Quiché', 'Chiché', 'Chinique', 'Zacualpa', 'Chajul', 'Santo Tomás Chichicastenango', 'Patzité', 'San Antonio Ilotenango', 'San Pedro Jocopilas', 'Cunén', 'San Juan Cotzal', 'Joyabaj', 'Nebaj', 'San Andrés Sajcabajá', 'Uspantán', 'Sacapulas', 'San Bartolomé Jocotenango', 'Canillá', 'Chicamán', 'Ixcán', 'Pachalum'],
  'Retalhuleu': ['Retalhuleu', 'San Sebastián', 'Santa Cruz Mulaúa', 'San Martín Zapotitlán', 'San Felipe', 'San Andrés Villa Seca', 'Champerico', 'Nuevo San Carlos', 'El Asintal'],
  'Sacatepéquez': ['Antigua Guatemala', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj', 'Santiago Sacatepéquez', 'San Bartolomé Milpas Altas', 'San Lucas Sacatepéquez', 'Santa Lucía Milpas Altas', 'Magdalena Milpas Altas', 'Santa María de Jesús', 'Ciudad Vieja', 'San Miguel Dueñas', 'Alotenango', 'San Antonio Aguas Calientes', 'Santa Catarina Barahona'],
  'San Marcos': ['San Marcos', 'San Pedro Sacatepéquez', 'San Antonio Sacatepéquez', 'Comitancillo', 'San Miguel Ixtahuacán', 'Concepción Tutuapa', 'Tacaná', 'Sibinal', 'San José Ojetenam', 'San Cristóbal Cucho', 'Esquipulas Palo Gordo', 'Río Blanco', 'San Lorenzo', 'Tejutla', 'San Rafael Pie de la Cuesta', 'Nuevo Progreso', 'El Tumbador', 'El Rodeo', 'Malacatán', 'Catarina', 'Ayutla', 'Ocós', 'San Pablo', 'El Quetzal', 'La Reforma', 'Pajapita', 'Ixchiguán', 'Tajumulco', 'La Blanca'],
  'Santa Rosa': ['Cuilapa', 'Barberena', 'Santa Rosa de Lima', 'Casillas', 'San Rafael las Flores', 'Oratorio', 'San Juan Tecuaco', 'Chiquimulilla', 'Taxisco', 'Santa María Ixhuatán', 'Guazacapán', 'Santa Cruz Naranjo', 'Pueblo Nuevo Viñas', 'Nueva Santa Rosa'],
  'Sololá': ['Sololá', 'San José Chacayá', 'Santa María Visitación', 'Santa Lucía Utatlán', 'Nahualá', 'Santa Catarina Ixtahuacán', 'Santa Clara la Laguna', 'Concepción', 'San Andrés Semetabaj', 'Panajachel', 'Santa Catarina Palopó', 'San Antonio Palopó', 'San Lucas Tolimán', 'Santa Cruz la Laguna', 'San Pablo la Laguna', 'San Marcos la Laguna', 'San Juan la Laguna', 'San Pedro la Laguna', 'Santiago Atitlán'],
  'Suchitepéquez': ['Mazatenango', 'Cuyotenango', 'San Francisco Zapotitlán', 'San Bernardino', 'San José el Idolo', 'Santo Domingo Suchitepéquez', 'San Lorenzo', 'Samayac', 'San Pablo Jocopilas', 'San Antonio Suchitepéquez', 'San Miguel Panán', 'Chicacao', 'Patulul', 'Santa Bárbara', 'San Juan Bautista', 'Santo Tomás La Unión', 'Zunilito', 'Pueblo Nuevo', 'Río Bravo', 'San José La Máquina'],
  'Totonicapán': ['Totonicapán', 'San Cristóbal Totonicapán', 'San Francisco El Alto', 'San Andrés Xecul', 'Momostenango', 'Santa María Chiquimula', 'Santa Lucía La Reforma', 'San Bartolo'],
  'Zacapa': ['Zacapa', 'Estanzuela', 'Río Hondo', 'Gualán', 'Teculután', 'Usumatlán', 'Cabañas', 'San Diego', 'La Unión', 'Huité', 'San Jorge'],
}

const municipiosDisponibles = computed(() => {
  return form.departamento ? (municipiosPorDepartamento[form.departamento] || []) : []
})

function onDepartamentoChange() {
  form.municipio = ''
}

function formatDpi(e) {
  let v = e.target.value.replace(/\D/g, '').slice(0, 13)
  if (v.length > 8) v = v.slice(0, 4) + ' ' + v.slice(4, 9) + ' ' + v.slice(9)
  else if (v.length > 4) v = v.slice(0, 4) + ' ' + v.slice(4)
  form.dpi = v
}

function crearPreview(file) {
  return { file, name: file.name, preview: URL.createObjectURL(file) }
}

function onFotoDpi(e, idx) {
  const file = e.target.files[0]
  if (file) fotosDpi.value[idx] = crearPreview(file)
}

function onFotoFachada(e) {
  const file = e.target.files[0]
  if (file) fotoFachada.value = crearPreview(file)
}

function onFotoEvidencia(e) {
  const file = e.target.files[0]
  if (file && fotosEvidencia.value.length < 5) {
    fotosEvidencia.value.push(crearPreview(file))
  }
}

function onArchivosEvidencia(e) {
  const files = Array.from(e.target.files)
  for (const f of files) {
    if (archivosEvidencia.value.length >= 5) break
    if (f.size > 20 * 1024 * 1024) { errorMsg.value = `El archivo "${f.name}" supera los 20MB`; continue }
    archivosEvidencia.value.push(f)
  }
}

function validarPaso() {
  errorMsg.value = ''
  if (paso.value === 1) {
    if (!form.nombre.trim()) { errorMsg.value = 'Ingresa tu nombre'; return false }
    const dpiLimpio = form.dpi.replace(/\s/g, '')
    if (dpiLimpio.length !== 13) { errorMsg.value = 'El DPI debe tener 13 dígitos'; return false }
    if (!fotosDpi.value[0] || !fotosDpi.value[1]) { errorMsg.value = 'Sube ambos lados del DPI'; return false }
  }
  if (paso.value === 2) {
    if (!form.direccion.trim()) { errorMsg.value = 'Ingresa la dirección del hecho'; return false }
    if (!form.departamento) { errorMsg.value = 'Selecciona el departamento'; return false }
    if (!form.municipio) { errorMsg.value = 'Selecciona el municipio'; return false }
    if (!fotoFachada.value) { errorMsg.value = 'Sube la foto de la fachada'; return false }
  }
  if (paso.value === 3) {
    if (form.descripcion.length < 10) { errorMsg.value = 'La descripción debe tener al menos 10 caracteres'; return false }
    if (form.infraccionesSeleccionadas.length === 0) { errorMsg.value = 'Selecciona al menos una infracción'; return false }
  }
  if (paso.value === 4) {
    if (!form.aceptaDeclaracion) { errorMsg.value = 'Debes aceptar la declaración jurada'; return false }
  }
  return true
}

function siguientePaso() {
  if (validarPaso()) paso.value++
}

async function enviarDenuncia() {
  if (!validarPaso()) return
  enviando.value = true
  errorMsg.value = ''
  try {
    const fd = new FormData()
    fd.append('nombre_denunciante', form.nombre)
    fd.append('dpi_denunciante', form.dpi.replace(/\s/g, ''))
    fd.append('edad_denunciante', form.edad)
    fd.append('genero_denunciante', form.genero)
    fd.append('celular_denunciante', form.celular)
    fd.append('correo_denunciante', form.correo)
    fd.append('responsable', form.responsable)
    fd.append('direccion_hecho', form.direccion)
    fd.append('departamento_hecho', form.departamento)
    fd.append('municipio_hecho', form.municipio)
    fd.append('color_casa', form.colorCasa)
    fd.append('color_puerta', form.colorPuerta)
    fd.append('latitud', form.latitud)
    fd.append('longitud', form.longitud)
    fd.append('especie', form.especie)
    fd.append('especie_otros', form.especieOtros)
    fd.append('cantidad_animales', form.cantidad)
    fd.append('raza', form.raza)
    fd.append('descripcion', form.descripcion)
    fd.append('infracciones', form.infraccionesSeleccionadas.join(','))
    fd.append('infracciones_otros', form.infraccionesOtros)
    fd.append('acepto_declaracion', form.aceptaDeclaracion ? '1' : '0')

    if (fotosDpi.value[0]) fd.append('dpi_frontal', fotosDpi.value[0].file)
    if (fotosDpi.value[1]) fd.append('dpi_reverso', fotosDpi.value[1].file)
    if (fotoFachada.value) fd.append('fachada', fotoFachada.value.file)
    fotosEvidencia.value.forEach(f => fd.append('evidencia_foto[]', f.file))
    archivosEvidencia.value.forEach(f => fd.append('evidencia_documento[]', f))

    await api.post('/denuncias', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    router.push('/denuncia/exito')
  } catch (e) {
    errorMsg.value = 'Ocurrió un error al enviar la denuncia. Inténtalo de nuevo.'
  } finally {
    enviando.value = false
  }
}
</script>

<style scoped>
.label {
  @apply block text-sm font-semibold text-[#0f2a4a] mb-1;
}
.input {
  @apply w-full border border-[#c3c6d6] rounded-xl px-4 py-3 text-sm outline-none focus:border-[#0f2a4a] focus:ring-2 focus:ring-[#0f2a4a]/10 transition-all bg-white;
}
</style>
