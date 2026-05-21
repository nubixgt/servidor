<template>
<div class="cal-root">

  <!-- ══ BANNER ══ -->
  <div class="cal-banner">
    <div class="blob blob-a"></div>
    <div class="blob blob-b"></div>
    <div class="blob blob-c"></div>
    <div class="cal-banner-inner">
      <div>
        <h1 class="cal-main-title">Calendario Global</h1>
        <p class="cal-month-hero">{{ monthName }} <span>{{ currentYear }}</span> <em>· {{ todayDay }}</em></p>
      </div>
      <div class="cal-banner-right">
        <div class="cal-events-summary">
          <button class="cal-summary-btn" @click="showStats = !showStats">
            <span class="material-symbols-outlined">event_note</span>
            <span>{{ events.length }} evento{{ events.length !== 1 ? 's' : '' }}</span>
            <span class="material-symbols-outlined cal-summary-chevron" :class="{ open: showStats }">expand_more</span>
          </button>
          <div class="cal-stats-dropdown" v-if="showStats">
            <div class="cal-stats-item" v-for="cat in CATEGORIES" :key="cat.id">
              <span class="cal-stats-dot" :style="{ background: cat.color }"></span>
              <span class="cal-stats-label">{{ cat.label }}</span>
              <span class="cal-stats-count" :style="{ color: cat.color }">{{ events.filter(e => e.category === cat.id).length }}</span>
            </div>
            <div class="cal-stats-total">
              <span>Total</span>
              <span>{{ events.length }}</span>
            </div>
          </div>
        </div>
        <div class="cal-banner-actions">
          <div class="cal-view-toggle">
            <button :class="{ active: viewMode === 'month' }" @click="viewMode = 'month'">
              <span class="material-symbols-outlined">calendar_month</span>Mes
            </button>
            <button :class="{ active: viewMode === 'week' }" @click="viewMode = 'week'">
              <span class="material-symbols-outlined">view_week</span>Semana
            </button>
          </div>
          <button class="cal-today-btn" @click="goToday">Hoy</button>
          <button class="cal-new-btn" @click="openAdd(formatDate(new Date()))">
            <span class="material-symbols-outlined">add</span>Nuevo evento
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ CONTROLES ══ -->
  <div class="cal-controls">
    <div class="cal-nav">
      <button class="cal-nav-btn" @click="prevPeriod">
        <span class="material-symbols-outlined">chevron_left</span>
      </button>
      <span class="cal-nav-label">{{ monthName }} {{ currentYear }}</span>
      <button class="cal-nav-btn" @click="nextPeriod">
        <span class="material-symbols-outlined">chevron_right</span>
      </button>
    </div>
    <div class="cal-right-controls">
      <div class="cal-search">
        <span class="material-symbols-outlined">search</span>
        <input v-model="searchQuery" placeholder="Buscar evento..." />
        <button v-if="searchQuery" @click="searchQuery = ''">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ══ GRILLA ══ -->
  <div class="cal-grid-wrap">
    <div class="cal-weekdays">
      <div v-for="d in dayNames" :key="d">{{ d }}</div>
    </div>
    <div class="cal-grid" :class="{ 'week-mode': viewMode === 'week' }">
      <div v-for="(day, idx) in displayDays" :key="day.date"
        class="cal-cell"
        :class="{
          faded: !day.currentMonth,
          'is-today': day.isToday,
          'drag-over': dragOverDate === day.date,
          'has-events': getEventsForDate(day.date).length > 0
        }"
        :style="{ '--anim-delay': (idx * 12) + 'ms' }"
        @dragover.prevent="dragOverDate = day.date"
        @dragleave="dragOverDate = null"
        @drop.prevent="handleDrop(day.date)"
        @mouseenter="hoverDate = day.date"
        @mouseleave="hoverDate = null">

        <div class="cal-cell-top">
          <span class="cal-cell-num" :class="{ 'today-badge': day.isToday }">{{ day.day }}</span>
          <button v-show="hoverDate === day.date || day.isToday"
            class="cal-cell-add-btn" @click.stop="openAdd(day.date)">
            <span class="material-symbols-outlined">add</span>
          </button>
        </div>

        <div class="cal-events-list">
          <div v-for="evt in getEventsForDate(day.date)" :key="evt.id"
            class="cal-evt"
            :style="{ '--ec': getCatColor(evt.category), '--eb': getCatBg(evt.category) }"
            draggable="true"
            @dragstart="handleDragStart($event, evt)"
            @dragend="dragItem = null; dragOverDate = null"
            @click.stop="openEdit(evt)">
            <span class="cal-evt-dot" :style="{ background: getCatColor(evt.category) }"></span>
            <span class="cal-evt-name">{{ evt.title }}</span>
            <span v-if="evt.files?.length" class="material-symbols-outlined cal-evt-pin">attach_file</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ PRÓXIMOS EVENTOS ══ -->
  <div class="cal-upcoming" v-if="upcomingEvents.length">
    <div class="cal-upcoming-header">
      <span class="material-symbols-outlined">upcoming</span>
      Próximos eventos
    </div>
    <div class="cal-upcoming-list">
      <div v-for="evt in upcomingEvents" :key="evt.id"
        class="cal-upcoming-item"
        :style="{ '--uc': getCatColor(evt.category), '--ub': getCatBg(evt.category) }"
        @click="openEdit(evt)">
        <div class="cal-upcoming-stripe" :style="{ background: getCatColor(evt.category) }"></div>
        <div class="cal-upcoming-info">
          <p class="cal-upcoming-title">{{ evt.title }}</p>
          <p class="cal-upcoming-date">{{ formatDisplayDate(evt.date) }}</p>
        </div>
        <span class="cal-upcoming-cat" :style="{ background: getCatBg(evt.category), color: getCatColor(evt.category) }">
          {{ getCatLabel(evt.category) }}
        </span>
      </div>
    </div>
  </div>

  <EventModal :show="showModal" :editing="editingEvent" :date="selectedDate" :categories="CATEGORIES"
    @close="closeModal" @save="handleSave" @delete="handleDelete" />
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCalendar } from '../../../composables/useCalendar.js'
import EventModal from '../../../components/calendar/EventModal.vue'

