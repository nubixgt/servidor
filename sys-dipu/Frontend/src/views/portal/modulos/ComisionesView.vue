<template>
  <div class="space-y-8">
    <!-- HEADER -->
    <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
      <div class="max-w-2xl">
        <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Comisiones</h1>
        <p class="text-on-surface-variant text-lg leading-relaxed">Administración de comisiones legislativas, dictámenes y grupos de trabajo especializados.</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
          <span class="material-symbols-outlined text-xl">ios_share</span> Exportar
        </button>
        <button
          @click="openModal()"
          class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95"
        >
          <span class="material-symbols-outlined text-xl">add</span> Nueva Comisión
        </button>
      </div>
    </header>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-12 gap-6 mb-10">
      <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
        <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Comisiones</span>
        <div class="flex items-baseline gap-2">
          <span class="text-5xl font-extrabold text-on-surface font-headline">{{ comisiones.length }}</span>
          <span class="text-primary font-bold text-sm">{{ comisiones.filter(c => c.tipo === 'Permanente').length }} Permanentes</span>
        </div>
      </div>
      <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
          <div class="flex justify-between items-start">
            <span class="text-on-surface-variant font-medium text-sm">Con Dictamen</span>
            <span class="w-2 h-2 rounded-full bg-primary"></span>
          </div>
          <span class="text-3xl font-bold text-on-surface font-headline">{{ comisiones.filter(c => c.dictamenes > 0).length }}</span>
        </div>
        <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
          <div class="flex justify-between items-start">
            <span class="text-on-surface-variant font-medium text-sm">En Sesión</span>
            <span class="w-2 h-2 rounded-full bg-tertiary"></span>
          </div>
          <span class="text-3xl font-bold text-on-surface font-headline">{{ comisiones.filter(c => c.estado === 'En Sesión').length }}</span>
        </div>
        <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
          <div class="flex justify-between items-start">
            <span class="text-on-surface-variant font-medium text-sm">Sin Actividad</span>
            <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
          </div>
          <span class="text-3xl font-bold text-on-surface font-headline">{{ comisiones.filter(c => c.estado === 'Sin Actividad').length }}</span>
        </div>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="flex flex-wrap items-center gap-4 mb-8">
      <div class="relative flex-1 min-w-[300px]">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
        <input
          v-model="busqueda"
          class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none"
          placeholder="Buscar por nombre o presidente..."
          type="text"
        />
      </div>
      <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
        <button
          v-for="f in filtros" :key="f"
          @click="filtroActivo = f"
          :class="filtroActivo === f
            ? 'px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm'
            : 'px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors'"
        >{{ f }}</button>
      </div>
      <button class="p-3 bg-surface-container-low text-on-surface-variant rounded-xl hover:bg-surface-container-high transition-colors">
        <span class="material-symbols-outlined">filter_list</span>
      </button>
    </div>

    <!-- TABLA -->
    <div class="bg-surface-container-lowest rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <div class="w-full overflow-x-auto pb-4">
          <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
              <tr class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                <th class="px-8 py-5">Comisión</th>
                <th class="px-8 py-5">Presidente</th>
                <th class="px-8 py-5">Tipo</th>
                <th class="px-8 py-5">Estado</th>
                <th class="px-8 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="comisionesFiltradas.length === 0">
                <td colspan="5" class="px-8 py-12 text-center text-on-surface-variant">
                  <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                  No hay comisiones registradas
                </td>
              </tr>
              <tr
                v-for="com in comisionesFiltradas"
                :key="com.id"
                class="group hover:bg-surface-container-low transition-colors border-t border-outline-variant/30"
              >
                <td class="px-8 py-6">
                  <div class="max-w-xs">
                    <p class="font-bold text-on-surface">{{ com.nombre }}</p>
                    <p class="text-xs text-on-surface-variant">{{ com.dictamenes }} Dictámenes activos</p>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center text-[10px] font-bold">
                      {{ initials(com.presidente) }}
                    </div>
                    <span class="text-sm">{{ com.presidente }}</span>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <span :class="com.tipo === 'Permanente'
                    ? 'px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full'
                    : 'px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full'">
                    {{ com.tipo }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <span :class="estadoClass(com.estado)">{{ com.estado }}</span>
                </td>
                <td class="px-8 py-6 text-right">
                  <button @click="openModal(com)" class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                    <span class="material-symbols-outlined">edit</span>
                  </button>
                  <button @click="eliminarComision(com.id)" class="p-2 text-on-surface-variant hover:text-error transition-colors opacity-0 group-hover:opacity-100">
                    <span class="material-symbols-outlined">delete</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════════ MODAL ════════ -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showModal" class="com-overlay" @click.self="closeModal">
          <div class="com-card">

            <!-- Header Modal -->
            <div class="com-header">
              <div class="com-header-left">
                <span class="material-symbols-outlined com-header-icon">groups</span>
                <div>
                  <h2 class="com-header-title">{{ editando ? 'Editar Comisión' : 'Nueva Comisión' }}</h2>
                  <p class="com-header-sub">{{ editando ? 'Modifica los datos de la comisión' : 'Completa los datos para registrar una comisión' }}</p>
                </div>
              </div>
              <button class="com-close" @click="closeModal">
                <span class="material-symbols-outlined text-xl">close</span>
              </button>
            </div>

            <!-- Body Modal -->
            <div class="com-body">
              <!-- Nombre -->
              <div class="com-field">
                <label class="com-label">Nombre de la Comisión *</label>
                <div class="com-input-wrap">
                  <span class="material-symbols-outlined com-input-icon">label</span>
                  <input
                    v-model="form.nombre"
                    class="com-input with-icon"
                    placeholder="Ej. Comisión de Hacienda y Presupuesto"
                    type="text"
                  />
                </div>
              </div>

              <!-- Presidente + Tipo -->
              <div class="com-field-row">
                <div class="com-field flex-1">
                  <label class="com-label">Presidente *</label>
                  <div class="com-input-wrap">
                    <span class="material-symbols-outlined com-input-icon">person</span>
                    <input
                      v-model="form.presidente"
                      class="com-input with-icon"
                      placeholder="Nombre del diputado"
                      type="text"
                    />
                  </div>
                </div>
                <div class="com-field" style="flex:0 0 170px">
                  <label class="com-label">Tipo *</label>
                  <div class="com-select-wrap">
                    <select v-model="form.tipo" class="com-input com-select">
                      <option value="">Seleccionar...</option>
                      <option value="Permanente">Permanente</option>
                      <option value="Especial">Especial</option>
                    </select>
                    <span class="material-symbols-outlined com-select-icon">expand_more</span>
                  </div>
                </div>
              </div>

              <!-- Estado + Dictámenes -->
              <div class="com-field-row">
                <div class="com-field flex-1">
                  <label class="com-label">Estado</label>
                  <div class="com-select-wrap">
                    <select v-model="form.estado" class="com-input com-select">
                      <option value="En Sesión">En Sesión</option>
                      <option value="Con Dictamen">Con Dictamen</option>
                      <option value="Sin Actividad">Sin Actividad</option>
                    </select>
                    <span class="material-symbols-outlined com-select-icon">expand_more</span>
                  </div>
                </div>
                <div class="com-field" style="flex:0 0 170px">
                  <label class="com-label">Dictámenes Activos</label>
                  <div class="com-input-wrap">
                    <span class="material-symbols-outlined com-input-icon">description</span>
                    <input
                      v-model.number="form.dictamenes"
                      class="com-input with-icon"
                      type="number" min="0" placeholder="0"
                    />
                  </div>
                </div>
              </div>

              <!-- Integrantes -->
              <div class="com-field">
                <label class="com-label">Integrantes / Notas</label>
                <textarea
                  v-model="form.notas"
                  class="com-textarea"
                  rows="3"
                  placeholder="Lista de diputados integrantes, temas a tratar, notas..."
                ></textarea>
              </div>

              <!-- Error -->
              <p v-if="formError" class="text-red-400 text-sm font-medium">{{ formError }}</p>
            </div>

            <!-- Footer Modal -->
            <div class="com-footer">
              <button v-if="editando" @click="confirmarEliminar" class="com-btn-delete">
                <span class="material-symbols-outlined text-base">delete</span> Eliminar
              </button>
              <div class="com-footer-right">
                <button @click="closeModal" class="com-btn-cancel">Cancelar</button>
                <button @click="guardarComision" class="com-btn-save" :style="{ background: 'linear-gradient(135deg, #184e5b 0%, #216170 100%)' }">
                  <span class="material-symbols-outlined text-base">{{ editando ? 'save' : 'add' }}</span>
                  {{ editando ? 'Guardar cambios' : 'Crear Comisión' }}
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

/* ─── Estado ─── */
const showModal = ref(false)
const editando  = ref(null) // null = crear | objeto = editar
const formError = ref('')
const busqueda      = ref('')
const filtroActivo  = ref('Todas')
const filtros       = ['Todas', 'Permanentes', 'Especiales']

const formVacio = () => ({ nombre: '', presidente: '', tipo: '', estado: 'En Sesión', dictamenes: 0, notas: '' })
const form = ref(formVacio())

const comisiones = ref([
  { id: 1, nombre: 'Comisión de Hacienda y Presupuesto',        presidente: 'M. Villanueva', tipo: 'Permanente', estado: 'En Sesión',    dictamenes: 12, notas: '' },
  { id: 2, nombre: 'Comisión de Salud y Previsión Social',      presidente: 'R. Castillo',   tipo: 'Permanente', estado: 'Sin Actividad', dictamenes: 5,  notas: '' },
  { id: 3, nombre: 'Comisión Especial de Seguimiento Electoral', presidente: 'L. Morales',   tipo: 'Especial',   estado: 'En Sesión',    dictamenes: 3,  notas: '' },
])

/* ─── Computed ─── */
const comisionesFiltradas = computed(() => {
  let lista = comisiones.value
  if (filtroActivo.value === 'Permanentes') lista = lista.filter(c => c.tipo === 'Permanente')
  if (filtroActivo.value === 'Especiales')  lista = lista.filter(c => c.tipo === 'Especial')
  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase()
    lista = lista.filter(c => c.nombre.toLowerCase().includes(q) || c.presidente.toLowerCase().includes(q))
  }
  return lista
})

