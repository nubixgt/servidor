<template>
    <div class="space-y-8">
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
        <div v-if="activeTab === 'autoridades'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-in fade-in slide-in-from-bottom-2 duration-500">
            <div v-for="m in ministries" :key="'aut-'+m.id" class="bg-surface rounded-2xl p-6 border border-outline-variant/20 shadow-sm">
                <h3 class="font-bold text-lg mb-6 font-headline border-b border-surface-container-low pb-3 uppercase tracking-widest">{{ m.short }} · Autoridades</h3>
                
                <div class="flex items-center gap-5 bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary to-primary-dim shadow-inner flex items-center justify-center font-bold text-xl text-white uppercase">
                        {{ m.ministro.nombre.substring(0,2) }}
                    </div>
                    <div>
                        <p class="text-[10px] text-primary font-bold uppercase tracking-widest mb-1">Ministro Titular</p>
                        <p class="font-extrabold text-lg text-on-surface leading-tight">{{ m.ministro.nombre }}</p>
                        <p class="text-xs text-on-surface-variant mt-2 font-medium bg-background px-3 py-1.5 rounded-lg border border-outline-variant/10">{{ m.ministro.perfil }}</p>
                    </div>
                </div>

                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3 ml-1">Cuerpo de Viceministros</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div v-for="(v, idx) in m.viceministros" :key="idx" class="flex items-center gap-3 p-3 bg-surface-container-low rounded-xl border border-transparent hover:border-outline-variant/20 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center font-bold text-sm text-on-surface-variant uppercase shadow-sm">
                            {{ v.nombre.substring(0,2) }}
                        </div>
                        <p class="text-sm font-bold text-on-surface">{{ v.nombre }}</p>
                    </div>
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
</template>

<script setup>
import { ref, computed } from 'vue';

const query = ref('');
const selected = ref('todos');
const activeTab = ref('ministerios');

const tabs = [
    { id: 'ministerios', label: 'Ministerios y Entidades' },
    { id: 'autoridades', label: 'Autoridades' },
    { id: 'comisiones', label: 'Comisiones' },
    { id: 'documentos', label: 'Documentos Generales' },
    { id: 'personal', label: 'Renglones y Personal' },
    { id: 'noticias', label: 'Noticias / Reputacional' },
];

// Data directly transcribed from React component
const ministries = [
  {
    id: 1,
    name: "Ministerio de Comunicaciones, Infraestructura y Vivienda",
    short: "MICIVI",
    presupuesto: 5800,
    ejecucion: 24,
    funcionamiento: 1300,
    inversion: 4500,
    empleados: 8421,
    alertas: 5,
    riesgo: "alto",
    hallazgos: ["Baja ejecución en proyectos prioritarios", "Adjudicaciones tardías", "Presión por mantenimiento vial"],
    docs: 12,
    ministro: { nombre: "José Aguilar", foto: "", perfil: "Ingeniero civil con 20 años de experiencia técnica." },
    viceministros: [
      { nombre: "Viceministro Civil", foto: "" },
      { nombre: "Viceministro Transportes", foto: "" },
    ],
    personalRenglones: [
      { renglon: "011", cantidad: 1240 },
      { renglon: "022", cantidad: 380 },
      { renglon: "029", cantidad: 2150 },
    ],
    transaccionesOI: 145,
  },
  {
    id: 2,
    name: "Ministerio de Salud Pública y Asistencia Social",
    short: "MSPAS",
    presupuesto: 14750,
    ejecucion: 31,
    funcionamiento: 10300,
    inversion: 4450,
    empleados: 38210,
    alertas: 7,
    riesgo: "alto",
    hallazgos: ["Incidencias en compras directas", "Rezagos programáticos", "Abastecimiento de medicamentos oncológicos bajo"],
    docs: 18,
    ministro: { nombre: "Dra. Carmen Ríos", foto: "", perfil: "Médica epidemióloga, experiencia en OPS." },
    viceministros: [
      { nombre: "Viceministro Hospitales", foto: "" },
      { nombre: "Viceministra Primaria", foto: "" },
    ],
    personalRenglones: [
      { renglon: "011", cantidad: 5420 },
      { renglon: "022", cantidad: 2120 },
      { renglon: "029", cantidad: 4850 },
    ],
    transaccionesOI: 220,
  },
  {
    id: 3,
    name: "Ministerio de Educación",
    short: "MINEDUC",
    presupuesto: 24500,
    ejecucion: 29,
    funcionamiento: 21400,
    inversion: 3100,
    empleados: 142000,
    alertas: 4,
    riesgo: "medio",
    hallazgos: ["Infraestructura pendiente en zonas rurales", "Contratación de maestros en proceso"],
    docs: 9,
    ministro: { nombre: "Lic. Mario Estrada", foto: "", perfil: "Docente y planificador educativo." },
    viceministros: [
      { nombre: "Vice. Técnico", foto: "" },
      { nombre: "Vice. Administrativo", foto: "" },
    ],
    personalRenglones: [
      { renglon: "011", cantidad: 18200 },
      { renglon: "022", cantidad: 4200 },
      { renglon: "029", cantidad: 9600 },
    ],
    transaccionesOI: 98,
  },
  {
    id: 4,
    name: "Ministerio de Desarrollo Social",
    short: "MIDES",
    presupuesto: 2850,
    ejecucion: 19,
    funcionamiento: 620,
    inversion: 2230,
    empleados: 1985,
    alertas: 6,
    riesgo: "alto",
    hallazgos: ["Transferencias condicionadas bajo lupa ciudadana", "Cobertura territorial lenta"],
    docs: 7,
    ministro: { nombre: "Ana López", foto: "", perfil: "Especialista en programas sociales." },
    viceministros: [
      { nombre: "Viceministro Focalización", foto: "" },
      { nombre: "Viceministra Ejecutiva", foto: "" },
    ],
    personalRenglones: [
      { renglon: "011", cantidad: 320 },
      { renglon: "022", cantidad: 110 },
      { renglon: "029", cantidad: 860 },
    ],
    transaccionesOI: 35,
  },
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
/* Agrega animaciones ligeras nativas si lo deseas */
</style>
