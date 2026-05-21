<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Toast Notification -->
    <div
      v-if="notification"
      class="fixed top-24 right-10 z-50 bg-primary/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl"
    >
      <CheckCircleIcon class="w-5 h-5 text-primary" />
      <span class="text-xs font-black uppercase tracking-wider">{{ notification }}</span>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Centro de Mensajes</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestiona las alertas críticas y actualizaciones de tus proyectos en tiempo real</p>
      </div>

      <div class="flex flex-wrap items-center gap-4">
        <button
          @click="handleMarkAllRead"
          class="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
        >
          <DocumentCheckIcon class="w-4 h-4 text-primary" /> Marcar Todo Como Leído
        </button>

        <button
          @click="showConfigModal = true"
          class="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
        >
          <Cog6ToothIcon class="w-4 h-4" /> Configurar Alertas
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">

      <div
        @click="activeTab = 'todas'; searchTerm = ''"
        class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-rose-500/50 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
      >
        <div class="flex justify-between items-center">
          <ExclamationTriangleIcon class="w-5 h-5 text-rose-400" />
          <span class="text-[10px] font-black text-white/30 uppercase tracking-widest">Críticas</span>
        </div>
        <div>
          <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ criticalCount }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">Requieren acción inmediata</p>
        </div>
      </div>

      <div
        @click="activeTab = 'todas'; searchTerm = ''"
        class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-primary/50 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
      >
        <div class="flex justify-between items-center">
          <InformationCircleIcon class="w-5 h-5 text-primary" />
          <span class="text-[10px] font-black text-white/30 uppercase tracking-widest font-sans">Nuevos</span>
        </div>
        <div>
          <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ unreadCount }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">No leídos actualmente</p>
        </div>
      </div>

      <div
        @click="activeTab = 'finanzas'"
        class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
      >
        <div class="flex justify-between items-center">
          <CurrencyDollarIcon class="w-5 h-5 text-emerald-400" />
          <span class="text-[10px] font-black text-white/30 uppercase tracking-widest">Finanzas</span>
        </div>
        <div>
          <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ financeCount }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">Registros de cuentas y egresos</p>
        </div>
      </div>

      <div
        @click="activeTab = 'todas'; searchTerm = ''"
        class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-white/20 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
      >
        <div class="flex justify-between items-center">
          <BellIcon class="w-5 h-5 text-white/50" />
          <span class="text-[10px] font-black text-white/30 uppercase tracking-widest">Historial</span>
        </div>
        <div>
          <h3 class="text-4xl font-black italic text-white/75 tracking-tighter">{{ notifications.length }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">Notificaciones registradas</p>
        </div>
      </div>

    </div>

    <!-- Notifications Panel -->
    <div class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">

      <!-- Tabs -->
      <div class="flex items-center gap-8 px-10 py-6 border-b border-white/5 bg-white/5 overflow-x-auto scrollbar-hide">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          :class="[
            'text-xs font-black uppercase tracking-widest pb-3 transition-colors',
            activeTab === tab.value ? 'text-primary border-b-2 border-primary' : 'text-white/40 hover:text-white'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Search bar -->
      <div class="p-8 border-b border-white/5 bg-black/10 flex items-center justify-between">
        <div class="relative w-full max-w-md">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" />
          <input
            type="text"
            v-model="searchTerm"
            placeholder="Buscar alertas por palabra clave o proyecto..."
            class="glass-input pl-11 pr-4 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-full text-white placeholder:text-white/20"
          />
        </div>
        <span class="text-[10px] font-black text-white/20 uppercase tracking-widest">
          {{ filteredNotifications.length }} de {{ notifications.length }} cargadas
        </span>
      </div>

      <!-- Notifications list -->
      <div class="divide-y divide-white/5">
        <div v-if="filteredNotifications.length === 0" class="px-10 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">
          No tienes notificaciones o alertas en este módulo.
        </div>

        <div
          v-for="nt in filteredNotifications"
          :key="nt.id"
          @click="handleMarkAsRead(nt.id)"
          :class="[
            'group flex flex-col md:flex-row items-start md:items-center gap-6 px-10 py-6 transition-colors cursor-pointer',
            nt.isUrgent && !nt.isRead ? 'bg-rose-500/[0.04] hover:bg-rose-500/[0.08]' :
            !nt.isRead ? 'bg-primary/[0.02] hover:bg-primary/[0.05]' :
            'hover:bg-white/[0.02]'
          ]"
        >
          <!-- Category icon -->
          <div
            :class="[
              'flex-shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center',
              nt.isUrgent && !nt.isRead
                ? 'bg-rose-500/15 text-rose-400 border border-rose-500/20 animate-pulse'
                : getCategoryStyles(nt).bg
            ]"
          >
            <ExclamationTriangleIcon v-if="nt.isUrgent && !nt.isRead" class="w-5 h-5" />
            <CurrencyDollarIcon v-else-if="nt.category === 'finanzas'" class="w-5 h-5" />
            <UserPlusIcon v-else-if="nt.category === 'personal'" class="w-5 h-5" />
            <WrenchIcon v-else-if="nt.category === 'maquinaria'" class="w-5 h-5" />
            <BuildingOfficeIcon v-else-if="nt.category === 'proyectos'" class="w-5 h-5" />
            <InformationCircleIcon v-else class="w-5 h-5" />
          </div>

          <!-- Content -->
          <div class="flex-grow space-y-1.5 min-w-0">
            <div class="flex items-center flex-wrap gap-2.5">
              <h4 :class="['font-extrabold text-white text-base tracking-tight uppercase italic', nt.isRead ? 'opacity-60' : '']">
                {{ nt.title }}
              </h4>
              <span v-if="nt.isUrgent && !nt.isRead" class="px-3 py-0.5 bg-rose-500 text-white font-black text-[9px] uppercase tracking-widest rounded-lg">
                Urgente
              </span>
              <span class="text-[10px] font-black uppercase tracking-wider text-white/30">{{ nt.id }}</span>
            </div>

            <p :class="['text-xs text-white/50 leading-relaxed font-semibold transition-all', nt.isRead ? 'opacity-50' : '']">
              {{ nt.description }}
            </p>

            <div class="flex items-center flex-wrap gap-4 pt-1.5 text-[10px] font-black text-white/30 uppercase tracking-widest">
              <span class="flex items-center gap-1.5">
                <CalendarDaysIcon class="w-3.5 h-3.5" />
                {{ nt.timeAgo }}
              </span>
              <span class="text-white/10">•</span>
              <span class="text-primary italic">{{ nt.projectOrMeta }}</span>
              <span class="text-white/10">•</span>
              <span :class="nt.isUrgent && !nt.isRead ? 'text-rose-400' : 'text-emerald-400'">
                {{ nt.valueOrPriority }}
              </span>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="flex items-center gap-2 self-end md:self-center opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              v-if="!nt.isRead"
              @click.stop="handleMarkAsRead(nt.id)"
              title="Marcar como leído"
              class="p-2.5 bg-white/5 hover:bg-white/10 text-white/50 hover:text-white rounded-xl border border-white/5 transition-all"
            >
              <CheckIcon class="w-4 h-4 text-emerald-400" />
            </button>
            <button
              @click.stop="handleDeleteNotification(nt.id)"
              title="Eliminar registro"
              class="p-2.5 bg-white/5 hover:bg-white/10 text-white/50 hover:text-rose-400 rounded-xl border border-white/5 transition-all"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

    </div>



    <!-- Config Modal -->
    <div v-if="showConfigModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md overflow-y-auto">
      <div class="absolute inset-0 cursor-pointer" @click="showConfigModal = false"></div>

      <div class="relative w-full max-w-lg glass-card rounded-[40px] p-10 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white my-8">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Configuración de Alerta</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Define los parámetros para disparar alertas automáticas</p>

        <form @submit.prevent="handleSaveConfig" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre de la alerta</label>
            <input type="text" v-model="form.nombre" required class="w-full glass-input rounded-2xl p-4 text-xs font-bold uppercase text-white placeholder:text-white/20" placeholder="Ej. Alerta de Stock de Cemento" />
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo de evento</label>
            <select v-model="form.tipo_evento" required class="w-full glass-input rounded-2xl p-4 text-xs font-bold uppercase text-white appearance-none cursor-pointer">
              <option value="Stock bajo" class="bg-slate-900">Stock bajo</option>
              <option value="Vencimiento de crédito" class="bg-slate-900">Vencimiento de crédito</option>
              <option value="Mantenimiento próximo" class="bg-slate-900">Mantenimiento próximo</option>
              <option value="Sobrecosto de proyecto" class="bg-slate-900">Sobrecosto de proyecto</option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Canal de notificación</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 text-xs font-bold uppercase text-white cursor-pointer"><input type="checkbox" value="WhatsApp" v-model="form.canales" class="rounded border-white/10 bg-white/5 text-primary focus:ring-0"> WhatsApp</label>
              <label class="flex items-center gap-2 text-xs font-bold uppercase text-white cursor-pointer"><input type="checkbox" value="Correo electrónico" v-model="form.canales" class="rounded border-white/10 bg-white/5 text-primary focus:ring-0"> Correo electrónico</label>
              <label class="flex items-center gap-2 text-xs font-bold uppercase text-white cursor-pointer"><input type="checkbox" value="In-app" v-model="form.canales" class="rounded border-white/10 bg-white/5 text-primary focus:ring-0"> In-app</label>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Destinatarios</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 text-xs font-bold uppercase text-white cursor-pointer"><input type="checkbox" value="Admin" v-model="form.destinatarios" class="rounded border-white/10 bg-white/5 text-emerald-500 focus:ring-0"> Admin</label>
              <label class="flex items-center gap-2 text-xs font-bold uppercase text-white cursor-pointer"><input type="checkbox" value="Supervisor" v-model="form.destinatarios" class="rounded border-white/10 bg-white/5 text-emerald-500 focus:ring-0"> Supervisor</label>
              <label class="flex items-center gap-2 text-xs font-bold uppercase text-white cursor-pointer"><input type="checkbox" value="Técnico" v-model="form.destinatarios" class="rounded border-white/10 bg-white/5 text-emerald-500 focus:ring-0"> Técnico</label>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Umbral o condición (Numérico)</label>
            <input type="number" v-model="form.umbral" required class="w-full glass-input rounded-2xl p-4 text-xs font-bold uppercase text-white placeholder:text-white/20" placeholder="Ej: 5 (Días o %)" />
          </div>

          <div class="flex justify-between items-center p-4 bg-white/5 rounded-2xl border border-white/5">
            <div>
              <p class="font-extrabold text-xs uppercase tracking-wider text-white">Alerta Activa</p>
              <p class="text-[10px] font-bold text-white/30 uppercase mt-0.5">Habilitar inmediatamente</p>
            </div>
            <input type="checkbox" v-model="form.activa" class="w-5 h-5 rounded border-white/10 bg-white/5 text-primary focus:ring-primary/20 focus:ring-offset-0 cursor-pointer" />
          </div>

          <!-- Buttons -->
          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Guardar Alerta
            </button>
            <button
              type="button"
              @click="showConfigModal = false"
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
import { ref, computed } from 'vue';
import {
  BellIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  TrashIcon,
  EnvelopeIcon,
  ChatBubbleLeftIcon,
  InformationCircleIcon,
  CurrencyDollarIcon,
  WrenchIcon,
  UserPlusIcon,
  BuildingOfficeIcon,
  MagnifyingGlassIcon,
  Cog6ToothIcon,
  CheckIcon,
  CalendarDaysIcon,
  DocumentCheckIcon,
  DevicePhoneMobileIcon,
  BoltIcon,
  ServerIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import Swal from 'sweetalert2';

const API_URL = '/concretos-oriente/Backend/api/v1';

interface NotificationItem {
  id: string;
  title: string;
  category: 'finanzas' | 'personal' | 'maquinaria' | 'proyectos';
  description: string;
  isUrgent: boolean;
  timeAgo: string;
  projectOrMeta: string;
  valueOrPriority: string;
  isRead: boolean;
}

interface SourceStatus {
  id: string;
  name: string;
  status: 'online' | 'syncing' | 'offline';
}

const searchTerm = ref('');
const activeTab = ref('todas');
const showConfigModal = ref(false);
const notification = ref('');

const form = ref({
  nombre: '',
  tipo_evento: 'Stock bajo',
  canales: [] as string[],
  destinatarios: [] as string[],
  umbral: 0,
  activa: true
});

const tabs = [
  { value: 'todas', label: 'Todas las Notificaciones' },
  { value: 'finanzas', label: 'Finanzas' },
  { value: 'personal', label: 'Personal' },
  { value: 'maquinaria', label: 'Maquinaria' },
  { value: 'proyectos', label: 'Proyectos' },
];

const notifications = ref<NotificationItem[]>([
  {
    id: 'NT-101',
    title: 'Retraso Crítico en Cimentación',
    category: 'proyectos',
    description: 'El Proyecto SkyTower reporta un retraso de 48h debido a falla mecánica en la excavadora principal. Requiere reprogramación inmediata de turnos.',
    isUrgent: true,
    timeAgo: 'Hace 15 min',
    projectOrMeta: 'SkyTower Phase 1',
    valueOrPriority: 'Urgente',
    isRead: false
  },
  {
    id: 'NT-102',
    title: 'Factura Aprobada',
    category: 'finanzas',
    description: 'La factura #F-2024-089 (Suministros de Acero Corrugado) ha sido validada y aprobada por el departamento contable principal.',
    isUrgent: false,
    timeAgo: 'Hace 2 horas',
    projectOrMeta: 'Compra de Aceros',
    valueOrPriority: 'Q12,450.00',
    isRead: false
  },
  {
    id: 'NT-103',
    title: 'Mantenimiento Preventivo Requerido',
    category: 'maquinaria',
    description: 'La Grúa Torre GT-04 requiere cambio de aceite hidráulico de alta densidad y revisión de tensión de cables mecánicos en los próximos 3 días.',
    isUrgent: false,
    timeAgo: 'Hace 5 horas',
    projectOrMeta: 'GT-04',
    valueOrPriority: 'Medio',
    isRead: false
  },
  {
    id: 'NT-104',
    title: 'Nuevo Ingreso a Equipo de Obra',
    category: 'personal',
    description: 'Roberto Gómez se ha unido oficialmente al proyecto residencial como Inspector de Seguridad Industrial certificado en normas ISO 4501.',
    isUrgent: false,
    timeAgo: 'Ayer, 09:30 AM',
    projectOrMeta: 'ID: #8900',
    valueOrPriority: 'Informativo',
    isRead: true
  },
  {
    id: 'NT-105',
    title: 'Vencimiento Próximo de Póliza',
    category: 'finanzas',
    description: 'La póliza de vicios ocultos para el Puente Interconector Sur vencerá en 30 días calendario. Solicite renovación regulatoria.',
    isUrgent: false,
    timeAgo: 'Hace 2 días',
    projectOrMeta: 'Póliza de Fianzas',
    valueOrPriority: 'Q45,000.00',
    isRead: true
  },
  {
    id: 'NT-106',
    title: 'Rendimiento semanal superado',
    category: 'proyectos',
    description: 'La cuadrilla de acabados arquitectónicos en Urbanización Las Colinas completó el doble de metrajes planificados para esta quincena.',
    isUrgent: false,
    timeAgo: 'Hace 3 días',
    projectOrMeta: 'Colinas',
    valueOrPriority: 'Alta Eficiencia',
    isRead: true
  }
]);

const criticalCount = computed(() => notifications.value.filter(n => n.isUrgent && !n.isRead).length);
const unreadCount = computed(() => notifications.value.filter(n => !n.isRead).length);
const financeCount = computed(() => notifications.value.filter(n => n.category === 'finanzas').length);

const filteredNotifications = computed(() => {
  return notifications.value.filter(n => {
    const matchesSearch = 
      n.title.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      n.projectOrMeta.toLowerCase().includes(searchTerm.value.toLowerCase());
    
    const matchesTab = activeTab.value === 'todas' || n.category === activeTab.value;
    
    return matchesSearch && matchesTab;
  });
});

const getCategoryStyles = (nt: NotificationItem) => {
  switch (nt.category) {
    case 'finanzas':   return { bg: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' };
    case 'personal':   return { bg: 'bg-amber-500/10 text-amber-400 border border-amber-500/20' };
    case 'maquinaria': return { bg: 'bg-sky-500/10 text-sky-400 border border-sky-500/20' };
    case 'proyectos':  return { bg: 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' };
    default:           return { bg: 'bg-white/5 text-white/50 border border-white/5' };
  }
};

const showToast = (msg: string) => {
  notification.value = msg;
  setTimeout(() => { notification.value = ''; }, 4500);
};

const handleMarkAllRead = () => {
  notifications.value = notifications.value.map(nt => ({ ...nt, isRead: true }));
  showToast('Todas las notificaciones marcadas como leídas');
};

const handleMarkAsRead = (id: string) => {
  const nt = notifications.value.find(n => n.id === id);
  if (nt && !nt.isRead) {
    nt.isRead = true;
    showToast('Notificación leída');
  }
};

const handleDeleteNotification = (id: string) => {
  notifications.value = notifications.value.filter(nt => nt.id !== id);
  showToast('Notificación removida del buzón');
};

async function handleSaveConfig() {
  try {
    const res = await axios.post(`${API_URL}/alerts_config`, {
      nombre: form.value.nombre,
      tipo_evento: form.value.tipo_evento,
      canales: form.value.canales,
      destinatarios: form.value.destinatarios,
      umbral: form.value.umbral,
      activa: form.value.activa ? 1 : 0
    });
    
    if (res.data.status === 'success') {
      showConfigModal.value = false;
      showToast('Configuración guardada en la base de datos');
      
      // Reset form
      form.value = {
        nombre: '',
        tipo_evento: 'Stock bajo',
        canales: [],
        destinatarios: [],
        umbral: 0,
        activa: true
      };
    } else {
      Swal.fire('Error', res.data.message || 'Error al guardar la configuración', 'error');
    }
  } catch (err: any) {
    Swal.fire('Error', 'Hubo un error de conexión', 'error');
  }
}
</script>
