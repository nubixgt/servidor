<template>
  <Transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-start justify-center pt-[15vh] sm:pt-[20vh] px-4 backdrop-blur-sm bg-slate-900/40 dark:bg-black/60">
      <div class="absolute inset-0" @click="close"></div>
      
      <div 
        class="relative w-full max-w-2xl bg-white/95 dark:bg-[#0f172a]/95 backdrop-blur-xl rounded-2xl shadow-2xl overflow-hidden border border-white/60 dark:border-white/10 flex flex-col"
        @click.stop
      >
        <!-- Search Input -->
        <div class="flex items-center px-4 py-4 border-b border-slate-200/50 dark:border-slate-800/50">
          <MagnifyingGlassIcon v-if="!isSearching" class="w-6 h-6 text-emerald-600 dark:text-emerald-500 mr-3 flex-shrink-0" />
          <div v-else class="w-6 h-6 mr-3 flex-shrink-0 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
          <input 
            ref="searchInput"
            v-model="searchQuery"
            type="text"
            class="flex-1 bg-transparent border-none outline-none text-lg text-slate-800 dark:text-white placeholder-slate-400 focus:ring-0"
            placeholder="Busca módulos, productores, congresistas..."
            @keydown.down.prevent="navigateDown"
            @keydown.up.prevent="navigateUp"
            @keydown.enter.prevent="selectCurrent"
            @keydown.esc.prevent="close"
          />
          <button @click="close" class="p-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 text-[10px] uppercase font-black px-2 ml-2">ESC</button>
        </div>

        <!-- Results -->
        <div class="max-h-[60vh] overflow-y-auto custom-scrollbar p-2">

          <!-- Empty state for searches with no results -->
          <div v-if="searchQuery.length >= 2 && !isSearching && allFlatResults.length === 0" class="py-14 text-center">
            <MagnifyingGlassIcon class="w-12 h-12 text-slate-300 dark:text-slate-700 mx-auto mb-3" />
            <p class="text-slate-500 font-medium">Sin resultados para <strong>"{{ searchQuery }}"</strong></p>
            <p class="text-slate-400 text-sm mt-1">Intenta buscar por nombre, DPI o municipio.</p>
          </div>

          <!-- Default state -->
          <div v-else-if="searchQuery.length < 2" class="py-10 text-center">
            <CommandLineIcon class="w-12 h-12 text-slate-200 dark:text-slate-800 mx-auto mb-3" />
            <p class="text-slate-400 text-sm">Escribe al menos 2 caracteres para buscar.</p>
            <p class="text-slate-400 text-sm mt-1">Ej: <span class="font-bold text-emerald-600">Productores</span>, <span class="font-bold text-emerald-600">VISAN</span>, <span class="font-bold text-emerald-600">Juan García</span></p>
          </div>

          <div v-else>
            <!-- DB Results (Live Data) -->
            <div v-if="dbResults.length > 0" class="mb-4">
              <h3 class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-500 mb-1 flex items-center gap-2">
                <CircleStackIcon class="w-3.5 h-3.5" />
                Registros en el Sistema
              </h3>
              <div class="space-y-1">
                <button
                  v-for="item in dbResults" 
                  :key="'db-' + item.entity_id + item.type"
                  class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 transition-colors outline-none"
                  :class="selectedIndex === item.globalIndex ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-400 shadow-sm' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-700 dark:text-slate-300'"
                  @mouseenter="selectedIndex = item.globalIndex"
                  @click="goToRoute(item.route)"
                >
                  <div class="p-2 rounded-lg flex-shrink-0" :class="selectedIndex === item.globalIndex ? 'bg-emerald-100 dark:bg-emerald-500/30' : 'bg-slate-100 dark:bg-slate-800'">
                    <UsersIcon      v-if="item.icon === 'user'"       class="w-4 h-4" />
                    <UserGroupIcon  v-else-if="item.icon === 'user-group'"  class="w-4 h-4" />
                    <BuildingLibraryIcon v-else-if="item.icon === 'building'" class="w-4 h-4" />
                    <MapIcon        v-else-if="item.icon === 'map'"    class="w-4 h-4" />
                    <HeartIcon      v-else-if="item.icon === 'heart'"  class="w-4 h-4" />
                    <ShieldCheckIcon v-else-if="item.icon === 'shield'" class="w-4 h-4" />
                    <DocumentCheckIcon v-else-if="item.icon === 'document'" class="w-4 h-4" />
                    <CalendarIcon   v-else                             class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm leading-tight truncate">{{ item.primary }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ item.secondary }}</p>
                  </div>
                  <!-- Badge tipo -->
                  <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400">
                      {{ item.type }}
                    </span>
                    <span v-if="item.badge" class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 max-w-[100px] truncate">
                      {{ item.badge }}
                    </span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Module Results (Navigation) -->
            <div v-if="filteredModuleResults.length > 0" class="mb-2">
              <h3 class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 flex items-center gap-2">
                <Squares2X2Icon class="w-3.5 h-3.5" />
                Módulos y Pantallas
              </h3>
              <div class="space-y-1">
                <button
                  v-for="item in filteredModuleResults"
                  :key="'mod-' + item.id"
                  class="w-full text-left px-4 py-3 rounded-xl flex items-center gap-3 transition-colors outline-none"
                  :class="selectedIndex === item.globalIndex ? 'bg-slate-100 dark:bg-slate-800 shadow-sm' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-700 dark:text-slate-300'"
                  @mouseenter="selectedIndex = item.globalIndex"
                  @click="goToRoute(item.route)"
                >
                  <div class="p-2 rounded-lg flex-shrink-0" :class="selectedIndex === item.globalIndex ? 'bg-slate-200 dark:bg-slate-700' : 'bg-slate-100 dark:bg-slate-800'">
                    <component :is="item.icon" class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm leading-tight">{{ item.name }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ item.description }}</p>
                  </div>
                  <ArrowRightIcon v-if="selectedIndex === item.globalIndex" class="w-4 h-4 flex-shrink-0 text-slate-400" />
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- Footer hint -->
        <div class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center gap-4 text-[10px] text-slate-400">
          <span class="flex items-center gap-1"><kbd class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">↑↓</kbd> Navegar</span>
          <span class="flex items-center gap-1"><kbd class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">↵</kbd> Ir</span>
          <span class="flex items-center gap-1"><kbd class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">ESC</kbd> Cerrar</span>
          <span class="ml-auto flex items-center gap-1 text-emerald-600 dark:text-emerald-500 font-bold">
            <CircleStackIcon class="w-3 h-3" /> Busca en datos reales del sistema
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { 
  MagnifyingGlassIcon, Squares2X2Icon, UsersIcon, ShieldCheckIcon, 
  DocumentCheckIcon, MapIcon, HeartIcon, ChartBarIcon, BuildingLibraryIcon,
  BriefcaseIcon, DocumentArrowDownIcon, UserGroupIcon, Cog6ToothIcon, 
  PlusIcon, ArrowRightIcon, CalendarIcon, CircleStackIcon, CommandLineIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({ modelValue: Boolean })
const emit  = defineEmits(['update:modelValue'])
const router = useRouter()

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const searchQuery   = ref('')
const searchInput   = ref(null)
const selectedIndex = ref(0)
const dbResults     = ref([])
const isSearching   = ref(false)
let   debounceTimer = null

// ── Catálogo estático de módulos ──────────────────────────────────
const moduleItems = [
  { id: 1,  name: 'Dashboard Principal',        description: 'Vista general y reportes',                        group: 'Navegación',   icon: Squares2X2Icon,       route: '/admin/dashboard' },
  { id: 2,  name: 'Padrón de Productores',      description: 'Listado del Padrón Nacional de Productores',      group: 'Módulos',      icon: UsersIcon,             route: '/admin/productores' },
  { id: 3,  name: 'Sanidad Agropecuaria',       description: 'Control sanitario y permisos VISAR',              group: 'Módulos',      icon: ShieldCheckIcon,       route: '/admin/sanidad' },
  { id: 4,  name: 'Licencias y Permisos',       description: 'Gestión de licencias emitidas',                   group: 'Módulos',      icon: DocumentCheckIcon,     route: '/admin/licencias' },
  { id: 5,  name: 'Extensión Rural',            description: 'Visitas técnicas por municipio',                  group: 'Módulos',      icon: MapIcon,               route: '/admin/extension' },
  { id: 6,  name: 'Gestión Ministerial',        description: 'Actividades del Despacho Superior',               group: 'Módulos',      icon: BriefcaseIcon,         route: '/admin/actividades-despacho' },
  { id: 7,  name: 'Ejecución Presupuestaria',   description: 'Gráficas del presupuesto institucional',          group: 'Módulos',      icon: ChartBarIcon,          route: '/admin/presupuesto' },
  { id: 8,  name: 'VISAN: Dashboard',           description: 'Estadísticas de Asistencia Alimentaria',          group: 'VISAN',        icon: HeartIcon,             route: '/admin/visan/dashboard' },
  { id: 9,  name: 'VISAN: DAPCA',              description: 'Indicadores de desempeño DAPCA',                  group: 'VISAN',        icon: ChartBarIcon,          route: '/admin/visan/dapca' },
  { id: 10, name: 'VISAN: Tabla de Datos',      description: 'Base de datos de entregas alimentarias',          group: 'VISAN',        icon: DocumentCheckIcon,     route: '/admin/visan/tabla' },
  { id: 11, name: 'VISAN: Editar Registros',    description: 'Gestión y edición de registros VISAN',            group: 'VISAN',        icon: PlusIcon,              route: '/admin/visan/editar' },
  { id: 12, name: 'VIDER: Dashboard',           description: 'Ejecución física del programa VIDER',             group: 'VIDER',        icon: ChartBarIcon,          route: '/admin/vider/dashboard' },
  { id: 13, name: 'VIDER: Base de Datos',       description: 'Tabla de ejecución física y municipios',          group: 'VIDER',        icon: DocumentCheckIcon,     route: '/admin/vider/tabla' },
  { id: 14, name: 'VIDER: Importar Archivos',   description: 'Carga masiva de Excel inteligente',               group: 'Acciones',     icon: DocumentArrowDownIcon, route: '/admin/vider/importar' },
  { id: 15, name: 'VIDER: Tobanik',             description: 'Cooperativas y programa Tobanik',                 group: 'VIDER',        icon: UsersIcon,             route: '/admin/vider/tobanik' },
  { id: 16, name: 'Votaciones del Congreso',    description: 'Actas y bloques legislativos',                    group: 'Institucional',icon: BuildingLibraryIcon,   route: '/admin/votaciones' },
  { id: 17, name: 'Usuarios y Permisos',        description: 'Administración de accesos al sistema',            group: 'Admin',        icon: UserGroupIcon,         route: '/admin/users' },
  { id: 18, name: 'Configuración General',      description: 'Ajustes y perfiles del sistema MAGA',             group: 'Admin',        icon: Cog6ToothIcon,         route: '/admin/settings' },
]

// ── Filtrado de módulos estáticos ──────────────────────────────────
const filteredModuleResults = computed(() => {
  const q = searchQuery.value.toLowerCase()
  if (q.length < 2) return []
  const filtered = moduleItems.filter(i =>
    i.name.toLowerCase().includes(q) ||
    i.description.toLowerCase().includes(q) ||
    i.group.toLowerCase().includes(q)
  )
  // Assign global indices (after dbResults)
  return filtered.map((item, idx) => ({ ...item, globalIndex: dbResults.value.length + idx }))
})

// ── Todos los resultados planos para navegación con teclado ────────
const allFlatResults = computed(() => [
  ...dbResults.value.map((r, i) => ({ ...r, globalIndex: i })),
  ...filteredModuleResults.value,
])

// ── Búsqueda en backend con debounce ──────────────────────────────
const searchInDB = async (query) => {
  if (query.length < 2) { dbResults.value = []; return }
  isSearching.value = true
  try {
    const { data } = await api.get('/search', { params: { q: query } })
    if (data.status === 'success') {
      dbResults.value = (data.data || []).map((r, i) => ({ ...r, globalIndex: i }))
    }
  } catch (e) {
    dbResults.value = []
  } finally {
    isSearching.value = false
  }
}

watch(searchQuery, (val) => {
  selectedIndex.value = 0
  clearTimeout(debounceTimer)
  if (val.length < 2) { dbResults.value = []; return }
  debounceTimer = setTimeout(() => searchInDB(val), 300)
})

// ── Navegación con teclado ────────────────────────────────────────
const navigateDown = () => {
  if (selectedIndex.value < allFlatResults.value.length - 1) selectedIndex.value++
}
const navigateUp = () => {
  if (selectedIndex.value > 0) selectedIndex.value--
}

const selectCurrent = () => {
  const item = allFlatResults.value.find(i => i.globalIndex === selectedIndex.value)
  if (item) goToRoute(item.route)
}

const goToRoute = (route) => {
  router.push(route)
  close()
}

const close = () => {
  isOpen.value = false
  searchQuery.value = ''
  selectedIndex.value = 0
  dbResults.value = []
}

watch(isOpen, async (val) => {
  if (val) {
    selectedIndex.value = 0
    searchQuery.value = ''
    dbResults.value = []
    await nextTick()
    searchInput.value?.focus()
  }
})

// ── Atajo global Ctrl+K ───────────────────────────────────────────
const handleGlobalKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    isOpen.value = true
  }
}
onMounted(()  => window.addEventListener('keydown', handleGlobalKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleGlobalKeydown))
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-12px) scale(0.98);
}
</style>
