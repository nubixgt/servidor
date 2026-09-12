<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-black uppercase tracking-widest">
          <BuildingOffice2Icon class="w-4 h-4" />
          Subcontratos y Proveedores de Obra
        </div>
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Subcontratistas</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión de empresas subcontratistas, proyectos asignados, persona a cargo y estado de pagos</p>
      </div>
      <button @click="openContractorModal" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
        <PlusIcon class="w-5 h-5" />
        Añadir Subcontratista
      </button>
    </div>

    <!-- Main List Card -->
    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="p-8 md:p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="relative flex-1 max-w-lg">
          <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar por empresa, representante o persona a cargo..."
            class="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
          />
        </div>
        <div class="flex items-center gap-4 text-xs font-bold text-white/40 uppercase tracking-widest">
          <span>Total: <strong class="text-white">{{ filteredContractors.length }}</strong> subcontratistas</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-0 divide-x divide-y divide-white/5">
        <div v-if="loading" class="p-16 text-center text-white/40 col-span-full">
          <div class="w-10 h-10 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          Cargando subcontratistas...
        </div>
        <div v-else-if="filteredContractors.length === 0" class="p-16 text-center text-white/40 col-span-full space-y-3">
          <BuildingOffice2Icon class="w-12 h-12 mx-auto text-white/20" />
          <p class="font-bold text-white/50 uppercase tracking-wider">No se encontraron subcontratistas registrados</p>
          <button @click="openContractorModal" class="text-xs font-bold text-primary hover:underline uppercase tracking-widest">+ Registrar nuevo subcontratista</button>
        </div>

        <div
          v-for="c in filteredContractors"
          :key="c.id"
          @click="selectContractor(c)"
          class="p-8 md:p-10 cursor-pointer transition-all hover:bg-white/[0.03] group relative flex flex-col justify-between space-y-6"
        >
          <!-- Actions Top Right -->
          <div class="absolute top-6 right-6 flex items-center gap-2 z-10">
            <button @click.stop="openHistoryModal(c)" title="Historial Financiero" class="px-2.5 py-1.5 bg-primary/10 hover:bg-primary/20 rounded-xl text-primary border border-primary/20 transition-all flex items-center gap-1 text-[10px] font-bold">
              <ChartBarIcon class="w-3.5 h-3.5"/>
              <span class="hidden sm:inline">Historial</span>
            </button>
            <button @click.stop="openEditContractor(c)" title="Editar" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-white transition-all">
              <PencilIcon class="w-4 h-4"/>
            </button>
            <button @click.stop="deleteContractor(c.id)" title="Eliminar" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-rose-400 transition-all">
              <TrashIcon class="w-4 h-4"/>
            </button>
          </div>

          <div>
            <!-- Icon & Projects Count Badge -->
            <div class="flex items-center gap-4 mb-5">
              <div class="w-14 h-14 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary group-hover:scale-105 transition-transform shadow-xl">
                <BuildingOffice2Icon class="w-7 h-7" />
              </div>
              <div>
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border"
                      :class="c.proyectos_count > 0 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-white/5 text-white/40 border-white/10'">
                  {{ c.proyectos_count || 0 }} {{ c.proyectos_count === 1 ? 'Proyecto' : 'Proyectos' }}
                </span>
              </div>
            </div>

            <!-- Empresa & Representante -->
            <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-1 line-clamp-1 group-hover:text-primary transition-colors" :title="c.empresa || c.nombre">
              {{ c.empresa || c.nombre }}
            </h4>
            <div class="flex items-center gap-2 text-white/50 text-xs font-semibold mb-4">
              <UserIcon class="w-3.5 h-3.5 text-white/30 shrink-0" />
              <span class="truncate">{{ c.representante || 'Sin representante asignado' }}</span>
            </div>

            <!-- Persona a Cargo -->
            <div class="p-3 rounded-xl bg-white/[0.03] border border-white/5 space-y-1 mb-4">
              <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.15em]">Persona a Cargo</p>
              <div class="flex items-center gap-2 text-xs font-bold" :class="c.encargado_asignado ? 'text-cyan-400' : 'text-white/30'">
                <ShieldCheckIcon class="w-4 h-4 shrink-0" />
                <span class="truncate">{{ c.encargado_asignado || 'Sin responsable asignado' }}</span>
              </div>
            </div>

            <!-- Contact -->
            <div class="flex items-center gap-3 text-white/60 text-xs font-medium">
              <PhoneIcon class="w-3.5 h-3.5 text-primary shrink-0" />
              <span>{{ c.telefono || 'Sin teléfono' }}</span>
            </div>
          </div>

          <!-- Financial Snapshot Footer -->
          <div class="pt-4 border-t border-white/5 space-y-3">
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-black/20 p-2.5 rounded-xl border border-white/5">
                <p class="text-[9px] font-black text-white/30 uppercase tracking-widest">Contratado</p>
                <p class="font-black text-white truncate">Q {{ formatMoney(c.total_contratado) }}</p>
              </div>
              <div class="bg-black/20 p-2.5 rounded-xl border border-white/5">
                <p class="text-[9px] font-black text-white/30 uppercase tracking-widest">Pagado</p>
                <p class="font-black text-emerald-400 truncate">Q {{ formatMoney(c.total_pagado) }}</p>
              </div>
            </div>

            <!-- Mini Progress -->
            <div v-if="Number(c.total_contratado) > 0" class="space-y-1">
              <div class="flex justify-between text-[9px] font-bold text-white/40">
                <span>Avance Pagado</span>
                <span class="text-white">{{ Math.min(100, ((c.total_pagado / c.total_contratado) * 100)).toFixed(1) }}%</span>
              </div>
              <div class="w-full h-1.5 bg-black/40 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-primary to-emerald-400 rounded-full"
                     :style="{ width: Math.min(100, ((c.total_pagado / c.total_contratado) * 100)) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Detail / Summary Modal -->
    <transition name="fade">
      <div v-if="selectedContractor" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="closeDetail" class="absolute inset-0 bg-black/85 backdrop-blur-md"></div>
        <div class="relative w-full max-w-5xl max-h-[92vh] overflow-y-auto custom-scrollbar glass-card rounded-[40px] md:rounded-[56px] p-6 md:p-12 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.8)] space-y-8" data-aos="zoom-in-up" data-aos-duration="1000">
          
          <!-- Modal Header -->
          <div class="flex flex-col lg:flex-row items-start justify-between gap-6 pb-6 border-b border-white/10">
            <div class="flex items-start gap-6">
              <div class="w-20 h-20 md:w-24 md:h-24 rounded-3xl bg-primary/20 flex items-center justify-center text-primary border border-primary/30 shrink-0 shadow-2xl">
                <BuildingOffice2Icon class="w-10 h-10 md:w-12 md:h-12" />
              </div>
              <div class="space-y-2">
                <span class="text-[10px] font-black text-primary uppercase tracking-[0.2em] bg-primary/10 px-3 py-1 rounded-full border border-primary/20">Subcontratista</span>
                <h2 class="text-3xl md:text-4xl font-black text-white italic uppercase tracking-tighter">{{ selectedContractor.empresa || selectedContractor.nombre }}</h2>
                <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-sm text-white/60">
                  <span v-if="selectedContractor.representante" class="flex items-center gap-2">
                    <UserIcon class="w-4 h-4 text-white/40" />
                    <strong>Rep:</strong> {{ selectedContractor.representante }}
                  </span>
                  <span v-if="selectedContractor.encargado_asignado" class="flex items-center gap-2 text-cyan-400 font-bold">
                    <ShieldCheckIcon class="w-4 h-4" />
                    <strong>A cargo:</strong> {{ selectedContractor.encargado_asignado }}
                  </span>
                  <span v-if="selectedContractor.telefono" class="flex items-center gap-2">
                    <PhoneIcon class="w-4 h-4 text-primary" />
                    {{ selectedContractor.telefono }}
                  </span>
                </div>
              </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-end">
              <button @click="openHistoryModal(selectedContractor)" class="px-5 py-3.5 rounded-2xl bg-primary/20 hover:bg-primary/30 text-primary border border-primary/30 font-black text-xs uppercase tracking-widest flex items-center gap-2 transition-all">
                <ChartBarIcon class="w-4 h-4" /> Historial Financiero
              </button>
              <button @click="openAssignModal" class="glass-button-primary text-white py-3.5 px-6 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center gap-2 shadow-xl hover:scale-105 transition-all">
                <PlusIcon class="w-4 h-4" /> Asignar a Proyecto
              </button>
              <button @click="closeDetail" class="w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5 text-white/40 hover:text-white shrink-0">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>
          </div>

          <!-- Overall Financial Summary Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white/5 border border-white/10 rounded-3xl p-6 space-y-1">
              <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total Contratado</p>
              <p class="text-2xl md:text-3xl font-black text-white italic">Q {{ formatMoney(totalContratadoGlobal) }}</p>
              <p class="text-[11px] text-white/30 font-medium">{{ summaryProjects.length }} proyecto(s) asignados</p>
            </div>
            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-3xl p-6 space-y-1">
              <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Total Pagado / Entregado</p>
              <p class="text-2xl md:text-3xl font-black text-emerald-400 italic">Q {{ formatMoney(totalPagadoGlobal) }}</p>
              <p class="text-[11px] text-emerald-400/60 font-medium">{{ totalAbonosCount }} abono(s) registrados</p>
            </div>
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-3xl p-6 space-y-1">
              <p class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Saldo Pendiente</p>
              <p class="text-2xl md:text-3xl font-black text-amber-400 italic">Q {{ formatMoney(Math.max(0, totalContratadoGlobal - totalPagadoGlobal)) }}</p>
              <p class="text-[11px] text-amber-400/60 font-medium">{{ globalPercentage.toFixed(1) }}% amortizado</p>
            </div>
          </div>

          <!-- Projects & Payments Section -->
          <div class="space-y-6">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-black text-white italic uppercase tracking-tight flex items-center gap-3">
                <FolderIcon class="w-5 h-5 text-primary" />
                Proyectos y Detalle de Pagos
              </h3>
            </div>

            <div v-if="loadingSummary" class="p-16 text-center text-white/40">
              <div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
              Cargando información de proyectos y pagos...
            </div>
            <div v-else-if="summaryProjects.length === 0" class="p-16 text-center text-white/40 space-y-4 border border-dashed border-white/10 rounded-3xl">
              <FolderIcon class="w-12 h-12 mx-auto text-white/20" />
              <p class="font-bold text-white/60">Este subcontratista aún no tiene proyectos asignados.</p>
              <button @click="openAssignModal" class="glass-button-primary text-white py-3 px-8 rounded-xl font-bold text-xs uppercase tracking-wider">
                + Asignar a un Proyecto Ahora
              </button>
            </div>

            <div v-else class="space-y-8">
              <div v-for="(p, pIdx) in summaryProjects" :key="p.project_id" :class="['rounded-[32px] overflow-hidden border bg-black/40 shadow-xl transition-all', getProjectTheme(pIdx).borderClass]">
                <!-- Project Bar Header -->
                <div class="bg-white/5 px-8 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10">
                  <div class="flex items-center gap-3">
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm', getProjectTheme(pIdx).iconBg, getProjectTheme(pIdx).iconText]">
                      <FolderIcon class="w-5 h-5" />
                    </div>
                    <div>
                      <h4 class="text-lg font-black text-white uppercase tracking-wider">{{ p.proyecto_nombre }}</h4>
                      <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Proyecto en ejecución</p>
                    </div>
                    <div class="flex items-center gap-2 ml-2">
                      <button v-if="p.project_contractor_id" @click="openEditAssignModal(p)" class="p-2 hover:bg-white/10 rounded-xl text-white/30 hover:text-white transition-all" title="Editar Trato / Extras">
                        <PencilIcon class="w-4 h-4" />
                      </button>
                      <button v-if="p.project_contractor_id" @click="removeAssignment(p)" class="p-2 hover:bg-white/10 rounded-xl text-white/30 hover:text-rose-400 transition-all" title="Quitar asignación de proyecto">
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Monto Contratado</p>
                    <span class="text-white font-black text-2xl italic whitespace-nowrap">Q {{ formatMoney(p.monto_contratado) }}</span>
                  </div>
                </div>

                <!-- Payments Table -->
                <div class="overflow-x-auto">
                  <table class="w-full min-w-[560px] text-left">
                    <thead>
                      <tr class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] bg-white/[0.02] border-b border-white/5">
                        <th class="px-8 py-4">Fecha</th>
                        <th class="px-8 py-4">No. Cheque / Doc</th>
                        <th class="px-8 py-4">Banco / Cuenta</th>
                        <th class="px-8 py-4">Descripción / Concepto</th>
                        <th class="px-8 py-4 text-right">Monto Pagado</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                      <tr v-if="p.pagos.length === 0">
                        <td colspan="5" class="px-8 py-8 text-center text-white/30 text-sm italic">
                          No se han registrado pagos para este proyecto aún.
                        </td>
                      </tr>
                      <tr v-for="pago in p.pagos" :key="pago.id" class="hover:bg-white/[0.03] transition-all">
                        <td class="px-8 py-4 text-sm text-white/80 font-medium">{{ formatDate(pago.fecha_egreso) }}</td>
                        <td class="px-8 py-4 text-sm text-white/60 font-mono">{{ pago.numero_cheque || '-' }}</td>
                        <td class="px-8 py-4 text-sm text-white/60">{{ pago.cuenta_origen || '-' }}</td>
                        <td class="px-8 py-4 text-sm text-white/50 max-w-xs truncate">{{ pago.descripcion || 'Pago a subcontratista' }}</td>
                        <td class="px-8 py-4 text-right text-sm font-bold text-emerald-400">Q {{ formatMoney(pago.monto) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Project Balance & Progress Footer -->
                <div class="bg-white/5 px-8 py-6 flex flex-wrap items-center justify-between gap-6 border-t border-white/5">
                  <div class="flex items-center gap-8">
                    <div>
                      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Total Pagado</p>
                      <p class="text-xl font-black text-emerald-400 italic">Q {{ formatMoney(p.total_pagado) }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Saldo Pendiente</p>
                      <p class="text-xl font-black text-amber-400 italic">Q {{ formatMoney(p.por_pagar) }}</p>
                    </div>
                  </div>
                  <div class="flex-1 min-w-[220px]">
                    <div class="flex items-center justify-between mb-1.5">
                      <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Avance Financiero</p>
                      <p class="text-xs font-black text-white">{{ Number(p.porcentaje).toFixed(1) }}%</p>
                    </div>
                    <div class="w-full h-2.5 bg-black/40 rounded-full overflow-hidden border border-white/5">
                      <div :class="['h-full rounded-full transition-all duration-500', getProjectTheme(pIdx).progressClass]" :style="{ width: Math.min(p.porcentaje, 100) + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Subcontractor Create/Edit Modal -->
    <div v-if="showContractorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="closeContractorModal"></div>
      <div class="glass-card w-full max-w-2xl rounded-[36px] p-6 md:p-10 relative z-10 border border-white/10 shadow-2xl space-y-8" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between border-b border-white/10 pb-5">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary/20 flex items-center justify-center text-primary">
              <BuildingOffice2Icon class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-2xl font-black text-white italic uppercase tracking-tight">{{ isEditing ? 'Editar Subcontratista' : 'Añadir Subcontratista' }}</h3>
              <p class="text-white/40 text-xs font-bold uppercase tracking-wider">Información general y responsable asignado</p>
            </div>
          </div>
          <button @click="closeContractorModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitContractor" class="space-y-6">
          <!-- Empresa / Razón Social -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Empresa / Razón Social *</label>
            <input
              v-model="formContractor.empresa"
              type="text"
              required
              placeholder="Ej. Constructora del Norte S.A. / Servicios Integrados"
              class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/60 transition-all"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Representante -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Representante / Encargado</label>
              <input
                v-model="formContractor.representante"
                type="text"
                placeholder="Nombre del representante legal"
                class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/60 transition-all"
              />
            </div>
            <!-- Teléfono -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Teléfono de Contacto</label>
              <input
                v-model="formContractor.telefono"
                @input="formatPhone"
                type="text"
                maxlength="9"
                placeholder="0000-0000"
                class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/60 transition-all"
              />
            </div>
          </div>

          <!-- Personas a Cargo (Manual + Múltiples) -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-2">
                <ShieldCheckIcon class="w-4 h-4" />
                Personas a Cargo (Supervisores / Encargados)
              </label>
              <button
                type="button"
                @click="addPersonaCargo"
                class="px-3 py-1.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 border border-cyan-500/20 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
              >
                <PlusIcon class="w-3.5 h-3.5" /> Añadir Persona
              </button>
            </div>

            <div class="space-y-2">
              <div v-for="(p, idx) in formContractor.personas_a_cargo" :key="idx" class="flex items-center gap-2">
                <input
                  v-model="formContractor.personas_a_cargo[idx]"
                  type="text"
                  placeholder="Nombre completo del supervisor o persona a cargo"
                  class="flex-1 bg-black/30 border border-white/10 rounded-2xl px-5 py-3.5 text-white text-sm focus:outline-none focus:border-cyan-400/60 transition-all"
                />
                <button
                  v-if="formContractor.personas_a_cargo.length > 1"
                  type="button"
                  @click="removePersonaCargo(idx)"
                  class="p-3 bg-white/5 hover:bg-rose-500/20 rounded-2xl text-white/40 hover:text-rose-400 border border-white/5 transition-all cursor-pointer"
                  title="Eliminar"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
            <p class="text-[11px] text-white/30 font-medium">Escriba manualmente las personas internas o supervisores a cargo de coordinar este subcontratista.</p>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeContractorModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              {{ isEditing ? 'Actualizar Subcontratista' : 'Guardar Subcontratista' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Project Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="closeAssignModal"></div>
      <div class="glass-card w-full max-w-2xl rounded-[36px] p-6 md:p-10 relative z-10 border border-white/10 shadow-2xl space-y-8" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="p-8 pb-0 flex items-start justify-between">
          <div>
            <h3 class="text-2xl font-black text-white italic uppercase tracking-tight">Asignar Proyecto</h3>
            <p class="text-white/40 text-xs font-bold uppercase tracking-wider">Subcontratista: {{ selectedContractor?.empresa || selectedContractor?.nombre }}</p>
          </div>
          <button @click="closeAssignModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>
        <form @submit.prevent="submitAssign" class="space-y-6 p-8">
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Proyecto *</label>
            <select v-model="formAssign.project_id" required class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 transition-all appearance-none">
              <option value="" disabled>Seleccione un proyecto...</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Monto Contratado (Q) *</label>
              <input v-model="formAssign.monto_contratado" type="number" step="0.01" min="0" required placeholder="0.00" class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 transition-all" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Fecha de Asignación *</label>
              <input v-model="formAssign.fecha_asignacion" type="date" required class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 transition-all" />
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Observaciones / Alcance</label>
            <textarea v-model="formAssign.observaciones" rows="3" placeholder="Detalle del trabajo contratado, condiciones, etc." class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 transition-all"></textarea>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeAssignModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              Guardar Asignación
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL HISTORIAL MENSUAL DE MONTOS Y PAGOS -->
    <transition name="fade">
      <div v-if="showHistoryModal && historyContractor" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="showHistoryModal = false" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto custom-scrollbar glass-card rounded-[40px] md:rounded-[56px] p-6 md:p-10 border border-white/10 shadow-2xl z-10 text-white space-y-6">
          
          <!-- Header Modal -->
          <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center text-primary border border-white/10 shrink-0">
                <ChartBarIcon class="w-7 h-7" />
              </div>
              <div>
                <h3 class="text-2xl font-black uppercase italic tracking-tighter text-white">{{ historyContractor.empresa || historyContractor.nombre }}</h3>
                <p class="text-xs font-bold text-primary tracking-widest uppercase">
                  {{ historyContractor.representante ? 'Rep: ' + historyContractor.representante + ' · ' : '' }}Historial de Pagos por Mes
                </p>
              </div>
            </div>
            <button @click="showHistoryModal = false" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="loadingHistory" class="py-16 text-center text-white/40 font-bold">
            Cargando historial de pagos...
          </div>

          <!-- History content -->
          <template v-else>
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Total Pagado Histórico</span>
                <span class="text-2xl font-black text-emerald-400 italic">Q {{ Number(historyData.total_general || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Pagos Este Mes</span>
                <span class="text-2xl font-black text-primary italic">Q {{ Number(historyData.total_este_mes || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Pagos Este Año</span>
                <span class="text-2xl font-black text-amber-400 italic">Q {{ Number(historyData.total_este_ano || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Total Movimientos</span>
                <span class="text-2xl font-black text-white italic">{{ historyData.transacciones_count || 0 }}</span>
              </div>
            </div>

            <!-- Filtros de Año y Mes -->
            <div class="bg-black/30 p-4 rounded-2xl border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-center gap-2">
                <h4 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                  <CalendarDaysIcon class="w-4 h-4" /> Desglose por Período
                </h4>
                <span v-if="historyYearFilter !== 'all' || historyMonthFilter !== 'all'" class="px-2.5 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                  Total Filtrado: Q {{ filteredTotalMonto.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} ({{ filteredTotalTransactions }} pagos)
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <!-- Filtro Año -->
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] font-black text-white/40 uppercase tracking-wider">Año:</span>
                  <select v-model="historyYearFilter" class="bg-slate-950/90 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold text-white focus:outline-none focus:border-primary">
                    <option value="all">Todos los años</option>
                    <option v-for="y in availableHistoryYears" :key="y" :value="y">{{ y }}</option>
                  </select>
                </div>

                <!-- Filtro Mes -->
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] font-black text-white/40 uppercase tracking-wider">Mes:</span>
                  <select v-model="historyMonthFilter" class="bg-slate-950/90 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold text-white focus:outline-none focus:border-primary">
                    <option v-for="m in monthsList" :key="m.value" :value="m.value">{{ m.label }}</option>
                  </select>
                </div>

                <!-- Reset button -->
                <button
                  v-if="historyYearFilter !== 'all' || historyMonthFilter !== 'all'"
                  @click="historyYearFilter = 'all'; historyMonthFilter = 'all';"
                  class="px-2.5 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/50 hover:text-white text-[10px] font-bold uppercase tracking-wider transition-all"
                >
                  Limpiar Filtros
                </button>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredMonthlyHistory.length === 0" class="py-12 text-center text-white/30 text-xs font-bold uppercase tracking-widest border-2 border-dashed border-white/5 rounded-3xl">
              No hay pagos ni egresos registrados para este subcontratista en el período seleccionado.
            </div>

            <!-- List of months (Accordion cards) -->
            <div class="space-y-4">
              <div v-for="m in filteredMonthlyHistory" :key="m.mes" class="bg-white/[0.02] border border-white/10 rounded-2xl overflow-hidden transition-all">
                <!-- Month header clickable -->
                <div @click="toggleMonthExpand(m.mes)" class="p-5 flex items-center justify-between cursor-pointer hover:bg-white/5 transition-colors">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-black text-xs">
                      {{ m.mes.split('-')[1] }}
                    </div>
                    <div>
                      <h5 class="text-sm font-black uppercase text-white tracking-wide">{{ m.mes_nombre }}</h5>
                      <span class="text-[10px] font-bold text-white/40">{{ m.count }} pago(s) registrado(s)</span>
                    </div>
                  </div>

                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">Total Mes</span>
                      <span class="text-lg font-black text-emerald-400 italic">Q {{ Number(m.total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                    </div>
                    <ChevronUpIcon v-if="expandedMonths[m.mes]" class="w-5 h-5 text-white/40" />
                    <ChevronDownIcon v-else class="w-5 h-5 text-white/40" />
                  </div>
                </div>

                <!-- Expanded Transactions Table -->
                <div v-if="expandedMonths[m.mes]" class="px-5 pb-5 pt-2 border-t border-white/5 bg-slate-950/40">
                  <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                      <thead>
                        <tr class="text-[8px] font-black text-white/30 uppercase tracking-widest border-b border-white/5">
                          <th class="py-2.5 px-3">Fecha</th>
                          <th class="py-2.5 px-3">Proyecto</th>
                          <th class="py-2.5 px-3">Cheque / Cuenta</th>
                          <th class="py-2.5 px-3">Descripción / Concepto</th>
                          <th class="py-2.5 px-3 text-right">Monto Pagado (Q)</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-white/5">
                        <tr v-for="(t, tIdx) in m.transacciones" :key="tIdx" class="hover:bg-white/5 transition-colors">
                          <td class="py-2.5 px-3 font-mono text-white/80 whitespace-nowrap">{{ formatDate(t.fecha) }}</td>
                          <td class="py-2.5 px-3 font-bold text-primary max-w-[200px] truncate" :title="t.proyecto_nombre">
                            {{ t.proyecto_nombre || 'Sin proyecto asignado' }}
                          </td>
                          <td class="py-2.5 px-3 text-white/60 font-mono">
                            {{ t.numero_cheque ? 'Cheque #' + t.numero_cheque : (t.cuenta_origen || '-') }}
                          </td>
                          <td class="py-2.5 px-3 text-white/70 max-w-[260px] truncate" :title="t.descripcion">
                            {{ t.descripcion || 'Sin descripción' }}
                          </td>
                          <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-400 whitespace-nowrap">
                            Q {{ Number(t.monto).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </template>

        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  BuildingOffice2Icon, PhoneIcon, PlusIcon, MagnifyingGlassIcon,
  XMarkIcon, PencilIcon, TrashIcon, UserIcon, ShieldCheckIcon, FolderIcon,
  ChartBarIcon, CalendarDaysIcon, ChevronUpIcon, ChevronDownIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';
const swalBase = { background: '#0f172a', color: '#fff' };

const contractors = ref([]);
const projects = ref([]);
const personnelList = ref([]);
const loading = ref(true);
const isSubmitting = ref(false);
const searchQuery = ref('');

const fetchContractors = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/contractors`);
    const data = await res.json();
    if (data.status === 'success') contractors.value = data.data;
  } catch (e) {}
  loading.value = false;
};

const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`);
    const data = await res.json();
    if (data.status === 'success') projects.value = data.data;
  } catch (e) {}
};

const fetchPersonnel = async () => {
  try {
    const res = await fetch(`${BASE_URL}/personnel`);
    const data = await res.json();
    if (data.status === 'success') personnelList.value = data.data || [];
  } catch (e) {}
};

onMounted(() => {
  fetchContractors();
  fetchProjects();
  fetchPersonnel();
});

const filteredContractors = computed(() => {
  if (!searchQuery.value) return contractors.value;
  const q = searchQuery.value.toLowerCase();
  return contractors.value.filter(c => {
    const empresa = (c.empresa || c.nombre || '').toLowerCase();
    const rep = (c.representante || '').toLowerCase();
    const encargado = (c.encargado_asignado || '').toLowerCase();
    return empresa.includes(q) || rep.includes(q) || encargado.includes(q);
  });
});

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const formatMoney = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

const PROJECT_THEMES = [
  {
    borderClass: 'border-emerald-500/50 shadow-emerald-500/20',
    iconBg: 'bg-emerald-500/20',
    iconText: 'text-emerald-400',
    progressClass: 'bg-gradient-to-r from-emerald-600 to-emerald-400'
  },
  {
    borderClass: 'border-amber-500/50 shadow-amber-500/20',
    iconBg: 'bg-amber-500/20',
    iconText: 'text-amber-400',
    progressClass: 'bg-gradient-to-r from-amber-600 to-amber-400'
  },
  {
    borderClass: 'border-sky-500/50 shadow-sky-500/20',
    iconBg: 'bg-sky-500/20',
    iconText: 'text-sky-400',
    progressClass: 'bg-gradient-to-r from-sky-600 to-sky-400'
  },
  {
    borderClass: 'border-fuchsia-500/50 shadow-fuchsia-500/20',
    iconBg: 'bg-fuchsia-500/20',
    iconText: 'text-fuchsia-400',
    progressClass: 'bg-gradient-to-r from-fuchsia-600 to-fuchsia-400'
  }
];

const getProjectTheme = (index) => PROJECT_THEMES[index % PROJECT_THEMES.length];

const formatPhone = (e) => {
  let val = e.target.value.replace(/\D/g, '');
  if (val.length > 4) {
    val = val.substring(0, 4) + '-' + val.substring(4, 8);
  }
  formContractor.value.telefono = val;
};

// CONTRACTOR CRUD
const showContractorModal = ref(false);
const isEditing = ref(false);
const editContractorId = ref(null);
const formContractor = ref({
  empresa: '',
  representante: '',
  telefono: '',
  personas_a_cargo: ['']
});

// Historial Mensual State
const showHistoryModal = ref(false);
const historyContractor = ref(null);
const historyData = ref({ total_general: 0, total_este_mes: 0, total_este_ano: 0, transacciones_count: 0, meses: [], transacciones: [] });
const loadingHistory = ref(false);
const historyYearFilter = ref('all');
const historyMonthFilter = ref('all');
const expandedMonths = ref({});

const monthsList = [
  { value: 'all', label: 'Todos los meses' },
  { value: '01', label: '01 - Enero' },
  { value: '02', label: '02 - Febrero' },
  { value: '03', label: '03 - Marzo' },
  { value: '04', label: '04 - Abril' },
  { value: '05', label: '05 - Mayo' },
  { value: '06', label: '06 - Junio' },
  { value: '07', label: '07 - Julio' },
  { value: '08', label: '08 - Agosto' },
  { value: '09', label: '09 - Septiembre' },
  { value: '10', label: '10 - Octubre' },
  { value: '11', label: '11 - Noviembre' },
  { value: '12', label: '12 - Diciembre' },
];

const addPersonaCargo = () => {
  formContractor.value.personas_a_cargo.push('');
};

const removePersonaCargo = (idx) => {
  if (formContractor.value.personas_a_cargo.length > 1) {
    formContractor.value.personas_a_cargo.splice(idx, 1);
  }
};

const openContractorModal = () => {
  isEditing.value = false;
  formContractor.value = {
    empresa: '',
    representante: '',
    telefono: '',
    personas_a_cargo: ['']
  };
  showContractorModal.value = true;
};

const openEditContractor = (c) => {
  isEditing.value = true;
  editContractorId.value = c.id;
  const rawEncargados = (c.encargado_asignado || c.encargado_nombre || '').trim();
  const list = rawEncargados ? rawEncargados.split(',').map(s => s.trim()).filter(Boolean) : [];
  formContractor.value = {
    empresa: c.empresa || c.nombre || '',
    representante: c.representante || '',
    telefono: c.telefono || '',
    personas_a_cargo: list.length > 0 ? list : ['']
  };
  showContractorModal.value = true;
};

const closeContractorModal = () => showContractorModal.value = false;

const submitContractor = async () => {
  if (!formContractor.value.empresa.trim()) {
    Swal.fire({ ...swalBase, icon: 'warning', text: 'El nombre de la empresa es obligatorio' });
    return;
  }
  isSubmitting.value = true;
  const fd = new FormData();
  fd.append('empresa', formContractor.value.empresa.trim());
  fd.append('nombre', formContractor.value.empresa.trim());
  if (formContractor.value.representante) fd.append('representante', formContractor.value.representante.trim());
  if (formContractor.value.telefono) fd.append('telefono', formContractor.value.telefono.trim());
  
  const personas = formContractor.value.personas_a_cargo.map(s => s.trim()).filter(Boolean);
  fd.append('encargado_nombre', personas.join(', '));

  try {
    const url = isEditing.value ? `${BASE_URL}/contractors/${editContractorId.value}` : `${BASE_URL}/contractors`;
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchContractors();
      closeContractorModal();
      Swal.fire({ ...swalBase, icon: 'success', title: 'Guardado correctamente' });
    } else {
      Swal.fire({ ...swalBase, icon: 'error', text: json.message });
    }
  } catch (e) {
    Swal.fire({ ...swalBase, icon: 'error', text: 'Error al conectar con el servidor' });
  }
  isSubmitting.value = false;
};

// HISTORIAL MENSUAL METHODS
const openHistoryModal = async (c) => {
  historyContractor.value = c;
  showHistoryModal.value = true;
  loadingHistory.value = true;
  historyYearFilter.value = 'all';
  historyMonthFilter.value = 'all';
  expandedMonths.value = {};

  try {
    const res = await fetch(`${BASE_URL}/contractors/${c.id}/history`);
    const json = await res.json();
    if (json.status === 'success') {
      historyData.value = json.data || { total_general: 0, total_este_mes: 0, total_este_ano: 0, transacciones_count: 0, meses: [], transacciones: [] };
      if (historyData.value.meses && historyData.value.meses.length > 0) {
        expandedMonths.value[historyData.value.meses[0].mes] = true;
      }
    }
  } catch (e) {
    console.error(e);
  }
  loadingHistory.value = false;
};

const toggleMonthExpand = (mes) => {
  expandedMonths.value[mes] = !expandedMonths.value[mes];
};

const availableHistoryYears = computed(() => {
  if (!historyData.value.meses) return [];
  const years = new Set(historyData.value.meses.map(m => m.ano));
  return Array.from(years).sort((a, b) => b - a);
});

const filteredMonthlyHistory = computed(() => {
  if (!historyData.value.meses) return [];
  return historyData.value.meses.filter(m => {
    const matchYear = historyYearFilter.value === 'all' || m.ano === parseInt(historyYearFilter.value);
    const monthPart = m.mes.split('-')[1];
    const matchMonth = historyMonthFilter.value === 'all' || monthPart === historyMonthFilter.value;
    return matchYear && matchMonth;
  });
});

const filteredTotalMonto = computed(() => {
  return filteredMonthlyHistory.value.reduce((sum, m) => sum + Number(m.total || 0), 0);
});

const filteredTotalTransactions = computed(() => {
  return filteredMonthlyHistory.value.reduce((sum, m) => sum + Number(m.count || 0), 0);
});

const deleteContractor = async (id) => {
  const { isConfirmed } = await Swal.fire({
    ...swalBase, title: '¿Eliminar subcontratista?', text: 'Esta acción no se puede deshacer.', icon: 'warning', showCancelButton: true
  });
  if (isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/contractors/${id}`, { method: 'DELETE' });
      const json = await res.json();
      if (json.status === 'success') {
        await fetchContractors();
        if (selectedContractor.value?.id === id) closeDetail();
        Swal.fire({ ...swalBase, icon: 'success', title: 'Eliminado' });
      } else {
        Swal.fire({ ...swalBase, icon: 'error', text: json.message });
      }
    } catch (e) {}
  }
};

// DETAIL / SUMMARY
const selectedContractor = ref(null);
const summaryProjects = ref([]);
const loadingSummary = ref(false);

const selectContractor = async (c) => {
  selectedContractor.value = c;
  await fetchSummary();
};

const fetchSummary = async () => {
  if (!selectedContractor.value) return;
  loadingSummary.value = true;
  try {
    const res = await fetch(`${BASE_URL}/contractors/${selectedContractor.value.id}/summary`);
    const data = await res.json();
    if (data.status === 'success') {
      summaryProjects.value = data.data.projects || [];
      if (data.data.contractor) {
        selectedContractor.value = { ...selectedContractor.value, ...data.data.contractor };
      }
    }
  } catch (e) {}
  loadingSummary.value = false;
};

const closeDetail = () => {
  selectedContractor.value = null;
  summaryProjects.value = [];
};

const totalContratadoGlobal = computed(() => {
  return summaryProjects.value.reduce((sum, p) => sum + Number(p.monto_contratado || 0), 0);
});

const totalPagadoGlobal = computed(() => {
  return summaryProjects.value.reduce((sum, p) => sum + Number(p.total_pagado || 0), 0);
});

const totalAbonosCount = computed(() => {
  return summaryProjects.value.reduce((sum, p) => sum + (p.pagos ? p.pagos.length : 0), 0);
});

const globalPercentage = computed(() => {
  if (totalContratadoGlobal.value <= 0) return 0;
  return Math.min(100, (totalPagadoGlobal.value / totalContratadoGlobal.value) * 100);
});

// ASSIGN PROJECT
const showAssignModal = ref(false);
const formAssign = ref({ project_id: '', monto_contratado: '', fecha_asignacion: new Date().toISOString().slice(0, 10), observaciones: '' });

const openAssignModal = () => {
  formAssign.value = { project_id: '', monto_contratado: '', fecha_asignacion: new Date().toISOString().slice(0, 10), observaciones: '' };
  showAssignModal.value = true;
};
const openEditAssignModal = (p) => {
  formAssign.value = { 
    project_id: p.project_id, 
    monto_contratado: p.monto_contratado, 
    fecha_asignacion: p.fecha_asignacion ? p.fecha_asignacion.split(' ')[0] : new Date().toISOString().slice(0, 10), 
    observaciones: p.observaciones || '' 
  };
  showAssignModal.value = true;
};
const closeAssignModal = () => showAssignModal.value = false;

const submitAssign = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  fd.append('project_id', formAssign.value.project_id);
  fd.append('monto_contratado', formAssign.value.monto_contratado);
  fd.append('fecha_asignacion', formAssign.value.fecha_asignacion);
  if (formAssign.value.observaciones) fd.append('observaciones', formAssign.value.observaciones);

  try {
    const res = await fetch(`${BASE_URL}/contractors/${selectedContractor.value.id}/projects`, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.status === 'success') {
      closeAssignModal();
      await fetchSummary();
      await fetchContractors();
      Swal.fire({ ...swalBase, icon: 'success', title: 'Proyecto asignado correctamente' });
    } else {
      Swal.fire({ ...swalBase, icon: 'error', text: json.message });
    }
  } catch (e) {}
  isSubmitting.value = false;
};

const removeAssignment = async (p) => {
  const { isConfirmed } = await Swal.fire({
    ...swalBase, title: '¿Quitar este proyecto del subcontratista?', icon: 'warning', showCancelButton: true
  });
  if (!isConfirmed) return;
  try {
    const res = await fetch(`${BASE_URL}/contractors/${selectedContractor.value.id}/projects/${p.project_id}`, { method: 'DELETE' });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchSummary();
      await fetchContractors();
    } else {
      Swal.fire({ ...swalBase, icon: 'error', text: json.message });
    }
  } catch (e) {}
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
