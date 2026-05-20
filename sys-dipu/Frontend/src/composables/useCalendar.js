import { ref, computed } from 'vue'
import api from '../services/api'

const CATEGORIES = [
  { id: 'iniciativas', label: 'Iniciativas de Ley', color: '#2563eb', bg: '#dbeafe', border: '#93c5fd' },
  { id: 'citaciones', label: 'Citaciones', color: '#ea580c', bg: '#ffedd5', border: '#fdba74' },
  { id: 'comisiones', label: 'Comisiones', color: '#9333ea', bg: '#f3e8ff', border: '#d8b4fe' },
  { id: 'fiscalizacion', label: 'Fiscalización', color: '#dc2626', bg: '#fee2e2', border: '#fca5a5' },
  { id: 'compromisos', label: 'Compromisos Distritales', color: '#16a34a', bg: '#dcfce7', border: '#86efac' },
  { id: 'actividades', label: 'Actividades', color: '#e11d48', bg: '#ffe4e6', border: '#fda4af' },
  { id: 'redes', label: 'Redes Sociales', color: '#0d9488', bg: '#ccfbf1', border: '#5eead4' },
  { id: 'afiliaciones', label: 'Afiliaciones Políticas', color: '#d97706', bg: '#fef3c7', border: '#fcd34d' }
]

export function useCalendar() {
  const currentDate = ref(new Date())
  const viewMode = ref('month') // month | week
  const searchQuery = ref('')
  const activeFilters = ref([]) // category ids
  const events = ref([])

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

  async function loadEvents() {
    try {
      const response = await api.get('/calendario/eventos')
      if (response.data && response.data.success) {
        events.value = response.data.data.map(evt => ({
          ...evt,
          files: evt.files ? JSON.parse(evt.files) : []
        }))
      }
    } catch (error) {
      console.error('Error al cargar eventos:', error)
    }
  }

  async function addEvent(evt) {
    try {
      const response = await api.post('/calendario/eventos', {
        title: evt.title,
        date: evt.date,
        category: evt.category,
        description: evt.description || '',
        files: JSON.stringify(evt.files || [])
      })
      if (response.data && response.data.success) {
        await loadEvents()
      }
    } catch (error) {
      console.error('Error al agregar el evento:', error)
    }
  }

  async function updateEvent(id, data) {
    try {
      const response = await api.put(`/calendario/eventos/${id}`, {
        title: data.title,
        date: data.date,
        category: data.category,
        description: data.description || '',
        files: JSON.stringify(data.files || [])
      })
      if (response.data && response.data.success) {
        await loadEvents()
      }
    } catch (error) {
      console.error('Error al actualizar el evento:', error)
    }
  }

  async function deleteEvent(id) {
    try {
      const response = await api.delete(`/calendario/eventos/${id}`)
      if (response.data && response.data.success) {
        await loadEvents()
      }
    } catch (error) {
      console.error('Error al eliminar el evento:', error)
    }
  }

  async function moveEvent(id, newDate) {
    const evt = events.value.find(e => e.id === id)
    if (!evt) return
    try {
      const response = await api.put(`/calendario/eventos/${id}`, {
        title: evt.title,
        date: newDate,
        category: evt.category,
        description: evt.description || '',
        files: JSON.stringify(evt.files || [])
      })
      if (response.data && response.data.success) {
        await loadEvents()
      }
    } catch (error) {
      console.error('Error al mover el evento:', error)
    }
  }

  return {
    currentDate, viewMode, searchQuery, activeFilters, events,
    currentYear, currentMonth, monthName, displayDays, filteredEvents,
    CATEGORIES, getEventsForDate, prevPeriod, nextPeriod, goToday,
    toggleFilter, addEvent, updateEvent, deleteEvent, moveEvent, formatDate,
    loadEvents
  }
}
