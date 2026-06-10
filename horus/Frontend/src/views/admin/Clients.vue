<template>
  <div>
    <!-- Create Client Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-surface-container-high/90 border border-white/10 p-6 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto custom-scrollbar shadow-[0_8px_32px_rgba(0,0,0,0.5)]">
            <h2 class="font-headline-lg text-primary mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined">{{ isEditing ? 'edit' : 'person_add' }}</span> 
                {{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}
            </h2>
            <form @submit.prevent="saveClient" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Fecha -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Fecha</label>
                    <input v-model="form.fecha" type="date" required class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                </div>
                <!-- Cliente -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Cliente</label>
                    <input v-model="form.cliente" type="text" required class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                </div>
                <!-- Refiere -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Refiere</label>
                    <div class="relative">
                        <select v-model="form.refiere" class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                            <option value="">Ninguno</option>
                            <option v-for="ref in referidos" :key="ref.id" :value="ref.id" class="bg-surface-container text-on-surface">{{ ref.nombre }}</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">arrow_drop_down</span>
                    </div>
                </div>
                <!-- Capital -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Capital</label>
                    <input v-model="form.capital" @input="formatInputCurrency('capital')" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" placeholder="Q0.00" />
                </div>
                <!-- Plazo -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Plazo</label>
                    <input v-model="form.plazo" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                </div>
                <!-- Porcentaje -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Porcentaje (%)</label>
                    <input v-model="form.porcentaje" @input="formatInputPercent('porcentaje')" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" placeholder="0%" />
                </div>
                <!-- Interes a Pagar -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Interés a Pagar</label>
                    <input v-model="form.interes_pagar" @input="formatInputCurrency('interes_pagar')" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" placeholder="Q0.00" />
                </div>
                <!-- Devolvió Capital -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Devolvió Capital</label>
                    <input v-model="form.devolvio_capital" @input="formatInputCurrency('devolvio_capital')" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" placeholder="Q0.00" />
                </div>
                <!-- Pago Interés -->
                <div class="flex flex-col gap-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Pago Interés</label>
                    <input v-model="form.pago_interes" @input="formatInputCurrency('pago_interes')" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" placeholder="Q0.00" />
                </div>
                <!-- Observaciones -->
                <div class="flex flex-col gap-1 md:col-span-2">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Observaciones</label>
                    <textarea v-model="form.observaciones" rows="3" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors"></textarea>
                </div>

                <!-- Documentación -->
                <div class="flex flex-col gap-1 md:col-span-2">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Documentación (Máx. 5 archivos)</label>
                    <input type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" @change="handleFileUpload" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors file:mr-4 file:py-1 file:px-4 file:rounded-xl file:border-0 file:bg-primary/20 file:text-primary file:font-semibold hover:file:bg-primary/30 file:cursor-pointer cursor-pointer text-sm" />
                    
                    <div v-if="archivos.length > 0" class="mt-2 flex flex-col gap-2">
                        <div v-for="(file, index) in archivos" :key="index" class="flex justify-between items-center bg-surface-container-high/50 px-3 py-2 rounded-xl border border-white/10">
                            <span class="text-xs text-on-surface truncate pr-2">{{ file.name }}</span>
                            <button type="button" @click="removeFile(index)" class="text-error hover:text-error-fixed transition-colors flex items-center">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                            </button>
                        </div>
                    </div>

                    <p v-if="isEditing && selectedClient?.documentacion" class="text-xs text-primary mt-1">
                        El cliente ya tiene archivos guardados. Si subes nuevos archivos, reemplazarán a los existentes.
                    </p>
                </div>

                <div class="col-span-1 md:col-span-2 flex justify-end gap-3 mt-4 pt-4 border-t border-white/10">
                    <button type="button" @click="showModal = false" class="px-5 py-2 rounded-xl border border-white/10 text-on-surface hover:bg-white/5 font-body-sm transition-colors">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary/20 text-primary border border-primary/50 hover:bg-primary/30 font-body-sm transition-colors shadow-[0_0_15px_rgba(233,193,118,0.2)]">
                        {{ isEditing ? 'Actualizar Cliente' : 'Guardar Cliente' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                    <button @click="openNewModal" class="bg-transparent border border-primary text-primary font-body-sm py-2 px-5 rounded-xl hover:bg-primary/10 transition-colors shadow-[0_0_10px_rgba(233,193,118,0.15)] backdrop-blur-sm hidden md:flex items-center gap-2 font-medium">
                        <span class="material-symbols-outlined text-lg">person_add</span>
                        Nuevo
                    </button>
                </div>

                <div class="relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input v-model="searchQuery" class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm text-body-sm py-3 pl-10 pr-4 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar por Nombre o Referido..." type="text"/>
                </div>
            </div>

            <!-- Customer List -->
            <div class="flex-1 bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden flex flex-col min-h-0">
                <div class="overflow-y-auto custom-scrollbar flex-1 p-2 space-y-2">
                    <div v-if="filteredClients.length === 0" class="text-center text-on-surface-variant p-4">No hay clientes encontrados</div>
                    
                    <div v-for="client in filteredClients" :key="client.id" 
                         @click="selectedClient = client"
                         :class="['p-4 rounded-2xl backdrop-blur-xl border cursor-pointer relative overflow-hidden group transition-all', 
                                  selectedClient?.id === client.id ? 'bg-surface-container-high/40 border-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]' : 'bg-transparent border-transparent hover:bg-surface-container-high/30 hover:border-white/5']">
                        <div v-if="selectedClient?.id === client.id" class="absolute top-0 left-0 w-1 h-full bg-primary shadow-[0_0_10px_rgba(233,193,118,0.8)]"></div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 :class="['font-title-md text-title-md transition-colors', selectedClient?.id === client.id ? 'text-on-surface' : 'text-on-surface group-hover:text-primary']">{{ client.cliente }}</h3>
                        </div>
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">event</span> {{ client.fecha }}
                                </p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">payments</span> Capital: Q{{ Number(client.capital).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}
                                </p>
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
                    <span class="font-label-caps text-label-caps text-on-surface tracking-widest uppercase">Detalle del Cliente</span>
                </div>
                <div class="flex gap-3">
                    <button v-if="selectedClient" @click="editClient" class="bg-surface-container-high/50 border border-white/10 text-on-surface font-body-sm py-2 px-4 rounded-xl hover:border-primary/50 hover:text-primary transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        Editar Perfil
                    </button>
                    <button v-if="selectedClient" @click="deleteClient" class="bg-error/20 border border-error/50 text-error font-body-sm py-2 px-4 rounded-xl hover:bg-error/30 transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        Eliminar
                    </button>
                </div>
            </div>

            <!-- Bento Grid Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 shrink-0">
                <!-- Personal Info Card -->
                <div class="bento-card rounded-2xl p-6 border border-white/5 flex flex-col gap-6 relative overflow-hidden bg-surface-container-high/30 backdrop-blur-xl shadow-[0_8px_32px_rgba(0,0,0,0.2)] hover:-translate-y-0.5 transition-transform" v-if="selectedClient">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="flex gap-4 items-start">
                        <div class="w-20 h-20 rounded-2xl bg-surface-container-high/50 border border-white/10 flex items-center justify-center overflow-hidden shrink-0 relative group shadow-inner">
                            <span class="material-symbols-outlined text-4xl text-outline group-hover:hidden">person</span>
                        </div>
                        <div>
                            <h2 class="font-headline-lg text-title-md text-primary mb-1">{{ selectedClient.cliente }}</h2>
                            <p class="font-body-sm text-body-sm text-on-surface-variant mb-2">Referido por: {{ selectedClient.refiere_nombre || 'N/A' }}</p>
                            <div class="flex gap-2">
                                <span class="px-2 py-0.5 rounded-lg bg-surface-container-high/30 border border-white/5 text-[11px] text-on-surface-variant uppercase tracking-wider">Fecha: {{ selectedClient.fecha }}</span>
                                <span class="px-2 py-0.5 rounded-lg bg-surface-container-high/30 border border-white/5 text-[11px] text-on-surface-variant uppercase tracking-wider">Plazo: {{ selectedClient.plazo || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="border-white/10"/>
                    
                    <div class="grid grid-cols-2 gap-y-4 gap-x-2">
                        <div>
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">Capital</p>
                            <p class="font-body-sm text-body-sm text-on-surface">Q{{ Number(selectedClient.capital).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</p>
                        </div>
                        <div>
                            <p class="font-label-caps text-[10px] text-outline mb-1 uppercase tracking-widest">Porcentaje</p>
                            <p class="font-body-sm text-body-sm text-on-surface">{{ selectedClient.porcentaje }}%</p>
                        </div>
                        <!-- Devolvió Capital -->
                        <div>
                            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Devolvió Capital</p>
                            <p class="font-body-md text-on-surface">{{ selectedClient.devolvioCapital ? 'Q' + Number(selectedClient.devolvioCapital).toLocaleString('en-US', {minimumFractionDigits: 2}) : '-' }}</p>
                        </div>
                        
                        <!-- Pago Interés -->
                        <div>
                            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Pago Interés</p>
                            <p class="font-body-md text-on-surface">{{ selectedClient.pagoInteres ? 'Q' + Number(selectedClient.pagoInteres).toLocaleString('en-US', {minimumFractionDigits: 2}) : '-' }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 border-t border-white/10 pt-6">
                        <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-2">Observaciones</p>
                        <p class="font-body-md text-on-surface leading-relaxed">
                            {{ selectedClient.observaciones || 'Sin observaciones registradas.' }}
                        </p>
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
                    <div v-if="!selectedClient?.documentacion" class="col-span-full text-center py-4 text-on-surface-variant font-body-sm border border-dashed border-white/10 rounded-xl">
                        No hay documentos guardados para este cliente.
                    </div>

                    <a v-for="(doc, index) in getDocArray(selectedClient?.documentacion)" :key="index"
                       :href="`/horus/Backend/${doc}`" target="_blank"
                       class="bg-surface-container-high/30 rounded-xl border border-white/5 p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:shadow-[0_0_15px_rgba(233,193,118,0.15)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-3xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all" style="font-variation-settings: 'FILL' 1;">{{ getExtensionIcon(doc) }}</span>
                        <div class="text-center w-full">
                            <p class="font-label-caps text-[10px] text-on-surface uppercase truncate" :title="getFilename(doc)">{{ getFilename(doc) }}</p>
                            <p class="font-body-sm text-[10px] text-tertiary-container mt-0.5">Archivo {{ index + 1 }}</p>
                        </div>
                    </a>

                    <!-- Add New Doc (Open edit modal) -->
                    <div @click="editClient" class="bg-surface-container-high/10 border border-dashed border-white/20 rounded-xl p-3 flex flex-col items-center justify-center gap-2 hover:border-primary hover:bg-primary/5 hover:shadow-[0_0_15px_rgba(233,193,118,0.1)] cursor-pointer transition-all group">
                        <span class="material-symbols-outlined text-2xl text-outline group-hover:text-primary group-hover:drop-shadow-[0_0_8px_rgba(233,193,118,0.6)] transition-all">add_circle</span>
                        <p class="font-label-caps text-[10px] text-outline group-hover:text-primary transition-all uppercase">Actualizar Docs</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { clientService } from '../../services/clientService';
import { referidoService } from '../../services/referidoService';
import Swal from 'sweetalert2';

const clients = ref([]);
const referidos = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const selectedClient = ref(null);

const filteredClients = computed(() => {
    if (!searchQuery.value) return clients.value;
    const query = searchQuery.value.toLowerCase();
    return clients.value.filter(client => 
        client.cliente.toLowerCase().includes(query) || 
        (client.refiere_nombre && client.refiere_nombre.toLowerCase().includes(query))
    );
});

const form = ref({
    id: null,
    fecha: '',
    cliente: '',
    refiere: '',
    capital: '',
    plazo: '',
    porcentaje: '',
    interes_pagar: '',
    devolvio_capital: '',
    pago_interes: '',
    observaciones: ''
});

const archivos = ref([]);

const handleFileUpload = (event) => {
    const newFiles = Array.from(event.target.files);
    if (archivos.value.length + newFiles.length > 5) {
        Swal.fire({
            icon: 'error',
            title: 'Límite alcanzado',
            text: 'Solo puedes subir un máximo de 5 archivos en total.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176'
        });
        event.target.value = '';
        return;
    }
    archivos.value = [...archivos.value, ...newFiles];
    event.target.value = ''; // Reset to allow same file again if removed
};

const removeFile = (index) => {
    archivos.value.splice(index, 1);
};

const getExtensionIcon = (filename) => {
    const ext = filename.split('.').pop().toLowerCase();
    if (['png', 'jpg', 'jpeg'].includes(ext)) return 'image';
    if (['pdf'].includes(ext)) return 'picture_as_pdf';
    if (['xls', 'xlsx'].includes(ext)) return 'table_chart';
    if (['doc', 'docx'].includes(ext)) return 'description';
    return 'insert_drive_file';
};

const getDocArray = (docs) => {
    if (!docs) return [];
    try {
        return typeof docs === 'string' ? JSON.parse(docs) : docs;
    } catch (e) {
        return [];
    }
};

const getFilename = (path) => {
    if (!path) return '';
    const parts = path.split('/');
    return parts[parts.length - 1];
};

const loadReferidos = async () => {
    try {
        const response = await referidoService.getReferidos();
        referidos.value = response.data.data;
    } catch (e) {
        console.error(e);
    }
};

const loadClients = async () => {
    try {
        const response = await clientService.getAllClients();
        clients.value = response.data.data;
        if(clients.value.length > 0 && !selectedClient.value) {
            selectedClient.value = clients.value[0];
        } else if (clients.value.length === 0) {
            selectedClient.value = null;
        } else {
            // update selected client ref if it exists in the new list
            selectedClient.value = clients.value.find(c => c.id === selectedClient.value.id) || null;
        }
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    loadClients();
    loadReferidos();
});

const parseFormatted = (val) => {
    if(!val) return null;
    return parseFloat(val.toString().replace(/[^0-9.-]+/g,""));
};

const formatInputCurrency = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    let num = parseInt(val, 10) / 100;
    form.value[field] = 'Q' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const formatInputPercent = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9.]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    form.value[field] = val + '%';
};

const openNewModal = () => {
    isEditing.value = false;
    form.value = { id: null, fecha: '', cliente: '', refiere: '', capital: '', plazo: '', porcentaje: '', interes_pagar: '', devolvio_capital: '', pago_interes: '', observaciones: '' };
    archivos.value = [];
    showModal.value = true;
};

const editClient = () => {
    if(!selectedClient.value) return;
    isEditing.value = true;
    archivos.value = [];
    form.value = {
        ...selectedClient.value,
        capital: selectedClient.value.capital ? 'Q' + Number(selectedClient.value.capital).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '',
        interes_pagar: selectedClient.value.interesPagar ? 'Q' + Number(selectedClient.value.interesPagar).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '',
        devolvio_capital: selectedClient.value.devolvioCapital ? 'Q' + Number(selectedClient.value.devolvioCapital).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '',
        pago_interes: selectedClient.value.pagoInteres ? 'Q' + Number(selectedClient.value.pagoInteres).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '',
        porcentaje: selectedClient.value.porcentaje ? selectedClient.value.porcentaje + '%' : ''
    };
    if (!form.value.refiere) form.value.refiere = ''; // Ensure select falls back to Ninguno
    showModal.value = true;
};

const saveClient = async () => {
    try {
        const payload = new FormData();
        payload.append('fecha', form.value.fecha || '');
        payload.append('cliente', form.value.cliente || '');
        payload.append('refiere', form.value.refiere || '');
        payload.append('capital', parseFormatted(form.value.capital) ?? '');
        payload.append('plazo', form.value.plazo || '');
        payload.append('porcentaje', parseFormatted(form.value.porcentaje) ?? '');
        payload.append('interes_pagar', parseFormatted(form.value.interes_pagar) ?? '');
        payload.append('devolvio_capital', parseFormatted(form.value.devolvio_capital) ?? '');
        payload.append('pago_interes', parseFormatted(form.value.pago_interes) ?? '');
        payload.append('observaciones', form.value.observaciones || '');

        archivos.value.forEach((file) => {
            payload.append('documentos[]', file);
        });
        
        if (isEditing.value) {
            await clientService.updateClient(form.value.id, payload);
        } else {
            await clientService.createClient(payload);
        }

        showModal.value = false;
        
        Swal.fire({
            title: '¡Éxito!',
            text: isEditing.value ? 'Cliente actualizado correctamente' : 'Cliente guardado correctamente',
            icon: 'success',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl',
                title: 'text-primary font-headline-lg',
            }
        });

        await loadClients();
    } catch (e) {
        console.error(e);
        Swal.fire({
            title: 'Error',
            text: 'Hubo un problema al procesar la solicitud',
            icon: 'error',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176'
        });
    }
};

const deleteClient = () => {
    if(!selectedClient.value) return;
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esto! El cliente se eliminará por completo.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffb4ab', // error color
        cancelButtonColor: '#353535',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: '#131313',
        color: '#ffffff',
        customClass: {
            popup: 'border border-white/10 rounded-2xl',
            title: 'text-error font-headline-lg',
            confirmButton: 'text-black'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await clientService.deleteClient(selectedClient.value.id);
                Swal.fire({
                    title: '¡Eliminado!',
                    text: 'El cliente ha sido eliminado.',
                    icon: 'success',
                    background: '#131313',
                    color: '#ffffff',
                    confirmButtonColor: '#e9c176',
                    customClass: {
                        popup: 'border border-white/10 rounded-2xl',
                    }
                });
                await loadClients();
            } catch (e) {
                console.error(e);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo eliminar el cliente',
                    icon: 'error',
                    background: '#131313',
                    color: '#ffffff',
                    confirmButtonColor: '#e9c176'
                });
            }
        }
    });
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #131313; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #353535; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e9c176; }
</style>
