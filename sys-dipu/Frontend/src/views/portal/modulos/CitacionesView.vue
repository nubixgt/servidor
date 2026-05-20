<template>
  <div class="space-y-5">

    <!-- HEADER -->
    <div class="cit-header-section">
      <div>
        <h1 class="cit-page-title">Citaciones</h1>
        <p class="cit-page-sub">Registro y gestión de comparecencias, convocatorias y órdenes de presentación formal.</p>
      </div>
      <div class="cit-header-actions">
        <div class="cit-stat-card">
          <span class="cit-stat-num">{{ citaciones.length }}</span>
          <span class="cit-stat-label">Total</span>
        </div>
        <div class="cit-stat-card">
          <span class="cit-stat-num">{{ citaciones.filter(c=>c.estado==='Programada').length }}</span>
          <span class="cit-stat-label">Programadas</span>
        </div>
        <div class="cit-stat-card">
          <span class="cit-stat-num">{{ citaciones.filter(c=>c.estado==='Completada').length }}</span>
          <span class="cit-stat-label">Completadas</span>
        </div>
        <button @click="abrirModal()" class="cit-btn-primary">
          <span class="material-symbols-outlined">add</span> Nueva Citación
        </button>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="cit-filters-bar">
      <div class="cit-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input v-model="busqueda" type="text" placeholder="Buscar por folio, citado o tipo..." />
      </div>
      <div class="cit-tab-group">
        <button v-for="f in ['Todas','Programada','Completada','Anulada']" :key="f"
          @click="filtro=f" :class="['cit-tab', { active: filtro===f }]">{{ f }}</button>
      </div>
    </div>

    <!-- TABLA -->
    <div class="cit-table-wrap">
      <div class="cit-table-scroll">
        <table class="cit-table">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Citado / Entidad</th>
              <th>Tipo</th>
              <th>Fecha y Hora</th>
              <th>Estado</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="citacionesFiltradas.length === 0">
              <td colspan="6" class="cit-empty">
                <span class="material-symbols-outlined">inbox</span>
                No hay citaciones registradas
              </td>
            </tr>
            <tr v-for="c in citacionesFiltradas" :key="c.id" class="cit-row">
              <td><span class="cit-folio">{{ c.folio }}</span></td>
              <td>
                <p class="cit-name">{{ c.citado }}</p>
                <p class="cit-desc">{{ c.descripcion }}</p>
              </td>
              <td><span class="cit-tipo-badge">{{ c.tipo }}</span></td>
              <td>
                <p class="cit-fecha">{{ c.fecha }}</p>
                <p class="cit-hora">{{ c.hora }}</p>
              </td>
              <td><span :class="estadoClass(c.estado)">{{ c.estado }}</span></td>
              <td class="text-right">
                <button @click="abrirModal(c)" class="cit-action-btn" title="Editar">
                  <span class="material-symbols-outlined">edit</span>
                </button>
                <button @click="eliminar(c.id)" class="cit-action-btn danger" title="Eliminar">
                  <span class="material-symbols-outlined">delete</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL -->
    <Teleport to="body">
      <Transition name="cit-modal">
        <div v-if="modalOpen" class="cit-overlay" @click.self="cerrarModal">
          <div class="cit-card">

            <div class="cit-modal-header">
              <div class="cit-modal-title-wrap">
                <div class="cit-modal-icon">
                  <span class="material-symbols-outlined">assignment_ind</span>
                </div>
                <div>
                  <h3 class="cit-modal-title">{{ editando ? 'Editar Citación' : 'Nueva Citación' }}</h3>
                  <p class="cit-modal-sub">{{ editando ? 'Modifica los datos del registro' : 'Completa los campos para registrar' }}</p>
                </div>
              </div>
              <button @click="cerrarModal" class="cit-modal-close">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>

            <div class="cit-modal-body">
              <div class="cit-form-row">
                <div class="cit-field">
                  <label class="cit-label">Folio *</label>
                  <input v-model="form.folio" class="cit-input" placeholder="CIT-2024-001" />
                </div>
                <div class="cit-field">
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
              <div class="cit-field">
                <label class="cit-label">Citado / Entidad *</label>
                <input v-model="form.citado" class="cit-input" placeholder="Ej. Ministerio de Economía" />
              </div>
              <div class="cit-field">
                <label class="cit-label">Descripción / Motivo</label>
                <input v-model="form.descripcion" class="cit-input" placeholder="Ej. Comparecencia por análisis presupuestario" />
              </div>
              <div class="cit-form-row">
                <div class="cit-field">
                  <label class="cit-label">Fecha *</label>
                  <input v-model="form.fecha" type="date" class="cit-input" />
                </div>
                <div class="cit-field">
                  <label class="cit-label">Hora</label>
                  <input v-model="form.hora" class="cit-input" placeholder="10:00 AM - 12:00 PM" />
                </div>
                <div class="cit-field">
                  <label class="cit-label">Estado</label>
                  <select v-model="form.estado" class="cit-input">
                    <option value="Programada">Programada</option>
                    <option value="Completada">Completada</option>
                    <option value="Anulada">Anulada</option>
                  </select>
                </div>
              </div>
              <div class="cit-field">
                <label class="cit-label">Notas adicionales</label>
                <textarea v-model="form.notas" class="cit-input" rows="2" placeholder="Observaciones, documentos adjuntos requeridos..."></textarea>
              </div>
              <p v-if="error" class="cit-error">{{ error }}</p>
            </div>

            <div class="cit-modal-footer">
              <button v-if="editando" @click="eliminar(editando.id); cerrarModal()" class="cit-btn-del">
                <span class="material-symbols-outlined">delete</span> Eliminar
              </button>
              <div class="cit-footer-right">
                <button @click="cerrarModal" class="cit-btn-cancel">Cancelar</button>
                <button @click="guardar" class="cit-btn-save">
                  <span class="material-symbols-outlined">{{ editando ? 'save' : 'add' }}</span>
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