/* ─── Helpers ─── */
function initials(name) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
function estadoClass(estado) {
  if (estado === 'En Sesión')    return 'px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full'
  if (estado === 'Con Dictamen') return 'px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full'
  return 'px-3 py-1 bg-surface-container-highest text-on-surface-variant text-xs font-bold rounded-full'
}

/* ─── Modal actions ─── */
function openModal(com = null) {
  formError.value = ''
  if (com) {
    editando.value = com
    form.value = { ...com }
  } else {
    editando.value = null
    form.value = formVacio()
  }
  showModal.value = true
}
function closeModal() { showModal.value = false }

function guardarComision() {
  formError.value = ''
  if (!form.value.nombre.trim())    { formError.value = 'El nombre es obligatorio.'; return }
  if (!form.value.presidente.trim()){ formError.value = 'El presidente es obligatorio.'; return }
  if (!form.value.tipo)             { formError.value = 'Selecciona un tipo.'; return }

  if (editando.value) {
    const idx = comisiones.value.findIndex(c => c.id === editando.value.id)
    if (idx !== -1) comisiones.value[idx] = { ...form.value, id: editando.value.id }
  } else {
    comisiones.value.push({ ...form.value, id: Date.now() })
  }
  closeModal()
}

function confirmarEliminar() {
  if (confirm(`¿Eliminar la comisión "${editando.value.nombre}"?`)) {
    eliminarComision(editando.value.id)
    closeModal()
  }
}
function eliminarComision(id) {
  comisiones.value = comisiones.value.filter(c => c.id !== id)
}
</script>

