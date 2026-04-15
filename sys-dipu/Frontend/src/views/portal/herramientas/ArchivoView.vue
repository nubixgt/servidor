<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">
            <div>
                <nav class="flex text-[10px] font-bold uppercase tracking-widest text-outline mb-2 gap-2 items-center">
                    <span>Ecosistema</span>
                    <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                    <span class="text-primary">Archivo Central</span>
                </nav>
                <h1 class="text-4xl font-extrabold text-on-surface tracking-tight font-headline">Repositorio Documental</h1>
                <p class="text-on-surface-variant mt-2 max-w-2xl text-sm">
                    Acceso al acervo histórico, expedientes cerrados y resoluciones pasadas. Todos los roles tienen acceso de lectura.
                </p>
            </div>
            <div class="flex gap-3">
                <button class="px-4 py-2 bg-surface-container-high text-on-surface rounded-lg font-bold text-xs flex items-center gap-2 hover:bg-surface-container-highest transition-colors">
                    <span class="material-symbols-outlined text-sm">download</span> Exportar Índice
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">
                        <span class="material-symbols-outlined">folder_open</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface font-headline">45,231</h3>
                <p class="text-xs text-outline mt-1">Expedientes digitalizados</p>
            </div>
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                        <span class="material-symbols-outlined">history</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Este Mes</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface font-headline">+124</h3>
                <p class="text-xs text-outline mt-1">Nuevos ingresos al archivo</p>
            </div>
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center">
                        <span class="material-symbols-outlined">search_insights</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Consultas</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface font-headline">892</h3>
                <p class="text-xs text-outline mt-1">Búsquedas en los últimos 7 días</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-surface-container-lowest p-4 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10 flex flex-col md:flex-row gap-4 items-center">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-outline">
                    <span class="material-symbols-outlined">search</span>
                </span>
                <input v-model="searchQuery" type="text" placeholder="Buscar por ID de expediente, título o palabras clave..." class="w-full bg-surface-container-low border-none rounded-xl py-3 pl-12 pr-4 text-sm focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <select class="bg-surface-container-low border-none rounded-xl py-3 px-4 text-sm text-on-surface font-medium focus:ring-2 focus:ring-primary/20 cursor-pointer outline-none">
                    <option>Todos los Tipos</option>
                    <option>Leyes</option>
                    <option>Decretos</option>
                    <option>Resoluciones</option>
                    <option>Actas</option>
                </select>
                <select class="bg-surface-container-low border-none rounded-xl py-3 px-4 text-sm text-on-surface font-medium focus:ring-2 focus:ring-primary/20 cursor-pointer outline-none">
                    <option>Cualquier Año</option>
                    <option>2023</option>
                    <option>2022</option>
                    <option>2021</option>
                </select>
                <button class="px-4 py-3 bg-surface-container-high text-on-surface rounded-xl flex items-center justify-center hover:bg-surface-container-highest transition-colors">
                    <span class="material-symbols-outlined text-lg">tune</span>
                </button>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 border-b border-outline-variant/20">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Expediente</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Título / Asunto</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Tipo</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Fecha Archivo</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Estado</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="item in archiveData" :key="item.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="p-4">
                                <span class="text-xs font-bold font-mono text-primary bg-primary/5 px-2 py-1 rounded">{{ item.id }}</span>
                            </td>
                            <td class="p-4">
                                <p class="text-sm font-bold text-on-surface">{{ item.title }}</p>
                                <p class="text-[10px] text-outline mt-0.5">Módulo: {{ item.module }}</p>
                            </td>
                            <td class="p-4">
                                <span class="text-xs font-medium text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">{{ iconForType(item.type) }}</span>
                                    {{ item.type }}
                                </span>
                            </td>
                            <td class="p-4 text-xs text-on-surface-variant">{{ item.date }}</td>
                            <td class="p-4">
                                <span :class="['text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-full', statusClass(item.status)]">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-white transition-colors" title="Ver Detalles">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </button>
                                    <button class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-white transition-colors" title="Descargar PDF">
                                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="p-4 border-t border-outline-variant/10 flex items-center justify-between bg-surface-container-lowest">
                <span class="text-xs text-outline font-medium">Mostrando 1 a 7 de 45,231 registros</span>
                <div class="flex gap-1">
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-outline hover:bg-surface-container transition-colors" disabled>
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </button>
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center bg-primary text-white font-bold text-xs">1</button>
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors font-bold text-xs">2</button>
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors font-bold text-xs">3</button>
                    <span class="w-8 h-8 flex items-center justify-center text-outline text-xs">...</span>
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors font-bold text-xs">89</button>
                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const searchQuery = ref('');

const archiveData = [
    { id: 'EXP-2022-0891', title: 'Ley de Presupuesto General 2023', type: 'Ley', date: '15 Dic 2022', module: 'Finanzas', status: 'Aprobado' },
    { id: 'RES-2023-0142', title: 'Resolución de Nombramientos Comité B', type: 'Resolución', date: '04 Mar 2023', module: 'Administración', status: 'Histórico' },
    { id: 'DEC-2021-0055', title: 'Decreto de Emergencia Sanitaria (Cierre)', type: 'Decreto', date: '30 Nov 2021', module: 'Salud', status: 'Abrogado' },
    { id: 'ACT-2023-0992', title: 'Acta de Sesión Plenaria Ordinaria #45', type: 'Acta', date: '12 Oct 2023', module: 'Pleno', status: 'Histórico' },
    { id: 'EXP-2020-1102', title: 'Reforma al Código de Comercio', type: 'Ley', date: '22 Ene 2021', module: 'Economía', status: 'Aprobado' },
    { id: 'RES-2022-0881', title: 'Aprobación de Plan de Desarrollo Urbano', type: 'Resolución', date: '18 Ago 2022', module: 'Infraestructura', status: 'Histórico' },
    { id: 'EXP-2019-0334', title: 'Iniciativa de Ley de Protección Animal', type: 'Iniciativa', date: '05 Sep 2019', module: 'Medio Ambiente', status: 'Rechazado' },
];

const iconForType = (type) => {
    const icons = { 'Ley': 'gavel', 'Resolución': 'contract', 'Acta': 'history_edu' };
    return icons[type] || 'description';
};

const statusClass = (status) => {
    const classes = {
        'Aprobado': 'bg-primary-container text-on-primary-container',
        'Rechazado': 'bg-error-container text-on-error-container',
        'Abrogado': 'bg-surface-container-highest text-on-surface-variant',
    };
    return classes[status] || 'bg-secondary-container text-on-secondary-container';
};
</script>
