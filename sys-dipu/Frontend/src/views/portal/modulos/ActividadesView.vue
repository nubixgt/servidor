<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Actividades y Eventos</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Registro y planificación de eventos públicos, reuniones, foros y actividades de representación.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openModal()" class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span> Nueva Actividad
                </button>
            </div>
        </header>

        <!-- Stats -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Actividades</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">{{ actividades.length }}</span>
                    <span class="text-primary font-bold text-sm">{{ proximosEventosCount }} Próximas</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Foros / Congresos</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ actividades.filter(a => a.tipo === 'Foro' || a.tipo === 'Protocolario').length }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Reuniones</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ actividades.filter(a => a.tipo === 'Reunión').length }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Inspecciones</span>
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ actividades.filter(a => a.tipo === 'Inspección').length }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="busqueda" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por nombre, lugar, tipo o descripción..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button 
                    v-for="e in ['Todas', 'Programada', 'Realizada', 'Cancelada']" 
                    :key="e"
                    @click="filtroEstado = e"
                    :class="filtroEstado === e 
                        ? 'px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm'
                        : 'px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors'"
                >
                    {{ e }}
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-surface-container-lowest rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <div class="w-full overflow-x-auto pb-4">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                                <th class="px-8 py-5">Fecha y Hora</th>
                                <th class="px-8 py-5">Actividad</th>
                                <th class="px-8 py-5">Lugar</th>
                                <th class="px-8 py-5">Tipo</th>
                                <th class="px-8 py-5">Estado</th>
                                <th class="px-8 py-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="actividadesFiltradas.length === 0">
                                <td colspan="6" class="px-8 py-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                                    No hay actividades registradas
                                </td>
                            </tr>
                            <tr v-for="act in actividadesFiltradas" :key="act.id" class="group hover:bg-surface-container-low transition-colors border-t border-outline-variant/30">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-on-surface">{{ formatoFecha(act.fecha) }}</span>
                                        <span class="text-xs text-on-surface-variant">{{ act.hora || 'Todo el día' }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="max-w-xs">
                                        <p class="font-bold text-on-surface">{{ act.nombre }}</p>
                                        <p class="text-xs text-on-surface-variant line-clamp-1">{{ act.descripcion }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-on-surface-variant">location_on</span>
                                        <span class="text-sm text-on-surface">{{ act.lugar }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1 text-xs font-bold rounded-full', tipoClass(act.tipo)]">
                                        {{ act.tipo }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1 text-xs font-bold rounded-full', estadoClass(act.estado)]">
                                        {{ act.estado }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="openModal(act)" class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                                    <button @click="eliminar(act.id)" class="p-2 text-on-surface-variant hover:text-error transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">delete</span></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="closeModal">
                    <div class="bg-surface rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col border border-outline-variant/30">
                        
                        <!-- Modal Header -->
                        <div class="px-6 py-5 bg-surface-container flex items-center justify-between border-b border-outline-variant/20">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary text-2xl">event_upcoming</span>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ editando ? 'Editar Actividad' : 'Nueva Actividad' }}</h3>
                                    <p class="text-xs text-on-surface-variant">Completa los campos de la actividad de representación</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="p-1 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nombre de la Actividad *</label>
                                <input v-model="form.nombre" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Foro de Desarrollo Económico Local" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Lugar / Ubicación *</label>
                                    <input v-model="form.lugar" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Hotel Camino Real" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tipo *</label>
                                    <select v-model="form.tipo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Foro">Foro</option>
                                        <option value="Reunión">Reunión</option>
                                        <option value="Inspección">Inspección</option>
                                        <option value="Protocolario">Protocolario</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Fecha *</label>
                                    <input v-model="form.fecha" type="date" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Hora *</label>
                                    <input v-model="form.hora" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. 09:00 AM - 13:00 PM" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Estado</label>
                                <select v-model="form.estado" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                    <option value="Programada">Programada</option>
                                    <option value="Realizada">Realizada</option>
                                    <option value="Cancelada">Cancelada</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Descripción</label>
                                <textarea v-model="form.descripcion" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20 resize-none" rows="3" placeholder="Describe brevemente de qué trata la actividad..."></textarea>
                            </div>

                            <p v-if="formError" class="text-error text-xs font-bold">{{ formError }}</p>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-surface-container flex items-center justify-between border-t border-outline-variant/20">
                            <button v-if="editando" @click="eliminar(editando.id); closeModal()" class="px-4 py-2 bg-error-container text-on-error-container font-semibold rounded-lg hover:bg-error-container/80 transition-colors text-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">delete</span> Eliminar
                            </button>
                            <div class="flex items-center gap-2 ml-auto">
                                <button @click="closeModal" class="px-4 py-2 bg-surface-container-high text-on-surface font-semibold rounded-lg hover:bg-surface-container-highest transition-colors text-sm">Cancelar</button>
                                <button @click="guardar" class="px-5 py-2 bg-primary text-on-primary font-bold rounded-lg hover:bg-primary/95 transition-colors text-sm flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base">{{ editando ? 'save' : 'add' }}</span>
                                    {{ editando ? 'Guardar' : 'Registrar' }}
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
import api from '../../../services/api'

const actividades = ref([])
const busqueda = ref('')
const filtroEstado = ref('Todas')

const showModal = ref(false)
const editando = ref(null)
const formError = ref('')
const formVacio = () => ({ nombre: '', lugar: '', tipo: 'Foro', fecha: '', hora: '', estado: 'Programada', descripcion: '' })
const form = ref(formVacio())

const cargarActividades = async () => {
    try {
        const res = await api.get('/modulos/actividades')
        if (res.data && res.data.success) {
            actividades.value = res.data.data
        }
    } catch (err) {
        console.error('Error al cargar actividades:', err)
    }
}

onMounted(() => {
    cargarActividades()
})

const actividadesFiltradas = computed(() => {
    let lista = actividades.value || []
    if (filtroEstado.value !== 'Todas') {
        lista = lista.filter(a => a.estado === filtroEstado.value)
    }
    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase()
        lista = lista.filter(a => 
            (a.nombre && a.nombre.toLowerCase().includes(q)) ||
            (a.lugar && a.lugar.toLowerCase().includes(q)) ||
            (a.tipo && a.tipo.toLowerCase().includes(q)) ||
            (a.descripcion && a.descripcion.toLowerCase().includes(q))
        )
    }
    return lista
})

const proximosEventosCount = computed(() => {
    return actividades.value.filter(a => a.estado === 'Programada').length
})

function tipoClass(tipo) {
    if (tipo === 'Foro') return 'bg-tertiary-container text-on-tertiary-container'
    if (tipo === 'Reunión') return 'bg-primary-container text-on-primary-container'
    if (tipo === 'Inspección') return 'bg-secondary-container text-on-secondary-container'
    return 'bg-surface-container-highest text-on-surface-variant'
}

function estadoClass(estado) {
    if (estado === 'Programada') return 'bg-primary-container text-on-primary-container'
    if (estado === 'Realizada') return 'bg-tertiary-container text-on-tertiary-container'
    if (estado === 'Cancelada') return 'bg-error-container text-on-error-container'
    return 'bg-surface-container-highest text-on-surface-variant'
}

function formatoFecha(fechaStr) {
    if (!fechaStr) return '-'
    const partes = fechaStr.split('-')
    if (partes.length === 3) {
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
        const dia = parseInt(partes[2])
        const mesIndex = parseInt(partes[1]) - 1
        const anio = partes[0]
        return `${dia} ${meses[mesIndex]} ${anio}`
    }
    return fechaStr
}

function openModal(act = null) {
    formError.value = ''
    if (act) {
        editando.value = act
        form.value = { ...act }
    } else {
        editando.value = null
        form.value = formVacio()
    }
    showModal.value = true
}

function closeModal() {
    showModal.value = false
}

async function guardar() {
    formError.value = ''
    if (!form.value.nombre.trim()) { formError.value = 'El nombre de la actividad es obligatorio.'; return }
    if (!form.value.lugar.trim()) { formError.value = 'El lugar es obligatorio.'; return }
    if (!form.value.fecha) { formError.value = 'La fecha es obligatoria.'; return }

    try {
        if (editando.value) {
            await api.put(`/modulos/actividades/${editando.value.id}`, form.value)
        } else {
            await api.post('/modulos/actividades', form.value)
        }
        await cargarActividades()
        closeModal()
    } catch (err) {
        formError.value = 'Error al guardar el registro en el servidor.'
        console.error(err)
    }
}

async function eliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta actividad?')) {
        try {
            await api.delete(`/modulos/actividades/${id}`)
            await cargarActividades()
        } catch (err) {
            console.error('Error al eliminar actividad:', err)
        }
    }
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-active > div, .modal-fade-leave-active > div {
    transition: transform 0.2s ease;
}
.modal-fade-enter-from > div, .modal-fade-leave-to > div {
    transform: scale(0.96);
}
</style>
