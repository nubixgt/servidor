<template>
<Teleport to="body">
<Transition name="modal">
<div v-if="show" class="em-overlay" @click.self="$emit('close')">
  <div class="em-card">

    <!-- Header con gradiente -->
    <div class="em-header" :style="activeCatStyle">
      <div class="em-header-blob"></div>
      <div class="em-header-inner">
        <div class="em-header-icon">
          <span class="material-symbols-outlined">{{ editing ? 'edit_calendar' : 'event_available' }}</span>
        </div>
        <div>
          <p class="em-header-label">{{ editing ? 'Editar evento' : 'Nuevo evento' }}</p>
          <p class="em-header-sub">{{ editing ? 'Modifica los datos del evento' : 'Completa los datos del evento' }}</p>
        </div>
      </div>
      <button class="em-close" @click="$emit('close')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Formulario -->
    <div class="em-body">

      <!-- Título -->
      <div class="em-field">
        <label class="em-label">Título</label>
        <div class="em-input-wrap">
          <span class="material-symbols-outlined em-input-icon">title</span>
          <input v-model="form.title" type="text" placeholder="Nombre del evento..." class="em-input" />
        </div>
      </div>

      <!-- Fecha -->
      <div class="em-field">
        <label class="em-label">Fecha</label>
        <div class="em-input-wrap">
          <span class="material-symbols-outlined em-input-icon">calendar_today</span>
          <input v-model="form.date" type="date" class="em-input" />
        </div>
      </div>

      <!-- Categoría -->
      <div class="em-field">
        <label class="em-label">Categoría</label>
        <div class="em-cats">
          <button v-for="cat in categories" :key="cat.id"
            type="button"
            @click="form.category = cat.id"
            :class="['em-cat-btn', { selected: form.category === cat.id }]"
            :style="{
              '--cc': cat.color,
              '--cb': cat.bg,
              '--cbrd': cat.border,
              background: form.category === cat.id ? cat.bg : '#f9fafb',
              borderColor: form.category === cat.id ? cat.color : '#e5e7eb',
              color: cat.color
            }">
            <span class="em-cat-dot" :style="{ background: cat.color }"></span>
            {{ cat.label }}
            <span v-if="form.category === cat.id" class="material-symbols-outlined em-cat-check">check_circle</span>
          </button>
        </div>
      </div>

      <!-- Descripción -->
      <div class="em-field">
        <label class="em-label">Descripción</label>
        <textarea v-model="form.description" rows="2" placeholder="Agrega detalles del evento..." class="em-textarea"></textarea>
      </div>

      <!-- Archivos -->
      <div class="em-field">
        <label class="em-label">Archivos adjuntos</label>
        <div class="em-dropzone" @click="$refs.fileInput.click()">
          <input ref="fileInput" type="file" multiple class="hidden" @change="handleFiles" />
          <span class="material-symbols-outlined em-drop-icon">cloud_upload</span>
          <p class="em-drop-text">Arrastra o haz clic para adjuntar</p>
        </div>
        <div v-if="form.files.length" class="em-files">
          <div v-for="(f, i) in form.files" :key="i" class="em-file-item">
            <span class="material-symbols-outlined em-file-icon">attach_file</span>
            <span class="em-file-name">{{ f.name }}</span>
            <button @click="form.files.splice(i, 1)" class="em-file-del">
              <span class="material-symbols-outlined">delete</span>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Acciones -->
    <div class="em-footer">
      <button v-if="editing" @click="$emit('delete')" class="em-btn-delete">
        <span class="material-symbols-outlined">delete_outline</span> Eliminar
      </button>
      <div class="em-footer-right">
        <button @click="$emit('close')" class="em-btn-cancel">Cancelar</button>
        <button @click="save" :disabled="!form.title.trim()" class="em-btn-save"
          :style="{ background: `linear-gradient(135deg, ${activeCat?.color || '#7c3aed'}, #4f46e5)` }">
          <span class="material-symbols-outlined">{{ editing ? 'save' : 'add_circle' }}</span>
          {{ editing ? 'Guardar cambios' : 'Crear evento' }}
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

const form = reactive({ title: '', date: '', category: 'trabajo', description: '', files: [] })

