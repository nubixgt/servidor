<template>
    <div class="space-y-10">
        <!-- Header Section -->
        <header class="mb-10 flex flex-col md:flex-row md:justify-between md:items-end gap-6">
            <div class="max-w-2xl">
                <h1 class="text-4xl font-extrabold text-on-surface tracking-tight mb-2 font-headline">Dashboard Técnico</h1>
                <p class="text-on-surface-variant leading-relaxed">Bienvenido, Técnico. Aquí tienes el estado actual de tu categoría asignada.</p>
            </div>
            <div class="flex space-x-4">
                <div class="bg-surface-container-low px-4 py-2 rounded-xl flex items-center space-x-3">
                    <span class="material-symbols-outlined text-primary">calendar_today</span>
                    <span class="text-sm font-semibold">Martes, 24 Oct 2023</span>
                </div>
            </div>
        </header>

        <!-- Bento Grid -->
        <div class="grid grid-cols-12 gap-6">

            <!-- Personal KPIs -->
            <section class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-ambient flex flex-col justify-between h-full min-h-[400px]">
                    <div>
                        <h3 class="text-lg font-bold mb-6 flex items-center space-x-2 font-headline">
                            <span class="material-symbols-outlined text-primary">assignment_ind</span>
                            <span>Mis Tareas del Día</span>
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <span class="h-2 w-2 rounded-full bg-error"></span>
                                    <span class="text-sm font-medium">Revisión de Auditoría #42</span>
                                </div>
                                <span class="text-xs font-bold text-on-surface-variant">09:00</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <span class="h-2 w-2 rounded-full bg-primary"></span>
                                    <span class="text-sm font-medium">Firma de Certificados</span>
                                </div>
                                <span class="text-xs font-bold text-on-surface-variant">11:30</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl opacity-60 line-through">
                                <div class="flex items-center space-x-3">
                                    <span class="h-2 w-2 rounded-full bg-outline"></span>
                                    <span class="text-sm font-medium">Sincronización Equipo</span>
                                </div>
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 pt-8 border-t border-surface-container">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Avance Semanal</span>
                            <span class="text-2xl font-black text-primary font-headline">72%</span>
                        </div>
                        <div class="w-full bg-primary-container h-3 rounded-full overflow-hidden">
                            <div class="bg-primary h-full rounded-full" style="width: 72%"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Module Status -->
            <section class="col-span-12 lg:col-span-8 bg-surface-container p-8 rounded-3xl space-y-8">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold mb-1 font-headline">Módulo: Fiscalización</h2>
                        <p class="text-sm text-on-surface-variant">Indicadores de rendimiento de área en tiempo real</p>
                    </div>
                    <span class="bg-primary-container text-on-primary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">Estado: Operativo</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-surface-container-lowest p-6 rounded-2xl flex flex-col">
                        <span class="text-xs font-semibold text-on-surface-variant mb-4">Inspecciones Activas</span>
                        <div class="flex items-end space-x-2">
                            <span class="text-4xl font-black font-headline">128</span>
                            <span class="text-error text-xs font-bold flex items-center mb-1">
                                <span class="material-symbols-outlined text-sm">arrow_upward</span> 12%
                            </span>
                        </div>
                    </div>
                    <div class="bg-surface-container-lowest p-6 rounded-2xl flex flex-col">
                        <span class="text-xs font-semibold text-on-surface-variant mb-4">Eficiencia de Cierre</span>
                        <div class="flex items-end space-x-2">
                            <span class="text-4xl font-black font-headline">94.2%</span>
                            <span class="text-primary text-xs font-bold flex items-center mb-1">
                                <span class="material-symbols-outlined text-sm">trending_up</span> 0.5
                            </span>
                        </div>
                    </div>
                    <div class="bg-surface-container-lowest p-6 rounded-2xl flex flex-col">
                        <span class="text-xs font-semibold text-on-surface-variant mb-4">Alertas de Módulo</span>
                        <div class="flex items-end space-x-2">
                            <span class="text-4xl font-black font-headline">03</span>
                            <span class="bg-error-container text-on-error-container px-2 py-0.5 rounded text-[10px] font-bold mb-1 ml-2">URGENTE</span>
                        </div>
                    </div>
                </div>

                <!-- Chart Mockup -->
                <div class="bg-surface-container-lowest rounded-2xl p-6 h-64 relative overflow-hidden flex items-end justify-between space-x-2">
                    <div class="absolute top-6 left-6 flex space-x-4 items-center">
                        <span class="text-sm font-bold">Distribución de Recursos</span>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-primary"></span>
                            <span class="text-[10px] text-on-surface-variant font-medium">Terreno</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-secondary"></span>
                            <span class="text-[10px] text-on-surface-variant font-medium">Gabinete</span>
                        </div>
                    </div>
                    <div class="w-full bg-primary/10 rounded-t-lg" style="height: 8rem"></div>
                    <div class="w-full bg-primary/30 rounded-t-lg" style="height: 12rem"></div>
                    <div class="w-full bg-primary rounded-t-lg" style="height: 6rem"></div>
                    <div class="w-full bg-primary/20 rounded-t-lg" style="height: 14rem"></div>
                    <div class="w-full bg-primary/60 rounded-t-lg" style="height: 10rem"></div>
                    <div class="w-full bg-primary/40 rounded-t-lg" style="height: 8rem"></div>
                    <div class="w-full bg-primary rounded-t-lg" style="height: 11rem"></div>
                    <div class="w-full bg-primary/10 rounded-t-lg" style="height: 7rem"></div>
                    <div class="w-full bg-primary/70 rounded-t-lg" style="height: 13rem"></div>
                </div>
            </section>

            <!-- Pending Tasks -->
            <section class="col-span-12 lg:col-span-8 bg-surface-container-lowest p-8 rounded-3xl shadow-ambient">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xl font-bold flex items-center space-x-2 font-headline">
                        <span class="material-symbols-outlined text-error">priority_high</span>
                        <span>Acción Inmediata (Módulo)</span>
                    </h3>
                    <button class="text-primary text-sm font-bold hover:underline">Ver todas</button>
                </div>
                <div class="space-y-1">
                    <div class="group flex items-center p-4 hover:bg-surface-container-low rounded-2xl transition-all cursor-pointer">
                        <div class="w-12 h-12 bg-error/10 text-error rounded-xl flex items-center justify-center mr-4">
                            <span class="material-symbols-outlined">gavel</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-sm">Denuncia Ciudadana #8819</h4>
                            <p class="text-xs text-on-surface-variant">Obstrucción de vía pública - Zona Norte</p>
                        </div>
                        <div class="text-right flex items-center space-x-6">
                            <div class="hidden md:block">
                                <p class="text-xs font-bold text-on-surface">Vence en 2h</p>
                                <p class="text-[10px] text-error font-medium italic">Prioridad Crítica</p>
                            </div>
                            <span class="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
                        </div>
                    </div>
                    <div class="group flex items-center p-4 hover:bg-surface-container-low rounded-2xl transition-all cursor-pointer">
                        <div class="w-12 h-12 bg-tertiary-container text-on-tertiary-container rounded-xl flex items-center justify-center mr-4">
                            <span class="material-symbols-outlined">contract</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-sm">Validación de Licencia Comercial</h4>
                            <p class="text-xs text-on-surface-variant">Expediente 2023-LC-451 - Pendiente firma Resp.</p>
                        </div>
                        <div class="text-right flex items-center space-x-6">
                            <div class="hidden md:block">
                                <p class="text-xs font-bold text-on-surface">Vence hoy</p>
                                <p class="text-[10px] text-on-surface-variant font-medium">Pendiente Firma</p>
                            </div>
                            <span class="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
                        </div>
                    </div>
                    <div class="group flex items-center p-4 hover:bg-surface-container-low rounded-2xl transition-all cursor-pointer">
                        <div class="w-12 h-12 bg-secondary-container text-on-secondary-container rounded-xl flex items-center justify-center mr-4">
                            <span class="material-symbols-outlined">feedback</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-sm">Descargo de Infracción #221</h4>
                            <p class="text-xs text-on-surface-variant">Revisión de pruebas presentadas por el titular</p>
                        </div>
                        <div class="text-right flex items-center space-x-6">
                            <div class="hidden md:block">
                                <p class="text-xs font-bold text-on-surface">Vence mañana</p>
                                <p class="text-[10px] text-on-surface-variant font-medium">En cola de revisión</p>
                            </div>
                            <span class="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Calendar & Events -->
            <section class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-surface-container-low p-8 rounded-3xl flex flex-col h-full">
                    <h3 class="text-lg font-bold mb-6 flex items-center space-x-2 font-headline">
                        <span class="material-symbols-outlined text-primary">event</span>
                        <span>Próximas Citaciones</span>
                    </h3>
                    <div class="space-y-6">
                        <div class="relative pl-6 border-l-2 border-primary">
                            <span class="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-primary ring-4 ring-surface-container-low"></span>
                            <p class="text-[10px] font-bold text-primary uppercase tracking-tighter">Mañana • 10:00 AM</p>
                            <h5 class="text-sm font-bold mt-1">Audiencia de Conciliación</h5>
                            <p class="text-xs text-on-surface-variant">Sala 4 - Caso #299-A</p>
                        </div>
                        <div class="relative pl-6 border-l-2 border-outline-variant/30">
                            <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-tighter">Jueves • 09:30 AM</p>
                            <h5 class="text-sm font-bold mt-1">Reunión de Gabinete</h5>
                            <p class="text-xs text-on-surface-variant">Virtual - Reporte Mensual</p>
                        </div>
                        <div class="relative pl-6 border-l-2 border-outline-variant/30">
                            <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-tighter">Viernes • 15:00 PM</p>
                            <h5 class="text-sm font-bold mt-1">Inspección In Situ</h5>
                            <p class="text-xs text-on-surface-variant">Complejo Industrial Norte</p>
                        </div>
                    </div>
                    <div class="mt-auto pt-10">
                        <div class="rounded-2xl overflow-hidden relative h-32 group cursor-pointer">
                            <img alt="Bureau Workspace" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=400&h=200" referrerpolicy="no-referrer" />
                            <div class="absolute inset-0 bg-primary/40 flex flex-col items-center justify-center text-white">
                                <p class="text-[10px] font-bold uppercase tracking-widest">Calendario Completo</p>
                                <span class="material-symbols-outlined text-2xl mt-1">open_in_new</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Floating Action -->
        <div class="fixed bottom-10 right-10 flex flex-col space-y-4 z-50">
            <button class="w-14 h-14 bg-surface-container-lowest shadow-ambient rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all duration-300">
                <span class="material-symbols-outlined">chat_bubble</span>
            </button>
            <button class="w-14 h-14 bg-primary shadow-ambient rounded-full flex items-center justify-center text-white hover:scale-110 transition-all duration-300">
                <span class="material-symbols-outlined">add</span>
            </button>
        </div>
    </div>
</template>

<script setup>
</script>
