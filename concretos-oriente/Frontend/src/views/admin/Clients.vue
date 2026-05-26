<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
    <!-- Header and top actionable items -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Gestión de Clientes</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">
          Administre la base de datos de sus clientes y el estado de sus carteras de obra
        </p>
      </div>

      <div>
        <button 
          @click="openDrawer('create')"
          class="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
        >
          <UserPlusIcon class="w-4 h-4" /> Nuevo Cliente
        </button>
      </div>
    </div>

    <!-- KPI Cards - Bento Style -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- KPI 1 -->
      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-primary flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total de Clientes</span>
          <div>
            <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ kpis.total }}</h3>
            <p class="text-[10px] font-bold text-primary tracking-wider uppercase mt-1 flex items-center gap-1">
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" /> +12% vs mes anterior
            </p>
          </div>
        </div>
        <div class="p-3 bg-primary/10 border border-primary/20 rounded-2xl text-primary">
          <UsersIcon class="w-5 h-5" />
        </div>
      </div>

      <!-- KPI 2 -->
      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-rose-500/50 flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Proyectos Activos</span>
          <div>
            <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ kpis.activeProjects }}</h3>
            <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">
              Distribuidos en carteras vigentes
            </p>
          </div>
        </div>
        <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-400">
          <BriefcaseIcon class="w-5 h-5" />
        </div>
      </div>

      <!-- KPI 3 -->
      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 flex items-start justify-between hover:bg-white/[0.03] transition-all h-36">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Valor de Cartera</span>
          <div>
            <h3 class="text-4xl font-black italic text-white tracking-tighter">
              Q{{ (kpis.totalPortfolio / 1000000).toFixed(1) }}M
            </h3>
            <p class="text-[10px] font-bold text-emerald-400 tracking-wider uppercase mt-1">
              Proyecciones estimadas de cierre
            </p>
          </div>
        </div>
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400">
          <CurrencyDollarIcon class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Filters Quick search and Categories -->
    <div class="flex flex-col lg:flex-row justify-between items-center gap-6 border-b border-white/5 pb-6">
      <!-- State select filter items -->
      <div class="flex gap-2 bg-white/5 p-1.5 rounded-2xl border border-white/5 overflow-x-auto w-full lg:w-auto custom-scrollbar">
        <button 
          type="button"
          @click="statusFilter = 'all'; currentPage = 1"
          :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap',
            statusFilter === 'all' ? 'bg-primary text-white shadow-lg shadow-primary/25' : 'text-white/40 hover:text-white'
          ]"
        >
          Todos
        </button>

        <button 
          type="button"
          @click="statusFilter = 'active'; currentPage = 1"
          :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap',
            statusFilter === 'active' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white'
          ]"
        >
          Activos
        </button>

        <button 
          type="button"
          @click="statusFilter = 'prospect'; currentPage = 1"
          :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap',
            statusFilter === 'prospect' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white'
          ]"
        >
          Prospectos
        </button>

        <button 
          type="button"
          @click="statusFilter = 'inactive'; currentPage = 1"
          :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap',
            statusFilter === 'inactive' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white'
          ]"
        >
          Inactivos
        </button>
      </div>

      <!-- Search tool block -->
      <div class="relative w-full lg:w-96">
        <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
        <input 
          type="text"
          placeholder="Buscar por cliente, empresa, RUC, contacto..."
          v-model="searchTerm"
          @input="currentPage = 1"
          class="glass-input pl-11 pr-4 py-3 rounded-xl text-xs uppercase tracking-wider font-extrabold w-full text-white placeholder:text-white/20 outline-none focus:ring-1 focus:ring-primary/40 transition-all"
        />
      </div>
    </div>

    <!-- Primary Table layout or listing -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/5 shadow-2xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
              <th class="px-8 py-5">Empresa / Cliente</th>
              <th class="px-8 py-5">Contacto Principal</th>
              <th class="px-8 py-5 text-center">Proyectos Activos</th>
              <th class="px-8 py-5 text-center">Estado</th>
              <th class="px-8 py-5 text-right">Monto Cartera</th>
              <th class="px-8 py-5 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading">
              <td colspan="6" class="px-8 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">Cargando...</td>
            </tr>
            <tr v-else-if="currentItems.length === 0">
              <td colspan="6" class="px-8 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">
                No se han encontrado registros en el directorio de clientes.
              </td>
            </tr>
            <tr v-for="c in currentItems" :key="c.id" class="hover:bg-white/[0.02] transition-colors">
              
              <!-- Company and visual brand ID initials -->
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div :class="`w-11 h-11 rounded-2xl bg-gradient-to-br ${getInitialsColor(c.id)} flex items-center justify-center text-white text-xs font-black shadow-lg shadow-indigo-500/10 shrink-0`">
                    {{ getInitials(c.company_name) }}
                  </div>
                  <div class="min-w-0">
                    <h4 
                      @click="openDrawer('detail', c)"
                      class="font-extrabold text-white text-sm hover:text-primary transition-colors cursor-pointer uppercase italic truncate"
                    >
                      {{ c.company_name }}
                    </h4>
                    <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">
                      RUC: {{ c.ruc || 'N/A' }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Contact Person metadata -->
              <td class="px-8 py-5">
                <p class="font-extrabold text-xs text-white/85">{{ c.contact_name }}</p>
                <p class="text-[10px] font-medium text-white/40 lowercase mt-1">{{ c.email || 'N/A' }}</p>
              </td>

              <!-- Active projects count -->
              <td class="px-8 py-5 text-center">
                <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2.5 rounded-full bg-white/5 border border-white/5 text-xs font-black italic text-primary">
                  {{ (c.projects_count || 0).toString().padStart(2, "0") }}
                </span>
              </td>

              <!-- Status Badge -->
              <td class="px-8 py-5 text-center">
                <span :class="`inline-flex px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border ${
                  c.status === 'active' 
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                    : c.status === 'prospect'
                    ? 'bg-primary/15 text-primary border-primary/20'
                    : 'bg-white/5 text-white/30 border-white/5'
                }`">
                  {{ c.status === "active" ? "Activo" : c.status === "prospect" ? "Prospecto" : "Inactivo" }}
                </span>
              </td>

              <!-- Estimated Portfolio amount -->
              <td class="px-8 py-5 text-right font-black italic text-sm text-white/70">
                Q{{ Number(c.portfolio_value || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>

              <!-- Action toggles -->
              <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button 
                    @click="openDrawer('detail', c)"
                    title="Ver detalles"
                    class="p-2 bg-white/5 hover:bg-white/10 text-white/65 hover:text-white rounded-xl border border-white/5 transition-all"
                  >
                    <EyeIcon class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="openDrawer('edit', c)"
                    title="Editar"
                    class="p-2 bg-white/5 hover:bg-white/10 text-white/65 hover:text-primary rounded-xl border border-white/5 transition-all"
                  >
                    <PencilIcon class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="handleDeleteClient(c.id, c.company_name)"
                    title="Eliminar"
                    class="p-2 bg-white/5 hover:bg-white/10 text-white/40 hover:text-rose-400 rounded-xl border border-white/5 transition-all"
                  >
                    <TrashIcon class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>

            </tr>
          </tbody>
        </table>

        <!-- Table pagination controller -->
        <div class="px-8 py-5 flex flex-col md:flex-row items-center justify-between border-t border-white/5 bg-white/[0.01]">
          <p class="text-[10px] font-black text-white/30 tracking-widest uppercase mb-4 md:mb-0">
            Mostrando {{ filteredClients.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} a {{ Math.min(currentPage * itemsPerPage, filteredClients.length) }} de {{ filteredClients.length }} clientes
          </p>
          <div class="flex items-center gap-2">
            <button 
              @click="currentPage = Math.max(currentPage - 1, 1)"
              :disabled="currentPage === 1"
              class="p-2 border border-white/5 bg-white/5 rounded-xl hover:bg-white/10 text-white disabled:opacity-30 transition-all cursor-pointer"
            >
              <ChevronLeftIcon class="w-4 h-4" />
            </button>
            
            <button
              v-for="idx in totalPages"
              :key="idx"
              @click="currentPage = idx"
              :class="`w-8 h-8 rounded-xl text-xs font-black transition-all ${
                currentPage === idx ? 'bg-primary text-white shadow-lg' : 'hover:bg-white/5 text-white/40'
              }`"
            >
              {{ idx }}
            </button>

            <button 
              @click="currentPage = Math.min(currentPage + 1, totalPages)"
              :disabled="currentPage === totalPages"
              class="p-2 border border-white/5 bg-white/5 rounded-xl hover:bg-white/10 text-white disabled:opacity-30 transition-all cursor-pointer"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Slide-In Side Drawer for creation, details viewing and editions -->
    <transition name="slide-in">
      <div v-if="isDrawerOpen" class="fixed inset-0 z-50 flex justify-end">
        <!-- Backdrop opacity layer -->
        <div 
          @click="closeDrawer"
          class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"
        ></div>

        <!-- Inner drawer panel content -->
        <div class="relative w-full max-w-lg bg-slate-950 border-l border-white/10 p-10 flex flex-col justify-between shadow-2xl h-full overflow-y-auto transform transition-transform duration-300">
          <!-- Drawer Top Header info -->
          <div class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/5 pb-5">
              <div>
                <h3 class="text-2xl font-black italic text-white uppercase tracking-tighter">
                  {{ drawerMode === 'create' ? 'Nuevo Cliente' : drawerMode === 'edit' ? 'Editar Cliente' : 'Detalles de Cliente' }}
                </h3>
                <p class="text-white/40 font-bold uppercase tracking-widest text-[9px] mt-1">
                  {{ drawerMode === 'detail' ? 'Ficha técnica y financiera corporativa' : 'Rellene el formulario regulatorio' }}
                </p>
              </div>
              <button 
                @click="closeDrawer"
                class="p-2.5 bg-white/5 hover:bg-white/10 rounded-xl border border-white/5 text-white/50 hover:text-white transition-all"
              >
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <!-- Main Client Form / Detail Presentation -->
            <div v-if="drawerMode === 'detail'" class="space-y-8 py-2">
              
              <!-- Detail Avatar and main title -->
              <div class="flex items-center gap-4 bg-white/5 p-6 rounded-3xl border border-white/5">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center text-white text-lg font-black shrink-0">
                  {{ getInitials(formClient.company_name) }}
                </div>
                <div>
                  <h4 class="text-lg font-black text-white italic uppercase tracking-tight">{{ formClient.company_name }}</h4>
                  <p class="text-xs font-bold text-primary tracking-wider uppercase mt-0.5">RUC: {{ formClient.ruc || 'N/A' }}</p>
                </div>
              </div>

              <!-- Detailed field items list -->
              <div class="grid grid-cols-2 gap-6">
                <div class="glass-card p-4 rounded-2xl border border-white/5 space-y-1">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">Proyectos Asignados</span>
                  <span class="text-xl font-black italic text-white">{{ formClient.projects_count || 0 }} Proyectos</span>
                </div>
                
                <div class="glass-card p-4 rounded-2xl border border-white/5 space-y-1">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">Valor de Cartera</span>
                  <span class="text-xl font-black italic text-primary">Q{{ Number(formClient.portfolio_value || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                </div>
              </div>

              <div class="space-y-6 pt-3">
                
                <!-- State status element -->
                <div class="flex justify-between items-center border-b border-white/5 pb-4">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Estado de Cartera</span>
                  <span :class="`px-2.5 py-1 rounded text-[9px] font-black uppercase tracking-widest border ${
                    formClient.status === 'active' 
                      ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                      : formClient.status === 'prospect'
                      ? 'bg-primary/10 text-primary border-primary/20'
                      : 'bg-white/5 text-white/30 border-white/5'
                  }`">
                    {{ formClient.status === 'active' ? 'Activo' : formClient.status === 'prospect' ? 'Prospecto' : 'Inactivo' }}
                  </span>
                </div>

                <!-- Contact metadata -->
                <div class="flex items-start gap-4 pb-2">
                  <UsersIcon class="w-5 h-5 text-primary shrink-0 mt-0.5" />
                  <div class="space-y-1">
                    <p class="text-[9px] font-black text-white/30 uppercase tracking-widest">Representante o Contacto</p>
                    <p class="text-sm font-bold text-white/90">{{ formClient.contact_name }}</p>
                  </div>
                </div>

                <!-- Contact Email -->
                <div class="flex items-start gap-4 pb-2">
                  <EnvelopeIcon class="w-5 h-5 text-primary shrink-0 mt-0.5" />
                  <div class="space-y-1">
                    <p class="text-[9px] font-black text-white/30 uppercase tracking-widest font-sans">Email Corporativo</p>
                    <p class="text-sm font-bold text-white/90 lowercase">{{ formClient.email || "No registrado" }}</p>
                  </div>
                </div>

                <!-- Contact Phone -->
                <div class="flex items-start gap-4 pb-2">
                  <PhoneIcon class="w-5 h-5 text-primary shrink-0 mt-0.5" />
                  <div class="space-y-1">
                    <p class="text-[9px] font-black text-white/30 uppercase tracking-widest">Teléfono / Celular</p>
                    <p class="text-sm font-bold text-white/90">{{ formClient.phone || "No registrado" }}</p>
                  </div>
                </div>

                <!-- Fiscal Address mapping -->
                <div class="flex items-start gap-4">
                  <MapPinIcon class="w-5 h-5 text-primary shrink-0 mt-0.5" />
                  <div class="space-y-1">
                    <p class="text-[9px] font-black text-white/30 uppercase tracking-widest">Domicilio Fiscal</p>
                    <p class="text-sm font-bold text-white/95 leading-relaxed">{{ formClient.address || "No registrado" }}</p>
                  </div>
                </div>

              </div>

            </div>
            <form v-else @submit.prevent="handleSaveClient" id="drawer-form" class="space-y-5 pt-3">
              
              <!-- Company name input -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre De La Empresa / Razón Social <span class="text-rose-400">*</span></label>
                <input 
                  type="text"
                  required
                  v-model="formClient.company_name"
                  placeholder="E.g. Skyline Constructora S.A."
                  class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white outline-none focus:border-primary"
                />
              </div>

              <!-- Fiscal ID and Initial state status grids -->
              <div class="grid grid-cols-2 gap-5">
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">RUC / ID Comercial o de Registro</label>
                  <input 
                    type="text"
                    v-model="formClient.ruc"
                    placeholder="20XXXXXXXXX"
                    class="w-full glass-input rounded-2xl p-3.5 text-sm font-bold placeholder:text-white/20 uppercase text-white outline-none focus:border-primary"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Estado Operativo Inicial</label>
                  <select 
                    v-model="formClient.status"
                    class="w-full bg-black/20 border border-white/10 rounded-2xl p-3.5 text-sm font-bold uppercase text-white outline-none focus:border-primary appearance-none"
                  >
                    <option value="active" class="bg-slate-950 text-white">Activo</option>
                    <option value="prospect" class="bg-slate-950 text-white">Prospecto</option>
                    <option value="inactive" class="bg-slate-950 text-white">Inactivo</option>
                  </select>
                </div>
              </div>

              <!-- Primary contact Name input -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Contacto Principal (Nombre del cargo) <span class="text-rose-400">*</span></label>
                <input 
                  type="text"
                  required
                  v-model="formClient.contact_name"
                  placeholder="E.g. Ing. Carlos Mendoza"
                  class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white outline-none focus:border-primary"
                />
              </div>

              <!-- Email & Phone connection settings -->
              <div class="grid grid-cols-2 gap-5">
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Correo Electrónico</label>
                  <input 
                    type="email"
                    v-model="formClient.email"
                    placeholder="carlos@empresa.com"
                    class="w-full glass-input rounded-2xl p-3.5 text-sm font-bold placeholder:text-white/20 text-white outline-none focus:border-primary"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Teléfono o WhatsApp</label>
                  <input 
                    type="text"
                    v-model="formClient.phone"
                    placeholder="E.g. +51 987 654 321"
                    class="w-full glass-input rounded-2xl p-3.5 text-sm font-bold placeholder:text-white/20 text-white outline-none focus:border-primary"
                  />
                </div>
              </div>

              <!-- Work stats and portfolio finances (optional for creation/edit) -->
              <div class="grid grid-cols-2 gap-5">
              </div>

              <!-- Address text field area -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest ml-1">Domicilio Fiscal Corporativo</label>
                <textarea 
                  v-model="formClient.address"
                  placeholder="E.g. Av. Javier Prado Este 1502, San Borja, Lima"
                  rows="3"
                  class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white resize-none outline-none focus:border-primary"
                />
              </div>

            </form>
          </div>

          <!-- Action operations controls -->
          <div class="flex gap-4 border-t border-white/5 pt-6 mt-6">
            <template v-if="drawerMode !== 'detail'">
              <button 
                type="submit"
                form="drawer-form"
                :disabled="isSubmitting"
                class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all cursor-pointer disabled:opacity-50"
              >
                {{ drawerMode === 'create' ? 'Guardar Cliente' : 'Actualizar Cliente' }}
              </button>
              <button 
                type="button"
                @click="closeDrawer"
                class="px-6 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 cursor-pointer"
              >
                Cancelar
              </button>
            </template>
            <template v-else>
              <button 
                @click="drawerMode = 'edit'"
                class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all cursor-pointer"
              >
                Editar Ficha
              </button>
              <button 
                @click="closeDrawer"
                class="px-6 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 cursor-pointer"
              >
                Cerrar
              </button>
            </template>
          </div>

        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  UsersIcon, UserPlusIcon, BriefcaseIcon, MagnifyingGlassIcon, 
  ArrowTrendingUpIcon, TrashIcon, PencilIcon, 
  EyeIcon, EnvelopeIcon, PhoneIcon, MapPinIcon, XMarkIcon, 
  ChevronLeftIcon, ChevronRightIcon, CurrencyDollarIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// States
const clients = ref([]);
const loading = ref(false);
const isSubmitting = ref(false);

const searchTerm = ref('');
const statusFilter = ref('all');

const isDrawerOpen = ref(false);
const drawerMode = ref('create');

const formClient = ref({
  id: null,
  company_name: '',
  ruc: '',
  status: 'active',
  contact_name: '',
  email: '',
  phone: '',
  address: '',
  portfolio_value: 0
});

const currentPage = ref(1);
const itemsPerPage = 10;

const fetchClients = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/clients`);
    const data = await res.json();
    if (data.status === 'success') clients.value = data.data;
  } catch(e) {}
  loading.value = false;
};

