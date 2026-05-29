<template>
    <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
        <!-- Toast Notice alerts -->
        <Transition name="toast">
            <div
                v-if="toastMessage"
                class="fixed top-24 right-10 z-50 bg-primary/25 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl"
            >
                <CheckCircle2 class="w-5 h-5 text-emerald-400" />
                <span class="text-xs font-black uppercase tracking-wider">{{ toastMessage }}</span>
            </div>
        </Transition>

        <!-- View Header with Navigation Action bars -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-3">
                <nav class="flex items-center gap-2 text-[10px] font-black text-white/40 uppercase tracking-widest leading-none mb-2">
                    <span>ConstructPro</span>
                    <ChevronRight class="w-3 h-3 text-white/20" />
                    <span class="text-primary">{{ activeTab === 'log' ? 'Bitácora de Vehículos' : 'Control de Vehículos' }}</span>
                </nav>

                <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">
                    {{ activeTab === 'log' ? 'Bitácora de Vehículos' : 'Control de Vehículos' }}
                </h2>
                <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">
                    {{ activeTab === 'log'
                        ? 'Hoja de movimientos diarios, asignación de pilotos y reparaciones'
                        : 'Hoja de inventario, catálogo general y registro de flota' }}
                </p>
            </div>

            <!-- Triple Tab Selection -->
            <div class="flex gap-2.5 bg-white/5 p-1.5 rounded-2xl border border-white/5 shadow-inner flex-nowrap overflow-x-auto">
                <button
                    @click="activeTab = 'fleet'; resetForm()"
                    :class="['px-5 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', activeTab === 'fleet'
                            ? 'bg-primary text-white shadow-lg shadow-primary/25'
                            : 'text-white/50 hover:text-white']"
                >
                    Ver Flota
                </button>

                <button
                    @click="activeTab = 'log'"
                    :class="['px-5 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap flex items-center gap-2', activeTab === 'log'
                            ? 'bg-primary text-white shadow-lg shadow-primary/25'
                            : 'text-white/50 hover:text-white']"
                >
                    <ClipboardList class="w-3.5 h-3.5" />
                    Bitácora Diario
                </button>

                <button
                    @click="activeTab = 'register'"
                    :class="['px-5 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap flex items-center gap-2', activeTab === 'register'
                            ? 'bg-primary text-white shadow-lg shadow-primary/25'
                            : 'text-white/50 hover:text-white']"
                >
                    <Plus class="w-3.5 h-3.5" />
                    {{ editingVehicleId ? 'Modificar Unidad' : 'Registrar Unidad' }}
                </button>
            </div>
        </div>

        <!-- KPI Cards Bento Box Grid Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Metric 1 -->
            <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-primary flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
                <div class="flex flex-col justify-between h-full w-full">
                    <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total Vehículos</span>
                    <div>
                        <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ stats.total }}</h3>
                        <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">Unidades registradas</p>
                    </div>
                </div>
                <div class="p-3 bg-primary/10 border border-primary/20 rounded-2xl text-primary">
                    <Car class="w-5 h-5" />
                </div>
            </div>

            <!-- Metric 2 -->
            <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
                <div class="flex flex-col justify-between h-full w-full">
                    <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Operativos</span>
                    <div>
                        <h3 class="text-4xl font-black italic text-emerald-400 tracking-tighter">{{ stats.operational }}</h3>
                        <p class="text-[10px] font-bold text-emerald-400 tracking-wider uppercase mt-1 flex items-center gap-1">
                            <UserCheck class="w-3 h-3 text-emerald-400" /> Disponibles
                        </p>
                    </div>
                </div>
                <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400">
                    <Fuel class="w-5 h-5" />
                </div>
            </div>

            <!-- Metric 3 -->
            <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-amber-500/50 flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
                <div class="flex flex-col justify-between h-full w-full">
                    <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">En Mantenimiento</span>
                    <div>
                        <h3 class="text-4xl font-black italic text-amber-400 tracking-tighter">{{ stats.maintenance }}</h3>
                        <p class="text-[10px] font-bold text-amber-500/80 tracking-wider uppercase mt-1">Talleres mecánicos</p>
                    </div>
                </div>
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-amber-400">
                    <Wrench class="w-5 h-5" />
                </div>
            </div>

            <!-- Metric 4 -->
            <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-rose-500/50 flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
                <div class="flex flex-col justify-between h-full w-full">
                    <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Con Averías / Inactivos</span>
                    <div>
                        <h3 class="text-4xl font-black italic text-rose-400 tracking-tighter">{{ stats.inactive }}</h3>
                        <p class="text-[10px] font-bold text-rose-400 tracking-wider uppercase mt-1 flex items-center gap-1">
                            <AlertTriangle class="w-3 h-3 text-rose-400" /> Atención requerida
                        </p>
                    </div>
                </div>
                <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-400">
                    <AlertTriangle class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Main Tab Controller Screens -->
        <template v-if="activeTab === 'fleet'">
            <div class="space-y-6">
                <!-- Quick Filters controls -->
                <div class="flex flex-col lg:flex-row justify-between items-center gap-6 border-b border-white/5 pb-6">
                    <div class="flex gap-2 bg-white/5 p-1.5 rounded-2xl border border-white/5 overflow-x-auto w-full lg:w-auto scrollbar-hide">
                        <button
                            v-for="filter in ['all', 'active', 'maintenance', 'inactive', 'draft']"
                            :key="filter"
                            @click="statusFilter = filter as any"
                            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap', statusFilter === filter ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']"
                        >
                            {{ filter === 'all' ? 'Todos' :
                               filter === 'active' ? 'Operativos' :
                               filter === 'maintenance' ? 'En Taller' :
                               filter === 'inactive' ? 'Con Avería' : 'Borrador' }}
                        </button>
                    </div>

                    <!-- Quick search input -->
                    <div class="relative w-full lg:w-96">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
                        <input
                            type="text"
                            placeholder="Buscar por placa, marca o modelo..."
                            v-model="searchTerm"
                            class="glass-input pl-11 pr-4 py-3 rounded-xl text-xs uppercase tracking-wider font-extrabold w-full text-white placeholder:text-white/20"
                        />
                    </div>
                </div>

                <!-- Grid Layout of Cards for Vehicles -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div v-if="filteredVehicles.length === 0" class="col-span-full py-16 text-center bg-white/5 border border-white/5 rounded-3xl text-white/30 font-black uppercase tracking-widest text-xs">
                        No se encontraron unidades registradas con los filtros vigentes.
                    </div>
                    <div
                        v-for="v in filteredVehicles"
                        :key="v.id"
                        class="glass-card rounded-[32px] border border-white/5 p-8 flex flex-col justify-between hover:border-white/15 transition-all relative overflow-hidden"
                    >
                        <div>
                            <!-- Top plate indicator, status tag -->
                            <div class="flex items-center justify-between mb-6">
                                <span class="font-mono text-lg font-black tracking-widest bg-white/5 border border-white/10 px-4 py-1.5 rounded-xl text-primary font-bold">
                                    {{ v.plate }}
                                </span>

                                <div class="flex items-center gap-2">
                                    <span :class="['px-2.5 py-1 rounded bg-white/5 text-[9px] font-black uppercase tracking-widest border', v.priority === 'Crítica' ? 'text-rose-400 border-rose-500/20 bg-rose-500/5' :
                                            v.priority === 'Preventiva' ? 'text-amber-400 border-amber-500/20 bg-amber-500/5' : 'text-white/40 border-white/5'
                                        ]">
                                        {{ v.priority }}
                                    </span>

                                    <span :class="['px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border', v.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                                            v.status === 'maintenance' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                                            v.status === 'inactive' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-white/5 text-white/35 border-white/5'
                                        ]">
                                        {{ v.status === 'active' ? 'Operativo' : v.status === 'maintenance' ? 'En Taller' : v.status === 'inactive' ? 'Con Avería' : 'Borrador' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Brand & Model details -->
                            <div class="space-y-1">
                                <h4 class="text-xl font-black italic tracking-tight uppercase text-white/90">
                                    {{ v.brand }} <span class="text-primary font-normal">{{ v.model }}</span>
                                </h4>
                                <p class="text-[10px] font-semibold text-white/30 tracking-widest uppercase mb-4">
                                    ID REGISTRO: {{ v.id }}
                                </p>
                            </div>

                            <!-- Pilot display, notes -->
                            <div class="space-y-4 my-6 bg-white/[0.02] border border-white/5 rounded-2xl p-4">
                                <div class="flex items-start gap-3">
                                    <User class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                                    <div>
                                        <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">Piloto Asignado</span>
                                        <span :class="['text-xs font-black uppercase', !!v.pilotId ? 'text-white/80' : 'text-white/35 italic']">
                                            {{ getPilotDisplay(v.pilotId) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Fuel class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                                    <div>
                                        <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">Kilometraje</span>
                                        <span class="text-xs font-black text-white/80">{{ v.mileage.toLocaleString() }} KM</span>
                                    </div>
                                </div>

                                <div v-if="v.notes" class="text-[11px] font-bold text-white/40 leading-relaxed italic pt-2 border-t border-white/5">
                                    "{{ v.notes }}"
                                </div>
                            </div>
                        </div>

                        <!-- Footer toggles buttons -->
                        <div class="flex items-center justify-end gap-2 pt-4 border-t border-white/5 mt-4">
                            <button
                                @click="startEditVehicle(v)"
                                class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/60 hover:text-white rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5"
                            >
                                <Edit3 class="w-3.5 h-3.5" /> Modificar
                            </button>
                            <button
                                @click="handleDeleteVehicle(v.id, v.plate)"
                                class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/30 hover:text-rose-400 rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5"
                            >
                                <Trash2 class="w-3.5 h-3.5" /> Retirar
                            </button>
                        </div>

                        <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-primary/5 rounded-full blur-2xl"></div>
                    </div>
                </div>
            </div>
        </template>
        
        <template v-else-if="activeTab === 'log'">
            <!-- Operations Log Section (formerly VehicleLog) -->
            <div class="space-y-8">
                <!-- Sub Header for Log tab -->
                <div class="flex flex-col lg:flex-row justify-between items-center gap-6 border-b border-white/5 pb-6">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-black uppercase tracking-widest text-white/40">Filtro Rápido Bitácora:</span>
                        <div class="flex gap-2 bg-white/5 p-1 rounded-xl border border-white/5">
                            <button
                                v-for="filter in ['all', 'active', 'maintenance', 'inactive']"
                                :key="filter"
                                @click="statusFilter = filter as any"
                                :class="['px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all', statusFilter === filter ? 'bg-primary text-white' : 'text-white/40 hover:text-white']"
                            >
                                {{ filter === 'all' ? 'Todos' :
                                   filter === 'active' ? 'Operativos' :
                                   filter === 'maintenance' ? 'En Taller' : 'Con Falla / Inactivos' }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full lg:w-auto">
                        <div class="relative flex-grow lg:w-64">
                            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
                            <input
                                type="text"
                                placeholder="Buscar unidad o placa..."
                                v-model="searchTerm"
                                class="glass-input pl-11 pr-4 py-2 rounded-xl text-xs uppercase tracking-wider font-extrabold w-full text-white placeholder:text-white/20"
                            />
                        </div>

                        <button
                            @click="openLogModal"
                            class="px-5 py-2.5 bg-primary text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg hover:opacity-90 active:scale-95 transition-all flex items-center gap-2 shrink-0"
                        >
                            <ClipboardList class="w-4 h-4" /> Registrar Bitácora
                        </button>
                    </div>
                </div>

                <!-- List/Table of Vehicles Log Entries -->
                <div class="glass-card rounded-[32px] overflow-hidden border border-white/5 shadow-2xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                                    <th class="px-8 py-5">Vehículo / ID Placa</th>
                                    <th class="px-8 py-5 text-center">Estado Enlace</th>
                                    <th class="px-8 py-5">Chofer / Piloto Asignado</th>
                                    <th class="px-8 py-5">Última Operación / Reporte</th>
                                    <th class="px-8 py-5 text-right">Controles Rápidos</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr v-if="filteredVehicles.length === 0">
                                    <td colspan="5" class="px-8 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">
                                        No se han encontrado registros en bitácora para el filtro aplicado.
                                    </td>
                                </tr>
                                <tr v-else v-for="v in filteredVehicles" :key="v.id" class="hover:bg-white/[0.015] transition-colors">
                                    <!-- Vehicle details -->
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-primary shrink-0">
                                                <Car class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <h4 class="font-extrabold text-white text-xs uppercase italic">
                                                    {{ v.brand }} {{ v.model }}
                                                </h4>
                                                <p class="text-[9px] font-mono text-[#60e0ff] tracking-widest uppercase mt-0.5">
                                                    PLACA: {{ v.plate }} • ID: {{ v.id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="px-8 py-5 text-center">
                                        <span :class="['inline-flex px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border', v.status === 'active'
                                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                                                : v.status === 'maintenance'
                                                    ? 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                                                    : v.status === 'inactive'
                                                        ? 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                                        : 'bg-white/5 text-white/30 border-white/5'
                                            ]">
                                            {{ v.status === 'active' ? 'OPERATIVO' :
                                               v.status === 'maintenance' ? 'EN TALLER' :
                                               v.status === 'inactive' ? 'AVERIADO' : 'BORRADOR' }}
                                        </span>
                                    </td>

                                    <!-- Pilot ref -->
                                    <td class="px-8 py-5">
                                        <div v-if="v.pilotId" class="flex items-center gap-2.5">
                                            <div class="w-6 h-6 rounded bg-primary/10 border border-primary/25 flex items-center justify-center text-white text-[9px] font-black tracking-tighter">
                                                {{ getPilotInitials(v.pilotId) }}
                                            </div>
                                            <span class="text-xs font-bold text-white/95">{{ getPilotNameOnly(v.pilotId) }}</span>
                                        </div>
                                        <span v-else class="text-[10px] font-bold text-white/20 italic tracking-wider uppercase">
                                            Sin piloto asignado
                                        </span>
                                    </td>

                                    <!-- Description notes or logs -->
                                    <td class="px-8 py-5">
                                        <div class="space-y-0.5">
                                            <span class="text-xs font-black text-white/80">
                                                {{ v.history[0] ? `${v.history[0].type}: ${v.history[0].date}` : "Sin historial" }}
                                            </span>
                                            <p class="text-[10px] font-medium text-white/40 leading-relaxed max-w-xs truncate" :title="v.notes">
                                                {{ v.notes || "No se cargaron observaciones operacionales." }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Quick Action triggers -->
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="showVehicleHistory(v)"
                                                title="Visualizar Historial"
                                                class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-xl border border-white/10 transition-all text-[10px] font-black uppercase flex items-center gap-2"
                                            >
                                                <Eye class="w-3.5 h-3.5" /> Visualizar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- simple tracking label footer -->
                    <div class="px-8 py-4 bg-white/[0.01] border-t border-white/5 flex items-center justify-between font-bold text-[9px] text-white/35 tracking-wider uppercase">
                        <span>Bitácora sincronizada. Mostrando {{ filteredVehicles.length }} unidades vehiculares operativas</span>
                        <span>ConstructPro Fleet Logs v2</span>
                    </div>
                </div>

            </div>
        </template>
        <template v-else>
            <!-- Form registration section -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Form blocks -->
                <div class="lg:col-span-8 space-y-8">
                    <form @submit.prevent="handleSaveVehicle" id="vehicle-register-form" class="space-y-6">
                        <!-- Box 1: Información General -->
                        <section class="glass-card p-8 rounded-3xl border border-white/5 shadow-2xl relative overflow-hidden">
                            <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl"></div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2.5">
                                <Info class="w-4 h-4" /> Información General de la Unidad
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Plate Field -->
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Placa <span class="text-rose-400">*</span></label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="ABC-1234"
                                        v-model="plate"
                                        class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary focus:bg-white/5 transition-all text-sm font-black uppercase tracking-widest text-white"
                                    />
                                </div>

                                <!-- Mileage Field -->
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Kilometraje de Registro</label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            placeholder="0.00"
                                            min="0"
                                            v-model.number="mileage"
                                            class="w-full h-12 pl-4 pr-12 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white"
                                        />
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-white/40 tracking-widest">KM</span>
                                    </div>
                                </div>

                                <!-- Brand Field -->
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Marca <span class="text-rose-400">*</span></label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ej: Toyota, Volvo, Caterpillar"
                                        v-model="brand"
                                        class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white"
                                    />
                                </div>

                                <!-- Model Field -->
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Modelo <span class="text-rose-400">*</span></label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="Ej: Hilux 2024"
                                        v-model="model"
                                        class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white"
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- Box 2: Piloto Asignado -->
                        <section class="glass-card p-8 rounded-3xl border border-white/5 shadow-2xl">
                            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2.5">
                                <User class="w-4 h-4" /> Piloto Asignado
                            </h3>

                            <div class="grid grid-cols-1 gap-6">
                                <div class="w-full space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Seleccionar Piloto Homologado</label>
                                    <select
                                        v-model="pilotId"
                                        class="w-full h-12 pl-4 pr-10 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                                    >
                                        <option value="" class="bg-slate-950 text-white/60">Ninguno seleccionado (Sin asignar)</option>
                                        <option v-for="p in pilots" :key="p.id" :value="p.id" class="bg-slate-950 text-white">
                                            {{ p.name }} ({{ p.license }})
                                        </option>
                                    </select>
                                </div>

                                <!-- System message check -->
                                <p v-if="!pilotId" class="text-[11px] font-bold text-white/40 flex items-start gap-2 max-w-xl italic">
                                    <Info class="w-3.5 h-3.5 text-primary mt-0.5 shrink-0" />
                                    Si no seleccionas un piloto, la unidad se guardará inicialmente en estado operativo "Borrador / Sin asignar".
                                </p>
                                <p v-else class="text-[11px] font-bold text-emerald-400 flex items-start gap-2 max-w-xl italic">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400 mt-0.5 shrink-0" />
                                    El piloto ha sido asignado. El sistema actualizará el estado operacional en el panel principal de flota automáticamente.
                                </p>
                            </div>
                        </section>

                        <!-- Box 3: Extras, priority & notes -->
                        <section class="glass-card p-8 rounded-3xl border border-white/5 shadow-2xl">
                            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2.5">
                                <SlidersHorizontal class="w-4 h-4" /> Configuración Adicional
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Priority check -->
                                <div class="space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Frecuencia / Prioridad de Prioridad</label>
                                    <select
                                        v-model="vehiclePriority"
                                        class="w-full h-12 pl-4 pr-10 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                                    >
                                        <option value="Mantenimiento Inicial">Mantenimiento Inicial</option>
                                        <option value="Normal">Prioridad Normal</option>
                                        <option value="Preventiva">Prioridad Preventiva</option>
                                        <option value="Crítica">Estado Crítico</option>
                                    </select>
                                </div>

                                <!-- Status checklist if editing -->
                                <div v-if="editingVehicleId" class="space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Estatus Actual</label>
                                    <select
                                        v-model="vehicleStatus"
                                        class="w-full h-12 pl-4 pr-10 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                                    >
                                        <option value="draft">Borrador / Sin Asignar</option>
                                        <option value="active">Activo Operando</option>
                                        <option value="maintenance">En Taller Mantenimiento</option>
                                        <option value="inactive">Inactivo de Baja</option>
                                    </select>
                                </div>

                                <!-- Notes Area -->
                                <div class="col-span-full space-y-2">
                                    <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Observaciones / Descripción Inicial</label>
                                    <textarea
                                        v-model="notes"
                                        placeholder="Indique los detalles de entrada, fallas detectadas preliminares o requerimientos..."
                                        rows="3"
                                        class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white resize-none"
                                    ></textarea>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>

                <!-- Right Form blocks: Photo Uploads & Visual live Preview card -->
                <div class="lg:col-span-4 space-y-8">
                    <!-- Fotos Block -->
                    <section class="glass-card p-8 rounded-3xl border border-white/5 shadow-2xl relative">
                        <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2.5">
                            <Camera class="w-4 h-4" /> Registro Fotográfico
                        </h3>

                        <div class="grid grid-cols-1 gap-5">
                            <!-- Front Photo Input -->
                            <div class="group relative aspect-video rounded-2xl bg-white/5 hover:bg-white/10 border-2 border-dashed border-white/10 hover:border-primary transition-all flex flex-col items-center justify-center cursor-pointer overflow-hidden text-center">
                                <img v-if="frontPhotoPreview" :src="frontPhotoPreview" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity z-0" />
                                
                                <div class="z-10 flex flex-col items-center justify-center relative p-3 rounded-xl transition-all" :class="frontPhotoPreview ? 'bg-slate-950/60 backdrop-blur-md opacity-0 group-hover:opacity-100' : ''">
                                    <Camera class="w-8 h-8 text-white/30 group-hover:text-primary transition-colors mb-2" />
                                    <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Foto Frontal / Tres cuartos</p>
                                    <p class="text-[10px] font-medium text-white/20 lowercase mt-1">
                                        {{ frontPhotoName ? `✓ ${frontPhotoName}` : 'Seleccionar Imagen' }}
                                    </p>
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="onFrontPhotoChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20"
                                />
                            </div>

                            <!-- Rear Photo Input -->
                            <div class="group relative aspect-video rounded-2xl bg-white/5 hover:bg-white/10 border-2 border-dashed border-white/10 hover:border-primary transition-all flex flex-col items-center justify-center cursor-pointer overflow-hidden text-center">
                                <img v-if="rearPhotoPreview" :src="rearPhotoPreview" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity z-0" />

                                <div class="z-10 flex flex-col items-center justify-center relative p-3 rounded-xl transition-all" :class="rearPhotoPreview ? 'bg-slate-950/60 backdrop-blur-md opacity-0 group-hover:opacity-100' : ''">
                                    <Camera class="w-8 h-8 text-white/30 group-hover:text-primary transition-colors mb-2" />
                                    <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Foto Trasera / Placa</p>
                                    <p class="text-[10px] font-medium text-white/20 lowercase mt-1">
                                        {{ rearPhotoName ? `✓ ${rearPhotoName}` : 'Seleccionar Imagen' }}
                                    </p>
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="onRearPhotoChange"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20"
                                />
                            </div>
                        </div>

                        <!-- Informative Lightbulb tip -->
                        <div class="mt-6 p-4 rounded-xl bg-white/5 border border-white/5">
                            <p class="text-[11px] font-bold text-white/40 leading-relaxed italic">
                                Las fotografías deben ser nítidas y mostrar el estado actual de la carrocería para el inventario inicial de la flota.
                            </p>
                        </div>
                    </section>

                    <!-- Visual Live Preview Card -->
                    <div class="bg-primary p-6 rounded-3xl text-white shadow-2xl relative overflow-hidden">
                        <div class="relative z-10 space-y-6">
                            <div>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Tarjeta Digital de Control</h4>
                                <p class="text-3xl font-black italic tracking-tighter uppercase mt-1">Vista Previa</p>
                            </div>

                            <div class="space-y-3 pt-2 text-xs">
                                <div class="flex justify-between items-center border-b border-white/10 pb-2.5">
                                    <span class="font-bold uppercase tracking-wider text-white/60 text-[9px]">Estatus</span>
                                    <span class="bg-white/15 px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest">
                                        {{ editingVehicleId ? vehicleStatus : (pilotId ? 'Activo' : 'Borrador') }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center border-b border-white/10 pb-2.5">
                                    <span class="font-bold uppercase tracking-wider text-white/60 text-[9px]">Registro Placa</span>
                                    <span class="font-mono font-black uppercase text-sm tracking-widest text-[#60e0ff]">
                                        {{ plate ? plate.toUpperCase() : 'FLT-2024-NEW' }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center border-b border-white/10 pb-2.5">
                                    <span class="font-bold uppercase tracking-wider text-white/60 text-[9px]">Marca / Modelo</span>
                                    <span class="font-black uppercase truncate text-white/95">
                                        {{ brand || model ? `${brand} ${model}` : 'Toyota Hilux' }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="font-bold uppercase tracking-wider text-white/60 text-[9px]">Prioridad Frecuencia</span>
                                    <span class="font-black tracking-wide text-white/90 text-[11px]">
                                        {{ vehiclePriority }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Decorative background element -->
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                    </div>

                    <!-- Form actions triggers inside register/edit view -->
                    <div class="flex gap-4 pt-4">
                        <button
                            type="submit"
                            form="vehicle-register-form"
                            class="flex-grow bg-primary hover:opacity-90 text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl transition-all cursor-pointer"
                        >
                            {{ editingVehicleId ? 'Cruzar Cambios' : 'Guardar Vehículo' }}
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'fleet'; resetForm()"
                            class="px-6 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 cursor-pointer"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </template>


        <!-- Log Movement Registrar Modal Dialog -->
        <Transition name="modal">
            <div v-if="isLogModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6">
                <div
                    @click="isLogModalOpen = false"
                    class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"
                ></div>

                <Transition name="modal-scale" appear>
                    <div class="relative w-full max-w-lg bg-slate-950 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] text-white">
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-primary/5 rounded-full blur-2xl"></div>

                        <!-- Modal Header -->
                        <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-6 sticky top-0 bg-slate-950 z-10">
                            <h4 class="text-sm font-black italic uppercase tracking-tight text-white">
                                Registrar Bitácora
                            </h4>
                            <button
                                @click="isLogModalOpen = false"
                                class="p-1.5 hover:bg-white/10 rounded-lg text-white/50 hover:text-white transition-all"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <form @submit.prevent="submitVehicleLog" class="space-y-6">
                            
                            <!-- Select Vehicle -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Seleccionar Vehículo <span class="text-rose-400">*</span></label>
                                <select
                                    v-model="logVehicleId"
                                    required
                                    class="w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-xs font-black text-white focus:outline-none focus:border-primary uppercase"
                                >
                                    <option value="" disabled>Seleccione un vehículo</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate }} - {{ v.brand }} {{ v.model }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Asignar Piloto -->
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Asignar Piloto</label>
                                    <select
                                        v-model="logPilotId"
                                        class="w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-xs font-black text-white focus:outline-none focus:border-primary uppercase"
                                    >
                                        <option value="">Ninguno / Sin Asignar</option>
                                        <option v-for="p in pilots" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                </div>
                                <!-- Estado -->
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Estado de la unidad</label>
                                    <select
                                        v-model="logStatus"
                                        class="w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-xs font-black text-white focus:outline-none focus:border-primary uppercase"
                                    >
                                        <option value="active">OPERATIVO</option>
                                        <option value="maintenance">EN TALLER / SERVICIO</option>
                                        <option value="inactive">AVERIADO / INACTIVO</option>
                                        <option value="draft">BORRADOR / SIN ASIGNAR</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Envio a Servicio -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 text-white">Envío a Servicio</label>
                                <textarea
                                    v-model="logServiceText"
                                    placeholder="Detalles del servicio técnico o mantenimiento..."
                                    rows="2"
                                    class="w-full glass-input rounded-2xl p-4 text-xs font-bold placeholder:text-white/20 text-white resize-none"
                                ></textarea>
                            </div>

                            <!-- Reportar Avería -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 text-white">Reportar Avería</label>
                                <textarea
                                    v-model="logIssueText"
                                    placeholder="Descripción del diagnóstico o falla reportada..."
                                    rows="2"
                                    class="w-full glass-input rounded-2xl p-4 text-xs font-bold placeholder:text-white/20 text-white resize-none"
                                ></textarea>
                            </div>

                            <!-- Observaciones -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 text-white">Observaciones</label>
                                <textarea
                                    v-model="logObservations"
                                    placeholder="Comentarios adicionales o vigentes de la ficha..."
                                    rows="2"
                                    class="w-full glass-input rounded-2xl p-4 text-xs font-bold placeholder:text-white/20 text-white resize-none"
                                ></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-4 pt-4 border-t border-white/5">
                                <button
                                    type="button"
                                    @click="isLogModalOpen = false"
                                    class="w-1/2 py-3.5 bg-white/5 text-white/60 hover:text-white hover:bg-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="px-6 py-3 bg-primary text-white font-black uppercase text-xs tracking-wider rounded-xl shadow-lg hover:shadow-primary/30 transition-all cursor-pointer"
                                >
                                    Confirmar Acción
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import Swal from 'sweetalert2';
import {
    Car,
    User,
    Camera,
    Trash2,
    Edit3,
    CheckCircle2,
    Plus,
    Search,
    Info,
    SlidersHorizontal,
    ChevronLeft,
    ChevronRight,
    TrendingUp,
    Fuel,
    Wrench,
    UserCheck,
    AlertTriangle,
    ClipboardList,
    FileSpreadsheet,
    X,
    FileText,
    UserPlus,
    Eye
} from 'lucide-vue-next';

interface Pilot {
    id: string;
    name: string;
    license: string;
}

interface VehicleLogEvent {
    date: string;
    type: string;
    description: string;
}

interface Vehicle {
    id: string;
    plate: string;
    mileage: number;
    brand: string;
    model: string;
    pilotId: string;
    status: "active" | "maintenance" | "inactive" | "draft";
    priority: "Mantenimiento Inicial" | "Normal" | "Crítica" | "Preventiva";
    notes?: string;
    frontPhoto?: string;
    rearPhoto?: string;
    history: VehicleLogEvent[];
}

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// State
const activeTab = ref<"fleet" | "register" | "log">("fleet");
const searchTerm = ref("");
const statusFilter = ref<"all" | "active" | "maintenance" | "inactive" | "draft">("all");

const pilots = ref<Pilot[]>([]);
const vehicles = ref<Vehicle[]>([]);

const editingVehicleId = ref<string | null>(null);
const plate = ref("");
const mileage = ref<number | "">("");
const brand = ref("");
const model = ref("");
const pilotId = ref("");
const vehiclePriority = ref<"Mantenimiento Inicial" | "Normal" | "Crítica" | "Preventiva">("Normal");
const vehicleStatus = ref<"active" | "maintenance" | "inactive" | "draft">("draft");
const notes = ref("");

const frontPhotoName = ref("");
const rearPhotoName = ref("");
const frontPhotoPreview = ref<string | null>(null);
const rearPhotoPreview = ref<string | null>(null);

// Vehicle Log (Bitácora) variables
const isLogModalOpen = ref(false);
const logVehicleId = ref("");
const logPilotId = ref("");
const logStatus = ref("active");
const logServiceText = ref("");
const logIssueText = ref("");
const logObservations = ref("");

// Auto-select pilot and status when vehicle changes in log form
watch(logVehicleId, (newId) => {
    if (newId) {
        const vehicle = vehicles.value.find((v) => v.id === newId);
        if (vehicle) {
            logPilotId.value = vehicle.pilotId || "";
            logStatus.value = vehicle.status || "active";
        }
    } else {
        logPilotId.value = "";
        logStatus.value = "active";
    }
});

const isActionModalOpen = ref(false);
const modalType = ref<"assign-pilot" | "maintenance" | "report-issue" | "observations" | "new-movement">("assign-pilot");
const selectedVehicleForLog = ref<Vehicle | null>(null);

const detailsText = ref("");
const toastMessage = ref("");

// Methods
const triggerToast = (msg: string, icon: "success" | "error" | "warning" | "info" = "success") => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: msg,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        background: '#0f172a', // slate-900
        color: '#ffffff'
    });
};

const fetchPilots = async () => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`${BASE_URL}/personnel`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        const result = await response.json();
        if (result.status === 'success' || result.success) {
            // Filtrar solo a los que son Pilotos
            const allPersonnel = result.data || [];
            pilots.value = allPersonnel
                .filter((p: any) => p.tipo_empleado === 'Piloto')
                .map((p: any) => ({
                    id: String(p.id),
                    name: `${p.nombres} ${p.apellidos}`,
                    license: "Piloto" // Si tuvieran licencia en DB iría aquí
                }));
        }
    } catch (error) {
        console.error("Error fetching pilots:", error);
    }
};

const fetchVehicles = async () => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`${BASE_URL}/vehicles`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        const result = await response.json();
        if (result.success) {
            vehicles.value = (result.data || []).map((v: any) => ({
                id: String(v.id),
                plate: v.placa,
                mileage: Number(v.kilometraje),
                brand: v.marca,
                model: v.modelo,
                pilotId: v.piloto_id ? String(v.piloto_id) : "",
                status: v.estatus || "draft",
                priority: v.frecuencia_prioridad || "Normal",
                notes: v.observaciones || "",
                frontPhoto: v.foto_frontal,
                rearPhoto: v.foto_trasera,
                history: v.history || []
            }));
        }
    } catch (error) {
        console.error("Error fetching vehicles:", error);
    }
};

onMounted(() => {
    fetchPilots();
    fetchVehicles();
});

const handleCreateNewPilot = () => {
    const pName = window.prompt("Ingrese el nombre completo del nuevo Piloto:");
    if (!pName || !pName.trim()) return;
    const pLic = window.prompt("Ingrese el tipo de licencia (ej: Lic. Tipo A, Lic. Especial):");
    if (!pLic || !pLic.trim()) return;

    const newPilot: Pilot = {
        id: String(pilots.value.length + 1),
        name: pName.trim(),
        license: pLic.trim()
    };
    pilots.value.push(newPilot);
    pilotId.value = newPilot.id;
    triggerToast(`Piloto "${newPilot.name}" registrado correctamente.`);
};

const resetForm = () => {
    editingVehicleId.value = null;
    plate.value = "";
    mileage.value = "";
    brand.value = "";
    model.value = "";
    pilotId.value = "";
    vehiclePriority.value = "Normal";
    vehicleStatus.value = "draft";
    notes.value = "";
    
    if (frontPhotoPreview.value) URL.revokeObjectURL(frontPhotoPreview.value);
    if (rearPhotoPreview.value) URL.revokeObjectURL(rearPhotoPreview.value);
    
    frontPhotoName.value = "";
    rearPhotoName.value = "";
    frontPhotoPreview.value = null;
    rearPhotoPreview.value = null;
    frontPhotoFile = null;
    rearPhotoFile = null;
};

const handleSaveVehicle = async () => {
    if (!plate.value.trim()) {
        triggerToast("Por favor especifique la placa del vehículo.", "warning");
        return;
    }
    if (!brand.value.trim() || !model.value.trim()) {
        triggerToast("Por favor ingrese marca y modelo de la unidad.", "warning");
        return;
    }

    try {
        const formData = new FormData();
        formData.append("placa", plate.value.toUpperCase());
        formData.append("kilometraje", String(mileage.value || 0));
        formData.append("marca", brand.value);
        formData.append("modelo", model.value);
        formData.append("piloto_id", pilotId.value);
        formData.append("frecuencia_prioridad", vehiclePriority.value);
        formData.append("estatus", editingVehicleId.value ? vehicleStatus.value : (pilotId.value ? "active" : "draft"));
        formData.append("observaciones", notes.value);

        if (frontPhotoFile) {
            formData.append("foto_frontal", frontPhotoFile);
        }
        if (rearPhotoFile) {
            formData.append("foto_trasera", rearPhotoFile);
        }

        const token = localStorage.getItem('token');
        const url = editingVehicleId.value 
            ? `${BASE_URL}/vehicles/update/${editingVehicleId.value}`
            : `${BASE_URL}/vehicles`;

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            triggerToast(editingVehicleId.value 
                ? `Ficha del vehículo ${plate.value.toUpperCase()} guardada correctamente.`
                : `Vehículo registrado exitosamente.`, "success");
            resetForm();
            activeTab.value = "fleet";
            fetchVehicles();
        } else {
            triggerToast(result.message || "Error al procesar el vehículo.", "error");
        }
    } catch (error) {
        triggerToast("Error de conexión al guardar.", "error");
    }
};

const startEditVehicle = (vehicle: Vehicle) => {
    editingVehicleId.value = vehicle.id;
    plate.value = vehicle.plate;
    mileage.value = vehicle.mileage;
    brand.value = vehicle.brand;
    model.value = vehicle.model;
    pilotId.value = vehicle.pilotId;
    vehiclePriority.value = vehicle.priority;
    vehicleStatus.value = vehicle.status;
    notes.value = vehicle.notes || "";
    activeTab.value = "register";
};

const handleDeleteVehicle = async (id: string, plateCode: string) => {
    const confirm = await Swal.fire({
        title: '¿Retirar vehículo?',
        text: `¿Está seguro de que desea retirar el vehículo [${plateCode}] de la flota activa?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e', // rose-500
        cancelButtonColor: '#475569', // slate-600
        confirmButtonText: 'Sí, retirar',
        cancelButtonText: 'Cancelar',
        background: '#0f172a',
        color: '#ffffff'
    });

    if (confirm.isConfirmed) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`${BASE_URL}/vehicles/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            const result = await response.json();
            
            if (result.success) {
                triggerToast(`Vehículo [${plateCode}] retirado de la base de datos.`);
                fetchVehicles();
            } else {
                triggerToast(result.message || "Error al retirar.", "error");
            }
        } catch(error) {
            triggerToast("Error de conexión.", "error");
        }
    }
};

const openLogActionModal = (type: "assign-pilot" | "maintenance" | "report-issue" | "observations", vehicle: Vehicle) => {
    modalType.value = type;
    selectedVehicleForLog.value = vehicle;
    detailsText.value = "";
    if (type === "assign-pilot") {
        detailsText.value = vehicle.pilotId;
    } else if (type === "observations") {
        detailsText.value = vehicle.notes || "";
    }
    isActionModalOpen.value = true;
};

const handleLogActionConfirm = () => {
    if (!selectedVehicleForLog.value) return;
    const targetId = selectedVehicleForLog.value.id;

    if (modalType.value === "assign-pilot") {
        const index = vehicles.value.findIndex(v => v.id === targetId);
        if (index !== -1) {
            const pilotObj = pilots.value.find(p => p.id === detailsText.value);
            const pNameDesc = pilotObj ? pilotObj.name : "Sin asignar (Liberado)";
            vehicles.value[index] = {
                ...vehicles.value[index],
                pilotId: detailsText.value,
                notes: `Piloto reasignado a: ${pNameDesc}.`,
                history: [{ date: new Date().toISOString().split("T")[0], type: "Asignación", description: `Unidad operada hoy por el piloto asignado: ${pNameDesc}.` }, ...vehicles.value[index].history]
            };
            triggerToast(`Operación confirmada: Piloto ${pilotObj ? pilotObj.name : "relevado"} asignado a placa [${selectedVehicleForLog.value.plate}].`);
        }
    } else if (modalType.value === "maintenance") {
        const index = vehicles.value.findIndex(v => v.id === targetId);
        if (index !== -1) {
            vehicles.value[index] = {
                ...vehicles.value[index],
                status: "maintenance",
                notes: detailsText.value || "Enviado a servicio de afinamiento de motor y revisión hidráulica preventiva.",
                history: [{ date: new Date().toISOString().split("T")[0], type: "Mantenimiento", description: detailsText.value || "Ingresó al taller por requerimiento de servicio técnico diario." }, ...vehicles.value[index].history]
            };
            triggerToast(`Vehículo [${selectedVehicleForLog.value.plate}] enviado a Servicio de Mantenimiento.`);
        }
    } else if (modalType.value === "report-issue") {
        const index = vehicles.value.findIndex(v => v.id === targetId);
        if (index !== -1) {
            vehicles.value[index] = {
                ...vehicles.value[index],
                status: "inactive",
                priority: "Crítica",
                notes: `FALLA CRÍTICA: ${detailsText.value || "Pérdida de fuerza detectada. Reparación urgente requerida."}`,
                history: [{ date: new Date().toISOString().split("T")[0], type: "Falla", description: `Reporte de Avería: ${detailsText.value || "Problema técnico"}` }, ...vehicles.value[index].history]
            };
            triggerToast(`Falla técnico-operativa registrada en la unidad [${selectedVehicleForLog.value.plate}]. Status: INACTIVO`);
        }
    } else if (modalType.value === "observations") {
        const index = vehicles.value.findIndex(v => v.id === targetId);
        if (index !== -1) {
            vehicles.value[index] = {
                ...vehicles.value[index],
                notes: detailsText.value,
                history: [{ date: new Date().toISOString().split("T")[0], type: "Nota", description: `Ficha de observaciones actualizada: ${detailsText.value}` }, ...vehicles.value[index].history]
            };
            triggerToast(`Observaciones actualizadas para la unidad [${selectedVehicleForLog.value.plate}].`);
        }
    }

    isActionModalOpen.value = false;
};

// Computed
const filteredVehicles = computed(() => {
    return vehicles.value.filter(v => {
        const matchText =
            v.plate.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            v.brand.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            v.model.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            v.id.toLowerCase().includes(searchTerm.value.toLowerCase());

        if (statusFilter.value === "all") return matchText;
        return matchText && v.status === statusFilter.value;
    });
});

const stats = computed(() => {
    const total = vehicles.value.length;
    const operational = vehicles.value.filter(v => v.status === "active").length;
    const maintenance = vehicles.value.filter(v => v.status === "maintenance").length;
    const inactive = vehicles.value.filter(v => v.status === "inactive").length;
    const totalMileage = vehicles.value.reduce((acc, current) => acc + current.mileage, 0);

    return { total, operational, maintenance, inactive, totalMileage };
});

const getPilotDisplay = (id: string) => {
    if (!id) return "Sin asignar";
    const found = pilots.value.find(p => p.id === id);
    return found ? `${found.name} (${found.license})` : "Sin asignar";
};

const getPilotNameOnly = (id: string) => {
    if (!id) return "Sin asignar";
    const found = pilots.value.find(p => p.id === id);
    return found ? found.name : "Sin asignar";
};

const getPilotInitials = (id: string) => {
    if (!id) return "SA";
    const found = pilots.value.find(p => p.id === id);
    if (!found) return "SA";
    return found.name.split(" ").map(w => w[0]).slice(0, 2).join("").toUpperCase();
};

const openLogModal = () => {
    logVehicleId.value = "";
    logPilotId.value = "";
    logStatus.value = "active";
    logServiceText.value = "";
    logIssueText.value = "";
    logObservations.value = "";
    isLogModalOpen.value = true;
};

const showVehicleHistory = (vehicle: Vehicle) => {
    let htmlContent = `<div class="text-left space-y-4 max-h-64 overflow-y-auto custom-scrollbar">`;
    
    if (!vehicle.history || vehicle.history.length === 0) {
        htmlContent += `<p class="text-white/50 text-xs italic">No hay registros en la bitácora aún.</p>`;
    } else {
        vehicle.history.forEach(log => {
            htmlContent += `
                <div class="p-3 bg-white/5 border border-white/10 rounded-xl">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[10px] font-black uppercase text-primary">${log.type}</span>
                        <span class="text-[9px] font-mono text-white/40">${log.date}</span>
                    </div>
                    <p class="text-xs text-white/80">${log.description}</p>
                </div>
            `;
        });
    }
    htmlContent += `</div>`;

    Swal.fire({
        title: `<span class="text-white font-black uppercase text-sm tracking-widest">Historial de Unidad [${vehicle.plate}]</span>`,
        html: htmlContent,
        background: '#0f172a',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'rounded-3xl border border-white/10 shadow-2xl',
            closeButton: 'text-white/50 hover:text-white'
        }
    });
};

const submitVehicleLog = async () => {
    try {
        const token = localStorage.getItem('token');
        const payload = {
            vehiculo_id: logVehicleId.value,
            piloto_id: logPilotId.value,
            estatus_vehiculo: logStatus.value,
            envio_servicio: logServiceText.value,
            reportar_averia: logIssueText.value,
            observaciones: logObservations.value
        };

        const response = await fetch(`${BASE_URL}/vehicle-logs`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.success) {
            triggerToast(result.message || 'Bitácora registrada y vehículo actualizado');
            isLogModalOpen.value = false;
            fetchVehicles(); // Refresh vehicles to see new status and pilot
        } else {
            alert('Error al registrar la bitácora: ' + (result.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error("Error submitting log:", error);
        alert("Ocurrió un error al registrar la bitácora.");
    }
};

const downloadReportSim = () => {
    const content = `CONSTRUCTPRO - REPORTE DE BITÁCORA Y CONTROL DE VEHÍCULOS\n` +
        `Generado: ${new Date().toLocaleString()}\n` +
        `Total Vehículos: ${stats.value.total} | Activos: ${stats.value.operational} | En Mantenimiento: ${stats.value.maintenance} | Inactivos/Averiados: ${stats.value.inactive}\n` +
        `Kilometraje Acumulado de la Flota: ${stats.value.totalMileage} KM\n` +
        `========================================================================\n\n` +
        vehicles.value.map((v, i) => {
            let historyStr = v.history.map(h => `   [${h.date}] - ${h.type} : ${h.description}`).join("\n");
            return `[Unidad n°${i + 1}] ID: ${v.id}\n` +
                `Placa: ${v.plate}\n` +
                `Vehículo: ${v.brand} ${v.model}\n` +
                `Kilometraje: ${v.mileage} KM\n` +
                `Estado Operativo: ${v.status.toUpperCase()} | Prioridad: ${v.priority}\n` +
                `Piloto Homologado: ${getPilotDisplay(v.pilotId)}\n` +
                `Observaciones Vigentes: "${v.notes || 'Ninguna'}"\n` +
                `Historial de Bitácora:\n${historyStr || '   Sin eventos de bitácora registrados.'}\n` +
                `------------------------------------------------------------------------`;
        }).join("\n\n");

    const element = document.createElement("a");
    const file = new Blob([content], { type: "text/plain" });
    element.href = URL.createObjectURL(file);
    element.download = `Reporte-Bitacora-Flota-${new Date().toISOString().split("T")[0]}.txt`;
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
    triggerToast("Descarga de reporte logístico exportado perfectamente.");
};

// Handle file input changes without re-rendering everything
let frontPhotoFile: File | null = null;
let rearPhotoFile: File | null = null;

const onFrontPhotoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) {
        frontPhotoFile = target.files[0];
        frontPhotoName.value = target.files[0].name;
        if (frontPhotoPreview.value) URL.revokeObjectURL(frontPhotoPreview.value);
        frontPhotoPreview.value = URL.createObjectURL(target.files[0]);
    }
};
const onRearPhotoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) {
        rearPhotoFile = target.files[0];
        rearPhotoName.value = target.files[0].name;
        if (rearPhotoPreview.value) URL.revokeObjectURL(rearPhotoPreview.value);
        rearPhotoPreview.value = URL.createObjectURL(target.files[0]);
    }
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
  transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.modal-scale-enter-from,
.modal-scale-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
</style>
