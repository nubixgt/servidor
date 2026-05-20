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
        <div class="cal-kpi-row">
          <div class="cal-kpi" v-for="cat in CATEGORIES" :key="cat.id">
            <span class="cal-kpi-num">{{ events.filter(e => e.category === cat.id).length }}</span>
            <span class="cal-kpi-label">{{ cat.label }}</span>
          </div>
          <div class="cal-kpi cal-kpi-total">
            <span class="cal-kpi-num">{{ events.length }}</span>
            <span class="cal-kpi-label">Total</span>
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
      <div class="cal-filters">
        <button v-for="cat in CATEGORIES" :key="cat.id"
          @click="toggleFilter(cat.id)"
          :class="['cal-filter', { active: activeFilters.includes(cat.id) }]"
          :style="{ '--fc': cat.color, '--fb': cat.bg }">
          <span class="cal-filter-dot" :style="{ background: cat.color }"></span>
          {{ cat.label }}
        </button>
        <button v-if="activeFilters.length" @click="activeFilters = []" class="cal-filter-clear">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
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
  background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 28%, #b91c1c 62%, #dc2626 100%);
  border-radius: 22px;
  padding: 28px 36px 32px;
  overflow: hidden;
  margin-bottom: 18px;
  box-shadow: 0 20px 60px rgba(185, 28, 28, 0.3);
}

