<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Redes Sociales y Prensa</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Gestión de comunicación digital, comunicados de prensa, métricas de impacto y monitoreo de medios.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openModal()" class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span> Nueva Publicación
                </button>
            </div>
        </header>

        <!-- Stats -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Alcance Total (Mes)</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">1.2M</span>
                    <span class="text-primary font-bold text-sm">+15% vs mes anterior</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Publicaciones (Redes)</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ publicacionesRedesCount }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Comunicados de Prensa</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ comunicadosPrensaCount }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Menciones Positivas</span>
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">68%</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="busqueda" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por título, plataforma o descripción..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button 
                    v-for="t in ['Todas', 'Redes', 'Prensa']" 
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
                                <th class="px-8 py-5">Fecha</th>
                                <th class="px-8 py-5">Contenido</th>
                                <th class="px-8 py-5">Plataforma/Medio</th>
                                <th class="px-8 py-5">Estado</th>
                                <th class="px-8 py-5">Impacto</th>
                                <th class="px-8 py-5">Interacciones</th>
                                <th class="px-8 py-5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="publicacionesFiltradas.length === 0">
                                <td colspan="7" class="px-8 py-12 text-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                                    No hay publicaciones registradas
                                </td>
                            </tr>
                            <tr v-for="pub in publicacionesFiltradas" :key="pub.id" class="group hover:bg-surface-container-low transition-all duration-300 border-t border-outline-variant/30">
                                <td class="px-8 py-6">
                                    <p class="text-sm font-bold text-on-surface">{{ formatoFecha(pub.fecha) }}</p>
                                    <p class="text-xs text-on-surface-variant">{{ pub.hora || '-' }}</p>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="max-w-xs">
                                        <p class="font-bold text-on-surface">{{ pub.titulo }}</p>
                                        <p class="text-xs text-on-surface-variant line-clamp-1">{{ pub.descripcion }}</p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-on-surface-variant">{{ plataformaIcon(pub.plataforma) }}</span>
                                        <span class="text-sm text-on-surface">{{ pub.plataforma }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1 text-xs font-bold rounded-full', estadoClass(pub.estado)]">
                                        {{ pub.estado }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1 text-xs font-bold rounded-full', impactoClass(pub.impacto)]">
                                        {{ pub.impacto }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-bold text-on-surface">{{ pub.interacciones || '-' }}</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <a v-if="pub.enlace" :href="pub.enlace" target="_blank" class="p-2 text-on-surface-variant hover:text-primary transition-all duration-300 opacity-0 group-hover:opacity-100 inline-block"><span class="material-symbols-outlined text-xl">open_in_new</span></a>
                                    <button @click="openModal(pub)" class="p-2 text-on-surface-variant hover:text-primary transition-all duration-300 opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                                    <button @click="eliminar(pub.id)" class="p-2 text-on-surface-variant hover:text-error transition-all duration-300 opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">delete</span></button>
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
                                <span class="material-symbols-outlined text-primary text-2xl">campaign</span>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ editando ? 'Editar Publicación' : 'Nueva Publicación' }}</h3>
                                    <p class="text-xs text-on-surface-variant">Completa los campos del comunicado o post de redes</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="p-1 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Título de la Publicación *</label>
                                <input v-model="form.titulo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Post: Aprobación Ley de Movilidad" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Plataforma / Medio *</label>
                                    <select v-model="form.plataforma" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="X / Twitter">X / Twitter</option>
                                        <option value="Facebook">Facebook</option>
                                        <option value="TikTok">TikTok</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="Medios Nacionales">Medios Nacionales</option>
                                        <option value="Prensa">Prensa</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Enlace / URL de la Publicación</label>
                                    <input v-model="form.enlace" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="https://x.com/diputado/status/1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Fecha *</label>
                                    <input v-model="form.fecha" type="date" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Hora *</label>
                                    <input v-model="form.hora" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. 10:30 AM" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="flex flex-col gap-1.5 col-span-1">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Estado</label>
                                    <select v-model="form.estado" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Publicado">Publicado</option>
                                        <option value="Programado">Programado</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5 col-span-1">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Impacto</label>
                                    <select v-model="form.impacto" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Alto">Alto</option>
                                        <option value="Medio">Medio</option>
                                        <option value="Bajo">Bajo</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5 col-span-1">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Interacciones</label>
                                    <input v-model="form.interacciones" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. 12.5K o -" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Contenido / Descripción</label>
                                <textarea v-model="form.descripcion" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20 resize-none" rows="3" placeholder="Contenido textual de la publicación..."></textarea>
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
import Swal from 'sweetalert2'

const redes = ref([])
const busqueda = ref('')
const filtroTipo = ref('Todas')

const showModal = ref(false)
const editando = ref(null)
const formError = ref('')
const formVacio = () => ({ titulo: '', plataforma: 'X / Twitter', enlace: '', fecha: '', hora: '', estado: 'Publicado', impacto: 'Medio', interacciones: '-', descripcion: '' })
const form = ref(formVacio())

const cargarRedes = async () => {
    try {
        const res = await api.get('/modulos/redes')
        if (res.data && res.data.success) {
            redes.value = res.data.data
        }
    } catch (err) {
        console.error('Error al cargar redes:', err)
    }
}

onMounted(() => {
    cargarRedes()
})

const publicacionesFiltradas = computed(() => {
    let lista = redes.value || []
    if (filtroTipo.value === 'Redes') {
        lista = lista.filter(r => r.plataforma !== 'Medios Nacionales' && r.plataforma !== 'Prensa')
    } else if (filtroTipo.value === 'Prensa') {
        lista = lista.filter(r => r.plataforma === 'Medios Nacionales' || r.plataforma === 'Prensa')
    }
    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase()
        lista = lista.filter(r => 
            (r.titulo && r.titulo.toLowerCase().includes(q)) ||
            (r.plataforma && r.plataforma.toLowerCase().includes(q)) ||
            (r.descripcion && r.descripcion.toLowerCase().includes(q))
        )
    }
    return lista
})