const {
  currentDate, viewMode, searchQuery, activeFilters, events,
  currentYear, currentMonth, monthName, displayDays, filteredEvents,
  CATEGORIES, getEventsForDate, prevPeriod, nextPeriod, goToday,
  toggleFilter, addEvent, updateEvent, deleteEvent, moveEvent, formatDate,
  loadEvents
} = useCalendar()

onMounted(() => {
  loadEvents()
})

const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']
const hoverDate = ref(null)
const showStats = ref(false)
const showModal = ref(false)
const editingEvent = ref(null)
const selectedDate = ref('')
const dragItem = ref(null)
const dragOverDate = ref(null)

const todayDay = computed(() => {
  const now = new Date()
  const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
  return `${days[now.getDay()]} ${now.getDate()}`
})

const upcomingEvents = computed(() => {
  const today = formatDate(new Date())
  return [...events.value]
    .filter(e => e.date >= today)
    .sort((a, b) => a.date.localeCompare(b.date))
    .slice(0, 5)
})

function getCatColor(id) { return CATEGORIES.find(c => c.id === id)?.color || '#6b7280' }
function getCatBg(id)    { return CATEGORIES.find(c => c.id === id)?.bg    || '#f3f4f6' }
function getCatLabel(id) { return CATEGORIES.find(c => c.id === id)?.label || id }

function formatDisplayDate(dateStr) {
  const [y, m, d] = dateStr.split('-').map(Number)
  const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
  return `${d} ${months[m - 1]} ${y}`
}

function openAdd(date)  { editingEvent.value = null; selectedDate.value = date; showModal.value = true }
function openEdit(evt)  { editingEvent.value = evt;  selectedDate.value = evt.date; showModal.value = true }
function closeModal()   { showModal.value = false; editingEvent.value = null }

function handleSave(data) {
  if (editingEvent.value) updateEvent(editingEvent.value.id, data)
  else addEvent(data)
  closeModal()
}
function handleDelete() { if (editingEvent.value) { deleteEvent(editingEvent.value.id); closeModal() } }

function handleDragStart(e, evt) {
  dragItem.value = evt
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', evt.id)
}
function handleDrop(date) {
  if (dragItem.value) { moveEvent(dragItem.value.id, date); dragItem.value = null }
  dragOverDate.value = null
}
</script>

<style scoped>
/* ═══ ROOT ═══ */
.cal-root { max-width: 100%; font-family: inherit; }

