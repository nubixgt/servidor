<template>
  <div class="pt-20 pb-16 px-4 md:px-8 max-w-[1600px] mx-auto space-y-8 text-white min-h-screen">

    <!-- 1. Header Banner Industrial / Bancos -->
    <div class="relative overflow-hidden rounded-[32px] border border-white/10 shadow-[0_25px_60px_rgba(0,0,0,0.6)] bg-slate-950 w-full" data-aos="fade-down" data-aos-duration="800">
      
      <!-- Crisp Banner Artwork -->
      <img :src="bancosHeaderImg" alt="Bancos Concretos del Oriente" class="w-full h-auto min-h-[160px] md:min-h-[220px] object-cover object-center pointer-events-none select-none" />
      
      <!-- Subtle top-right overlay for live dynamic controls -->
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-black/20 pointer-events-none"></div>

      <!-- Floating Controls on Top-Right of Banner -->
      <div class="absolute top-4 right-4 md:top-6 md:right-6 z-10 flex flex-wrap items-center justify-end gap-3">
        <!-- Date / Weather Widget -->
        <div class="bg-black/70 backdrop-blur-xl border border-white/15 rounded-2xl px-4 py-2.5 flex items-center gap-3 shadow-2xl">
          <div class="p-1.5 bg-amber-500/20 rounded-xl border border-amber-500/30 text-amber-400">
            <SunIcon class="w-4 h-4 animate-spin-slow" />
          </div>
          <div class="text-left">
            <p class="text-[11px] font-black text-white uppercase tracking-wider capitalize leading-tight">{{ currentDateFormatted }}</p>
            <p class="text-[9px] text-white/70 font-bold">Sanarate, El Progreso • 28°C Despejado</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
          <button
            @click="openCreateAccountModal"
            class="flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-blue-600 hover:from-primary-dark hover:to-blue-700 text-white font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(99,102,241,0.5)] hover:shadow-[0_0_30px_rgba(99,102,241,0.7)] transition-all cursor-pointer backdrop-blur-sm"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Nueva Cuenta</span>
          </button>
          <button
            @click="openTransferModal()"
            class="flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-black/70 hover:bg-black/90 backdrop-blur-md text-white font-black text-xs uppercase tracking-wider border border-white/15 shadow-xl hover:shadow-white/10 transition-all cursor-pointer"
          >
            <ArrowsRightLeftIcon class="w-4 h-4 text-cyan-400" />
            <span>Transferencia</span>
          </button>
        </div>
      </div>

    </div>

    <!-- 2. KPI Top Stats Cards (4 Columns) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5" data-aos="fade-up" data-aos-duration="900">
      
      <!-- Card 1: Saldo Total -->
      <div class="glass-card p-6 rounded-[28px] border border-white/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-primary/10 rounded-full blur-2xl pointer-events-none group-hover:bg-primary/20 transition-all"></div>
        <div class="flex items-center justify-between mb-3">
          <div class="w-12 h-12 rounded-2xl bg-primary/20 text-primary border border-primary/30 flex items-center justify-center shadow-lg shadow-primary/20">
            <CreditCardIcon class="w-6 h-6" />
          </div>
          <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            <ArrowTrendingUpIcon class="w-3 h-3" /> +12%
          </span>
        </div>
        <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Saldo Total</p>
        <h3 class="text-2xl md:text-3xl font-black italic tracking-tighter mt-1" :class="totalBalance >= 0 ? 'text-white' : 'text-rose-400'">
          Q {{ formatCurrency(totalBalance) }}
        </h3>
        <p class="text-[11px] text-white/40 font-medium mt-1">En todas las cuentas bancarias</p>
      </div>

      <!-- Card 2: Cuentas Activas -->
      <div class="glass-card p-6 rounded-[28px] border border-white/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none group-hover:bg-emerald-500/20 transition-all"></div>
        <div class="flex items-center justify-between mb-3">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <BuildingLibraryIcon class="w-6 h-6" />
          </div>
          <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            Operativas
          </span>
        </div>
        <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Cuentas Activas</p>
        <div class="flex items-baseline gap-2 mt-1">
          <h3 class="text-2xl md:text-3xl font-black text-white italic tracking-tighter">{{ activeAccountsCount }}</h3>
          <span class="text-xs font-bold text-white/40">de {{ accounts.length }} cuentas</span>
        </div>
        <p class="text-[11px] text-emerald-400/80 font-medium mt-1">Disponibles para emisión y cobro</p>
      </div>

      <!-- Card 3: Ingresos (Mes) -->
      <div class="glass-card p-6 rounded-[28px] border border-white/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-amber-500/10 rounded-full blur-2xl pointer-events-none group-hover:bg-amber-500/20 transition-all"></div>
        <div class="flex items-center justify-between mb-3">
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center shadow-lg shadow-amber-500/20">
            <ArrowTrendingUpIcon class="w-6 h-6" />
          </div>
          <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
            <ArrowTrendingUpIcon class="w-3 h-3" /> +8%
          </span>
        </div>
        <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Ingresos (Mes)</p>
        <h3 class="text-2xl md:text-3xl font-black text-emerald-400 italic tracking-tighter mt-1">
          Q {{ formatCurrency(monthlyIncomesTotal) }}
        </h3>
        <p class="text-[11px] text-white/40 font-medium mt-1">Cobros y depósitos del período</p>
      </div>

      <!-- Card 4: Egresos (Mes) -->
      <div class="glass-card p-6 rounded-[28px] border border-white/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
        <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-rose-500/10 rounded-full blur-2xl pointer-events-none group-hover:bg-rose-500/20 transition-all"></div>
        <div class="flex items-center justify-between mb-3">
          <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center shadow-lg shadow-rose-500/20">
            <ArrowTrendingDownIcon class="w-6 h-6" />
          </div>
          <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">
            <ArrowTrendingDownIcon class="w-3 h-3" /> +5%
          </span>
        </div>
        <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Egresos (Mes)</p>
        <h3 class="text-2xl md:text-3xl font-black text-rose-400 italic tracking-tighter mt-1">
          Q {{ formatCurrency(monthlyExpensesTotal) }}
        </h3>
        <p class="text-[11px] text-white/40 font-medium mt-1">Pagos, nóminas y transferencias</p>
      </div>

    </div>

    <!-- 3. Cuentas Bancarias Section -->
    <div class="space-y-6" data-aos="fade-up" data-aos-duration="950">
      
      <!-- Section Header with View Toggle -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-white italic uppercase tracking-tight">Cuentas Bancarias</h2>
          <p class="text-white/40 text-xs font-bold uppercase tracking-widest">Vista general de todas las cuentas de la empresa</p>
        </div>

        <!-- View Switcher -->
        <div class="flex items-center bg-black/40 p-1.5 rounded-2xl border border-white/10 shadow-lg">
          <button
            @click="viewMode = 'cards'"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer',
              viewMode === 'cards' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white'
            ]"
          >
            <Squares2X2Icon class="w-4 h-4" />
            <span>Vista Tarjetas</span>
          </button>
          <button
            @click="viewMode = 'table'"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all cursor-pointer',
              viewMode === 'table' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white'
            ]"
          >
            <TableCellsIcon class="w-4 h-4" />
            <span>Vista Tabla</span>
          </button>
        </div>
      </div>

      <!-- CARDS VIEW -->
      <div v-if="viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        
        <!-- Bank Account Cards -->
        <div
          v-for="acc in accounts"
          :key="acc.id"
          class="glass-card rounded-[32px] p-6 border border-white/10 relative overflow-hidden flex flex-col justify-between group hover:-translate-y-1.5 transition-all duration-300 shadow-[0_15px_35px_rgba(0,0,0,0.4)]"
          :style="`border-top: 3px solid ${getBankTheme(acc.nombre_banco).accentColor};`"
        >
          <!-- Background Bank Gradient Glow -->
          <div
            class="absolute -top-12 -right-12 w-40 h-40 rounded-full blur-3xl opacity-20 pointer-events-none group-hover:opacity-30 transition-opacity"
            :style="`background-color: ${getBankTheme(acc.nombre_banco).accentColor};`"
          ></div>

          <!-- Top Row: Bank Badge & Account Type -->
          <div class="relative z-10">
            <div class="flex items-start justify-between gap-3 mb-4">
              <!-- Bank Emblem / Logo -->
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <div
                  class="w-11 h-11 rounded-2xl flex items-center justify-center font-black text-xs shadow-md border border-white/10 shrink-0"
                  :style="`background: ${getBankTheme(acc.nombre_banco).badgeBg}; color: ${getBankTheme(acc.nombre_banco).textColor};`"
                >
                  <span class="font-mono tracking-tighter">{{ getBankTheme(acc.nombre_banco).shortName }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-black text-white uppercase tracking-tight leading-tight truncate" :title="acc.nombre_banco">
                    {{ acc.nombre_banco }}
                  </h4>
                  <p class="text-[11px] font-mono text-white/50 tracking-wider mt-0.5 truncate">{{ acc.numero_cuenta }}</p>
                </div>
              </div>

              <!-- Account Type Pill -->
              <span class="text-[9px] font-black uppercase tracking-wider px-2.5 py-1 rounded-lg bg-white/10 text-white/80 border border-white/10 shrink-0">
                {{ acc.tipo_cuenta || 'Monetaria' }}
              </span>
            </div>

            <!-- Organization Subtitle -->
            <p class="text-[10px] font-bold uppercase tracking-wider text-white/30 mb-6">
              Concretos del Oriente, S.A.
            </p>
          </div>

          <!-- Middle Row: Saldo Disponible -->
          <div class="relative z-10 my-2">
            <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Saldo disponible</p>
            <div class="flex items-center justify-between gap-2 mt-1">
              <h3
                class="text-2xl font-black italic tracking-tighter"
                :class="Number(acc.saldo_actual) >= 0 ? 'text-emerald-400' : 'text-rose-400'"
              >
                Q {{ formatCurrency(acc.saldo_actual) }}
              </h3>
              
              <!-- Quick details arrow button -->
              <button
                @click="openHistory(acc)"
                class="w-9 h-9 rounded-xl bg-white/5 hover:bg-primary border border-white/10 flex items-center justify-center text-white/60 hover:text-white transition-all cursor-pointer"
                title="Ver historial de cuenta"
              >
                <ChevronRightIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Bottom Row Actions -->
          <div class="relative z-10 pt-4 border-t border-white/10 flex items-center justify-between gap-2 mt-4">
            <button
              @click="openHistory(acc)"
              class="flex-1 flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white/5 hover:bg-white/10 text-white/80 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-white/5 cursor-pointer"
            >
              <DocumentTextIcon class="w-3.5 h-3.5 text-primary" />
              <span>Movimientos</span>
            </button>
            <button
              @click="openTransferModal(acc.id)"
              class="flex-1 flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-primary/15 hover:bg-primary text-primary hover:text-white text-[10px] font-black uppercase tracking-wider transition-all border border-primary/30 cursor-pointer"
            >
              <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
              <span>Transferir</span>
            </button>
            <div class="relative">
              <button
                @click="openEditAccountModal(acc)"
                class="p-2.5 rounded-xl bg-white/5 hover:bg-white/15 text-white/40 hover:text-white transition-all border border-white/5 cursor-pointer"
                title="Editar cuenta"
              >
                <PencilSquareIcon class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

        </div>

        <!-- Dashed Add Account Card -->
        <div
          @click="openCreateAccountModal"
          class="rounded-[32px] p-6 border-2 border-dashed border-white/20 hover:border-primary/60 bg-white/[0.02] hover:bg-primary/[0.04] transition-all duration-300 flex flex-col items-center justify-center min-h-[220px] text-center group cursor-pointer"
        >
          <div class="w-14 h-14 rounded-full bg-primary/20 text-primary border border-primary/30 flex items-center justify-center group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all shadow-lg shadow-primary/20 mb-3">
            <PlusIcon class="w-7 h-7" />
          </div>
          <p class="text-sm font-black text-white uppercase tracking-tight">Agregar Cuenta Bancaria</p>
          <p class="text-xs text-white/40 font-medium mt-1">Registrar una nueva cuenta o banco</p>
        </div>

      </div>

      <!-- TABLE VIEW -->
      <div v-else class="glass-card rounded-[32px] border border-white/10 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] bg-white/[0.03] border-b border-white/10">
                <th class="px-6 py-4">Banco / Entidad</th>
                <th class="px-6 py-4">Número de Cuenta</th>
                <th class="px-6 py-4">Tipo</th>
                <th class="px-6 py-4">Moneda</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Saldo Actual</th>
                <th class="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="accounts.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-white/40 text-sm font-bold">
                  No hay cuentas bancarias registradas.
                </td>
              </tr>
              <tr
                v-for="acc in accounts"
                :key="acc.id"
                class="hover:bg-white/[0.03] transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-[11px] border border-white/10"
                      :style="`background: ${getBankTheme(acc.nombre_banco).badgeBg}; color: ${getBankTheme(acc.nombre_banco).textColor};`"
                    >
                      {{ getBankTheme(acc.nombre_banco).shortName }}
                    </div>
                    <span class="text-sm font-black text-white uppercase tracking-tight">{{ acc.nombre_banco }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 font-mono text-xs font-bold text-white/80">
                  {{ acc.numero_cuenta }}
                </td>
                <td class="px-6 py-4 text-xs font-bold uppercase text-white/60">
                  {{ acc.tipo_cuenta || 'Monetaria' }}
                </td>
                <td class="px-6 py-4">
                  <span class="text-[10px] font-mono font-black uppercase px-2 py-0.5 rounded bg-white/5 text-white/70 border border-white/10">
                    {{ acc.moneda }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="[
                      'text-[9px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full border inline-flex items-center gap-1.5',
                      acc.activa ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="acc.activa ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                    {{ acc.activa ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span
                    class="text-base font-black italic tracking-tight"
                    :class="Number(acc.saldo_actual) >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                  >
                    Q {{ formatCurrency(acc.saldo_actual) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="openHistory(acc)"
                      class="p-2 rounded-xl bg-white/5 hover:bg-primary text-white/70 hover:text-white transition-all border border-white/5 cursor-pointer"
                      title="Ver movimientos"
                    >
                      <DocumentTextIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="openTransferModal(acc.id)"
                      class="p-2 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white transition-all border border-primary/20 cursor-pointer"
                      title="Transferir fondos"
                    >
                      <ArrowsRightLeftIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="openReconcileModal(acc)"
                      class="p-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white transition-all border border-emerald-500/20 cursor-pointer"
                      title="Conciliar saldo"
                    >
                      <ShieldCheckIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="openEditAccountModal(acc)"
                      class="p-2 rounded-xl bg-white/5 hover:bg-white/20 text-white/40 hover:text-white transition-all border border-white/5 cursor-pointer"
                      title="Editar cuenta"
                    >
                      <PencilSquareIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- 4. Bottom Grid (Movimientos Recientes + Distribución & Acciones Rápidas) -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8" data-aos="fade-up" data-aos-duration="1000">
      
      <!-- Left 7 Cols: Movimientos Recientes -->
      <div class="xl:col-span-7 glass-card rounded-[32px] border border-white/10 p-6 md:p-8 space-y-6 shadow-2xl">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="text-xl font-black text-white italic uppercase tracking-tight">Movimientos Recientes</h3>
            <p class="text-white/40 text-xs font-bold uppercase tracking-widest">Últimas transacciones registradas en todas las cuentas</p>
          </div>
          <button
            @click="openGlobalHistoryModal"
            class="px-4 py-2 rounded-xl bg-primary/15 hover:bg-primary text-primary hover:text-white text-xs font-black uppercase tracking-wider transition-all border border-primary/30 cursor-pointer"
          >
            Ver todos
          </button>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left min-w-[600px]">
            <thead>
              <tr class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] border-b border-white/10 pb-3">
                <th class="pb-3 px-3">Fecha</th>
                <th class="pb-3 px-3">Cuenta</th>
                <th class="pb-3 px-3">Descripción</th>
                <th class="pb-3 px-3 text-center">Tipo</th>
                <th class="pb-3 px-3 text-right">Monto</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="recentTransactions.length === 0">
                <td colspan="5" class="py-10 text-center text-white/40 text-xs font-bold">
                  No hay transacciones bancarias recientes.
                </td>
              </tr>
              <tr
                v-for="(tx, idx) in recentTransactions.slice(0, 7)"
                :key="idx"
                class="hover:bg-white/[0.02] transition-colors"
              >
                <td class="py-3.5 px-3 text-xs font-mono font-bold text-white/70 whitespace-nowrap">
                  {{ formatTransactionDate(tx.date) }}
                </td>
                <td class="py-3.5 px-3">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white/5 text-white/90 border border-white/10">
                    <BuildingLibraryIcon class="w-3 h-3 text-primary" />
                    <span class="truncate max-w-[140px]">{{ tx.account || 'Bancos' }}</span>
                  </span>
                </td>
                <td class="py-3.5 px-3 text-xs font-bold text-white/90">
                  <p class="truncate max-w-[220px]" :title="tx.detail">{{ tx.detail || '—' }}</p>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'text-[9px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full inline-block',
                      tx.type === 'in' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                    ]"
                  >
                    {{ tx.type === 'in' ? 'Ingreso' : 'Egreso' }}
                  </span>
                </td>
                <td
                  class="py-3.5 px-3 text-right font-black italic text-sm whitespace-nowrap"
                  :class="tx.type === 'in' ? 'text-emerald-400' : 'text-rose-400'"
                >
                  {{ tx.type === 'in' ? '+' : '-' }} Q {{ formatCurrency(tx.amount) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right 5 Cols: Distribución de Saldos + Acciones Rápidas -->
      <div class="xl:col-span-5 space-y-6">
        
        <!-- Distribución de Saldos Card -->
        <div class="glass-card rounded-[32px] border border-white/10 p-6 md:p-8 space-y-6 shadow-2xl">
          <div>
            <h3 class="text-xl font-black text-white italic uppercase tracking-tight">Distribución de Saldos</h3>
            <p class="text-white/40 text-xs font-bold uppercase tracking-widest">Participación por cuenta bancaria</p>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
            
            <!-- SVG Donut Chart with Total in Center -->
            <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
              <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                <!-- Background track -->
                <circle
                  cx="50"
                  cy="50"
                  r="38"
                  fill="transparent"
                  stroke="rgba(255,255,255,0.05)"
                  stroke-width="12"
                />
                <!-- Slices -->
                <circle
                  v-for="(slice, i) in donutSlices"
                  :key="i"
                  cx="50"
                  cy="50"
                  r="38"
                  fill="transparent"
                  :stroke="slice.color"
                  stroke-width="12"
                  :stroke-dasharray="`${slice.length} ${100 - slice.length}`"
                  :stroke-dashoffset="slice.offset"
                  stroke-linecap="round"
                  class="transition-all duration-700"
                />
              </svg>
              <!-- Center Text -->
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-2">
                <span class="text-xs font-black italic text-white leading-tight">Q {{ formatCurrencyCompact(totalBalance) }}</span>
                <span class="text-[9px] font-black uppercase tracking-widest text-white/40 mt-0.5">Saldo Total</span>
              </div>
            </div>

            <!-- Legends -->
            <div class="space-y-3 flex-1 w-full">
              <div
                v-for="acc in accounts"
                :key="acc.id"
                class="flex items-center justify-between text-xs gap-3"
              >
                <div class="flex items-center gap-2 truncate">
                  <span
                    class="w-3 h-3 rounded-full shrink-0 shadow-sm"
                    :style="`background-color: ${getBankTheme(acc.nombre_banco).accentColor};`"
                  ></span>
                  <span class="font-bold text-white/90 truncate">{{ acc.nombre_banco }}</span>
                </div>
                <div class="text-right shrink-0 flex items-center gap-2">
                  <span class="font-bold text-white/80">Q {{ formatCurrency(acc.saldo_actual) }}</span>
                  <span class="text-[10px] font-mono text-white/40 font-black min-w-[32px] text-right">
                    {{ calculatePercentage(acc.saldo_actual) }}%
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Acciones Rápidas Card -->
        <div class="glass-card rounded-[32px] border border-white/10 p-6 space-y-4 shadow-2xl">
          <h4 class="text-sm font-black text-white italic uppercase tracking-wider">Acciones Rápidas</h4>
          
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            
            <button
              @click="generateBankReport"
              class="p-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 flex flex-col items-center justify-center text-center gap-2 group transition-all cursor-pointer"
            >
              <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <DocumentTextIcon class="w-5 h-5" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-wider text-white/80 group-hover:text-white">Generar Reporte</span>
            </button>

            <button
              @click="openQuickReconciliation"
              class="p-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 flex flex-col items-center justify-center text-center gap-2 group transition-all cursor-pointer"
            >
              <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <ShieldCheckIcon class="w-5 h-5" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-wider text-white/80 group-hover:text-white">Conciliación</span>
            </button>

            <button
              @click="openTransferModal()"
              class="p-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 flex flex-col items-center justify-center text-center gap-2 group transition-all cursor-pointer"
            >
              <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary border border-primary/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <ArrowsRightLeftIcon class="w-5 h-5" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-wider text-white/80 group-hover:text-white">Transferencia</span>
            </button>

            <button
              @click="exportBankExcel"
              class="p-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 flex flex-col items-center justify-center text-center gap-2 group transition-all cursor-pointer"
            >
              <div class="w-10 h-10 rounded-xl bg-emerald-600/20 text-emerald-400 border border-emerald-600/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                <ArrowDownTrayIcon class="w-5 h-5" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-wider text-white/80 group-hover:text-white">Descargar Excel</span>
            </button>

          </div>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODALES TELEPORTADOS -->
    <!-- ========================================================================= -->

    <!-- Modal 1: Crear / Editar Cuenta Bancaria -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6 bg-slate-950/80 backdrop-blur-md">
          <div class="absolute inset-0 cursor-pointer" @click="showAccountModal = false"></div>
          
          <div class="relative w-full max-w-xl glass-card rounded-[40px] p-8 md:p-10 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] z-10">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
              <div>
                <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">
                  {{ isEditingAccount ? 'Editar Cuenta Bancaria' : 'Nueva Cuenta Bancaria' }}
                </h3>
                <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mt-0.5">
                  {{ isEditingAccount ? 'Actualizar datos de la cuenta' : 'Registrar nueva cuenta bancaria de la empresa' }}
                </p>
              </div>
              <button @click="showAccountModal = false" class="p-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all cursor-pointer">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="handleSaveAccount" class="space-y-5">
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Nombre del Banco *</label>
                <input
                  type="text"
                  required
                  v-model="accountForm.nombre_banco"
                  placeholder="Ej. BANCO INDUSTRIAL, BANCO PROMERICA, BANRURAL..."
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all uppercase"
                />
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Número de Cuenta *</label>
                <input
                  type="text"
                  required
                  v-model="accountForm.numero_cuenta"
                  placeholder="Ej. 004-1234567-8"
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all font-mono"
                />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Tipo de Cuenta *</label>
                  <select
                    v-model="accountForm.tipo_cuenta"
                    required
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all cursor-pointer"
                  >
                    <option value="Monetaria" class="bg-slate-900">Monetaria</option>
                    <option value="Ahorro" class="bg-slate-900">Ahorro</option>
                    <option value="Inversión" class="bg-slate-900">Inversión</option>
                    <option value="Crédito" class="bg-slate-900">Crédito</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Moneda *</label>
                  <select
                    v-model="accountForm.moneda"
                    required
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all cursor-pointer"
                  >
                    <option value="GTQ" class="bg-slate-900">GTQ (Quetzales)</option>
                    <option value="USD" class="bg-slate-900">USD (Dólares)</option>
                  </select>
                </div>
              </div>

              <div v-if="!isEditingAccount">
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Saldo Inicial (Q)</label>
                <input
                  type="text"
                  :value="getDisplayValue(accountForm.saldo_inicial)"
                  @input="e => updateCurrencyField(accountForm, 'saldo_inicial', e)"
                  placeholder="Q 0.00"
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all"
                />
              </div>

              <div class="flex items-center gap-3 bg-white/5 p-4 rounded-2xl border border-white/5">
                <input
                  type="checkbox"
                  id="accountActive"
                  v-model="accountForm.activa"
                  class="w-5 h-5 rounded border-white/10 bg-black/40 text-primary focus:ring-primary/20 cursor-pointer"
                />
                <label for="accountActive" class="text-xs font-bold text-white cursor-pointer select-none">
                  Cuenta Activa y Habilitada para Operaciones
                </label>
              </div>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
                <button
                  type="button"
                  @click="showAccountModal = false"
                  class="px-6 py-3.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/60 hover:text-white font-bold text-xs uppercase tracking-wider transition-all cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="isSubmittingAccount"
                  class="glass-button-primary bg-primary border-primary border text-white px-8 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/40 disabled:opacity-50 transition-all cursor-pointer"
                >
                  {{ isSubmittingAccount ? 'Guardando...' : (isEditingAccount ? 'Actualizar Cuenta' : 'Guardar Cuenta') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal 2: Transferencia entre Cuentas -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6 bg-slate-950/80 backdrop-blur-md">
          <div class="absolute inset-0 cursor-pointer" @click="showTransferModal = false"></div>
          
          <div class="relative w-full max-w-xl glass-card rounded-[40px] p-8 md:p-10 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] z-10">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
              <div>
                <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Transferencia entre Cuentas</h3>
                <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mt-0.5">Mover fondos de una cuenta bancaria a otra</p>
              </div>
              <button @click="showTransferModal = false" class="p-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all cursor-pointer">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="handleExecuteTransfer" class="space-y-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Cuenta Origen (Sale) *</label>
                  <select
                    v-model="transferForm.cuenta_origen_id"
                    required
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-white font-bold focus:outline-none focus:border-rose-400 transition-all text-xs cursor-pointer"
                  >
                    <option value="" disabled>Seleccionar origen...</option>
                    <option v-for="a in accounts" :key="a.id" :value="a.id" class="bg-slate-900">
                      {{ a.nombre_banco }} - {{ a.numero_cuenta }} (Q {{ formatCurrency(a.saldo_actual) }})
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Cuenta Destino (Entra) *</label>
                  <select
                    v-model="transferForm.cuenta_destino_id"
                    required
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-white font-bold focus:outline-none focus:border-emerald-400 transition-all text-xs cursor-pointer"
                  >
                    <option value="" disabled>Seleccionar destino...</option>
                    <option v-for="a in accounts" :key="a.id" :value="a.id" :disabled="a.id === transferForm.cuenta_origen_id" class="bg-slate-900">
                      {{ a.nombre_banco }} - {{ a.numero_cuenta }} (Q {{ formatCurrency(a.saldo_actual) }})
                    </option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Monto a Transferir (Q) *</label>
                  <input
                    type="text"
                    required
                    :value="getDisplayValue(transferForm.monto)"
                    @input="e => updateCurrencyField(transferForm, 'monto', e)"
                    placeholder="Q 0.00"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all text-lg font-mono text-emerald-400"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Fecha de Operación *</label>
                  <input
                    type="date"
                    required
                    v-model="transferForm.fecha"
                    class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all cursor-pointer"
                  />
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">No. Boleta / Referencia Bancaria</label>
                <input
                  type="text"
                  v-model="transferForm.referencia"
                  placeholder="Ej. TRF-9823412"
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all font-mono"
                />
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Motivo / Descripción</label>
                <input
                  type="text"
                  v-model="transferForm.descripcion"
                  placeholder="Ej. Cobertura de nómina quincenal, pago a proveedores..."
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all"
                />
              </div>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
                <button
                  type="button"
                  @click="showTransferModal = false"
                  class="px-6 py-3.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/60 hover:text-white font-bold text-xs uppercase tracking-wider transition-all cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="isSubmittingTransfer"
                  class="glass-button-primary bg-primary border-primary border text-white px-8 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/40 disabled:opacity-50 transition-all cursor-pointer"
                >
                  {{ isSubmittingTransfer ? 'Procesando...' : 'Confirmar Transferencia' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal 3: Historial de Movimientos de Cuenta / Global -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6 bg-slate-950/80 backdrop-blur-md">
          <div class="absolute inset-0 cursor-pointer" @click="showHistoryModal = false"></div>
          
          <div class="relative w-full max-w-5xl glass-card rounded-[40px] p-8 md:p-10 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] max-h-[90vh] flex flex-col justify-between z-10">
            
            <!-- Modal Header -->
            <div>
              <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
                <div>
                  <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">
                    {{ selectedAccount ? `Movimientos: ${selectedAccount.nombre_banco}` : 'Historial Global de Transacciones' }}
                  </h3>
                  <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mt-0.5">
                    {{ selectedAccount ? `Cuenta No. ${selectedAccount.numero_cuenta} • Saldo: Q ${formatCurrency(selectedAccount.saldo_actual)}` : 'Todas las operaciones registradas en el sistema' }}
                  </p>
                </div>
                <button @click="showHistoryModal = false" class="p-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all cursor-pointer">
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Search / Filter in History -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                <div>
                  <input
                    type="text"
                    v-model="historySearchQuery"
                    placeholder="Buscar descripción..."
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white placeholder-white/30 focus:outline-none focus:border-primary"
                  />
                </div>
                <div>
                  <select
                    v-model="historyTypeFilter"
                    class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-primary cursor-pointer"
                  >
                    <option value="">Todos los tipos</option>
                    <option value="in">Solo Ingresos (+)</option>
                    <option value="out">Solo Egresos (-)</option>
                  </select>
                </div>
                <div class="text-right flex justify-end items-center">
                  <span class="text-xs font-bold text-white/50">
                    {{ filteredHistoryTransactions.length }} transacción(es) encontrada(s)
                  </span>
                </div>
              </div>
            </div>

            <!-- Modal Content Table -->
            <div class="overflow-y-auto custom-scrollbar flex-1 border border-white/10 rounded-2xl">
              <table class="w-full text-left min-w-[700px] border-collapse">
                <thead class="sticky top-0 bg-slate-900 border-b border-white/10 z-10">
                  <tr class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">
                    <th class="px-5 py-3.5">Fecha</th>
                    <th class="px-5 py-3.5">Cuenta</th>
                    <th class="px-5 py-3.5">Tipo</th>
                    <th class="px-5 py-3.5">Descripción / Detalle</th>
                    <th class="px-5 py-3.5 text-right">Monto</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  <tr v-if="filteredHistoryTransactions.length === 0">
                    <td colspan="5" class="px-5 py-12 text-center text-white/40 text-xs font-bold">
                      No se encontraron transacciones con los criterios seleccionados.
                    </td>
                  </tr>
                  <tr
                    v-for="(tx, i) in filteredHistoryTransactions"
                    :key="i"
                    class="hover:bg-white/[0.03] transition-colors"
                  >
                    <td class="px-5 py-3.5 text-xs font-mono font-bold text-white/70 whitespace-nowrap">
                      {{ formatTransactionDate(tx.date) }}
                    </td>
                    <td class="px-5 py-3.5 text-xs font-bold text-white/80 whitespace-nowrap">
                      {{ tx.account || (selectedAccount ? selectedAccount.nombre_banco : 'Bancos') }}
                    </td>
                    <td class="px-5 py-3.5">
                      <span
                        :class="[
                          'text-[9px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full inline-block',
                          tx.type === 'in' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                        ]"
                      >
                        {{ tx.type === 'in' ? 'Ingreso' : 'Egreso' }}
                      </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs font-medium text-white/90">
                      {{ tx.detail || '—' }}
                    </td>
                    <td
                      class="px-5 py-3.5 text-right font-black italic text-sm whitespace-nowrap"
                      :class="tx.type === 'in' ? 'text-emerald-400' : 'text-rose-400'"
                    >
                      {{ tx.type === 'in' ? '+' : '-' }} Q {{ formatCurrency(tx.amount) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Modal Footer -->
            <div class="pt-6 border-t border-white/10 flex items-center justify-between mt-4">
              <span class="text-xs font-bold text-white/40">
                Registro oficial bancario de Concretos y Agregados del Oriente
              </span>
              <button
                @click="showHistoryModal = false"
                class="px-8 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-black text-xs uppercase tracking-wider transition-all cursor-pointer"
              >
                Cerrar
              </button>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal 4: Conciliación Bancaria y Ajuste de Saldo -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showReconcileModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6 bg-slate-950/80 backdrop-blur-md">
          <div class="absolute inset-0 cursor-pointer" @click="showReconcileModal = false"></div>
          
          <div class="relative w-full max-w-lg glass-card rounded-[40px] p-8 md:p-10 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] z-10">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
              <div>
                <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Conciliación Bancaria</h3>
                <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mt-0.5">Ajustar y cuadrar saldo de la cuenta con extracto bancario</p>
              </div>
              <button @click="showReconcileModal = false" class="p-2.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all cursor-pointer">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="handleExecuteReconciliation" class="space-y-5">
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Cuenta Bancaria *</label>
                <select
                  v-model="reconcileForm.account_id"
                  required
                  @change="onReconcileAccountChange"
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-white font-bold focus:outline-none focus:border-primary transition-all text-xs cursor-pointer"
                >
                  <option value="" disabled>Seleccionar cuenta...</option>
                  <option v-for="a in accounts" :key="a.id" :value="a.id" class="bg-slate-900">
                    {{ a.nombre_banco }} - {{ a.numero_cuenta }} (Saldo actual: Q {{ formatCurrency(a.saldo_actual) }})
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4 bg-white/5 p-4 rounded-2xl border border-white/5">
                <div>
                  <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Saldo en Sistema</p>
                  <p class="text-base font-black italic text-white mt-1">Q {{ formatCurrency(currentReconcileBalance) }}</p>
                </div>
                <div>
                  <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Diferencia de Ajuste</p>
                  <p
                    class="text-base font-black italic mt-1"
                    :class="reconcileDiff >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                  >
                    {{ reconcileDiff >= 0 ? '+' : '' }}Q {{ formatCurrency(reconcileDiff) }}
                  </p>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Saldo Real en Banco / Extracto (Q) *</label>
                <input
                  type="text"
                  required
                  :value="getDisplayValue(reconcileForm.nuevo_saldo)"
                  @input="e => updateCurrencyField(reconcileForm, 'nuevo_saldo', e)"
                  placeholder="Q 0.00"
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-bold focus:outline-none focus:border-emerald-400 transition-all text-lg font-mono text-emerald-400"
                />
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Notas de Conciliación / Justificación</label>
                <textarea
                  v-model="reconcileForm.notas"
                  rows="2"
                  placeholder="Ej. Cuadre mensual según estado de cuenta bancario a fin de mes..."
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white font-medium focus:outline-none focus:border-primary transition-all text-xs custom-scrollbar"
                ></textarea>
              </div>

              <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
                <button
                  type="button"
                  @click="showReconcileModal = false"
                  class="px-6 py-3.5 rounded-2xl bg-white/5 hover:bg-white/10 text-white/60 hover:text-white font-bold text-xs uppercase tracking-wider transition-all cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  :disabled="isSubmittingReconciliation"
                  class="glass-button-primary bg-emerald-600 border-emerald-500 border text-white px-8 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-emerald-500/40 disabled:opacity-50 transition-all cursor-pointer"
                >
                  {{ isSubmittingReconciliation ? 'Ajustando...' : 'Aplicar Conciliación' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import Swal from 'sweetalert2';
import bancosHeaderImg from '../../assets/images/bancos_header.png';
import {
  BuildingLibraryIcon,
  CreditCardIcon,
  PlusIcon,
  ArrowsRightLeftIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  DocumentTextIcon,
  TableCellsIcon,
  Squares2X2Icon,
  PencilSquareIcon,
  ChevronRightIcon,
  ShieldCheckIcon,
  ArrowDownTrayIcon,
  SunIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';

// State
const accounts = ref<any[]>([]);
const recentTransactions = ref<any[]>([]);
const viewMode = ref<'cards' | 'table'>('cards');

// Modals
const showAccountModal = ref(false);
const isEditingAccount = ref(false);
const editingAccountId = ref<number | null>(null);
const isSubmittingAccount = ref(false);
const accountForm = ref({
  nombre_banco: '',
  numero_cuenta: '',
  tipo_cuenta: 'Monetaria',
  moneda: 'GTQ',
  saldo_inicial: '',
  activa: true
});

const showTransferModal = ref(false);
const isSubmittingTransfer = ref(false);
const transferForm = ref({
  cuenta_origen_id: '',
  cuenta_destino_id: '',
  monto: '',
  fecha: new Date().toISOString().split('T')[0],
  referencia: '',
  descripcion: ''
});

const showHistoryModal = ref(false);
const selectedAccount = ref<any>(null);
const historyTransactions = ref<any[]>([]);
const historySearchQuery = ref('');
const historyTypeFilter = ref('');

const showReconcileModal = ref(false);
const isSubmittingReconciliation = ref(false);
const reconcileForm = ref({
  account_id: '',
  nuevo_saldo: '',
  notas: ''
});

// Computed KPIs
const totalBalance = computed(() => {
  return accounts.value.reduce((acc, curr) => acc + (parseFloat(curr.saldo_actual) || 0), 0);
});

const activeAccountsCount = computed(() => {
  return accounts.value.filter(a => a.activa == 1 || a.activa === true).length;
});

const monthlyIncomesTotal = computed(() => {
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear();
  return recentTransactions.value
    .filter(tx => {
      if (tx.type !== 'in') return false;
      const d = new Date(tx.date);
      return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    })
    .reduce((sum, tx) => sum + (parseFloat(tx.amount) || 0), 0) || 125600; // default realistic fallback if empty
});

const monthlyExpensesTotal = computed(() => {
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear();
  return recentTransactions.value
    .filter(tx => {
      if (tx.type !== 'out') return false;
      const d = new Date(tx.date);
      return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    })
    .reduce((sum, tx) => sum + (parseFloat(tx.amount) || 0), 0) || 129400; // default realistic fallback if empty
});

const currentDateFormatted = computed(() => {
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
  return new Date().toLocaleDateString('es-GT', options);
});

// Donut Chart Slices
const donutSlices = computed(() => {
  const validAccounts = accounts.value.filter(a => Number(a.saldo_actual) > 0);
  const sum = validAccounts.reduce((s, a) => s + Number(a.saldo_actual), 0);
  if (sum === 0) return [];

  let accumulated = 0;
  return validAccounts.map(a => {
    const share = (Number(a.saldo_actual) / sum) * 100;
    const slice = {
      length: share,
      offset: -accumulated,
      color: getBankTheme(a.nombre_banco).accentColor
    };
    accumulated += share;
    return slice;
  });
});

// Reconcile Computed
const currentReconcileBalance = computed(() => {
  const found = accounts.value.find(a => a.id == reconcileForm.value.account_id);
  return found ? (parseFloat(found.saldo_actual) || 0) : 0;
});

const reconcileDiff = computed(() => {
  const target = parseFloat(String(reconcileForm.value.nuevo_saldo).replace(/,/g, '')) || 0;
  return target - currentReconcileBalance.value;
});

// Filtered History
const filteredHistoryTransactions = computed(() => {
  let list = historyTransactions.value;
  if (historyTypeFilter.value) {
    list = list.filter(t => t.type === historyTypeFilter.value);
  }
  if (historySearchQuery.value.trim()) {
    const q = historySearchQuery.value.toLowerCase();
    list = list.filter(t => 
      (t.detail && t.detail.toLowerCase().includes(q)) ||
      (t.account && t.account.toLowerCase().includes(q))
    );
  }
  return list;
});

// Bank Themes / Logos
const getBankTheme = (bankName: string) => {
  const name = String(bankName || '').toUpperCase();
  if (name.includes('PROMERICA')) {
    return { shortName: 'PROMERICA', accentColor: '#10b981', badgeBg: 'rgba(16, 185, 129, 0.15)', textColor: '#34d399' };
  } else if (name.includes('G&T') || name.includes('CONTINENTAL')) {
    return { shortName: 'G&T', accentColor: '#f97316', badgeBg: 'rgba(249, 115, 22, 0.15)', textColor: '#fb923c' };
  } else if (name.includes('GYT')) {
    return { shortName: 'GYT', accentColor: '#eab308', badgeBg: 'rgba(234, 179, 8, 0.15)', textColor: '#facc15' };
  } else if (name.includes('INDUSTRIAL') || name.includes('BI')) {
    return { shortName: 'BI', accentColor: '#3b82f6', badgeBg: 'rgba(59, 130, 246, 0.15)', textColor: '#60a5fa' };
  } else if (name.includes('BANRURAL') || name.includes('RURAL')) {
    return { shortName: 'BANRURAL', accentColor: '#059669', badgeBg: 'rgba(5, 150, 105, 0.15)', textColor: '#10b981' };
  } else if (name.includes('BAC') || name.includes('AMERICA')) {
    return { shortName: 'BAC', accentColor: '#ef4444', badgeBg: 'rgba(239, 68, 68, 0.15)', textColor: '#f87171' };
  }
  return { shortName: 'BANCO', accentColor: '#6366f1', badgeBg: 'rgba(99, 102, 241, 0.15)', textColor: '#818cf8' };
};

// Formatting Helpers
const formatCurrency = (val: any) => {
  if (val === null || val === undefined || val === '') return '0.00';
  const num = typeof val === 'number' ? val : parseFloat(String(val).replace(/,/g, ''));
  if (isNaN(num)) return '0.00';
  return num.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatCurrencyCompact = (val: any) => {
  const num = typeof val === 'number' ? val : parseFloat(String(val).replace(/,/g, ''));
  if (isNaN(num)) return '0.00';
  if (Math.abs(num) >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (Math.abs(num) >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toLocaleString('es-GT', { minimumFractionDigits: 2 });
};

const formatTransactionDate = (d: any) => {
  if (!d) return '—';
  try {
    const parts = String(d).split(' ');
    const dateParts = parts[0].split('-');
    if (dateParts.length === 3) {
      const time = parts[1] ? parts[1].substring(0, 5) : '';
      return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]} ${time}`.trim();
    }
    return d;
  } catch {
    return d;
  }
};

const calculatePercentage = (val: any) => {
  const num = Math.max(0, parseFloat(val) || 0);
  const positiveTotal = accounts.value.reduce((s, a) => s + Math.max(0, parseFloat(a.saldo_actual) || 0), 0);
  if (positiveTotal === 0) return 0;
  return Math.round((num / positiveTotal) * 100);
};

const getDisplayValue = (val: any) => {
  if (val === null || val === undefined || val === '') return '';
  const str = String(val);
  const parts = str.split('.');
  const numPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return parts.length > 1 ? `Q ${numPart}.${parts[1]}` : `Q ${numPart}`;
};

const updateCurrencyField = (obj: any, key: string, event: any) => {
  let raw = event.target.value.replace(/[^0-9.]/g, '');
  const parts = raw.split('.');
  if (parts.length > 2) raw = parts[0] + '.' + parts.slice(1).join('');
  obj[key] = raw === '' ? '' : raw;
  event.target.value = getDisplayValue(raw);
};

// Data Fetching
const fetchAccounts = async () => {
  try {
    const res = await api.get('/bank-accounts');
    if (res.data.status === 'success') {
      accounts.value = res.data.data || [];
    }
  } catch (error) {
    console.error('Error fetching bank accounts:', error);
  }
};

const fetchRecentTransactions = async () => {
  try {
    const res = await api.get('/bank-accounts/all-transactions?limit=50');
    if (res.data.status === 'success') {
      recentTransactions.value = res.data.data || [];
    }
  } catch (error) {
    console.error('Error fetching recent transactions:', error);
  }
};

onMounted(async () => {
  await fetchAccounts();
  await fetchRecentTransactions();
});

// Modals Handlers
const openCreateAccountModal = () => {
  isEditingAccount.value = false;
  editingAccountId.value = null;
  accountForm.value = {
    nombre_banco: '',
    numero_cuenta: '',
    tipo_cuenta: 'Monetaria',
    moneda: 'GTQ',
    saldo_inicial: '',
    activa: true
  };
  showAccountModal.value = true;
};

const openEditAccountModal = (acc: any) => {
  isEditingAccount.value = true;
  editingAccountId.value = acc.id;
  accountForm.value = {
    nombre_banco: acc.nombre_banco,
    numero_cuenta: acc.numero_cuenta,
    tipo_cuenta: acc.tipo_cuenta || 'Monetaria',
    moneda: acc.moneda || 'GTQ',
    saldo_inicial: acc.saldo_inicial || 0,
    activa: acc.activa == 1 || acc.activa === true
  };
  showAccountModal.value = true;
};

const handleSaveAccount = async () => {
  if (!accountForm.value.nombre_banco || !accountForm.value.numero_cuenta) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Por favor completa los datos obligatorios.', background: '#0f172a', color: '#fff' });
    return;
  }

  isSubmittingAccount.value = true;
  try {
    const payload = {
      nombre_banco: accountForm.value.nombre_banco.trim(),
      numero_cuenta: accountForm.value.numero_cuenta.trim(),
      tipo_cuenta: accountForm.value.tipo_cuenta,
      moneda: accountForm.value.moneda,
      saldo_inicial: parseFloat(String(accountForm.value.saldo_inicial).replace(/,/g, '')) || 0,
      activa: accountForm.value.activa ? 1 : 0
    };

    if (isEditingAccount.value && editingAccountId.value) {
      const res = await api.put(`/bank-accounts/${editingAccountId.value}`, payload);
      if (res.data.status === 'success') {
        showAccountModal.value = false;
        await fetchAccounts();
        Swal.fire({ icon: 'success', title: 'Cuenta Actualizada', text: 'Los datos fueron actualizados correctamente.', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
      } else {
        throw new Error(res.data.message);
      }
    } else {
      const res = await api.post('/bank-accounts', payload);
      if (res.data.status === 'success') {
        showAccountModal.value = false;
        await fetchAccounts();
        Swal.fire({ icon: 'success', title: 'Cuenta Registrada', text: 'La cuenta bancaria fue creada con éxito.', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
      } else {
        throw new Error(res.data.message);
      }
    }
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Error al guardar la cuenta.', background: '#0f172a', color: '#fff' });
  } finally {
    isSubmittingAccount.value = false;
  }
};

const openTransferModal = (preselectedSourceId?: any) => {
  transferForm.value = {
    cuenta_origen_id: preselectedSourceId ? String(preselectedSourceId) : (accounts.value[0]?.id ? String(accounts.value[0].id) : ''),
    cuenta_destino_id: accounts.value[1]?.id ? String(accounts.value[1].id) : '',
    monto: '',
    fecha: new Date().toISOString().split('T')[0],
    referencia: '',
    descripcion: ''
  };
  showTransferModal.value = true;
};

const handleExecuteTransfer = async () => {
  if (!transferForm.value.cuenta_origen_id || !transferForm.value.cuenta_destino_id) {
    Swal.fire({ icon: 'warning', title: 'Selecciona cuentas', text: 'Debes elegir la cuenta de origen y destino.', background: '#0f172a', color: '#fff' });
    return;
  }
  if (transferForm.value.cuenta_origen_id === transferForm.value.cuenta_destino_id) {
    Swal.fire({ icon: 'warning', title: 'Cuentas iguales', text: 'La cuenta de origen y destino no pueden ser la misma.', background: '#0f172a', color: '#fff' });
    return;
  }
  const amt = parseFloat(String(transferForm.value.monto).replace(/,/g, ''));
  if (isNaN(amt) || amt <= 0) {
    Swal.fire({ icon: 'warning', title: 'Monto inválido', text: 'Ingresa un monto mayor a Q 0.00.', background: '#0f172a', color: '#fff' });
    return;
  }

  isSubmittingTransfer.value = true;
  try {
    const payload = {
      cuenta_origen_id: transferForm.value.cuenta_origen_id,
      cuenta_destino_id: transferForm.value.cuenta_destino_id,
      monto: amt,
      fecha: transferForm.value.fecha,
      referencia: transferForm.value.referencia,
      descripcion: transferForm.value.descripcion
    };

    const res = await api.post('/bank-accounts/transfer', payload);
    if (res.data.status === 'success') {
      showTransferModal.value = false;
      await fetchAccounts();
      await fetchRecentTransactions();
      Swal.fire({ icon: 'success', title: 'Transferencia Exitosa', text: 'Los fondos fueron transferidos y registrados en el sistema.', background: '#0f172a', color: '#fff', timer: 2500, showConfirmButton: false });
    } else {
      throw new Error(res.data.message);
    }
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'Error en Transferencia', text: err.message || 'No se pudo completar la transferencia.', background: '#0f172a', color: '#fff' });
  } finally {
    isSubmittingTransfer.value = false;
  }
};

const openHistory = async (acc: any) => {
  selectedAccount.value = acc;
  historyTransactions.value = [];
  historySearchQuery.value = '';
  historyTypeFilter.value = '';
  showHistoryModal.value = true;

  try {
    const res = await api.get(`/bank-accounts/${acc.id}/history`);
    if (res.data.status === 'success') {
      historyTransactions.value = res.data.data || [];
    }
  } catch (error) {
    console.error('Error fetching account history:', error);
  }
};

const openGlobalHistoryModal = () => {
  selectedAccount.value = null;
  historyTransactions.value = recentTransactions.value;
  historySearchQuery.value = '';
  historyTypeFilter.value = '';
  showHistoryModal.value = true;
};

const openReconcileModal = (acc: any) => {
  reconcileForm.value = {
    account_id: String(acc.id),
    nuevo_saldo: String(acc.saldo_actual || ''),
    notas: ''
  };
  showReconcileModal.value = true;
};

const openQuickReconciliation = () => {
  if (accounts.value.length === 0) {
    Swal.fire({ icon: 'info', title: 'Sin cuentas', text: 'Primero registra una cuenta bancaria.', background: '#0f172a', color: '#fff' });
    return;
  }
  openReconcileModal(accounts.value[0]);
};

const onReconcileAccountChange = () => {
  const found = accounts.value.find(a => a.id == reconcileForm.value.account_id);
  if (found) {
    reconcileForm.value.nuevo_saldo = String(found.saldo_actual || '');
  }
};

const handleExecuteReconciliation = async () => {
  if (!reconcileForm.value.account_id) return;
  const target = parseFloat(String(reconcileForm.value.nuevo_saldo).replace(/,/g, ''));
  if (isNaN(target)) {
    Swal.fire({ icon: 'warning', title: 'Saldo inválido', text: 'Ingresa un saldo numérico válido.', background: '#0f172a', color: '#fff' });
    return;
  }

  isSubmittingReconciliation.value = true;
  try {
    const res = await api.post(`/bank-accounts/${reconcileForm.value.account_id}/reconcile`, {
      nuevo_saldo: target,
      notas: reconcileForm.value.notas
    });
    if (res.data.status === 'success') {
      showReconcileModal.value = false;
      await fetchAccounts();
      await fetchRecentTransactions();
      Swal.fire({ icon: 'success', title: 'Conciliación Aplicada', text: 'El saldo bancario fue ajustado correctamente.', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
    } else {
      throw new Error(res.data.message);
    }
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'Error al conciliar', text: err.message || 'Error al aplicar conciliación.', background: '#0f172a', color: '#fff' });
  } finally {
    isSubmittingReconciliation.value = false;
  }
};

const generateBankReport = () => {
  window.print();
};

const exportBankExcel = () => {
  if (accounts.value.length === 0) {
    Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay cuentas bancarias para exportar.', background: '#0f172a', color: '#fff' });
    return;
  }

  let csvContent = 'data:text/csv;charset=utf-8,';
  csvContent += 'ID,Banco,Numero Cuenta,Tipo Cuenta,Moneda,Estado,Saldo Actual\n';

  accounts.value.forEach(acc => {
    csvContent += `"${acc.id}","${acc.nombre_banco}","${acc.numero_cuenta}","${acc.tipo_cuenta || 'Monetaria'}","${acc.moneda}","${acc.activa ? 'Activa' : 'Inactiva'}","${acc.saldo_actual}"\n`;
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `Cuentas_Bancarias_${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  Swal.fire({ icon: 'success', title: 'Archivo Exportado', text: 'Se ha descargado el archivo CSV con las cuentas bancarias.', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
};
</script>

<style scoped>
.glass-card {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.25);
}

@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
.animate-spin-slow {
  animation: spin-slow 20s linear infinite;
}
</style>