const publicacionesRedesCount = computed(() => {
    return redes.value.filter(r => r.plataforma !== 'Medios Nacionales' && r.plataforma !== 'Prensa').length
})

const comunicadosPrensaCount = computed(() => {
    return redes.value.filter(r => r.plataforma === 'Medios Nacionales' || r.plataforma === 'Prensa').length
})

function plataformaIcon(plataforma) {
    if (plataforma === 'X / Twitter') return 'alternate_email'
    if (plataforma === 'Facebook') return 'public'
    if (plataforma === 'TikTok') return 'music_note'
    if (plataforma === 'Instagram') return 'photo_camera'
    if (plataforma === 'Medios Nacionales') return 'newspaper'
    if (plataforma === 'Prensa') return 'article'
    return 'share'
}

function estadoClass(estado) {
    if (estado === 'Publicado') return 'bg-primary-container text-on-primary-container'
    if (estado === 'Programado') return 'bg-tertiary-container text-on-tertiary-container'
    return 'bg-surface-container-highest text-on-surface-variant'
}

function impactoClass(impacto) {
    if (impacto === 'Alto') return 'bg-primary-container text-on-primary-container font-extrabold'
    if (impacto === 'Medio') return 'bg-secondary-container text-on-secondary-container'
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

function openModal(pub = null) {
    formError.value = ''
    if (pub) {
        editando.value = pub
        form.value = { ...pub }
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
    if (!form.value.titulo.trim()) { formError.value = 'El título de la publicación es obligatorio.'; return }
    if (!form.value.plataforma) { formError.value = 'La plataforma o medio es obligatorio.'; return }
    if (!form.value.fecha) { formError.value = 'La fecha es obligatoria.'; return }
    if (!form.value.hora || !form.value.hora.trim()) { formError.value = 'La hora de publicación es obligatoria.'; return }

    try {
        Swal.fire({
            title: editando.value ? 'Actualizando...' : 'Registrando...',
            allowOutsideClick: false,
            background: '#1e293b',
            color: '#fff',
            didOpen: () => {
                Swal.showLoading()
            }
        })

        if (editando.value) {
            await api.put(`/modulos/redes/${editando.value.id}`, form.value)
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'La publicación se ha actualizado correctamente.',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#fff',
                iconColor: '#34d399'
            })
        } else {
            await api.post('/modulos/redes', form.value)
            Swal.fire({
                icon: 'success',
                title: '¡Registrado!',
                text: 'La publicación se ha registrado correctamente.',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#fff',
                iconColor: '#34d399'
            })
        }
        await cargarRedes()
        closeModal()
    } catch (err) {
        console.error(err)
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema al procesar la solicitud en el servidor.',
            background: '#1e293b',
            color: '#fff',
            confirmButtonColor: '#3b82f6'
        })
    }
}

async function eliminar(id) {
    const resultado = await Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará de forma permanente el registro de la publicación.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#475569',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: '#1e293b',
        color: '#fff'
    })

    if (resultado.isConfirmed) {
        try {
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                background: '#1e293b',
                color: '#fff',
                didOpen: () => {
                    Swal.showLoading()
                }
            })
            await api.delete(`/modulos/redes/${id}`)
            await cargarRedes()
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado!',
                text: 'La publicación ha sido eliminada correctamente.',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#fff',
                iconColor: '#ef4444'
            })
        } catch (err) {
            console.error('Error al eliminar publicación:', err)
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo eliminar el registro de la base de datos.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#3b82f6'
            })
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
