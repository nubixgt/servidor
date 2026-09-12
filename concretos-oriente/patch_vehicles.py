import re
import os

filepath = r'C:\Users\cvall\Desktop\GitHub\servidor\concretos-oriente\Frontend\src\views\admin\Vehicles.vue'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update the tab buttons
content = content.replace(
    '''<template v-if="activeTab === 'register'">''',
    '''<!-- ═══════════════════════════════════ TAB: BITÁCORA DIARIA ═══════════════════════════════════ -->
    <template v-else-if="activeTab === 'log'">
      <div class="glass-card rounded-[32px] overflow-hidden border border-white/10">
        <!-- Top bar with New Log button -->
        <div class="p-6 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex flex-1 gap-3">
            <div class="relative w-full md:w-80">
              <MagnifyingGlassIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-white/40" />
              <input 
                v-model="searchLog" 
                type="text" 
                placeholder="Buscar vehículo o piloto..." 
                class="w-full bg-black/20 border border-white/10 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all"
              />
            </div>
            <select v-model="filterLogProject" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-xs font-bold text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[180px]">
              <option value="">Todos los Proyectos</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>

          <button
            @click="openLogModal"
            class="px-6 py-3 bg-primary hover:opacity-90 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 shadow-lg shadow-primary/30 shrink-0"
          >
            <PlusIcon class="w-4 h-4" /> Registrar Bitácora
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-left">
            <thead>
              <tr class="border-b border-white/5 bg-white/[0.02]">
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Fecha</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Vehículo</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Proyecto</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Kilometraje</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Piloto</th>
                <th v-if="authStore.userRole === 'admin'" class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Creador</th>
                <th class="px-6 py-5 text-right text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="loadingLogs">
                <td colspan="7" class="px-6 py-10 text-center text-white/50 text-xs font-bold">Cargando bitácoras...</td>
              </tr>
              <tr v-else-if="filteredLogs.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-white/40 font-black uppercase tracking-widest text-xs">No hay bitácoras registradas</td>
              </tr>
              <tr v-for="log in paginatedLogs" :key="log.id" class="hover:bg-white/5 transition-all">
                <td class="px-6 py-4 text-xs font-bold text-white/80">{{ formatDate(log.fecha) }}</td>
                <td class="px-6 py-4 font-black uppercase text-sm text-white">{{ log.vehiculo_nombre }}</td>
                <td class="px-6 py-4 text-xs font-bold text-primary">{{ log.proyecto_nombre || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <p class="text-[11px] font-bold text-white/50">Ini: <span class="text-white">{{ log.kilometraje_inicial }}</span></p>
                  <p class="text-[11px] font-bold text-white/50">Fin: <span class="text-white">{{ log.kilometraje_final }}</span></p>
                </td>
                <td class="px-6 py-4 text-xs font-bold text-white/80">{{ log.piloto_nombre || 'N/A' }}</td>
                <td v-if="authStore.userRole === 'admin'" class="px-6 py-4 text-xs font-bold text-white/60">{{ log.creado_por_nombre || 'N/A' }}</td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button @click="openViewLog(log)" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Visualizar">
                      <EyeIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination for logs -->
        <div v-if="filteredLogs.length > logsPerPage" class="p-4 border-t border-white/5 flex items-center justify-between bg-white/[0.02]">
          <span class="text-xs font-bold text-white/40">Mostrando {{ paginatedLogs.length }} de {{ filteredLogs.length }}</span>
          <div class="flex gap-1">
            <button
              v-for="page in totalLogPages"
              :key="page"
              @click="currentLogPage = page"
              :class="['w-8 h-8 rounded-lg text-xs font-black transition-all flex items-center justify-center', currentLogPage === page ? 'bg-primary text-white' : 'hover:bg-white/10 text-white/50']"
            >
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════ REGISTRAR / EDITAR ═══ -->
    <template v-if="activeTab === 'register'">'''
)