const activeCat = computed(() => props.categories?.find(c => c.id === form.category))
const activeCatStyle = computed(() => ({
  background: activeCat.value
    ? `linear-gradient(135deg, ${activeCat.value.color}dd, ${activeCat.value.color}99)`
    : 'linear-gradient(135deg, #7c3aed, #4f46e5)'
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
    Object.assign(form, { title: '', date: props.date || '', category: 'trabajo', description: '', files: [] })
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
.modal-enter-from  { opacity: 0; transform: scale(0.85) translateY(20px); }
.modal-leave-to    { opacity: 0; transform: scale(0.92) translateY(10px); }
.modal-enter-from .em-overlay,
.modal-leave-to   .em-overlay { opacity: 0; }

/* Overlay */
.em-overlay {
  position: fixed; inset: 0; z-index: 100;
  display: flex; align-items: center; justify-content: center;
  background: rgba(15, 10, 30, 0.55);
  backdrop-filter: blur(6px);
  padding: 16px;
}

/* Card */
.em-card {
  background: white; border-radius: 22px;
  width: 100%; max-width: 460px;
  box-shadow: 0 30px 80px rgba(0,0,0,0.25);
  overflow: hidden;
}

/* Header */
.em-header {
  position: relative; overflow: hidden;
  padding: 22px 24px; display: flex;
  align-items: center; justify-content: space-between; gap: 14px;
  transition: background 0.4s ease;
}
.em-header-blob {
  position: absolute; width: 200px; height: 200px;
  background: rgba(255,255,255,0.12); border-radius: 50%;
  top: -80px; right: -40px; filter: blur(30px);
  pointer-events: none;
}
.em-header-inner { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
.em-header-icon {
  width: 44px; height: 44px; background: rgba(255,255,255,0.2);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(6px);
}
.em-header-icon .material-symbols-outlined { font-size: 22px; color: white; }
.em-header-label { font-size: 16px; font-weight: 800; color: white; }
.em-header-sub   { font-size: 11px; color: rgba(255,255,255,0.65); margin-top: 2px; }
.em-close {
  width: 32px; height: 32px; border: none;
  background: rgba(255,255,255,0.2); border-radius: 10px;
  color: white; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s; position: relative; z-index: 1; flex-shrink: 0;
}
.em-close:hover { background: rgba(255,255,255,0.35); }
.em-close .material-symbols-outlined { font-size: 18px; }

/* Body */
.em-body { padding: 22px 24px; display: flex; flex-direction: column; gap: 18px; }

.em-field { display: flex; flex-direction: column; gap: 7px; }
.em-label {
  font-size: 10px; font-weight: 800; color: #6b7280;
  text-transform: uppercase; letter-spacing: 0.15em;
}

.em-input-wrap {
  position: relative; display: flex; align-items: center;
  background: #f9f7ff; border: 2px solid #ede9fe;
  border-radius: 12px; transition: all 0.3s ease;
}
.em-input-wrap:focus-within { border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(167,139,250,0.15); }
.em-input-icon {
  position: absolute; left: 12px; font-size: 17px !important;
  color: #a78bfa; pointer-events: none;
}
.em-input {
  width: 100%; padding: 11px 14px 11px 40px;
  border: none; background: none; outline: none;
  font-size: 13px; color: #1f2937;
}
.em-input::placeholder { color: #c4b5fd; }

/* Categorías */
.em-cats { display: flex; gap: 8px; flex-wrap: wrap; }
.em-cat-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 16px; border: 2px solid; border-radius: 20px;
  font-size: 12px; font-weight: 700; cursor: pointer;
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.em-cat-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.em-cat-btn.selected { transform: scale(1.06); box-shadow: 0 4px 14px rgba(0,0,0,0.12); }
.em-cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.em-cat-check { font-size: 14px !important; }

/* Textarea */
.em-textarea {
  width: 100%; padding: 11px 14px;
  background: #f9f7ff; border: 2px solid #ede9fe;
  border-radius: 12px; font-size: 13px; color: #1f2937;
  outline: none; resize: none; transition: all 0.3s ease;
  font-family: inherit;
}
.em-textarea:focus { border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(167,139,250,0.15); }
.em-textarea::placeholder { color: #c4b5fd; }

/* Dropzone */
.em-dropzone {
  border: 2px dashed #d8b4fe; border-radius: 14px;
  padding: 20px; text-align: center; cursor: pointer;
  background: #faf5ff; transition: all 0.3s ease;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
}
.em-dropzone:hover { border-color: #7c3aed; background: #f3e8ff; }
.em-drop-icon { font-size: 28px !important; color: #a78bfa; }
.em-drop-text { font-size: 12px; color: #a78bfa; font-weight: 600; }

/* Archivos */
.em-files { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; }
.em-file-item {
  display: flex; align-items: center; gap: 8px;
  background: #f9f7ff; border: 1px solid #ede9fe;
  border-radius: 10px; padding: 8px 12px;
}
.em-file-icon { font-size: 16px !important; color: #a78bfa; }
.em-file-name { font-size: 12px; color: #374151; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.em-file-del { border: none; background: none; cursor: pointer; color: #fca5a5; display: flex; transition: color 0.2s; }
.em-file-del:hover { color: #dc2626; }
.em-file-del .material-symbols-outlined { font-size: 16px; }

/* Footer */
.em-footer {
  display: flex; align-items: center; padding: 16px 24px;
  border-top: 1px solid #f5f3ff; gap: 10px;
}
.em-footer-right { display: flex; gap: 10px; margin-left: auto; }

.em-btn-delete {
  display: flex; align-items: center; gap: 5px;
  padding: 9px 16px; background: #fef2f2; color: #dc2626;
  border: 1px solid #fecaca; border-radius: 12px;
  font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.em-btn-delete .material-symbols-outlined { font-size: 16px !important; }
.em-btn-delete:hover { background: #fee2e2; }

.em-btn-cancel {
  padding: 9px 18px; background: #f3f4f6; color: #6b7280;
  border: none; border-radius: 12px;
  font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.em-btn-cancel:hover { background: #e5e7eb; color: #374151; }

.em-btn-save {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 22px; color: white; border: none;
  border-radius: 12px; font-size: 12px; font-weight: 800;
  cursor: pointer; transition: all 0.25s ease;
  box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);
}
.em-btn-save .material-symbols-outlined { font-size: 16px !important; }
.em-btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(109, 40, 217, 0.4); }
.em-btn-save:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
</style>
