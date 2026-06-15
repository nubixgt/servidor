<template>
    <div class="h-full flex flex-col bg-surface overflow-hidden">
        <!-- Header Section -->
        <header class="shrink-0 bg-surface-container-high/30 backdrop-blur-xl border-b border-white/5 py-6 px-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-96 h-96 bg-primary/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
                <div>
                    <h1 class="font-display-md text-display-md text-on-surface mb-1 flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary text-4xl drop-shadow-[0_0_15px_rgba(233,193,118,0.3)]">event_repeat</span>
                        Gastos Recurrentes
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Control y gestión de pagos periódicos.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow shadow-[0_8px_32px_rgba(0,0,0,0.2)] w-64 hidden sm:block">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                        <input v-model="searchQuery" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-sm py-2.5 pl-10 pr-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar concepto..." type="text"/>
                    </div>
                    <select v-model="filterStatus" class="bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-sm py-2.5 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                        <option value="TODOS">Todos</option>
                        <option value="ACTIVO">Activos</option>
                        <option value="INACTIVO">Inactivos</option>
                    </select>
                    <button @click="openModal" class="bg-primary text-on-primary font-label-lg px-6 py-2.5 rounded-xl hover:bg-primary-fixed hover:text-on-primary-fixed transition-all duration-300 shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] flex items-center gap-2 hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[20px]">add</span>
                        Nuevo Gasto
                    </button>
                </div>
            </div>
            <div class="mt-4 sm:hidden relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                <input v-model="searchQuery" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-sm py-2.5 pl-10 pr-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar concepto..." type="text"/>
            </div>
        </header>

        <!-- Main Content (Grid View) -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 md:p-8">
            <div v-if="filteredGastos.length === 0" class="h-full flex flex-col items-center justify-center opacity-70">
                <span class="material-symbols-outlined text-7xl text-on-surface-variant mb-4 opacity-50">receipt_long</span>
                <h3 class="font-title-lg text-title-lg text-on-surface-variant">No hay gastos registrados</h3>
                <p class="font-body-md text-on-surface-variant/70 text-center max-w-sm mt-2">Los gastos recurrentes que agregues aparecerán aquí como tarjetas.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
                <!-- Tarjetas Tipo Kanban/Grid -->
                <div v-for="gasto in filteredGastos" :key="gasto.id" 
                     class="bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden hover:border-primary/30 transition-all duration-300 hover:-translate-y-1 group relative flex flex-col h-full">
                    
                    <!-- Decorative Top Line -->
                    <div :class="['h-1.5 w-full absolute top-0 left-0', gasto.estado === 'ACTIVO' ? 'bg-primary shadow-[0_0_15px_rgba(233,193,118,0.5)]' : 'bg-on-surface-variant/30']"></div>

                    <!-- Card Header -->
                    <div class="p-5 pb-3 flex justify-between items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span :class="['px-2 py-0.5 rounded-md text-[10px] font-label-caps uppercase tracking-wider border', gasto.estado === 'ACTIVO' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-surface-container-highest text-on-surface-variant border-white/10']">
                                    {{ gasto.estado }}
                                </span>
                            </div>
                            <h3 class="font-title-lg text-xl text-on-surface truncate" :title="gasto.concepto">{{ gasto.concepto }}</h3>
                        </div>
                        <div class="flex flex-col items-center justify-center bg-surface-container-highest/50 rounded-xl p-2 min-w-[60px] border border-white/5">
                            <span class="font-label-caps text-[10px] text-on-surface-variant uppercase mb-0.5">Día</span>
                            <span class="font-display-sm text-2xl text-primary leading-none">{{ gasto.dia_pago }}</span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 pt-0 flex-1 flex flex-col">
                        <p class="font-body-sm text-on-surface-variant/80 text-sm line-clamp-3 mb-4 flex-1">
                            {{ gasto.descripcion || 'Sin descripción' }}
                        </p>
                        <div class="mt-auto">
                            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Monto Mensual</p>
                            <p class="font-headline-md text-3xl text-on-surface drop-shadow-[0_2px_4px_rgba(0,0,0,0.5)]">
                                Q{{ Number(gasto.monto).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="px-5 py-3 border-t border-white/5 bg-surface-container-highest/30 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="editGasto(gasto)" class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors" title="Editar">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>
                        <button @click="deleteGasto(gasto)" class="p-2 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors" title="Eliminar">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Formulario -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative w-full max-w-lg bg-surface-container border border-white/10 rounded-3xl shadow-[0_24px_48px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden animate-fade-in-up">
                <!-- Modal Header -->
                <div class="px-8 py-6 border-b border-white/10 flex justify-between items-center bg-surface-container-high/50">
                    <h2 class="font-headline-sm text-2xl text-primary flex items-center gap-3">
                        <span class="material-symbols-outlined text-3xl">{{ isEditing ? 'edit_square' : 'add_circle' }}</span>
                        {{ isEditing ? 'Editar Gasto' : 'Nuevo Gasto' }}
                    </h2>
                    <button @click="closeModal" class="text-on-surface-variant hover:text-error transition-colors rounded-full p-2 hover:bg-error/10">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-8 overflow-y-auto">
                    <form @submit.prevent="saveGasto" class="flex flex-col gap-6">
                        
                        <div class="flex flex-col gap-2">
                            <label class="font-label-lg text-on-surface-variant">Concepto <span class="text-error">*</span></label>
                            <input v-model="form.concepto" required type="text" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant/50" placeholder="Ej. Pago de Luz, Alquiler, Internet..." />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-label-lg text-on-surface-variant">Monto <span class="text-error">*</span></label>
                            <div class="relative">
                                <input v-model="form.monto" @input="formatInputCurrency" required type="text" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-primary font-headline-sm py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors" placeholder="Q0.00" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <label class="font-label-lg text-on-surface-variant">Día de Pago <span class="text-error">*</span></label>
                                <div class="relative">
                                    <input v-model="form.dia_pago" @input="handleDiaInput" required type="number" min="1" max="31" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-lg py-3 px-4 pl-12 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors" placeholder="1-31" />
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">calendar_today</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="font-label-lg text-on-surface-variant">Estado</label>
                                <div class="relative">
                                    <select v-model="form.estado" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                                        <option value="ACTIVO">ACTIVO</option>
                                        <option value="INACTIVO">INACTIVO</option>
                                    </select>
                                    <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-label-lg text-on-surface-variant">Descripción (Opcional)</label>
                            <textarea v-model="form.descripcion" rows="3" class="w-full bg-surface-container-highest/50 backdrop-blur-xl text-on-surface font-body-md py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant/50 custom-scrollbar" placeholder="Añade detalles adicionales..."></textarea>
                        </div>

                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-8 py-6 border-t border-white/10 bg-surface-container-high/50 flex justify-end gap-4">
                    <button @click="closeModal" class="px-6 py-2.5 font-label-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest rounded-xl transition-colors">Cancelar</button>
                    <button @click="saveGasto" class="bg-primary text-on-primary font-label-lg px-8 py-2.5 rounded-xl hover:bg-primary-fixed transition-colors shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] transform hover:-translate-y-0.5">
                        {{ isEditing ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { recurringExpenseService } from '../../services/recurringExpenseService';
import Swal from 'sweetalert2';

const gastos = ref([]);
const searchQuery = ref('');
const filterStatus = ref('TODOS');
const showModal = ref(false);
const isEditing = ref(false);

const form = ref({
    id: null,
    concepto: '',
    descripcion: '',
    monto: '',
    dia_pago: '',
    estado: 'ACTIVO'
});

const filteredGastos = computed(() => {
    let result = gastos.value;
    if (filterStatus.value !== 'TODOS') {
        result = result.filter(g => g.estado === filterStatus.value);
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(g => g.concepto.toLowerCase().includes(q));
    }
    return result;
});

const loadGastos = async () => {
    try {
        const res = await recurringExpenseService.getAllGastos();
        if (res.data.status === 'success') {
            gastos.value = res.data.data;
        }
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    loadGastos();
});

const formatInputCurrency = (e) => {
    let val = e.target.value.replace(/[^0-9]/g, '');
    if (!val) {
        form.value.monto = '';
        return;
    }
    let num = parseInt(val, 10) / 100;
    form.value.monto = 'Q' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const handleDiaInput = (e) => {
    let val = parseInt(e.target.value.replace(/[^0-9]/g, ''), 10);
    if (isNaN(val)) {
        form.value.dia_pago = '';
    } else if (val > 31) {
        form.value.dia_pago = 31;
    } else if (val < 1) {
        form.value.dia_pago = 1;
    } else {
        form.value.dia_pago = val;
    }
};

const parseFormatted = (val) => {
    if(!val) return null;
    return parseFloat(val.toString().replace(/[^0-9.-]+/g,""));
};

const openModal = () => {
    isEditing.value = false;
    form.value = {
        id: null,
        concepto: '',
        descripcion: '',
        monto: '',
        dia_pago: '',
        estado: 'ACTIVO'
    };
    showModal.value = true;
};

const editGasto = (gasto) => {
    isEditing.value = true;
    form.value = {
        ...gasto,
        monto: 'Q' + Number(gasto.monto).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const saveGasto = async () => {
    if (!form.value.concepto || !form.value.monto || !form.value.dia_pago) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor completa el concepto, monto y día de pago.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: { popup: 'border border-white/10 rounded-2xl' }
        });
        return;
    }

    try {
        const payload = {
            ...form.value,
            monto: parseFormatted(form.value.monto)
        };

        if (isEditing.value) {
            await recurringExpenseService.updateGasto(form.value.id, payload);
            Swal.fire({
                icon: 'success',
                title: 'Actualizado',
                text: 'El gasto recurrente se actualizó correctamente.',
                background: '#131313',
                color: '#ffffff',
                confirmButtonColor: '#e9c176',
                customClass: { popup: 'border border-white/10 rounded-2xl' }
            });
        } else {
            await recurringExpenseService.createGasto(payload);
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: 'El gasto recurrente se creó correctamente.',
                background: '#131313',
                color: '#ffffff',
                confirmButtonColor: '#e9c176',
                customClass: { popup: 'border border-white/10 rounded-2xl' }
            });
        }
        closeModal();
        await loadGastos();
    } catch (e) {
        console.error(e);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.response?.data?.message || 'Ocurrió un problema al guardar el gasto.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: { popup: 'border border-white/10 rounded-2xl' }
        });
    }
};

const deleteGasto = (gasto) => {
    Swal.fire({
        title: '¿Eliminar Gasto?',
        text: `¿Estás seguro de eliminar "${gasto.concepto}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffb4ab',
        cancelButtonColor: '#353535',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: '#131313',
        color: '#ffffff',
        customClass: {
            popup: 'border border-white/10 rounded-2xl',
            title: 'text-error',
            confirmButton: 'text-black'
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await recurringExpenseService.deleteGasto(gasto.id);
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: 'El gasto ha sido eliminado.',
                    background: '#131313',
                    color: '#ffffff',
                    confirmButtonColor: '#e9c176',
                    customClass: { popup: 'border border-white/10 rounded-2xl' }
                });
                await loadGastos();
            } catch (e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo eliminar el gasto.',
                    background: '#131313',
                    color: '#ffffff',
                    confirmButtonColor: '#e9c176',
                    customClass: { popup: 'border border-white/10 rounded-2xl' }
                });
            }
        }
    });
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #353535; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e9c176; }

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
</style>
