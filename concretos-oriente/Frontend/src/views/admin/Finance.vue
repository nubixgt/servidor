<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-12 text-white">
    <!-- KPIs Section -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div
        v-for="(kpi, i) in kpis"
        :key="i"
        class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-48 cursor-pointer group hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] hover:scale-105 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000"
      >
        <div class="flex justify-between items-start">
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">{{ kpi.label }}</span>
          <div :class="`bg-${kpi.color}/20 p-4 rounded-2xl text-${kpi.color} shadow-lg shadow-${kpi.color}/20 border border-white/10`">
            <component :is="kpi.icon" class="w-6 h-6" />
          </div>
        </div>
        <div class="mt-auto">
          <h2 :class="`text-4xl font-black tracking-tighter italic ${kpi.label.includes('Pasivos') || kpi.label.includes('Egreso') ? 'text-tertiary' : 'text-white'}`">{{ kpi.value }}</h2>
          <div :class="`flex items-center gap-2.5 mt-3 text-[10px] font-black uppercase tracking-widest ${kpi.label.includes('Pasivos') || kpi.label.includes('Egreso') ? 'text-tertiary shadow-[0_0_10px_#f43f5e30]' : 'text-primary shadow-[0_0_10px_#6366f130]'}`">
            <ArrowTrendingUpIcon v-if="kpi.label.includes('Líquido') || kpi.label.includes('Ingreso')" class="w-4 h-4" />
            {{ kpi.sub }}
          </div>
        </div>
      </div>
    </section>

    <!-- Charts Row -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-10">
      <div class="lg:col-span-2 glass-card p-5 md:p-12 rounded-[56px] relative overflow-hidden transition-all duration-500 hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)]" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="flex justify-between items-center mb-12 relative z-10">
          <div>
            <h3 class="text-3xl font-black text-white uppercase italic tracking-tighter">Dinámica Fiscal</h3>
            <p class="text-sm font-bold text-white/30 mt-2 uppercase tracking-widest">Análisis de entrada vs salida YTD</p>
          </div>
          <div class="flex gap-10">
            <div class="flex items-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-primary shadow-[0_0_12px_#6366f1]"></span>
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Ingresos</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-tertiary shadow-[0_0_12px_#f43f5e]"></span>
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Egresos</span>
            </div>
          </div>
        </div>
        <div class="h-72 relative flex items-end justify-between px-6 z-10">
           <div class="absolute inset-0 flex flex-col justify-between text-[9px] font-black text-white/10 uppercase tracking-widest pointer-events-none pb-12">
            <div v-for="val in chartScale" :key="val" class="border-t border-white/5 w-full pt-2 flex justify-between items-center">
              <span>{{ val }}</span>
              <div class="w-[85%] border-t border-dashed border-white/5"></div>
            </div>
          </div>
          <div class="flex-1 flex items-end justify-around gap-10 h-full z-10 pb-6 ml-10">
            <div v-for="(col, i) in chartData" :key="col.month" class="flex flex-col items-center gap-4 w-full group h-full justify-end">
              <div class="flex gap-3 w-full items-end justify-center h-full">
                <div 
                  :style="{ height: `${col.incPct}%` }"
                  :class="`w-5 rounded-t-xl transition-all duration-700 group-hover:scale-x-110 bg-primary/40 hover:bg-primary shadow-lg hover:shadow-primary/50`"
                  :title="`Ingresos: Q${col.inc}`"
                ></div>
                <div 
                  :style="{ height: `${col.expPct}%` }"
                  :class="`w-5 rounded-t-xl transition-all duration-700 group-hover:scale-x-110 bg-tertiary/40 hover:bg-tertiary shadow-lg hover:shadow-tertiary/50`"
                  :title="`Egresos: Q${col.exp}`"
                ></div>
              </div>
              <span class="text-[10px] font-black tracking-widest uppercase text-white/40 group-hover:text-white">{{ col.month }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="glass-card p-5 md:p-12 rounded-[56px] flex flex-col border border-white/5 transition-all duration-500 hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)]" data-aos="zoom-in-up" data-aos-duration="1000">
        <h3 class="text-3xl font-black text-white uppercase italic tracking-tighter">Vector de Presupuesto</h3>
        <p class="text-[10px] font-bold text-white/30 mt-2 mb-12 uppercase tracking-[0.2em]">Perfil de distribución de recursos</p>
        <div class="relative w-60 h-60 mx-auto mb-12 flex items-center justify-center">
          <svg class="w-full h-full transform -rotate-90 drop-shadow-2xl" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="rgba(255,255,255,0.05)" stroke-width="4.5" />
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#6366f1" stroke-width="4.5" :stroke-dasharray="`${pieChartData[0]?.val || 0} 100`" stroke-linecap="round" class="drop-shadow-[0_0_8px_#6366f1] transition-all duration-1000" />
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#f43f5e" stroke-width="4.5" :stroke-dasharray="`${pieChartData[1]?.val || 0} 100`" :stroke-dashoffset="`-${pieChartData[0]?.val || 0}`" stroke-linecap="round" class="drop-shadow-[0_0_8px_#f43f5e] transition-all duration-1000" />
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="rgba(255,255,255,0.3)" stroke-width="4.5" :stroke-dasharray="`${pieChartData[2]?.val || 0} 100`" :stroke-dashoffset="`-${(pieChartData[0]?.val || 0) + (pieChartData[1]?.val || 0)}`" stroke-linecap="round" class="transition-all duration-1000" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center text-center translate-y-1">
            <span class="text-5xl font-black text-white italic tracking-tighter">{{ budgetPercent }}%</span>
            <span class="text-[8px] text-white/30 uppercase font-black tracking-[0.4em] mt-2">Margen Neto</span>
          </div>
        </div>
        <div class="space-y-6 mt-auto">
          <div v-for="item in pieChartData" :key="item.label" class="flex items-center justify-between group cursor-pointer border-b border-white/5 pb-4">
            <div class="flex items-center gap-4">
              <span :class="`w-3 h-3 rounded-full ${item.color} group-hover:scale-150 transition-transform duration-500`"></span>
              <span class="text-xs font-bold text-white/60 tracking-wider uppercase">{{ item.label }}</span>
            </div>
            <span class="text-sm font-black text-white italic">{{ item.val }}%</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Transactions Section -->
    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500 hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)]" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="p-5 md:p-12 flex flex-col md:flex-row md:items-center justify-between gap-8 border-b border-white/5 bg-white/5 backdrop-blur-3xl">
        <div>
          <h3 class="text-2xl md:text-4xl font-black text-white italic uppercase tracking-tighter">Protocolo de Transacciones</h3>
          <p class="text-[10px] font-bold text-white/30 mt-3 uppercase tracking-[0.3em]">Libro mayor financiero en tiempo real</p>
        </div>
        <div class="flex flex-col md:flex-row items-center gap-4">
          <input v-model="searchQuery" type="text" placeholder="Buscar..." class="w-full md:w-64 bg-black/20 border border-white/10 rounded-2xl px-5 py-3 text-xs font-bold text-white tracking-widest focus:outline-none focus:border-primary/50 placeholder:text-white/30" />
          <select v-model="filterType" class="w-full md:w-auto bg-black/20 border border-white/10 rounded-2xl px-5 py-3 text-xs font-bold text-white uppercase tracking-widest focus:outline-none focus:border-primary/50 appearance-none cursor-pointer">
            <option value="Todos">Todas las Transacciones</option>
            <option value="Ingreso">Solo Ingresos</option>
            <option value="Egreso">Solo Egresos</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto px-6">
        <table class="w-full min-w-[640px] text-left">
          <thead>
            <tr class="text-[11px] font-bold text-white/20 uppercase tracking-[0.3em]">
              <th class="px-4 md:px-8 py-4 md:py-8">Descripción / Fecha</th>
              <th class="px-4 md:px-8 py-4 md:py-8">Tipo / Entidad</th>
              <th class="px-4 md:px-8 py-4 md:py-8">Proyecto Vinculado</th>
              <th class="px-10 py-10 text-right">Valor Neto</th>
              <th class="px-4 md:px-8 py-4 md:py-8">Comprobante</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading">
              <td colspan="5" class="px-4 md:px-8 py-4 md:py-8 text-center text-white/40">Sincronizando libro mayor...</td>
            </tr>
            <tr v-else-if="paginatedTransactions.length === 0">
              <td colspan="5" class="px-4 md:px-8 py-4 md:py-8 text-center text-white/40">No hay transacciones registradas.</td>
            </tr>
            <tr v-for="tx in paginatedTransactions" :key="tx.id + tx.transaction_type" class="hover:bg-white/5 group transition-all duration-500 cursor-pointer">
              <td class="px-4 md:px-8 py-4 md:py-8">
                <div class="flex items-center gap-6">
                  <div :class="`w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center transition-all duration-500 shadow-xl ${tx.transaction_type === 'Ingreso' ? 'text-primary group-hover:bg-primary group-hover:shadow-primary/30' : 'text-tertiary group-hover:bg-tertiary group-hover:shadow-tertiary/30'} group-hover:text-white`">
                    <component :is="tx.transaction_type === 'Ingreso' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-8 h-8" />
                  </div>
                  <div>
                    <p class="font-black text-lg text-white tracking-tight italic uppercase truncate max-w-[200px]" :title="tx.descripcion || tx.transaction_type">{{ tx.descripcion || tx.transaction_type }}</p>
                    <p class="text-[10px] font-bold text-white/30 mt-1 uppercase tracking-widest">{{ formatDate(tx.fecha_ingreso || tx.fecha_egreso) }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 md:px-8 py-4 md:py-8">
                <p class="font-black text-sm text-white uppercase">{{ tx.tipo_ingreso || tx.tipo_egreso }}</p>
                <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mt-1 block">{{ tx.pagador || tx.beneficiario || 'N/A' }}</span>
              </td>
              <td class="px-4 md:px-8 py-4 md:py-8">
                <span class="text-sm font-bold text-white/40 uppercase tracking-widest">{{ tx.proyecto_nombre || 'Múltiples / General' }}</span>
              </td>
              <td :class="`px-10 py-10 text-right font-black italic text-2xl ${tx.transaction_type === 'Ingreso' ? 'text-primary shadow-[0_0_15px_#6366f130]' : 'text-tertiary shadow-[0_0_15px_#f43f5e30]'}`">
                {{ tx.transaction_type === 'Ingreso' ? '+' : '-' }}Q{{ Number(tx.monto).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
              </td>
              <td class="px-4 md:px-8 py-4 md:py-8 text-center">
                <a v-if="tx.comprobante_path" :href="getFileUrl(tx.comprobante_path)" target="_blank" class="inline-flex p-3 rounded-2xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all shadow-xl" title="Ver Comprobante">
                  <DocumentTextIcon class="w-6 h-6" />
                </a>
                <span v-else class="text-[10px] font-black text-white/20 uppercase tracking-widest">Sin Adjunto</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="totalPages > 1" class="p-5 md:p-10 flex flex-col md:flex-row justify-between items-center gap-8 border-t border-white/5 bg-black/20">
        <p class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">Página {{ currentPage }} de {{ totalPages }}</p>
        <div class="flex items-center gap-4">
          <button @click="currentPage--" :disabled="currentPage === 1" class="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/40 hover:bg-white/5 disabled:opacity-30 transition-all">
            <ChevronLeftIcon class="w-7 h-7" />
          </button>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/40 hover:bg-white/5 disabled:opacity-30 transition-all">
            <ChevronRightIcon class="w-7 h-7" />
          </button>
        </div>
      </div>
    </section>

    <!-- FAB -->
    <button @click="openFinanceModal" class="fixed bottom-6 right-6 md:bottom-12 md:right-12 w-14 h-14 md:w-20 md:h-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50 group">
      <PlusIcon class="w-10 h-10 group-hover:rotate-90 transition-transform duration-500 shadow-[0_0_20px_rgba(99,102,241,0.5)]" />
    </button>

    <!-- NEW TRANSACTION MODAL -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
      <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Registro Financiero</h3>
          <button @click="closeModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <div class="flex gap-4 mb-8">
          <button @click="txType = 'Ingreso'" :class="['flex-1 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all', txType === 'Ingreso' ? 'bg-primary/20 text-primary border border-primary/20 shadow-[0_0_15px_#6366f130]' : 'bg-white/5 text-white/40 hover:text-white border border-white/5']">
            Registrar Ingreso
          </button>
          <button @click="txType = 'Egreso'" :class="['flex-1 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all', txType === 'Egreso' ? 'bg-tertiary/20 text-tertiary border border-tertiary/20 shadow-[0_0_15px_#f43f5e30]' : 'bg-white/5 text-white/40 hover:text-white border border-white/5']">
            Registrar Egreso
          </button>
        </div>

        <!-- FORMULARIO DE INGRESO -->
        <form v-if="txType === 'Ingreso'" @submit.prevent="submitIncome" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto</label>
              <select v-model="formIncome.proyecto_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="">Ingreso Corporativo General</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
              </select>
            </div>
            
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Ingreso *</label>
              <input v-model="formIncome.tipo_ingreso" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha *</label>
              <input v-model="formIncome.fecha_ingreso" type="date" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Cuenta Bancaria *</label>
              <select v-model="formIncome.cuenta_bancaria" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccione...</option>
                <option v-for="c in bankAccounts" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Cheque Deposito / ref</label>
              <input v-model="formIncome.numero_cheque" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Pagador (Cliente)</label>
              <input v-model="formIncome.pagador" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Comprobante Digital</label>
              <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="w-full text-white/60 file:mr-4 file:py-4 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:bg-white/10 file:text-white hover:file:bg-white/20 transition-all cursor-pointer" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Descripción o Concepto</label>
              <textarea v-model="formIncome.descripcion" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50"></textarea>
            </div>

            <!-- REGISTROS DINAMICOS INGRESO -->
            <div class="md:col-span-2 space-y-4 bg-white/5 p-6 rounded-3xl border border-white/5">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-white uppercase tracking-wider">Registros</h4>
                <button type="button" @click="addRegistro(formIncome)" class="flex items-center gap-2 text-xs font-bold text-primary hover:text-primary/70 uppercase tracking-widest transition-all">
                  <PlusIcon class="w-4 h-4" /> Agregar más
                </button>
              </div>
              
              <div v-for="(rec, idx) in formIncome.registros" :key="idx" class="flex gap-4 items-start">
                <div class="flex-1 space-y-2">
                  <label class="text-[10px] font-bold text-white/40 uppercase tracking-wider">Descripción</label>
                  <input v-model="rec.descripcion" type="text" required class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50" />
                </div>
                <div class="w-1/3 space-y-2">
                  <label class="text-[10px] font-bold text-white/40 uppercase tracking-wider">Monto</label>
                  <input :value="rec.monto_display" @input="handleCurrencyInput($event, rec)" type="text" required placeholder="Q0.00" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50" />
                </div>
                <button type="button" @click="removeRegistro(formIncome, idx)" v-if="formIncome.registros.length > 1" class="mt-8 p-3 text-tertiary hover:bg-tertiary/20 rounded-xl transition-all">
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>

              <div class="pt-4 border-t border-white/10 flex justify-end items-center gap-4">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Monto Total *</label>
                <input :value="'Q' + Number(formIncome.monto || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})" type="text" readonly class="w-40 bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none cursor-not-allowed font-black text-right" />
              </div>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              Registrar Ingreso
            </button>
          </div>
        </form>

        <!-- FORMULARIO DE EGRESO -->
        <form v-if="txType === 'Egreso'" @submit.prevent="submitExpense" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto</label>
              <select v-model="formExpense.proyecto_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50 appearance-none">
                <option value="">Egreso Corporativo General</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
              </select>
            </div>
            
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Egreso *</label>
              <select v-model="formExpense.tipo_egreso" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50 appearance-none">
                <option value="Proveedor">Proveedor</option>
                <option value="Contratista">Contratista</option>
                <option value="Caja Chica">Caja Chica</option>
                <option value="Nómina">Nómina</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Combustible">Combustible</option>
                <option value="Recurrentes">Recurrentes</option>
              </select>
            </div>

            <div class="space-y-2" v-if="formExpense.tipo_egreso === 'Recurrentes'">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Seleccionar Recurrente *</label>
              <select @change="handleRecurrentSelection" required class="w-full bg-primary/20 border border-primary/50 rounded-2xl px-5 py-4 text-white font-bold focus:outline-none focus:border-primary appearance-none transition-all">
                <option value="" disabled selected>Elige un recurrente...</option>
                <option v-for="r in recurrentsList" :key="r.id" :value="r.id">{{ r.concepto }} - Q{{ Number(r.monto).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha *</label>
              <input v-model="formExpense.fecha_egreso" type="date" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Cuenta Origen</label>
              <select v-model="formExpense.cuenta_origen" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50 appearance-none">
                <option value="">Seleccione (Opcional)...</option>
                <option v-for="c in bankAccounts" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Número Cheque / Ref.</label>
              <input v-model="formExpense.numero_cheque" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Beneficiario *</label>
              <div v-if="formExpense.tipo_egreso === 'Contratista'" class="flex gap-2">
                <select v-model="formExpense.contratista_id" @change="handleContractorSelect" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50 appearance-none">
                  <option value="" disabled>Seleccione un contratista...</option>
                  <option v-for="c in contractors" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                </select>
                <button type="button" @click="openQuickContractorModal" class="shrink-0 w-14 h-14 flex items-center justify-center rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-white/60 hover:text-white transition-all" title="Nuevo contratista">
                  <PlusIcon class="w-5 h-5" />
                </button>
              </div>
              <input v-else v-model="formExpense.beneficiario" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Comprobante Digital</label>
              <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="w-full text-white/60 file:mr-4 file:py-4 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:bg-white/10 file:text-white hover:file:bg-white/20 transition-all cursor-pointer" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Descripción o Concepto</label>
              <textarea v-model="formExpense.descripcion" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50"></textarea>
            </div>

            <!-- REGISTROS DINAMICOS EGRESO -->
            <div class="md:col-span-2 space-y-4 bg-white/5 p-6 rounded-3xl border border-white/5">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-white uppercase tracking-wider">Registros</h4>
                <button type="button" @click="addRegistro(formExpense)" class="flex items-center gap-2 text-xs font-bold text-tertiary hover:text-tertiary/70 uppercase tracking-widest transition-all">
                  <PlusIcon class="w-4 h-4" /> Agregar más
                </button>
              </div>
              
              <div v-for="(rec, idx) in formExpense.registros" :key="idx" class="flex gap-4 items-start">
                <div class="flex-1 space-y-2">
                  <label class="text-[10px] font-bold text-white/40 uppercase tracking-wider">Descripción</label>
                  <input v-model="rec.descripcion" type="text" required class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-tertiary/50" />
                </div>
                <div class="w-1/3 space-y-2">
                  <label class="text-[10px] font-bold text-white/40 uppercase tracking-wider">Monto</label>
                  <input :value="rec.monto_display" @input="handleCurrencyInput($event, rec)" type="text" required placeholder="Q0.00" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-tertiary/50" />
                </div>
                <button type="button" @click="removeRegistro(formExpense, idx)" v-if="formExpense.registros.length > 1" class="mt-8 p-3 text-tertiary hover:bg-tertiary/20 rounded-xl transition-all">
                  <TrashIcon class="w-5 h-5" />
                </button>
              </div>

              <div class="pt-4 border-t border-white/10 flex justify-end items-center gap-4">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Monto Total *</label>
                <input :value="'Q' + Number(formExpense.monto || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})" type="text" readonly class="w-40 bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none cursor-not-allowed font-black text-right" />
              </div>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="bg-tertiary border border-tertiary/50 text-white py-4 px-10 rounded-2xl font-bold shadow-xl shadow-tertiary/20 hover:shadow-tertiary/40 disabled:opacity-50 transition-all">
              Registrar Egreso
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- QUICK NEW CONTRACTOR MODAL -->
    <div v-if="showQuickContractorModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeQuickContractorModal"></div>
      <div class="glass-card w-full max-w-lg rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-xl font-bold text-white">Nuevo Contratista</h3>
          <button @click="closeQuickContractorModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitQuickContractor" class="space-y-5">
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombre *</label>
            <input v-model="formQuickContractor.nombre" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50" />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Teléfono</label>
              <input v-model="formQuickContractor.telefono" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Correo Electrónico</label>
              <input v-model="formQuickContractor.correo_electronico" type="email" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-tertiary/50" />
            </div>
          </div>
          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeQuickContractorModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmittingQuickContractor" class="bg-tertiary border border-tertiary/50 text-white py-4 px-10 rounded-2xl font-bold shadow-xl shadow-tertiary/20 hover:shadow-tertiary/40 disabled:opacity-50 transition-all">
              Guardar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  WalletIcon, ArrowTrendingUpIcon, ClockIcon, ArrowTrendingDownIcon,
  BanknotesIcon, ArchiveBoxIcon, UserIcon, PlusIcon, DocumentArrowDownIcon, 
  EllipsisVerticalIcon, ChevronLeftIcon, ChevronRightIcon, DocumentTextIcon, XMarkIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import api from '../../services/api';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const transactions = ref([]);
const projects = ref([]);
const contractors = ref([]);
const loading = ref(true);
const isSubmitting = ref(false);

const dbKpis = ref({
  total_income: 0,
  total_expense: 0,
  net_balance: 0
});

const bankAccounts = ref([]);
const recurrentsList = ref([]);

const fetchRecurrents = async () => {
  try {
    const res = await api.get(`/recurrents`);
    if(res.data.status === 'success') {
      recurrentsList.value = res.data.data;
    }
  } catch(e) {
    console.error("Error fetching recurrents: ", e);
  }
};

const fetchBankAccounts = async () => {
  try {
    const res = await fetch(`${BASE_URL}/bank-accounts`);
    const data = await res.json();
    if(data.status === 'success') {
      bankAccounts.value = data.data.map(acc => `${acc.nombre_banco} - ${acc.numero_cuenta}`);
    }
  } catch(e) {}
};

const fetchTransactions = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/finance/transactions`);
    const data = await res.json();
    if (data.status === 'success') {
      transactions.value = data.data;
      dbKpis.value = data.kpis;
    }
  } catch(e) {}
  loading.value = false;
};

const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`);
    const data = await res.json();
    if(data.status === 'success') projects.value = data.data;
  } catch(e) {}
};

const fetchContractors = async () => {
  try {
    const res = await fetch(`${BASE_URL}/contractors`);
    const data = await res.json();
    if(data.status === 'success') contractors.value = data.data;
  } catch(e) {}
};

onMounted(() => {
  fetchTransactions();
  fetchProjects();
  fetchContractors();
  fetchBankAccounts();
  fetchRecurrents();
});

// Dynamic KPIs
const kpis = computed(() => {
  return [
    { label: "Ingreso Bruto YTD", value: `Q ${dbKpis.value.total_income.toLocaleString('en-US', {minimumFractionDigits: 2})}`, sub: "Volumen Facturado", icon: WalletIcon, color: "primary" },
    { label: "Capital Líquido Actual", value: `Q ${dbKpis.value.net_balance.toLocaleString('en-US', {minimumFractionDigits: 2})}`, sub: "Flujo de Efectivo", icon: ArrowTrendingUpIcon, color: "primary" },
    { label: "Egresos y Pasivos", value: `Q ${dbKpis.value.total_expense.toLocaleString('en-US', {minimumFractionDigits: 2})}`, sub: "Gastos Operativos", icon: ArrowTrendingDownIcon, color: "tertiary" },
  ];
});

const budgetPercent = computed(() => {
  if (dbKpis.value.total_income === 0) return 0;
  return Math.round((dbKpis.value.net_balance / dbKpis.value.total_income) * 100);
});


// Chart Data Computed
const chartData = computed(() => {
  if (transactions.value.length === 0) return [];
  
  // Group by month
  const monthly = {};
  transactions.value.forEach(tx => {
    const d = new Date(tx.fecha_ingreso || tx.fecha_egreso);
    const m = d.toLocaleString('es-ES', { month: 'short' });
    const y = d.getFullYear();
    const key = `${m} ${y}`;
    
    if (!monthly[key]) monthly[key] = { month: m, inc: 0, exp: 0, sort: d.getTime() };
    
    if (tx.transaction_type === 'Ingreso') monthly[key].inc += parseFloat(tx.monto);
    else monthly[key].exp += parseFloat(tx.monto);
  });

  // Sort and get last 6 months
  const sorted = Object.values(monthly).sort((a, b) => a.sort - b.sort).slice(-6);
  
  // Find max for scaling
  let maxVal = 0;
  sorted.forEach(col => {
    if (col.inc > maxVal) maxVal = col.inc;
    if (col.exp > maxVal) maxVal = col.exp;
  });
  
  if (maxVal === 0) maxVal = 1;

  // Calculate percentages (max height is 100%)
  return sorted.map(col => ({
    ...col,
    incPct: (col.inc / maxVal) * 85, // 85% visually so it doesn't touch the very top
    expPct: (col.exp / maxVal) * 85
  }));
});

const chartScale = computed(() => {
  if (chartData.value.length === 0) return ['100K', '80K', '60K', '40K', '20K', '0'];
  let maxVal = 0;
  chartData.value.forEach(col => {
    if (col.inc > maxVal) maxVal = col.inc;
    if (col.exp > maxVal) maxVal = col.exp;
  });
  
  const step = maxVal / 5;
  const scale = [];
  for(let i=5; i>=0; i--) {
    let val = step * i;
    scale.push(val > 1000 ? (val/1000).toFixed(1) + 'K' : val.toFixed(0));
  }
  return scale;
});

const pieChartData = computed(() => {
  const total = dbKpis.value.total_income + dbKpis.value.total_expense + Math.abs(dbKpis.value.net_balance);
  if (total === 0) {
    return [
      { label: 'Ingresos Operativos', val: 0, color: 'bg-primary shadow-[0_0_10px_#6366f1]' },
      { label: 'Egresos Administrativos', val: 0, color: 'bg-tertiary shadow-[0_0_10px_#f43f5e]' },
      { label: 'Reservas de Capital', val: 0, color: 'bg-white/20' },
    ];
  }
  
  const pInc = Math.round((dbKpis.value.total_income / total) * 100);
  const pExp = Math.round((dbKpis.value.total_expense / total) * 100);
  const pNet = Math.round((Math.abs(dbKpis.value.net_balance) / total) * 100);
  
  return [
    { label: 'Ingresos Operativos', val: pInc, color: 'bg-primary shadow-[0_0_10px_#6366f1]' },
    { label: 'Egresos Administrativos', val: pExp, color: 'bg-tertiary shadow-[0_0_10px_#f43f5e]' },
    { label: 'Reservas de Capital', val: pNet, color: 'bg-white/20' },
  ];
});


// Pagination & Filters
const filterType = ref('Todos');
const searchQuery = ref('');

const filteredTransactions = computed(() => {
  let result = transactions.value;
  if (filterType.value !== 'Todos') {
    result = result.filter(tx => tx.transaction_type === filterType.value);
  }
  if (searchQuery.value) {
    const s = searchQuery.value.toLowerCase();
    result = result.filter(tx => 
      (tx.descripcion && tx.descripcion.toLowerCase().includes(s)) ||
      (tx.tipo_ingreso && tx.tipo_ingreso.toLowerCase().includes(s)) ||
      (tx.tipo_egreso && tx.tipo_egreso.toLowerCase().includes(s)) ||
      (tx.pagador && tx.pagador.toLowerCase().includes(s)) ||
      (tx.beneficiario && tx.beneficiario.toLowerCase().includes(s)) ||
      (tx.proyecto_nombre && tx.proyecto_nombre.toLowerCase().includes(s)) ||
      (tx.transaction_type.toLowerCase().includes(s))
    );
  }
  return result;
});

const currentPage = ref(1);
const itemsPerPage = 5;
const totalPages = computed(() => Math.ceil(filteredTransactions.value.length / itemsPerPage) || 1);
const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredTransactions.value.slice(start, start + itemsPerPage);
});

