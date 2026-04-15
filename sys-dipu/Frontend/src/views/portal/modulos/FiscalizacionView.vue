<template>
    <div class="space-y-8">
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Fiscalización</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Control y seguimiento de auditorías, inspecciones e investigaciones de actos administrativos.</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
                    <span class="material-symbols-outlined text-xl">ios_share</span> Exportar
                </button>
                <button class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span> Nueva Fiscalización
                </button>
            </div>
        </header>
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Expedientes</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">91</span>
                    <span class="text-primary font-bold text-sm">6 Críticos</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Inspecciones Activas</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">40</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Cerradas</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">42</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Pendientes de Inicio</span>
                        <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">9</span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por entidad, expediente o auditor..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button class="px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm">Todos</button>
                <button class="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Activos</button>
                <button class="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Cerrados</button>
                <button class="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Críticos</button>
            </div>
            <button class="p-3 bg-surface-container-low text-on-surface-variant rounded-xl hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined">filter_list</span>
            </button>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                            <th class="px-8 py-5">Expediente</th>
                            <th class="px-8 py-5">Entidad Fiscalizada</th>
                            <th class="px-8 py-5">Auditor</th>
                            <th class="px-8 py-5">Prioridad</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="group hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">FIS-2024-089</span></td>
                            <td class="px-8 py-6">
                                <div>
                                    <p class="font-bold text-on-surface">Municipalidad Zona Central</p>
                                    <p class="text-xs text-on-surface-variant">Auditoría financiera integral</p>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center text-[10px] font-bold text-on-primary-container">JR</div>
                                    <span class="text-sm">J. Ricci</span>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-error-container text-on-error-container text-xs font-bold rounded-full">Alta</span></td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full">En Proceso</span></td>
                            <td class="px-8 py-6 text-right">
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">visibility</span></button>
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                            </td>
                        </tr>
                        <tr class="group hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">FIS-2024-072</span></td>
                            <td class="px-8 py-6">
                                <div>
                                    <p class="font-bold text-on-surface">Hospital Nacional San Miguel</p>
                                    <p class="text-xs text-on-surface-variant">Revisión de adquisiciones de equipos</p>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">EV</div>
                                    <span class="text-sm">E. Valdés</span>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full">Media</span></td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">Cerrado</span></td>
                            <td class="px-8 py-6 text-right">
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">visibility</span></button>
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
<script setup></script>