/* ═══ BANNER ═══ */
.cal-banner {
  position: relative;
  background: white;
  border-radius: 16px;
  padding: 24px 32px 28px;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.08;
  pointer-events: none;
}
.blob-a { width: 300px; height: 300px; background: #dc2626; top: -100px; right: 5%; }
.blob-b { width: 200px; height: 200px; background: #ef4444; bottom: -60px; left: 10%; }
.blob-c { width: 150px; height: 150px; background: #f87171; top: 10px; right: 30%; }

.cal-banner-inner {
  position: relative; z-index: 1;
  display: flex; justify-content: space-between; align-items: flex-end;
  flex-wrap: wrap; gap: 20px;
}

.cal-main-title {
  font-size: 30px; font-weight: 900; color: #111827;
  letter-spacing: -0.5px; margin: 0 0 6px;
  font-family: 'Public Sans', sans-serif;
}
.cal-month-hero {
  font-size: 13px; color: #6b7280;
  font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase;
}
.cal-month-hero span { color: #9ca3af; margin-left: 6px; }
.cal-month-hero em { font-style: normal; color: #374151; margin-left: 4px; font-weight: 700; }

.cal-banner-right { display: flex; flex-direction: column; align-items: flex-end; gap: 14px; }

/* Botón resumen de eventos */
.cal-events-summary { position: relative; }

.cal-summary-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 16px;
  background: white; border: 1.5px solid #e5e7eb;
  border-radius: 10px; font-size: 13px; font-weight: 700;
  color: #374151; cursor: pointer; transition: all 0.2s ease;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.cal-summary-btn:hover { border-color: #fca5a5; background: #fff5f5; color: #dc2626; }
.cal-summary-btn .material-symbols-outlined { font-size: 18px !important; color: #dc2626; }
.cal-summary-chevron {
  font-size: 18px !important; color: #9ca3af !important;
  transition: transform 0.25s ease;
}
.cal-summary-chevron.open { transform: rotate(180deg); }

.cal-stats-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0;
  background: white; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 8px;
  min-width: 240px; z-index: 50;
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
  animation: dropIn 0.2s ease;
}
@keyframes dropIn {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}
.cal-stats-item {
  display: flex; align-items: center; gap: 10px;
  padding: 7px 10px; border-radius: 8px;
  transition: background 0.15s;
}
.cal-stats-item:hover { background: #f9fafb; }
.cal-stats-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.cal-stats-label { flex: 1; font-size: 12px; font-weight: 600; color: #374151; }
.cal-stats-count { font-size: 13px; font-weight: 800; }
.cal-stats-total {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 10px 4px; margin-top: 4px;
  border-top: 1px solid #f3f4f6;
  font-size: 12px; font-weight: 800; color: #111827;
}

/* Banner actions */
.cal-banner-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.cal-view-toggle {
  display: flex;
  background: #f3f4f6;
  border-radius: 10px; padding: 3px; gap: 2px;
}
.cal-view-toggle button {
  display: flex; align-items: center; gap: 5px;
  padding: 7px 14px; border: none; background: none;
  border-radius: 8px; font-size: 12px; font-weight: 700;
  color: #6b7280; cursor: pointer; transition: all 0.2s ease;
}
.cal-view-toggle button .material-symbols-outlined { font-size: 15px !important; }
.cal-view-toggle button.active {
  background: white; color: #111827;
  box-shadow: 0 1px 4px rgba(0,0,0,0.12);
}
.cal-view-toggle button:hover:not(.active) { color: #374151; }

.cal-today-btn {
  padding: 8px 18px;
  border: 1.5px solid #d1d5db;
  background: white; color: #374151;
  border-radius: 10px; font-size: 12px; font-weight: 700;
  cursor: pointer; transition: all 0.2s ease;
}
.cal-today-btn:hover { background: #f9fafb; border-color: #9ca3af; }

.cal-new-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 9px 20px; background: #dc2626; color: white;
  border: none; border-radius: 10px; font-size: 12px; font-weight: 800;
  cursor: pointer; transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(220,38,38,0.3);
}
.cal-new-btn .material-symbols-outlined { font-size: 16px !important; }
.cal-new-btn:hover { background: #b91c1c; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(220,38,38,0.4); }

/* ═══ CONTROLES ═══ */
.cal-controls {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px; margin-bottom: 14px;
  padding: 14px 20px; background: white;
  border-radius: 14px; border: 1px solid #e5e7eb;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}

.cal-nav { display: flex; align-items: center; gap: 10px; }
.cal-nav-btn {
  width: 34px; height: 34px; border: 1.5px solid #e5e7eb;
  background: white; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #374151; transition: all 0.2s ease;
}
.cal-nav-btn:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
.cal-nav-label {
  font-size: 15px; font-weight: 800; color: #111827;
  text-transform: uppercase; letter-spacing: 0.08em;
  min-width: 160px; text-align: center;
}

.cal-right-controls { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

/* Filtros */
.cal-filters { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.cal-filter {
  display: flex; align-items: center; gap: 5px;
  padding: 5px 12px; border: 1.5px solid #e5e7eb;
  background: white; border-radius: 20px;
  font-size: 11px; font-weight: 600; color: #6b7280;
  cursor: pointer; transition: all 0.2s ease;
}
.cal-filter:hover { border-color: #d1d5db; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.cal-filter.active {
  border-color: var(--fc); background: var(--fb); color: var(--fc);
}
.cal-filter-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.cal-filter-clear {
  display: flex; align-items: center; padding: 5px 10px;
  border: none; background: #fee2e2; border-radius: 8px;
  font-size: 11px; font-weight: 700; color: #dc2626;
  cursor: pointer; transition: all 0.2s;
}
.cal-filter-clear .material-symbols-outlined { font-size: 14px !important; }
.cal-filter-clear:hover { background: #fecaca; }

/* Búsqueda */
.cal-search {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; background: #f9fafb;
  border: 1.5px solid #e5e7eb; border-radius: 10px; transition: all 0.2s ease;
}
.cal-search:focus-within { border-color: #fca5a5; box-shadow: 0 0 0 3px rgba(220,38,38,0.08); background: white; }
.cal-search .material-symbols-outlined { font-size: 17px; color: #9ca3af; flex-shrink: 0; }
.cal-search input { border: none; background: none; outline: none; font-size: 13px; color: #374151; min-width: 130px; }
.cal-search input::placeholder { color: #d1d5db; }
.cal-search button { border: none; background: none; cursor: pointer; display: flex; color: #9ca3af; }
.cal-search button .material-symbols-outlined { font-size: 16px; }

/* ═══ GRILLA ═══ */
.cal-grid-wrap {
  background: white; border-radius: 16px;
  border: 1px solid #e5e7eb; overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.cal-weekdays {
  display: grid; grid-template-columns: repeat(7, 1fr);
  background: #111827;
}
.cal-weekdays > div {
  padding: 12px 8px; text-align: center;
  font-size: 10px; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.15em;
  color: rgba(255,255,255,0.6);
}

.cal-grid {
  display: grid; grid-template-columns: repeat(7, 1fr);
  gap: 1px; background: #e5e7eb;
}

/* Celdas */
.cal-cell {
  background: white; min-height: 120px; padding: 8px;
  position: relative; transition: background 0.15s ease;
  animation: cellAppear 0.3s ease-out var(--anim-delay, 0ms) both;
  cursor: default;
}
@keyframes cellAppear {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}

.cal-cell:hover { background: #fafafa; }
.cal-cell.faded { background: #fafafa; }
.cal-cell.faded .cal-cell-num { color: #d1d5db; }

.cal-cell.is-today {
  background: #fff5f5;
  box-shadow: inset 0 0 0 2px #fca5a5;
}
.cal-cell.drag-over {
  background: #fee2e2 !important;
  box-shadow: inset 0 0 0 2px #dc2626;
  z-index: 5;
}
.week-mode .cal-cell { min-height: 200px; }

/* Parte superior de la celda */
.cal-cell-top {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 5px;
}
.cal-cell-num {
  font-size: 13px; font-weight: 700; color: #6b7280;
  width: 26px; height: 26px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
}
.today-badge {
  background: #dc2626;
  color: white !important;
  box-shadow: 0 2px 8px rgba(220,38,38,0.4);
}

.cal-cell-add-btn {
  width: 20px; height: 20px; background: #dc2626;
  border: none; border-radius: 5px; color: white;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; opacity: 0; transform: scale(0.6);
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cal-cell:hover .cal-cell-add-btn,
.cal-cell.is-today .cal-cell-add-btn { opacity: 1; transform: scale(1); }
.cal-cell-add-btn:hover { background: #b91c1c; transform: scale(1.15) !important; }
.cal-cell-add-btn .material-symbols-outlined { font-size: 13px; }

/* Eventos */
.cal-events-list { display: flex; flex-direction: column; gap: 3px; }
.cal-evt {
  display: flex; align-items: center; gap: 5px;
  padding: 3px 6px 3px 5px;
  background: var(--eb); border-radius: 5px;
  cursor: grab; transition: all 0.15s ease;
  border: 1px solid transparent;
  overflow: hidden;
}
.cal-evt:hover {
  transform: translateX(2px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-color: var(--ec);
}
.cal-evt:active { cursor: grabbing; transform: scale(0.95); }
.cal-evt-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.cal-evt-name {
  font-size: 10px; font-weight: 600; color: #374151;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;
}
.cal-evt-pin { font-size: 11px !important; color: #9ca3af; }

/* ═══ PRÓXIMOS EVENTOS ═══ */
.cal-upcoming {
  margin-top: 14px; background: white;
  border-radius: 14px; border: 1px solid #e5e7eb;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.cal-upcoming-header {
  display: flex; align-items: center; gap: 8px;
  padding: 12px 18px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  font-size: 11px; font-weight: 800; color: #374151;
  text-transform: uppercase; letter-spacing: 0.1em;
}
.cal-upcoming-header .material-symbols-outlined { font-size: 16px !important; color: #dc2626; }
.cal-upcoming-list { display: flex; gap: 0; flex-direction: column; }
.cal-upcoming-item {
  display: flex; align-items: center; gap: 14px;
  padding: 12px 18px; cursor: pointer;
  transition: background 0.15s ease;
  border-bottom: 1px solid #f3f4f6;
  position: relative; overflow: hidden;
}
.cal-upcoming-item:last-child { border-bottom: none; }
.cal-upcoming-item:hover { background: #fafafa; }
.cal-upcoming-stripe {
  width: 3px; min-height: 100%;
  position: absolute; left: 0; top: 0;
}
.cal-upcoming-info { flex: 1; padding-left: 6px; }
.cal-upcoming-title { font-size: 13px; font-weight: 700; color: #111827; }
.cal-upcoming-date  { font-size: 11px; color: #9ca3af; margin-top: 2px; font-weight: 500; }
.cal-upcoming-cat {
  padding: 3px 10px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.06em;
  white-space: nowrap;
}

/* ═══ RESPONSIVE TABLET ═══ */
@media (max-width: 900px) {
  .cal-banner { padding: 20px 22px; }
  .cal-banner-inner { flex-direction: column; align-items: flex-start; gap: 16px; }
  .cal-banner-right { align-items: flex-start; width: 100%; }
  .cal-main-title { font-size: 24px; }
  .cal-kpi-row { gap: 8px; }
  .cal-kpi { min-width: 60px; padding: 8px 12px; }
  .cal-kpi-num { font-size: 18px; }
  .cal-controls { flex-direction: column; align-items: flex-start; }
  .cal-right-controls { width: 100%; flex-direction: column; align-items: flex-start; }
  .cal-search { width: 100%; box-sizing: border-box; }
  .cal-search input { min-width: unset; flex: 1; }
  .cal-filters { flex-wrap: wrap; }
}

/* ═══ RESPONSIVE MÓVIL ═══ */
@media (max-width: 600px) {
  .cal-banner { padding: 16px; border-radius: 12px; margin-bottom: 10px; }
  .cal-main-title { font-size: 20px; }
  .cal-month-hero { font-size: 11px; }
  .cal-kpi-row { gap: 6px; }
  .cal-kpi { min-width: 52px; padding: 7px 10px; }
  .cal-kpi-num { font-size: 16px; }
  .cal-kpi-label { font-size: 8px; }
  .cal-banner-actions { gap: 6px; }
  .cal-view-toggle button { padding: 6px 10px; font-size: 11px; }
  .cal-today-btn { padding: 6px 12px; font-size: 11px; }
  .cal-new-btn { padding: 7px 14px; font-size: 11px; }

  .cal-controls { padding: 10px 12px; border-radius: 12px; }
  .cal-nav-label { font-size: 13px; min-width: 120px; }

  /* Grilla móvil: celdas más compactas */
  .cal-grid-wrap { border-radius: 12px; }
  .cal-weekdays > div { padding: 8px 2px; font-size: 8px; letter-spacing: 0.05em; }
  .cal-cell { min-height: 60px; padding: 4px 3px; }
  .cal-cell-num { font-size: 11px; width: 22px; height: 22px; }
  .cal-cell-add-btn { width: 16px; height: 16px; }
  .cal-cell-add-btn .material-symbols-outlined { font-size: 11px; }
  .cal-evt { padding: 2px 4px; }
  .cal-evt-name { font-size: 9px; }
  .cal-evt-dot { width: 5px; height: 5px; }

  /* Upcoming más compacto */
  .cal-upcoming { margin-top: 10px; border-radius: 12px; }
  .cal-upcoming-item { padding: 10px 14px; }
  .cal-upcoming-title { font-size: 12px; }
  .cal-upcoming-date { font-size: 10px; }
  .cal-upcoming-cat { display: none; }

  /* En móvil: ocultar pin de archivo para ahorrar espacio */
  .cal-evt-pin { display: none; }
}
</style>
