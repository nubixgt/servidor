<template>
    <div class="space-y-8 h-full flex flex-col">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-8">
            <div>
                <span class="text-[10px] font-bold text-primary uppercase tracking-[0.25em] mb-2 block">Workspace Control</span>
                <h2 class="text-4xl font-extrabold text-on-surface tracking-tighter font-headline">Gestión de Tareas</h2>
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <button
                    @click="exportarPdf"
                    class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg hover:bg-surface-container-high transition-all border border-outline-variant/20 uppercase tracking-widest"
                >
                    <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> PDF
                </button>
                <button
                    @click="exportarExcel"
                    class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg hover:bg-surface-container-high transition-all border border-outline-variant/20 uppercase tracking-widest"
                >
                    <span class="material-symbols-outlined text-[16px]">description</span> Excel
                </button>
                <button
                    @click="openModal()"
                    class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-white text-xs font-bold rounded-lg hover:shadow-xl active:scale-95 transition-all shadow-lg shadow-primary/10 uppercase tracking-widest ml-2"
                >
                    <span class="material-symbols-outlined text-[16px]">add</span> Nueva Tarea
                </button>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl mb-8 border border-outline-variant/30 flex flex-wrap gap-6 items-end shadow-sm">
            <!-- Buscar Tarea -->
            <div class="flex-1 min-w-[200px]">
                <p class="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Buscar Tarea</p>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline/50 text-[18px]">search</span>
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Buscar por título o descripción..."
                        class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border-none text-sm rounded-lg focus:ring-1 focus:ring-primary/30 font-medium text-on-surface placeholder:text-on-surface-variant/40 outline-none"
                    />
                </div>
            </div>

            <!-- Responsable -->
            <div class="flex-1 min-w-[200px]">
                <p class="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Responsable</p>
                <div class="relative flex items-center">
                    <select
                        v-model="filtroResponsable"
                        class="w-full bg-surface-container-low border-none text-sm rounded-lg py-2.5 pl-4 pr-10 focus:ring-1 focus:ring-primary/30 font-medium text-on-surface outline-none appearance-none cursor-pointer"
                    >
                        <option value="Todos">Todos los Responsables</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.nombre_completo }}</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 pointer-events-none text-outline/50">expand_more</span>
                </div>
            </div>

            <!-- Prioridad Toggle -->
            <div class="flex-[1.5] min-w-[300px]">
                <p class="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Prioridad</p>
                <div class="flex gap-2">
                    <button
                        v-for="p in ['Todas', 'Alta', 'Media', 'Baja', 'Crítica']"
                        :key="p"
                        @click="filtroPrioridad = p"
                        :class="filtroPrioridad === p
                            ? 'flex-1 py-2 px-1 bg-primary text-white text-[10px] font-bold rounded-lg border border-transparent transition-all uppercase tracking-wider'
                            : 'flex-1 py-2 px-1 bg-surface-container-low text-on-surface-variant text-[10px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider'"
                    >
                        {{ p }}
                    </button>
                </div>
            </div>

            <!-- Botón Limpiar Filtros -->
            <button
                @click="limpiarFiltros"
                class="p-2.5 text-on-surface-variant hover:bg-surface-container rounded-lg transition-all border border-outline-variant/30"
                title="Limpiar filtros"
            >
                <span class="material-symbols-outlined text-[20px]">filter_alt_off</span>
            </button>
        </div>

        <!-- Kanban Grid -->
        <div class="flex gap-6 overflow-x-auto pb-8 -mx-2 px-2 scroll-smooth flex-1">
            <!-- Column template for each status -->
            <div
                v-for="col in columnsList"
                :key="col.id"
                class="flex flex-col gap-6 flex-shrink-0 w-[300px]"
            >
                <!-- Column Header -->
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-bold uppercase tracking-widest flex items-center gap-3" :class="col.textClass">
                        {{ col.titulo }}
                        <span class="text-[10px] px-2 py-0.5 rounded-full" :class="col.badgeClass">
                            {{ tasksByColumn[col.id].length }}
                        </span>
                    </h3>
                </div>

                <!-- Column Container (Drop Area) -->
                <div
                    class="flex flex-col gap-4 min-h-[500px] bg-surface-container-lowest/20 rounded-2xl p-2 border border-dashed border-outline-variant/20"
                    @dragover.prevent
                    @drop="onDrop(col.id)"
                >
                    <!-- Empty Column State -->
                    <div
                        v-if="tasksByColumn[col.id].length === 0"
                        class="flex flex-col items-center justify-center flex-1 text-on-surface-variant/30 py-12"
                    >
                        <span class="material-symbols-outlined text-3xl mb-1">dashboard_customize</span>
                        <span class="text-[10px] uppercase font-bold tracking-wider">Arrastra aquí</span>
                    </div>

                    <!-- Task Cards -->
                    <div
                        v-for="task in tasksByColumn[col.id]"
                        :key="task.id"
                        draggable="true"
                        @dragstart="onDragStart(task)"
                        @click="openModal(task)"
                        class="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing relative"
                    >
                        <!-- Card Header -->
                        <div class="flex justify-between items-start mb-4">
                            <span :class="priorityBadgeClass(task.prioridad)">
                                {{ task.prioridad }}
                            </span>
                            <span class="material-symbols-outlined text-outline/20 group-hover:text-outline/40 cursor-grab active:cursor-grabbing text-[18px]">
                                drag_indicator
                            </span>
                        </div>

                        <!-- Card Body -->
                        <h4
                            class="text-sm font-bold text-on-surface mb-6 leading-relaxed"
                            :class="{ 'line-through text-on-surface-variant/40 decoration-on-surface-variant/40': task.estado === 'Completada' }"
                        >
                            {{ task.titulo }}
                        </h4>

                        <!-- Responsable Avatar & Name -->
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-6 h-6 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center text-[9px] font-extrabold">
                                {{ getInitials(task.asignado_nombre) }}
                            </div>
                            <span class="text-[10px] text-outline font-medium">
                                {{ task.asignado_nombre || 'Sin Asignar' }}
                            </span>
                        </div>

                        <!-- Card Footer -->
                        <div class="flex justify-between items-center pt-4 border-t border-outline-variant/20">
                            <!-- Due date -->
                            <div class="flex items-center gap-1.5 text-outline">
                                <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                <span class="text-[10px] font-bold">{{ formatDate(task.fecha_limite) }}</span>
                            </div>
                            <!-- Delete action -->
                            <button
                                @click.stop="eliminarTarea(task.id)"
                                class="text-outline/30 hover:text-error transition-colors p-1 opacity-0 group-hover:opacity-100"
                                title="Eliminar tarea"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════ DIALOG MODAL ════════ -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="task-overlay" @click.self="closeModal">
                    <div class="task-card">
                        <!-- Header -->
                        <div class="task-header">
                            <div class="task-header-left">
                                <span class="material-symbols-outlined task-header-icon">checklist</span>
                                <div>
                                    <h2 class="task-header-title">{{ editando ? 'Editar Tarea' : 'Nueva Tarea' }}</h2>
                                    <p class="task-header-sub">
                                        {{ editando ? 'Modifica los detalles de la tarea' : 'Crea una nueva tarea organizativa' }}
                                    </p>
                                </div>
                            </div>
                            <button class="task-close" @click="closeModal">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="task-body">
                            <!-- Título -->
                            <div class="task-field">
                                <label class="task-label">Título de la Tarea *</label>
                                <div class="task-input-wrap">
                                    <span class="material-symbols-outlined task-input-icon">label</span>
                                    <input
                                        v-model="form.titulo"
                                        type="text"
                                        class="task-input with-icon"
                                        placeholder="Ej. Revisión de presupuesto de egresos"
                                    />
                                </div>
                            </div>

                            <!-- Descripción -->
                            <div class="task-field">
                                <label class="task-label">Descripción</label>
                                <textarea
                                    v-model="form.descripcion"
                                    class="task-textarea"
                                    rows="3"
                                    placeholder="Detalles o requerimientos de la tarea..."
                                ></textarea>
                            </div>

                            <!-- Responsable + Prioridad -->
                            <div class="task-field-row">
                                <div class="task-field flex-1">
                                    <label class="task-label">Responsable</label>
                                    <div class="task-select-wrap">
                                        <select v-model="form.asignado_a" class="task-input task-select">
                                            <option value="">Sin Asignar</option>
                                            <option v-for="user in users" :key="user.id" :value="user.id">
                                                {{ user.nombre_completo }}
                                            </option>
                                        </select>
                                        <span class="material-symbols-outlined task-select-icon">expand_more</span>
                                    </div>
                                </div>
                                <div class="task-field" style="flex: 0 0 170px">
                                    <label class="task-label">Prioridad</label>
                                    <div class="task-select-wrap">
                                        <select v-model="form.prioridad" class="task-input task-select">
                                            <option value="Baja">Baja</option>
                                            <option value="Media">Media</option>
                                            <option value="Alta">Alta</option>
                                            <option value="Crítica">Crítica</option>
                                        </select>
                                        <span class="material-symbols-outlined task-select-icon">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Fecha Límite + Estado -->
                            <div class="task-field-row">
                                <div class="task-field flex-1">
                                    <label class="task-label">Fecha Límite *</label>
                                    <div class="task-input-wrap">
                                        <span class="material-symbols-outlined task-input-icon">calendar_today</span>
                                        <input
                                            v-model="form.fecha_limite"
                                            type="date"
                                            class="task-input with-icon"
                                        />
                                    </div>
                                </div>
                                <div class="task-field" style="flex: 0 0 170px">
                                    <label class="task-label">Estado</label>
                                    <div class="task-select-wrap">
                                        <select v-model="form.estado" class="task-input task-select">
                                            <option value="Pendiente">Pendiente</option>
                                            <option value="En Proceso">En Proceso</option>
                                            <option value="En Revisión">En Revisión</option>
                                            <option value="Completada">Completada</option>
                                        </select>
                                        <span class="material-symbols-outlined task-select-icon">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <p v-if="formError" class="text-red-400 text-sm font-medium">{{ formError }}</p>
                        </div>

                        <!-- Footer -->
                        <div class="task-footer">
                            <button
                                v-if="editando"
                                @click="eliminarTarea(editando.id)"
                                class="task-btn-delete"
                            >
                                <span class="material-symbols-outlined text-base">delete</span> Eliminar
                            </button>
                            <div class="task-footer-right">
                                <button @click="closeModal" class="task-btn-cancel">Cancelar</button>
                                <button
                                    @click="guardarTarea"
                                    :disabled="loading"
                                    class="task-btn-save"
                                    :style="{ background: 'linear-gradient(135deg, #184e5b 0%, #216170 100%)' }"
                                >
                                    <span v-if="loading" class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span>
                                    <span v-else class="material-symbols-outlined text-base">{{ editando ? 'save' : 'add' }}</span>
                                    {{ editando ? 'Guardar cambios' : 'Crear Tarea' }}
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
import { ref, computed, onMounted } from 'vue'
import api, { getApiBaseUrl } from '../../../services/api'
import Swal from 'sweetalert2'

