<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Toast Alert -->
    <div
      v-if="notification"
      class="fixed top-24 right-10 z-50 bg-primary/25 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl"
    >
      <CheckCircleIcon class="w-5 h-5 text-primary" />
      <span class="text-xs font-black uppercase tracking-wider">{{ notification }}</span>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Bitácoras y Mantenimiento</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Control diario de operaciones y salud de activos críticos</p>
      </div>
      <button
        @click="showAddLogModal = true"
        class="glass-button-primary bg-primary border-primary border text-white px-8 py-5 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
      >
        <PlusIcon class="w-4 h-4" /> Nuevo Registro Bitácora
      </button>
    </div>

    <!-- Main Grid Content Layout -->
    <div class="grid grid-cols-12 gap-10">

      <!-- Full Width: Log Timeline -->
      <section class="col-span-12 space-y-8">
        <div class="flex justify-between items-center border-b border-white/5 pb-4">
          <h3 class="text-xl font-black italic uppercase tracking-wider flex items-center gap-3">
            <ClipboardDocumentListIcon class="w-5 h-5 text-primary" />
            Línea de Tiempo de Mantenimientos
          </h3>
          <div class="flex items-center gap-4">
            <select
              v-model="filterType"
              class="glass-input px-4 py-2.5 rounded-xl text-2xs uppercase tracking-widest font-bold text-white focus:outline-none focus:border-primary/50"
            >
              <option value="Todos" class="bg-slate-900">Todos</option>
              <option value="Preventivo" class="bg-slate-900">Preventivo</option>
              <option value="Correctivo" class="bg-slate-900">Correctivo</option>
            </select>
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/40" />
              <input
                type="text"
                v-model="searchTerm"
                placeholder="Buscar mantenimientos..."
                class="glass-input pl-10 pr-4 py-2.5 rounded-xl text-2xs uppercase tracking-widest font-bold placeholder:text-white/25 w-72 text-white focus:outline-none focus:border-primary/50"
              />
            </div>
          </div>
        </div>

        <div class="glass-card rounded-[48px] p-10 border border-white/5 shadow-2xl relative overflow-hidden">
          <div class="absolute left-16 top-10 bottom-10 w-[2px] bg-white/5 pointer-events-none"></div>

          <div class="space-y-12 relative z-10">
            <div v-if="filteredLogs.length === 0" class="text-xs text-white/40 italic pl-16">
              No hay mantenimientos registrados aún.
            </div>
            <div v-for="item in filteredLogs" :key="item.id" class="flex gap-6 relative group">
              <!-- Timeline bullet/icon indicator -->
              <div :class="[
                'w-12 h-12 rounded-2xl flex items-center justify-center z-10 transition-transform duration-300 group-hover:scale-110 shadow-lg shrink-0',
                item.tipo_mantenimiento === 'Preventivo' ? 'bg-primary/15 border border-primary/20 text-primary' : 'bg-amber-500/15 border border-amber-500/30 text-amber-400'
              ]">
                <CheckCircleIcon v-if="item.tipo_mantenimiento === 'Preventivo'" class="w-5 h-5" />
                <ExclamationTriangleIcon v-else class="w-5 h-5" />
              </div>

              <!-- Body Content -->
              <div class="flex-1 space-y-3 bg-white/[0.01] hover:bg-white/[0.03] p-6 rounded-3xl border border-transparent hover:border-white/5 transition-all">
                <div class="flex justify-between items-start gap-4">
                  <div>
                    <span class="text-[10px] font-black tracking-widest text-primary bg-primary/20 px-2.5 py-0.5 rounded uppercase">
                      #{{ item.id }}
                    </span>
                    <h4 class="text-lg font-black italic tracking-tight text-white uppercase mt-2 group-hover:text-primary transition-colors">
                      {{ item.codigo_interno }} - {{ item.marca }} {{ item.modelo }}
                    </h4>
                  </div>
                  <span class="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-1.5 bg-white/5 px-2 py-1 rounded">
                    <ClockIcon class="w-3.5 h-3.5" />
                    {{ item.fecha_mantenimiento }}
                  </span>
                </div>

                <p class="text-xs text-white/50 leading-relaxed font-bold">{{ item.descripcion }}</p>

                <!-- Details -->
                <div class="flex flex-wrap gap-2 pt-2">
                  <span class="text-[9px] font-black uppercase tracking-wider text-white/40 bg-white/5 border border-white/5 px-2.5 py-1 rounded-lg">
                    TIPO: {{ item.tipo_mantenimiento }}
                  </span>
                  <span class="text-[9px] font-black uppercase tracking-wider text-white/40 bg-white/5 border border-white/5 px-2.5 py-1 rounded-lg">
                    HORÓMETRO: {{ item.horometro_servicio }} hrs
                  </span>
                  <span v-if="item.nombres" class="text-[9px] font-black uppercase tracking-wider text-white/40 bg-white/5 border border-white/5 px-2.5 py-1 rounded-lg">
                    MECÁNICO: {{ item.nombres }} {{ item.apellidos }}
                  </span>
                </div>

                <!-- Repuestos -->
                <div v-if="item.repuestos && item.repuestos.length > 0" class="mt-4 p-3 bg-black/20 rounded-xl border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">Repuestos Utilizados</p>
                  <ul class="space-y-1">
                    <li v-for="r in item.repuestos" :key="r.id" class="text-[11px] font-bold text-white/70 flex justify-between">
                      <span>{{ r.cantidad }}x {{ r.nombre_repuesto }}</span>
                      <span class="text-emerald-400">Q{{ (Number(r.cantidad) * Number(r.costo_unitario)).toLocaleString('es-GT') }}</span>
                    </li>
                  </ul>
                </div>
                
                <div class="pt-2 flex justify-end items-center gap-2">
                  <span class="text-xs font-black italic text-white uppercase tracking-wider">Costo Total:</span>
                  <span class="text-base font-black text-primary">Q{{ Number(item.costo_total).toLocaleString('es-GT', {minimumFractionDigits:2}) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>

    <!-- Create Log Modal -->
    <div v-if="showAddLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md overflow-y-auto">
      <div class="absolute inset-0 cursor-pointer" @click="showAddLogModal = false"></div>
      <div class="relative w-full max-w-2xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white my-auto">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Orden de Mantenimiento</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Registrar nuevo servicio para maquinaria o vehículo</p>

        <form @submit.prevent="handleCreateLog" class="space-y-6">
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Maquinaria / Vehículo</label>
              <select required v-model="form.machinery_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccionar...</option>
                <option v-for="m in machineryList" :key="m.id" :value="m.id" class="bg-slate-950 text-white">
                  {{ m.codigo_interno }} - {{ m.marca }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo de Servicio</label>
              <select v-model="form.tipo_mantenimiento" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none">
                <option value="Preventivo" class="bg-slate-950 text-white">Preventivo</option>
                <option value="Correctivo" class="bg-slate-950 text-white">Correctivo</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fecha del Servicio</label>
              <input type="date" required v-model="form.fecha_mantenimiento" class="w-full glass-input rounded-2xl p-4 text-sm font-bold uppercase text-white" />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Próximo Servicio (Opcional)</label>
              <input type="date" v-model="form.proximo_mantenimiento" class="w-full glass-input rounded-2xl p-4 text-sm font-bold uppercase text-white" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Responsable / Mecánico</label>
              <select v-model="form.responsable_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none">
                <option value="" class="bg-slate-900 text-white/50">Ninguno o Externo</option>
                <option v-for="emp in personnelList" :key="emp.id" :value="emp.id" class="bg-slate-950 text-white">
                  {{ emp.nombres }} {{ emp.apellidos }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Horómetro (Hrs / Km)</label>
              <input type="number" required v-model="form.horometro_servicio" placeholder="Ej: 1520" class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white" />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Trabajo Realizado</label>
            <textarea
              rows="3"
              required
              v-model="form.descripcion"
              placeholder="Describa el trabajo o servicio hecho..."
              class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white focus:outline-none focus:border-primary/50"
            ></textarea>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Observaciones / Recomendaciones (Opcional)</label>
            <textarea
              rows="2"
              v-model="form.observaciones"
              placeholder="Notas adicionales sobre el equipo o el servicio..."
              class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white focus:outline-none focus:border-primary/50"
            ></textarea>
          </div>

          <!-- Repuestos Section -->
          <div class="bg-black/20 p-6 rounded-3xl border border-white/5 space-y-4">
            <div class="flex justify-between items-center">
              <label class="text-[10px] font-black text-white/60 uppercase tracking-widest">Repuestos Utilizados</label>
              <button type="button" @click="addRepuesto" class="text-[10px] font-black uppercase text-primary hover:text-white">+ Agregar Fila</button>
            </div>
            
            <div v-for="(rep, idx) in form.repuestos" :key="idx" class="flex gap-3 items-center">
              <input type="text" v-model="rep.nombre" placeholder="Nombre repuesto" class="flex-grow glass-input rounded-xl p-3 text-xs font-bold text-white placeholder:text-white/20" />
              <input type="number" step="0.01" v-model="rep.cantidad" placeholder="Cant." class="w-20 glass-input rounded-xl p-3 text-xs font-bold text-white text-center" />
              <input type="number" step="0.01" v-model="rep.costo_unitario" placeholder="Costo Unit (Q)" class="w-28 glass-input rounded-xl p-3 text-xs font-bold text-white text-right" />
              <button type="button" @click="removeRepuesto(idx)" class="text-rose-400 hover:text-rose-300 p-2"><TrashIcon class="w-4 h-4" /></button>
            </div>
            
            <div class="pt-4 border-t border-white/5 flex justify-between items-center text-sm font-black uppercase">
              <span class="text-white/40">Costo Estimado Repuestos:</span>
              <span class="text-emerald-400">Q{{ calcularTotalRepuestos().toLocaleString('es-GT', {minimumFractionDigits: 2}) }}</span>
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border border-primary text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Guardar Registro
            </button>
            <button
              type="button"
              @click="showAddLogModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
  ClipboardDocumentListIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  WrenchScrewdriverIcon,
  SparklesIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';

const API_URL = '/concretos-oriente/Backend/api/v1';

const machineryList = ref<any[]>([]);
const personnelList = ref<any[]>([]);
const logsList = ref<any[]>([]);

const showAddLogModal = ref(false);
const searchTerm = ref('');
const filterType = ref('Todos');
const notification = ref('');

const form = ref({
  machinery_id: '',
  tipo_mantenimiento: 'Preventivo',
  fecha_mantenimiento: new Date().toISOString().split('T')[0],
  descripcion: '',
  horometro_servicio: '',
  responsable_id: '',
  proximo_mantenimiento: '',
  observaciones: '',
  repuestos: [] as {nombre: string, cantidad: number, costo_unitario: number}[]
});

onMounted(() => {
  fetchMachinery();
  fetchPersonnel();
  fetchLogs();
});

const fetchMachinery = async () => {
  try {
    const res = await axios.get(`${API_URL}/maintenance/machinery`);
    if (res.data.status === 'success') machineryList.value = res.data.data;
  } catch (error) { console.error(error); }
};

const fetchPersonnel = async () => {
  try {
    const res = await axios.get(`${API_URL}/payrolls/active-personnel`);
    if (res.data.status === 'success') personnelList.value = res.data.data;
  } catch (error) { console.error(error); }
};

const fetchLogs = async () => {
  try {
    const res = await axios.get(`${API_URL}/maintenance/logs`);
    if (res.data.status === 'success') logsList.value = res.data.data;
  } catch (error) { console.error(error); }
};

const filteredLogs = computed(() => {
  return logsList.value.filter(item => {
    const term = searchTerm.value.toLowerCase();
    const matchSearch = !term || 
           item.descripcion.toLowerCase().includes(term) ||
           item.codigo_interno.toLowerCase().includes(term) ||
           item.marca.toLowerCase().includes(term);
    
    const matchFilter = filterType.value === 'Todos' || item.tipo_mantenimiento === filterType.value;
    
    return matchSearch && matchFilter;
  });
});

const addRepuesto = () => {
  form.value.repuestos.push({ nombre: '', cantidad: 1, costo_unitario: 0 });
};

const removeRepuesto = (idx: number) => {
  form.value.repuestos.splice(idx, 1);
};

const calcularTotalRepuestos = () => {
  return form.value.repuestos.reduce((sum, r) => sum + (Number(r.cantidad) * Number(r.costo_unitario)), 0);
};

function triggerToast(msg: string) {
  notification.value = msg;
  setTimeout(() => { notification.value = ''; }, 4500);
}

async function handleCreateLog() {
  if (!form.value.machinery_id || !form.value.descripcion) return;

  try {
    const res = await axios.post(`${API_URL}/maintenance/logs`, form.value);
    if (res.data.status === 'success') {
      showAddLogModal.value = false;
      form.value = {
        machinery_id: '',
        tipo_mantenimiento: 'Preventivo',
        fecha_mantenimiento: new Date().toISOString().split('T')[0],
        descripcion: '',
        horometro_servicio: '',
        responsable_id: '',
        proximo_mantenimiento: '',
        observaciones: '',
        repuestos: []
      };
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Mantenimiento registrado',
        showConfirmButton: false,
        timer: 3000,
        background: '#0f172a',
        color: '#fff'
      });
      fetchLogs();
    }
  } catch (error) {
    Swal.fire('Error', 'No se pudo guardar el registro', 'error');
  }
}
</script>