const citaciones = ref([
  { id: 1, folio: 'CIT-2024-044', citado: 'Ministerio de Economía',    descripcion: 'Comparecencia por análisis presupuestario',  tipo: 'Comparecencia', fecha: '2024-10-28', hora: '10:00 AM - 12:00 PM', estado: 'Programada',  notas: '' },
  { id: 2, folio: 'CIT-2024-038', citado: 'Municipalidad de Mixco',    descripcion: 'Informe sobre obra pública postergada',       tipo: 'Convocatoria',  fecha: '2024-10-20', hora: '14:00 PM - 16:00 PM', estado: 'Completada',  notas: '' },
  { id: 3, folio: 'CIT-2024-022', citado: 'Director General de Salud', descripcion: 'Requerimiento sobre déficit de medicamentos', tipo: 'Audiencia',     fecha: '2024-10-10', hora: '09:00 AM - 11:00 AM', estado: 'Anulada',     notas: '' },
])

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

const modalOpen = ref(false)
const editando  = ref(null)
const error     = ref('')
const formVacio = () => ({ folio: '', citado: '', descripcion: '', tipo: '', fecha: '', hora: '', estado: 'Programada', notas: '' })
const form      = ref(formVacio())

function abrirModal(cit = null) {
  error.value    = ''
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

function estadoClass(estado) {
  if (estado === 'Programada') return 'cit-badge programada'
  if (estado === 'Completada') return 'cit-badge completada'
  return 'cit-badge anulada'
}
</script>

<style scoped>
/* ═══ HEADER ═══ */
.cit-header-section {
  display: flex; align-items: flex-start; justify-content: space-between;
  flex-wrap: wrap; gap: 16px;
  padding-bottom: 20px; border-bottom: 1px solid #e5e7eb;
}
.cit-page-title {
  font-size: 28px; font-weight: 900; color: #111827;
  margin: 0 0 4px; font-family: 'Public Sans', sans-serif;
}
.cit-page-sub { font-size: 13px; color: #6b7280; margin: 0; max-width: 480px; }

.cit-header-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.cit-stat-card {
  display: flex; flex-direction: column; align-items: center;
  padding: 10px 18px; background: #f9fafb;
  border: 1px solid #e5e7eb; border-radius: 10px;
  min-width: 72px; text-align: center;
}
.cit-stat-num  { font-size: 20px; font-weight: 900; color: #111827; line-height: 1; }
.cit-stat-label { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 3px; }

.cit-btn-primary {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 20px; background: #111827; color: white;
  border: none; border-radius: 10px; font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.2s ease;
}
.cit-btn-primary .material-symbols-outlined { font-size: 18px !important; }
.cit-btn-primary:hover { background: #1f2937; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

/* ═══ FILTROS ═══ */
.cit-filters-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.cit-search-wrap {
  display: flex; align-items: center; gap: 8px;
  flex: 1; min-width: 220px;
  padding: 9px 14px; background: white;
  border: 1.5px solid #e5e7eb; border-radius: 10px;
  transition: all 0.2s;
}
.cit-search-wrap:focus-within { border-color: #9ca3af; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
.cit-search-wrap .material-symbols-outlined { font-size: 18px; color: #9ca3af; flex-shrink: 0; }
.cit-search-wrap input { border: none; outline: none; font-size: 13px; color: #374151; width: 100%; background: none; }
.cit-search-wrap input::placeholder { color: #d1d5db; }

.cit-tab-group {
  display: flex; background: #f3f4f6;
  border-radius: 10px; padding: 3px; gap: 2px;
}
.cit-tab {
  padding: 7px 16px; border: none; background: none;
  border-radius: 8px; font-size: 12px; font-weight: 600;
  color: #6b7280; cursor: pointer; transition: all 0.2s ease;
}
.cit-tab.active { background: white; color: #111827; box-shadow: 0 1px 4px rgba(0,0,0,0.1); font-weight: 700; }
.cit-tab:hover:not(.active) { color: #374151; }

/* ═══ TABLA ═══ */
.cit-table-wrap {
  background: white; border-radius: 14px;
  border: 1px solid #e5e7eb; overflow: hidden;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}
.cit-table-scroll { overflow-x: auto; }
.cit-table { width: 100%; border-collapse: collapse; min-width: 700px; }

.cit-table thead tr {
  background: #f9fafb; border-bottom: 1px solid #e5e7eb;
}
.cit-table thead th {
  padding: 12px 20px; font-size: 10px; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.12em;
  color: #6b7280; text-align: left;
}
.cit-table thead th.text-right { text-align: right; }

.cit-row { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
.cit-row:last-child { border-bottom: none; }
.cit-row:hover { background: #fafafa; }
.cit-table td { padding: 14px 20px; vertical-align: middle; }

.cit-folio { font-family: monospace; font-size: 11px; font-weight: 700; color: #6b7280; }
.cit-name  { font-size: 13px; font-weight: 700; color: #111827; }
.cit-desc  { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.cit-fecha { font-size: 13px; font-weight: 600; color: #374151; }
.cit-hora  { font-size: 11px; color: #9ca3af; margin-top: 2px; }

.cit-tipo-badge {
  padding: 3px 10px; background: #f3f4f6;
  border-radius: 20px; font-size: 10px; font-weight: 700;
  color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em;
}

.cit-badge {
  padding: 4px 12px; border-radius: 20px;
  font-size: 10px; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.06em;
}
.cit-badge.programada { background: #eff6ff; color: #1d4ed8; }
.cit-badge.completada { background: #f0fdf4; color: #15803d; }
.cit-badge.anulada    { background: #fef2f2; color: #dc2626; }

.cit-empty {
  padding: 48px 20px; text-align: center;
  color: #9ca3af; font-size: 13px;
}
.cit-empty .material-symbols-outlined { font-size: 36px; display: block; margin-bottom: 8px; color: #d1d5db; }

.cit-action-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border: none; background: none;
  border-radius: 8px; cursor: pointer; color: #9ca3af;
  transition: all 0.15s; opacity: 0;
}
.cit-row:hover .cit-action-btn { opacity: 1; }
.cit-action-btn:hover { background: #f3f4f6; color: #374151; }
.cit-action-btn.danger:hover { background: #fef2f2; color: #dc2626; }
.cit-action-btn .material-symbols-outlined { font-size: 17px; }

/* ═══ MODAL ═══ */
.cit-modal-enter-active, .cit-modal-leave-active { transition: opacity 0.2s ease; }
.cit-modal-enter-from, .cit-modal-leave-to { opacity: 0; }
.cit-modal-enter-active .cit-card, .cit-modal-leave-active .cit-card { transition: transform 0.2s ease; }
.cit-modal-enter-from .cit-card, .cit-modal-leave-to .cit-card { transform: scale(0.96) translateY(10px); }

.cit-overlay {
  position: fixed; inset: 0; z-index: 200;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); padding: 16px;
}
.cit-card {
  background: white; border: 1px solid #e5e7eb; border-radius: 16px;
  width: 100%; max-width: 580px;
  display: flex; flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.cit-modal-header {
  padding: 20px 24px; border-bottom: 1px solid #f3f4f6;
  display: flex; align-items: center; justify-content: space-between;
}
.cit-modal-title-wrap { display: flex; align-items: center; gap: 12px; }
.cit-modal-icon {
  width: 40px; height: 40px; background: #f3f4f6;
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
}
.cit-modal-icon .material-symbols-outlined { font-size: 20px; color: #374151; }
.cit-modal-title { font-size: 16px; font-weight: 800; color: #111827; margin: 0; }
.cit-modal-sub   { font-size: 12px; color: #9ca3af; margin: 2px 0 0; }
.cit-modal-close {
  width: 32px; height: 32px; border: none; background: #f3f4f6;
  border-radius: 8px; cursor: pointer; display: flex; align-items: center;
  justify-content: center; color: #6b7280; transition: all 0.15s;
}
.cit-modal-close:hover { background: #e5e7eb; color: #111827; }

.cit-modal-body {
  display: flex; flex-direction: column; gap: 14px;
  padding: 20px 24px; max-height: 65vh; overflow-y: auto;
}
.cit-form-row { display: flex; gap: 12px; flex-wrap: wrap; }
.cit-form-row .cit-field { flex: 1; min-width: 140px; }
.cit-field { display: flex; flex-direction: column; gap: 5px; }
.cit-label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; }
.cit-input {
  width: 100%; padding: 9px 12px;
  background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 13px; color: #111827; outline: none; transition: all 0.2s; font-family: inherit;
  box-sizing: border-box;
}
.cit-input:focus { border-color: #9ca3af; background: white; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
.cit-input::placeholder { color: #d1d5db; }
.cit-error { font-size: 12px; font-weight: 600; color: #dc2626; }

.cit-modal-footer {
  display: flex; align-items: center; padding: 16px 24px;
  border-top: 1px solid #f3f4f6; gap: 8px;
}
.cit-footer-right { display: flex; gap: 8px; margin-left: auto; }
.cit-btn-del {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 14px; background: none; color: #dc2626;
  border: 1.5px solid #fecaca; border-radius: 8px;
  font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s;
}
.cit-btn-del:hover { background: #fef2f2; }
.cit-btn-del .material-symbols-outlined { font-size: 16px; }
.cit-btn-cancel {
  padding: 8px 18px; background: #f3f4f6; color: #374151;
  border: none; border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: all 0.15s;
}
.cit-btn-cancel:hover { background: #e5e7eb; }
.cit-btn-save {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 20px; background: #111827; color: white;
  border: none; border-radius: 8px; font-size: 13px; font-weight: 700;
  cursor: pointer; transition: all 0.15s;
}
.cit-btn-save:hover { background: #1f2937; transform: translateY(-1px); }
.cit-btn-save .material-symbols-outlined { font-size: 16px; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 640px) {
  .cit-header-section { flex-direction: column; }
  .cit-header-actions { width: 100%; }
  .cit-stat-card { flex: 1; }
  .cit-btn-primary { width: 100%; justify-content: center; }
  .cit-tab-group { width: 100%; overflow-x: auto; }
  .cit-modal-footer { flex-direction: column; align-items: stretch; }
  .cit-footer-right { flex-direction: column; margin-left: 0; }
}
</style>
