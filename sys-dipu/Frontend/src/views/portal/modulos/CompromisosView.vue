<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Compromisos Distritales</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Gestión y seguimiento de promesas de campaña, proyectos locales y atención ciudadana en el distrito.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openModal()" class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span> Nuevo Compromiso
                </button>
            </div>
        </header>

        <!-- Stats -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Compromisos</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">{{ compromisos.length }}</span>
                    <span class="text-primary font-bold text-sm">{{ avanceGlobal }}% Avance Global</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">En Ejecución</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ compromisos.filter(c => c.estado === 'En Ejecución').length }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Completados</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ compromisos.filter(c => c.estado === 'Completado').length }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Pendientes</span>
                        <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ compromisos.filter(c => c.estado === 'Pendiente').length }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="busqueda" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por proyecto, municipio o descripción..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button 
                    v-for="t in ['Todos', 'Infraestructura', 'Social', 'Económico']" 
                    :key="t"
                    @click="filtroTipo = t"
                    :class="filtroTipo === t 
                        ? 'px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm'
                        : 'px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors'"
                >
                    {{ t }}
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
                                <th class="px-8 py-5">Folio</th>
                                <th class="px-8 py-5">Proyecto / Compromiso</th>
                                <th class="px-8 py-5">Municipio</th>
                                <th class="px-8 py-5">Fecha Entrega</th>
                                <th class="px-8 py-5">Estado</th>
                                <th class="px-8 py-5">Avance</th>
                                <th class="px-8 py-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="compromisosFiltrados.length === 0">
                                <td colspan="7" class="px-8 py-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                                    No hay compromisos registrados
                                </td>
                            </tr>
                            <tr v-for="comp in compromisosFiltrados" :key="comp.id" class="group hover:bg-surface-container-low transition-colors border-t border-outline-variant/30">
                                <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">{{ comp.folio }}</span></td>
                                <td class="px-8 py-6">
                                    <div class="max-w-xs">
                                        <p class="font-bold text-on-surface">{{ comp.compromiso }}</p>
                                        <p class="text-xs text-on-surface-variant line-clamp-1">{{ comp.descripcion }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-on-surface-variant">location_on</span>
                                        <span class="text-sm text-on-surface">{{ comp.lugar }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6"><p class="text-sm text-on-surface">{{ formatoFecha(comp.fecha) }}</p></td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1 text-xs font-bold rounded-full', estadoClass(comp.estado)]">
                                        {{ comp.estado }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden max-w-[100px]">
                                            <div :class="['h-full rounded-full', avanceColor(comp.avance)]" :style="{ width: comp.avance + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-bold text-on-surface">{{ comp.avance }}%</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="openModal(comp)" class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                                    <button @click="eliminar(comp.id)" class="p-2 text-on-surface-variant hover:text-error transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">delete</span></button>
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
                                <span class="material-symbols-outlined text-primary text-2xl">handshake</span>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ editando ? 'Editar Compromiso' : 'Nuevo Compromiso' }}</h3>
                                    <p class="text-xs text-on-surface-variant">Completa los campos del compromiso distrital</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="p-1 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Folio *</label>
                                    <input v-model="form.folio" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="CD-001" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Municipio *</label>
                                    <input v-model="form.lugar" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Villa Nueva" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Proyecto / Compromiso *</label>
                                <input v-model="form.compromiso" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Dotación de computadoras" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tipo *</label>
                                    <select v-model="form.tipo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Infraestructura">Infraestructura</option>
                                        <option value="Social">Social</option>
                                        <option value="Económico">Económico</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Fecha de Entrega *</label>
                                    <input v-model="form.fecha" type="date" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Estado</label>
                                    <select v-model="form.estado" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="En Ejecución">En Ejecución</option>
                                        <option value="Completado">Completado</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex justify-between items-center">
                                        <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Avance (%)</label>
                                        <span class="text-xs font-bold text-primary">{{ form.avance }}%</span>
                                    </div>
                                    <input v-model.number="form.avance" type="range" min="0" max="100" class="w-full accent-primary h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer mt-3" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Descripción del Proyecto</label>
                                <textarea v-model="form.descripcion" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20 resize-none" rows="3" placeholder="Describe brevemente el alcance del proyecto local..."></textarea>
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

const compromisos = ref([])
const busqueda = ref('')
const filtroTipo = ref('Todos')

const showModal = ref(false)
const editando = ref(null)
const formError = ref('')
const formVacio = () => ({ folio: '', lugar: '', compromiso: '', tipo: 'Infraestructura', fecha: '', estado: 'Pendiente', avance: 0, descripcion: '' })
const form = ref(formVacio())

const cargarCompromisos = async () => {
    try {
        const res = await api.get('/modulos/compromisos')
        if (res.data && res.data.success) {
            compromisos.value = res.data.data
        }
    } catch (err) {
        console.error('Error al cargar compromisos:', err)
    }
}

onMounted(() => {
    cargarCompromisos()
})

const compromisosFiltrados = computed(() => {
    let lista = compromisos.value || []
    if (filtroTipo.value !== 'Todos') {
        lista = lista.filter(c => c.tipo === filtroTipo.value)
    }
    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase()
        lista = lista.filter(c => 
            (c.compromiso && c.compromiso.toLowerCase().includes(q)) ||
            (c.lugar && c.lugar.toLowerCase().includes(q)) ||
            (c.descripcion && c.descripcion.toLowerCase().includes(q)) ||
            (c.folio && c.folio.toLowerCase().includes(q))
        )
    }
    return lista
})

const avanceGlobal = computed(() => {
    if (compromisos.value.length === 0) return 0
    const suma = compromisos.value.reduce((acc, curr) => acc + (parseInt(curr.avance) || 0), 0)
    return Math.round(suma / compromisos.value.length)
})

function estadoClass(estado) {
    if (estado === 'Pendiente') return 'bg-surface-container-highest text-on-surface-variant'
    if (estado === 'En Ejecución') return 'bg-tertiary-container text-on-tertiary-container'
    if (estado === 'Completado') return 'bg-primary-container text-on-primary-container'
    return 'bg-surface-container-highest text-on-surface-variant'
}

function avanceColor(avance) {
    if (avance >= 100) return 'bg-primary'
    if (avance >= 50) return 'bg-tertiary'
    return 'bg-outline-variant'
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

function openModal(comp = null) {
    formError.value = ''
    if (comp) {
        editando.value = comp
        form.value = { ...comp }
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
    if (!form.value.folio.trim()) { formError.value = 'El folio es obligatorio.'; return }
    if (!form.value.compromiso.trim()) { formError.value = 'El proyecto/compromiso es obligatorio.'; return }
    if (!form.value.lugar.trim()) { formError.value = 'El municipio es obligatorio.'; return }
    if (!form.value.fecha) { formError.value = 'La fecha de entrega es obligatoria.'; return }

    try {
        if (editando.value) {
            await api.put(`/modulos/compromisos/${editando.value.id}`, form.value)
        } else {
            await api.post('/modulos/compromisos', form.value)
        }
        await cargarCompromisos()
        closeModal()
    } catch (err) {
        formError.value = 'Error al guardar el registro en el servidor.'
        console.error(err)
    }
}

async function eliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este compromiso?')) {
        try {
            await api.delete(`/modulos/compromisos/${id}`)
            await cargarCompromisos()
        } catch (err) {
            console.error('Error al eliminar compromiso:', err)
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

