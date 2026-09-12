<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Planilla de Sueldos</h2>
        <p class="text-white/60">Genera, calcula y consulta los pagos de nómina mensuales y semanales de los colaboradores.</p>
      </div>

      <div class="flex gap-3">
        <button
          @click="openPayrollModal()"
          class="glass-button-primary text-white py-4 px-8 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
        >
          <PlusIcon class="w-5 h-5" />
          Registrar Pago de Planilla
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div
        v-for="(stat, i) in stats"
        :key="i"
        class="glass-card p-8 rounded-[32px] flex flex-col justify-between h-44 cursor-pointer group hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] transition-all duration-500 border border-white/5"
      >
        <div class="flex items-center justify-between mb-4">
          <div :class="`p-3 rounded-2xl ${stat.bgColor} ${stat.color} border border-white/10 shadow-lg`">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
          <span :class="`text-[10px] font-bold px-3 py-1.5 rounded-full ${stat.color} ${stat.bgColor} border border-white/5 tracking-wider uppercase`">
            {{ stat.badge }}
          </span>
        </div>
        <div>
          <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em]">{{ stat.label }}</p>
          <h3 class="text-3xl font-black italic text-white mt-1 group-hover:text-primary transition-colors">{{ stat.value }}</h3>
        </div>
      </div>
    </div>

    <!-- Table: Historial de Planillas -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10">
      <div class="p-8 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Search -->
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3 flex-1 min-w-[220px]">
            <MagnifyingGlassIcon class="w-4 h-4 text-white/30 flex-shrink-0" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar por colaborador o puesto..."
              class="bg-transparent flex-1 text-sm text-white placeholder-white/30 focus:outline-none"
            />
          </div>

          <!-- Filtro Periodo -->
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3">
            <CalendarIcon class="w-4 h-4 text-white/40" />
            <input
              v-model="filterPeriodo"
              type="month"
              class="bg-transparent text-sm text-white focus:outline-none"
            />
            <button v-if="filterPeriodo" @click="filterPeriodo = ''" class="text-white/40 hover:text-white text-xs">✕</button>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex bg-black/20 border border-white/10 rounded-2xl p-1">
            <button @click="viewMode = 'individual'" :class="['px-4 py-2 rounded-xl text-xs font-bold transition-all', viewMode === 'individual' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']">
              Boletas
            </button>
            <button @click="viewMode = 'consolidada'" :class="['px-4 py-2 rounded-xl text-xs font-bold transition-all', viewMode === 'consolidada' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']">
              Consolidada
            </button>
          </div>
          <span class="text-xs font-bold text-white/40 uppercase tracking-widest">
            {{ filteredPayments.length }} {{ filteredPayments.length === 1 ? 'Pago' : 'Pagos' }}
          </span>
        </div>
      </div>

      <!-- Table Content -->
      <div v-if="viewMode === 'individual'" class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 bg-white/[0.02]">
              <th class="py-5 px-8 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Colaborador</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Periodo / Fecha</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Días</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Salario Base</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Extras</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Viáticos</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Total Pagado</th>
              <th class="py-5 px-8 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading" class="text-center">
              <td colspan="8" class="py-16 text-white/40 font-semibold">Cargando registros de planilla...</td>
            </tr>
            <tr v-else-if="filteredPayments.length === 0" class="text-center">
              <td colspan="8" class="py-16 text-white/40">
                <BanknotesIcon class="w-12 h-12 text-white/10 mx-auto mb-3" />
                <p class="font-bold text-base">No hay pagos de planilla registrados</p>
                <p class="text-xs text-white/30 mt-1">Haz clic en "Registrar Pago de Planilla" para emitir un nuevo comprobante.</p>
              </td>
            </tr>
            <tr
              v-for="p in paginatedPayments"
              :key="p.id"
              class="hover:bg-white/[0.03] transition-colors group"
            >
              <!-- Colaborador -->
              <td class="py-5 px-8">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-primary/20 flex items-center justify-center font-bold text-primary text-sm flex-shrink-0">
                    {{ (p.nombres || 'E')[0] }}{{ (p.apellidos || '')[0] }}
                  </div>
                  <div>
                    <p class="font-bold text-white text-sm leading-tight">{{ p.nombres }} {{ p.apellidos }}</p>
                    <p class="text-xs text-white/40 mt-0.5">{{ p.puesto || 'Colaborador' }}</p>
                  </div>
                </div>
              </td>

              <!-- Periodo / Fecha -->
              <td class="py-5 px-6">
                <span class="px-2.5 py-1 rounded-xl bg-primary/10 border border-primary/20 text-primary font-bold text-xs">
                  {{ formatPeriodo(p.periodo) }}
                </span>
                <p class="text-[11px] text-white/40 mt-1">Pagado: {{ formatDate(p.fecha_pago) }}</p>
              </td>

              <!-- Días Trabajados -->
              <td class="py-5 px-6 font-bold text-sm text-white/80">
                {{ p.dias_trabajados }} días
              </td>

              <!-- Salario Base -->
              <td class="py-5 px-6 text-sm text-white/80">
                <span class="font-bold">Q {{ formatCurrency(p.salario_base_calculado || p.salario_base) }}</span>
                <p v-if="p.dias_trabajados < 30" class="text-[10px] text-white/30">Base Q {{ formatCurrency(p.salario_base) }}</p>
              </td>

              <!-- Horas Extras -->
              <td class="py-5 px-6 text-sm">
                <template v-if="Number(p.horas_extras) > 0">
                  <span class="font-bold text-amber-400">+Q {{ formatCurrency(p.total_horas_extras) }}</span>
                  <p class="text-[10px] text-white/40">{{ p.horas_extras }} hrs</p>
                </template>
                <span v-else class="text-white/20">—</span>
              </td>

              <!-- Viáticos -->
              <td class="py-5 px-6 text-sm">
                <span v-if="Number(p.monto_viaticos) > 0" class="font-bold text-sky-400">
                  +Q {{ formatCurrency(p.monto_viaticos) }}
                </span>
                <span v-else class="text-white/20">—</span>
              </td>

              <!-- Total Pagado -->
              <td class="py-5 px-6">
                <span class="font-black text-emerald-400 text-base">
                  Q {{ formatCurrency(p.total_pagar) }}
                </span>
              </td>

              <!-- Acciones -->
              <td class="py-5 px-8 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openReceiptModal(p)"
                    class="p-2.5 rounded-xl bg-white/5 hover:bg-primary/20 hover:text-primary text-white/60 transition-all border border-white/5"
                    title="Ver Boleta de Pago"
                  >
                    <DocumentTextIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="deletePayrollPayment(p.id)"
                    class="p-2.5 rounded-xl bg-white/5 hover:bg-rose-500/20 hover:text-rose-400 text-white/60 transition-all border border-white/5"
                    title="Eliminar registro"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Consolidada Table Content -->
      <div v-if="viewMode === 'consolidada'" class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 bg-white/[0.02]">
              <th class="py-5 px-8 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Colaborador</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Días Total</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Sueldo Base</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">H. Extras</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Viáticos</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Extras</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Total Líquido</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="consolidatedData.length === 0" class="text-center">
              <td colspan="7" class="py-16 text-white/40 font-semibold">No hay datos consolidados para este filtro.</td>
            </tr>
            <tr v-for="row in consolidatedData" :key="row.personnel_id" class="hover:bg-white/[0.03] transition-colors">
              <td class="py-5 px-8">
                <p class="font-bold text-white text-sm leading-tight">{{ row.nombres }} {{ row.apellidos }}</p>
                <p class="text-xs text-white/40 mt-0.5">{{ row.puesto || 'Colaborador' }}</p>
              </td>
              <td class="py-5 px-6 text-sm text-white/80 font-bold">{{ row.dias_trabajados }}</td>
              <td class="py-5 px-6 text-sm text-white/80">Q {{ formatCurrency(row.salario_base_calculado) }}</td>
              <td class="py-5 px-6 text-sm text-amber-400">Q {{ formatCurrency(row.total_horas_extras) }}</td>
              <td class="py-5 px-6 text-sm text-sky-400">Q {{ formatCurrency(row.monto_viaticos) }}</td>
              <td class="py-5 px-6 text-sm text-fuchsia-400">Q {{ formatCurrency(row.monto_extra) }}</td>
              <td class="py-5 px-6 text-base font-black text-emerald-400">Q {{ formatCurrency(row.total_pagar) }}</td>
            </tr>
          </tbody>
          <tfoot v-if="consolidatedData.length > 0" class="border-t-2 border-white/10 bg-white/5">
            <tr>
              <td class="py-5 px-8 font-black uppercase text-white/60 tracking-wider">Totales Generales</td>
              <td class="py-5 px-6 text-white font-bold">{{ consolidatedData.reduce((a, b) => a + b.dias_trabajados, 0) }}</td>
              <td class="py-5 px-6 text-white font-bold">Q {{ formatCurrency(consolidatedData.reduce((a, b) => a + b.salario_base_calculado, 0)) }}</td>
              <td class="py-5 px-6 text-amber-400 font-bold">Q {{ formatCurrency(consolidatedData.reduce((a, b) => a + b.total_horas_extras, 0)) }}</td>
              <td class="py-5 px-6 text-sky-400 font-bold">Q {{ formatCurrency(consolidatedData.reduce((a, b) => a + b.monto_viaticos, 0)) }}</td>
              <td class="py-5 px-6 text-fuchsia-400 font-bold">Q {{ formatCurrency(consolidatedData.reduce((a, b) => a + b.monto_extra, 0)) }}</td>
              <td class="py-5 px-6 text-emerald-400 font-black text-lg">Q {{ formatCurrency(consolidatedData.reduce((a, b) => a + b.total_pagar, 0)) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1 && viewMode === 'individual'" class="p-6 border-t border-white/5 flex items-center justify-between">
        <p class="text-xs text-white/40">
          Página <span class="text-white font-bold">{{ currentPage }}</span> de <span class="text-white font-bold">{{ totalPages }}</span>
        </p>
        <div class="flex gap-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs font-bold disabled:opacity-30 disabled:pointer-events-none transition-all"
          >
            Anterior
          </button>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs font-bold disabled:opacity-30 disabled:pointer-events-none transition-all"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: REGISTRAR PAGO DE PLANILLA -->
    <Transition name="fade">
      <div v-if="showPayrollModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="closePayrollModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-2xl bg-slate-950/95 border border-white/10 rounded-[36px] p-8 shadow-2xl overflow-y-auto max-h-[92vh] text-white z-10 space-y-6">

          <div class="flex items-center justify-between border-b border-white/5 pb-4">
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                <BanknotesIcon class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-xl font-black italic uppercase">Registrar Pago de Planilla</h3>
                <p class="text-xs text-white/50">Cálculo de sueldo, horas extras y viáticos.</p>
              </div>
            </div>
            <button @click="closePayrollModal" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitPayrollPayment" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Colaborador -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Colaborador <span class="text-rose-400">*</span></label>
                <select
                  v-model="payrollForm.personnel_id"
                  @change="onPayrollPersonnelChange"
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                >
                  <option value="" disabled>Seleccione un colaborador...</option>
                  <option v-for="emp in activePersonnelList" :key="emp.id" :value="emp.id">
                    {{ emp.nombres }} {{ emp.apellidos }} — {{ emp.puesto }} (Q {{ formatCurrency(emp.salario_base) }})
                  </option>
                </select>
              </div>

              <!-- Periodo (Mes) -->
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Periodo <span class="text-rose-400">*</span></label>
                <input
                  v-model="payrollForm.periodo"
                  type="month"
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                />
              </div>

              <!-- Fecha de Pago -->
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Fecha de Pago <span class="text-rose-400">*</span></label>
                <input
                  v-model="payrollForm.fecha_pago"
                  type="date"
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                />
              </div>

              <!-- Días Trabajados -->
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Días Trabajados</label>
                <input
                  v-model.number="payrollForm.dias_trabajados"
                  type="number"
                  min="1"
                  max="31"
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                />
              </div>

              <!-- Salario Base Info Card -->
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Salario Base Mensual</label>
                <div class="h-12 px-4 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between text-sm font-black text-white/80">
                  <span>Sueldo Base:</span>
                  <span class="text-primary font-mono">Q {{ formatCurrency(payrollCalculations.salarioBase) }}</span>
                </div>
              </div>

              <!-- Horas Extras Toggle -->
              <div class="space-y-2 md:col-span-2 bg-white/5 p-5 rounded-2xl border border-white/5">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-bold text-sm text-white">¿Aplica Horas Extras?</p>
                    <p class="text-xs text-white/40">Calculadas con base en el salario nominal.</p>
                  </div>
                  <input
                    type="checkbox"
                    v-model="payrollForm.tiene_horas_extras"
                    class="w-5 h-5 accent-primary cursor-pointer"
                  />
                </div>

                <div v-if="payrollForm.tiene_horas_extras" class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-white/5">
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Cantidad Horas Extras</label>
                    <input
                      v-model.number="payrollForm.horas_extras"
                      type="number"
                      min="0"
                      step="0.5"
                      placeholder="0"
                      class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                    />
                  </div>
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Total Horas Extras</label>
                    <div class="h-11 px-3 rounded-xl bg-white/5 flex items-center justify-between text-xs font-bold text-amber-400">
                      <span>Tarifa: Q {{ formatCurrency(payrollCalculations.tarifaHoraExtra) }}</span>
                      <span>+Q {{ formatCurrency(payrollCalculations.totalHorasExtras) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Viáticos Toggle -->
              <div class="space-y-2 md:col-span-2 bg-white/5 p-5 rounded-2xl border border-white/5">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-bold text-sm text-white">¿Aplica Viáticos / Bonificación?</p>
                    <p class="text-xs text-white/40">Montos adicionales por viajes o proyectos.</p>
                  </div>
                  <input
                    type="checkbox"
                    v-model="payrollForm.tiene_viaticos"
                    class="w-5 h-5 accent-primary cursor-pointer"
                  />
                </div>

                <div v-if="payrollForm.tiene_viaticos" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-white/5">
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Cantidad Viáticos</label>
                    <input
                      v-model.number="payrollForm.cantidad_viaticos"
                      type="number"
                      min="0"
                      class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                    />
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Inicio Rango</label>
                      <input
                        v-model="payrollForm.fecha_viaticos_inicio"
                        type="date"
                        class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                      />
                    </div>
                    <div>
                      <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Fin Rango</label>
                      <input
                        v-model="payrollForm.fecha_viaticos_fin"
                        type="date"
                        class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                      />
                    </div>
                  </div>
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Monto Viáticos (Q)</label>
                    <input
                      v-model.number="payrollForm.monto_viaticos"
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                    />
                  </div>
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Concepto Viáticos</label>
                    <input
                      v-model="payrollForm.observaciones_viaticos"
                      type="text"
                      placeholder="Ej. Viaje a Planta Salamá"
                      class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                    />
                  </div>
                </div>
              </div>

              <!-- Pago Extra Toggle -->
              <div class="space-y-2 md:col-span-2 bg-white/5 p-5 rounded-2xl border border-white/5">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="font-bold text-sm text-white">¿Aplica Pago Extra?</p>
                    <p class="text-xs text-white/40">Agregar un pago extra a la planilla.</p>
                  </div>
                  <input
                    type="checkbox"
                    v-model="payrollForm.tiene_extra"
                    class="w-5 h-5 accent-primary cursor-pointer"
                  />
                </div>

                <div v-if="payrollForm.tiene_extra" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-white/5">
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Monto Extra (Q)</label>
                    <input
                      v-model.number="payrollForm.monto_extra"
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                    />
                  </div>
                  <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-1">Observación</label>
                    <input
                      v-model="payrollForm.observacion_extra"
                      type="text"
                      placeholder="Ej. Bono especial"
                      class="w-full h-11 px-3 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                    />
                  </div>
                </div>
              </div>

              <!-- Resumen de Liquidación -->
              <div class="md:col-span-2 bg-gradient-to-r from-emerald-500/20 via-primary/20 to-slate-900 p-6 rounded-3xl border border-emerald-500/30">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60 mb-3">Resumen de Liquidación</p>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between">
                    <span class="text-white/60">Sueldo Base Calculado:</span>
                    <span class="font-bold text-white">Q {{ formatCurrency(payrollCalculations.salarioBaseCalculado) }}</span>
                  </div>
                  <div v-if="payrollForm.tiene_horas_extras" class="flex justify-between text-amber-300">
                    <span>Horas Extras ({{ payrollForm.horas_extras }} hrs):</span>
                    <span class="font-bold">+Q {{ formatCurrency(payrollCalculations.totalHorasExtras) }}</span>
                  </div>
                  <div v-if="payrollForm.tiene_viaticos" class="flex justify-between text-sky-300">
                    <span>Viáticos / Adicionales:</span>
                    <span class="font-bold">+Q {{ formatCurrency(payrollCalculations.montoViaticos) }}</span>
                  </div>
                  <div v-if="payrollForm.tiene_extra" class="flex justify-between text-fuchsia-300">
                    <span>Pago Extra:</span>
                    <span class="font-bold">+Q {{ formatCurrency(payrollCalculations.montoExtra) }}</span>
                  </div>
                  <div class="pt-3 border-t border-white/10 flex justify-between items-center">
                    <span class="font-black text-sm text-white uppercase tracking-wider">Total a Pagar:</span>
                    <span class="font-black text-2xl text-emerald-400">Q {{ formatCurrency(payrollCalculations.totalPagar) }}</span>
                  </div>
                  <div class="pt-1 flex justify-between items-center text-white/50">
                    <span class="font-black text-xs uppercase tracking-wider">Valor Líquido (Sin Viáticos):</span>
                    <span class="font-black text-lg">Q {{ formatCurrency(payrollCalculations.totalPagar - payrollCalculations.montoViaticos) }}</span>
                  </div>
                </div>
              </div>

              <!-- Observaciones -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Observaciones Generales</label>
                <textarea
                  v-model="payrollForm.observaciones"
                  rows="2"
                  placeholder="Notas adicionales sobre este pago..."
                  class="w-full p-4 rounded-xl bg-slate-900 border border-white/10 text-sm text-white focus:outline-none focus:border-primary resize-none"
                ></textarea>
              </div>
            </div>

            <!-- Botones -->
            <div class="flex gap-3 pt-4 border-t border-white/5">
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex-1 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-emerald-500/20 transition-all flex items-center justify-center gap-2"
              >
                <span v-if="isSubmitting">Procesando...</span>
                <span v-else>Confirmar y Registrar Pago</span>
              </button>
              <button
                type="button"
                @click="closePayrollModal"
                class="px-6 py-4 rounded-2xl bg-white/5 hover:bg-white/10 text-white/50 hover:text-white text-xs font-bold transition-all"
              >
                Cancelar
              </button>
            </div>
          </form>

        </div>
      </div>
    </Transition>

    <!-- MODAL: RECIBO / BOLETA DE PAGO IMPRIMIBLE -->
    <Transition name="fade">
      <div v-if="showReceiptModal && selectedReceipt" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showReceiptModal = false" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-xl bg-white text-slate-900 rounded-[32px] p-8 shadow-2xl overflow-y-auto max-h-[92vh] z-10 print:m-0 print:p-4 print:shadow-none">

          <!-- Header Boleta -->
          <div class="flex justify-between items-start border-b border-slate-200 pb-6 mb-6">
            <div>
              <h2 class="text-2xl font-black uppercase tracking-tight text-slate-950">Concretos del Oriente</h2>
              <p class="text-xs text-slate-500 font-semibold mt-0.5">Comprobante Oficial de Pago de Nómina</p>
              <span class="inline-block mt-2 px-3 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-700">
                Periodo: {{ formatPeriodo(selectedReceipt.periodo) }}
              </span>
            </div>
            <div class="text-right">
              <p class="text-[10px] uppercase font-bold text-slate-400">Recibo No.</p>
              <p class="font-mono font-bold text-base text-primary">#{{ String(selectedReceipt.id).padStart(5, '0') }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ formatDate(selectedReceipt.fecha_pago) }}</p>
            </div>
          </div>

          <!-- Datos Colaborador -->
          <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2 mb-6">
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase block">Colaborador</span>
                <span class="font-bold text-slate-800 text-sm">{{ selectedReceipt.nombres }} {{ selectedReceipt.apellidos }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase block">Puesto</span>
                <span class="font-semibold text-slate-700">{{ selectedReceipt.puesto || 'Colaborador' }}</span>
              </div>
              <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase block">Días Trabajados</span>
                <span class="font-bold text-slate-700">{{ selectedReceipt.dias_trabajados }} días</span>
              </div>
              <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase block">Tipo de Planilla</span>
                <span class="font-bold text-slate-700">{{ selectedReceipt.tipo_planilla || 'Mensual' }}</span>
              </div>
            </div>
          </div>

          <!-- Detalle de Conceptos -->
          <div class="space-y-3 mb-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Desglose de Pago</h4>
            <div class="border rounded-2xl overflow-hidden border-slate-100 divide-y divide-slate-100 text-xs">
              <div class="p-3 flex justify-between items-center bg-slate-50/50">
                <span class="text-slate-600">Sueldo Base Calculado</span>
                <span class="font-bold text-slate-800">Q {{ formatCurrency(selectedReceipt.salario_base_calculado || selectedReceipt.salario_base) }}</span>
              </div>
              <div v-if="Number(selectedReceipt.total_horas_extras) > 0" class="p-3 flex justify-between items-center">
                <div>
                  <span class="text-slate-600">Horas Extras</span>
                  <span class="text-[10px] text-slate-400 ml-1.5">({{ selectedReceipt.horas_extras }} hrs)</span>
                </div>
                <span class="font-bold text-slate-800">+Q {{ formatCurrency(selectedReceipt.total_horas_extras) }}</span>
              </div>
              <div v-if="Number(selectedReceipt.monto_viaticos) > 0" class="p-3 flex justify-between items-center">
                <div>
                  <span class="text-slate-600">Viáticos / Bonificación</span>
                  <p v-if="selectedReceipt.observaciones_viaticos" class="text-[10px] text-slate-400">{{ selectedReceipt.observaciones_viaticos }}</p>
                </div>
                <span class="font-bold text-slate-800">+Q {{ formatCurrency(selectedReceipt.monto_viaticos) }}</span>
              </div>
              <div v-if="Number(selectedReceipt.monto_extra) > 0" class="p-3 flex justify-between items-center">
                <div>
                  <span class="text-slate-600">Pago Extra</span>
                  <p v-if="selectedReceipt.observacion_extra" class="text-[10px] text-slate-400">{{ selectedReceipt.observacion_extra }}</p>
                </div>
                <span class="font-bold text-slate-800">+Q {{ formatCurrency(selectedReceipt.monto_extra) }}</span>
              </div>
              <div class="p-3 flex justify-between items-center bg-slate-100 text-slate-800">
                <span class="font-black text-sm uppercase">Total a Pagar en el Mes</span>
                <span class="font-black text-lg">Q {{ formatCurrency(selectedReceipt.total_pagar) }}</span>
              </div>
              <div class="p-4 flex flex-col justify-center items-end bg-emerald-50 text-emerald-950">
                <div class="w-full flex justify-between items-center">
                  <span class="font-black text-sm uppercase">Valor a Pagar (Líquido)</span>
                  <span class="font-black text-2xl text-emerald-700">Q {{ formatCurrency(Number(selectedReceipt.total_pagar) - Number(selectedReceipt.monto_viaticos || 0)) }}</span>
                </div>
                <p class="text-[9px] text-emerald-700/60 font-bold uppercase tracking-wider mt-1 text-right max-w-[80%]">(Base + Horas + Extras) No se suman viáticos. Los viáticos se pagan por separado.</p>
              </div>
            </div>
          </div>

          <!-- Firmas -->
          <div class="pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs text-slate-500 mb-6">
            <div>
              <div class="h-12 border-b border-slate-300"></div>
              <p class="mt-2 font-bold">Firma Autorizada</p>
              <p class="text-[10px] text-slate-400">Recursos Humanos</p>
            </div>
            <div>
              <div class="h-12 border-b border-slate-300"></div>
              <p class="mt-2 font-bold">Firma de Recibido</p>
              <p class="text-[10px] text-slate-400">{{ selectedReceipt.nombres }} {{ selectedReceipt.apellidos }}</p>
            </div>
          </div>

          <!-- Botones Modal -->
          <div class="flex gap-3 print:hidden">
            <button
              @click="printReceipt"
              class="flex-1 bg-primary hover:bg-primary/90 text-white py-3.5 rounded-2xl font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-primary/20"
            >
              <PrinterIcon class="w-4 h-4" /> Imprimir Boleta
            </button>
            <button
              @click="showReceiptModal = false"
              class="px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-bold text-xs"
            >
              Cerrar
            </button>
          </div>

        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  BanknotesIcon, PlusIcon, MagnifyingGlassIcon, CalendarIcon,
  TrashIcon, DocumentTextIcon, XMarkIcon, PrinterIcon,
  UserGroupIcon, ClockIcon, ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const swalBase = {
  background: '#0f172a',
  color: '#ffffff',
  confirmButtonColor: '#6366f1',
  cancelButtonColor: '#475569',
  customClass: {
    popup: 'rounded-3xl border border-white/10 shadow-2xl',
    confirmButton: 'rounded-xl px-6 py-3 font-bold text-sm',
    cancelButton: 'rounded-xl px-6 py-3 font-bold text-sm'
  }
};

// State
const payments = ref([]);
const activePersonnelList = ref([]);
const loading = ref(true);
const isSubmitting = ref(false);

const searchQuery = ref('');
const filterPeriodo = ref('');
const viewMode = ref('individual');

const showPayrollModal = ref(false);
const showReceiptModal = ref(false);
const selectedReceipt = ref(null);

const payrollForm = ref({
  personnel_id: '',
  periodo: '',
  fecha_pago: '',
  dias_trabajados: 30,
  tiene_horas_extras: false,
  horas_extras: 0,
  tiene_viaticos: false,
  cantidad_viaticos: 0,
  fecha_viaticos_inicio: '',
  fecha_viaticos_fin: '',
  monto_viaticos: 0,
  observaciones_viaticos: '',
  tiene_extra: false,
  monto_extra: 0,
  observacion_extra: '',
  observaciones: ''
});

// Stats
const stats = computed(() => {
  const totalMonto = payments.value.reduce((acc, p) => acc + Number(p.total_pagar || 0), 0);
  const totalExtras = payments.value.reduce((acc, p) => acc + Number(p.total_horas_extras || 0), 0);
  const totalViaticos = payments.value.reduce((acc, p) => acc + Number(p.monto_viaticos || 0), 0);
  const count = payments.value.length;

  return [
    { label: 'Total Pagado', value: `Q ${formatCurrency(totalMonto)}`, change: 'Histórico', badge: 'Planilla', icon: BanknotesIcon, color: 'text-emerald-400', bgColor: 'bg-emerald-500/10' },
    { label: 'Colaboradores Pagados', value: count.toString(), change: 'Boletas', badge: 'Registros', icon: UserGroupIcon, color: 'text-primary', bgColor: 'bg-primary/10' },
    { label: 'Horas Extras', value: `Q ${formatCurrency(totalExtras)}`, change: 'Total', badge: 'Extras', icon: ClockIcon, color: 'text-amber-400', bgColor: 'bg-amber-500/10' },
    { label: 'Viáticos y Bonos', value: `Q ${formatCurrency(totalViaticos)}`, change: 'Total', badge: 'Bonos', icon: ArrowTrendingUpIcon, color: 'text-sky-400', bgColor: 'bg-sky-500/10' },
  ];
});

// Payroll Dynamic Calculations
const selectedPersonnel = computed(() => {
  return activePersonnelList.value.find(p => p.id === Number(payrollForm.value.personnel_id)) || null;
});

const payrollCalculations = computed(() => {
  const emp = selectedPersonnel.value;
  const salarioBase = emp ? Number(emp.salario_base || 0) : 0;
  const dias = Number(payrollForm.value.dias_trabajados || 30);
  const salarioBaseCalculado = Number(((salarioBase / 30) * dias).toFixed(2));

  let totalHorasExtras = 0;
  let tarifaHoraExtra = 0;
  if (payrollForm.value.tiene_horas_extras && salarioBase > 0) {
    const valorHoraNormal = salarioBase / 30 / 8;
    tarifaHoraExtra = Number((valorHoraNormal * 1.5).toFixed(2));
    totalHorasExtras = Number((tarifaHoraExtra * Number(payrollForm.value.horas_extras || 0)).toFixed(2));
  }

  let montoViaticos = 0;
  if (payrollForm.value.tiene_viaticos) {
    montoViaticos = Number(payrollForm.value.monto_viaticos || 0);
  }

  let montoExtra = 0;
  if (payrollForm.value.tiene_extra) {
    montoExtra = Number(payrollForm.value.monto_extra || 0);
  }

  const totalPagar = Number((salarioBaseCalculado + totalHorasExtras + montoViaticos + montoExtra).toFixed(2));

  return {
    salarioBase,
    salarioBaseCalculado,
    tarifaHoraExtra,
    horasExtras: Number(payrollForm.value.horas_extras || 0),
    totalHorasExtras,
    montoViaticos,
    montoExtra,
    totalPagar
  };
});

const onPayrollPersonnelChange = () => {
  const emp = selectedPersonnel.value;
  if (emp) {
    payrollForm.value.dias_trabajados = 30;
  }
};

// Filtered & Paginated Payments
const filteredPayments = computed(() => {
  return payments.value.filter(p => {
    const q = searchQuery.value.toLowerCase();
    const matchSearch = !q || (p.nombres && p.nombres.toLowerCase().includes(q)) ||
                        (p.apellidos && p.apellidos.toLowerCase().includes(q)) ||
                        (p.puesto && p.puesto.toLowerCase().includes(q));
    const matchPeriodo = !filterPeriodo.value || p.periodo === filterPeriodo.value;
    return matchSearch && matchPeriodo;
  });
});

const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(filteredPayments.value.length / itemsPerPage));
const paginatedPayments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredPayments.value.slice(start, start + itemsPerPage);
});

const consolidatedData = computed(() => {
  const map = new Map();
  filteredPayments.value.forEach(p => {
    const key = p.personnel_id;
    if (!map.has(key)) {
      map.set(key, {
        personnel_id: key,
        nombres: p.nombres,
        apellidos: p.apellidos,
        puesto: p.puesto,
        dias_trabajados: 0,
        salario_base: 0,
        salario_base_calculado: 0,
        horas_extras: 0,
        total_horas_extras: 0,
        monto_viaticos: 0,
        monto_extra: 0,
        total_pagar: 0
      });
    }
    const item = map.get(key);
    item.dias_trabajados += Number(p.dias_trabajados || 0);
    item.salario_base = Number(p.salario_base || 0);
    item.salario_base_calculado += Number(p.salario_base_calculado || p.salario_base || 0);
    item.horas_extras += Number(p.horas_extras || 0);
    item.total_horas_extras += Number(p.total_horas_extras || 0);
    item.monto_viaticos += Number(p.monto_viaticos || 0);
    item.monto_extra += Number(p.monto_extra || 0);
    item.total_pagar += Number(p.total_pagar || 0);
  });
  return Array.from(map.values());
});

// Lifecycle
onMounted(() => {
  fetchPayments();
  fetchActivePersonnel();
});

const fetchPayments = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/personnel/payroll-payments`);
    const result = await res.json();
    if (result.status === 'success') {
      payments.value = result.data || [];
    }
  } catch (err) {
    console.error('Error fetching payments:', err);
  } finally {
    loading.value = false;
  }
};

const fetchActivePersonnel = async () => {
  try {
    const res = await fetch(`${BASE_URL}/payrolls/active-personnel`);
    const result = await res.json();
    if (result.status === 'success') {
      activePersonnelList.value = result.data || [];
    }
  } catch (err) {
    console.error('Error fetching active personnel:', err);
  }
};

const openPayrollModal = () => {
  const now = new Date();
  const defaultPeriodo = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

  payrollForm.value = {
    personnel_id: '',
    periodo: defaultPeriodo,
    fecha_pago: now.toISOString().split('T')[0],
    dias_trabajados: 30,
    tiene_horas_extras: false,
    horas_extras: 0,
    tiene_viaticos: false,
    cantidad_viaticos: 0,
    fecha_viaticos_inicio: '',
    fecha_viaticos_fin: '',
    monto_viaticos: 0,
    observaciones_viaticos: '',
    tiene_extra: false,
    monto_extra: 0,
    observacion_extra: '',
    observaciones: ''
  };
  showPayrollModal.value = true;
};

const closePayrollModal = () => {
  showPayrollModal.value = false;
};

const submitPayrollPayment = async () => {
  if (!payrollForm.value.personnel_id) {
    Swal.fire({ ...swalBase, title: 'Atención', text: 'Por favor seleccione un colaborador.', icon: 'warning' });
    return;
  }

  isSubmitting.value = true;
  try {
    const fd = new FormData();
    fd.append('personnel_id',           payrollForm.value.personnel_id);
    fd.append('periodo',                payrollForm.value.periodo);
    fd.append('fecha_pago',             payrollForm.value.fecha_pago);
    fd.append('dias_trabajados',        payrollForm.value.dias_trabajados);
    fd.append('salario_base',           payrollCalculations.value.salarioBase);
    fd.append('salario_base_calculado', payrollCalculations.value.salarioBaseCalculado);
    fd.append('tiene_horas_extras',     payrollForm.value.tiene_horas_extras ? '1' : '0');
    fd.append('horas_extras',           payrollCalculations.value.horasExtras);
    fd.append('tarifa_hora_extra',      payrollCalculations.value.tarifaHoraExtra);
    fd.append('total_horas_extras',     payrollCalculations.value.totalHorasExtras);
    fd.append('tiene_viaticos',         payrollForm.value.tiene_viaticos ? '1' : '0');
    fd.append('cantidad_viaticos',      payrollForm.value.cantidad_viaticos || 0);
    fd.append('fecha_viaticos_inicio',  payrollForm.value.fecha_viaticos_inicio || '');
    fd.append('fecha_viaticos_fin',     payrollForm.value.fecha_viaticos_fin || '');
    fd.append('monto_viaticos',         payrollCalculations.value.montoViaticos);
    fd.append('observaciones_viaticos', payrollForm.value.observaciones_viaticos || '');
    fd.append('tiene_extra',            payrollForm.value.tiene_extra ? '1' : '0');
    fd.append('monto_extra',            payrollCalculations.value.montoExtra);
    fd.append('observacion_extra',      payrollForm.value.observacion_extra || '');
    fd.append('total_pagar',            payrollCalculations.value.totalPagar);
    fd.append('observaciones',          payrollForm.value.observaciones || '');

    const res = await fetch(`${BASE_URL}/personnel/payroll-payments`, {
      method: 'POST',
      body: fd
    });
    const result = await res.json();

    if (result.status === 'success') {
      await fetchPayments();
      closePayrollModal();

      const created = result.data;
      Swal.fire({
        ...swalBase,
        title: '¡Pago Registrado!',
        html: `<p class="text-white/80">Total pagado: <b class="text-emerald-400 text-lg">Q ${formatCurrency(payrollCalculations.value.totalPagar)}</b></p>`,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Ver Boleta',
        cancelButtonText: 'Cerrar'
      }).then(r => {
        if (r.isConfirmed && created) {
          openReceiptModal(created);
        }
      });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: result.message || 'Error al registrar el pago.', icon: 'error' });
    }
  } catch (err) {
    console.error('Error submitting payroll payment:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al registrar pago.', icon: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

const deletePayrollPayment = async (id) => {
  const result = await Swal.fire({
    ...swalBase,
    title: '¿Eliminar registro de pago?',
    text: 'Esta acción cancelará el registro del pago de planilla.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(`${BASE_URL}/personnel/payroll-payments/${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.status === 'success') {
      await fetchPayments();
      Swal.fire({ ...swalBase, title: '¡Eliminado!', text: 'El pago ha sido eliminado correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: data.message || 'Error al eliminar', icon: 'error' });
    }
  } catch (err) {
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  }
};

const openReceiptModal = (payment) => {
  selectedReceipt.value = payment;
  showReceiptModal.value = true;
};

const printReceipt = () => {
  window.print();
};

const formatCurrency = (val) => {
  const n = Number(val);
  if (isNaN(n)) return '0.00';
  return n.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const formatPeriodo = (val) => {
  if (!val) return '';
  const [y, m] = val.split('-');
  const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  return `${months[parseInt(m) - 1] || m} ${y}`;
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
