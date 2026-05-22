<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Afiliaciones Políticas</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Gestión de base de datos de afiliados, simpatizantes, líderes comunitarios y estructura partidaria.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportarCSV" class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
                    <span class="material-symbols-outlined text-xl">ios_share</span> Exportar
                </button>
                <button @click="openModal()" class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">person_add</span> Nuevo Registro
                </button>
            </div>
        </header>

        <!-- Stats Dashboard -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Afiliados Activos</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">{{ totalActivos }}</span>
                    <span class="text-primary font-bold text-sm">+{{ nuevosEsteMes }} este mes</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Líderes Comunitarios</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ totalLideres }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Simpatizantes Base</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ totalBase }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Registros Inactivos</span>
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ totalInactivos }}</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="busqueda" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por nombre, DPI o municipio..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
                <button 
                    v-for="f in ['Todos', 'Afiliados', 'Líderes']" 
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
                            <th class="px-8 py-5">Nombre Completo</th>
                            <th class="px-8 py-5">DPI / Identificación</th>
                            <th class="px-8 py-5">Municipio / Zona</th>
                            <th class="px-8 py-5">Tipo de Registro</th>
                            <th class="px-8 py-5">Fecha de Ingreso</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="afiliacionesFiltradas.length === 0">
                            <td colspan="7" class="px-8 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl block mb-2">inbox</span>
                                No hay afiliaciones registradas
                            </td>
                        </tr>
                        <tr v-for="af in afiliacionesFiltradas" :key="af.id" class="group hover:bg-surface-container-low transition-colors border-t border-outline-variant/30">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container font-bold text-xs">
                                        {{ initials(af.nombre_completo) }}
                                    </div>
                                    <span class="font-bold text-on-surface">{{ af.nombre_completo }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="font-mono text-sm text-on-surface">{{ af.dpi }}</span></td>
                            <td class="px-8 py-6"><span class="text-sm text-on-surface">{{ af.municipio }}</span></td>
                            <td class="px-8 py-6">
                                <span :class="['px-3 py-1 text-xs font-bold rounded-full', tipoClass(af.tipo_registro)]">
                                    {{ af.tipo_registro }}
                                </span>
                            </td>
                            <td class="px-8 py-6"><span class="text-sm text-on-surface">{{ formatoFecha(af.fecha_ingreso) }}</span></td>
                            <td class="px-8 py-6">
                                <span :class="['px-2.5 py-0.5 text-xs font-bold rounded-full', estadoClass(af.estado)]">
                                    {{ af.estado }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button @click="openModal(af)" class="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">edit</span></button>
                                <button @click="eliminar(af.id)" class="p-2 text-on-surface-variant hover:text-error transition-colors opacity-0 group-hover:opacity-100"><span class="material-symbols-outlined">delete</span></button>
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
                                <span class="material-symbols-outlined text-primary text-2xl">person_add</span>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ editando ? 'Editar Registro' : 'Nuevo Registro' }}</h3>
                                    <p class="text-xs text-on-surface-variant">Completa los datos del afiliado o líder partidario</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="p-1 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nombre Completo *</label>
                                <input v-model="form.nombre_completo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Juan Carlos López" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">DPI / IDENTIFICACIÓN *</label>
                                    <input 
                                        :value="form.dpi" 
                                        @input="onDpiInput" 
                                        maxlength="15" 
                                        class="px-4 py-3 bg-surface-container-low text-on-surface border border-primary/40 focus:border-primary focus:ring-4 focus:ring-primary/10 rounded-xl outline-none font-mono text-base tracking-widest transition-all" 
                                        placeholder="3601-88416-0101" 
                                    />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Municipio *</label>
                                    <input v-model="form.municipio" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Villa Nueva" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Tipo de Registro</label>
                                    <select v-model="form.tipo_registro" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Afiliado Base">Afiliado Base</option>
                                        <option value="Líder Comunitario">Líder Comunitario</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Fecha de Ingreso *</label>
                                    <input v-model="form.fecha_ingreso" type="date" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Estado</label>
                                <select v-model="form.estado" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
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

const afiliaciones = ref([])
const busqueda = ref('')
const filtroActivo = ref('Todos')

const showModal = ref(false)
const editando = ref(null)
const formError = ref('')
const formVacio = () => {
    const hoy = new Date().toISOString().split('T')[0]
    return { 
        nombre_completo: '', 
        dpi: '', 
        municipio: '', 
        tipo_registro: 'Afiliado Base', 
        fecha_ingreso: hoy, 
        estado: 'Activo' 
    }
}
const form = ref(formVacio())

const cargarAfiliaciones = async () => {
    try {
        const res = await api.get('/modulos/afiliaciones')
        if (res.data && res.data.success) {
            afiliaciones.value = res.data.data
        }
    } catch (err) {
        console.error('Error al cargar afiliaciones:', err)
    }
}

onMounted(() => {
    cargarAfiliaciones()
})

const formatearDPI = (value) => {
    let clean = value.replace(/\D/g, '');
    if (clean.length > 13) {
        clean = clean.slice(0, 13);
    }
    let formatted = '';
    if (clean.length > 0) {
        formatted += clean.slice(0, 4);
    }
    if (clean.length > 4) {
        formatted += '-' + clean.slice(4, 9);
    }
    if (clean.length > 9) {
        formatted += '-' + clean.slice(9, 13);
    }
    return formatted;
}

