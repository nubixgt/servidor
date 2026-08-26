<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../../services/api'
import { 
  ArrowRight, 
  PlaneTakeoff, 
  PlaneLanding, 
  Truck, 
  Leaf, 
  FileBadge,
  TrendingUp,
  ShieldCheck,
  Globe2
} from 'lucide-vue-next'

const router = useRouter()

const modules = [
  {
    id: 'exportaciones',
    title: 'Exportaciones',
    description: 'Control de certificados SIIA y envíos internacionales.',
    icon: PlaneTakeoff,
    route: '/admin/visar/exportaciones',
    color: 'blue',
    stats: 'Módulo Activo'
  },
  {
    id: 'importaciones',
    title: 'Importaciones',
    description: 'Registro de ingresos aduaneros y revisiones fito/zoosanitarias.',
    icon: PlaneLanding,
    route: '/admin/visar/importaciones',
    color: 'emerald',
    stats: 'Módulo Activo'
  },
  {
    id: 'licencias-transporte',
    title: 'Licencias de Transporte',
    description: 'Control de vehículos, placas y vigencia de permisos.',
    icon: Truck,
    route: '/admin/visar/licencias-transporte',
    color: 'orange',
    stats: 'Módulo Activo'
  },
  {
    id: 'licencias-fito',
    title: 'Licencias Fitosanitarias',
    description: 'Establecimientos, categorías y control de propietarios.',
    icon: Leaf,
    route: '/admin/visar/licencias-fitosanitarias',
    color: 'teal',
    stats: 'Módulo Activo'
  },
  {
    id: 'libre-venta',
    title: 'Libre Venta LV SIIA',
    description: 'Certificados emitidos para exportación de productos procesados.',
    icon: FileBadge,
    route: '/admin/visar/libre-venta',
    color: 'fuchsia'
  }
]

const stats = ref({})
const loadingStats = ref(true)

const loadStats = async () => {
  try {
    const { data } = await api.get('/visar/dashboard/stats')
    if (data.success) {
      stats.value = data.data
    }
  } catch (error) {
    console.error("Error cargando estadísticas", error)
  } finally {
    loadingStats.value = false
  }
}

onMounted(() => {
  loadStats()
})

const getColorClasses = (colorName) => {
  const map = {
    'blue': {
      bg: 'bg-blue-50 dark:bg-blue-900/20',
      iconText: 'text-blue-600 dark:text-blue-400',
      borderHover: 'hover:border-blue-300 dark:hover:border-blue-700',
      shadowHover: 'hover:shadow-blue-500/20'
    },
    'emerald': {
      bg: 'bg-emerald-50 dark:bg-emerald-900/20',
      iconText: 'text-emerald-600 dark:text-emerald-400',
      borderHover: 'hover:border-emerald-300 dark:hover:border-emerald-700',
      shadowHover: 'hover:shadow-emerald-500/20'
    },
    'orange': {
      bg: 'bg-orange-50 dark:bg-orange-900/20',
      iconText: 'text-orange-600 dark:text-orange-400',
      borderHover: 'hover:border-orange-300 dark:hover:border-orange-700',
      shadowHover: 'hover:shadow-orange-500/20'
    },
    'teal': {
      bg: 'bg-teal-50 dark:bg-teal-900/20',
      iconText: 'text-teal-600 dark:text-teal-400',
      borderHover: 'hover:border-teal-300 dark:hover:border-teal-700',
      shadowHover: 'hover:shadow-teal-500/20'
    },
    'fuchsia': {
      bg: 'bg-fuchsia-50 dark:bg-fuchsia-900/20',
      iconText: 'text-fuchsia-600 dark:text-fuchsia-400',
      borderHover: 'hover:border-fuchsia-300 dark:hover:border-fuchsia-700',
      shadowHover: 'hover:shadow-fuchsia-500/20'
    }
  }
  return map[colorName] || map['blue']
}

const formatNumber = (num) => {
  if (num === undefined || num === null) return '0'
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 1 }).format(num)
}

const formatCurrency = (num) => {
  if (num === undefined || num === null) return '$0'
  if (num >= 1000000) {
    return '$' + (num / 1000000).toFixed(1) + 'M'
  }
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(num)
}
</script>

<template>
  <div class="space-y-8 pb-12 animate-fade-in max-w-7xl mx-auto">
    
    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 md:p-12 shadow-2xl border border-slate-700">
      <div class="absolute top-0 right-0 p-12 opacity-10 pointer-events-none">
        <Globe2 class="w-64 h-64 text-white" />
      </div>
      
      <div class="relative z-10 max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-white text-xs font-bold uppercase tracking-wider mb-6">
          <ShieldCheck class="w-4 h-4" />
          Sistema Unificado
        </div>
        
        <h1 class="text-4xl md:text-5xl font-black text-white mb-4 leading-tight">
          VISAR <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">Hub Central</span>
        </h1>
        
        <p class="text-lg text-slate-300 mb-8 max-w-2xl font-medium leading-relaxed">
          Centro de control y monitoreo de sanidad agropecuaria. Accede rápidamente a los módulos de importación, exportación y licenciamiento.
        </p>
      </div>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div 
        v-for="mod in modules" 
        :key="mod.id"
        @click="router.push(mod.route)"
        class="group relative bg-white dark:bg-slate-900 rounded-3xl p-6 border-2 border-transparent transition-all duration-300 cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1"
        :class="getColorClasses(mod.color).borderHover + ' ' + getColorClasses(mod.color).shadowHover"
      >
        <div class="flex items-start justify-between mb-4">
          <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3', getColorClasses(mod.color).bg, getColorClasses(mod.color).iconText]">
            <component :is="mod.icon" class="w-7 h-7" />
          </div>
          <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-slate-100 dark:group-hover:bg-slate-700 transition-colors">
            <ArrowRight class="w-4 h-4" />
          </div>
        </div>
        
        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2 group-hover:text-current transition-colors">
          {{ mod.title }}
        </h3>
        
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 min-h-[40px] leading-relaxed">
          {{ mod.description }}
        </p>
        
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
          <div v-if="loadingStats" class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-400 animate-pulse">
            <TrendingUp class="w-3.5 h-3.5" /> Cargando...
          </div>
          <div v-else-if="stats[mod.id] !== undefined">
            <div class="grid gap-2 text-center" :class="{
                'grid-cols-3': stats[mod.id].valor !== undefined && stats[mod.id].toneladas !== undefined,
                'grid-cols-2': (stats[mod.id].valor !== undefined && stats[mod.id].toneladas === undefined) || (stats[mod.id].valor === undefined && stats[mod.id].toneladas !== undefined),
                'grid-cols-1': stats[mod.id].valor === undefined && stats[mod.id].toneladas === undefined
            }">
                <div>
                    <div class="text-xl font-black text-slate-800 dark:text-white">{{ formatNumber(stats[mod.id].total) }}</div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">Registros</div>
                </div>
                <div v-if="stats[mod.id].valor !== undefined">
                    <div class="text-xl font-black text-slate-800 dark:text-white">{{ formatCurrency(stats[mod.id].valor) }}</div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">Valor {{ mod.id === 'exportaciones' ? 'FOB' : 'Total' }}</div>
                </div>
                <div v-if="stats[mod.id].toneladas !== undefined">
                    <div class="text-xl font-black text-slate-800 dark:text-white">{{ formatNumber(stats[mod.id].toneladas) }}T</div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">Toneladas</div>
                </div>
            </div>
          </div>
          <div v-else class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-400">
            <TrendingUp class="w-3.5 h-3.5" /> Módulo Activo
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
