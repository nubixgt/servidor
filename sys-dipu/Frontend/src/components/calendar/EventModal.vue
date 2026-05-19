<template>
<Teleport to="body">
<Transition name="modal">
<div v-if="show" class="em-overlay" @click.self="$emit('close')">
  <div class="em-card" :style="{ '--active-color': activeCat?.color || '#3b82f6' }">

    <!-- Nueva Cabecera Simple -->
    <div class="em-header">
      <div class="em-header-left">
        <span class="material-symbols-outlined em-header-icon" :style="{ color: 'var(--active-color)' }">
          {{ editing ? 'edit_calendar' : 'event_available' }}
        </span>
        <div>
          <h2 class="em-header-title">{{ editing ? 'Editar Evento' : 'Nuevo Evento' }}</h2>
          <p class="em-header-sub">Completa los detalles a continuación</p>
        </div>
      </div>
      <button class="em-close" @click="$emit('close')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Contenido -->
    <div class="em-layout">
      <div class="em-field">
        <label class="em-label">Título del evento</label>
        <div class="em-input-wrap">
          <span class="material-symbols-outlined em-input-icon">title</span>
          <input v-model="form.title" type="text" placeholder="Ej. Reunión de planeación..." class="em-input with-icon" />
        </div>
      </div>

      <div class="em-field-row">
        <div class="em-field w-half">
          <label class="em-label">Fecha programada</label>
          <div class="em-input-wrap">
            <span class="material-symbols-outlined em-input-icon">calendar_month</span>
            <input v-model="form.date" type="date" class="em-input with-icon" />
          </div>
        </div>

        <div class="em-field w-half">
          <label class="em-label">Clasificación</label>
          <div class="em-select-wrap">
            <select v-model="form.category" class="em-input em-select" :style="{ '--cc': activeCat?.color || '#64748b' }">
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.label }}
              </option>
            </select>
            <span class="material-symbols-outlined em-select-icon">expand_more</span>
            <div class="em-select-color-indicator" :style="{ background: activeCat?.color || 'transparent' }"></div>
          </div>
        </div>
      </div>
      
      <div class="em-field">
        <label class="em-label">Descripción detallada</label>
        <textarea v-model="form.description" rows="3" placeholder="Agrega detalles, enlaces o notas..." class="em-textarea"></textarea>
      </div>

      <div class="em-field">
        <label class="em-label">Archivos adjuntos</label>
        <div class="em-dropzone" @click="$refs.fileInput.click()">
          <input ref="fileInput" type="file" multiple class="hidden" @change="handleFiles" />
          <span class="material-symbols-outlined em-drop-icon">cloud_upload</span>
          <div class="em-drop-text">
            <strong>Haz clic para subir</strong> o arrastra y suelta<br>
            <span>PDF, DOCX, JPG (Max. 10MB)</span>
          </div>
        </div>
        <div v-if="form.files.length" class="em-files">
          <div v-for="(f, i) in form.files" :key="i" class="em-file-item">
            <span class="material-symbols-outlined em-file-icon">description</span>
            <span class="em-file-name">{{ f.name }}</span>
            <button @click="form.files.splice(i, 1)" class="em-file-del">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pie del Modal -->
    <div class="em-footer">
      <button v-if="editing" @click="$emit('delete')" class="em-btn-delete">
        Eliminar Evento
      </button>
      <div class="em-footer-right">
        <button @click="$emit('close')" class="em-btn-cancel">Cancelar</button>
        <button @click="save" :disabled="!form.title.trim()" class="em-btn-save"
          :style="{ background: activeCat?.color || '#3b82f6', boxShadow: `0 8px 20px ${activeCat?.color || '#3b82f6'}40` }">
          {{ editing ? 'Guardar Cambios' : 'Crear Evento' }}
          <span class="material-symbols-outlined">arrow_forward</span>
        </button>
      </div>
    </div>

  </div>
</div>
</Transition>
</Teleport>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'

const props = defineProps({ show: Boolean, editing: Object, date: String, categories: Array })
const emit = defineEmits(['close', 'save', 'delete'])

const form = reactive({ title: '', date: '', category: 'iniciativas', description: '', files: [] })

