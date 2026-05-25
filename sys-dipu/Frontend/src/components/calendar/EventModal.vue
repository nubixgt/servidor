<template>
<Teleport to="body">
<Transition name="gcal-modal">
<div v-if="show" class="gcal-overlay" @click.self="$emit('close')">
  <div class="gcal-card">
    
    <!-- Top Action Bar (Drag Handle & Close Button) -->
    <div class="gcal-top-bar">
      <span class="material-symbols-outlined gcal-drag-icon">drag_indicator</span>
      <button class="gcal-close-btn" @click="$emit('close')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Main Content Form -->
    <div class="gcal-form">
      
      <!-- Underlined Agregar Título Input -->
      <div class="gcal-title-wrap">
        <input 
          v-model="form.title" 
          type="text" 
          placeholder="Agregar título" 
          class="gcal-title-input" 
          autofocus
        />
      </div>

      <!-- Pill Tab Selectors: Evento, Tarea, Agenda de citas -->
      <div class="gcal-tabs-row">
        <button 
          type="button" 
          class="gcal-tab-pill" 
          :class="{ active: form.eventType === 'evento' }"
          @click="form.eventType = 'evento'"
        >
          Evento
        </button>
        <button 
          type="button" 
          class="gcal-tab-pill" 
          :class="{ active: form.eventType === 'tarea' }"
          @click="form.eventType = 'tarea'"
        >
          Tarea
        </button>
        <button 
          type="button" 
          class="gcal-tab-pill" 
          :class="{ active: form.eventType === 'agenda' }"
          @click="form.eventType = 'agenda'"
        >
          Agenda de citas
          <span class="gcal-new-badge">Nuevo</span>
        </button>
      </div>

      <!-- Icon-prefixed row list -->
      <div class="gcal-fields-list">
        
        <!-- Row 1: Clock Icon (Date / Time Settings) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon">schedule</span>
          <div class="gcal-field-content">
            <div class="gcal-datetime-row">
              <input v-model="form.date" type="date" class="gcal-date-input" />
              <!-- Real, Google Calendar Styled Time Selectors -->
              <div class="gcal-time-selectors">
                <div class="gcal-select-wrap">
                  <select v-model="form.timeStart" class="gcal-time-select">
                    <option value="">Todo el día</option>
                    <option v-for="t in timeOptions" :key="t.value" :value="t.value">
                      {{ t.label }}
                    </option>
                  </select>
                  <span class="material-symbols-outlined gcal-select-chevron">arrow_drop_down</span>
                </div>
                <span v-if="form.timeStart" class="gcal-time-dash">–</span>
                <div class="gcal-select-wrap" v-if="form.timeStart">
                  <select v-model="form.timeEnd" class="gcal-time-select">
                    <option v-for="t in timeOptions" :key="t.value" :value="t.value">
                      {{ t.label }}
                    </option>
                  </select>
                  <span class="material-symbols-outlined gcal-select-chevron">arrow_drop_down</span>
                </div>
              </div>
            </div>
            <p class="gcal-subtext">Zona horaria • No se repite</p>
          </div>
        </div>

        <!-- Row 2: Category Selector (Google Calendar Circle Color Dot) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon" :style="{ color: activeCat?.color || '#3b82f6' }">fiber_manual_record</span>
          <div class="gcal-field-content">
            <div class="gcal-select-wrap">
              <select v-model="form.category" class="gcal-select-input">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.label }}
                </option>
              </select>
              <span class="material-symbols-outlined gcal-select-chevron">arrow_drop_down</span>
            </div>
          </div>
        </div>

        <!-- Row 3: Guests Icon (People Input) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon">group</span>
          <div class="gcal-field-content">
            <input 
              v-model="form.guests" 
              type="text" 
              placeholder="Agregar invitados" 
              class="gcal-inline-input"
            />
          </div>
        </div>

        <!-- Row 4: Google Meet Icon (Meet Videoconference Button) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon" style="color: #1a73e8;">videocam</span>
          <div class="gcal-field-content">
            <button 
              type="button" 
              class="gcal-meet-btn" 
              @click="generateMeetLink"
            >
              <span class="material-symbols-outlined">video_call</span>
              <span>{{ form.meetLink ? 'Enlace de Google Meet generado' : 'Agregar una videoconferencia de Google Meet' }}</span>
            </button>
            <div v-if="form.meetLink" class="gcal-meet-link-box">
              <a :href="form.meetLink" target="_blank" class="gcal-meet-link">{{ form.meetLink }}</a>
              <button type="button" @click="form.meetLink = ''" class="gcal-meet-link-clear">✕</button>
            </div>
          </div>
        </div>

        <!-- Row 5: Location Icon (Location Input) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon">location_on</span>
          <div class="gcal-field-content">
            <input 
              v-model="form.location" 
              type="text" 
              placeholder="Agregar lugar" 
              class="gcal-inline-input"
            />
          </div>
        </div>

        <!-- Row 6: Description & Attachments Icon (Notes Textarea & Upload Dropzone) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon">segment</span>
          <div class="gcal-field-content">
            <textarea 
              v-model="form.description" 
              placeholder="Agregar descripción" 
              class="gcal-textarea"
              rows="2"
            ></textarea>
            
            <!-- Sleek drive-like drag/click dropzone for attachments -->
            <div 
              class="gcal-attachment-zone" 
              @click="$refs.fileInput.click()"
              @dragover.prevent
              @drop.prevent="handleDropFile"
            >
              <input ref="fileInput" type="file" multiple class="hidden" @change="handleFiles" />
              <span class="material-symbols-outlined gcal-cloud-icon">cloud_upload</span>
              <div class="gcal-attachment-text">
                <strong>Subir archivo adjunto</strong> o arrastra y suelta aquí
              </div>
            </div>
            
            <!-- Staged attachments list -->
            <div v-if="form.files.length" class="gcal-files-container">
              <div 
                v-for="(f, i) in form.files" 
                :key="i" 
                class="gcal-file-badge clickable"
                @click="downloadFile(f)"
                title="Haga clic para descargar archivo"
              >
                <span class="material-symbols-outlined gcal-badge-doc">description</span>
                <span class="gcal-file-name" :title="f.name">{{ f.name }}</span>
                <button type="button" @click.stop="form.files.splice(i, 1)" class="gcal-file-del">
                  <span class="material-symbols-outlined">close</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Row 7: Account/Calendar Profile Icon (User Profile display) -->
        <div class="gcal-field-row">
          <span class="material-symbols-outlined gcal-field-icon">event</span>
          <div class="gcal-field-content">
            <div class="gcal-profile-box">
              <span class="gcal-profile-dot" :style="{ background: activeCat?.color || '#1a73e8' }"></span>
              <span class="gcal-profile-name">Alexander López</span>
            </div>
            <p class="gcal-subtext">Ocupado • Visibilidad predeterminada • Notificar 10 minutos antes</p>
          </div>
        </div>

      </div>

    </div>

    <!-- Footer Controls -->
    <div class="gcal-footer">
      <button v-if="editing && editing.id" type="button" @click="$emit('delete')" class="gcal-more-options">
        Eliminar
      </button>
      <button v-else type="button" class="gcal-more-options">
        Más opciones
      </button>
      
      <div class="gcal-footer-right">
        <button type="button" @click="$emit('close')" class="gcal-btn-cancel">Cancelar</button>
        <button 
          type="button" 
          @click="save" 
          :disabled="!form.title.trim()" 
          class="gcal-btn-save"
        >
          Guardar
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