<style scoped>
/* ── Transition ── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active .com-card, .modal-fade-leave-active .com-card { transition: transform 0.25s ease; }
.modal-fade-enter-from .com-card, .modal-fade-leave-to .com-card { transform: scale(0.95) translateY(12px); }

/* ── Overlay ── */
.com-overlay {
  position: fixed; inset: 0; z-index: 200;
  display: flex; align-items: center; justify-content: center;
  background: rgba(14, 40, 48, 0.75);
  backdrop-filter: blur(8px);
  padding: 16px;
}

/* ── Card ── */
.com-card {
  background: #216170;
  border: 1px solid #327f91;
  border-radius: 20px;
  width: 100%; max-width: 560px;
  box-shadow: 0 25px 50px -12px rgba(14, 40, 48, 0.6);
  overflow: hidden;
  display: flex; flex-direction: column;
}

/* ── Header ── */
.com-header {
  padding: 22px 28px;
  background: #184e5b;
  display: flex; align-items: flex-start; justify-content: space-between;
  border-bottom: 1px solid #327f91;
}
.com-header-left { display: flex; align-items: center; gap: 14px; }
.com-header-icon {
  font-size: 26px !important;
  padding: 10px; background: rgba(255,255,255,0.1);
  border-radius: 12px; color: #e0f2fe;
  border: 1px solid rgba(255,255,255,0.2);
}
.com-header-title { font-size: 18px; font-weight: 800; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 0.04em; }
.com-header-sub   { font-size: 12px; color: #a5d0db; margin: 3px 0 0; }
.com-close {
  width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.1);
  border-radius: 8px; color: #e0f2fe; cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.com-close:hover { background: rgba(255,255,255,0.2); color: #fff; }

/* ── Body ── */
.com-body { display: flex; flex-direction: column; gap: 18px; padding: 24px 28px; }

/* ── Fields ── */
.com-field      { display: flex; flex-direction: column; gap: 6px; }
.com-field-row  { display: flex; gap: 14px; }
.com-label      { font-size: 11px; font-weight: 800; color: #a5d0db; text-transform: uppercase; letter-spacing: 0.05em; }

/* ── Inputs ── */
.com-input-wrap { position: relative; display: flex; align-items: center; }
.com-input {
  width: 100%; padding: 11px 14px;
  background: #184e5b; border: 1px solid #327f91; border-radius: 10px;
  font-size: 14px; color: #fff; font-weight: 500;
  transition: all 0.2s; outline: none; color-scheme: dark;
}
.com-input.with-icon { padding-left: 40px; }
.com-input:focus { border-color: #5ab1c5; box-shadow: 0 0 0 3px rgba(90,177,197,0.2); }
.com-input::placeholder { color: #6ba7b8; font-weight: 400; }

.com-input-icon { position: absolute; left: 12px; font-size: 18px !important; color: #6ba7b8; pointer-events: none; transition: color 0.3s; }
.com-input-wrap:focus-within .com-input-icon { color: #5ab1c5; }

/* ── Select ── */
.com-select-wrap { position: relative; display: flex; align-items: center; }
.com-select { appearance: none; padding-right: 36px; cursor: pointer; }
.com-select-icon { position: absolute; right: 10px; font-size: 20px !important; color: #6ba7b8; pointer-events: none; }

/* ── Textarea ── */
.com-textarea {
  width: 100%; padding: 11px 14px;
  background: #184e5b; border: 1px solid #327f91; border-radius: 10px;
  font-size: 14px; color: #fff; resize: none; transition: all 0.2s; outline: none;
  font-family: inherit; line-height: 1.5;
}
.com-textarea:focus  { border-color: #5ab1c5; box-shadow: 0 0 0 3px rgba(90,177,197,0.2); }
.com-textarea::placeholder { color: #6ba7b8; }

/* ── Footer ── */
.com-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 28px; background: #184e5b; border-top: 1px solid #327f91;
}
.com-footer-right { display: flex; gap: 10px; margin-left: auto; }

.com-btn-delete {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 16px; background: transparent; color: #f87171;
  border: 1px solid transparent; border-radius: 10px;
  font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.com-btn-delete:hover { background: #7f1d1d; border-color: #fca5a5; }

.com-btn-cancel {
  padding: 10px 20px; background: #216170; color: #fff;
  border: 1px solid #327f91; border-radius: 10px;
  font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.com-btn-cancel:hover { background: #327f91; }

.com-btn-save {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 22px; color: white; border: none;
  border-radius: 10px; font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(14,40,48,0.3);
}
.com-btn-save:hover { filter: brightness(1.15); transform: translateY(-1px); }
</style>
