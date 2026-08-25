<script setup>
import { ref, watch } from 'vue';
import { 
  Search, 
  X, 
  CheckCircle2, 
  Clock, 
  AlertCircle, 
  UserCheck, 
  Calendar, 
  DollarSign, 
  FileText
} from 'lucide-vue-next';
import { MOCK_ORDERS } from '../../data/mockData';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  initialCode: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close']);

const ticketQuery = ref(props.initialCode || 'TF-8841');
const currentOrder = ref(MOCK_ORDERS[ticketQuery.value] || MOCK_ORDERS['TF-8841']);
const notFound = ref(false);

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.initialCode) {
      ticketQuery.value = props.initialCode;
      handleSearch(props.initialCode);
    }
  }
});

const handleSearch = (codeToSearch = '') => {
  const code = (codeToSearch || ticketQuery.value).trim().toUpperCase();
  if (!code) return;

  if (MOCK_ORDERS[code]) {
    currentOrder.value = MOCK_ORDERS[code];
    notFound.value = false;
    ticketQuery.value = code;
  } else {
    currentOrder.value = null;
    notFound.value = true;
  }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 flex flex-col">
      
      <!-- Modal Header -->
      <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
            <Search class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Rastreador de Estado de Reparación</h3>
            <p class="text-xs text-slate-500">Consulta el avance técnico de tu equipo en tiempo real</p>
          </div>
        </div>
        <button
          @click="emit('close')"
          class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200/60 transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Search Bar & Quick Test Tickets -->
      <div class="p-5 sm:p-6 space-y-4 border-b border-slate-100">
        <form
          @submit.prevent="handleSearch()"
          class="flex gap-2"
        >
          <div class="relative flex-1">
            <input
              type="text"
              v-model="ticketQuery"
              placeholder="Ejemplo: TF-8841"
              class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold tracking-wider text-slate-900 placeholder:font-normal placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white"
            />
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
          </div>
          <button
            type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-xl text-sm transition-colors cursor-pointer shadow-xs"
          >
            Consultar
          </button>
        </form>

        <!-- Quick preset tickets -->
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
          <span>Tickets de prueba:</span>
          <button
            v-for="(order, code) in MOCK_ORDERS"
            :key="code"
            type="button"
            @click="handleSearch(code)"
            class="px-2.5 py-1 rounded-md border font-mono transition-colors cursor-pointer"
            :class="[
              currentOrder?.ticketCode === code
                ? 'bg-blue-50 border-blue-300 text-blue-700 font-bold'
                : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
            ]"
          >
            {{ code }}
          </button>
        </div>
      </div>

      <!-- Modal Content: Order Details -->
      <div class="p-5 sm:p-6 space-y-6">
        <div v-if="notFound" class="text-center py-10 space-y-3">
          <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto">
            <AlertCircle class="w-6 h-6" />
          </div>
          <h4 class="text-base font-bold text-slate-800">No encontramos el ticket "{{ ticketQuery }}"</h4>
          <p class="text-xs text-slate-500 max-w-sm mx-auto">
            Verifica que el código coincida con el comprobante que te enviamos por correo o WhatsApp.
          </p>
        </div>

        <div v-if="currentOrder" class="space-y-6">
          <!-- Top Overview Card -->
          <div class="bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-200 space-y-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-mono text-sm font-bold text-blue-700 bg-blue-100/70 px-2 py-0.5 rounded">
                    {{ currentOrder.ticketCode }}
                  </span>
                  <!-- Status Badge directly rendered in template for simplicity in Vue without function component -->
                  <span v-if="currentOrder.currentStatus === 'received'" class="px-3 py-1 bg-slate-100 text-slate-800 rounded-full text-xs font-semibold">Recepcionado</span>
                  <span v-else-if="currentOrder.currentStatus === 'diagnosing'" class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">En Diagnóstico</span>
                  <span v-else-if="currentOrder.currentStatus === 'waiting_parts'" class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">Esperando Repuestos</span>
                  <span v-else-if="currentOrder.currentStatus === 'repairing'" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold animate-pulse">En Reparación</span>
                  <span v-else-if="currentOrder.currentStatus === 'qa_testing'" class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-semibold">Control de Calidad</span>
                  <span v-else-if="currentOrder.currentStatus === 'ready'" class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">Listo para Retiro</span>
                  <span v-else-if="currentOrder.currentStatus === 'delivered'" class="px-3 py-1 bg-emerald-700 text-white rounded-full text-xs font-semibold">Entregado</span>
                </div>
                <h4 class="text-base font-bold text-slate-900 mt-1.5">
                  {{ currentOrder.device }}
                </h4>
                <p class="text-xs text-slate-500">{{ currentOrder.serialOrModel }}</p>
              </div>

              <div class="text-right">
                <span class="text-xs text-slate-500 block">Cliente</span>
                <span class="text-sm font-semibold text-slate-800">{{ currentOrder.clientName }}</span>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-200/80 text-xs">
              <div>
                <span class="text-slate-500 block">Técnico Asignado</span>
                <span class="font-medium text-slate-800 flex items-center gap-1 mt-0.5">
                  <UserCheck class="w-3.5 h-3.5 text-blue-600" />
                  {{ currentOrder.technician }}
                </span>
              </div>
              <div>
                <span class="text-slate-500 block">Fecha Ingreso</span>
                <span class="font-medium text-slate-800 flex items-center gap-1 mt-0.5">
                  <Calendar class="w-3.5 h-3.5 text-slate-500" />
                  {{ currentOrder.intakeDate }}
                </span>
              </div>
              <div>
                <span class="text-slate-500 block">Presupuesto / Saldo</span>
                <span class="font-semibold text-emerald-700 flex items-center gap-1 mt-0.5">
                  <DollarSign class="w-3.5 h-3.5 text-emerald-600" />
                  {{ currentOrder.costEstimate }} (Resta: {{ currentOrder.remainingBalance }})
                </span>
              </div>
            </div>
          </div>

          <!-- Technical Diagnosis Note -->
          <div class="space-y-2">
            <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
              <FileText class="w-4 h-4 text-blue-600" />
              Informe de Diagnóstico Técnico
            </h5>
            <div class="bg-blue-50/50 rounded-xl p-3.5 border border-blue-100 text-xs text-slate-700 leading-relaxed">
              <p><strong>Falla reportada:</strong> {{ currentOrder.reportedIssue }}</p>
              <p class="mt-1.5"><strong>Hallazgo de laboratorio:</strong> {{ currentOrder.diagnosticReport }}</p>
              <div v-if="currentOrder.partsUsed && currentOrder.partsUsed.length > 0" class="mt-2 pt-2 border-t border-blue-200/60">
                <span class="font-semibold text-slate-800">Repuestos aplicados: </span>
                <span>{{ currentOrder.partsUsed.join(', ') }}</span>
              </div>
            </div>
          </div>

          <!-- Step-by-Step Progress Timeline -->
          <div class="space-y-3">
            <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
              <Clock class="w-4 h-4 text-blue-600" />
              Línea de Tiempo del Proceso
            </h5>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
              <div v-for="(step, idx) in currentOrder.timeline" :key="idx" class="relative">
                <!-- Step marker -->
                <div 
                  class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center border-2 bg-white"
                  :class="[
                    step.completed 
                      ? 'border-blue-600 text-blue-600' 
                      : step.current 
                        ? 'border-blue-600 bg-blue-600 text-white animate-pulse'
                        : 'border-slate-300 text-slate-300'
                  ]"
                >
                  <CheckCircle2 v-if="step.completed" class="w-3.5 h-3.5 fill-blue-600 text-white" />
                  <span v-else class="w-1.5 h-1.5 rounded-full bg-current" />
                </div>

                <div class="space-y-0.5">
                  <div class="flex items-center justify-between">
                    <h6 class="text-sm font-bold" :class="step.completed || step.current ? 'text-slate-900' : 'text-slate-400'">
                      {{ step.label }}
                    </h6>
                    <span class="text-[11px] text-slate-500 font-mono">{{ step.date }}</span>
                  </div>
                  <p v-if="step.notes" class="text-xs text-slate-600 pt-0.5">{{ step.notes }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Ready for pickup notification banner -->
          <div v-if="currentOrder.currentStatus === 'ready'" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
              <CheckCircle2 class="w-5 h-5" />
            </div>
            <div>
              <h5 class="text-sm font-bold text-emerald-900">¡Tu equipo está listo para ser retirado!</h5>
              <p class="text-xs text-emerald-700 mt-0.5">
                Pasa por nuestra sucursal con este número de ticket o solicita envío a domicilio.
              </p>
            </div>
          </div>

        </div>
      </div>

      <!-- Footer Actions -->
      <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex justify-between items-center text-xs">
        <span class="text-slate-500">¿Dudas sobre tu orden? Llámanos al +1 234 567 8900</span>
        <button
          @click="emit('close')"
          class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium rounded-lg transition-colors cursor-pointer"
        >
          Cerrar
        </button>
      </div>

    </div>
  </div>
</template>
