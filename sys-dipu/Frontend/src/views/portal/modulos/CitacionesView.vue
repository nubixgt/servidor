<template>
  <div class="space-y-6">

    <!-- HERO BANNER -->
    <div class="relative rounded-3xl overflow-hidden p-8 shadow-xl" style="background: linear-gradient(135deg, #0f3642 0%, #184e5b 40%, #216170 100%);">
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 40px 40px;"></div>
      <div class="absolute top-0 right-0 w-80 h-80 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3" style="background: rgba(90,177,197,0.15);"></div>
      <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);">
              <span class="material-symbols-outlined text-white text-xl">assignment_ind</span>
            </div>
            <span class="text-xs font-black uppercase tracking-widest" style="color:#a5d0db;">Módulo · Citaciones</span>
          </div>
          <h1 class="text-3xl font-extrabold text-white font-headline">Citaciones</h1>
          <p class="text-sm mt-1" style="color:#a5d0db;">Registro y gestión de comparecencias, convocatorias y órdenes de presentación formal.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
          <div class="text-center px-5 py-3 rounded-2xl" style="background:rgba(255,255,255,0.1);">
            <p class="text-2xl font-extrabold text-white">{{ citaciones.length }}</p>
            <p class="text-[10px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.6);">Total</p>
          </div>
          <div class="text-center px-5 py-3 rounded-2xl" style="background:rgba(255,255,255,0.1);">
            <p class="text-2xl font-extrabold text-white">{{ citaciones.filter(c=>c.estado==='Programada').length }}</p>
            <p class="text-[10px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.6);">Programadas</p>
          </div>
          <div class="text-center px-5 py-3 rounded-2xl" style="background:rgba(255,255,255,0.1);">
            <p class="text-2xl font-extrabold text-white">{{ citaciones.filter(c=>c.estado==='Anulada').length }}</p>
            <p class="text-[10px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.6);">Anuladas</p>
          </div>
          <button @click="abrirModal()" class="px-5 py-3 font-bold text-sm rounded-xl flex items-center gap-2 text-white shadow-lg transition-all hover:shadow-xl hover:-translate-y-0.5 active:scale-95" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);">
            <span class="material-symbols-outlined text-lg">add</span> Nueva Citación
          </button>
        </div>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[260px]">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
        <input v-model="busqueda" type="text" placeholder="Buscar por folio, citado o tipo..." class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
      </div>
      <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
        <button v-for="f in ['Todas','Programada','Completada','Anulada']" :key="f" @click="filtro=f"
          :class="filtro===f ? 'px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm' : 'px-4 py-2 text-on-surface-variant text-sm hover:text-on-surface transition-colors'">{{ f }}</button>
      </div>
      <button @click="abrirModal()" class="px-5 py-2.5 font-bold text-sm rounded-xl flex items-center gap-2 text-white shadow-lg transition-all hover:shadow-xl hover:-translate-y-0.5 active:scale-95" style="background:linear-gradient(135deg,#184e5b,#216170);">
        <span class="material-symbols-outlined text-lg">add</span> Nueva Citación
      </button>
    </div>

    <!-- TABLA -->
    <div class="bg-surface-container-lowest rounded-2xl overflow-hidden border border-outline-variant/20 shadow-sm">
      <table class="w-full text-left border-collapse min-w-[750px]">
        <thead>
          <tr class="bg-surface-container text-on-surface-variant text-[10px] uppercase tracking-widest font-bold border-b border-surface-container-low">
            <th class="px-6 py-4">Folio</th>
            <th class="px-6 py-4">Citado / Entidad</th>
            <th class="px-6 py-4">Tipo</th>
            <th class="px-6 py-4">Fecha y Hora</th>
            <th class="px-6 py-4">Estado</th>
            <th class="px-6 py-4 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container-low">
          <tr v-if="citacionesFiltradas.length === 0">
            <td colspan="6" class="px-6 py-14 text-center text-on-surface-variant text-sm">
              <span class="material-symbols-outlined text-4xl block mb-2 text-outline">inbox</span>
              No hay citaciones registradas
            </td>
          </tr>
          <tr v-for="c in citacionesFiltradas" :key="c.id" class="group hover:bg-surface-container-low transition-colors">
            <td class="px-6 py-5"><span class="font-mono text-xs text-on-surface-variant font-bold">{{ c.folio }}</span></td>
            <td class="px-6 py-5">
              <p class="font-bold text-sm text-on-surface">{{ c.citado }}</p>
              <p class="text-xs text-on-surface-variant mt-0.5">{{ c.descripcion }}</p>
            </td>
            <td class="px-6 py-5">
              <span class="px-3 py-1 bg-surface-container-high text-on-surface-variant text-[10px] font-black uppercase rounded-full">{{ c.tipo }}</span>
            </td>
            <td class="px-6 py-5">
              <p class="font-bold text-sm text-on-surface">{{ c.fecha }}</p>
              <p class="text-xs text-on-surface-variant">{{ c.hora }}</p>
            </td>
            <td class="px-6 py-5">
              <span :class="estadoClass(c.estado)">{{ c.estado }}</span>
            </td>
            <td class="px-6 py-5 text-right">
              <button @click="abrirModal(c)" class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100 rounded-lg hover:bg-surface-container-low" title="Editar">
                <span class="material-symbols-outlined text-[18px]">edit</span>
              </button>
              <button @click="eliminar(c.id)" class="p-2 text-on-surface-variant hover:text-error transition-colors opacity-0 group-hover:opacity-100 rounded-lg hover:bg-error/5" title="Eliminar">
                <span class="material-symbols-outlined text-[18px]">delete</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL -->
    <Teleport to="body">
      <Transition name="cit-modal">
        <div v-if="modalOpen" class="cit-overlay" @click.self="cerrarModal">
          <div class="cit-card">

            <!-- Header -->
            <div class="cit-header">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined cit-icon">assignment_ind</span>
                <div>
                  <h3 class="cit-title">{{ editando ? 'Editar Citación' : 'Nueva Citación' }}</h3>
                  <p class="cit-sub">{{ editando ? 'Modifica los datos del registro' : 'Completa los campos para registrar' }}</p>
                </div>
              </div>
              <button @click="cerrarModal" class="cit-close"><span class="material-symbols-outlined">close</span></button>
            </div>

            <!-- Body -->
            <div class="cit-body">
              <!-- Folio + Tipo -->
              <div class="flex gap-3">
                <div class="cit-field flex-1">
                  <label class="cit-label">Folio *</label>
                  <input v-model="form.folio" class="cit-input" placeholder="CIT-2024-001" />
                </div>
                <div class="cit-field flex-1">
                  <label class="cit-label">Tipo *</label>
                  <select v-model="form.tipo" class="cit-input">
                    <option value="">Seleccionar...</option>
                    <option value="Comparecencia">Comparecencia</option>
                    <option value="Convocatoria">Convocatoria</option>
                    <option value="Orden de Presentación">Orden de Presentación</option>
                    <option value="Audiencia">Audiencia</option>
                  </select>
                </div>
              </div>

              <!-- Citado -->
              <div class="cit-field">
                <label class="cit-label">Citado / Entidad *</label>
                <input v-model="form.citado" class="cit-input" placeholder="Ej. Ministerio de Economía" />
              </div>

              <!-- Descripción -->
              <div class="cit-field">
                <label class="cit-label">Descripción / Motivo</label>
                <input v-model="form.descripcion" class="cit-input" placeholder="Ej. Comparecencia por análisis presupuestario" />
              </div>

              <!-- Fecha + Hora + Estado -->
              <div class="flex gap-3 flex-wrap">
                <div class="cit-field flex-1 min-w-[140px]">
                  <label class="cit-label">Fecha *</label>
                  <input v-model="form.fecha" type="date" class="cit-input" />
                </div>
                <div class="cit-field flex-1 min-w-[140px]">
                  <label class="cit-label">Hora</label>
                  <input v-model="form.hora" class="cit-input" placeholder="10:00 AM - 12:00 PM" />
                </div>
                <div class="cit-field flex-1 min-w-[140px]">
                  <label class="cit-label">Estado</label>
                  <select v-model="form.estado" class="cit-input">
                    <option value="Programada">Programada</option>
                    <option value="Completada">Completada</option>
                    <option value="Anulada">Anulada</option>
                  </select>
                </div>
              </div>

              <!-- Notas -->
              <div class="cit-field">
                <label class="cit-label">Notas adicionales</label>
                <textarea v-model="form.notas" class="cit-input" rows="2" placeholder="Observaciones, documentos adjuntos requeridos..."></textarea>
              </div>

              <p v-if="error" class="text-red-400 text-xs font-semibold">{{ error }}</p>
            </div>

            <!-- Footer -->
            <div class="cit-footer">
              <button v-if="editando" @click="eliminar(editando.id); cerrarModal()" class="cit-btn-del">
                <span class="material-symbols-outlined text-sm">delete</span> Eliminar
              </button>
              <div class="flex gap-2 ml-auto">
                <button @click="cerrarModal" class="cit-btn-cancel">Cancelar</button>
                <button @click="guardar" class="cit-btn-save">
                  <span class="material-symbols-outlined text-sm">{{ editando ? 'save' : 'add' }}</span>
                  {{ editando ? 'Guardar cambios' : 'Registrar' }}
                </button>
              </div>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