const form = reactive({
  title: '',
  date: '',
  category: 'iniciativas',
  description: '',
  eventType: 'evento',
  guests: '',
  location: '',
  meetLink: '',
  timeStart: '09:00',
  timeEnd: '10:00',
  files: []
})

const activeCat = computed(() => props.categories?.find(c => c.id === form.category))

// Generate 30-minute intervals
const timeOptions = computed(() => {
  const options = []
  for (let hour = 0; hour < 24; hour++) {
    for (let min of [0, 30]) {
      const hh = String(hour).padStart(2, '0')
      const mm = String(min).padStart(2, '0')
      const ampm = hour >= 12 ? 'PM' : 'AM'
      const displayHour = hour % 12 === 0 ? 12 : hour % 12
      options.push({
        value: `${hh}:${mm}`,
        label: `${displayHour}:${mm} ${ampm}`
      })
    }
  }
  return options
})

// Auto-adjust timeEnd to be 1 hour after timeStart
watch(() => form.timeStart, (newStart) => {
  if (!newStart) {
    form.timeEnd = ''
    return
  }
  if (!form.timeEnd || form.timeEnd <= newStart) {
    const [h, m] = newStart.split(':').map(Number)
    let endHour = h + 1
    let endMin = m
    if (endHour >= 24) {
      endHour = 23
      endMin = 30
    }
    const endHStr = String(endHour).padStart(2, '0')
    const endMStr = String(endMin).padStart(2, '0')
    form.timeEnd = `${endHStr}:${endMStr}`
  }
})

