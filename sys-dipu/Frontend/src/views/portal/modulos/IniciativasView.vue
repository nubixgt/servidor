<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Iniciativas de Ley</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Gestión integral de propuestas legislativas y seguimiento de estados parlamentarios.</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
                    <span class="material-symbols-outlined text-xl">ios_share</span> Exportar
                </button>
                <button class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span> Nueva Iniciativa
                </button>
            </div>
        </header>

        <!-- Stats -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Iniciativas</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">142</span>
                    <span class="text-primary font-bold text-sm">+12% este mes</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">En Revisión</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">24</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Presentadas</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">88</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Borradores</span>
                        <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">30</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por título, folio o autor..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button class="px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm">Todas</button>
                <button class="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Borrador</button>
                <button class="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">En Trámite</button>
                <button class="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Aprobadas</button>
            </div>
            <button class="p-3 bg-surface-container-low text-on-surface-variant rounded-xl hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined">filter_list</span>
            </button>
        </div>

        <!-- Table -->
        <div class="bg-surface-container-lowest rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                            <th class="px-8 py-5">Referencia</th>
                            <th class="px-8 py-5">Título de la Iniciativa</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5">Fecha</th>
                            <th class="px-8 py-5">Autor</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="group hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">L-2024-089</span></td>
                            <td class="px-8 py-6">
                                <div class="max-w-md">
                                    <p class="font-bold text-on-surface mb-1">Ley de Transparencia Algorítmica</p>
                                    <p class="text-xs text-on-surface-variant line-clamp-1">Reforma al artículo 45 en materia de inteligencia artificial y ética pública.</p>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full">En Comisión</span></td>
                            <td class="px-8 py-6"><p class="text-sm text-on-surface">12 Oct 2024</p></td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center text-[10px] font-bold text-on-primary-container">MS</div>
                                    <span class="text-sm">M. Sánchez</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">visibility</span></button>
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                            </td>
                        </tr>
                        <tr class="group hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">L-2024-092</span></td>
                            <td class="px-8 py-6">
                                <div class="max-w-md">
                                    <p class="font-bold text-on-surface mb-1">Programa de Movilidad Sustentable 2030</p>
                                    <p class="text-xs text-on-surface-variant line-clamp-1">Incentivos fiscales para la transición a vehículos eléctricos en transporte público.</p>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full">Borrador</span></td>
                            <td class="px-8 py-6"><p class="text-sm text-on-surface">15 Oct 2024</p></td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">LR</div>
                                    <span class="text-sm">L. Rivera</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">visibility</span></button>
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                            </td>
                        </tr>
                        <tr class="group hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">L-2024-075</span></td>
                            <td class="px-8 py-6">
                                <div class="max-w-md">
                                    <p class="font-bold text-on-surface mb-1">Protección de Datos en Entornos Virtuales</p>
                                    <p class="text-xs text-on-surface-variant line-clamp-1">Regulación de la privacidad en el metaverso y plataformas de realidad aumentada.</p>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">Aprobada</span></td>
                            <td class="px-8 py-6"><p class="text-sm text-on-surface">05 Oct 2024</p></td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-tertiary-container flex items-center justify-center text-[10px] font-bold text-on-tertiary-container">JC</div>
                                    <span class="text-sm">J. Castillo</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">visibility</span></button>
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                            </td>
                        </tr>
                        <tr class="group hover:bg-surface-container-low transition-colors">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">L-2024-101</span></td>
                            <td class="px-8 py-6">
                                <div class="max-w-md">
                                    <p class="font-bold text-on-surface mb-1">Ley de Fomento a la Ciberseguridad Nacional</p>
                                    <p class="text-xs text-on-surface-variant line-clamp-1">Creación de la agencia nacional de respuesta ante incidentes críticos.</p>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-error-container text-on-error-container text-xs font-bold rounded-full">Observada</span></td>
                            <td class="px-8 py-6"><p class="text-sm text-on-surface">20 Oct 2024</p></td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-surface-container-highest flex items-center justify-center text-[10px] font-bold text-on-surface-variant">AM</div>
                                    <span class="text-sm">A. Mendoza</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">visibility</span></button>
                                <button class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="px-8 py-5 border-t border-surface-container flex items-center justify-between">
                <span class="text-xs text-on-surface-variant font-medium">Mostrando 1-4 de 142 iniciativas</span>
                <div class="flex items-center gap-2">
                    <button class="w-8 h-8 flex items-center justify-center text-on-surface-variant hover:bg-surface-container rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-xl">chevron_left</span>
                    </button>
                    <button class="w-8 h-8 flex items-center justify-center bg-primary text-white text-xs font-bold rounded-lg shadow-sm">1</button>
                    <button class="w-8 h-8 flex items-center justify-center text-on-surface-variant text-xs font-bold hover:bg-surface-container rounded-lg transition-colors">2</button>
                    <button class="w-8 h-8 flex items-center justify-center text-on-surface-variant text-xs font-bold hover:bg-surface-container rounded-lg transition-colors">3</button>
                    <button class="w-8 h-8 flex items-center justify-center text-on-surface-variant hover:bg-surface-container rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-xl">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- FAB -->
        <button class="fixed bottom-10 right-10 w-16 h-16 bg-gradient-to-br from-primary to-primary-dim text-white rounded-full shadow-2xl flex items-center justify-center transition-transform hover:scale-110 active:scale-95 z-50">
            <span class="material-symbols-outlined text-3xl">add</span>
        </button>
    </div>
</template>

<script setup>
</script>
