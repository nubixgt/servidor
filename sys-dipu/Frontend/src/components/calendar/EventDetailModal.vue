<template>
<Teleport to="body">
<Transition name="gcal-detail-modal">
<div v-if="show" class="gcal-detail-overlay" @click.self="$emit('close')">
  <div class="gcal-detail-card" v-if="event">
    
    <!-- Top Actions Bar -->
    <div class="gcal-detail-actions">
      <!-- Edit Pencil Button -->
      <button class="gcal-detail-action-btn" @click="$emit('edit', event)" title="Editar evento">
        <span class="material-symbols-outlined">edit</span>
      </button>
      <!-- Delete Trash Button -->
      <button class="gcal-detail-action-btn" @click="$emit('delete', event)" title="Eliminar evento">
        <span class="material-symbols-outlined">delete</span>
      </button>
      <!-- Vertical More Options -->
      <button class="gcal-detail-action-btn" title="Más opciones">
        <span class="material-symbols-outlined">more_vert</span>
      </button>
      <!-- Close Button -->
      <button class="gcal-detail-action-btn" @click="$emit('close')" title="Cerrar">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Details Content -->
    <div class="gcal-detail-content">
      
      <!-- Title & Date Row -->
      <div class="gcal-detail-row align-items-start">
        <span class="gcal-detail-color-box" :style="{ background: catColor }"></span>
        <div class="gcal-detail-header-text">
          <h2 class="gcal-detail-title">{{ event.title }}</h2>
          <p class="gcal-detail-date">{{ formattedDate }}</p>
        </div>
      </div>

      <!-- Icon-prefixed rows -->
      <div class="gcal-detail-rows-list">
        
        <!-- Description Row -->
        <div v-if="event.description" class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon">segment</span>
          <div class="gcal-detail-row-text">
            <p class="gcal-detail-value">{{ event.description }}</p>
          </div>
        </div>

        <!-- Guests Row -->
        <div v-if="event.guests" class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon">group</span>
          <div class="gcal-detail-row-text">
            <p class="gcal-detail-label">Invitados</p>
            <p class="gcal-detail-value">{{ event.guests }}</p>
          </div>
        </div>

        <!-- Google Meet Row -->
        <div v-if="event.meetLink" class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon" style="color: #1a73e8;">videocam</span>
          <div class="gcal-detail-row-text">
            <a :href="event.meetLink" target="_blank" class="gcal-meet-action-btn">
              <span class="material-symbols-outlined">video_call</span>
              <span>Unirse con Google Meet</span>
            </a>
          </div>
        </div>

        <!-- Location Row -->
        <div v-if="event.location" class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon">location_on</span>
          <div class="gcal-detail-row-text">
            <p class="gcal-detail-value">{{ event.location }}</p>
          </div>
        </div>

        <!-- Attachments Row -->
        <div v-if="event.files && event.files.length" class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon">attachment</span>
          <div class="gcal-detail-row-text">
            <p class="gcal-detail-label">Archivos adjuntos</p>
            <div class="gcal-detail-files-container">
              <div 
                v-for="(f, i) in event.files" 
                :key="i" 
                class="gcal-detail-file-chip"
                @click="downloadFile(f)"
                title="Descargar archivo"
              >
                <span class="material-symbols-outlined gcal-detail-file-icon">description</span>
                <div class="gcal-detail-file-info">
                  <span class="gcal-detail-file-name">{{ f.name }}</span>
                  <span class="gcal-detail-file-size">{{ formatSize(f.size) }}</span>
                </div>
                <span class="material-symbols-outlined gcal-detail-file-download">download</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Calendar Row -->
        <div class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon">event</span>
          <div class="gcal-detail-row-text">
            <p class="gcal-detail-value">Alexander López · {{ catLabel }}</p>
          </div>
        </div>

        <!-- Visibility Row -->
        <div class="gcal-detail-row">
          <span class="material-symbols-outlined gcal-detail-icon">lock</span>
          <div class="gcal-detail-row-text">
            <p class="gcal-detail-value">Público</p>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>
</Transition>
</Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ show: Boolean, event: Object, categories: Array })
defineEmits(['close', 'edit', 'delete'])

const activeCat = computed(() => props.categories?.find(c => c.id === props.event?.category))
const catColor = computed(() => activeCat.value?.color || '#3b82f6')
const catLabel = computed(() => activeCat.value?.label || props.event?.category)