onMounted(() => {
  fetchClients();
});

// Computed
const filteredClients = computed(() => {
  return clients.value.filter(c => {
    const s = searchTerm.value.toLowerCase();
    const matchSearch = 
      (c.company_name || '').toLowerCase().includes(s) ||
      (c.contact_name || '').toLowerCase().includes(s) ||
      (c.ruc && c.ruc.includes(s)) ||
      (c.email && c.email.toLowerCase().includes(s));
    
    const matchStatus = statusFilter.value === 'all' || c.status === statusFilter.value;
    
    return matchSearch && matchStatus;
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredClients.value.length / itemsPerPage)));

const currentItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredClients.value.slice(start, start + itemsPerPage);
});

const kpis = computed(() => {
  const total = clients.value.length;
  const activeProjects = clients.value.reduce((sum, c) => sum + (Number(c.projects_count) || 0), 0);
  const totalPortfolio = clients.value.reduce((sum, c) => sum + (Number(c.portfolio_value) || 0), 0);
  return { total, activeProjects, totalPortfolio };
});

const getInitials = (name) => {
  if (!name) return '';
  return name.split(" ").map(w => w[0]).slice(0,2).join("").toUpperCase();
};

const getInitialsColor = (id) => {
  const colors = [
    "from-indigo-500 to-cyan-500",
    "from-rose-500 to-amber-500",
    "from-emerald-500 to-teal-500",
    "from-violet-500 to-fuchsia-500",
    "from-blue-600 to-indigo-600"
  ];
  const index = parseInt(String(id).replace(/\D/g, "")) % colors.length || 0;
  return colors[index];
};