function onDpiInput(e) {
    const rawValue = e.target.value;
    form.value.dpi = formatearDPI(rawValue);
    e.target.value = form.value.dpi;
}

// Stats Computados
const totalActivos = computed(() => {
    return afiliaciones.value.filter(a => a.estado === 'Activo').length
})

const totalLideres = computed(() => {
    return afiliaciones.value.filter(a => a.tipo_registro === 'Líder Comunitario').length
})

const totalBase = computed(() => {
    return afiliaciones.value.filter(a => a.tipo_registro === 'Afiliado Base').length
})

const totalInactivos = computed(() => {
    return afiliaciones.value.filter(a => a.estado === 'Inactivo').length
})

const nuevosEsteMes = computed(() => {
    const hoy = new Date()
    const anioActual = hoy.getFullYear()
    const mesActual = hoy.getMonth()
    return afiliaciones.value.filter(a => {
        if (!a.fecha_ingreso) return false
        const f = new Date(a.fecha_ingreso + 'T00:00:00')
        return f.getFullYear() === anioActual && f.getMonth() === mesActual
    }).length
})

// Filtros y búsquedas
const afiliacionesFiltradas = computed(() => {
    let lista = afiliaciones.value || []
    
    if (filtroActivo.value === 'Afiliados') {
        lista = lista.filter(a => a.tipo_registro === 'Afiliado Base')
    } else if (filtroActivo.value === 'Líderes') {
        lista = lista.filter(a => a.tipo_registro === 'Líder Comunitario')
    }
    
    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase()
        lista = lista.filter(a => 
            (a.nombre_completo && a.nombre_completo.toLowerCase().includes(q)) ||
            (a.dpi && a.dpi.toLowerCase().includes(q)) ||
            (a.municipio && a.municipio.toLowerCase().includes(q))
        )
    }
    
    return lista
})

function initials(name) {
    if (!name) return 'AF'
    return name.trim().split(/\s+/).map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function tipoClass(tipo) {
    if (tipo === 'Líder Comunitario') return 'bg-primary-container text-on-primary-container'
    return 'bg-surface-container-highest text-on-surface-variant'
}

function estadoClass(estado) {
    if (estado === 'Activo') return 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
    return 'bg-error/10 text-error border border-error/20'
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

function openModal(af = null) {
    formError.value = ''
    if (af) {
        editando.value = af
        form.value = { ...af }
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
    if (!form.value.nombre_completo.trim()) { formError.value = 'El nombre completo es obligatorio.'; return }
    if (!form.value.dpi.trim()) { formError.value = 'El DPI es obligatorio.'; return }
    if (form.value.dpi.trim().length !== 15) { 
        formError.value = 'El DPI debe tener el formato de 13 dígitos (XXXX-XXXXX-XXXX).'; 
        return 
    }
    if (!form.value.municipio.trim()) { formError.value = 'El municipio es obligatorio.'; return }
    if (!form.value.fecha_ingreso) { formError.value = 'La fecha de ingreso es obligatoria.'; return }

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
            await api.put(`/modulos/afiliaciones/${editando.value.id}`, form.value)
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'El registro de afiliación se ha actualizado correctamente.',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#fff',
                iconColor: '#34d399'
            })
        } else {
            await api.post('/modulos/afiliaciones', form.value)
            Swal.fire({
                icon: 'success',
                title: '¡Registrado!',
                text: 'El nuevo afiliado se ha registrado correctamente.',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#fff',
                iconColor: '#34d399'
            })
        }
        await cargarAfiliaciones()
        closeModal()
    } catch (err) {
        console.error(err)
        Swal.fire({
            icon: 'error',
            title: 'Error al guardar',
            text: 'Asegúrate de que el DPI no esté duplicado o registrado previamente.',
            background: '#1e293b',
            color: '#fff',
            confirmButtonColor: '#3b82f6'
        })
    }
}

async function eliminar(id) {
    const resultado = await Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará de forma permanente el registro de afiliación.',
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
            await api.delete(`/modulos/afiliaciones/${id}`)
            await cargarAfiliaciones()
            Swal.fire({
                icon: 'success',
                title: '¡Eliminado!',
                text: 'La afiliación ha sido eliminada correctamente.',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#fff',
                iconColor: '#ef4444'
            })
        } catch (err) {
            console.error('Error al eliminar afiliación:', err)
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

function exportarCSV() {
    const cabeceras = ['ID', 'Nombre Completo', 'DPI', 'Municipio', 'Tipo de Registro', 'Fecha de Ingreso', 'Estado']
    const filas = afiliaciones.value.map(a => [
        a.id,
        `"${a.nombre_completo.replace(/"/g, '""')}"`,
        `"${a.dpi.replace(/"/g, '""')}"`,
        `"${a.municipio.replace(/"/g, '""')}"`,
        `"${a.tipo_registro.replace(/"/g, '""')}"`,
        a.fecha_ingreso,
        a.estado
    ])
    
    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" 
        + cabeceras.join(",") + "\n" 
        + filas.map(e => e.join(",")).join("\n")
        
    const encodedUri = encodeURI(csvContent)
    const link = document.createElement("a")
    link.setAttribute("href", encodedUri)
    link.setAttribute("download", "afiliaciones_politicas_sysdipu.csv")
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
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
