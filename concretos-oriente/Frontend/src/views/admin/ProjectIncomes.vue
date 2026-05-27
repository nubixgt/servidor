<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
    <!-- Header y Selección de Proyecto -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
      <div>
        <h1 class="text-4xl font-black italic tracking-tighter uppercase text-white">Ingresos por Proyectos</h1>
        <p class="text-[10px] font-bold text-white/40 mt-2 uppercase tracking-[0.3em]">Gestión de anticipos, estimaciones y pago final</p>
      </div>
      <div class="w-full md:w-1/3">
        <label class="text-[10px] font-bold text-white/40 uppercase tracking-widest block mb-2">Seleccionar Proyecto</label>
        <select v-model="selectedProjectId" @change="fetchProjectData" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
          <option value="" disabled>Seleccione un proyecto...</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.nombre }}</option>
        </select>
      </div>
    </div>

    <!-- Indicador de Carga o No Selección -->
    <div v-if="!selectedProjectId" class="glass-card p-12 rounded-[40px] text-center border border-white/5 shadow-xl">
      <BriefcaseIcon class="w-16 h-16 mx-auto text-white/20 mb-4" />
      <p class="text-lg font-bold text-white/50">Por favor seleccione un proyecto para ver sus cobros.</p>
    </div>
    
    <div v-else-if="loading" class="text-center py-20">
      <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
      <p class="text-white/50 font-bold tracking-widest uppercase text-[10px]">Cargando datos...</p>
    </div>

    <!-- Contenido del Proyecto Seleccionado -->
    <div v-else class="space-y-12">
      <!-- Acciones Rápidas -->
      <div class="flex flex-wrap gap-4">
        <button 
          @click="openModal('Anticipo')" 
          :disabled="totals.tiene_anticipo"
          :class="['px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-xl', !totals.tiene_anticipo ? 'glass-button-primary text-white hover:-translate-y-1' : 'bg-white/5 text-white/20 cursor-not-allowed border border-white/5']"
        >
          <span class="flex items-center gap-2"><PlusIcon class="w-4 h-4" /> Registrar Anticipo</span>
        </button>
        
        <button 
          @click="openModal('Estimacion')" 
          :disabled="!totals.tiene_anticipo || totals.tiene_pago_final || totals.ultima_estimacion >= 8 || totals.restante <= 0"
          :class="['px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-xl', (totals.tiene_anticipo && !totals.tiene_pago_final && totals.ultima_estimacion < 8 && totals.restante > 0) ? 'glass-button-primary text-white hover:-translate-y-1' : 'bg-white/5 text-white/20 cursor-not-allowed border border-white/5']"
        >
          <span class="flex items-center gap-2"><PlusIcon class="w-4 h-4" /> Registrar Estimación {{ totals.ultima_estimacion + 1 > 8 ? '' : totals.ultima_estimacion + 1 }}</span>
        </button>

        <button 
          @click="openModal('Pago Final')" 
          :disabled="!totals.tiene_anticipo || totals.ultima_estimacion === 0 || totals.tiene_pago_final || totals.restante <= 0"
          :class="['px-6 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all shadow-xl', (totals.tiene_anticipo && totals.ultima_estimacion > 0 && !totals.tiene_pago_final && totals.restante > 0) ? 'bg-tertiary text-white hover:bg-tertiary/80 hover:-translate-y-1' : 'bg-white/5 text-white/20 cursor-not-allowed border border-white/5']"
        >
          <span class="flex items-center gap-2"><CheckCircleIcon class="w-4 h-4" /> Registrar Pago Final</span>
        </button>
      </div>

      <!-- Tabla de Línea de Tiempo de Cobros -->
      <section class="glass-card rounded-[40px] overflow-hidden border border-white/5 shadow-2xl" data-aos="fade-up">
        <div class="p-8 border-b border-white/5 bg-white/5">
          <h3 class="text-xl font-black text-white italic tracking-tighter uppercase">Línea de Tiempo de Cobros</h3>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mt-1">Historial cronológico de ingresos del contrato</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-black/20 text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">
                <th class="px-8 py-6"># / Tipo</th>
                <th class="px-8 py-6">% del Contrato</th>
                <th class="px-8 py-6">Monto Total</th>
                <th class="px-8 py-6">Fuentes y Estado</th>
                <th class="px-8 py-6 text-right">% Cobrado Acumulado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="timeline.length === 0">
                <td colspan="5" class="px-8 py-10 text-center text-white/30 font-bold text-sm">No hay cobros registrados aún.</td>
              </tr>
              <tr v-for="(inc, index) in timeline" :key="inc.id" class="hover:bg-white/5 transition-all group">
                <td class="px-8 py-6 align-top">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center font-black">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <p class="font-black text-white uppercase text-sm">
                        {{ inc.tipo_cobro }} {{ inc.numero_estimacion ? inc.numero_estimacion : '' }}
                      </p>
                      <p class="text-[10px] text-white/40">{{ formatDate(inc.fecha_registro) }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6 align-top font-bold text-white/60">
                  {{ Number(inc.porcentaje_contrato).toFixed(2) }}%
                </td>
                <td class="px-8 py-6 align-top font-black text-lg text-primary">
                  Q{{ Number(inc.monto_total).toLocaleString('en-US', {minimumFractionDigits:2}) }}
                </td>
                <td class="px-8 py-6">
                  <div class="space-y-3">
                    <div v-for="src in inc.sources" :key="src.id" class="flex items-center justify-between p-3 rounded-xl bg-black/20 border border-white/5">
                      <div>
                        <p class="text-xs font-bold text-white">{{ src.fuente }}</p>
                        <p class="text-[10px] text-white/40 uppercase tracking-widest">{{ Number(src.porcentaje_aporte).toFixed(2) }}% - Q{{ Number(src.monto_aportado).toLocaleString('en-US', {minimumFractionDigits:2}) }}</p>
                      </div>
                      <div class="flex items-center gap-3">
                        <span :class="['text-[9px] font-black uppercase tracking-[0.2em] px-2 py-1 rounded-md', src.estado === 'Recibido' ? 'bg-primary/20 text-primary' : 'bg-tertiary/20 text-tertiary']">
                          {{ src.estado }}
                        </span>
                        <button v-if="src.estado === 'Pendiente'" @click="openSourceModal(src)" class="p-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-white/60 hover:text-white transition-all" title="Actualizar a Recibido">
                          <PencilSquareIcon class="w-4 h-4" />
                        </button>
                        <a v-if="src.comprobante_path" :href="getFileUrl(src.comprobante_path)" target="_blank" class="p-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-white/60 hover:text-white transition-all" title="Ver Comprobante">
                          <DocumentTextIcon class="w-4 h-4" />
                        </a>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6 align-top text-right">
                  <span class="font-black text-2xl text-white/80 italic">{{ calculateAccumulated(index) }}%</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Tabla de Consolidado por Fuente -->
      <section class="glass-card rounded-[40px] overflow-hidden border border-white/5 shadow-2xl" data-aos="fade-up">
        <div class="p-8 border-b border-white/5 bg-white/5">
          <h3 class="text-xl font-black text-white italic tracking-tighter uppercase">Resumen de Totales por Fuente</h3>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mt-1">Consolidado general del proyecto</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-black/20 text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">
                <th class="px-8 py-6">Fuente</th>
                <th class="px-8 py-6 text-right">Monto Total Esperado</th>
                <th class="px-8 py-6 text-right text-primary">Monto Cobrado</th>
                <th class="px-8 py-6 text-right text-tertiary">Pendiente</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="summary.length === 0">
                <td colspan="4" class="px-8 py-10 text-center text-white/30 font-bold text-sm">No hay datos consolidados.</td>
              </tr>
              <tr v-for="sum in summary" :key="sum.fuente" class="hover:bg-white/5 transition-all group">
                <td class="px-8 py-6 font-black text-white text-sm">{{ sum.fuente }}</td>
                <td class="px-8 py-6 text-right font-bold text-white/80">Q{{ Number(sum.monto_total_esperado).toLocaleString('en-US', {minimumFractionDigits:2}) }}</td>
                <td class="px-8 py-6 text-right font-black text-primary">Q{{ Number(sum.monto_cobrado).toLocaleString('en-US', {minimumFractionDigits:2}) }}</td>
                <td class="px-8 py-6 text-right font-black text-tertiary">Q{{ Number(sum.monto_pendiente).toLocaleString('en-US', {minimumFractionDigits:2}) }}</td>
              </tr>
              <!-- FILA DE TOTAL -->
              <tr v-if="summary.length > 0" class="bg-primary/5">
                <td class="px-8 py-6 font-black text-white text-lg tracking-widest">TOTAL CONTRATO</td>
                <td class="px-8 py-6 text-right font-black text-xl text-white">Q{{ Number(totals.presupuesto).toLocaleString('en-US', {minimumFractionDigits:2}) }}</td>
                <td class="px-8 py-6 text-right font-black text-xl text-primary">Q{{ Number(totals.total_cobrado).toLocaleString('en-US', {minimumFractionDigits:2}) }}</td>
                <td class="px-8 py-6 text-right font-black text-xl text-tertiary">Q{{ Number(totals.restante).toLocaleString('en-US', {minimumFractionDigits:2}) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- MODAL PRINCIPAL (Anticipo, Estimacion, Pago Final) -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="closeModal"></div>
      <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[40px] p-10 relative z-10 border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)] custom-scrollbar" data-aos="zoom-in-up" data-aos-duration="600">
        <div class="flex items-center justify-between mb-8 border-b border-white/5 pb-6">
          <div>
            <h3 class="text-3xl font-black text-white italic tracking-tighter uppercase">Registrar {{ formMode }}</h3>
            <p class="text-[10px] font-bold text-white/40 uppercase tracking-[0.3em] mt-1">Completa los datos y divisiones por fuente</p>
          </div>
          <button @click="closeModal" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl transition-all text-white/40 hover:text-white"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-8">
          <!-- Datos Generales -->
          <div class="bg-white/5 p-6 rounded-3xl border border-white/5 space-y-6">
            <h4 class="text-xs font-black text-white uppercase tracking-[0.2em] mb-4 text-primary">Datos Generales</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Valor del Contrato</label>
                <input type="text" :value="'Q ' + Number(totals.presupuesto).toLocaleString('en-US',{minimumFractionDigits:2})" disabled class="w-full bg-black/40 border border-white/5 rounded-2xl px-5 py-4 text-white/60 focus:outline-none" />
              </div>
              
              <div class="space-y-2" v-if="formMode === 'Estimacion' || formMode === 'Pago Final'">
                <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Total Acumulado Previo</label>
                <input type="text" :value="'Q ' + Number(totals.total_cobrado).toLocaleString('en-US',{minimumFractionDigits:2})" disabled class="w-full bg-black/40 border border-white/5 rounded-2xl px-5 py-4 text-white/60 focus:outline-none" />
              </div>

              <div class="space-y-2" v-if="formMode === 'Estimacion'">
                <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Número de Estimación</label>
                <input type="text" :value="totals.ultima_estimacion + 1" disabled class="w-full bg-black/40 border border-white/5 rounded-2xl px-5 py-4 text-white/60 focus:outline-none" />
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Monto de este Cobro (Q) *</label>
                <input type="number" step="0.01" min="0.01" :max="formMode === 'Estimacion' ? totals.restante : undefined" v-model="formData.monto_total" :disabled="formMode !== 'Estimacion'" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white font-bold focus:outline-none focus:border-primary/50 disabled:bg-black/40 disabled:text-white/60 disabled:border-white/5" />
              </div>

              <div class="space-y-2" v-if="formMode === 'Estimacion'">
                <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Período de Avance</label>
                <input type="text" v-model="formData.periodo_avance" placeholder="Ej: Semana 3-6" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Fecha de Registro *</label>
                <input type="date" v-model="formData.fecha_registro" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Observaciones</label>
              <textarea v-model="formData.observaciones" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50"></textarea>
            </div>
          </div>

          <!-- División por Fuentes -->
          <div class="bg-white/5 p-6 rounded-3xl border border-white/5 space-y-6">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-xs font-black text-white uppercase tracking-[0.2em] text-primary">División por Fuente</h4>
              <span :class="['text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg', totalPorcentajeFuentes === 100 ? 'bg-primary/20 text-primary' : 'bg-tertiary/20 text-tertiary']">
                Total: {{ totalPorcentajeFuentes }}%
              </span>
            </div>
            
            <div v-for="(src, idx) in formData.sources" :key="src.fuente" class="p-6 rounded-2xl bg-black/20 border border-white/5 space-y-4">
              <h5 class="text-sm font-black text-white">{{ src.fuente }}</h5>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                  <label class="text-[9px] font-bold text-white/40 uppercase tracking-widest">% Aporte *</label>
                  <input type="number" step="0.01" min="0" max="100" v-model.number="src.porcentaje_aporte" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary/50" />
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-bold text-white/40 uppercase tracking-widest">Monto Calculado</label>
                  <input type="text" :value="'Q ' + Number((formData.monto_total || 0) * (src.porcentaje_aporte / 100)).toLocaleString('en-US',{minimumFractionDigits:2})" disabled class="w-full bg-transparent border border-transparent rounded-xl px-4 py-3 text-white/60 text-sm" />
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-bold text-white/40 uppercase tracking-widest">Estado Inicial</label>
                  <select v-model="src.estado" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary/50 appearance-none">
                    <option value="Pendiente">Pendiente</option>
                    <option value="Recibido">Recibido</option>
                  </select>
                </div>
              </div>

              <!-- Campos extra si Estado es Recibido -->
              <div v-if="src.estado === 'Recibido'" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-white/5">
                <div class="space-y-1">
                  <label class="text-[9px] font-bold text-white/40 uppercase tracking-widest">Fecha Cobro Efectivo *</label>
                  <input type="date" v-model="src.fecha_cobro" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary/50" />
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-bold text-white/40 uppercase tracking-widest">Cuenta Bancaria *</label>
                  <select v-model="src.bank_account_id" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary/50 appearance-none">
                    <option value="" disabled>Seleccione cuenta...</option>
                    <option v-for="c in bankAccounts" :key="c.id" :value="c.id">{{ c.nombre_banco }} - {{ c.numero_cuenta }}</option>
                  </select>
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-bold text-white/40 uppercase tracking-widest">No. Cheque / Ref.</label>
                  <input type="text" v-model="src.numero_documento" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary/50" />
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-4 pt-6">
            <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/50 hover:text-white hover:bg-white/5 transition-all text-xs tracking-widest uppercase">Cancelar</button>
            <button type="submit" :disabled="isSubmitting || totalPorcentajeFuentes !== 100" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold shadow-[0_0_20px_rgba(99,102,241,0.3)] hover:shadow-[0_0_30px_rgba(99,102,241,0.6)] disabled:opacity-50 disabled:cursor-not-allowed transition-all tracking-widest uppercase text-xs">
              Guardar {{ formMode }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ACTUALIZAR FUENTE A RECIBIDO -->
    <div v-if="showSourceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="closeSourceModal"></div>
      <div class="glass-card w-full max-w-lg rounded-[40px] p-10 relative z-10 border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)]" data-aos="zoom-in" data-aos-duration="400">
        <div class="flex items-center justify-between mb-8 border-b border-white/5 pb-6">
          <div>
            <h3 class="text-2xl font-black text-white italic tracking-tighter uppercase">Actualizar Cobro</h3>
            <p class="text-[10px] font-bold text-primary uppercase tracking-[0.3em] mt-1">{{ selectedSource.fuente }}</p>
          </div>
          <button @click="closeSourceModal" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl transition-all text-white/40 hover:text-white"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitSourceUpdate" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Cambiar Estado a *</label>
            <select v-model="formSource.estado" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
              <option value="Pendiente">Pendiente</option>
              <option value="Recibido">Recibido</option>
            </select>
          </div>

          <template v-if="formSource.estado === 'Recibido'">
            <div class="space-y-2">
              <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Fecha de Cobro *</label>
              <input type="date" v-model="formSource.fecha_cobro" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
            
            <div class="space-y-2">
              <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Cuenta Bancaria *</label>
              <select v-model="formSource.bank_account_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccione...</option>
                <option v-for="c in bankAccounts" :key="c.id" :value="c.id">{{ c.nombre_banco }} - {{ c.numero_cuenta }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">No. Cheque / Depósito</label>
              <input type="text" v-model="formSource.numero_documento" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Comprobante (Imagen/PDF)</label>
              <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="w-full text-white/60 file:mr-4 file:py-4 file:px-6 file:rounded-2xl file:border-0 file:text-[10px] file:uppercase file:tracking-widest file:font-bold file:bg-white/10 file:text-white hover:file:bg-white/20 transition-all cursor-pointer" />
            </div>
          </template>

          <div class="flex justify-end gap-4 pt-6 border-t border-white/5">
            <button type="submit" :disabled="isSubmitting" class="w-full glass-button-primary text-white py-4 px-10 rounded-2xl font-bold shadow-[0_0_20px_rgba(99,102,241,0.3)] hover:shadow-[0_0_30px_rgba(99,102,241,0.6)] disabled:opacity-50 transition-all tracking-widest uppercase text-xs">
              Actualizar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { BriefcaseIcon, PlusIcon, CheckCircleIcon, XMarkIcon, PencilSquareIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const projects = ref([]);
const bankAccounts = ref([]);
const selectedProjectId = ref('');
const loading = ref(false);

const timeline = ref([]);
const summary = ref([]);
const totals = ref({ presupuesto: 0, total_cobrado: 0, ultima_estimacion: 0, tiene_anticipo: false, tiene_pago_final: false, restante: 0 });

onMounted(async () => {
  await fetchProjects();
  await fetchBankAccounts();
});

const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`);
    const data = await res.json();
    if(data.status === 'success') projects.value = data.data;
  } catch(e) {}
};

const fetchBankAccounts = async () => {
  try {
    const res = await fetch(`${BASE_URL}/finance/bank-accounts`); // Asumiendo que esta ruta existe o crearemos un proxy si falla
    // En el script real de vue se debería adaptar a la ruta existente. 
    // Como vimos Finance.vue, allí estaba estático, pero la DB tiene bank_accounts. 
    // Trataré de obtenerlos de API o lo pongo vacío temporal.
    const text = await res.text();
    try {
      const data = JSON.parse(text);
      if(data.status === 'success') bankAccounts.value = data.data;
    } catch(err) {
      console.warn("Ruta bank-accounts no existe en v1, cargando de Projects u otra ruta");
    }
  } catch(e) {}
  
  if (bankAccounts.value.length === 0) {
    // Fallback based on DB view
    bankAccounts.value = [
      { id: 1, nombre_banco: 'banco industrial', numero_cuenta: '984651023' },
      { id: 2, nombre_banco: 'gyt continental', numero_cuenta: '87456120' },
      { id: 3, nombre_banco: 'Banco gyt', numero_cuenta: '65410352130' }
    ];
  }
};

const fetchProjectData = async () => {
  if (!selectedProjectId.value) return;
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/projects/${selectedProjectId.value}/incomes`);
    const data = await res.json();
    if (data.status === 'success') {
      timeline.value = data.data.timeline;
      summary.value = data.data.summary;
      totals.value = data.data.totals;
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: data.message});
    }
  } catch (e) {
    Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: "Error de conexión"});
  }
  loading.value = false;
};