watch(() => props.show, (v) => {
  if (v && props.editing) {
    let parsedDesc = { text: '', eventType: 'evento', guests: '', location: '', meetLink: '', timeStart: '', timeEnd: '' };
    try {
      if (props.editing.description && props.editing.description.startsWith('{')) {
        parsedDesc = { ...parsedDesc, ...JSON.parse(props.editing.description) };
      } else {
        parsedDesc.text = props.editing.description || '';
      }
    } catch (e) {
      parsedDesc.text = props.editing.description || '';
    }

    Object.assign(form, {
      title: props.editing.title || '',
      date: props.editing.date || props.date || '',
      category: props.editing.category || 'iniciativas',
      description: parsedDesc.text,
      eventType: props.editing.eventType || parsedDesc.eventType || 'evento',
      guests: parsedDesc.guests || '',
      location: parsedDesc.location || '',
      meetLink: parsedDesc.meetLink || '',
      timeStart: parsedDesc.timeStart || '',
      timeEnd: parsedDesc.timeEnd || '',
      files: [...(props.editing.files || [])]
    })
  } else if (v) {
    Object.assign(form, {
      title: '',
      date: props.date || '',
      category: 'iniciativas',
      description: '',
      eventType: 'evento',
      guests: '',
      location: '',
      meetLink: '',
      timeStart: '09:00',
      timeEnd: '10:00',
      files: []
    })
  }
})

function generateMeetLink() {
  if (!form.meetLink) {
    const code = Math.random().toString(36).substring(2, 5) + '-' + 
                 Math.random().toString(36).substring(2, 6) + '-' + 
                 Math.random().toString(36).substring(2, 5);
    form.meetLink = `https://meet.google.com/${code}`;
  }
}

function handleFiles(e) {
  const selectedFiles = Array.from(e.target.files)
  selectedFiles.forEach(file => {
    const reader = new FileReader()
    reader.onload = (event) => {
      form.files.push({
        name: file.name,
        size: file.size,
        url: event.target.result // Base64 data URL
      })
    }
    reader.readAsDataURL(file)
  })
}

function handleDropFile(e) {
  const selectedFiles = Array.from(e.dataTransfer.files)
  selectedFiles.forEach(file => {
    const reader = new FileReader()
    reader.onload = (event) => {
      form.files.push({
        name: file.name,
        size: file.size,
        url: event.target.result // Base64 data URL
      })
    }
    reader.readAsDataURL(file)
  })
}

function save() {
  if (form.title.trim()) {
    const serializedDesc = JSON.stringify({
      text: form.description || '',
      eventType: form.eventType,
      guests: form.guests || '',
      location: form.location || '',
      meetLink: form.meetLink || '',
      timeStart: form.timeStart || '',
      timeEnd: form.timeEnd || ''
    });
    emit('save', {
      title: form.title,
      date: form.date,
      category: form.category,
      description: serializedDesc,
      files: [...form.files]
    })
  }
}

function downloadFile(file) {
  if (!file.url) return;
  try {
    const parts = file.url.split(',');
    const mime = parts[0].match(/:(.*?);/)[1];
    const bstr = atob(parts[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) {
      u8arr[n] = bstr.charCodeAt(n);
    }
    const blob = new Blob([u8arr], { type: mime });
    const blobUrl = window.URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);
  } catch (err) {
    const link = document.createElement('a');
    link.href = file.url;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}
</script>

<style scoped>
/* ═══ TRANSICIÓN AL ESTILO GOOGLE ═══ */
.gcal-modal-enter-active, .gcal-modal-leave-active {
  transition: opacity 0.15s cubic-bezier(0, 0, 0.2, 1);
}
.gcal-modal-enter-from, .gcal-modal-leave-to {
  opacity: 0;
}
.gcal-modal-enter-active .gcal-card, .gcal-modal-leave-active .gcal-card {
  transition: transform 0.18s cubic-bezier(0, 0, 0.2, 1);
}
.gcal-modal-enter-from .gcal-card, .gcal-modal-leave-to .gcal-card {
  transform: scale(0.9) translateY(12px);
}

/* ═══ OVERLAY ═══ */
.gcal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(60, 64, 67, 0.3);
  padding: 16px;
  box-sizing: border-box;
}

