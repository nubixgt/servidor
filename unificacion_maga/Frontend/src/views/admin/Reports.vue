<template>
    <div class="animate-fade-in w-full max-w-5xl mx-auto pt-4">
        <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 px-2">
            <div>
                <h2 class="text-2xl font-bold text-brand-dark dark:text-white flex items-center gap-3">
                    <ChartPieIcon class="w-6 h-6 text-secondary" />
                    Centro de Reportes
                </h2>
                <p class="text-sm text-gray-500 mt-1">Descarga y analiza el rendimiento financiero.</p>
            </div>
            
            <div class="flex items-center gap-2 mt-4 md:mt-0">
                <button class="flex items-center gap-2 px-4 py-2 bg-white/40 dark:bg-black/20 border border-white/30 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-white/60 transition-colors">
                    <FunnelIcon class="w-4 h-4" />
                    Filtrar por Fecha
                </button>
                <button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium shadow-lg shadow-blue-500/20 hover:bg-blue-600 transition-colors">
                    Generar Nuevo
                </button>
            </div>
        </div>

        <div class="glass-panel p-8 rounded-3xl mb-8">
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-6">Reportes Mensuales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Manual invocation of Report Cards for varied styles -->
                
                <div class="glass-card p-5 rounded-2xl border border-white/40 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/10 rounded-full blur-2xl -mr-10 -mt-10 transition-transform group-hover:scale-150"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-xl text-green-600 dark:text-green-400">
                            <CurrencyDollarIcon class="w-6 h-6" />
                        </div>
                        <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-gray-600 transition-colors">
                            <ArrowDownTrayIcon class="w-4.5 h-4.5" />
                        </button>
                    </div>
                    <h3 class="font-bold text-lg text-brand-dark dark:text-white mb-1 relative z-10">Consolidado de Viáticos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 relative z-10">Resumen general de gastos aprobados por departamento.</p>
                    <div class="flex items-center justify-between text-xs font-medium text-gray-400 border-t border-gray-200/50 dark:border-gray-700/50 pt-3 relative z-10">
                        <span>Generado: Hoy</span>
                        <span>2.4 MB (PDF)</span>
                    </div>
                </div>

                <div class="glass-card p-5 rounded-2xl border border-white/40 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl -mr-10 -mt-10 transition-transform group-hover:scale-150"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-xl text-blue-600 dark:text-blue-400">
                            <ArrowTrendingUpIcon class="w-6 h-6" />
                        </div>
                        <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-gray-600 transition-colors">
                            <ArrowDownTrayIcon class="w-4.5 h-4.5" />
                        </button>
                    </div>
                    <h3 class="font-bold text-lg text-brand-dark dark:text-white mb-1 relative z-10">Métricas de Rendimiento</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 relative z-10">Análisis de tiempos de aprobación y cuellos de botella.</p>
                    <div class="flex items-center justify-between text-xs font-medium text-gray-400 border-t border-gray-200/50 dark:border-gray-700/50 pt-3 relative z-10">
                        <span>Generado: Ayer</span>
                        <span>1.1 MB (XLSX)</span>
                    </div>
                </div>

                <div class="glass-card p-5 rounded-2xl border border-white/40 dark:border-gray-700 hover:shadow-lg transition-all group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl -mr-10 -mt-10 transition-transform group-hover:scale-150"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-xl text-purple-600 dark:text-purple-400">
                            <DocumentTextIcon class="w-6 h-6" />
                        </div>
                        <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-400 hover:text-gray-600 transition-colors">
                            <ArrowDownTrayIcon class="w-4.5 h-4.5" />
                        </button>
                    </div>
                    <h3 class="font-bold text-lg text-brand-dark dark:text-white mb-1 relative z-10">Auditoría Interna</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 relative z-10">Log completo de cambios y accesos al sistema.</p>
                    <div class="flex items-center justify-between text-xs font-medium text-gray-400 border-t border-gray-200/50 dark:border-gray-700/50 pt-3 relative z-10">
                        <span>Generado: 20 Oct</span>
                        <span>8.5 MB (CSV)</span>
                    </div>
                </div>

                </div>
        </div>

        <div class="glass-panel p-8 rounded-3xl">
                <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-6">Reportes Históricos (2023)</h3>
                <div class="space-y-3">
                <div v-for="i in [1, 2, 3]" :key="i" class="flex items-center justify-between p-4 bg-white/30 dark:bg-black/20 rounded-xl hover:bg-white/50 transition-colors cursor-pointer group">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500">
                            <DocumentTextIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-brand-dark dark:text-gray-200 group-hover:text-primary transition-colors">Cierre Fiscal Q{{ i }} 2023</h4>
                            <p class="text-xs text-gray-500">Autor: Contabilidad General</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-mono text-gray-400">4.2 MB</span>
                        <ArrowDownTrayIcon class="w-4.5 h-4.5 text-gray-400 group-hover:text-primary transition-colors" />
                    </div>
                </div>
                </div>
        </div>

    </div>
</template>

<script setup>
import { 
    ChartPieIcon, 
    FunnelIcon, 
    CurrencyDollarIcon, 
    ArrowDownTrayIcon, 
    ArrowTrendingUpIcon, 
    DocumentTextIcon 
} from '@heroicons/vue/24/outline';
</script>