const activeCat = computed(() => props.categories?.find(c => c.id === form.category))
const activeCatStyle = computed(() => ({
  background: activeCat.value
    ? `linear-gradient(135deg, ${activeCat.value.color}, ${activeCat.value.color}dd)`
    : 'linear-gradient(135deg, #0ea5e9, #3b82f6)'
}))

watch(() => props.show, (v) => {
  if (v && props.editing) {
    Object.assign(form, {
      title: props.editing.title,
      date: props.editing.date,
      category: props.editing.category,
      description: props.editing.description || '',
      files: [...(props.editing.files || [])]
    })
  } else if (v) {
    Object.assign(form, { title: '', date: props.date || '', category: 'iniciativas', description: '', files: [] })
  }
})

function handleFiles(e) {
  form.files.push(...Array.from(e.target.files).map(f => ({ name: f.name, size: f.size })))
}
function save() {
  if (form.title.trim()) emit('save', { ...form, files: [...form.files] })
}
</script>

<style scoped>
/* Transición */
.modal-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from  { opacity: 0; transform: scale(0.95) translateY(10px); }
.modal-leave-to    { opacity: 0; transform: scale(0.98) translateY(5px); }
.modal-enter-from .em-overlay, .modal-leave-to .em-overlay { opacity: 0; }

/* Overlay */
.em-overlay {
  position: fixed; inset: 0; z-index: 100;
  display: flex; align-items: center; justify-content: center;
  background: rgba(14, 40, 48, 0.8);
  backdrop-filter: blur(8px);
  padding: 16px;
}

/* Card Principal */
.em-card {
  background: #216170; /* Teal MINDES Base */
  border: 1px solid #327f91; /* Teal Border */
  border-radius: 20px;
  width: 100%; max-width: 500px;
  box-shadow: 0 25px 50px -12px rgba(14, 40, 48, 0.6);
  overflow: hidden;
  display: flex; flex-direction: column;
}

