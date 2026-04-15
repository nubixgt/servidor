<template>
    <div class="space-y-8 h-full flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-start mb-12">
            <div>
                <span class="text-[10px] font-bold text-primary uppercase tracking-[0.25em] mb-2 block">Workspace Control</span>
                <h2 class="text-4xl font-extrabold text-on-surface tracking-tighter font-headline">Gestión de Tareas</h2>
            </div>
            <div class="flex items-center gap-2">
                <button class="flex items-center gap-2 px-5 py-2.5 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg hover:bg-surface-container-high transition-all border border-outline-variant/20 uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> PDF
                </button>
                <button class="flex items-center gap-2 px-5 py-2.5 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg hover:bg-surface-container-high transition-all border border-outline-variant/20 uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[16px]">description</span> Excel
                </button>
                <button class="flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 uppercase tracking-widest ml-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Nueva Tarea
                </button>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl mb-12 border border-outline-variant/30 flex flex-wrap gap-8 items-end shadow-sm">
            <div class="flex-1 min-w-[200px]">
                <p class="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Filtrar por Módulo</p>
                <select class="w-full bg-surface-container-low border-none text-sm rounded-lg py-2.5 px-4 focus:ring-1 focus:ring-primary/30 appearance-none font-medium text-on-surface-variant outline-none">
                    <option>Todos los Módulos</option>
                    <option>Legislación</option>
                    <option>Constituyentes</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <p class="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Responsable</p>
                <select class="w-full bg-surface-container-low border-none text-sm rounded-lg py-2.5 px-4 focus:ring-1 focus:ring-primary/30 appearance-none font-medium text-on-surface-variant outline-none">
                    <option>Cualquier Responsable</option>
                    <option>Senior Advisor</option>
                    <option>Staff Lead</option>
                </select>
            </div>
            <div class="flex-[1.5] min-w-[200px]">
                <p class="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Prioridad</p>
                <div class="flex gap-2">
                    <button class="flex-1 py-2 px-4 bg-surface-container-low text-[11px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider">Alta</button>
                    <button class="flex-1 py-2 px-4 bg-surface-container-low text-[11px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider">Media</button>
                    <button class="flex-1 py-2 px-4 bg-surface-container-low text-[11px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider">Baja</button>
                </div>
            </div>
            <button class="p-2.5 text-on-surface-variant hover:bg-surface-container rounded-lg transition-all border border-outline-variant/30">
                <span class="material-symbols-outlined">filter_list</span>
            </button>
        </div>

        <!-- Kanban Grid -->
        <div class="flex gap-8 overflow-x-auto pb-8 -mx-2 px-2 scroll-smooth flex-1">

            <!-- Column: Pendiente -->
            <div class="flex flex-col gap-6 flex-shrink-0 w-[300px]">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
                        Pendiente
                        <span class="text-[10px] bg-outline-variant/30 px-2 py-0.5 rounded-full text-on-surface-variant">3</span>
                    </h3>
                    <span class="material-symbols-outlined text-outline/50 cursor-pointer hover:text-outline">more_horiz</span>
                </div>
                <div class="flex flex-col gap-4 min-h-[500px]">
                    <div class="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] font-bold px-2 py-0.5 bg-tertiary-container text-on-tertiary-container rounded uppercase tracking-tighter">Alta</span>
                            <span class="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
                        </div>
                        <h4 class="text-sm font-bold text-on-surface mb-6 leading-relaxed">Revisión de Enmienda Presupuestaria Q3</h4>
                        <div class="flex items-center gap-2 mb-6">
                            <img class="w-6 h-6 rounded-full object-cover" alt="Carlos Méndez" src="https://i.pravatar.cc/50?img=11" />
                            <span class="text-[10px] text-outline font-medium">Carlos Méndez</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-outline-variant/20">
                            <div class="flex items-center gap-1.5 text-outline">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <span class="text-[10px] font-bold">15 OCT</span>
                            </div>
                            <div class="text-[10px] font-black text-primary/40">0%</div>
                        </div>
                    </div>
                    <div class="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] font-bold px-2 py-0.5 bg-primary-container text-on-primary-container rounded uppercase tracking-tighter">Baja</span>
                            <span class="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
                        </div>
                        <h4 class="text-sm font-bold text-on-surface mb-6 leading-relaxed">Actualización de Directorio de Comisiones</h4>
                        <div class="flex items-center gap-2 mb-6">
                            <img class="w-6 h-6 rounded-full object-cover" alt="Elena Rivas" src="https://i.pravatar.cc/50?img=5" />
                            <span class="text-[10px] text-outline font-medium">Elena Rivas</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-outline-variant/20">
                            <div class="flex items-center gap-1.5 text-outline">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <span class="text-[10px] font-bold">22 OCT</span>
                            </div>
                            <div class="text-[10px] font-black text-primary/40">0%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column: En Proceso -->
            <div class="flex flex-col gap-6 flex-shrink-0 w-[300px]">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
                        En Proceso
                        <span class="text-[10px] bg-tertiary-container/30 px-2 py-0.5 rounded-full text-on-tertiary-container">2</span>
                    </h3>
                    <span class="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
                </div>
                <div class="flex flex-col gap-4 min-h-[500px]">
                    <div class="group bg-surface-container-lowest p-5 rounded-xl border border-primary/30 shadow-sm transition-all cursor-grab active:cursor-grabbing">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] font-bold px-2 py-0.5 bg-secondary-container text-on-secondary-container rounded uppercase tracking-tighter">Media</span>
                            <span class="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
                        </div>
                        <h4 class="text-sm font-bold text-on-surface mb-6 leading-relaxed">Redacción Informe de Impacto Ambiental</h4>
                        <div class="flex items-center gap-2 mb-6">
                            <img class="w-6 h-6 rounded-full object-cover" alt="Senior Advisor" src="https://i.pravatar.cc/50?img=3" />
                            <span class="text-[10px] text-outline font-medium">Senior Advisor</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-1 mb-6 overflow-hidden">
                            <div class="bg-primary h-full rounded-full" style="width: 65%"></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1.5 text-outline">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <span class="text-[10px] font-bold">12 OCT</span>
                            </div>
                            <div class="text-[10px] font-black text-primary">65%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column: En Revisión -->
            <div class="flex flex-col gap-6 flex-shrink-0 w-[300px]">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
                        En Revisión
                        <span class="text-[10px] bg-secondary-container/30 px-2 py-0.5 rounded-full text-on-secondary-container">1</span>
                    </h3>
                    <span class="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
                </div>
                <div class="flex flex-col gap-4 min-h-[500px]">
                    <div class="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] font-bold px-2 py-0.5 bg-tertiary-container text-on-tertiary-container rounded uppercase tracking-tighter">Alta</span>
                            <span class="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
                        </div>
                        <h4 class="text-sm font-bold text-on-surface mb-6 leading-relaxed">Aprobación de Agenda de Sesión Plenaria</h4>
                        <div class="flex items-center gap-2 mb-6">
                            <img class="w-6 h-6 rounded-full object-cover" alt="Marta Soler" src="https://i.pravatar.cc/50?img=9" />
                            <span class="text-[10px] text-outline font-medium">Marta Soler</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-1 mb-6 overflow-hidden">
                            <div class="bg-primary h-full rounded-full" style="width: 90%"></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1.5 text-outline">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <span class="text-[10px] font-bold uppercase">Hoy</span>
                            </div>
                            <div class="text-[10px] font-black text-primary">90%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column: Completada -->
            <div class="flex flex-col gap-6 flex-shrink-0 w-[300px]">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
                        Completada
                        <span class="text-[10px] bg-primary-container px-2 py-0.5 rounded-full text-on-primary-container">12</span>
                    </h3>
                    <span class="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
                </div>
                <div class="flex flex-col gap-4 min-h-[500px]">
                    <div class="group bg-surface-container p-5 rounded-xl border border-transparent opacity-60">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] font-bold px-2 py-0.5 bg-secondary-container text-on-secondary-container rounded uppercase tracking-tighter">Media</span>
                            <span class="material-symbols-outlined text-primary text-[18px]">check_circle</span>
                        </div>
                        <h4 class="text-sm font-bold text-on-surface mb-6 leading-relaxed line-through decoration-outline/50">Respuesta a Solicitud de Transparencia #442</h4>
                        <div class="flex items-center gap-2 mb-6">
                            <img class="w-6 h-6 rounded-full object-cover opacity-50" alt="Carlos Méndez" src="https://i.pravatar.cc/50?img=11" />
                            <span class="text-[10px] text-outline font-medium">Carlos Méndez</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1.5 text-primary">
                                <span class="material-symbols-outlined text-[14px]">done_all</span>
                                <span class="text-[10px] font-bold uppercase tracking-tighter">Finalizada</span>
                            </div>
                            <div class="text-[10px] font-black text-primary">100%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column: Vencida -->
            <div class="flex flex-col gap-6 flex-shrink-0 w-[300px]">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold text-error uppercase tracking-widest flex items-center gap-3">
                        Vencida
                        <span class="text-[10px] bg-error-container px-2 py-0.5 rounded-full text-on-error-container">1</span>
                    </h3>
                    <span class="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
                </div>
                <div class="flex flex-col gap-4 min-h-[500px]">
                    <div class="group bg-error-container/5 p-5 rounded-xl border border-error/20 hover:border-error shadow-sm transition-all cursor-grab active:cursor-grabbing">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[9px] font-bold px-2 py-0.5 bg-error-container text-on-error-container rounded uppercase tracking-tighter">Alta</span>
                            <span class="material-symbols-outlined text-error text-[18px]">priority_high</span>
                        </div>
                        <h4 class="text-sm font-bold text-error mb-6 leading-relaxed">Presentación de Informe Anual 2023</h4>
                        <div class="flex items-center gap-2 mb-6">
                            <img class="w-6 h-6 rounded-full object-cover" alt="Elena Rivas" src="https://i.pravatar.cc/50?img=5" />
                            <span class="text-[10px] text-outline font-medium">Elena Rivas</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-error/10">
                            <div class="flex items-center gap-1.5 text-error">
                                <span class="material-symbols-outlined text-[14px]">event_busy</span>
                                <span class="text-[10px] font-bold uppercase">Ayer</span>
                            </div>
                            <div class="text-[10px] font-black text-error">40%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup></script>