# 2. Update Modal Log Content
content = content.replace(
    '''<!-- 📋 MODAL BITÁCORA -->''',
    '''<!-- 📋 MODAL VIEW LOG -->
    <Transition name="modal">
      <div v-if="showViewLogModal && viewLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showViewLogModal = false" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-lg bg-slate-950 border border-white/10 rounded-3xl p-8 shadow-2xl z-10 text-white space-y-6">
          <div class="flex items-center justify-between border-b border-white/5 pb-4">
            <h4 class="text-lg font-black italic uppercase flex items-center gap-2">
              <ClipboardDocumentListIcon class="w-5 h-5 text-primary" /> Detalles Bitácora
            </h4>
            <button @click="showViewLogModal = false" class="p-1.5 hover:bg-white/10 rounded-lg text-white/50 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Vehículo</p>
                <p class="text-sm font-bold text-white">{{ viewLog.vehiculo_nombre }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Proyecto</p>
                <p class="text-sm font-bold text-primary">{{ viewLog.proyecto_nombre || 'N/A' }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Fecha</p>
                <p class="text-sm font-bold text-white">{{ formatDate(viewLog.fecha) }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Piloto</p>
                <p class="text-sm font-bold text-white">{{ viewLog.piloto_nombre || 'N/A' }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Km Inicial</p>
                <p class="text-sm font-bold text-white">{{ viewLog.kilometraje_inicial }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Km Final</p>
                <p class="text-sm font-bold text-white">{{ viewLog.kilometraje_final }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Combustible (Gal)</p>
                <p class="text-sm font-bold text-white">{{ viewLog.combustible_consumido }}</p>
              </div>
              <div class="p-4 bg-white/5 rounded-2xl">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Precio Renta</p>
                <p class="text-sm font-bold text-white">{{ viewLog.precio_renta }}</p>
              </div>
            </div>
            <div v-if="viewLog.observaciones" class="p-4 bg-white/5 rounded-2xl mt-4">
              <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1">Observaciones</p>
              <p class="text-xs font-bold text-white/80 whitespace-pre-wrap">{{ viewLog.observaciones }}</p>
            </div>
            <div v-if="viewLog.reportar_averia" class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl mt-4">
              <p class="text-[10px] font-black text-rose-400/70 uppercase tracking-widest mb-1">Avería Reportada</p>
              <p class="text-xs font-bold text-rose-400 whitespace-pre-wrap">{{ viewLog.reportar_averia }}</p>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- 📋 MODAL BITÁCORA -->'''
)

# Replace the inner log modal content to adapt to Kilometros
old_modal = '''<div class="space-y-4">
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Vehículo</label>
                <select v-model="logForm.vehiculo_id" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="" disabled>Seleccione un vehículo...</option>
                  <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }}</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Piloto / Operador</label>
                <select v-model="logForm.piloto_id" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="">Sin piloto asignado</option>
                  <option v-for="p in personnel" :key="p.id" :value="p.id">{{ p.nombres }} {{ p.apellidos }}</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Estatus del Vehículo</label>
                <select v-model="logForm.estatus_vehiculo" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="Activo">Activo / Operativo</option>
                  <option value="Inactivo">Inactivo / Fuera de servicio</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Reportar Avería (Opcional)</label>
                <textarea v-model="logForm.reportar_averia" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-sm font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all resize-none" placeholder="Describa si el vehículo presenta fallas..."></textarea>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Observaciones (Opcional)</label>
                <textarea v-model="logForm.observaciones" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-sm font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all resize-none" placeholder="Notas adicionales de la bitácora..."></textarea>
              </div>
            </div>'''

new_modal = '''<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Fecha</label>
                <input v-model="logForm.fecha" type="date" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Vehículo</label>
                <select v-model="logForm.vehiculo_id" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="" disabled>Seleccione un vehículo...</option>
                  <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }}</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Proyecto</label>
                <select v-model="logForm.proyecto_id" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="">Seleccione Proyecto (Opcional)</option>
                  <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Piloto / Operador</label>
                <select v-model="logForm.piloto_id" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="">Sin piloto asignado</option>
                  <option v-for="p in personnel" :key="p.id" :value="p.id">{{ p.nombres }} {{ p.apellidos }}</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Kilometraje Inicial</label>
                <input v-model="logForm.kilometraje_inicial" type="number" step="0.01" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Kilometraje Final</label>
                <input v-model="logForm.kilometraje_final" type="number" step="0.01" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Combustible (Gal)</label>
                <input v-model="logForm.combustible_consumido" type="number" step="0.01" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Precio Renta (Q)</label>
                <input v-model="logForm.precio_renta" type="number" step="0.01" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Estatus del Vehículo</label>
                <select v-model="logForm.estatus_vehiculo" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="Activo">Activo / Operativo</option>
                  <option value="Inactivo">Inactivo / Fuera de servicio</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Reportar Avería</label>
                <textarea v-model="logForm.reportar_averia" rows="1" class="w-full bg-white/5 border border-white/10 rounded-xl p-3 text-sm font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all resize-none" placeholder="Opcional"></textarea>
              </div>
              <div class="col-span-full">
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Observaciones</label>
                <textarea v-model="logForm.observaciones" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl p-4 text-sm font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all resize-none" placeholder="Opcional"></textarea>
              </div>
            </div>'''
