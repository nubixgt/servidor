<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Iniciativas de Ley</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Gestión integral de propuestas legislativas y seguimiento de estados parlamentarios.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openModal()" class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span> Nueva Iniciativa
                </button>
            </div>
        </header>

        <!-- Stats -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Iniciativas</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">{{ iniciativas.length }}</span>
                    <span class="text-primary font-bold text-sm">Registradas</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">En Comisión</span>
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ iniciativas.filter(i => i.estado === 'En Comisión').length }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Aprobadas</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ iniciativas.filter(i => i.estado === 'Aprobada').length }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Borradores</span>
                        <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ iniciativas.filter(i => i.estado === 'Borrador').length }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="busqueda" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por título, folio o autor..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button 
                    v-for="f in ['Todas', 'Borrador', 'En Comisión', 'Aprobada', 'Observada']" 
                    :key="f"
                    @click="filtroActivo = f"
                    :class="filtroActivo === f 
                        ? 'px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm'
                        : 'px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors'"
                >
                    {{ f }}
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
                            <th class="px-8 py-5">Referencia</th>
                            <th class="px-8 py-5">Título de la Iniciativa</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5">Fecha</th>
                            <th class="px-8 py-5">Autor</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="iniciativasFiltradas.length === 0">
                            <td colspan="6" class="px-8 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                                No hay iniciativas registradas
                            </td>
                        </tr>
                        <tr v-for="ini in iniciativasFiltradas" :key="ini.id" class="group hover:bg-surface-container-low transition-colors border-t border-outline-variant/30">
                            <td class="px-8 py-6"><span class="font-mono text-xs text-on-surface-variant">{{ ini.referencia }}</span></td>
                            <td class="px-8 py-6">
                                <div class="max-w-md">
                                    <p class="font-bold text-on-surface mb-1">{{ ini.titulo }}</p>
                                    <p class="text-xs text-on-surface-variant line-clamp-1">{{ ini.descripcion }}</p>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span :class="['px-3 py-1 text-xs font-bold rounded-full', estadoClass(ini.estado)]">
                                    {{ ini.estado }}
                                </span>
                            </td>
                            <td class="px-8 py-6"><p class="text-sm text-on-surface">{{ formatoFecha(ini.fecha) }}</p></td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center text-[10px] font-bold text-on-primary-container">
                                        {{ initials(ini.autor) }}
                                    </div>
                                    <span class="text-sm">{{ ini.autor }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button @click="openModal(ini)" class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                                <button @click="eliminar(ini.id)" class="p-2 text-on-surface-variant hover:text-error transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">delete</span></button>
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
                                <span class="material-symbols-outlined text-primary text-2xl">description</span>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ editando ? 'Editar Iniciativa' : 'Nueva Iniciativa' }}</h3>
                                    <p class="text-xs text-on-surface-variant">Completa los campos del registro legislativo</p>
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
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Referencia *</label>
                                    <input v-model="form.referencia" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="L-2024-001" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Autor *</label>
                                    <input v-model="form.autor" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Nombre de diputado" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Título de la Iniciativa *</label>
                                <input v-model="form.titulo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Ley de Transparencia Algorítmica" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Estado</label>
                                    <select v-model="form.estado" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Borrador">Borrador</option>
                                        <option value="En Comisión">En Comisión</option>
                                        <option value="Aprobada">Aprobada</option>
                                        <option value="Observada">Observada</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Fecha *</label>
                                    <input v-model="form.fecha" type="date" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Descripción / Propósito</label>
                                <textarea v-model="form.descripcion" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20 resize-none" rows="3" placeholder="Describe brevemente de qué trata la propuesta..."></textarea>
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

const iniciativas = ref([])
const busqueda = ref('')
const filtroActivo = ref('Todas')

const showModal = ref(false)
const editando = ref(null)
const formError = ref('')
const formVacio = () => ({ referencia: '', autor: '', titulo: '', estado: 'Borrador', fecha: '', descripcion: '' })
const form = ref(formVacio())

const cargarIniciativas = async () => {
    try {
        const res = await api.get('/modulos/iniciativas')
        if (res.data && res.data.success) {
            iniciativas.value = res.data.data
        }
    } catch (err) {
        console.error('Error al cargar iniciativas:', err)
    }
}

onMounted(() => {
    cargarIniciativas()
})

const iniciativasFiltradas = computed(() => {
    let lista = iniciativas.value || []
    if (filtroActivo.value !== 'Todas') {
        lista = lista.filter(i => i.estado === filtroActivo.value)
    }
    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase()
        lista = lista.filter(i => 
            (i.titulo && i.titulo.toLowerCase().includes(q)) ||
            (i.referencia && i.referencia.toLowerCase().includes(q)) ||
            (i.autor && i.autor.toLowerCase().includes(q))
        )
    }
    return lista
})

function initials(name) {
    if (!name) return 'IN'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function estadoClass(estado) {
    if (estado === 'Borrador') return 'bg-surface-container-highest text-on-surface-variant'
    if (estado === 'En Comisión') return 'bg-secondary-container text-on-secondary-container'
    if (estado === 'Aprobada') return 'bg-primary-container text-on-primary-container'
    if (estado === 'Observada') return 'bg-error-container text-on-error-container'
    return 'bg-surface-container-highest text-on-surface-variant'
}

function formatoFecha(fechaStr) {
    if (!fechaStr) return '-'
    const partes = fechaStr.split('-')
    if (partes.length === 3) {
        // Retornar en formato mas legible: ej. 12 Oct 2024
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
        const dia = parseInt(partes[2])
        const mesIndex = parseInt(partes[1]) - 1
        const anio = partes[0]
        return `${dia} ${meses[mesIndex]} ${anio}`
    }
    return fechaStr
}

function openModal(ini = null) {
    formError.value = ''
    if (ini) {
        editando.value = ini
        form.value = { ...ini }
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
    if (!form.value.referencia.trim()) { formError.value = 'La referencia es obligatoria.'; return }
    if (!form.value.titulo.trim()) { formError.value = 'El título es obligatorio.'; return }
    if (!form.value.autor.trim()) { formError.value = 'El autor es obligatorio.'; return }
    if (!form.value.fecha) { formError.value = 'La fecha es obligatoria.'; return }

    try {
        if (editando.value) {
            await api.put(`/modulos/iniciativas/${editando.value.id}`, form.value)
        } else {
            await api.post('/modulos/iniciativas', form.value)
        }
        await cargarIniciativas()
        closeModal()
    } catch (err) {
        formError.value = 'Error al guardar el registro en el servidor.'
        console.error(err)
    }
}

async function eliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta iniciativa?')) {
        try {
            await api.delete(`/modulos/iniciativas/${id}`)
            await cargarIniciativas()
        } catch (err) {
            console.error('Error al eliminar iniciativa:', err)
        }
    }
}
</script>

<style scoped>
/* Transición simple para el modal */
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