/* ─── Datos ─── */
const citaciones = ref([
  { id: 1, folio: 'CIT-2024-044', citado: 'Ministerio de Economía',    descripcion: 'Comparecencia por análisis presupuestario',  tipo: 'Comparecencia', fecha: '2024-10-28', hora: '10:00 AM - 12:00 PM', estado: 'Programada',  notas: '' },
  { id: 2, folio: 'CIT-2024-038', citado: 'Municipalidad de Mixco',    descripcion: 'Informe sobre obra pública postergada',       tipo: 'Convocatoria',  fecha: '2024-10-20', hora: '14:00 PM - 16:00 PM', estado: 'Completada',  notas: '' },
  { id: 3, folio: 'CIT-2024-022', citado: 'Director General de Salud', descripcion: 'Requerimiento sobre déficit de medicamentos', tipo: 'Audiencia',     fecha: '2024-10-10', hora: '09:00 AM - 11:00 AM', estado: 'Anulada',     notas: '' },
])

/* ─── Filtros ─── */
const busqueda = ref('')
const filtro   = ref('Todas')

const citacionesFiltradas = computed(() => {
  let lista = citaciones.value
  if (filtro.value !== 'Todas') lista = lista.filter(c => c.estado === filtro.value)
  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase()
    lista = lista.filter(c =>
      c.folio.toLowerCase().includes(q) ||
      c.citado.toLowerCase().includes(q) ||
      c.tipo.toLowerCase().includes(q)
    )
  }
  return lista
})

