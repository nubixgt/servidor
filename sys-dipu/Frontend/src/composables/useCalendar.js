import { ref, computed } from 'vue'

const CATEGORIES = [
  { id: 'trabajo', label: 'Trabajo', color: '#7c3aed', bg: '#ede9fe', border: '#a78bfa' },
  { id: 'personal', label: 'Personal', color: '#16a34a', bg: '#dcfce7', border: '#4ade80' },
  { id: 'urgente', label: 'Urgente', color: '#dc2626', bg: '#fee2e2', border: '#f87171' }
]

function createId() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 7)
}

export function useCalendar() {
  const currentDate = ref(new Date())
  const viewMode = ref('month') // month | week
  const searchQuery = ref('')
  const activeFilters = ref([]) // category ids
  const events = ref([
    { id: createId(), title: 'Reunión de equipo', date: formatDate(new Date()), category: 'trabajo', description: 'Revisión semanal del proyecto', files: [] },
    { id: createId(), title: 'Cita médica', date: formatDate(addDays(new Date(), 2)), category: 'personal', description: 'Control anual', files: [] },
    { id: createId(), title: 'Entrega de reporte', date: formatDate(addDays(new Date(), 1)), category: 'urgente', description: 'Reporte fiscal Q2', files: [] },
    { id: createId(), title: 'Capacitación Vue.js', date: formatDate(addDays(new Date(), 5)), category: 'trabajo', description: 'Curso avanzado', files: [] },
    { id: createId(), title: 'Almuerzo familiar', date: formatDate(addDays(new Date(), 3)), category: 'personal', description: '', files: [] },
    { id: createId(), title: 'Auditoría interna', date: formatDate(addDays(new Date(), -1)), category: 'urgente', description: 'Preparar documentos', files: [] },
  ])

  function formatDate(d) {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
  }

  function addDays(d, n) {
    const r = new Date(d)
    r.setDate(r.getDate() + n)
    return r
  }

  const currentYear = computed(() => currentDate.value.getFullYear())
  const currentMonth = computed(() => currentDate.value.getMonth())

  const monthName = computed(() => {
    const names = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
    return names[currentMonth.value]
  })

  const calendarDays = computed(() => {
    const year = currentYear.value
    const month = currentMonth.value
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    let startDow = firstDay.getDay()
    if (startDow === 0) startDow = 7
    const days = []
    // Previous month padding
    for (let i = startDow - 1; i > 0; i--) {
      const d = new Date(year, month, 1 - i)
      days.push({ date: formatDate(d), day: d.getDate(), currentMonth: false, isToday: false })
    }
    // Current month
    const today = formatDate(new Date())
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const d = new Date(year, month, i)
      const dateStr = formatDate(d)
      days.push({ date: dateStr, day: i, currentMonth: true, isToday: dateStr === today })
    }
    // Next month padding
    while (days.length % 7 !== 0) {
      const d = new Date(year, month + 1, days.length - (startDow - 1 + lastDay.getDate()) + 1)
      days.push({ date: formatDate(d), day: d.getDate(), currentMonth: false, isToday: false })
    }
    return days
  })

  const weekDays = computed(() => {
    const d = new Date(currentDate.value)
    const dow = d.getDay()
    const monday = new Date(d)
    monday.setDate(d.getDate() - (dow === 0 ? 6 : dow - 1))
    const days = []
    const today = formatDate(new Date())
    for (let i = 0; i < 7; i++) {
      const day = new Date(monday)
      day.setDate(monday.getDate() + i)
      const dateStr = formatDate(day)
      days.push({ date: dateStr, day: day.getDate(), currentMonth: true, isToday: dateStr === today })
    }
    return days
  })

  const displayDays = computed(() => viewMode.value === 'week' ? weekDays.value : calendarDays.value)

  const filteredEvents = computed(() => {
    let result = events.value
    if (activeFilters.value.length > 0) {
      result = result.filter(e => activeFilters.value.includes(e.category))
    }
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      result = result.filter(e => e.title.toLowerCase().includes(q) || e.date.includes(q) || e.description.toLowerCase().includes(q))
    }
    return result
  })

  function getEventsForDate(date) {
    return filteredEvents.value.filter(e => e.date === date)
  }

  function prevPeriod() {
    const d = new Date(currentDate.value)
    if (viewMode.value === 'week') d.setDate(d.getDate() - 7)
    else d.setMonth(d.getMonth() - 1)
    currentDate.value = d
  }

  function nextPeriod() {
    const d = new Date(currentDate.value)
    if (viewMode.value === 'week') d.setDate(d.getDate() + 7)
    else d.setMonth(d.getMonth() + 1)
    currentDate.value = d
  }

  function goToday() {
    currentDate.value = new Date()
  }

  function toggleFilter(catId) {
    const idx = activeFilters.value.indexOf(catId)
    if (idx >= 0) activeFilters.value.splice(idx, 1)
    else activeFilters.value.push(catId)
  }

  function addEvent(evt) {
    events.value.push({ ...evt, id: createId(), files: evt.files || [] })
  }

  function updateEvent(id, data) {
    const idx = events.value.findIndex(e => e.id === id)
    if (idx >= 0) events.value[idx] = { ...events.value[idx], ...data }
  }

  function deleteEvent(id) {
    events.value = events.value.filter(e => e.id !== id)
  }

  function moveEvent(id, newDate) {
    const idx = events.value.findIndex(e => e.id === id)
    if (idx >= 0) events.value[idx].date = newDate
  }

  return {
    currentDate, viewMode, searchQuery, activeFilters, events,
    currentYear, currentMonth, monthName, displayDays, filteredEvents,
    CATEGORIES, getEventsForDate, prevPeriod, nextPeriod, goToday,
    toggleFilter, addEvent, updateEvent, deleteEvent, moveEvent, formatDate
  }
}