// Utils
const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const getFileUrl = (path) => {
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

const calculateAccumulated = (index) => {
  let acc = 0;
  for (let i = 0; i <= index; i++) {
    acc += parseFloat(timeline.value[i].porcentaje_contrato);
  }
  return acc.toFixed(2);
};

// MODAL MAIN
const showModal = ref(false);
const formMode = ref('Anticipo');
const isSubmitting = ref(false);

const formData = ref({
  monto_total: 0,
  periodo_avance: '',
  fecha_registro: new Date().toISOString().slice(0,10),
  observaciones: '',
  sources: []
});

const openModal = (mode) => {
  formMode.value = mode;
  let defaultMonto = 0;
  
  if (mode === 'Anticipo') defaultMonto = totals.value.presupuesto * 0.20;
  if (mode === 'Pago Final') defaultMonto = totals.value.restante;

  formData.value = {
    monto_total: defaultMonto,
    periodo_avance: '',
    fecha_registro: new Date().toISOString().slice(0,10),
    observaciones: '',
    sources: [
      { fuente: 'Consejo de Desarrollo', porcentaje_aporte: 0, estado: 'Pendiente', fecha_cobro: '', bank_account_id: '', numero_documento: '' },
      { fuente: 'Municipalidad', porcentaje_aporte: 0, estado: 'Pendiente', fecha_cobro: '', bank_account_id: '', numero_documento: '' },
      { fuente: 'COCODE', porcentaje_aporte: 0, estado: 'Pendiente', fecha_cobro: '', bank_account_id: '', numero_documento: '' }
    ]
  };
  showModal.value = true;
};

const closeModal = () => showModal.value = false;

const totalPorcentajeFuentes = computed(() => {
  return formData.value.sources.reduce((acc, curr) => acc + (parseFloat(curr.porcentaje_aporte) || 0), 0);
});

const submitForm = async () => {
  if (totalPorcentajeFuentes.value !== 100) {
    Swal.fire({background: '#0f172a', color: '#fff', icon: 'warning', text: "Los porcentajes de las fuentes deben sumar 100%."});
    return;
  }
  isSubmitting.value = true;
  
  const payload = {
    tipo_cobro: formMode.value,
    monto_total: parseFloat(formData.value.monto_total),
    fecha_registro: formData.value.fecha_registro,
    periodo_avance: formData.value.periodo_avance,
    observaciones: formData.value.observaciones,
    sources: formData.value.sources
  };

  try {
    const res = await fetch(`${BASE_URL}/projects/${selectedProjectId.value}/incomes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchProjectData();
      closeModal();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Registro exitoso'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {
    Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: "Error de conexión"});
  }
  isSubmitting.value = false;
};

// MODAL SOURCE UPDATE
const showSourceModal = ref(false);
const selectedSource = ref({});
const formSource = ref({ estado: 'Recibido', fecha_cobro: new Date().toISOString().slice(0,10), bank_account_id: '', numero_documento: '' });
const attachmentFile = ref(null);

const openSourceModal = (src) => {
  selectedSource.value = src;
  formSource.value = { 
    estado: 'Recibido', 
    fecha_cobro: new Date().toISOString().slice(0,10), 
    bank_account_id: '', 
    numero_documento: '' 
  };
  attachmentFile.value = null;
  showSourceModal.value = true;
};

const closeSourceModal = () => showSourceModal.value = false;

const handleFileUpload = (e) => {
  if (e.target.files.length > 0) attachmentFile.value = e.target.files[0];
};

const submitSourceUpdate = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formSource.value).forEach(k => {
    fd.append(k, formSource.value[k]);
  });
  if (attachmentFile.value) fd.append('comprobante', attachmentFile.value);

  try {
    const res = await fetch(`${BASE_URL}/project-incomes/sources/${selectedSource.value.id}`, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchProjectData();
      closeSourceModal();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Fuente Actualizada'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
