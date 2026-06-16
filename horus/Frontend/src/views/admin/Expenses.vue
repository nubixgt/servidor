<template>
    <div class="h-full flex flex-col bg-surface overflow-hidden">
        <!-- Header Section -->
        <header class="shrink-0 bg-surface-container-high/30 backdrop-blur-xl border-b border-white/5 py-6 px-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-96 h-96 bg-primary/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
                <div>
                    <h1 class="font-display-md text-display-md text-on-surface mb-1 flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary text-4xl drop-shadow-[0_0_15px_rgba(233,193,118,0.3)]">money_off</span>
                        Egresos
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">Gestión y control de egresos generales.</p>
                </div>
                <div class="flex items-center gap-4">
                    <button @click="openModal" class="bg-primary text-on-primary font-label-lg px-6 py-2.5 rounded-xl hover:bg-primary-fixed hover:text-on-primary-fixed transition-all duration-300 shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] flex items-center gap-2 hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[20px]">add</span>
                        Nuevo Egreso
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 md:p-8">
            <div v-if="egresos.length === 0" class="h-full flex flex-col items-center justify-center opacity-70">
                <span class="material-symbols-outlined text-7xl text-on-surface-variant mb-4 opacity-50">money_off</span>
                <h3 class="font-title-lg text-title-lg text-on-surface-variant">No hay egresos registrados</h3>
                <p class="font-body-md text-on-surface-variant/70 text-center max-w-sm mt-2">Los egresos que agregues aparecerán aquí.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div v-for="item in egresos" :key="item.egreso.id" 
                     class="bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden hover:border-primary/30 transition-all duration-300 hover:-translate-y-1 group relative flex flex-col h-full">
                    
                    <div class="h-1.5 w-full absolute top-0 left-0 bg-primary shadow-[0_0_15px_rgba(233,193,118,0.5)]"></div>

                    <div class="p-5 pb-3 flex flex-col gap-2">
                        <div class="flex justify-between items-start">
                            <h3 class="font-title-lg text-xl text-primary font-bold">{{ item.egreso.tipo_egreso }}</h3>
                            <span class="text-on-surface-variant text-sm font-label-caps">{{ item.egreso.fecha }}</span>
                        </div>
                        <div v-if="item.recurrente_nombre" class="bg-surface-container text-on-surface-variant text-xs py-1 px-2 rounded-lg self-start">
                            Recurrente: {{ item.recurrente_nombre }}
                        </div>
                    </div>

                    <div class="p-5 pt-0 flex-1 flex flex-col gap-2">
                        <p class="text-on-surface-variant text-sm"><strong class="text-on-surface">Pagador:</strong> {{ item.egreso.pagador || '-' }}</p>
                        <p class="text-on-surface-variant text-sm"><strong class="text-on-surface">Ref:</strong> {{ item.egreso.referencia || '-' }}</p>
                        
                        <div class="mt-4 border-t border-white/10 pt-4">
                            <p class="font-label-caps text-on-surface-variant mb-2">Registros ({{ item.egreso.registros?.length || 0 }})</p>
                            <div class="max-h-24 overflow-y-auto custom-scrollbar flex flex-col gap-1">
                                <div v-for="reg in item.egreso.registros" :key="reg.id" class="flex justify-between text-sm">
                                    <span class="text-on-surface-variant truncate mr-2">{{ reg.descripcion }}</span>
                                    <span class="text-primary font-bold">Q{{ Number(reg.monto).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-white/5 bg-surface-container-highest/30 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button v-if="item.egreso.comprobante" @click="viewFile(item.egreso.comprobante)" class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors" title="Ver comprobante">
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                        </button>
                        <button @click="editEgreso(item)" class="p-2 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors" title="Editar">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>
                        <button @click="deleteEgreso(item.egreso)" class="p-2 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors" title="Eliminar">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Formulario -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative w-full max-w-4xl max-h-[90vh] bg-surface-container border border-white/10 rounded-3xl shadow-[0_24px_48px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden animate-fade-in-up">
                <!-- Modal Header -->
                <div class="px-8 py-6 border-b border-white/10 flex justify-between items-center bg-surface-container-high/50">
                    <h2 class="font-headline-sm text-2xl text-primary flex items-center gap-3">
                        <span class="material-symbols-outlined text-3xl">{{ isEditing ? 'edit_square' : 'add_circle' }}</span>
                        {{ isEditing ? 'Editar Egreso' : 'Nuevo Egreso' }}
                    </h2>
                    <button @click="closeModal" class="text-on-surface-variant hover:text-error transition-colors rounded-full p-2 hover:bg-error/10">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-8 overflow-y-auto custom-scrollbar">
                    <form @submit.prevent="saveEgreso" class="flex flex-col gap-8">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Col 1 -->
                            <div class="flex flex-col gap-6">
                                <div class="flex flex-col gap-2">
                                    <label class="font-label-lg text-on-surface-variant">Tipo de Egreso <span class="text-error">*</span></label>
                                    <input v-model="form.tipo_egreso" required type="text" class="w-full bg-surface-container-highest/50 text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors" placeholder="Ej. Pago a proveedor" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="font-label-lg text-on-surface-variant">Gasto Recurrente (Opcional)</label>
                                    <select v-model="form.recurrente_id" class="w-full bg-surface-container-highest/50 text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                                        <option :value="null">-- Ninguno --</option>
                                        <option v-for="gasto in recurrentes" :key="gasto.id" :value="gasto.id">{{ gasto.concepto }}</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="font-label-lg text-on-surface-variant">Fecha <span class="text-error">*</span></label>
                                    <input v-model="form.fecha" required type="date" class="w-full bg-surface-container-highest/50 text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors" />
                                </div>
                            </div>
                            
                            <!-- Col 2 -->
                            <div class="flex flex-col gap-6">
                                <div class="flex flex-col gap-2">
                                    <label class="font-label-lg text-on-surface-variant">Cheque Depósito / Ref</label>
                                    <input v-model="form.referencia" type="text" class="w-full bg-surface-container-highest/50 text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors" placeholder="No. de Referencia" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="font-label-lg text-on-surface-variant">Pagador (Cliente)</label>
                                    <input v-model="form.pagador" type="text" class="w-full bg-surface-container-highest/50 text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors" placeholder="Nombre del pagador" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="font-label-lg text-on-surface-variant">Comprobante Digital (Imagen/PDF)</label>
                                    <input type="file" @change="handleFileUpload" class="w-full bg-surface-container-highest/50 text-on-surface font-body-lg py-2.5 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary/20 file:text-primary hover:file:bg-primary/30" />
                                    <p v-if="form.comprobanteUrl" class="text-xs text-primary mt-1">Archivo actual cargado.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-label-lg text-on-surface-variant">Descripción o Concepto general</label>
                            <textarea v-model="form.descripcion_concepto" rows="2" class="w-full bg-surface-container-highest/50 text-on-surface font-body-md py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors custom-scrollbar" placeholder="Añade detalles adicionales..."></textarea>
                        </div>

                        <!-- Registros Dinámicos -->
                        <div class="border border-white/10 rounded-2xl p-6 bg-surface-container-highest/20">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-headline-sm text-lg text-on-surface">Registros (Detalle)</h3>
                                <button type="button" @click="addRegistro" class="bg-primary/10 text-primary px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-primary/20 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">add</span> Añadir Registro
                                </button>
                            </div>
                            
                            <div v-if="form.registros.length === 0" class="text-on-surface-variant text-center py-4 opacity-70">
                                Sin registros añadidos. Haz clic en "Añadir Registro".
                            </div>

                            <div class="flex flex-col gap-4">
                                <div v-for="(reg, index) in form.registros" :key="index" class="flex flex-col md:flex-row gap-4 items-start md:items-center bg-surface-container p-4 rounded-xl border border-white/5 relative">
                                    <div class="flex-1 w-full">
                                        <label class="text-xs text-on-surface-variant mb-1 block">Descripción</label>
                                        <input v-model="reg.descripcion" required type="text" class="w-full bg-surface-container-highest/50 text-on-surface font-body-sm py-2 px-3 rounded-lg border border-white/10 focus:border-primary" placeholder="Concepto del registro" />
                                    </div>
                                    <div class="w-full md:w-48">
                                        <label class="text-xs text-on-surface-variant mb-1 block">Monto</label>
                                        <input v-model="reg.montoDisplay" @input="(e) => formatRegistroMonto(e, index)" required type="text" class="w-full bg-surface-container-highest/50 text-primary font-bold py-2 px-3 rounded-lg border border-white/10 focus:border-primary" placeholder="Q0.00" />
                                    </div>
                                    <button type="button" @click="removeRegistro(index)" class="mt-5 text-error hover:bg-error/10 p-2 rounded-lg transition-colors md:self-center" title="Eliminar">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div v-if="form.registros.length > 0" class="mt-6 flex justify-end gap-4 border-t border-white/10 pt-4">
                                <span class="font-bold text-on-surface text-lg">Total:</span>
                                <span class="font-headline-md text-primary text-2xl font-bold">Q{{ totalRegistros.toLocaleString('en-US', {minimumFractionDigits: 2}) }}</span>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-8 py-6 border-t border-white/10 bg-surface-container-high/50 flex justify-end gap-4">
                    <button @click="closeModal" class="px-6 py-2.5 font-label-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest rounded-xl transition-colors">Cancelar</button>
                    <button @click="saveEgreso" class="bg-primary text-on-primary font-label-lg px-8 py-2.5 rounded-xl hover:bg-primary-fixed transition-colors shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] transform hover:-translate-y-0.5">
                        {{ isEditing ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { expenseService } from '../../services/expenseService';
import { recurringExpenseService } from '../../services/recurringExpenseService';
import Swal from 'sweetalert2';

const egresos = ref([]);
const recurrentes = ref([]);
const showModal = ref(false);
const isEditing = ref(false);

const form = ref({
    id: null,
    recurrente_id: null,
    tipo_egreso: '',
    fecha: '',
    referencia: '',
    pagador: '',
    descripcion_concepto: '',
    comprobante: null,
    comprobanteUrl: null,
    registros: []
});

const loadData = async () => {
    try {
        const [resEgresos, resRecurrentes] = await Promise.all([
            expenseService.getAllEgresos(),
            recurringExpenseService.getAllGastos()
        ]);
        if (resEgresos.data.success) {
            egresos.value = resEgresos.data.data;
        }
        if (resRecurrentes.data.status === 'success') {
            recurrentes.value = resRecurrentes.data.data;
        }
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    loadData();
});

const totalRegistros = computed(() => {
    return form.value.registros.reduce((acc, reg) => {
        let val = parseFloat(reg.monto) || 0;
        return acc + val;
    }, 0);
});

const openModal = () => {
    isEditing.value = false;
    form.value = {
        id: null,
        recurrente_id: null,
        tipo_egreso: '',
        fecha: new Date().toISOString().split('T')[0],
        referencia: '',
        pagador: '',
        descripcion_concepto: '',
        comprobante: null,
        comprobanteUrl: null,
        registros: []
    };
    showModal.value = true;
};

const editEgreso = (item) => {
    isEditing.value = true;
    const egr = item.egreso;
    
    const mappedRegistros = (egr.registros || []).map(r => ({
        descripcion: r.descripcion,
        monto: r.monto,
        montoDisplay: 'Q' + Number(r.monto).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
    }));

    form.value = {
        id: egr.id,
        recurrente_id: egr.recurrente_id,
        tipo_egreso: egr.tipo_egreso,
        fecha: egr.fecha,
        referencia: egr.referencia,
        pagador: egr.pagador,
        descripcion_concepto: egr.descripcion_concepto,
        comprobante: null,
        comprobanteUrl: egr.comprobante,
        registros: mappedRegistros
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.value.comprobante = file;
    }
};

const formatRegistroMonto = (e, index) => {
    let val = e.target.value.replace(/[^0-9]/g, '');
    if (!val) {
        form.value.registros[index].monto = '';
        form.value.registros[index].montoDisplay = '';
        return;
    }
    let num = parseInt(val, 10) / 100;
    form.value.registros[index].monto = num;
    form.value.registros[index].montoDisplay = 'Q' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const addRegistro = () => {
    form.value.registros.push({
        descripcion: '',
        monto: '',
        montoDisplay: ''
    });
};

const removeRegistro = (index) => {
    form.value.registros.splice(index, 1);
};

const saveEgreso = async () => {
    if (!form.value.tipo_egreso || !form.value.fecha) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor completa el tipo de egreso y fecha.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176'
        });
        return;
    }

    try {
        const formData = new FormData();
        if (form.value.recurrente_id) formData.append('recurrente_id', form.value.recurrente_id);
        formData.append('tipo_egreso', form.value.tipo_egreso);
        formData.append('fecha', form.value.fecha);
        formData.append('referencia', form.value.referencia);
        formData.append('pagador', form.value.pagador);
        formData.append('descripcion_concepto', form.value.descripcion_concepto);
        
        if (form.value.comprobante) {
            formData.append('comprobante', form.value.comprobante);
        }

        const validRegistros = form.value.registros.filter(r => r.descripcion && r.monto);
        formData.append('registros', JSON.stringify(validRegistros));

        if (isEditing.value) {
            await expenseService.updateEgreso(form.value.id, formData);
            Swal.fire({ icon: 'success', title: 'Actualizado', background: '#131313', color: '#ffffff', confirmButtonColor: '#e9c176' });
        } else {
            await expenseService.createEgreso(formData);
            Swal.fire({ icon: 'success', title: 'Guardado', background: '#131313', color: '#ffffff', confirmButtonColor: '#e9c176' });
        }
        closeModal();
        await loadData();
    } catch (e) {
        console.error(e);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un problema.', background: '#131313', color: '#ffffff', confirmButtonColor: '#e9c176' });
    }
};

const deleteEgreso = (egreso) => {
    Swal.fire({
        title: '¿Eliminar Egreso?',
        text: `¿Estás seguro de eliminar el egreso "${egreso.tipo_egreso}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffb4ab',
        cancelButtonColor: '#353535',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: '#131313',
        color: '#ffffff',
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await expenseService.deleteEgreso(egreso.id);
                Swal.fire({ icon: 'success', title: 'Eliminado', background: '#131313', color: '#ffffff', confirmButtonColor: '#e9c176' });
                await loadData();
            } catch (e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error', background: '#131313', color: '#ffffff', confirmButtonColor: '#e9c176' });
            }
        }
    });
};

const viewFile = (path) => {
    const url = import.meta.env.VITE_API_URL ? import.meta.env.VITE_API_URL.replace('/api/v1', '') : 'http://localhost/Antigravity/servidor/horus/Backend';
    window.open(`${url}/${path}`, '_blank');
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #353535; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e9c176; }

@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-fade-in-up { animation: fade-in-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