watch([filterType, searchQuery], () => {
  currentPage.value = 1;
});

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const getFileUrl = (path) => {
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

// MODALS
const showModal = ref(false);
const txType = ref('Ingreso');
const attachmentFile = ref(null);

const formIncome = ref({
  proyecto_id: '', tipo_ingreso: '', monto: 0, 
  fecha_ingreso: new Date().toISOString().slice(0,10), cuenta_bancaria: '', 
  numero_cheque: '', pagador: '', descripcion: '',
  registros: []
});

const formExpense = ref({
  proyecto_id: '', contratista_id: '', tipo_egreso: 'Proveedor', monto: 0,
  fecha_egreso: new Date().toISOString().slice(0,10), cuenta_origen: '',
  numero_cheque: '', beneficiario: '', descripcion: '',
  registros: []
});

const openFinanceModal = () => {
  attachmentFile.value = null;
  formIncome.value = {
    proyecto_id: '', tipo_ingreso: '', monto: 0, 
    fecha_ingreso: new Date().toISOString().slice(0,10), cuenta_bancaria: '', 
    numero_cheque: '', pagador: '', descripcion: '',
    registros: [{ descripcion: '', monto: 0, monto_display: '' }]
  };
  formExpense.value = {
    proyecto_id: '', contratista_id: '', tipo_egreso: 'Proveedor', monto: 0,
    fecha_egreso: new Date().toISOString().slice(0,10), cuenta_origen: '',
    numero_cheque: '', beneficiario: '', descripcion: '',
    registros: [{ descripcion: '', monto: 0, monto_display: '' }]
  };
  showModal.value = true;
};

const closeModal = () => showModal.value = false;

const handleFileUpload = (e) => {
  if (e.target.files.length > 0) attachmentFile.value = e.target.files[0];
};

const submitIncome = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formIncome.value).forEach(k => {
    if (k === 'registros') {
      fd.append(k, JSON.stringify(formIncome.value[k]));
    } else if (formIncome.value[k] !== null && formIncome.value[k] !== '') {
      fd.append(k, formIncome.value[k]);
    }
  });
  if(attachmentFile.value) fd.append('comprobante', attachmentFile.value);

  try {
    const res = await fetch(`${BASE_URL}/finance/incomes`, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchTransactions();
      closeModal();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Ingreso Registrado'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

const submitExpense = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formExpense.value).forEach(k => {
    if (k === 'registros') {
      fd.append(k, JSON.stringify(formExpense.value[k]));
    } else if (formExpense.value[k] !== null && formExpense.value[k] !== '') {
      fd.append(k, formExpense.value[k]);
    }
  });
  if(attachmentFile.value) fd.append('comprobante', attachmentFile.value);

  try {
    const res = await fetch(`${BASE_URL}/finance/expenses`, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchTransactions();
      closeModal();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Egreso Registrado'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

const addRegistro = (form) => {
  form.registros.push({ descripcion: '', monto: 0, monto_display: '' });
};

const removeRegistro = (form, index) => {
  form.registros.splice(index, 1);
};

const handleCurrencyInput = (event, record) => {
  let val = event.target.value;
  let num = val.replace(/\D/g, '');
  if (!num) {
    record.monto_display = '';
    record.monto = 0;
    return;
  }
  let floatVal = (parseInt(num, 10) / 100);
  record.monto = floatVal;
  record.monto_display = 'Q' + floatVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

watch(() => formExpense.value.tipo_egreso, (tipo) => {
  if (tipo !== 'Contratista') {
    formExpense.value.contratista_id = '';
  } else {
    formExpense.value.beneficiario = '';
  }
});

const handleContractorSelect = () => {
  const contractor = contractors.value.find(c => c.id === formExpense.value.contratista_id);
  formExpense.value.beneficiario = contractor ? contractor.nombre : '';
};

// QUICK NEW CONTRACTOR MODAL
const showQuickContractorModal = ref(false);
const isSubmittingQuickContractor = ref(false);
const formQuickContractor = ref({ nombre: '', telefono: '', correo_electronico: '' });

const openQuickContractorModal = () => {
  formQuickContractor.value = { nombre: '', telefono: '', correo_electronico: '' };
  showQuickContractorModal.value = true;
};

const closeQuickContractorModal = () => showQuickContractorModal.value = false;

const submitQuickContractor = async () => {
  isSubmittingQuickContractor.value = true;
  const fd = new FormData();
  Object.keys(formQuickContractor.value).forEach(k => {
    if (formQuickContractor.value[k] !== null && formQuickContractor.value[k] !== '') fd.append(k, formQuickContractor.value[k]);
  });

  try {
    const res = await fetch(`${BASE_URL}/contractors`, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchContractors();
      const created = contractors.value.find(c => c.nombre === formQuickContractor.value.nombre);
      if (created) {
        formExpense.value.contratista_id = created.id;
        formExpense.value.beneficiario = created.nombre;
      }
      closeQuickContractorModal();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Contratista Registrado'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmittingQuickContractor.value = false;
};

const handleRecurrentSelection = (event) => {
  const recurrentId = parseInt(event.target.value, 10);
  const rec = recurrentsList.value.find(r => r.id === recurrentId);
  if (rec) {
    formExpense.value.descripcion = rec.concepto + (rec.descripcion ? ' - ' + rec.descripcion : '');
    
    if (formExpense.value.registros.length === 0) {
      addRegistro(formExpense.value);
    }
    const montoFloat = Number(rec.monto) || 0;
    formExpense.value.registros[0].descripcion = rec.concepto;
    formExpense.value.registros[0].monto = montoFloat;
    formExpense.value.registros[0].monto_display = 'Q' + montoFloat.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }
};

const totalIncomeMonto = computed(() => {
  return formIncome.value.registros.reduce((sum, r) => sum + (Number(r.monto) || 0), 0);
});
watch(totalIncomeMonto, (newVal) => formIncome.value.monto = newVal);

const totalExpenseMonto = computed(() => {
  return formExpense.value.registros.reduce((sum, r) => sum + (Number(r.monto) || 0), 0);
});
watch(totalExpenseMonto, (newVal) => formExpense.value.monto = newVal);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