const formattedDate = computed(() => {
  if (!props.event?.date) return '';
  const dateParts = props.event.date.split('-').map(Number);
  const dateObj = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
  const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
  const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  const dayName = days[dateObj.getDay()];
  const dayNum = dateObj.getDate();
  const monthName = months[dateObj.getMonth()];
  
  let dateText = `${dayName}, ${dayNum} de ${monthName}`;
  if (props.event.timeStart) {
    const formatTime = (t) => {
      const [h, m] = t.split(':').map(Number);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const displayHour = h % 12 === 0 ? 12 : h % 12;
      return `${displayHour}:${String(m).padStart(2, '0')} ${ampm}`;
    };
    const startStr = formatTime(props.event.timeStart);
    const endStr = props.event.timeEnd ? formatTime(props.event.timeEnd) : '';
    dateText += ` • ${startStr}${endStr ? ' – ' + endStr : ''}`;
  }
  return dateText;
});

function formatSize(bytes) {
  if (!bytes) return ''
  if (bytes < 1024) return `${bytes} B`
  const kb = bytes / 1024
  if (kb < 1024) return `${kb.toFixed(1)} KB`
  const mb = kb / 1024
  return `${mb.toFixed(1)} MB`
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
.gcal-detail-modal-enter-active, .gcal-detail-modal-leave-active {
  transition: opacity 0.15s cubic-bezier(0, 0, 0.2, 1);
}
.gcal-detail-modal-enter-from, .gcal-detail-modal-leave-to {
  opacity: 0;
}
.gcal-detail-modal-enter-active .gcal-detail-card, .gcal-detail-modal-leave-active .gcal-detail-card {
  transition: transform 0.18s cubic-bezier(0, 0, 0.2, 1);
}
.gcal-detail-modal-enter-from .gcal-detail-card, .gcal-detail-modal-leave-to .gcal-detail-card {
  transform: scale(0.95) translateY(8px);
}

/* ═══ OVERLAY ═══ */
.gcal-detail-overlay {
  position: fixed;
  inset: 0;
  z-index: 950;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(60, 64, 67, 0.2);
  padding: 16px;
  box-sizing: border-box;
}

/* ═══ CARD ═══ */
.gcal-detail-card {
  background: #ffffff;
  border-radius: 8px;
  width: 100%;
  max-width: 440px;
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

/* ═══ TOP ACTIONS BAR ═══ */
.gcal-detail-actions {
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  background-color: #ffffff;
  border-bottom: 1px solid #f1f3f4;
}
.gcal-detail-action-btn {
  width: 32px;
  height: 32px;
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
.gcal-detail-action-btn:hover {
  background-color: #f1f3f4;
  color: #202124;
}
.gcal-detail-action-btn .material-symbols-outlined {
  font-size: 20px !important;
}

/* ═══ DETAILS CONTENT ═══ */
.gcal-detail-content {
  padding: 20px 24px 28px;
  overflow-y: auto;
  max-height: 60vh;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Title & Date Header */
.gcal-detail-row {
  display: flex;
  align-items: center;
  gap: 16px;
}
.gcal-detail-row.align-items-start {
  align-items: flex-start;
}
.gcal-detail-color-box {
  width: 14px;
  height: 14px;
  border-radius: 4px;
  margin-top: 6px;
  flex-shrink: 0;
}
.gcal-detail-header-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.gcal-detail-title {
  font-size: 20px;
  font-weight: 500;
  color: #202124;
  margin: 0;
  line-height: 1.3;
}
.gcal-detail-date {
  font-size: 14px;
  color: #3c4043;
  margin: 0;
}

/* List of details rows */
.gcal-detail-rows-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.gcal-detail-icon {
  font-size: 20px !important;
  color: #5f6368;
  flex-shrink: 0;
  width: 24px;
  text-align: center;
}
.gcal-detail-row-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.gcal-detail-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #70757a;
  margin: 0;
}
.gcal-detail-value {
  font-size: 14px;
  color: #202124;
  margin: 0;
  line-height: 1.4;
}

/* Meet Button in preview */
.gcal-meet-action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background-color: #1a73e8;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.1s;
  max-width: fit-content;
  font-family: inherit;
  text-decoration: none;
}
.gcal-meet-action-btn:hover {
  background-color: #1557b0;
}
.gcal-meet-action-btn .material-symbols-outlined {
  font-size: 18px !important;
}

/* Attachments Styling */
.gcal-detail-files-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 6px;
}
.gcal-detail-file-chip {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: #f1f3f4;
  border: 1px solid #dadce0;
  border-radius: 8px;
  text-decoration: none;
  color: #3c4043;
  transition: background-color 0.15s, border-color 0.15s;
}
.gcal-detail-file-chip:hover {
  background: #e8eaed;
  border-color: #1a73e8;
}
.gcal-detail-file-icon {
  font-size: 24px !important;
  color: #1a73e8;
}
.gcal-detail-file-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.gcal-detail-file-name {
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #202124;
}
.gcal-detail-file-size {
  font-size: 11px;
  color: #5f6368;
}
.gcal-detail-file-download {
  font-size: 18px !important;
  color: #5f6368;
}
.gcal-detail-file-chip:hover .gcal-detail-file-download {
  color: #1a73e8;
}
</style>