/* Blobs flotantes */
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.35;
  animation: blobDrift 9s ease-in-out infinite;
  pointer-events: none;
}
.blob-a { width: 320px; height: 320px; background: #f87171; top: -100px; right: 8%;  animation-delay: 0s; }
.blob-b { width: 220px; height: 220px; background: #fb923c; bottom: -70px; left: 18%; animation-delay: -3.5s; }
.blob-c { width: 160px; height: 160px; background: #fca5a5; top: 10px;  right: 32%; animation-delay: -6s; }

@keyframes blobDrift {
  0%, 100% { transform: translateY(0) scale(1); }
  50%       { transform: translateY(-22px) scale(1.06); }
}

.cal-banner-inner {
  position: relative; z-index: 1;
  display: flex; justify-content: space-between; align-items: flex-end;
  flex-wrap: wrap; gap: 20px;
}

.cal-main-title {
  font-size: 34px; font-weight: 900; color: white;
  letter-spacing: -0.5px; margin: 0 0 6px;
  font-family: 'Public Sans', sans-serif;
}
.cal-month-hero {
  font-size: 14px; color: rgba(255,255,255,0.65);
  font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
}
.cal-month-hero span { color: rgba(255,255,255,0.4); margin-left: 6px; }
.cal-month-hero em { font-style: normal; color: rgba(255,255,255,0.75); margin-left: 4px; font-weight: 800; }

.cal-banner-right { display: flex; flex-direction: column; align-items: flex-end; gap: 14px; }

/* KPI cards */
.cal-kpi-row { display: flex; gap: 10px; flex-wrap: wrap; }
.cal-kpi {
  background: rgba(255,255,255,0.13);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.22);
  border-radius: 14px; padding: 10px 18px;
  text-align: center; min-width: 72px;
  transition: all 0.3s ease;
}
.cal-kpi:hover { background: rgba(255,255,255,0.22); transform: translateY(-3px); }
.cal-kpi-num {
  display: block; font-size: 24px; font-weight: 900;
  color: white; line-height: 1; margin-bottom: 4px;
}
.cal-kpi-label {
  display: block; font-size: 9px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.12em;
  color: rgba(255,255,255,0.55);
}
.cal-kpi-total .cal-kpi-num { color: #fde68a; }

/* Banner actions */
.cal-banner-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.cal-view-toggle {
  display: flex;
  background: rgba(0,0,0,0.22);
  border-radius: 12px; padding: 3px; gap: 2px;
}
.cal-view-toggle button {
  display: flex; align-items: center; gap: 5px;
  padding: 7px 14px; border: none; background: none;
  border-radius: 9px; font-size: 12px; font-weight: 700;
  color: rgba(255,255,255,0.55); cursor: pointer; transition: all 0.25s ease;
}
.cal-view-toggle button .material-symbols-outlined { font-size: 15px !important; }
.cal-view-toggle button.active {
  background: rgba(255,255,255,0.2); color: white;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.cal-view-toggle button:hover:not(.active) { color: rgba(255,255,255,0.85); }

.cal-today-btn {
  padding: 8px 18px;
  border: 2px solid rgba(255,255,255,0.3);
  background: transparent; color: white;
  border-radius: 12px; font-size: 12px; font-weight: 700;
  cursor: pointer; transition: all 0.25s ease;
}
.cal-today-btn:hover { background: rgba(255,255,255,0.15); border-color: white; }

.cal-new-btn {
  display: flex; align-items: center; gap: 5px;
  padding: 9px 20px; background: white; color: #b91c1c;
  border: none; border-radius: 12px; font-size: 12px; font-weight: 800;
  cursor: pointer; transition: all 0.25s ease;
  box-shadow: 0 4px 14px rgba(0,0,0,0.18);
}
.cal-new-btn .material-symbols-outlined { font-size: 16px !important; }
.cal-new-btn:hover { background: #fff1f2; transform: translateY(-2px); box-shadow: 0 8px 22px rgba(0,0,0,0.22); }

/* ═══ CONTROLES ═══ */
.cal-controls {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 12px; margin-bottom: 16px;
  padding: 14px 22px; background: white;
  border-radius: 16px; border: 1px solid #fecaca;
  box-shadow: 0 2px 10px rgba(185, 28, 28, 0.07);
}

.cal-nav { display: flex; align-items: center; gap: 12px; }
.cal-nav-btn {
  width: 36px; height: 36px; border: 2px solid #fecaca;
  background: white; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #b91c1c; transition: all 0.25s ease;
}
.cal-nav-btn:hover { background: #fef2f2; border-color: #f87171; transform: scale(1.08); }
.cal-nav-label {
  font-size: 16px; font-weight: 800; color: #1f2937;
  text-transform: uppercase; letter-spacing: 0.1em;
  min-width: 180px; text-align: center;
}

.cal-right-controls { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

/* Filtros */
.cal-filters { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.cal-filter {
  display: flex; align-items: center; gap: 6px;
  padding: 6px 14px; border: 2px solid #e5e7eb;
  background: white; border-radius: 20px;
  font-size: 12px; font-weight: 600; color: #6b7280;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cal-filter:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.cal-filter.active {
  border-color: var(--fc); background: var(--fb); color: var(--fc);
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.cal-filter-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.cal-filter-clear {
  display: flex; align-items: center; padding: 5px 10px;
  border: none; background: #fee2e2; border-radius: 10px;
  font-size: 11px; font-weight: 700; color: #dc2626;
  cursor: pointer; transition: all 0.2s;
}
.cal-filter-clear .material-symbols-outlined { font-size: 14px !important; }
.cal-filter-clear:hover { background: #fecaca; }

/* Búsqueda */
.cal-search {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 14px; background: #fff5f5;
  border: 2px solid #fecaca; border-radius: 12px; transition: all 0.3s ease;
}
.cal-search:focus-within { border-color: #f87171; box-shadow: 0 0 0 3px rgba(248,113,113,0.15); }
.cal-search .material-symbols-outlined { font-size: 18px; color: #f87171; flex-shrink: 0; }
.cal-search input { border: none; background: none; outline: none; font-size: 13px; color: #374151; min-width: 140px; }
.cal-search input::placeholder { color: #fca5a5; }
.cal-search button { border: none; background: none; cursor: pointer; display: flex; color: #9ca3af; }
.cal-search button .material-symbols-outlined { font-size: 16px; }

/* ═══ GRILLA ═══ */
.cal-grid-wrap {
  background: white; border-radius: 20px;
  border: 1px solid #fecaca; overflow: hidden;
  box-shadow: 0 8px 36px rgba(185, 28, 28, 0.09);
}

.cal-weekdays {
  display: grid; grid-template-columns: repeat(7, 1fr);
  background: linear-gradient(135deg, #991b1b, #dc2626);
}
.cal-weekdays > div {
  padding: 14px; text-align: center;
  font-size: 10px; font-weight: 900;
  text-transform: uppercase; letter-spacing: 0.2em;
  color: rgba(255,255,255,0.75);
}

.cal-grid {
  display: grid; grid-template-columns: repeat(7, 1fr);
  gap: 1px; background: #fecaca;
}

/* Celdas */
.cal-cell {
  background: white; min-height: 130px; padding: 10px;
  position: relative; transition: background 0.2s ease, transform 0.2s ease;
  animation: cellAppear 0.35s ease-out var(--anim-delay, 0ms) both;
  cursor: default;
}
@keyframes cellAppear {
  from { opacity: 0; transform: scale(0.92) translateY(4px); }
  to   { opacity: 1; transform: scale(1)    translateY(0);   }
}

.cal-cell:hover { background: #fff5f5; z-index: 2; }
.cal-cell.faded { background: #fafafa; }
.cal-cell.faded .cal-cell-num { color: #d1d5db; }

.cal-cell.is-today {
  background: linear-gradient(150deg, #fff5f5 0%, #fef2f2 60%, #fee2e2 100%);
  box-shadow: inset 0 0 0 2px #f87171;
}
.cal-cell.drag-over {
  background: #fee2e2 !important;
  box-shadow: inset 0 0 0 3px #dc2626;
  transform: scale(1.025);
  z-index: 5;
}
.week-mode .cal-cell { min-height: 220px; }

/* Parte superior de la celda */
.cal-cell-top {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 6px;
}
.cal-cell-num {
  font-size: 13px; font-weight: 800; color: #6b7280;
  width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
}
.today-badge {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: white !important;
  box-shadow: 0 4px 14px rgba(185, 28, 28, 0.5);
  animation: todayGlow 3s ease-in-out infinite;
}
@keyframes todayGlow {
  0%, 100% { box-shadow: 0 4px 14px rgba(185, 28, 28, 0.5); }
  50%       { box-shadow: 0 4px 22px rgba(185, 28, 28, 0.8); }
}

.cal-cell-add-btn {
  width: 22px; height: 22px; background: #dc2626;
  border: none; border-radius: 6px; color: white;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; opacity: 0; transform: scale(0.5);
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cal-cell:hover .cal-cell-add-btn,
.cal-cell.is-today .cal-cell-add-btn { opacity: 1; transform: scale(1); }
.cal-cell-add-btn:hover { background: #b91c1c; transform: scale(1.18) !important; }
.cal-cell-add-btn .material-symbols-outlined { font-size: 15px; }

/* Eventos */
.cal-events-list { display: flex; flex-direction: column; gap: 3px; }
.cal-evt {
  display: flex; align-items: center; gap: 5px;
  padding: 3px 7px 3px 5px;
  background: var(--eb); border-radius: 6px;
  cursor: grab; transition: all 0.2s ease;
  border: 1px solid transparent;
  overflow: hidden;
}
.cal-evt:hover {
  transform: translateX(3px) scale(1.01);
  box-shadow: 0 3px 10px rgba(0,0,0,0.12); z-index: 5;
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
  margin-top: 16px; background: white;
  border-radius: 18px; border: 1px solid #fecaca;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(185, 28, 28, 0.06);
}
.cal-upcoming-header {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 20px;
  background: linear-gradient(90deg, #fff5f5, #fef2f2);
  border-bottom: 1px solid #fecaca;
  font-size: 12px; font-weight: 800; color: #991b1b;
  text-transform: uppercase; letter-spacing: 0.1em;
}
.cal-upcoming-header .material-symbols-outlined { font-size: 18px !important; color: #dc2626; }
.cal-upcoming-list { display: flex; gap: 0; flex-direction: column; }
.cal-upcoming-item {
  display: flex; align-items: center; gap: 14px;
  padding: 12px 20px; cursor: pointer;
  transition: background 0.2s ease;
  border-bottom: 1px solid #fff5f5;
  position: relative; overflow: hidden;
}
.cal-upcoming-item:last-child { border-bottom: none; }
.cal-upcoming-item:hover { background: #fff5f5; }
.cal-upcoming-stripe {
  width: 4px; min-height: 100%;
  position: absolute; left: 0; top: 0;
  border-radius: 0 4px 4px 0;
}
.cal-upcoming-info { flex: 1; padding-left: 8px; }
.cal-upcoming-title { font-size: 13px; font-weight: 700; color: #1f2937; }
.cal-upcoming-date  { font-size: 11px; color: #9ca3af; margin-top: 2px; font-weight: 500; }
.cal-upcoming-cat {
  padding: 4px 10px; border-radius: 20px;
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.06em;
  white-space: nowrap;
}

/* ═══ RESPONSIVE MÓVIL ═══ */
@media (max-width: 768px) {
  .cal-banner { padding: 18px 16px 22px; border-radius: 16px; }
  .cal-banner-inner { flex-direction: column; align-items: flex-start; gap: 14px; }
  .cal-banner-right { align-items: flex-start; width: 100%; }
  .cal-main-title { font-size: 24px; }
  .cal-month-hero { font-size: 11px; }

  /* KPIs: scroll horizontal en móvil */
  .cal-kpi-row {
    display: flex; flex-wrap: nowrap;
    overflow-x: auto; gap: 8px;
    padding-bottom: 4px; width: 100%;
    scrollbar-width: none;
  }
  .cal-kpi-row::-webkit-scrollbar { display: none; }
  .cal-kpi { min-width: 64px; padding: 8px 12px; flex-shrink: 0; }
  .cal-kpi-num { font-size: 20px; }

  .cal-banner-actions { flex-wrap: wrap; gap: 8px; }
  .cal-view-toggle button { padding: 6px 10px; font-size: 11px; }
  .cal-today-btn { padding: 7px 12px; font-size: 11px; }
  .cal-new-btn { padding: 7px 14px; font-size: 11px; }

  /* Controles */
  .cal-controls {
    flex-direction: column; align-items: flex-start;
    padding: 12px 14px; gap: 10px;
    border-radius: 12px;
  }
  .cal-nav-label { font-size: 14px; min-width: 150px; }

  /* Filtros: scroll horizontal */
  .cal-right-controls { flex-direction: column; align-items: flex-start; width: 100%; gap: 8px; }
  .cal-filters {
    display: flex; flex-wrap: nowrap;
    overflow-x: auto; gap: 6px;
    width: 100%; padding-bottom: 4px;
    scrollbar-width: none;
  }
  .cal-filters::-webkit-scrollbar { display: none; }
  .cal-filter { flex-shrink: 0; padding: 5px 10px; font-size: 11px; }
  .cal-search { width: 100%; box-sizing: border-box; }
  .cal-search input { min-width: 0; flex: 1; }

  /* Cuadrícula de calendario */
  .cal-grid-wrap { border-radius: 14px; }
  .cal-weekdays > div { font-size: 8px; padding: 10px 2px; letter-spacing: 0.05em; }
  .cal-cell { min-height: 60px; padding: 4px 3px; }
  .cal-cell-num { font-size: 11px; width: 22px; height: 22px; }
  .cal-cell-add-btn { display: none; }
  .cal-evt { padding: 2px 4px; }
  .cal-evt-name { font-size: 9px; }
  .cal-evt-dot { width: 5px; height: 5px; }
  .week-mode .cal-cell { min-height: 120px; }

  /* Próximos eventos */
  .cal-upcoming { border-radius: 14px; }
  .cal-upcoming-item { padding: 10px 14px; gap: 10px; }
  .cal-upcoming-title { font-size: 12px; }
  .cal-upcoming-cat { font-size: 9px; padding: 3px 8px; }
}

@media (max-width: 480px) {
  .cal-main-title { font-size: 20px; }
  .cal-cell { min-height: 48px; padding: 2px; }
  .cal-cell-num { font-size: 10px; width: 18px; height: 18px; }
  .cal-evt-name { font-size: 8px; }
  .cal-weekdays > div { font-size: 7px; padding: 8px 1px; }
}
</style>
