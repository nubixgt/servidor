<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <span class="text-primary font-bold text-[10px] tracking-[0.2em] uppercase mb-3 block">Social Media Suite</span>
                <h2 class="text-4xl font-extrabold text-on-surface font-headline tracking-tight">Calendario Editorial</h2>
                <p class="text-on-surface-variant mt-2 text-base">Gestiona mensajes institucionales multiplataforma y flujos de aprobación.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex bg-surface-container p-1 rounded-lg border border-outline-variant/50">
                    <button class="px-5 py-1.5 bg-surface shadow-sm rounded-md text-sm font-bold text-on-surface">Mensual</button>
                    <button class="px-5 py-1.5 text-sm font-medium text-on-surface-variant hover:text-on-surface transition-colors">Semanal</button>
                    <button class="px-5 py-1.5 text-sm font-medium text-on-surface-variant hover:text-on-surface transition-colors">Lista</button>
                </div>
                <button class="flex items-center gap-2 bg-gradient-to-br from-primary to-primary-dim text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:opacity-90 transition-all shadow-md">
                    <span class="material-symbols-outlined text-sm">add_box</span>
                    Nuevo Contenido
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4 py-4 border-b border-outline-variant/50">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Plataforma:</span>
                    <div class="flex gap-2">
                        <button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors"><span class="material-symbols-outlined text-lg">public</span></button>
                        <button class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center shadow-sm"><span class="material-symbols-outlined text-lg">movie</span></button>
                        <button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors"><span class="material-symbols-outlined text-lg">photo_camera</span></button>
                    </div>
                </div>
                <div class="h-6 w-px bg-outline-variant/50"></div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Estado:</span>
                    <select class="bg-transparent border-none text-sm font-bold text-on-surface focus:ring-0 cursor-pointer p-0 outline-none">
                        <option>Todos los Estados</option>
                        <option>Borrador</option>
                        <option>Aprobado</option>
                        <option>Publicado</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button class="w-8 h-8 flex items-center justify-center text-on-surface hover:bg-surface-container rounded-full transition-colors">
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <span class="text-sm font-black text-on-surface font-headline uppercase tracking-widest">Octubre 2023</span>
                <button class="w-8 h-8 flex items-center justify-center text-on-surface hover:bg-surface-container rounded-full transition-colors">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-surface border border-outline-variant/50 rounded-lg overflow-hidden shadow-sm">
            <div class="grid grid-cols-7 border-b border-outline-variant/50 bg-surface-container-lowest">
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Lun</div>
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Mar</div>
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Mie</div>
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Jue</div>
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Vie</div>
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Sab</div>
                <div class="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Dom</div>
            </div>
            <div class="grid grid-cols-7 gap-[1px] bg-outline-variant/50">
                <div class="bg-surface-container-lowest h-44 p-3 opacity-50"><span class="text-xs font-bold font-headline text-on-surface-variant">28</span></div>
                <div class="bg-surface-container-lowest h-44 p-3 opacity-50"><span class="text-xs font-bold font-headline text-on-surface-variant">29</span></div>
                <div class="bg-surface-container-lowest h-44 p-3 opacity-50"><span class="text-xs font-bold font-headline text-on-surface-variant">30</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer group">
                    <span class="text-xs font-bold font-headline text-on-surface">01</span>
                    <div class="mt-2 p-2 bg-surface border border-outline-variant/50 rounded shadow-sm group-hover:border-primary/30 transition-all">
                        <div class="flex items-center justify-between mb-1">
                            <span class="material-symbols-outlined text-[14px] text-on-surface-variant">public</span>
                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant uppercase">Borrador</span>
                        </div>
                        <p class="text-[10px] font-medium leading-tight line-clamp-2 text-on-surface">Anuncio de Asamblea Live stream</p>
                    </div>
                </div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
                    <span class="text-xs font-bold font-headline text-on-surface">02</span>
                    <div class="mt-2 p-2 bg-on-surface text-surface rounded shadow-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="material-symbols-outlined text-[14px]">movie</span>
                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-surface/20 uppercase">Requerido</span>
                        </div>
                        <p class="text-[10px] font-medium leading-tight line-clamp-2">Un Día en el Senado - Video BTS</p>
                    </div>
                </div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">03</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">04</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">05</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
                    <span class="text-xs font-bold font-headline text-on-surface">06</span>
                    <div class="mt-2 p-2 bg-surface border-l-4 border-primary rounded shadow-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="material-symbols-outlined text-[14px] text-primary">photo_camera</span>
                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-primary-container text-on-primary-container uppercase">Aprobado</span>
                        </div>
                        <p class="text-[10px] font-medium leading-tight line-clamp-2 text-on-surface">Infografía Ley de Educación</p>
                    </div>
                </div>
                <div class="bg-surface h-44 p-3 ring-1 ring-inset ring-primary relative">
                    <span class="text-xs font-bold font-headline text-primary">07</span>
                    <div class="mt-2 p-2 bg-primary text-white rounded shadow-md">
                        <div class="flex items-center justify-between mb-1">
                            <span class="material-symbols-outlined text-[14px]">public</span>
                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-white/20 uppercase">Publicado</span>
                        </div>
                        <p class="text-[10px] font-medium leading-tight line-clamp-2">Declaración Política de Seguridad</p>
                    </div>
                </div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">08</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">09</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">10</span></div>
                <div class="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer"><span class="text-xs font-bold font-headline text-on-surface">11</span></div>
            </div>
        </div>
    </div>
</template>
<script setup></script>
