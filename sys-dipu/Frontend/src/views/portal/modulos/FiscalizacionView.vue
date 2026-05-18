<template>
    <div class="relative min-h-full">
        <!-- Fondo interactivo que cubre el área de contenido -->
        <div 
            class="fixed inset-0 pointer-events-none transition-colors duration-1000 z-0"
            :class="activeTab === 'presupuesto' ? 'bg-sky-200' : 'bg-transparent'"
        ></div>
        
        <div class="relative z-10 space-y-8">
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-6">
            <div class="max-w-3xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Centro de Fiscalización del Ejecutivo</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Tablero integral para seguimiento político, presupuestario, documental y mediático por ministerio, secretaría, entidad y comisión.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest">
                    <span class="material-symbols-outlined text-xl">upload</span> Cargar documento
                </button>
                <button class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl">
                    <span class="material-symbols-outlined text-xl">notifications_active</span> Activar alertas
                </button>
            </div>
        </header>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-surface rounded-2xl p-6 flex flex-col justify-between border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-on-surface-variant uppercase tracking-widest">Presupuesto</p>
                    <span class="material-symbols-outlined text-outline">account_balance_wallet</span>
                </div>
                <p class="text-3xl font-extrabold text-on-surface font-headline">Q{{ totalPresupuesto.toLocaleString() }} M</p>
            </div>
            <div class="bg-surface rounded-2xl p-6 flex flex-col justify-between border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-on-surface-variant uppercase tracking-widest">Prom. Ejecución</p>
                    <span class="material-symbols-outlined text-outline">analytics</span>
                </div>
                <p class="text-3xl font-extrabold text-on-surface font-headline">{{ avgEjecucion }}%</p>
            </div>
            <div class="bg-surface rounded-2xl p-6 flex flex-col justify-between border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-on-surface-variant uppercase tracking-widest">Documentos</p>
                    <span class="material-symbols-outlined text-outline">folder_open</span>
                </div>
                <p class="text-3xl font-extrabold text-on-surface font-headline">{{ totalDocs }}</p>
            </div>
            <div class="bg-surface rounded-2xl p-6 flex flex-col justify-between border border-error/30 bg-error-container/10 shadow-[0_1px_3px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-error uppercase tracking-widest">Alertas Críticas</p>
                    <span class="material-symbols-outlined text-error">warning</span>
                </div>
                <p class="text-3xl font-extrabold text-error font-headline">{{ totalAlertas }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-surface p-6 rounded-2xl border border-outline-variant/20 flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">search</span>
                <input v-model="query" type="text" placeholder="Buscar ministerio, hallazgo o tema sensible" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 transition-all text-on-surface" />
            </div>
            <select v-model="selected" class="py-3 px-4 bg-surface-container-low border-none rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer font-semibold text-on-surface">
                <option value="todos">Todas las entidades</option>
                <option value="MICIVI">MICIVI</option>
                <option value="MSPAS">MSPAS</option>
                <option value="MINEDUC">MINEDUC</option>
                <option value="MIDES">MIDES</option>
            </select>
        </div>

        <!-- Tabs -->
        <div class="flex overflow-x-auto gap-2 border-b-2 border-surface-container-low pb-2 scrollbar-hide">
            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                'px-6 py-2.5 rounded-xl font-bold text-sm whitespace-nowrap transition-all duration-300',
                activeTab === tab.id ? 'bg-primary text-white shadow-md' : 'bg-transparent text-on-surface-variant hover:bg-surface-container-lowest border border-transparent'
            ]">{{ tab.label }}</button>
        </div>

        <!-- Tab Content -->
        <!-- Tab: Ministerios -->
        <div v-if="activeTab === 'ministerios'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="m in filteredMinistries" :key="m.id" class="bg-surface rounded-2xl p-6 border border-outline-variant/20 shadow-sm flex flex-col hover:border-primary/30 transition-colors">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="font-extrabold text-xl text-on-surface font-headline">{{ m.short }}</h3>
                            <p class="text-xs font-semibold text-on-surface-variant line-clamp-1 mt-1" :title="m.name">{{ m.name }}</p>
                        </div>
                        <span :class="['px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border', riskColorClass(m.riesgo)]">{{ m.riesgo }}</span>
                    </div>
                    
                    <div class="mb-6">
                        <div class="flex justify-between text-xs mb-2">
                            <span class="text-on-surface-variant font-bold uppercase tracking-wider text-[10px]">Ejecución Presup.</span>
                            <span class="font-black text-on-surface">{{ m.ejecucion }}%</span>
                        </div>
                        <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden">
                            <div class="bg-primary h-full rounded-full" :style="{ width: m.ejecucion + '%' }"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-surface-container-lowest border border-outline-variant/10 p-3 rounded-xl">
                            <p class="text-[9px] text-on-surface-variant uppercase font-bold tracking-widest mb-1">Presupuesto</p>
                            <p class="font-extrabold text-sm text-on-surface">Q{{ m.presupuesto.toLocaleString() }} M</p>
                        </div>
                        <div class="bg-surface-container-lowest border border-outline-variant/10 p-3 rounded-xl">
                            <p class="text-[9px] text-on-surface-variant uppercase font-bold tracking-widest mb-1">Personal</p>
                            <p class="font-extrabold text-sm text-on-surface">{{ m.empleados.toLocaleString() }}</p>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <p class="text-[10px] text-on-surface-variant uppercase font-bold tracking-widest mb-3 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-error"></span> Líneas de ataque</p>
                        <ul class="space-y-1.5">
                            <li v-for="(h, i) in m.hallazgos" :key="i" class="text-xs bg-error-container/10 border border-error/10 p-2 rounded-lg text-on-surface-variant font-medium flex items-start gap-2 leading-tight">
                                <span class="material-symbols-outlined text-[14px] text-error shrink-0">emergency</span> {{ h }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Autoridades -->
        <div v-if="activeTab === 'autoridades'" class="space-y-8 animate-in fade-in slide-in-from-bottom-2 duration-500">

            <!-- Hero Header -->
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-primary via-primary-dim to-secondary p-8 shadow-xl shadow-primary/20">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 40px 40px;"></div>
                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-xl">account_balance</span>
                            </div>
                            <span class="text-white/70 text-sm font-bold uppercase tracking-widest">Fiscalización · Autoridades</span>
                        </div>
                        <h2 class="text-3xl font-extrabold text-white font-headline leading-tight">Directorio Ministerial</h2>
                        <p class="text-white/70 text-sm mt-1">Registro de autoridades y funcionarios por ministerio</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-center bg-white/10 backdrop-blur rounded-2xl px-5 py-3">
                            <p class="text-2xl font-extrabold text-white">{{ ministries.length }}</p>
                            <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest">Ministerios</p>
                        </div>
                        <div class="text-center bg-white/10 backdrop-blur rounded-2xl px-5 py-3">
                            <p class="text-2xl font-extrabold text-white">{{ totalPersonalRegistrado }}</p>
                            <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest">Registrados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de Ministerios -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div v-for="(m, mIdx) in ministries" :key="'aut-'+m.id"
                    class="group bg-surface rounded-3xl border border-outline-variant/15 shadow-sm overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
                    :style="`--card-hue: ${ministryHue(mIdx)}`">

                    <!-- Banner con gradiente de color único por ministerio -->
                    <div class="relative h-24 flex items-end pb-4 px-6 overflow-hidden"
                        :style="`background: linear-gradient(135deg, hsl(${ministryHue(mIdx)}, 60%, 35%) 0%, hsl(${ministryHue(mIdx)}, 45%, 22%) 100%)`">
                        <!-- Watermark acronym -->
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[4rem] font-black text-white/10 leading-none tracking-tight select-none pointer-events-none">{{ m.short }}</span>
                        <!-- Glow blob -->
                        <div class="absolute top-0 left-0 w-32 h-32 rounded-full blur-3xl opacity-30"
                            :style="`background: hsl(${ministryHue(mIdx)}, 80%, 70%)`"></div>
                        <!-- Ministry info -->
                        <div class="relative z-10 flex items-center justify-between w-full">
                            <div>
                                <h3 class="font-extrabold text-xl text-white font-headline leading-tight">{{ m.short }}</h3>
                                <p class="text-white/60 text-[11px] font-medium leading-snug max-w-[200px]">{{ m.name }}</p>
                            </div>
                            <button @click="abrirModal(m)"
                                class="flex items-center gap-1.5 px-3.5 py-2 bg-white/15 hover:bg-white/30 backdrop-blur text-white text-[11px] font-bold rounded-xl transition-all border border-white/20 hover:border-white/40 shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">person_add</span>
                                Agregar
                            </button>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">

                        <!-- Ministro Titular -->
                        <div class="flex items-center gap-4 p-4 rounded-2xl relative overflow-hidden transition-all"
                            :style="`background: hsl(${ministryHue(mIdx)}, 50%, 96%); border: 1px solid hsl(${ministryHue(mIdx)}, 40%, 88%)`">
                            <div class="absolute inset-0 opacity-5"
                                :style="`background: linear-gradient(135deg, hsl(${ministryHue(mIdx)}, 80%, 50%), transparent)`"></div>
                            <!-- Avatar -->
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-base text-white shadow-lg shrink-0 relative z-10"
                                :style="`background: linear-gradient(135deg, hsl(${ministryHue(mIdx)}, 60%, 40%), hsl(${ministryHue(mIdx)}, 50%, 28%))`">
                                {{ m.ministro.nombre.substring(0,2).toUpperCase() }}
                            </div>
                            <div class="relative z-10 flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full text-white"
                                        :style="`background: hsl(${ministryHue(mIdx)}, 55%, 40%)`">Ministro Titular</span>
                                </div>
                                <p class="font-extrabold text-sm text-on-surface leading-tight truncate">{{ m.ministro.nombre }}</p>
                                <p v-if="m.ministro.perfil" class="text-xs text-on-surface-variant mt-0.5 truncate">{{ m.ministro.perfil }}</p>
                            </div>
                        </div>

                        <!-- Viceministros -->
                        <div>
                            <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-[0.15em] mb-2 ml-1">Viceministros</p>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="(v, idx) in m.viceministros" :key="idx"
                                    class="flex items-center gap-2 px-3 py-2 bg-surface-container-low rounded-xl border border-outline-variant/10 hover:border-outline-variant/30 transition-all">
                                    <div class="w-6 h-6 rounded-full bg-surface-container-high flex items-center justify-center font-bold text-[9px] text-on-surface-variant uppercase shrink-0"
                                        :style="`background: hsl(${ministryHue(mIdx)}, 30%, 88%); color: hsl(${ministryHue(mIdx)}, 50%, 35%)`">
                                        {{ v.nombre.substring(0,2) }}
                                    </div>
                                    <p class="text-xs font-semibold text-on-surface">{{ v.nombre }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-outline-variant/10"></div>

                        <!-- Personal registrado -->
                        <div v-if="personalPorMinisterio[m.id] && personalPorMinisterio[m.id].length > 0">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[9px] font-black text-on-surface-variant uppercase tracking-[0.15em]">Personal registrado</p>
                                <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full text-white"
                                    :style="`background: hsl(${ministryHue(mIdx)}, 55%, 40%)`">
                                    {{ personalPorMinisterio[m.id].length }}
                                </span>
                            </div>
                            <div class="space-y-2.5">
                                <div v-for="(p, idx) in personalPorMinisterio[m.id]" :key="idx"
                                    class="flex items-center gap-3 p-3 rounded-2xl border transition-all duration-200 group/card hover:shadow-md cursor-default"
                                    :style="`background: hsl(${ministryHue(mIdx)}, 40%, 97%); border-color: hsl(${ministryHue(mIdx)}, 35%, 90%)`">
                                    <!-- Avatar con foto o iniciales -->
                                    <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 shadow-sm"
                                        :style="`background: linear-gradient(135deg, hsl(${ministryHue(mIdx)}, 60%, 50%), hsl(${ministryHue(mIdx)}, 45%, 35%))`">
                                        <img v-if="p.fotoPreview" :src="p.fotoPreview" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center font-bold text-xs text-white uppercase">
                                            {{ p.nombre.substring(0,2) }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-bold text-on-surface leading-tight">{{ p.nombre }}</p>
                                            <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full"
                                                :class="p.tipoPuesto === 'Ministro' ? 'bg-amber-100 text-amber-700' : p.tipoPuesto === 'Viceministro' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'">
                                                {{ p.tipoPuesto }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1 flex-wrap">
                                            <span v-if="p.sueldo" class="text-[11px] font-semibold text-on-surface-variant flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-[11px]">payments</span>Q{{ p.sueldo }}
                                            </span>
                                            <span v-if="p.fechaPosesion" class="text-[11px] text-on-surface-variant flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-[11px]">event</span>{{ p.fechaPosesion }}
                                            </span>
                                        </div>
                                    </div>
                                    <button @click="eliminarPersona(m.id, idx)"
                                        class="opacity-0 group-hover/card:opacity-100 transition-all p-1.5 rounded-xl hover:bg-error/10 text-error shrink-0">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Agregar Personal (rediseñado) -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="modalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-md" @click="cerrarModal"></div>

                    <div class="relative w-full max-w-lg bg-surface rounded-3xl shadow-2xl border border-outline-variant/20 overflow-hidden">

                        <!-- Gradient header del modal -->
                        <div class="relative p-6 overflow-hidden"
                            :style="ministerioSeleccionado ? `background: linear-gradient(135deg, hsl(${ministryHue(ministries.findIndex(m => m.id === ministerioSeleccionado.id))}, 60%, 35%), hsl(${ministryHue(ministries.findIndex(m => m.id === ministerioSeleccionado.id))}, 45%, 22%))` : 'background: linear-gradient(135deg, #334155, #1e293b)'">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[4rem] font-black text-white/10 leading-none select-none pointer-events-none">
                                {{ ministerioSeleccionado?.short }}
                            </div>
                            <div class="relative z-10 flex items-start justify-between">
                                <div>
                                    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Agregar Funcionario</p>
                                    <h3 class="text-xl font-extrabold text-white font-headline">{{ ministerioSeleccionado?.short }}</h3>
                                    <p class="text-white/50 text-xs mt-0.5 max-w-[240px] leading-snug">{{ ministerioSeleccionado?.name }}</p>
                                </div>
                                <button @click="cerrarModal"
                                    class="p-2 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-white">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Form body -->
                        <div class="p-6 space-y-5 max-h-[60vh] overflow-y-auto">

                            <!-- Foto preview -->
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 rounded-2xl bg-surface-container-high border-2 border-dashed border-outline-variant/30 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                                    <img v-if="nuevoPersonal.fotoPreview" :src="nuevoPersonal.fotoPreview" class="w-full h-full object-cover" />
                                    <span v-else class="material-symbols-outlined text-3xl text-outline-variant">person</span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">Foto</p>
                                    <label class="cursor-pointer flex items-center gap-2 px-4 py-2.5 bg-surface-container-low rounded-xl text-sm text-on-surface-variant hover:bg-surface-container transition-all border border-outline-variant/15 hover:border-primary/30 group/upload">
                                        <span class="material-symbols-outlined text-base text-primary group-hover/upload:scale-110 transition-transform">cloud_upload</span>
                                        <span class="text-xs font-semibold truncate">{{ nuevoPersonal.fotoNombre || 'Subir fotografía...' }}</span>
                                        <input type="file" accept="image/*" class="hidden" @change="onFotoChange" />
                                    </label>
                                    <p class="text-[10px] text-outline mt-1.5 ml-1">JPG, PNG o WebP · Máx. 5MB</p>
                                </div>
                            </div>

                            <div class="h-px bg-outline-variant/10"></div>

                            <!-- Nombre -->
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Nombre completo <span class="text-error">*</span></label>
                                <input v-model="nuevoPersonal.nombre" type="text" placeholder="Ej: Lic. Carlos Morales González"
                                    class="w-full px-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all placeholder:text-outline" />
                            </div>

                            <!-- Tipo de Puesto -->
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Tipo de Puesto <span class="text-error">*</span></label>
                                <div class="flex gap-2">
                                    <button v-for="tipo in ['Ministro', 'Viceministro', 'Director']" :key="tipo"
                                        @click="nuevoPersonal.tipoPuesto = tipo"
                                        :class="[
                                            'flex-1 py-2.5 text-xs font-bold rounded-xl border transition-all',
                                            nuevoPersonal.tipoPuesto === tipo
                                                ? 'bg-primary text-white border-primary shadow-md shadow-primary/20'
                                                : 'bg-surface-container-low text-on-surface-variant border-outline-variant/15 hover:border-primary/30'
                                        ]">{{ tipo }}</button>
                                </div>
                            </div>

                            <!-- Sueldo + Fecha en grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Sueldo mensual</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-extrabold text-on-surface-variant">Q</span>
                                        <input v-model="nuevoPersonal.sueldo" type="text" placeholder="00.00"
                                            class="w-full pl-7 pr-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all placeholder:text-outline" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Fecha de toma</label>
                                    <input v-model="nuevoPersonal.fechaPosesion" type="date"
                                        class="w-full px-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all" />
                                </div>
                            </div>

                            <!-- Error -->
                            <Transition name="slide-error">
                                <p v-if="errorModal" class="flex items-center gap-2 text-xs font-bold text-error bg-error-container/30 px-4 py-3 rounded-xl border border-error/15">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    {{ errorModal }}
                                </p>
                            </Transition>
                        </div>

                        <!-- Footer actions -->
                        <div class="p-5 pt-0 flex gap-3">
                            <button @click="cerrarModal"
                                class="flex-1 py-3 px-4 bg-surface-container font-bold text-on-surface text-sm rounded-2xl hover:bg-surface-container-high transition-colors border border-outline-variant/10">
                                Cancelar
                            </button>
                            <button @click="guardarPersonal"
                                class="flex-2 py-3 px-6 bg-primary text-white font-bold text-sm rounded-2xl shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">how_to_reg</span>
                                Registrar funcionario
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Tab: Presupuesto -->
        <div v-if="activeTab === 'presupuesto'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-700">
            <!-- Hero Header Presupuesto -->
            <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-blue-700 via-indigo-600 to-purple-700 p-8 shadow-2xl shadow-indigo-900/20 mb-8 border border-white/10">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 50%, white 1.5px, transparent 1.5px), radial-gradient(circle at 80% 20%, white 1.5px, transparent 1.5px); background-size: 48px 48px; animation: float 10s linear infinite;"></div>
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-fuchsia-500/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/4"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center shadow-inner border border-white/20">
                                <span class="material-symbols-outlined text-white text-2xl">account_balance_wallet</span>
                            </div>
                            <span class="text-white/90 text-xs font-black uppercase tracking-[0.2em] bg-white/10 px-3 py-1 rounded-full backdrop-blur-sm">Ejecución Financiera</span>
                        </div>
                        <h2 class="text-4xl font-extrabold text-white font-headline leading-tight tracking-tight drop-shadow-sm">Presupuesto</h2>
                        <p class="text-white/80 text-sm mt-2 max-w-2xl leading-relaxed">Módulo interactivo del Sistema de Contabilidad Integrada. Adjunta el reporte oficial en formato Excel para analizar, formatear y visualizar la ejecución presupuestaria en tiempo real.</p>
                    </div>
                    
                    <div class="shrink-0 relative group mt-4 md:mt-0">
                        <div class="absolute -inset-1 bg-gradient-to-r from-blue-400 via-indigo-300 to-purple-400 rounded-2xl blur-md opacity-40 group-hover:opacity-100 transition duration-500"></div>
                        <input type="file" id="excelUploadSicoin" accept=".xlsx, .xls" class="hidden" @change="handleExcelUpload" />
                        <label for="excelUploadSicoin" class="relative cursor-pointer px-8 py-4 bg-white/95 backdrop-blur-sm text-indigo-900 font-extrabold text-sm rounded-xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all flex items-center gap-3 active:scale-95 border border-white">
                            <span class="material-symbols-outlined text-xl text-indigo-600 animate-bounce">upload_file</span>
                            Cargar Reporte Oficial
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tabla Detallada del Excel (Formato SICOIN) -->
            <div v-if="excelRows.length > 0" class="bg-white rounded-3xl border border-indigo-100 shadow-xl shadow-indigo-900/5 overflow-hidden p-2">
                <div class="w-full overflow-x-auto rounded-2xl border border-indigo-50">
                    <table class="w-full text-left border-collapse min-w-[1000px] text-[13px] font-sans">
                        <thead>
                            <tr class="bg-gradient-to-r from-indigo-950 to-slate-900 text-white shadow-sm">
                                <th v-for="(header, index) in excelHeaders" :key="index" :class="['px-4 py-3.5 font-bold border-r border-white/10 tracking-wide', index === 0 ? 'text-left' : 'text-right']">
                                    {{ header || (index === 0 ? 'Entidad' : '') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-50">
                            <tr v-for="(row, rowIndex) in excelRows" :key="rowIndex" class="hover:bg-indigo-50/50 transition-colors bg-white text-slate-700 group">
                                <td v-for="(cell, cellIndex) in excelHeaders" :key="cellIndex" :class="['px-4 py-2 border-r border-indigo-50 whitespace-nowrap', cellIndex === 0 ? 'text-left text-indigo-950' : 'text-right']">
                                    <span :class="{'uppercase font-bold text-[11px] tracking-wider': cellIndex === 0}">{{ row[cellIndex] || '' }}</span>
                                </td>
                            </tr>
                            <tr v-if="excelTotals" class="bg-gradient-to-r from-indigo-50 to-blue-50 font-extrabold text-indigo-950 border-t-4 border-indigo-200 shadow-inner">
                                <td v-for="(cell, cellIndex) in excelHeaders" :key="'tot_'+cellIndex" :class="['px-4 py-3.5 border-r border-indigo-100 whitespace-nowrap', cellIndex === 0 ? 'text-left uppercase tracking-widest text-xs' : 'text-right text-indigo-700']">
                                    {{ excelTotals[cellIndex] || '' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="relative overflow-hidden bg-surface p-12 rounded-3xl border border-outline-variant/15 shadow-sm text-center flex flex-col items-center justify-center group/empty">
                <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]"></div>
                <div class="relative z-10">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-emerald-50 border-8 border-emerald-100 flex items-center justify-center group-hover/empty:scale-110 transition-transform duration-500 shadow-inner">
                        <span class="material-symbols-outlined text-4xl text-emerald-500">analytics</span>
                    </div>
                    <h4 class="font-extrabold text-on-surface text-2xl font-headline tracking-tight">Base de datos vacía</h4>
                    <p class="text-on-surface-variant mt-3 max-w-md mx-auto text-sm leading-relaxed">Sube el archivo Excel oficial de SICOIN utilizando el botón superior. El sistema procesará el documento automáticamente manteniendo su formato original.</p>
                </div>
            </div>
        </div>

        <!-- Tab: Comisiones -->
        <div v-if="activeTab === 'comisiones'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-surface-container-low bg-surface-container-lowest">
                    <h3 class="font-extrabold text-xl font-headline tracking-tight">Estado de comisiones fiscales</h3>
                </div>
                <div class="w-full overflow-x-auto pb-2">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="bg-surface-container-lowest text-on-surface-variant text-[10px] uppercase tracking-widest border-b border-surface-container-low">
                                <th class="px-6 py-4 font-bold">Comisión</th>
                                <th class="px-6 py-4 font-bold">Prioridad</th>
                                <th class="px-6 py-4 font-bold text-center">Agenda activa</th>
                                <th class="px-6 py-4 font-bold">Avance de Objetivos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-background">
                            <tr v-for="c in commissions" :key="c.nombre" class="hover:bg-surface-container-lowest transition-colors">
                                <td class="px-6 py-5 font-bold text-sm text-on-surface">{{ c.nombre }}</td>
                                <td class="px-6 py-5">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', c.prioridad==='Alta'?'bg-error-container text-on-error-container':'bg-tertiary-container text-on-tertiary-container']">{{ c.prioridad }}</span>
                                </td>
                                <td class="px-6 py-5 text-center font-bold text-sm text-on-surface-variant">{{ c.agenda }}</td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-surface-container-highest h-2.5 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full" :style="{ width: c.avances + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-black text-on-surface">{{ c.avances }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-6 max-h-min">
                <h3 class="font-extrabold text-lg font-headline mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary">target</span> Prioridades tácticas</h3>
                <div class="space-y-3">
                    <div class="p-4 rounded-xl border border-outline-variant/30 text-xs font-medium leading-relaxed text-on-surface-variant bg-surface-container-lowest shadow-sm">Control estricto de citaciones, validación de oficios enviados, revisión de respuestas recibidas y plazos pendientes por institución.</div>
                    <div class="p-4 rounded-xl border border-outline-variant/30 text-xs font-medium leading-relaxed text-on-surface-variant bg-surface-container-lowest shadow-sm">Agenda pormenorizada por comisión con responsables asignados, plazos irrevocables, insumos y hallazgos clave.</div>
                    <div class="p-4 rounded-xl border border-outline-variant/30 text-xs font-medium leading-relaxed text-on-surface-variant bg-surface-container-lowest shadow-sm">Uso del Semáforo de riesgo para detectar oportunidades óptimas de fiscalización política y técnica.</div>
                </div>
            </div>
        </div>

        <!-- Tab: Documentos -->
        <div v-if="activeTab === 'documentos'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-surface-container-low bg-surface-container-lowest flex justify-between items-center">
                    <h3 class="font-extrabold text-xl font-headline tracking-tight">Repositorio Documental</h3>
                    <button class="text-primary hover:bg-primary-container p-2 rounded-lg transition-colors"><span class="material-symbols-outlined">filter_list</span></button>
                </div>
                <div class="w-full overflow-x-auto pb-2">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="bg-surface-container-lowest text-on-surface-variant text-[10px] uppercase tracking-widest border-b border-surface-container-low">
                                <th class="px-6 py-4 font-bold">Tipo</th>
                                <th class="px-6 py-4 font-bold">Documento</th>
                                <th class="px-6 py-4 font-bold">Entidad Central</th>
                                <th class="px-6 py-4 font-bold">Fecha Reseña</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-background">
                            <tr v-for="d in docs" :key="d.nombre" class="hover:bg-surface-container-lowest transition-colors cursor-pointer group">
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 bg-surface-container-highest border border-outline-variant/20 rounded text-[10px] font-black text-on-surface-variant shadow-sm">{{ d.tipo }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-sm text-primary group-hover:underline">{{ d.nombre }}</td>
                                <td class="px-6 py-4 text-xs font-semibold text-on-surface-variant">{{ d.entidad }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-on-surface-variant">{{ d.fecha }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-6 overflow-hidden relative">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-primary/5 rounded-full blur-xl pointer-events-none"></div>
                <h3 class="font-extrabold text-lg font-headline mb-4 relative z-10">Carga Segura de Evidencia</h3>
                
                <div class="border-2 border-dashed border-primary/30 bg-primary/5 rounded-2xl p-8 flex flex-col items-center justify-center text-center text-on-surface-variant mb-6 hover:bg-primary/10 transition-colors cursor-pointer relative z-10">
                    <div class="w-16 h-16 rounded-full bg-surface shadow-sm flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-3xl text-primary">cloud_upload</span>
                    </div>
                    <p class="text-sm font-bold text-on-surface mb-1">Arrastra tus archivos aquí</p>
                    <p class="text-xs">Soporta PDFs, XLS, DOCX, PPT hasta 50MB.</p>
                </div>
                
                <div class="p-4 bg-surface-container-low rounded-xl text-xs font-medium text-on-surface-variant leading-relaxed relative z-10 border border-outline-variant/20">
                    <span class="font-bold text-on-surface">Recordatorio:</span> Cada archivo de fiscalización debe vincularse sistemáticamente a su respectivo ministerio, comisión o tema. Agregue etiquetas de riesgo para facilitar las búsquedas de la bancada.
                </div>
            </div>
        </div>

        <!-- Tab: Personal -->
        <div v-if="activeTab === 'personal'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
             <div class="lg:col-span-2 space-y-6">
                <!-- Tabla Personal -->
                <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-surface-container-low bg-surface-container-lowest">
                        <h3 class="font-extrabold text-xl font-headline tracking-tight">Directorio Ejecutivo: Salarios y Puestos</h3>
                    </div>
                    <div class="w-full overflow-x-auto pb-2">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-surface-container-lowest text-on-surface-variant text-[10px] uppercase tracking-widest border-b border-surface-container-low">
                                    <th class="px-6 py-4 font-bold">Ministerio/Entidad</th>
                                    <th class="px-6 py-4 font-bold">Cargo Suscrito</th>
                                    <th class="px-6 py-4 font-bold text-center">Renglón/Partida</th>
                                    <th class="px-6 py-4 font-bold text-right">Monto Devengado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-background">
                                <tr v-for="(p, i) in personal" :key="i" class="hover:bg-surface-container-lowest transition-colors">
                                    <td class="px-6 py-4 text-xs font-bold text-on-surface-variant">{{ p.entidad }}</td>
                                    <td class="px-6 py-4 font-bold text-sm text-on-surface">{{ p.puesto }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 bg-surface-container-high border border-outline-variant/10 rounded-lg text-xs font-black text-on-surface-variant">{{ p.renglones }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono font-bold text-right text-primary">{{ p.salario }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Renglones Totales -->
                <h3 class="font-bold text-lg font-headline mt-8 mb-2">Desglose de Renglones por Dependencia</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="m in ministries" :key="'rengpon-'+m.short" class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <h4 class="font-extrabold mb-5 font-headline border-b border-surface-container-low pb-2">{{ m.short }} <span class="font-normal text-sm text-on-surface-variant">· Fuerza Laboral</span></h4>
                        <div class="space-y-3">
                            <div v-for="r in m.personalRenglones" :key="r.renglon" class="flex justify-between items-center text-sm p-3 bg-surface-container-lowest rounded-xl border border-outline-variant/10 hover:border-primary/20 transition-colors">
                                <span class="font-black font-mono text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded text-[10px]">Reng. {{ r.renglon }}</span>
                                <span class="font-bold text-on-surface text-base">{{ r.cantidad.toLocaleString() }} <span class="text-xs font-medium text-on-surface-variant">emp.</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-surface rounded-2xl border border-primary/20 bg-primary/5 shadow-sm p-6 max-h-min">
                <h3 class="font-extrabold text-lg font-headline mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary">hub</span> Enfoques de Análisis</h3>
                <div class="space-y-3">
                    <div class="p-4 rounded-xl border border-surface-container-low text-xs font-medium leading-relaxed text-on-surface-variant bg-surface shadow-sm">Recolección de nóminas enteras, renglones detallados, contratos vigentes 011/022/029 y cruce con directorios institucionales.</div>
                    <div class="p-4 rounded-xl border border-surface-container-low text-xs font-medium leading-relaxed text-on-surface-variant bg-surface shadow-sm">Auditoría a puestos sustantivos versus asesores y personal temporal. Identificación de duplicidad y trazabilidad por dependencia específica.</div>
                    <div class="p-4 rounded-xl border border-surface-container-low text-xs font-medium leading-relaxed text-on-surface-variant bg-surface shadow-sm">Cruces estratégicos entre honorarios, unidad ejecutora contratante y observaciones directas derivadas de fiscalizaciones previas.</div>
                </div>
            </div>
        </div>

        <!-- Tab: Noticias -->
        <div v-if="activeTab === 'noticias'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-6">
                <h3 class="font-extrabold text-xl font-headline tracking-tight mb-6">Clipping y Monitoreo Mediático</h3>
                <div class="space-y-4">
                    <div v-for="(n, i) in noticias" :key="i" class="p-5 border border-outline-variant/20 hover:border-primary/30 transition-all rounded-2xl bg-surface-container-lowest shadow-[0_2px_10px_rgba(0,0,0,0.02)] group">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[1rem]">newspaper</span>
                                </div>
                                <span class="font-black text-sm tracking-tight">{{ n.ministerio }}</span>
                                <span :class="['px-2.5 py-0.5 rounded text-[9px] font-black uppercase tracking-widest', n.tono === 'crítico' ? 'bg-error-container/50 text-error border border-error/20' : 'bg-surface-container text-on-surface-variant']">{{ n.tono }}</span>
                            </div>
                            <span class="text-xs font-bold text-on-surface-variant bg-background px-2.5 py-1 rounded-lg border border-outline-variant/10">{{ n.fecha }}</span>
                        </div>
                        <p class="text-[1.1rem] text-on-surface font-bold leading-snug mb-2 group-hover:text-primary transition-colors cursor-pointer">{{ n.titulo }}</p>
                        <p class="text-xs text-outline font-medium flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">link</span> Fuente citada: {{ n.fuente }}</p>
                    </div>
                </div>
            </div>
            
             <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-6 max-h-min">
                <h3 class="font-extrabold text-lg font-headline mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary">auto_graph</span> Ejecución Política</h3>
                <div class="space-y-3">
                    <div class="p-4 rounded-xl border border-outline-variant/30 text-xs font-medium leading-relaxed text-on-surface-variant bg-surface-container-lowest shadow-sm">Relacionar inmediatamente cada noticia y escándalo público con ejecución vigente, funcionarios interpelables y oficios enviados con anterioridad.</div>
                    <div class="p-4 rounded-xl border border-outline-variant/30 text-xs font-medium leading-relaxed text-on-surface-variant bg-surface-container-lowest shadow-sm">Emplear inteligencia para generar preguntas y citaciones automáticas a despachos ministeriales.</div>
                    <div class="p-4 rounded-xl border border-outline-variant/30 text-xs font-medium leading-relaxed text-on-surface-variant bg-surface-container-lowest shadow-sm">Establecer máxima prioridad en aquellos ministerios con mayor ataque en redes o medios, y nula capacidad de respuesta o baja ejecución comprobable.</div>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import * as XLSX from 'xlsx';

const query = ref('');
const selected = ref('todos');
const activeTab = ref('ministerios');

// --- Lógica de Autoridades / Personal ---
const personalPorMinisterio = ref({});
const modalAbierto = ref(false);
const ministerioSeleccionado = ref(null);
const errorModal = ref('');
const nuevoPersonal = ref({ nombre: '', tipoPuesto: '', sueldo: '', fechaPosesion: '', fotoPreview: null, fotoNombre: '' });

function abrirModal(ministerio) {
    ministerioSeleccionado.value = ministerio;
    nuevoPersonal.value = { nombre: '', tipoPuesto: '', sueldo: '', fechaPosesion: '', fotoPreview: null, fotoNombre: '' };
    errorModal.value = '';
    modalAbierto.value = true;
}

function cerrarModal() {
    modalAbierto.value = false;
    ministerioSeleccionado.value = null;
    errorModal.value = '';
}

function onFotoChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    nuevoPersonal.value.fotoNombre = file.name;
    const reader = new FileReader();
    reader.onload = (e) => { nuevoPersonal.value.fotoPreview = e.target.result; };
    reader.readAsDataURL(file);
}

function guardarPersonal() {
    if (!nuevoPersonal.value.nombre.trim()) {
        errorModal.value = 'El nombre es obligatorio.';
        return;
    }
    if (!nuevoPersonal.value.tipoPuesto) {
        errorModal.value = 'Selecciona el tipo de puesto.';
        return;
    }
    const id = ministerioSeleccionado.value.id;
    if (!personalPorMinisterio.value[id]) {
        personalPorMinisterio.value[id] = [];
    }
    personalPorMinisterio.value[id].push({ ...nuevoPersonal.value });
    cerrarModal();
}

function eliminarPersona(ministerioId, index) {
    personalPorMinisterio.value[ministerioId].splice(index, 1);
}

// Color único por ministerio basado en su posición
function ministryHue(idx) {
    const hues = [210, 165, 24, 280, 340, 195, 50, 155, 270, 310, 35, 130, 190, 240];
    return hues[idx % hues.length];
}

const totalPersonalRegistrado = computed(() =>
    Object.values(personalPorMinisterio.value).reduce((sum, arr) => sum + arr.length, 0)
);

// --- Lógica de Presupuesto (Excel SICOIN) ---
const excelHeaders = ref([]);
const excelRows = ref([]);
const excelTotals = ref(null);

function handleExcelUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        // raw: false asegura que obtenemos el texto exacto que se ve en Excel (con comas, fechas, etc.)
        const rawData = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false, defval: '' });
        
        if (rawData.length > 0) {
            // 1. Encontrar cuántas columnas máximas hay
            let maxCols = 0;
            rawData.forEach(row => {
                if (row.length > maxCols) maxCols = row.length;
            });

            // 2. Identificar qué columnas están completamente vacías en todas las filas
            const colIsEmpty = new Array(maxCols).fill(true);
            for (let r = 0; r < rawData.length; r++) {
                for (let c = 0; c < maxCols; c++) {
                    const cellValue = rawData[r][c];
                    if (cellValue !== null && cellValue !== undefined && String(cellValue).trim() !== '') {
                        colIsEmpty[c] = false;
                    }
                }
            }

            // 3. Filtrar columnas vacías y filas completamente vacías
            const cleanedRows = [];
            for (let r = 0; r < rawData.length; r++) {
                const newRow = [];
                let rowHasData = false;
                for (let c = 0; c < maxCols; c++) {
                    if (!colIsEmpty[c]) {
                        const cellStr = String(rawData[r][c] || '').trim();
                        newRow.push(cellStr);
                        if (cellStr !== '') rowHasData = true;
                    }
                }
                if (rowHasData) cleanedRows.push(newRow);
            }

            if (cleanedRows.length > 0) {
                // Buscar la fila de cabecera: la que tenga más columnas llenas dentro de las primeras 15 filas
                let headerIndex = 0;
                let maxFilled = 0;
                const limit = Math.min(15, cleanedRows.length);
                for(let i = 0; i < limit; i++) {
                    const filled = cleanedRows[i].filter(c => c !== '').length;
                    if(filled > maxFilled) {
                        maxFilled = filled;
                        headerIndex = i;
                    }
                }

                excelHeaders.value = cleanedRows[headerIndex];
                
                // Los datos reales están debajo de la cabecera
                const dataRows = cleanedRows.slice(headerIndex + 1);
                
                // Identificar fila de totales (suele ser la última)
                if (dataRows.length > 0) {
                    const lastRow = dataRows[dataRows.length - 1];
                    const firstCell = lastRow[0].toLowerCase();
                    if (firstCell.includes('total') || firstCell === '' || firstCell.includes('sum')) {
                        excelTotals.value = dataRows.pop();
                    } else {
                        excelTotals.value = null;
                    }
                } else {
                    excelTotals.value = null;
                }
                
                excelRows.value = dataRows;
            } else {
                excelHeaders.value = [];
                excelRows.value = [];
                excelTotals.value = null;
            }
        }
    };
    reader.readAsArrayBuffer(file);
}

const tabs = [
    { id: 'ministerios', label: 'Ministerios y Entidades' },
    { id: 'autoridades', label: 'Autoridades' },
    { id: 'presupuesto', label: 'Presupuesto' },
    { id: 'comisiones', label: 'Comisiones' },
    { id: 'documentos', label: 'Documentos Generales' },
    { id: 'personal', label: 'Renglones y Personal' },
    { id: 'noticias', label: 'Noticias / Reputacional' },
];

// 14 Ministerios reales de Guatemala
const ministries = [
  { id: 1,  short: 'MAGA',    name: 'Ministerio de Agricultura, Ganadería y Alimentación',  presupuesto: 3200,  ejecucion: 22, funcionamiento: 800,   inversion: 2400,  empleados: 4200,  alertas: 3, riesgo: 'medio', hallazgos: ['Baja ejecución agrícola', 'Rezago en programas rurales'], docs: 5,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Desarrollo Rural', foto: '' }, { nombre: 'Viceministro Seguridad Alimentaria', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 800 }, { renglon: '022', cantidad: 200 }, { renglon: '029', cantidad: 600 }], transaccionesOI: 45 },
  { id: 2,  short: 'MARN',    name: 'Ministerio de Ambiente y Recursos Naturales',            presupuesto: 890,   ejecucion: 18, funcionamiento: 400,   inversion: 490,   empleados: 1200,  alertas: 2, riesgo: 'medio', hallazgos: ['Escasa inversión ambiental', 'Deforestación sin control'], docs: 4,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Recursos Naturales', foto: '' }, { nombre: 'Viceministro Ambiente', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 300 }, { renglon: '022', cantidad: 80 }, { renglon: '029', cantidad: 150 }], transaccionesOI: 20 },
  { id: 3,  short: 'CIV',     name: 'Ministerio de Comunicaciones, Infraestructura y Vivienda', presupuesto: 5800,  ejecucion: 24, funcionamiento: 1300,  inversion: 4500,  empleados: 8421,  alertas: 5, riesgo: 'alto',  hallazgos: ['Baja ejecución en proyectos prioritarios', 'Adjudicaciones tardías', 'Presión por mantenimiento vial'], docs: 12, ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Civil', foto: '' }, { nombre: 'Viceministro Transportes', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 1240 }, { renglon: '022', cantidad: 380 }, { renglon: '029', cantidad: 2150 }], transaccionesOI: 145 },
  { id: 4,  short: 'MCD',     name: 'Ministerio de Cultura y Deportes',                        presupuesto: 780,   ejecucion: 20, funcionamiento: 500,   inversion: 280,   empleados: 2100,  alertas: 1, riesgo: 'bajo',  hallazgos: ['Presupuesto limitado para cultura'], docs: 3,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Cultural', foto: '' }, { nombre: 'Viceministro Deportivo', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 400 }, { renglon: '022', cantidad: 100 }, { renglon: '029', cantidad: 300 }], transaccionesOI: 15 },
  { id: 5,  short: 'MINDEF',  name: 'Ministerio de la Defensa Nacional',                       presupuesto: 4200,  ejecucion: 35, funcionamiento: 3500,  inversion: 700,   empleados: 22000, alertas: 2, riesgo: 'medio', hallazgos: ['Gasto en personal elevado', 'Equipamiento desactualizado'], docs: 6,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Defensa', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 5000 }, { renglon: '022', cantidad: 1200 }, { renglon: '029', cantidad: 800 }], transaccionesOI: 60 },
  { id: 6,  short: 'MINDES',  name: 'Ministerio de Desarrollo Social',                         presupuesto: 2850,  ejecucion: 19, funcionamiento: 620,   inversion: 2230,  empleados: 1985,  alertas: 6, riesgo: 'alto',  hallazgos: ['Transferencias condicionadas bajo lupa ciudadana', 'Cobertura territorial lenta'], docs: 7,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Focalización', foto: '' }, { nombre: 'Viceministra Ejecutiva', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 320 }, { renglon: '022', cantidad: 110 }, { renglon: '029', cantidad: 860 }], transaccionesOI: 35 },
  { id: 7,  short: 'MINEDUC', name: 'Ministerio de Educación',                                  presupuesto: 24500, ejecucion: 29, funcionamiento: 21400, inversion: 3100,  empleados: 142000,alertas: 4, riesgo: 'medio', hallazgos: ['Infraestructura pendiente en zonas rurales', 'Contratación de maestros en proceso'], docs: 9,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Vice. Técnico', foto: '' }, { nombre: 'Vice. Administrativo', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 18200 }, { renglon: '022', cantidad: 4200 }, { renglon: '029', cantidad: 9600 }], transaccionesOI: 98 },
  { id: 8,  short: 'MINECO',  name: 'Ministerio de Economía',                                   presupuesto: 1100,  ejecucion: 21, funcionamiento: 700,   inversion: 400,   empleados: 1500,  alertas: 2, riesgo: 'medio', hallazgos: ['Baja inversión productiva', 'Rezago en MiPymes'], docs: 4,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Inversión', foto: '' }, { nombre: 'Viceministro MIPYMES', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 280 }, { renglon: '022', cantidad: 90 }, { renglon: '029', cantidad: 200 }], transaccionesOI: 22 },
  { id: 9,  short: 'MEM',     name: 'Ministerio de Energía y Minas',                            presupuesto: 1800,  ejecucion: 26, funcionamiento: 900,   inversion: 900,   empleados: 1800,  alertas: 3, riesgo: 'medio', hallazgos: ['Concesiones mineras cuestionadas', 'Energía rural deficiente'], docs: 6,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Energía', foto: '' }, { nombre: 'Viceministro Minas', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 350 }, { renglon: '022', cantidad: 100 }, { renglon: '029', cantidad: 250 }], transaccionesOI: 40 },
  { id: 10, short: 'MINFIN',  name: 'Ministerio de Finanzas Públicas',                          presupuesto: 2100,  ejecucion: 40, funcionamiento: 1800,  inversion: 300,   empleados: 3200,  alertas: 4, riesgo: 'medio', hallazgos: ['Deuda pública creciente', 'Ejecución presupuestaria lenta en otros ministerios'], docs: 10, ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Fiscal', foto: '' }, { nombre: 'Viceministro Tesorería', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 600 }, { renglon: '022', cantidad: 200 }, { renglon: '029', cantidad: 400 }], transaccionesOI: 80 },
  { id: 11, short: 'MINGOB',  name: 'Ministerio de Gobernación',                                presupuesto: 6500,  ejecucion: 33, funcionamiento: 5500,  inversion: 1000,  empleados: 35000, alertas: 7, riesgo: 'alto',  hallazgos: ['Inseguridad creciente', 'Corrupción en corporaciones policiales', 'Hacinamiento carcelario'], docs: 14, ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Seguridad', foto: '' }, { nombre: 'Viceministro Administrativo', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 8000 }, { renglon: '022', cantidad: 2500 }, { renglon: '029', cantidad: 5000 }], transaccionesOI: 190 },
  { id: 12, short: 'MINEX',   name: 'Ministerio de Relaciones Exteriores',                      presupuesto: 950,   ejecucion: 30, funcionamiento: 800,   inversion: 150,   empleados: 1100,  alertas: 1, riesgo: 'bajo',  hallazgos: ['Representación consular insuficiente'], docs: 3,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Diplomático', foto: '' }, { nombre: 'Viceministro Consular', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 200 }, { renglon: '022', cantidad: 60 }, { renglon: '029', cantidad: 120 }], transaccionesOI: 12 },
  { id: 13, short: 'MSPAS',   name: 'Ministerio de Salud Pública y Asistencia Social',          presupuesto: 14750, ejecucion: 31, funcionamiento: 10300, inversion: 4450,  empleados: 38210, alertas: 7, riesgo: 'alto',  hallazgos: ['Incidencias en compras directas', 'Rezagos programáticos', 'Abastecimiento de medicamentos oncológicos bajo'], docs: 18, ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Hospitales', foto: '' }, { nombre: 'Viceministra Primaria', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 5420 }, { renglon: '022', cantidad: 2120 }, { renglon: '029', cantidad: 4850 }], transaccionesOI: 220 },
  { id: 14, short: 'MINTRAB', name: 'Ministerio de Trabajo y Previsión Social',                 presupuesto: 820,   ejecucion: 25, funcionamiento: 600,   inversion: 220,   empleados: 1400,  alertas: 2, riesgo: 'medio', hallazgos: ['Inspecciones laborales insuficientes', 'Bajo presupuesto para IGSS'], docs: 3,  ministro: { nombre: 'Pendiente', foto: '', perfil: '' }, viceministros: [{ nombre: 'Viceministro Trabajo', foto: '' }, { nombre: 'Viceministro Previsión Social', foto: '' }], personalRenglones: [{ renglon: '011', cantidad: 250 }, { renglon: '022', cantidad: 80 }, { renglon: '029', cantidad: 180 }], transaccionesOI: 18 },
];

const commissions = [
  { nombre: "Descentralización y Desarrollo", prioridad: "Alta", agenda: 8, avances: 62 },
  { nombre: "Seguridad Alimentaria", prioridad: "Alta", agenda: 6, avances: 48 },
  { nombre: "Probidad y Transparencia", prioridad: "Media", agenda: 5, avances: 71 },
  { nombre: "Defensa Nacional", prioridad: "Media", agenda: 4, avances: 55 },
];

const noticias = [
  { ministerio: "MICIVI", titulo: "Retrasos en adjudicaciones y presión civil por fallos en red vial departamental", fecha: "2026-04-14", fuente: "Prensa Libre", tono: "crítico" },
  { ministerio: "MSPAS", titulo: "Incidente e inoperabilidad informática en base de datos de laboratorio nacional central", fecha: "2026-04-13", fuente: "elPeriódico", tono: "crítico" },
  { ministerio: "MSPAS", titulo: "Críticas por baja ejecución continuada en atención básica y oncológica", fecha: "2026-03-28", fuente: "Plaza Pública", tono: "crítico" },
  { ministerio: "MICIVI", titulo: "Arranque de operaciones 2026 frena por trabas presupuestarias en finanzas", fecha: "2026-01-09", fuente: "Guatevisión", tono: "crítico" },
];

const personal = [
  { puesto: "Ministro Titular", renglones: "011 / 029", salario: "Q42,000", entidad: "MICIVI" },
  { puesto: "Viceministro Administrativo", renglones: "011 / 029", salario: "Q30,000", entidad: "MICIVI" },
  { puesto: "Director General Financiero", renglones: "011", salario: "Q26,500", entidad: "MSPAS" },
  { puesto: "Asesor Técnico Especial", renglones: "029", salario: "Q18,000", entidad: "MIDES" },
  { puesto: "Asistente Despacho", renglones: "022", salario: "Q8,000", entidad: "MINEDUC" },
];

const docs = [
  { tipo: "PDF", nombre: "Informe de consolidación de ejecución de Marzo 2026", entidad: "MICIVI", fecha: "2026-04-01" },
  { tipo: "PPTX", nombre: "Presentación de resultados interinstitucional presidencia", entidad: "MSPAS", fecha: "2026-04-09" },
  { tipo: "XLSX", nombre: "Nómina y renglones de contrato temporal consolidados", entidad: "MINEDUC", fecha: "2026-04-10" },
  { tipo: "PDF", nombre: "Oficio formal de amparo y fiscalización 0938-2026", entidad: "MIDES", fecha: "2026-04-12" },
];

const totalPresupuesto = computed(() => ministries.reduce((acc, m) => acc + m.presupuesto, 0));
const totalDocs = computed(() => ministries.reduce((acc, m) => acc + m.docs, 0));
const totalAlertas = computed(() => ministries.reduce((acc, m) => acc + m.alertas, 0));
const avgEjecucion = computed(() => Math.round(ministries.reduce((acc, m) => acc + m.ejecucion, 0) / ministries.length));

const filteredMinistries = computed(() => {
    return ministries.filter((m) => {
        const textToSearch = `${m.name} ${m.short} ${m.hallazgos.join(' ')}`.toLowerCase();
        const matchesText = textToSearch.includes(query.value.toLowerCase());
        const matchesSelect = selected.value === 'todos' || m.short === selected.value;
        return matchesText && matchesSelect;
    });
});

const riskColorClass = (risk) => {
    switch(risk) {
        case 'alto': return 'bg-error text-white border-error';
        case 'medio': return 'bg-tertiary text-white border-tertiary';
        case 'bajo': return 'bg-primary text-white border-primary';
        default: return 'bg-surface-container text-on-surface-variant border-outline-variant/20';
    }
}

</script>

<style scoped>
/* Transición del modal */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .relative {
    transform: scale(0.92) translateY(16px);
}
.modal-leave-to .relative {
    transform: scale(0.95) translateY(8px);
}
</style>