/* Header */
.em-header {
  padding: 24px 28px; display: flex;
  background: #184e5b; /* Darker Teal Header */
  align-items: flex-start; justify-content: space-between;
  border-bottom: 1px solid #327f91;
}
.em-header-left { display: flex; align-items: center; gap: 16px; }
.em-header-icon {
  font-size: 28px !important;
  padding: 10px; background: rgba(255, 255, 255, 0.1); 
  border-radius: 12px;
  color: #e0f2fe; /* Light Cyan */
  border: 1px solid rgba(255, 255, 255, 0.2); 
  transition: all 0.3s ease;
  backdrop-filter: blur(4px);
}
.em-header-title { font-size: 20px; font-weight: 800; color: #ffffff; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
.em-header-sub { font-size: 13px; color: #a5d0db; margin: 4px 0 0; }

.em-close {
  width: 32px; height: 32px; border: none; background: rgba(255, 255, 255, 0.1);
  border-radius: 8px; color: #e0f2fe; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
}
.em-close:hover { background: rgba(255, 255, 255, 0.2); color: #ffffff; }

/* Layout de una columna compacta */
.em-layout {
  display: flex; flex-direction: column;
  gap: 20px; padding: 24px 28px;
}

/* Campos */
.em-field-row { display: flex; gap: 16px; }
.w-half { flex: 1; min-width: 0; }

.em-field { display: flex; flex-direction: column; gap: 6px; }
.em-label { font-size: 11px; font-weight: 800; color: #a5d0db; text-transform: uppercase; letter-spacing: 0.05em; }

/* Inputs Text / Date */
.em-input-wrap { position: relative; display: flex; align-items: center; }
.em-input {
  width: 100%; padding: 12px 14px;
  background: #184e5b; 
  border: 1px solid #327f91; 
  border-radius: 10px;
  font-size: 14px; color: #ffffff; font-weight: 500;
  transition: all 0.2s; outline: none;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.2);
  color-scheme: dark;
}
.em-input.with-icon { padding-left: 40px; }
.em-input:focus { border-color: #5ab1c5; background: #184e5b; box-shadow: 0 0 0 3px rgba(90, 177, 197, 0.2); }
.em-input::placeholder { color: #6ba7b8; font-weight: 400; }

.em-input-icon {
  position: absolute; left: 12px; font-size: 18px !important; color: #6ba7b8;
  pointer-events: none; transition: color 0.3s;
}
.em-input:focus ~ .em-input-icon, .em-input-wrap:focus-within .em-input-icon { color: #5ab1c5; }

/* Select (Clasificación) */
.em-select-wrap { position: relative; display: flex; align-items: center; }
.em-select {
  appearance: none; padding-left: 36px; padding-right: 30px; cursor: pointer;
}
.em-select:focus { border-color: #5ab1c5; background: #184e5b; }
.em-select-icon {
  position: absolute; right: 10px; font-size: 20px !important; color: #6ba7b8;
  pointer-events: none;
}
.em-select-color-indicator {
  position: absolute; left: 14px; width: 10px; height: 10px;
  border-radius: 50%; pointer-events: none; transition: background 0.3s;
}

/* Textarea */
.em-textarea {
  width: 100%; padding: 12px 14px;
  background: #184e5b; border: 1px solid #327f91; border-radius: 10px;
  font-size: 14px; color: #ffffff; resize: none; transition: all 0.2s; outline: none;
  font-family: inherit; line-height: 1.5;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.2);
}
.em-textarea:focus { border-color: #5ab1c5; background: #184e5b; box-shadow: 0 0 0 3px rgba(90, 177, 197, 0.2); }
.em-textarea::placeholder { color: #6ba7b8; }

/* Dropzone */
.em-dropzone {
  border: 2px dashed #5ab1c5; border-radius: 12px;
  padding: 24px; text-align: center; cursor: pointer;
  background: #184e5b; transition: all 0.2s;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.em-dropzone:hover { border-color: #79c6d8; background: #216170; }
.em-drop-icon {
  font-size: 28px !important; color: #ffffff; background: #327f91;
  padding: 12px; border-radius: 50%;
  transition: all 0.3s;
}
.em-dropzone:hover .em-drop-icon { color: #ffffff; background: #5ab1c5; }
.em-drop-text { font-size: 13px; color: #ffffff; line-height: 1.5; }
.em-drop-text strong { color: #79c6d8; transition: color 0.3s; }
.em-drop-text span { font-size: 11px; color: #a5d0db; }

/* Archivos adjuntos */
.em-files { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
.em-file-item {
  display: flex; align-items: center; gap: 12px;
  background: #184e5b; border: 1px solid #327f91;
  border-radius: 12px; padding: 12px;
}
.em-file-icon { font-size: 20px !important; color: #5ab1c5; }
.em-file-name { font-size: 13px; font-weight: 500; color: #ffffff; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.em-file-del {
  border: none; background: #216170; border-radius: 8px;
  width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #a5d0db; transition: all 0.2s;
}
.em-file-del:hover { background: #7f1d1d; color: #fca5a5; }

/* Footer */
.em-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 28px; background: #184e5b; border-top: 1px solid #327f91;
}
.em-footer-right { display: flex; gap: 12px; margin-left: auto; }

.em-btn-delete {
  padding: 10px 16px; background: transparent; color: #f87171;
  border: 1px solid transparent; border-radius: 10px;
  font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.em-btn-delete:hover { background: #7f1d1d; border-color: #fca5a5; }

.em-btn-cancel {
  padding: 10px 20px; background: #216170; color: #ffffff;
  border: 1px solid #327f91; border-radius: 10px;
  font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.em-btn-cancel:hover { background: #327f91; color: #ffffff; border-color: #5ab1c5; }

.em-btn-save {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 24px; color: white; border: none;
  border-radius: 12px; font-size: 14px; font-weight: 800;
  cursor: pointer; transition: all 0.2s;
}
.em-btn-save .material-symbols-outlined { font-size: 18px !important; }
.em-btn-save:hover { transform: translateY(-2px); filter: brightness(1.1); }
.em-btn-save:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none !important; }

/* Responsive */
@media (max-width: 640px) {
  .em-layout { grid-template-columns: 1fr; gap: 24px; padding: 20px; }
  .em-header { padding: 20px; }
  .em-footer { padding: 20px; flex-direction: column-reverse; gap: 16px; }
  .em-footer-right { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
  .em-btn-delete { width: 100%; text-align: center; }
}
</style>