/* ═══ CARD ═══ */
.gcal-card {
  background: #ffffff;
  border-radius: 8px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 24px 38px 3px rgba(60, 64, 67, 0.14), 
              0 9px 46px 8px rgba(60, 64, 67, 0.12), 
              0 11px 15px -7px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  color: #3c4043;
  font-family: 'Google Sans', 'Roboto', Arial, sans-serif;
  box-sizing: border-box;
}

/* ═══ TOP ACTION BAR ═══ */
.gcal-top-bar {
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #f1f3f4;
  height: 36px;
}
.gcal-drag-icon {
  font-size: 20px !important;
  color: #5f6368;
  cursor: move;
}
.gcal-close-btn {
  width: 28px;
  height: 28px;
  border: none;
  background: none;
  border-radius: 50%;
  color: #5f6368;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.1s;
}
.gcal-close-btn:hover {
  background-color: #e8eaed;
  color: #202124;
}
.gcal-close-btn .material-symbols-outlined {
  font-size: 20px !important;
}

/* ═══ FORM ═══ */
.gcal-form {
  padding: 16px 20px 24px 16px;
  overflow-y: auto;
  max-height: 70vh;
}

/* Underlined Title */
.gcal-title-wrap {
  margin-left: 48px;
  margin-bottom: 20px;
}
.gcal-title-input {
  width: 100%;
  border: none;
  border-bottom: 1px solid #dadce0;
  padding: 6px 0;
  font-size: 22px;
  font-weight: 400;
  color: #202124;
  outline: none;
  transition: border-color 0.15s ease-in-out;
  background: transparent;
}
.gcal-title-input:focus {
  border-color: #1a73e8;
  border-bottom-width: 2px;
  padding-bottom: 5px;
}
.gcal-title-input::placeholder {
  color: #80868b;
}

/* Pills Tabs selectors */
.gcal-tabs-row {
  margin-left: 48px;
  margin-bottom: 24px;
  display: flex;
  gap: 8px;
  align-items: center;
}
.gcal-tab-pill {
  padding: 8px 16px;
  border: none;
  background: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  color: #5f6368;
  cursor: pointer;
  transition: background-color 0.1s, color 0.1s;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 6px;
  position: relative;
}
.gcal-tab-pill:hover {
  background-color: #f1f3f4;
  color: #202124;
}
.gcal-tab-pill.active {
  background-color: #e8f0fe;
  color: #1a73e8;
}
.gcal-new-badge {
  background-color: #1a73e8;
  color: #ffffff;
  font-size: 8px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 1px 4px;
  border-radius: 8px;
  letter-spacing: 0.05em;
}

/* Icon Rows list */
.gcal-fields-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.gcal-field-row {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}
.gcal-field-icon {
  font-size: 20px !important;
  color: #5f6368;
  margin-top: 6px;
  flex-shrink: 0;
  width: 28px;
  text-align: center;
}
.gcal-field-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* DateTime Settings */
.gcal-datetime-row {
  display: flex;
  align-items: center;
  gap: 12px;
}
.gcal-date-input {
  border: none;
  background: #f1f3f4;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 14px;
  color: #202124;
  outline: none;
  font-weight: 500;
  cursor: pointer;
  font-family: inherit;
  color-scheme: light;
}
.gcal-date-input:focus {
  background: #e8eaed;
}
.gcal-time-selectors {
  display: flex;
  align-items: center;
  gap: 8px;
}
.gcal-time-select {
  appearance: none;
  border: none;
  background: #f1f3f4;
  border-radius: 4px;
  padding: 8px 30px 8px 12px;
  font-size: 14px;
  color: #202124;
  outline: none;
  font-weight: 500;
  cursor: pointer;
  font-family: inherit;
}
.gcal-time-select:focus {
  background: #e8eaed;
}
.gcal-time-dash {
  font-size: 14px;
  color: #5f6368;
  font-weight: 500;
}
.gcal-subtext {
  font-size: 12px;
  color: #70757a;
  margin: 0;
}

/* Custom dynamic category select dropdown */
.gcal-select-wrap {
  position: relative;
  display: flex;
  align-items: center;
  max-width: fit-content;
}
.gcal-select-input {
  appearance: none;
  background-color: #f1f3f4;
  border: none;
  border-radius: 4px;
  padding: 8px 36px 8px 12px;
  font-size: 14px;
  font-weight: 500;
  color: #202124;
  outline: none;
  cursor: pointer;
  font-family: inherit;
}
.gcal-select-input:focus {
  background-color: #e8eaed;
}
.gcal-select-chevron {
  position: absolute;
  right: 8px;
  font-size: 20px !important;
  color: #5f6368;
  pointer-events: none;
}