/* ─── State ─── */
const tasks = ref([])
const users = ref([])
const loading = ref(false)
const showModal = ref(false)
const editando = ref(null) // null = crear | objeto = editar
const formError = ref('')

const busqueda = ref('')
const filtroPrioridad = ref('Todas')
const filtroResponsable = ref('Todos')

const formVacio = () => ({
    titulo: '',
    descripcion: '',
    asignado_a: '',
    fecha_limite: new Date().toISOString().substring(0, 10),
    prioridad: 'Media',
    estado: 'Pendiente'
})

const form = ref(formVacio())

// Hardcoded Columns List configuration
const columnsList = [
    {
        id: 'Pendiente',
        titulo: 'Pendientes',
        textClass: 'text-on-surface',
        badgeClass: 'bg-outline-variant/30 text-on-surface-variant'
    },
    {
        id: 'En Proceso',
        titulo: 'En Proceso',
        textClass: 'text-on-surface',
        badgeClass: 'bg-tertiary-container/30 text-on-tertiary-container'
    },
    {
        id: 'En Revisión',
        titulo: 'En Revisión',
        textClass: 'text-on-surface',
        badgeClass: 'bg-secondary-container/30 text-on-secondary-container'
    },
    {
        id: 'Completada',
        titulo: 'Completadas',
        textClass: 'text-on-surface',
        badgeClass: 'bg-primary-container text-on-primary-container'
    },
    {
        id: 'Vencida',
        titulo: 'Vencidas',
        textClass: 'text-error',
        badgeClass: 'bg-error-container text-on-error-container font-extrabold'
    }
]