/* ─── Modal ─── */
const modalOpen = ref(false)
const editando  = ref(null)
const error     = ref('')
const formVacio = () => ({ folio: '', citado: '', descripcion: '', tipo: '', fecha: '', hora: '', estado: 'Programada', notas: '' })
const form      = ref(formVacio())

function abrirModal(cit = null) {
  error.value   = ''
  editando.value = cit
  form.value     = cit ? { ...cit } : formVacio()
  modalOpen.value = true
}
function cerrarModal() { modalOpen.value = false }

function guardar() {
  error.value = ''
  if (!form.value.folio.trim())  { error.value = 'El folio es obligatorio.'; return }
  if (!form.value.citado.trim()) { error.value = 'El citado es obligatorio.'; return }
  if (!form.value.tipo)          { error.value = 'Selecciona un tipo.'; return }

  if (editando.value) {
    const idx = citaciones.value.findIndex(c => c.id === editando.value.id)
    if (idx !== -1) citaciones.value[idx] = { ...form.value, id: editando.value.id }
  } else {
    citaciones.value.push({ ...form.value, id: Date.now() })
  }
  cerrarModal()
}

function eliminar(id) { citaciones.value = citaciones.value.filter(c => c.id !== id) }

/* ─── Helpers ─── */
function estadoClass(estado) {
  if (estado === 'Programada') return 'px-3 py-1 bg-tertiary-container text-on-tertiary-container text-[10px] font-black rounded-full uppercase'
  if (estado === 'Completada') return 'px-3 py-1 bg-primary-container text-on-primary-container text-[10px] font-black rounded-full uppercase'
  return 'px-3 py-1 bg-error-container text-on-error-container text-[10px] font-black rounded-full uppercase'
}
</script>