/* Generic inline inputs */
.gcal-inline-input {
  width: 100%;
  border: none;
  border-bottom: 1px solid transparent;
  padding: 8px 0;
  font-size: 14px;
  color: #202124;
  outline: none;
  font-family: inherit;
  background: transparent;
}
.gcal-inline-input:focus {
  border-bottom-color: #1a73e8;
}
.gcal-inline-input::placeholder {
  color: #5f6368;
}

/* Meet Button */
.gcal-meet-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background-color: #1a73e8;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.1s;
  max-width: fit-content;
  font-family: inherit;
}
.gcal-meet-btn:hover {
  background-color: #1557b0;
}
.gcal-meet-btn .material-symbols-outlined {
  font-size: 20px !important;
}

.gcal-meet-link-box {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f1f3f4;
  padding: 6px 12px;
  border-radius: 4px;
  width: fit-content;
  max-width: 100%;
}
.gcal-meet-link {
  font-size: 13px;
  color: #1a73e8;
  text-decoration: none;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.gcal-meet-link:hover {
  text-decoration: underline;
}
.gcal-meet-link-clear {
  background: none;
  border: none;
  color: #5f6368;
  font-size: 11px;
  cursor: pointer;
  padding: 2px;
}

/* Description Textarea */
.gcal-textarea {
  width: 100%;
  border: none;
  resize: none;
  outline: none;
  font-size: 14px;
  color: #202124;
  font-family: inherit;
  padding: 8px 0;
  background: transparent;
  line-height: 1.5;
  border-bottom: 1px solid transparent;
}
.gcal-textarea:focus {
  border-bottom-color: #1a73e8;
}
.gcal-textarea::placeholder {
  color: #5f6368;
}

/* Attachment zone */
.gcal-attachment-zone {
  margin-top: 10px;
  border: 1px dashed #dadce0;
  border-radius: 6px;
  padding: 12px;
  text-align: center;
  cursor: pointer;
  background-color: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: background-color 0.1s, border-color 0.1s;
}
.gcal-attachment-zone:hover {
  background-color: #f1f3f4;
  border-color: #1a73e8;
}
.gcal-cloud-icon {
  font-size: 22px !important;
  color: #1a73e8;
}
.gcal-attachment-text {
  font-size: 12px;
  color: #3c4043;
}
.gcal-attachment-text strong {
  color: #1a73e8;
}

/* Attachment list */
.gcal-files-container {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
.gcal-file-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f1f3f4;
  padding: 4px 8px 4px 6px;
  border-radius: 12px;
  max-width: 180px;
  transition: background-color 0.15s, border-color 0.15s;
}
.gcal-file-badge.clickable {
  cursor: pointer;
}
.gcal-file-badge.clickable:hover {
  background-color: #e8eaed;
}
.gcal-badge-doc {
  font-size: 16px !important;
  color: #1a73e8;
}
.gcal-file-name {
  font-size: 11px;
  color: #3c4043;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.gcal-file-del {
  border: none;
  background: none;
  color: #5f6368;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 50%;
}
.gcal-file-del:hover {
  background-color: #dadce0;
  color: #202124;
}
.gcal-file-del .material-symbols-outlined {
  font-size: 12px !important;
}

/* Profile indicator box */
.gcal-profile-box {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
}
.gcal-profile-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}
.gcal-profile-name {
  font-size: 14px;
  font-weight: 500;
  color: #202124;
}

/* ═══ FOOTER CONTROLS ═══ */
.gcal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-top: 1px solid #dadce0;
  background-color: #ffffff;
}
.gcal-more-options {
  background: none;
  border: none;
  color: #1a73e8;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  font-family: inherit;
}
.gcal-more-options:hover {
  background-color: #f1f3f4;
}
.gcal-footer-right {
  display: flex;
  gap: 8px;
  align-items: center;
}
.gcal-btn-cancel {
  padding: 8px 16px;
  background: none;
  border: 1px solid #dadce0;
  border-radius: 4px;
  color: #1a73e8;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  font-family: inherit;
}
.gcal-btn-cancel:hover {
  background-color: #f1f3f4;
}
.gcal-btn-save {
  padding: 8px 24px;
  background-color: #1a73e8;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
}
.gcal-btn-save:hover {
  background-color: #1557b0;
  box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15);
}
.gcal-btn-save:disabled {
  background-color: #f1f3f4;
  color: #3c4043;
  opacity: 0.38;
  cursor: not-allowed;
  box-shadow: none;
}
</style>