content = content.replace(old_modal, new_modal)

# 3. Add variables and computed to scripts
script_insert = '''const logs = ref([]);
const loadingLogs = ref(false);
const searchLog = ref('');
const filterLogProject = ref('');
const projects = ref([]);
const showViewLogModal = ref(false);
const viewLog = ref(null);
const currentLogPage = ref(1);
const logsPerPage = 10;

const filteredLogs = computed(() => {
  return logs.value.filter(l => {
    const searchVal = searchLog.value.toLowerCase();
    const matchSearch = (l.vehiculo_nombre && l.vehiculo_nombre.toLowerCase().includes(searchVal)) ||
                        (l.piloto_nombre && l.piloto_nombre.toLowerCase().includes(searchVal));
    const matchProj = filterLogProject.value === "" || String(l.proyecto_id) === String(filterLogProject.value);
    return matchSearch && matchProj;
  });
});

const paginatedLogs = computed(() => {
  const start = (currentLogPage.value - 1) * logsPerPage;
  return filteredLogs.value.slice(start, start + logsPerPage);
});

const totalLogPages = computed(() => Math.ceil(filteredLogs.value.length / logsPerPage));

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  // adjust to local UTC
  const adjustedDate = new Date(date.getTime() + date.getTimezoneOffset() * 60000);
  return adjustedDate.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};
'''

content = content.replace(
    "const statusOptions = [",
    script_insert + "\nconst statusOptions = ["
)

# 4. Update form default object and logic
old_log_form = "const logForm = ref({ vehiculo_id: '', piloto_id: '', estatus_vehiculo: 'Activo', envio_servicio: '', reportar_averia: '', observaciones: '' });"
new_log_form = "const logForm = ref({ fecha: new Date().toISOString().split('T')[0], vehiculo_id: '', proyecto_id: '', piloto_id: '', estatus_vehiculo: 'Activo', kilometraje_inicial: 0, kilometraje_final: 0, combustible_consumido: 0, precio_renta: 0, envio_servicio: '', reportar_averia: '', observaciones: '' });"
content = content.replace(old_log_form, new_log_form)

old_open_modal = '''const openLogModal = () => {
  logForm.value = { vehiculo_id: '', piloto_id: '', estatus_vehiculo: 'En Funcionamiento', envio_servicio: '', reportar_averia: '', observaciones: '' };
  showLogModal.value = true;
};'''
new_open_modal = '''const openLogModal = () => {
  logForm.value = { fecha: new Date().toISOString().split('T')[0], vehiculo_id: '', proyecto_id: '', piloto_id: '', estatus_vehiculo: 'Activo', kilometraje_inicial: 0, kilometraje_final: 0, combustible_consumido: 0, precio_renta: 0, envio_servicio: '', reportar_averia: '', observaciones: '' };
  showLogModal.value = true;
};

const openViewLog = (log) => {
  viewLog.value = log;
  showViewLogModal.value = true;
};

const fetchLogs = async () => {
  loadingLogs.value = true;
  try {
    const res = await fetch(`${BASE_URL}/vehicle-log`, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }});
    const data = await res.json();
    if (data.status === 'success') logs.value = data.data;
  } catch (err) { console.error(err); }
  loadingLogs.value = false;
};

const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }});
    const data = await res.json();
    if (data.status === 'success') projects.value = data.data;
  } catch (err) { console.error(err); }
};
'''
content = content.replace(old_open_modal, new_open_modal)

# 5. On mounted add fetchLogs and fetchProjects
content = content.replace("fetchVehicles();", "fetchVehicles(); fetchLogs(); fetchProjects();")
# and in submitLog update fetchLogs
content = content.replace("fetchVehicles();", "fetchVehicles(); fetchLogs();", 2) # in submitLog

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated successfully")