/* ─── Drag & Drop State ─── */
const draggedTask = ref(null)

const onDragStart = (task) => {
    draggedTask.value = task
}

const onDrop = async (newStatus) => {
    if (!draggedTask.value) return

    // If dragged to virtually represented columns
    let statusToSave = newStatus
    if (newStatus === 'Vencida') {
        statusToSave = 'Pendiente'
    }

    try {
        const res = await api.put(`/dashboard/tareas/${draggedTask.value.id}`, { estado: statusToSave })
        if (res.data && res.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Tarea movida a ${newStatus}`,
                showConfirmButton: false,
                timer: 2000
            })
            await fetchTareas()
        }
    } catch (err) {
        console.error('Error al actualizar estado por drag and drop:', err)
        Swal.fire('Error', 'No se pudo mover la tarea', 'error')
    } finally {
        draggedTask.value = null
    }
}

/* ─── API Methods ─── */
const fetchTareas = async () => {
    try {
        const res = await api.get('/dashboard/tareas')
        if (res.data && res.data.success) {
            tasks.value = res.data.data || []
        }
    } catch (err) {
        console.error('Error al obtener tareas:', err)
    }
}

const fetchUsers = async () => {
    try {
        const res = await api.get('/usuarios')
        if (res.data && res.data.status === 'success') {
            users.value = res.data.data || []
        }
    } catch (err) {
        console.error('Error al obtener usuarios:', err)
    }
}

/* ─── Exports ─── */
const exportarExcel = () => {
    const API_URL = getApiBaseUrl()
    window.open(`${API_URL}/dashboard/tareas/export/excel`, '_blank')
}

const exportarPdf = () => {
    const API_URL = getApiBaseUrl()
    window.open(`${API_URL}/dashboard/tareas/export/pdf`, '_blank')
}

/* ─── Computed Properties (Filter & Map to Columns) ─── */
const tasksByColumn = computed(() => {
    const all = tasks.value || []

    // 1. Filter tasks
    const filtered = all.filter(t => {
        const q = busqueda.value.toLowerCase().trim()
        const matchesSearch = !q ||
            (t.titulo && t.titulo.toLowerCase().includes(q)) ||
            (t.descripcion && t.descripcion.toLowerCase().includes(q)) ||
            (t.asignado_nombre && t.asignado_nombre.toLowerCase().includes(q))

        const matchesPriority = filtroPrioridad.value === 'Todas' || t.prioridad === filtroPrioridad.value
        const matchesResponsable = filtroResponsable.value === 'Todos' || t.asignado_a === parseInt(filtroResponsable.value)

        return matchesSearch && matchesPriority && matchesResponsable
    })

    // 2. Map tasks to columns
    const columns = {
        'Pendiente': [],
        'En Proceso': [],
        'En Revisión': [],
        'Completada': [],
        'Vencida': []
    }

    const todayStr = new Date().toISOString().substring(0, 10)

    filtered.forEach(t => {
        // If due date has passed and task is not completed, it is virtually 'Vencida'
        const isOverdue = t.estado !== 'Completada' && t.fecha_limite < todayStr
        if (isOverdue) {
            columns['Vencida'].push(t)
        } else if (columns[t.estado]) {
            columns[t.estado].push(t)
        } else {
            columns['Pendiente'].push(t)
        }
    })

    return columns
})

/* ─── Helpers ─── */
const priorityBadgeClass = (priority) => {
    switch (priority) {
        case 'Crítica':
            return 'text-[9px] font-bold px-2 py-0.5 bg-red-500/20 text-red-300 rounded uppercase tracking-tighter border border-red-500/30'
        case 'Alta':
            return 'text-[9px] font-bold px-2 py-0.5 bg-orange-500/20 text-orange-300 rounded uppercase tracking-tighter border border-orange-500/30'
        case 'Media':
            return 'text-[9px] font-bold px-2 py-0.5 bg-blue-500/20 text-blue-300 rounded uppercase tracking-tighter border border-blue-500/30'
        default:
            return 'text-[9px] font-bold px-2 py-0.5 bg-slate-500/20 text-slate-300 rounded uppercase tracking-tighter border border-slate-500/30'
    }
}

const getInitials = (name) => {
    if (!name) return 'SA'
    const parts = name.trim().split(' ').filter(p => p.length > 0)
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const parts = dateStr.split('-')
    if (parts.length !== 3) return dateStr
    const monthsShort = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC']
    const m = parseInt(parts[1]) - 1
    return `${parts[2]} ${monthsShort[m] || 'MES'}`
}

const limpiarFiltros = () => {
    busqueda.value = ''
    filtroPrioridad.value = 'Todas'
    filtroResponsable.value = 'Todos'
}

/* ─── Modal Actions ─── */
function openModal(task = null) {
    formError.value = ''
    if (task) {
        editando.value = task
        form.value = {
            titulo: task.titulo,
            descripcion: task.descripcion || '',
            asignado_a: task.asignado_a || '',
            fecha_limite: task.fecha_limite,
            prioridad: task.prioridad,
            estado: task.estado
        }
    } else {
        editando.value = null
        form.value = formVacio()
    }
    showModal.value = true
}

function closeModal() {
    showModal.value = false
}

async function guardarTarea() {
    formError.value = ''
    if (!form.value.titulo.trim()) {
        formError.value = 'El título de la tarea es obligatorio.'
        return
    }
    if (!form.value.fecha_limite) {
        formError.value = 'La fecha límite es obligatoria.'
        return
    }

    loading.value = true
    try {
        let res
        if (editando.value) {
            res = await api.put(`/dashboard/tareas/${editando.value.id}`, form.value)
        } else {
            res = await api.post('/dashboard/tareas', form.value)
        }

        if (res.data && res.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: editando.value ? 'Tarea actualizada' : 'Tarea creada',
                showConfirmButton: false,
                timer: 3000
            })
            await fetchTareas()
            closeModal()
        } else {
            formError.value = res.data.error || 'No se pudo guardar la tarea.'
        }
    } catch (err) {
        formError.value = 'Error de conexión con el servidor.'
        console.error(err)
    } finally {
        loading.value = false
    }
}

async function eliminarTarea(id) {
    const result = await Swal.fire({
        title: '¿Eliminar tarea?',
        text: 'Estás a punto de borrar esta tarea de forma permanente. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#BA1A1A',
        cancelButtonColor: '#40484C',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    })

    if (result.isConfirmed) {
        try {
            const res = await api.delete(`/dashboard/tareas/${id}`)
            if (res.data && res.data.success) {
                Swal.fire('Eliminada', 'La tarea ha sido eliminada con éxito.', 'success')
                await fetchTareas()
                if (showModal.value && editando.value && editando.value.id === id) {
                    closeModal()
                }
            } else {
                Swal.fire('Error', res.data.error || 'No se pudo eliminar la tarea', 'error')
            }
        } catch (err) {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
            console.error(err)
        }
    }
}

onMounted(() => {
    fetchTareas()
    fetchUsers()
})
</script>

<style scoped>
/* ── Transition ── */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-active .task-card,
.modal-fade-leave-active .task-card {
    transition: transform 0.25s ease;
}

.modal-fade-enter-from .task-card,
.modal-fade-leave-to .task-card {
    transform: scale(0.95) translateY(12px);
}

/* ── Overlay ── */
.task-overlay {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(14, 40, 48, 0.75);
    backdrop-filter: blur(8px);
    padding: 16px;
}

/* ── Card ── */
.task-card {
    background: #216170;
    border: 1px solid #327f91;
    border-radius: 20px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 25px 50px -12px rgba(14, 40, 48, 0.6);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ── Header ── */
.task-header {
    padding: 22px 28px;
    background: #184e5b;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    border-bottom: 1px solid #327f91;
}

.task-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.task-header-icon {
    font-size: 26px !important;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #e0f2fe;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.task-header-title {
    font-size: 18px;
    font-weight: 800;
    color: #fff;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.task-header-sub {
    font-size: 12px;
    color: #a5d0db;
    margin: 3px 0 0;
}

.task-close {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: #e0f2fe;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.task-close:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

/* ── Body ── */
.task-body {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding: 24px 28px;
}

/* ── Fields ── */
.task-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.task-field-row {
    display: flex;
    gap: 14px;
}

.task-label {
    font-size: 11px;
    font-weight: 800;
    color: #a5d0db;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Inputs ── */
.task-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.task-input {
    width: 100%;
    padding: 11px 14px;
    background: #184e5b;
    border: 1px solid #327f91;
    border-radius: 10px;
    font-size: 14px;
    color: #fff;
    font-weight: 500;
    transition: all 0.2s;
    outline: none;
    color-scheme: dark;
}

.task-input.with-icon {
    padding-left: 40px;
}

.task-input:focus {
    border-color: #5ab1c5;
    box-shadow: 0 0 0 3px rgba(90, 177, 197, 0.2);
}

.task-input::placeholder {
    color: #6ba7b8;
    font-weight: 400;
}

.task-input-icon {
    position: absolute;
    left: 12px;
    font-size: 18px !important;
    color: #6ba7b8;
    pointer-events: none;
    transition: color 0.3s;
}

.task-input-wrap:focus-within .task-input-icon {
    color: #5ab1c5;
}

/* ── Select ── */
.task-select-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.task-select {
    appearance: none;
    padding-right: 36px;
    cursor: pointer;
}

.task-select-icon {
    position: absolute;
    right: 10px;
    font-size: 20px !important;
    color: #6ba7b8;
    pointer-events: none;
}

/* ── Textarea ── */
.task-textarea {
    width: 100%;
    padding: 11px 14px;
    background: #184e5b;
    border: 1px solid #327f91;
    border-radius: 10px;
    font-size: 14px;
    color: #fff;
    resize: none;
    transition: all 0.2s;
    outline: none;
    font-family: inherit;
    line-height: 1.5;
}

.task-textarea:focus {
    border-color: #5ab1c5;
    box-shadow: 0 0 0 3px rgba(90, 177, 197, 0.2);
}

.task-textarea::placeholder {
    color: #6ba7b8;
}

/* ── Footer ── */
.task-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 28px;
    background: #184e5b;
    border-top: 1px solid #327f91;
}

.task-footer-right {
    display: flex;
    gap: 10px;
    margin-left: auto;
}

.task-btn-delete {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: transparent;
    color: #f87171;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.task-btn-delete:hover {
    background: #7f1d1d;
    border-color: #fca5a5;
}

.task-btn-cancel {
    padding: 10px 20px;
    background: #216170;
    color: #fff;
    border: 1px solid #327f91;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.task-btn-cancel:hover {
    background: #327f91;
}

.task-btn-save {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(14, 40, 48, 0.3);
}

.task-btn-save:hover {
    filter: brightness(1.15);
    transform: translateY(-1px);
}
</style>