const openDrawer = (mode, client = null) => {
  drawerMode.value = mode;
  if (client) {
    formClient.value = { ...client };
  } else {
    formClient.value = {
      id: null, company_name: '', ruc: '', status: 'active',
      contact_name: '', email: '', phone: '', address: '', portfolio_value: 0
    };
  }
  isDrawerOpen.value = true;
};

const closeDrawer = () => {
  isDrawerOpen.value = false;
};

const handleSaveClient = async () => {
  if (!formClient.value.company_name.trim() || !formClient.value.contact_name.trim()) {
    Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: 'Por favor complete los campos obligatorios'});
    return;
  }

  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formClient.value).forEach(k => {
    if (formClient.value[k] !== null && formClient.value[k] !== undefined) {
      fd.append(k, formClient.value[k]);
    }
  });

  try {
    const url = drawerMode.value === 'edit' 
      ? `${BASE_URL}/clients/${formClient.value.id}` 
      : `${BASE_URL}/clients`;
    
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchClients();
      closeDrawer();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Guardado exitosamente'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

const handleDeleteClient = async (id, name) => {
  const { isConfirmed } = await Swal.fire({
    background: '#0f172a', color: '#fff', title: `¿Eliminar a ${name}?`, icon: 'warning', showCancelButton: true
  });
  if(isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/clients/${id}`, { method: 'DELETE' });
      const json = await res.json();
      if(json.status === 'success') {
        await fetchClients();
        Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Eliminado'});
      } else {
        Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
      }
    } catch(e) {}
  }
};
</script>

<style scoped>
.slide-in-enter-active, .slide-in-leave-active {
  transition: opacity 0.3s;
}
.slide-in-enter-active .transform, .slide-in-leave-active .transform {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-in-enter-from, .slide-in-leave-to {
  opacity: 0;
}
.slide-in-enter-from .transform, .slide-in-leave-to .transform {
  transform: translateX(100%);
}
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
