<template>
  <div>
    <div class="flex flex-col xl:flex-row gap-gutter h-[calc(100vh-8rem)]">
        <!-- Left Column: Search & List -->
        <section class="w-full xl:w-1/3 flex flex-col gap-6 h-full">
            <!-- Header & Search -->
            <div class="flex flex-col gap-4">
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="font-headline-lg text-primary tracking-wide font-medium">Gestión de Clientes</h2>
                        <p class="font-body-sm text-on-surface-variant mt-1">Directorio y análisis de cartera</p>
                    </div>
                    <button class="bg-transparent border border-primary text-primary font-body-sm py-2 px-5 rounded-xl hover:bg-primary/10 transition-colors shadow-[0_0_10px_rgba(233,193,118,0.15)] backdrop-blur-sm hidden md:flex items-center gap-2 font-medium">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        Nuevo
                    </button>
                </div>

                <div class="relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm text-body-sm py-3 pl-10 pr-4 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar por Nombre, NIT o DPI..." type="text"/>
                </div>

                <!-- Filters -->
                <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar">
                    <button class="whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container-high/50 backdrop-blur-md text-on-surface font-label-caps text-label-caps border border-white/10 hover:border-primary transition-colors">Todos (245)</button>
                    <button class="whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container-high/30 backdrop-blur-md text-on-surface font-label-caps text-label-caps border border-white/5 hover:border-primary transition-colors flex items-center gap-1 shadow-[0_4px_12px_rgba(0,0,0,0.1)]">
                        <span class="w-2 h-2 rounded-full bg-tertiary-container shadow-[0_0_8px_rgba(143,165,214,0.6)]"></span> Activos
                    </button>
                    <button class="whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container-high/30 backdrop-blur-md text-on-surface font-label-caps text-label-caps border border-white/5 hover:border-primary transition-colors flex items-center gap-1 shadow-[0_4px_12px_rgba(0,0,0,0.1)]">
                        <span class="w-2 h-2 rounded-full bg-error shadow-[0_0_8px_rgba(255,180,171,0.6)]"></span> En Mora
                    </button>
                    <button class="whitespace-nowrap px-4 py-1.5 rounded-full bg-surface-container-high/30 backdrop-blur-md text-on-surface font-label-caps text-label-caps border border-white/5 hover:border-primary transition-colors flex items-center gap-1 shadow-[0_4px_12px_rgba(0,0,0,0.1)]">
                        <span class="w-2 h-2 rounded-full bg-outline"></span> Potenciales
                    </button>
                </div>
            </div>

            <!-- Customer List -->
            <div class="flex-1 bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden flex flex-col min-h-0">
                <div class="overflow-y-auto custom-scrollbar flex-1 p-2 space-y-2">
                    <!-- Selected Customer Item -->
                    <div class="p-4 rounded-2xl bg-surface-container-high/40 backdrop-blur-xl border border-primary shadow-[0_0_15px_rgba(233,193,118,0.2)] cursor-pointer relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-primary shadow-[0_0_10px_rgba(233,193,118,0.8)]"></div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-title-md text-title-md text-on-surface">Roberto Alejandro Muñoz V.</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider bg-tertiary-container/20 text-tertiary-container border border-tertiary-container/30 uppercase">Activo</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">badge</span> DPI: 2938 48392 0101
                                </p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">call</span> +502 4432-1928
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-label-caps text-label-caps text-outline mb-0.5">Score</p>
                                <p class="font-title-md text-title-md text-primary">A-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Other Customer Items -->
                    <div class="p-4 rounded-2xl bg-transparent border border-transparent hover:bg-surface-container-high/30 hover:backdrop-blur-md hover:border-white/5 cursor-pointer transition-all group">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-title-md text-title-md text-on-surface group-hover:text-primary transition-colors">María Fernanda Lázaro</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider bg-error/10 text-error border border-error/20 uppercase">En Mora</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">badge</span> DPI: 3049 11203 0101
                                </p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">call</span> +502 5543-9988
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-label-caps text-label-caps text-outline mb-0.5">Score</p>
                                <p class="font-title-md text-title-md text-error">C</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-transparent border border-transparent hover:bg-surface-container-high/30 hover:backdrop-blur-md hover:border-white/5 cursor-pointer transition-all group">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-title-md text-title-md text-on-surface group-hover:text-primary transition-colors">Constructora Cúspide S.A.</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider bg-outline/20 text-on-surface-variant border border-outline/30 uppercase">Potencial</span>
                        </div>
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">receipt</span> NIT: 984392-K
                                </p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">call</span> +502 2233-4455
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-label-caps text-label-caps text-outline mb-0.5">Score</p>
                                <p class="font-title-md text-title-md text-on-surface-variant">N/A</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right Column: Detail View -->
        <section class="w-full xl:w-2/3 flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 h-full">
            <!-- Action Bar -->
            <div class="flex justify-between items-center bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] shrink-0">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full bg-tertiary-container shadow-[0_0_8px_rgba(143,165,214,0.6)]"></span>
                    <span class="font-label-caps text-label-caps text-on-surface tracking-widest uppercase">Expediente: C-10492</span>
                </div>
                <div class="flex gap-3">
                    <button class="bg-surface-container-high/50 border border-white/10 text-on-surface font-body-sm py-2 px-4 rounded-xl hover:border-primary/50 hover:text-primary transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        Editar Perfil
                    </button>
                    <button class="bg-transparent border border-primary text-primary font-body-sm py-2 px-4 rounded-xl hover:bg-primary/10 transition-colors shadow-[0_0_10px_rgba(233,193,118,0.15)] backdrop-blur-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">history</span>
                        Historial Crediticio
                    </button>
                </div>
            </div>

            <!-- Bento Grid Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 shrink-0">
                <!-- Personal Info Card -->
                <div class="bento-card rounded-2xl p-6 border border-white/5 flex flex-col gap-6 relative overflow-hidden bg-surface-container-high/30 backdrop-blur-xl shadow-[0_8px_32px_rgba(0,0,0,0.2)] hover:-translate-y-0.5 transition-transform">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="flex gap-4 items-start">
                        <div class="w-20 h-20 rounded-2xl bg-surface-container-high/50 border border-white/10 flex items-center justify-center overflow-hidden shrink-0 relative group shadow-inner">
                            <span class="material-symbols-outlined text-4xl text-outline group-hover:hidden">person</span>
                            <div class="absolute inset-0 bg-black/60 hidden group-hover:flex items-center justify-center cursor-pointer transition-all">
                                <span class="material-symbols-outlined text-primary text-xl drop-shadow-[0_0_8px_rgba(233,193,118,0.8)]">upload</span>
                            </div>
                        </div>
                        <div>
                            <h2 class="font-headline-lg text-title-md text-primary mb-1">Roberto Alejandro Muñoz Villalobos</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mb-2">Ingeniero Civil / Contratista Independiente</p>
                            <div class="flex gap-2">
                                <span class="px-2 py-0.5 rounded-lg bg-surface-container-high/30 border border-white/5 text-[11px] text-on-surface-variant uppercase tracking-wider">Casado</span>
                                <span class="px-2 py-0.5 rounded-lg bg-surface-container-high/30 border border-white/5 text-[11px] text-on-surface-variant uppercase tracking-wider">Guatemalteco</span>
                            </div>
                        </div>
                    </div>

                    <hr class="border-white/10"/>
                    
                    <div class="grid grid-cols-2 gap-y-4 gap-x-2">
                        <div>
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">DPI</p>
                            <p class="font-body-sm text-body-sm text-on-surface">2938 48392 0101</p>
                        </div>
                        <div>
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">NIT</p>
                            <p class="font-body-sm text-body-sm text-on-surface">492019-K</p>
                        </div>
                        <div class="col-span-2">
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">Dirección Residencia</p>
                            <p class="font-body-sm text-body-sm text-on-surface leading-tight">14 Avenida 3-45 Zona 10, Edificio Las Margaritas, Apto 4B. Guatemala, Guatemala.</p>
                        </div>
                        <div>
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">Teléfono</p>
                            <p class="font-body-sm text-body-sm text-on-surface">+502 4432-1928</p>
                        </div>
                        <div>
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">Correo Electrónico</p>
                            <p class="font-body-sm text-body-sm text-on-surface truncate" title="roberto.munoz@constructora-rm.com">roberto.munoz@constructora-rm.com</p>
                        </div>
                    </div>
                </div>

                <!-- Financial & Risk Summary Card -->
                <div class="flex flex-col gap-6">
                    <!-- Risk Score Small Card -->
                    <div class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] flex items-center justify-between hover:-translate-y-0.5 transition-transform">
                        <div>
                            <h3 class="font-label-caps text-label-caps text-outline mb-2 uppercase tracking-widest">Nivel de Riesgo Crediticio</h3>
                            <div class="flex items-end gap-3">
                                <span class="font-display-lg text-[40px] text-primary leading-none drop-shadow-[0_0_15px_rgba(233,193,118,0.5)]">A-</span>
                                <span class="font-body-sm text-body-sm text-tertiary-container mb-1 flex items-center drop-shadow-[0_0_8px_rgba(143,165,214,0.5)]"><span class="material-symbols-outlined text-[16px] mr-1">trending_up</span> Estable</span>
                            </div>
                        </div>
                        <!-- Abstract sparkline -->
                        <div class="w-24 h-16 border-b border-l border-surface-variant relative">
                            <svg class="absolute bottom-0 left-0 w-full h-full" preserveaspectratio="none" viewbox="0 0 100 40">
                                <path d="M0,35 L20,30 L40,35 L60,15 L80,20 L100,5" fill="none" stroke="#e9c176" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                <path d="M0,35 L20,30 L40,35 L60,15 L80,20 L100,5 L100,40 L0,40 Z" fill="url(#grad)" opacity="0.2"></path>
                                <defs>
                                    <lineargradient id="grad" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#e9c176"></stop>
                                        <stop offset="100%" stop-color="transparent"></stop>
                                    </lineargradient>
                                </defs>
                            </svg>
                        </div>
                    </div>

                    <!-- Financial Summary Card -->
                    <div class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] flex-1 hover:-translate-y-0.5 transition-transform">
                        <h3 class="font-title-md text-title-md text-on-surface mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">account_balance_wallet</span>
                            Perfil Financiero Declarado
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-body-sm font-body-sm mb-1">
                                    <span class="text-on-surface-variant">Ingresos Mensuales Promedio</span>
                                    <span class="text-on-surface font-semibold">Q. 45,000.00</span>
                                </div>
                                <div class="w-full bg-surface-container-highest rounded-full h-1.5">
                                    <div class="bg-primary h-1.5 rounded-full" style="width: 80%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-body-sm font-body-sm mb-1">
                                    <span class="text-on-surface-variant">Egresos Fijos Declarados</span>
                                    <span class="text-on-surface font-semibold">Q. 18,500.00</span>
                                </div>
                                <div class="w-full bg-surface-container-highest rounded-full h-1.5">
                                    <div class="bg-error h-1.5 rounded-full" style="width: 35%"></div>
                                </div>
                            </div>

                            <hr class="border-white/10 my-4"/>

                            <div>
                                <h4 class="font-label-caps text-[10px] text-outline mb-3 uppercase tracking-widest">Referencias Validadas</h4>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-tertiary-container text-[18px] mt-0.5" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                        <div>
                                            <p class="font-body-sm text-body-sm text-on-surface leading-tight">Banco Industrial S.A. (Comercial)</p>
                                            <p class="font-body-sm text-[12px] text-on-surface-variant">Cuenta activa, buen manejo.</p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-tertiary-container text-[18px] mt-0.5" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                        <div>
                                            <p class="font-body-sm text-body-sm text-on-surface leading-tight">Lic. Carlos Méndez (Personal)</p>
                                            <p class="font-body-sm text-[12px] text-on-surface-variant">Confirmado 12/10/2023</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Management Section -->
            <div class="col-span-1 md:col-span-2 bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] shrink-0 hover:-translate-y-0.5 transition-transform mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-title-md text-title-md text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">folder_open</span>
                        Gestor Documental
                    </h3>
                    <button class="text-primary hover:text-primary-fixed-dim font-body-sm text-sm transition-colors">Ver todos</button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <!-- Doc Items -->
                    <div class="bg-surface-container-high/30 rounded-xl border border-white/5 p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:shadow-[0_0_15px_rgba(233,193,118,0.15)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-3xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all" style="font-variation-settings: 'FILL' 1;">id_card</span>
                        <div class="text-center w-full">
                            <p class="font-label-caps text-[10px] text-on-surface uppercase truncate">DPI - Frente</p>
                            <p class="font-body-sm text-[10px] text-tertiary-container mt-0.5">Validado</p>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-high/30 rounded-xl border border-white/5 p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:shadow-[0_0_15px_rgba(233,193,118,0.15)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-3xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all" style="font-variation-settings: 'FILL' 1;">id_card</span>
                        <div class="text-center w-full">
                            <p class="font-label-caps text-[10px] text-on-surface uppercase truncate">DPI - Reverso</p>
                            <p class="font-body-sm text-[10px] text-tertiary-container mt-0.5">Validado</p>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-high/30 rounded-xl border border-white/5 p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:shadow-[0_0_15px_rgba(233,193,118,0.15)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-3xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all" style="font-variation-settings: 'FILL' 1;">receipt</span>
                        <div class="text-center w-full">
                            <p class="font-label-caps text-[10px] text-on-surface uppercase truncate">Recibo Luz</p>
                            <p class="font-body-sm text-[10px] text-tertiary-container mt-0.5">Validado</p>
                        </div>
                    </div>
                    
                    <div class="bg-surface-container-high/30 rounded-xl border border-white/5 p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:shadow-[0_0_15px_rgba(233,193,118,0.15)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-3xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all" style="font-variation-settings: 'FILL' 1;">description</span>
                        <div class="text-center w-full">
                            <p class="font-label-caps text-[10px] text-on-surface uppercase truncate">Contrato Base</p>
                            <p class="font-body-sm text-[10px] text-tertiary-container mt-0.5">Firmado</p>
                        </div>
                    </div>

                    <!-- Add New Doc -->
                    <div class="bg-surface-container-high/10 border border-dashed border-white/20 rounded-xl p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:bg-primary/5 hover:shadow-[0_0_15px_rgba(233,193,118,0.1)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-2xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all">add_circle</span>
                        <p class="font-label-caps text-[10px] text-outline group-hover:text-primary transition-all uppercase">Añadir Doc</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #131313; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #353535; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e9c176; }
</style>