<style scoped>
/* Transition */
.cit-modal-enter-active, .cit-modal-leave-active { transition: opacity 0.25s ease; }
.cit-modal-enter-from, .cit-modal-leave-to { opacity: 0; }
.cit-modal-enter-active .cit-card, .cit-modal-leave-active .cit-card { transition: transform 0.25s ease; }
.cit-modal-enter-from .cit-card, .cit-modal-leave-to .cit-card { transform: scale(0.95) translateY(12px); }

.cit-overlay {
  position: fixed; inset: 0; z-index: 200;
  display: flex; align-items: center; justify-content: center;
  background: rgba(14,40,48,0.75); backdrop-filter: blur(8px); padding: 16px;
}
.cit-card {
  background: #216170; border: 1px solid #327f91; border-radius: 20px;
  width: 100%; max-width: 580px; overflow: hidden;
  display: flex; flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(14,40,48,0.6);
}
.cit-header {
  padding: 22px 28px; background: #184e5b;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid #327f91;
}
.cit-icon  { font-size: 24px !important; padding: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; color: #e0f2fe; border: 1px solid rgba(255,255,255,0.2); }
.cit-title { font-size: 17px; font-weight: 800; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 0.04em; }
.cit-sub   { font-size: 12px; color: #a5d0db; margin: 2px 0 0; }
.cit-close { width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.1); border-radius: 8px; color: #e0f2fe; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.cit-close:hover { background: rgba(255,255,255,0.2); }
.cit-body  { display: flex; flex-direction: column; gap: 14px; padding: 22px 28px; max-height: 65vh; overflow-y: auto; }
.cit-field { display: flex; flex-direction: column; gap: 5px; }
.cit-label { font-size: 11px; font-weight: 800; color: #a5d0db; text-transform: uppercase; letter-spacing: 0.05em; }
.cit-input {
  width: 100%; padding: 10px 13px;
  background: #184e5b; border: 1px solid #327f91; border-radius: 10px;
  font-size: 14px; color: #fff; outline: none; transition: all 0.2s; font-family: inherit;
  color-scheme: dark;
}
.cit-input:focus { border-color: #5ab1c5; box-shadow: 0 0 0 3px rgba(90,177,197,0.2); }
.cit-input::placeholder { color: #6ba7b8; }
.cit-footer { display: flex; align-items: center; padding: 18px 28px; background: #184e5b; border-top: 1px solid #327f91; }
.cit-btn-del    { display:flex;align-items:center;gap:6px;padding:9px 15px;background:transparent;color:#f87171;border:1px solid transparent;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s; }
.cit-btn-del:hover { background:#7f1d1d;border-color:#fca5a5; }
.cit-btn-cancel { padding:9px 18px;background:#216170;color:#fff;border:1px solid #327f91;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s; }
.cit-btn-cancel:hover { background:#327f91; }
.cit-btn-save   { display:flex;align-items:center;gap:7px;padding:9px 20px;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:all 0.2s;background:linear-gradient(135deg,#0f3642,#216170);box-shadow:0 4px 12px rgba(14,40,48,0.3); }
.cit-btn-save:hover { filter:brightness(1.15);transform:translateY(-1px); }
</style>
