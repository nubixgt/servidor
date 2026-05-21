<template>
    <div class="relative min-h-full">
        <!-- Fondo interactivo que cubre el área de contenido -->
        <div 
            class="fixed inset-0 pointer-events-none transition-colors duration-1000 z-0"
            :class="activeTab === 'presupuesto' ? 'bg-white' : 'bg-transparent'"
        ></div>
        
        <div class="relative z-10 space-y-8">
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-6">
            <div class="max-w-3xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Centro de Fiscalización del Ejecutivo</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Tablero integral para seguimiento político, presupuestario, documental y mediático por ministerio, secretaría, entidad y comisión.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button @click="abrirDocModal" class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest">
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

            <!-- Barra de búsqueda de ministerios -->
            <div class="flex items-center gap-3 bg-white rounded-2xl border border-outline-variant/20 px-4 py-3 shadow-sm">
                <span class="material-symbols-outlined text-outline text-xl">search</span>
                <input v-model="busquedaAutoridades" type="text" placeholder="Buscar ministerio o entidad..."
                    class="flex-1 border-none outline-none text-sm text-on-surface bg-transparent"
                    style="font-family: inherit;" />
                <button v-if="busquedaAutoridades" @click="busquedaAutoridades = ''"
                    class="text-outline hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Mensaje sin resultados -->
            <div v-if="ministeriosFiltrados.length === 0" class="text-center py-12 text-on-surface-variant">
                <span class="material-symbols-outlined text-5xl block mb-3 text-outline">search_off</span>
                <p class="font-semibold">No se encontró ningún ministerio con "{{ busquedaAutoridades }}"</p>
            </div>

            <!-- Grid de Ministerios -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div v-for="(m, mIdx) in ministeriosFiltrados" :key="'aut-'+m.id"
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
                            <!-- Avatar clickeable para subir foto -->
                            <input :id="`foto-ministro-${m.id}`" type="file" accept="image/*" class="hidden"
                                @change="onFotoMinistro($event, m.id)" />
                            <div class="relative shrink-0 z-10 group/avatar">
                                <div :class="['w-12 h-12 rounded-full flex items-center justify-center font-bold text-base text-white shadow-lg overflow-hidden', (isAdmin || ministerioFotos[m.id]) ? 'cursor-pointer transition-all duration-300 hover:scale-105 active:scale-95' : '']"
                                    :style="`background: linear-gradient(135deg, hsl(${ministryHue(mIdx)}, 60%, 40%), hsl(${ministryHue(mIdx)}, 50%, 28%))`"
                                    @click="ministerioFotos[m.id] ? verFotoMinistro(m) : (isAdmin ? abrirSelectorFotoMinistro(m.id) : null)"
                                    :title="ministerioFotos[m.id] ? (isAdmin ? 'Ver, cambiar o ampliar foto' : 'Ver foto ampliada') : (isAdmin ? 'Agregar foto' : '')">
                                    <img v-if="ministerioFotos[m.id] && subiendoFoto !== m.id"
                                        :src="ministerioFotos[m.id]"
                                        style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:9999px;"
                                        @error="ministerioFotos[m.id] = null" />
                                    <span v-if="subiendoFoto === m.id" class="material-symbols-outlined text-xl animate-spin" style="position:relative;z-index:1;">progress_activity</span>
                                    <template v-else-if="!ministerioFotos[m.id]">
                                        <span :class="{'group-hover/avatar:hidden': isAdmin}">{{ m.ministro.nombre.substring(0,2).toUpperCase() }}</span>
                                        <span v-if="isAdmin" class="material-symbols-outlined hidden group-hover/avatar:block text-xl">add_a_photo</span>
                                    </template>
                                </div>
                                <!-- Botón eliminar foto -->
                                <button v-if="isAdmin && ministerioFotos[m.id] && subiendoFoto !== m.id"
                                    @click.stop="eliminarFotoMinistro(m.id)"
                                    class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-error text-white flex items-center justify-center opacity-0 group-hover/avatar:opacity-100 transition-all duration-200 hover:scale-110 shadow-md z-20"
                                    title="Eliminar foto">
                                    <span class="material-symbols-outlined" style="font-size: 12px;">close</span>
                                </button>
                            </div>
                            <div class="relative z-10 flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full text-white"
                                        :style="`background: hsl(${ministryHue(mIdx)}, 55%, 40%)`">Ministro Titular</span>
                                </div>
                                <p v-if="isAdmin" @click="editarNombreMinistro(m)" class="font-extrabold text-sm text-on-surface leading-tight truncate hover:text-primary cursor-pointer flex items-center gap-1 group/name transition-colors" title="Haga clic para editar el nombre del ministro">
                                    {{ m.ministro.nombre }}
                                    <span class="material-symbols-outlined text-[14px] text-outline opacity-0 group-hover/name:opacity-100 transition-opacity">edit</span>
                                </p>
                                <p v-else class="font-extrabold text-sm text-on-surface leading-tight truncate">
                                    {{ m.ministro.nombre }}
                                </p>
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
                                    <button @click="eliminarPersona(p)"
                                        class="opacity-0 group-hover/card:opacity-100 transition-all p-1.5 rounded-xl hover:bg-error/10 text-error shrink-0"
                                        title="Eliminar funcionario">
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
                                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Fecha de ingreso</label>
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

        <!-- Modal: Cargar Documento -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="docModalAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-md" @click="cerrarDocModal"></div>

                    <div class="relative w-full max-w-lg bg-surface rounded-3xl shadow-2xl border border-outline-variant/20 overflow-hidden">

                        <!-- Gradient header del modal -->
                        <div class="relative p-6 overflow-hidden bg-gradient-to-br from-primary via-primary-dim to-secondary">
                            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 40px 40px;"></div>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[4rem] font-black text-white/10 leading-none select-none pointer-events-none">
                                DOCS
                            </div>
                            <div class="relative z-10 flex items-start justify-between">
                                <div>
                                    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Cargar Documento</p>
                                    <h3 class="text-xl font-extrabold text-white font-headline">Evidencia & Fiscalización</h3>
                                    <p class="text-white/50 text-xs mt-0.5 max-w-[240px] leading-snug">Vincule documentos al repositorio de control de la bancada</p>
                                </div>
                                <button @click="cerrarDocModal"
                                    class="p-2 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-white">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Form body -->
                        <div class="p-6 space-y-5">

                            <!-- Nombre del documento -->
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Nombre del Documento <span class="text-error">*</span></label>
                                <input v-model="nuevoDoc.nombre" type="text" placeholder="Ej: Oficio de amparo y fiscalización 0938-2026"
                                    class="w-full px-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all placeholder:text-outline" />
                            </div>

                            <!-- Tipo y Entidad en grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Tipo de Archivo <span class="text-error">*</span></label>
                                    <select v-model="nuevoDoc.tipo" class="w-full px-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all cursor-pointer">
                                        <option value="PDF">PDF</option>
                                        <option value="XLSX">XLSX / Excel</option>
                                        <option value="DOCX">DOCX / Word</option>
                                        <option value="PPTX">PPTX / PowerPoint</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Entidad Vinculada <span class="text-error">*</span></label>
                                    <select v-model="nuevoDoc.entidad" class="w-full px-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all cursor-pointer">
                                        <option value="" disabled>Seleccione entidad...</option>
                                        <option v-for="m in ministries" :key="m.id" :value="m.short">{{ m.short }} - {{ m.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Fecha -->
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Fecha de Carga</label>
                                <input v-model="nuevoDoc.fecha" type="date"
                                    class="w-full px-4 py-3 bg-surface-container-low rounded-xl text-sm text-on-surface border border-outline-variant/15 focus:border-primary/40 outline-none focus:ring-2 focus:ring-primary/10 transition-all" />
                            </div>

                            <!-- Error -->
                            <Transition name="slide-error">
                                <p v-if="errorDoc" class="flex items-center gap-2 text-xs font-bold text-error bg-error-container/30 px-4 py-3 rounded-xl border border-error/15">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    {{ errorDoc }}
                                </p>
                            </Transition>
                        </div>

                        <!-- Footer actions -->
                        <div class="p-5 pt-0 flex gap-3">
                            <button @click="cerrarDocModal"
                                class="flex-1 py-3 px-4 bg-surface-container font-bold text-on-surface text-sm rounded-2xl hover:bg-surface-container-high transition-colors border border-outline-variant/10">
                                Cancelar
                            </button>
                            <button @click="guardarDocumento"
                                class="flex-2 py-3 px-6 bg-primary text-white font-bold text-sm rounded-2xl shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">cloud_upload</span>
                                Guardar documento
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Tab: Presupuesto -->
        <div v-if="activeTab === 'presupuesto'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-700">
            <!-- Hero Header Presupuesto -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-8 mb-2">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-red-600 flex items-center justify-center shadow-md">
                                <span class="material-symbols-outlined text-white text-2xl">account_balance_wallet</span>
                            </div>
                            <span class="text-red-600 text-xs font-black uppercase tracking-[0.2em] bg-red-50 px-3 py-1 rounded-full border border-red-100">Ejecución Financiera</span>
                        </div>
                        <h2 class="text-3xl font-extrabold text-gray-900 font-headline leading-tight tracking-tight">Presupuesto</h2>
                        <p class="text-gray-500 text-sm mt-2 max-w-2xl leading-relaxed">Módulo interactivo del Sistema de Contabilidad Integrada. Adjunta el reporte oficial en formato Excel para analizar y visualizar la ejecución presupuestaria.</p>
                    </div>

                    <div class="shrink-0 mt-4 md:mt-0">
                        <input type="file" id="excelUploadSicoin" accept=".xlsx, .xls" class="hidden" @change="handleExcelUpload" />
                        <label for="excelUploadSicoin" class="cursor-pointer px-8 py-3.5 bg-red-600 hover:bg-red-700 text-white font-bold text-sm rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-3 active:scale-95">
                            <span class="material-symbols-outlined text-xl">upload_file</span>
                            Cargar Reporte Oficial
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tabla Detallada del Excel (Formato SICOIN) -->
            <div v-if="excelRows.length > 0" class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-base">Reporte de Ejecución Presupuestaria</h3>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">SICOIN</span>
                </div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1000px] text-[13px] font-sans">
                        <thead>
                            <tr class="bg-gray-900 text-white">
                                <th v-for="(header, index) in excelHeaders" :key="index" :class="['px-4 py-3.5 font-bold border-r border-white/10 tracking-wide text-xs uppercase', index === 0 ? 'text-left' : 'text-right']">
                                    {{ header || (index === 0 ? 'Entidad' : '') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(row, rowIndex) in excelRows" :key="rowIndex" class="hover:bg-red-50/50 transition-colors bg-white text-gray-700 group">
                                <td v-for="(cell, cellIndex) in excelHeaders" :key="cellIndex" :class="['px-4 py-2.5 border-r border-gray-100 whitespace-nowrap', cellIndex === 0 ? 'text-left font-semibold text-gray-900' : 'text-right text-gray-900 font-semibold']">
                                    <span :class="{'uppercase text-[11px] tracking-wider': cellIndex === 0}">{{ row[cellIndex] || '' }}</span>
                                </td>
                            </tr>
                            <tr v-if="excelTotals" class="bg-gray-50 font-extrabold text-gray-900 border-t-2 border-gray-200">
                                <td v-for="(cell, cellIndex) in excelHeaders" :key="'tot_'+cellIndex" :class="['px-4 py-3.5 border-r border-gray-100 whitespace-nowrap', cellIndex === 0 ? 'text-left uppercase tracking-widest text-xs' : 'text-right text-gray-900']">
                                    {{ excelTotals[cellIndex] || '' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="bg-white p-12 rounded-3xl border border-gray-200 shadow-sm text-center flex flex-col items-center justify-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-red-50 border-8 border-red-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-4xl text-red-500">analytics</span>
                </div>
                <h4 class="font-extrabold text-gray-900 text-2xl font-headline tracking-tight">Sin datos cargados</h4>
                <p class="text-gray-500 mt-3 max-w-md mx-auto text-sm leading-relaxed">Sube el archivo Excel oficial de SICOIN utilizando el botón superior. El sistema procesará el documento automáticamente.</p>
                <label for="excelUploadSicoin" class="mt-6 cursor-pointer px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold text-sm rounded-xl shadow-md transition-all flex items-center gap-2 w-fit mx-auto">
                    <span class="material-symbols-outlined text-lg">upload_file</span>
                    Cargar Reporte
                </label>
            </div>
        </div>

        <!-- Tab: Comisiones -->
        <div v-if="activeTab === 'comisiones'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500">

            <!-- Hero Header Comisiones -->
            <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-8">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-red-600 flex items-center justify-center shadow-md">
                                <span class="material-symbols-outlined text-white text-2xl">groups</span>
                            </div>
                            <span class="text-red-600 text-xs font-black uppercase tracking-[0.2em] bg-red-50 px-3 py-1 rounded-full border border-red-100">Fiscalización · Comisiones</span>
                        </div>
                        <h2 class="text-3xl font-extrabold text-gray-900 font-headline">Estado de Comisiones Fiscales</h2>
                        <p class="text-gray-500 text-sm mt-1">Seguimiento de comisiones legislativas, agenda activa y avance de objetivos.</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <div class="text-center px-5 py-3 rounded-2xl bg-gray-50 border border-gray-200">
                            <p class="text-2xl font-extrabold text-gray-900">{{ commissionsData.length }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Comisiones</p>
                        </div>
                        <div class="text-center px-5 py-3 rounded-2xl bg-red-50 border border-red-100">
                            <p class="text-2xl font-extrabold text-red-600">{{ commissionsData.filter(c=>c.prioridad==='Alta').length }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-red-400">Alta prioridad</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones + búsqueda -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[260px]">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl">search</span>
                    <input v-model="comBusqueda" type="text" placeholder="Buscar comisión..." class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-red-200 text-gray-900" />
                </div>
                <div class="flex items-center bg-gray-100 p-1.5 rounded-xl gap-1">
                    <button v-for="f in ['Todas','Alta','Media']" :key="f" @click="comFiltro=f"
                        :class="comFiltro===f ? 'px-4 py-2 bg-white text-gray-900 text-sm font-semibold rounded-lg shadow-sm' : 'px-4 py-2 text-gray-500 text-sm hover:text-gray-900 transition-colors'">{{ f }}</button>
                </div>
                <button @click="abrirComModal()" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 font-bold text-sm rounded-xl flex items-center gap-2 text-white shadow-md transition-all hover:shadow-lg active:scale-95">
                    <span class="material-symbols-outlined text-lg">add</span> Nueva Comisión
                </button>
            </div>

            <!-- Grid de comisiones -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Tabla principal -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse min-w-[550px]">
                        <thead>
                            <tr class="text-gray-500 text-[10px] uppercase tracking-widest border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-4 font-bold">Comisión</th>
                                <th class="px-6 py-4 font-bold">Prioridad</th>
                                <th class="px-6 py-4 font-bold text-center">Agenda</th>
                                <th class="px-6 py-4 font-bold">Avance</th>
                                <th class="px-6 py-4 font-bold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="comisionesFiltradas.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                    <span class="material-symbols-outlined text-4xl block mb-2 text-gray-300">inbox</span>
                                    No hay comisiones registradas
                                </td>
                            </tr>
                            <tr v-for="c in comisionesFiltradas" :key="c.id" class="group hover:bg-red-50/40 transition-colors">
                                <td class="px-6 py-5">
                                    <p class="font-bold text-sm text-gray-900">{{ c.nombre }}</p>
                                    <p v-if="c.notas" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ c.notas }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', c.prioridad==='Alta' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600']">{{ c.prioridad }}</span>
                                </td>
                                <td class="px-6 py-5 text-center font-bold text-sm text-gray-500">{{ c.agenda }}</td>
                                <td class="px-6 py-5 min-w-[140px]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-100 h-2 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-red-500 transition-all" :style="{ width: c.avances + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-black text-gray-700 w-8 text-right">{{ c.avances }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <button @click="abrirComModal(c)" class="p-2 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100 rounded-lg hover:bg-gray-100">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button @click="eliminarComision(c.id)" class="p-2 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100 rounded-lg hover:bg-red-50">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Panel derecho -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                    <h3 class="font-extrabold text-lg font-headline mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">target</span> Prioridades tácticas
                    </h3>
                    <div class="space-y-3">
                        <div class="p-4 rounded-xl border border-gray-100 text-xs font-medium leading-relaxed text-gray-500 bg-gray-50">Control estricto de citaciones, validación de oficios enviados, revisión de respuestas recibidas y plazos pendientes por institución.</div>
                        <div class="p-4 rounded-xl border border-gray-100 text-xs font-medium leading-relaxed text-gray-500 bg-gray-50">Agenda pormenorizada por comisión con responsables asignados, plazos irrevocables, insumos y hallazgos clave.</div>
                        <div class="p-4 rounded-xl border border-gray-100 text-xs font-medium leading-relaxed text-gray-500 bg-gray-50">Uso del Semáforo de riesgo para detectar oportunidades óptimas de fiscalización política y técnica.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Nueva/Editar Comisión -->
        <Teleport to="body">
            <Transition name="com-modal">
                <div v-if="comModalOpen" class="com-overlay" @click.self="cerrarComModal">
                    <div class="com-card">
                        <div class="com-header">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined com-icon">groups</span>
                                <div>
                                    <h3 class="com-title">{{ comEditando ? 'Editar Comisión' : 'Nueva Comisión' }}</h3>
                                    <p class="com-sub">{{ comEditando ? 'Modifica los datos' : 'Registra una nueva comisión' }}</p>
                                </div>
                            </div>
                            <button @click="cerrarComModal" class="com-close"><span class="material-symbols-outlined">close</span></button>
                        </div>
                        <div class="com-body">
                            <div class="com-field">
                                <label class="com-label">Nombre *</label>
                                <input v-model="comForm.nombre" class="com-input" placeholder="Ej. Comisión de Hacienda..." />
                            </div>
                            <div class="flex gap-3">
                                <div class="com-field flex-1">
                                    <label class="com-label">Prioridad</label>
                                    <select v-model="comForm.prioridad" class="com-input">
                                        <option value="Alta">Alta</option>
                                        <option value="Media">Media</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                                <div class="com-field flex-1">
                                    <label class="com-label">Agenda activa</label>
                                    <input v-model.number="comForm.agenda" type="number" min="0" class="com-input" placeholder="0" />
                                </div>
                                <div class="com-field flex-1">
                                    <label class="com-label">Avance %</label>
                                    <input v-model.number="comForm.avances" type="number" min="0" max="100" class="com-input" placeholder="0" />
                                </div>
                            </div>
                            <div class="com-field">
                                <label class="com-label">Notas / Integrantes</label>
                                <textarea v-model="comForm.notas" class="com-input" rows="3" placeholder="Lista de diputados, temas, observaciones..."></textarea>
                            </div>
                            <p v-if="comError" class="text-red-400 text-xs font-semibold">{{ comError }}</p>
                        </div>
                        <div class="com-footer">
                            <button v-if="comEditando" @click="eliminarComision(comEditando.id); cerrarComModal()" class="com-btn-del">
                                <span class="material-symbols-outlined text-sm">delete</span> Eliminar
                            </button>
                            <div class="flex gap-2 ml-auto">
                                <button @click="cerrarComModal" class="com-btn-cancel">Cancelar</button>
                                <button @click="guardarComision" class="com-btn-save">
                                    <span class="material-symbols-outlined text-sm">{{ comEditando ? 'save' : 'add' }}</span>
                                    {{ comEditando ? 'Guardar' : 'Crear' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Tab: Documentos -->
        <div v-if="activeTab === 'documentos'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
            <div class="lg:col-span-2 bg-surface rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-surface-container-low bg-surface-container-lowest flex justify-between items-center">
                    <h3 class="font-extrabold text-xl font-headline tracking-tight">Repositorio Documental</h3>
                    <button @click="abrirDocModal" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all hover:bg-primary/95 shadow-sm active:scale-95">
                        <span class="material-symbols-outlined text-[16px]">upload_file</span> Cargar
                    </button>
                </div>
                <div class="w-full overflow-x-auto pb-2">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="bg-surface-container-lowest text-on-surface-variant text-[10px] uppercase tracking-widest border-b border-surface-container-low">
                                <th class="px-6 py-4 font-bold">Tipo</th>
                                <th class="px-6 py-4 font-bold">Documento</th>
                                <th class="px-6 py-4 font-bold">Entidad Central</th>
                                <th class="px-6 py-4 font-bold">Fecha Reseña</th>
                                <th class="px-6 py-4 font-bold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-background">
                            <tr v-if="docs.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant text-sm">
                                    <span class="material-symbols-outlined text-4xl block mb-2 text-outline">folder_off</span>
                                    No hay documentos cargados en el repositorio.
                                </td>
                            </tr>
                            <tr v-for="d in docs" :key="d.id" class="hover:bg-surface-container-lowest transition-colors cursor-pointer group">
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 bg-surface-container-highest border border-outline-variant/20 rounded text-[10px] font-black text-on-surface-variant shadow-sm">{{ d.tipo }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-sm text-primary group-hover:underline">{{ d.nombre }}</td>
                                <td class="px-6 py-4 text-xs font-semibold text-on-surface-variant">{{ d.entidad }}</td>
                                <td class="px-6 py-4 text-xs font-mono text-on-surface-variant">{{ d.fecha }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click.stop="eliminarDocumento(d)" class="p-1.5 rounded-xl hover:bg-error/10 text-error inline-flex items-center justify-center transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="bg-surface rounded-2xl border border-outline-variant/20 shadow-sm p-6 overflow-hidden relative">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-primary/5 rounded-full blur-xl pointer-events-none"></div>
                <h3 class="font-extrabold text-lg font-headline mb-4 relative z-10">Carga Segura de Evidencia</h3>
                
                <div @click="abrirDocModal" class="border-2 border-dashed border-primary/30 bg-primary/5 rounded-2xl p-8 flex flex-col items-center justify-center text-center text-on-surface-variant mb-6 hover:bg-primary/10 transition-colors cursor-pointer relative z-10">
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
import { ref, computed, onMounted } from 'vue';
import api from '../../../services/api';
import * as XLSX from 'xlsx';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../../stores/authStore.js';

const authStore = useAuthStore();
const isAdmin = computed(() => authStore.role === 'administrador');

const query = ref('');
const selected = ref('todos');
const activeTab = ref('ministerios');

onMounted(async () => {
    try {
        const response = await api.get('/presupuesto');
        if (response.data && response.data.success && response.data.data) {
            const data = response.data.data;
            excelHeaders.value = data.excelHeaders || [];
            excelRows.value = data.excelRows || [];
            excelTotals.value = data.excelTotals || null;
        }
    } catch (error) {
        console.error('Error al cargar el presupuesto guardado:', error);
    }
    await cargarPersonalBD();
    await cargarDocumentosBD();
    await cargarFotosMinistros();
});

// --- Lógica de Autoridades / Personal ---
const personalPorMinisterio = ref({});
const modalAbierto = ref(false);
const ministerioSeleccionado = ref(null);
const busquedaAutoridades = ref('');
const ministerioFotos = ref({});
const subiendoFoto = ref(null);

const ministeriosFiltrados = computed(() => {
    if (!busquedaAutoridades.value.trim()) return ministries.value;
    const q = busquedaAutoridades.value.toLowerCase();
    return ministries.value.filter(m =>
        m.short.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
    );
});

async function cargarFotosMinistros() {
    try {
        const res = await api.get('/fiscalizacion/ministro-fotos');
        if (res.data?.success) {
            const raw = res.data.data || {};
            const isLocal = window.location.hostname === 'localhost' ||
                            window.location.hostname === '127.0.0.1' ||
                            window.location.hostname.startsWith('192.168.') ||
                            window.location.hostname.startsWith('10.') ||
                            window.location.hostname.endsWith('.local');
            const base = isLocal ? 'http://localhost:8080' : '';
            const fotos = {};
            for (const [key, val] of Object.entries(raw)) {
                const fotoUrl = val && typeof val === 'object' ? val.foto : null;
                const nombre = val && typeof val === 'object' ? val.nombre : 'Pendiente';
                
                if (fotoUrl) {
                    fotos[key] = fotoUrl.startsWith('http') ? fotoUrl : base + fotoUrl;
                }
                
                // Actualizar el nombre reactivamente en el array ministries
                const mId = parseInt(key);
                const mIndex = ministries.value.findIndex(m => m.id === mId);
                if (mIndex !== -1) {
                    ministries.value[mIndex].ministro.nombre = nombre || 'Pendiente';
                }
            }
            ministerioFotos.value = fotos;
        }
    } catch (e) {
        console.error('Error al cargar fotos de ministros:', e);
    }
}

async function editarNombreMinistro(m) {
    if (!isAdmin.value) return;
    const { value: nuevoNombre } = await Swal.fire({
        title: 'Editar Ministro Titular',
        input: 'text',
        inputLabel: 'Nombre del Ministro para ' + m.short,
        inputValue: m.ministro.nombre === 'Pendiente' ? '' : m.ministro.nombre,
        placeholder: 'Ej: Lic. Carlos Morales González',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#005D6B',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return '¡El nombre no puede estar vacío!';
            }
        }
    });

    if (nuevoNombre !== undefined) {
        const nombreTrimmed = nuevoNombre.trim();
        try {
            const res = await api.post(`/fiscalizacion/ministro-nombre/${m.id}`, { nombre: nombreTrimmed });
            if (res.data?.success) {
                m.ministro.nombre = nombreTrimmed;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Nombre actualizado exitosamente',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                Swal.fire('Error', res.data?.error || 'No se pudo actualizar el nombre del ministro.', 'error');
            }
        } catch (error) {
            console.error('Error al actualizar nombre del ministro:', error);
            Swal.fire('Error de red', 'No se pudo conectar con el servidor.', 'error');
        }
    }
}

async function onFotoMinistro(event, ministerioId) {
    if (!isAdmin.value) return;
    const file = event.target.files[0];
    if (!file) return;

    // Validar localmente tamaño (máx 10MB para la foto)
    if (file.size > 10 * 1024 * 1024) {
        Swal.fire('Archivo muy grande', 'La foto no debe superar los 10 MB.', 'warning');
        event.target.value = '';
        return;
    }

    subiendoFoto.value = ministerioId;
    const formData = new FormData();
    formData.append('foto', file);

    try {
        const res = await api.post(`/fiscalizacion/ministro-foto/${ministerioId}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        if (res.data?.success) {
            const isLocal = window.location.hostname === 'localhost' ||
                            window.location.hostname === '127.0.0.1' ||
                            window.location.hostname.startsWith('192.168.') ||
                            window.location.hostname.startsWith('10.') ||
                            window.location.hostname.endsWith('.local');
            const base = isLocal ? 'http://localhost:8080' : '';
            const url = res.data.url;
            ministerioFotos.value[ministerioId] = (url.startsWith('http') ? url : base + url) + '?t=' + Date.now();
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Foto de ministro guardada exitosamente',
                showConfirmButton: false,
                timer: 2500
            });
        } else {
            Swal.fire('Error', res.data?.error || 'No se pudo guardar la foto del ministro.', 'error');
        }
    } catch (e) {
        console.error('Error al subir foto:', e);
        const errorMsg = e.response?.data?.error || 'No se pudo conectar con el servidor o subir la imagen.';
        Swal.fire('Error al subir imagen', errorMsg, 'error');
    } finally {
        subiendoFoto.value = null;
        event.target.value = '';
    }
}

function abrirSelectorFotoMinistro(ministerioId) {
    const input = document.getElementById(`foto-ministro-${ministerioId}`);
    if (input) input.click();
}

function verFotoMinistro(m) {
    Swal.fire({
        title: m.ministro.nombre || 'Ministro Titular',
        text: m.name,
        imageUrl: ministerioFotos.value[m.id],
        imageAlt: `Foto de ${m.ministro.nombre}`,
        showConfirmButton: isAdmin.value,
        confirmButtonText: '<span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">photo_camera</span> Cambiar Foto</span>',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#005D6B',
        cancelButtonColor: '#6c757d',
        showCloseButton: true,
        customClass: {
            popup: 'rounded-3xl shadow-2xl p-6 border border-outline-variant/10',
            image: 'rounded-2xl object-cover shadow-lg max-h-[380px] max-w-full my-4 border border-outline-variant/15',
            title: 'font-headline font-black text-xl text-on-surface pt-2',
            htmlContainer: 'text-xs font-bold text-outline-variant uppercase tracking-widest'
        }
    }).then((result) => {
        if (result.isConfirmed && isAdmin.value) {
            abrirSelectorFotoMinistro(m.id);
        }
    });
}

async function eliminarFotoMinistro(ministerioId) {
    if (!isAdmin.value) return;
    const result = await Swal.fire({
        title: '¿Eliminar foto?',
        text: '¿Está seguro de eliminar la foto de este ministro titular?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6'
    });

    if (result.isConfirmed) {
        try {
            const res = await api.delete(`/fiscalizacion/ministro-foto/${ministerioId}`);
            if (res.data?.success) {
                delete ministerioFotos.value[ministerioId];
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Foto eliminada exitosamente',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                Swal.fire('Error', res.data?.error || 'No se pudo eliminar la foto de la base de datos.', 'error');
            }
        } catch (e) {
            console.error('Error al eliminar foto:', e);
            Swal.fire('Error', 'Hubo un error de red al intentar eliminar la foto.', 'error');
        }
    }
}
const errorModal = ref('');
const nuevoPersonal = ref({ nombre: '', tipoPuesto: '', sueldo: '', fechaPosesion: '', fotoPreview: null, fotoNombre: '' });

// --- Lógica de Documentos ---
const docs = ref([]);
const docModalAbierto = ref(false);
const nuevoDoc = ref({ nombre: '', tipo: 'PDF', entidad: '', fecha: '' });
const errorDoc = ref('');

async function cargarPersonalBD() {
    try {
        const response = await api.get('/fiscalizacion/personal');
        if (response.data?.success && Array.isArray(response.data.data)) {
            const agrupado = {};
            response.data.data.forEach(p => {
                const mId = p.ministerio_id;
                if (!agrupado[mId]) {
                    agrupado[mId] = [];
                }
                agrupado[mId].push(p);
            });
            personalPorMinisterio.value = agrupado;
        }
    } catch (error) {
        console.error('Error al cargar personal:', error);
    }
}

async function cargarDocumentosBD() {
    try {
        const response = await api.get('/fiscalizacion/documentos');
        if (response.data?.success && Array.isArray(response.data.data)) {
            docs.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar documentos:', error);
    }
}

function abrirModal(ministerio) {
    ministerioSeleccionado.value = ministerio;
    nuevoPersonal.value = { nombre: '', tipoPuesto: 'Director', sueldo: '', fechaPosesion: new Date().toISOString().split('T')[0], fotoPreview: null, fotoNombre: '' };
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

async function guardarPersonal() {
    if (!nuevoPersonal.value.nombre.trim()) {
        errorModal.value = 'El nombre es obligatorio.';
        return;
    }
    if (!nuevoPersonal.value.tipoPuesto) {
        errorModal.value = 'Selecciona el tipo de puesto.';
        return;
    }
    try {
        const payload = {
            ministerio_id: ministerioSeleccionado.value.id,
            nombre: nuevoPersonal.value.nombre,
            tipo_puesto: nuevoPersonal.value.tipoPuesto,
            sueldo: nuevoPersonal.value.sueldo ? parseFloat(nuevoPersonal.value.sueldo) : null,
            fecha_posesion: nuevoPersonal.value.fechaPosesion || null,
            foto_nombre: nuevoPersonal.value.fotoNombre || null,
            foto_preview: nuevoPersonal.value.fotoPreview || null
        };
        const response = await api.post('/fiscalizacion/personal', payload);
        if (response.data?.success) {
            await cargarPersonalBD();
            cerrarModal();
        } else {
            errorModal.value = response.data?.error || 'Error al guardar el funcionario.';
        }
    } catch (error) {
        console.error('Error al guardar personal:', error);
        errorModal.value = 'Error de conexión con el servidor.';
    }
}

async function eliminarPersona(p) {
    if (!p.id) return;
    if (confirm(`¿Está seguro de eliminar a ${p.nombre}?`)) {
        try {
            const response = await api.delete(`/fiscalizacion/personal/${p.id}`);
            if (response.data?.success) {
                await cargarPersonalBD();
            } else {
                alert(response.data?.error || 'Error al eliminar el funcionario.');
            }
        } catch (error) {
            console.error('Error al eliminar funcionario:', error);
            alert('Error de conexión con el servidor.');
        }
    }
}

function abrirDocModal() {
    nuevoDoc.value = {
        nombre: '',
        tipo: 'PDF',
        entidad: ministries.value[0]?.short || '',
        fecha: new Date().toISOString().split('T')[0]
    };
    errorDoc.value = '';
    docModalAbierto.value = true;
}

function cerrarDocModal() {
    docModalAbierto.value = false;
    errorDoc.value = '';
}

async function guardarDocumento() {
    if (!nuevoDoc.value.nombre.trim()) {
        errorDoc.value = 'El nombre del documento es obligatorio.';
        return;
    }
    if (!nuevoDoc.value.tipo) {
        errorDoc.value = 'Selecciona el tipo de documento.';
        return;
    }
    if (!nuevoDoc.value.entidad) {
        errorDoc.value = 'Selecciona la entidad vinculada.';
        return;
    }
    try {
        const response = await api.post('/fiscalizacion/documentos', nuevoDoc.value);
        if (response.data?.success) {
            await cargarDocumentosBD();
            cerrarDocModal();
        } else {
            errorDoc.value = response.data?.error || 'Error al guardar el documento.';
        }
    } catch (error) {
        console.error('Error al guardar documento:', error);
        errorDoc.value = 'Error de conexión con el servidor.';
    }
}

async function eliminarDocumento(d) {
    if (!d.id) return;
    if (confirm(`¿Está seguro de eliminar el documento "${d.nombre}"?`)) {
        try {
            const response = await api.delete(`/fiscalizacion/documentos/${d.id}`);
            if (response.data?.success) {
                await cargarDocumentosBD();
            } else {
                alert(response.data?.error || 'Error al eliminar el documento.');
            }
        } catch (error) {
            console.error('Error al eliminar documento:', error);
            alert('Error de conexión con el servidor.');
        }
    }
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

                // 4. Guardar en Backend
                guardarPresupuestoBD();

            } else {
                excelHeaders.value = [];
                excelRows.value = [];
                excelTotals.value = null;
            }
        }
    };
    reader.readAsArrayBuffer(file);
}

async function guardarPresupuestoBD() {
    try {
        const payload = {
            datos_json: {
                excelHeaders: excelHeaders.value,
                excelRows: excelRows.value,
                excelTotals: excelTotals.value
            }
        };
        const response = await api.post('/presupuesto', payload);
        if (response.data && response.data.success) {
            alert('¡Presupuesto SICOIN guardado con éxito en la base de datos!');
        }
    } catch (error) {
        console.error('Error guardando presupuesto:', error);
        alert('Hubo un error al intentar guardar el presupuesto.');
    }
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
const ministries = ref([
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
]);

// ─── Comisiones: estado reactivo y CRUD ───
const commissionsData = ref([
  { id: 1, nombre: 'Descentralización y Desarrollo', prioridad: 'Alta',  agenda: 8, avances: 62, notas: '' },
  { id: 2, nombre: 'Seguridad Alimentaria',           prioridad: 'Alta',  agenda: 6, avances: 48, notas: '' },
  { id: 3, nombre: 'Probidad y Transparencia',        prioridad: 'Media', agenda: 5, avances: 71, notas: '' },
  { id: 4, nombre: 'Defensa Nacional',                prioridad: 'Media', agenda: 4, avances: 55, notas: '' },
]);

const comBusqueda  = ref('');
const comFiltro    = ref('Todas');
const comModalOpen = ref(false);
const comEditando  = ref(null);
const comError     = ref('');
const comFormVacio = () => ({ nombre: '', prioridad: 'Alta', agenda: 0, avances: 0, notas: '' });
const comForm      = ref(comFormVacio());

const comisionesFiltradas = computed(() => {
    let lista = commissionsData.value;
    if (comFiltro.value !== 'Todas') lista = lista.filter(c => c.prioridad === comFiltro.value);
    if (comBusqueda.value.trim()) {
        const q = comBusqueda.value.toLowerCase();
        lista = lista.filter(c => c.nombre.toLowerCase().includes(q));
    }
    return lista;
});

function abrirComModal(com = null) {
    comError.value = '';
    comEditando.value = com;
    comForm.value = com ? { ...com } : comFormVacio();
    comModalOpen.value = true;
}
function cerrarComModal() { comModalOpen.value = false; }
function guardarComision() {
    if (!comForm.value.nombre.trim()) { comError.value = 'El nombre es obligatorio.'; return; }
    if (comEditando.value) {
        const idx = commissionsData.value.findIndex(c => c.id === comEditando.value.id);
        if (idx !== -1) commissionsData.value[idx] = { ...comForm.value, id: comEditando.value.id };
    } else {
        commissionsData.value.push({ ...comForm.value, id: Date.now() });
    }
    cerrarComModal();
}
function eliminarComision(id) { commissionsData.value = commissionsData.value.filter(c => c.id !== id); }

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

const totalPresupuesto = computed(() => ministries.value.reduce((acc, m) => acc + m.presupuesto, 0));
const totalDocs = computed(() => docs.value.length);
const totalAlertas = computed(() => ministries.value.reduce((acc, m) => acc + m.alertas, 0));
const avgEjecucion = computed(() => Math.round(ministries.value.reduce((acc, m) => acc + m.ejecucion, 0) / ministries.value.length));

const filteredMinistries = computed(() => {
    return ministries.value.filter((m) => {
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

/* ── Comisiones Modal ── */
.com-modal-enter-active, .com-modal-leave-active { transition: opacity 0.25s ease; }
.com-modal-enter-from, .com-modal-leave-to { opacity: 0; }
.com-modal-enter-active .com-card, .com-modal-leave-active .com-card { transition: transform 0.25s ease; }
.com-modal-enter-from .com-card, .com-modal-leave-to .com-card { transform: scale(0.95) translateY(12px); }

.com-overlay {
  position: fixed; inset: 0; z-index: 200;
  display: flex; align-items: center; justify-content: center;
  background: rgba(14, 40, 48, 0.75); backdrop-filter: blur(8px); padding: 16px;
}
.com-card {
  background: #216170; border: 1px solid #327f91; border-radius: 20px;
  width: 100%; max-width: 560px; overflow: hidden;
  display: flex; flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(14,40,48,0.6);
}
.com-header {
  padding: 22px 28px; background: #184e5b;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid #327f91;
}
.com-icon { font-size: 24px !important; padding: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; color: #e0f2fe; border: 1px solid rgba(255,255,255,0.2); }
.com-title { font-size: 17px; font-weight: 800; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 0.04em; }
.com-sub   { font-size: 12px; color: #a5d0db; margin: 2px 0 0; }
.com-close { width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.1); border-radius: 8px; color: #e0f2fe; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.com-close:hover { background: rgba(255,255,255,0.2); }
.com-body  { display: flex; flex-direction: column; gap: 16px; padding: 22px 28px; }
.com-field { display: flex; flex-direction: column; gap: 5px; }
.com-label { font-size: 11px; font-weight: 800; color: #a5d0db; text-transform: uppercase; letter-spacing: 0.05em; }
.com-input {
  width: 100%; padding: 10px 13px;
  background: #184e5b; border: 1px solid #327f91; border-radius: 10px;
  font-size: 14px; color: #fff; outline: none; transition: all 0.2s; font-family: inherit;
  color-scheme: dark;
}
.com-input:focus { border-color: #5ab1c5; box-shadow: 0 0 0 3px rgba(90,177,197,0.2); }
.com-input::placeholder { color: #6ba7b8; }
.com-footer { display: flex; align-items: center; padding: 18px 28px; background: #184e5b; border-top: 1px solid #327f91; }
.com-btn-del    { display:flex;align-items:center;gap:6px;padding:9px 15px;background:transparent;color:#f87171;border:1px solid transparent;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s; }
.com-btn-del:hover { background:#7f1d1d;border-color:#fca5a5; }
.com-btn-cancel { padding:9px 18px;background:#216170;color:#fff;border:1px solid #327f91;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s; }
.com-btn-cancel:hover { background:#327f91; }
.com-btn-save   { display:flex;align-items:center;gap:7px;padding:9px 20px;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:all 0.2s;background:linear-gradient(135deg,#0f3642,#216170);box-shadow:0 4px 12px rgba(14,40,48,0.3); }
.com-btn-save:hover { filter:brightness(1.15);transform:translateY(-1px); }
</style>
