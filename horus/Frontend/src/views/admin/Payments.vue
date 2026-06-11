<template>
  <div class="h-full flex flex-col bg-surface overflow-hidden">
    <!-- Header Section -->
    <header class="shrink-0 bg-surface-container-high/30 backdrop-blur-xl border-b border-white/5 py-4 px-6 md:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="font-display-sm text-display-sm text-on-surface mb-1 drop-shadow-[0_0_15px_rgba(233,193,118,0.3)]">Pagos e Ingresos</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Control de cuotas, desembolsos y transferencias.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openNewModal" class="bg-primary text-on-primary font-label-lg px-6 py-2.5 rounded-full hover:bg-primary-fixed hover:text-on-primary-fixed transition-all duration-300 shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] flex items-center gap-2 hover:-translate-y-0.5">
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                    Registrar Pago
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content (Split View) -->
    <div class="flex-1 overflow-hidden flex flex-col lg:flex-row gap-6 p-6 md:px-8">
        
        <!-- Left Panel: Payments List -->
        <div class="w-full lg:w-[400px] flex flex-col gap-4 shrink-0 h-[40vh] lg:h-full">
            <!-- Search -->
            <div class="relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="searchQuery" class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm text-body-sm py-3 pl-10 pr-4 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar por Cliente o Referencia..." type="text"/>
            </div>

            <!-- List -->
            <div class="flex-1 bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden flex flex-col min-h-0">
                <div class="overflow-y-auto custom-scrollbar flex-1 p-2 space-y-2">
                    <div v-if="filteredPayments.length === 0" class="text-center text-on-surface-variant p-4">No hay pagos registrados</div>
                    
                    <div v-for="pago in filteredPayments" :key="pago.id" 
                         @click="selectedPayment = pago"
                         :class="['p-4 rounded-2xl backdrop-blur-xl border cursor-pointer relative overflow-hidden group transition-all', 
                                  selectedPayment?.id === pago.id ? 'bg-surface-container-high/40 border-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]' : 'bg-transparent border-transparent hover:bg-surface-container-high/30 hover:border-white/5']">
                        
                        <div v-if="selectedPayment?.id === pago.id" class="absolute left-0 top-0 bottom-0 w-1 bg-primary shadow-[0_0_10px_rgba(233,193,118,0.5)]"></div>

                        <div class="flex justify-between items-start mb-2 pl-2">
                            <div>
                                <h3 class="font-title-sm text-title-sm text-on-surface group-hover:text-primary transition-colors">{{ pago.cliente_nombre }}</h3>
                                <p class="font-body-sm text-[11px] text-on-surface-variant mt-0.5"><span class="material-symbols-outlined text-[12px] align-middle mr-1">event</span> {{ pago.fecha }}</p>
                            </div>
                            <div class="text-right">
                                <span class="font-label-caps text-xs text-primary font-bold">Q{{ Number(pago.monto_pagado).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Payment Details -->
        <div class="flex-1 flex flex-col overflow-hidden h-[50vh] lg:h-full">
            <div v-if="selectedPayment" class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-6">
                <!-- Action Bar -->
                <div class="flex justify-between items-center bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-tertiary-container shadow-[0_0_8px_rgba(143,165,214,0.6)]"></span>
                        <span class="font-label-caps text-label-caps text-on-surface tracking-widest uppercase">Detalle de Pago</span>
                    </div>
                </div>

                <!-- Details Card -->
                <div class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <div class="flex items-center gap-6 mb-8 border-b border-white/10 pb-6">
                        <div class="w-16 h-16 rounded-full bg-surface-container-highest overflow-hidden border border-primary/50 shadow-[0_0_15px_rgba(233,193,118,0.2)] flex items-center justify-center">
                            <span class="material-symbols-outlined text-3xl text-primary">payments</span>
                        </div>
                        <div>
                            <h2 class="font-display-md text-2xl text-on-surface mb-1">{{ selectedPayment.cliente_nombre }}</h2>
                            <p class="font-body-md text-on-surface-variant flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">receipt_long</span> 
                                Ref: {{ selectedPayment.referencia || 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <div>
                            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Fecha</p>
                            <p class="font-body-md text-on-surface">{{ selectedPayment.fecha }}</p>
                        </div>
                        
                        <div>
                            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Banco</p>
                            <p class="font-body-md text-on-surface">{{ selectedPayment.banco || 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Monto Pagado</p>
                            <p class="font-body-lg text-primary font-semibold">Q{{ Number(selectedPayment.monto_pagado).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-if="selectedPayment.interes > 0" class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] border-l-2 border-l-secondary">
                        <h3 class="font-title-md text-title-md text-on-surface mb-4">Interés</h3>
                        <p class="font-body-lg text-secondary font-semibold mb-2">Q{{ Number(selectedPayment.interes).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</p>
                        <p class="font-body-sm text-on-surface-variant mb-4">Fecha: {{ selectedPayment.fecha_interes }}</p>
                        <a v-if="selectedPayment.comprobante_interes" :href="`/horus/Backend/${selectedPayment.comprobante_interes}`" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">visibility</span> Ver Comprobante
                        </a>
                    </div>

                    <div v-if="selectedPayment.capital > 0" class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] border-l-2 border-l-tertiary">
                        <h3 class="font-title-md text-title-md text-on-surface mb-4">Capital</h3>
                        <p class="font-body-lg text-tertiary font-semibold mb-2">Q{{ Number(selectedPayment.capital).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</p>
                        <p class="font-body-sm text-on-surface-variant mb-4">Fecha: {{ selectedPayment.fecha_capital }}</p>
                        <a v-if="selectedPayment.comprobante_capital" :href="`/horus/Backend/${selectedPayment.comprobante_capital}`" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">visibility</span> Ver Comprobante
                        </a>
                    </div>
                </div>

                <!-- General Photo -->
                <div v-if="selectedPayment.foto" class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <h3 class="font-title-md text-title-md text-on-surface flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-primary text-[20px]">image</span>
                        Foto Adjunta
                    </h3>
                    <div class="w-full max-w-md aspect-video rounded-xl border border-white/10 overflow-hidden">
                        <a :href="`/horus/Backend/${selectedPayment.foto}`" target="_blank">
                            <img :src="`/horus/Backend/${selectedPayment.foto}`" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                        </a>
                    </div>
                </div>

            </div>

            <!-- Empty State -->
            <div v-else class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-2xl bg-surface-container-lowest/30">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4 opacity-50">receipt_long</span>
                <h3 class="font-title-lg text-title-lg text-on-surface-variant">Selecciona un Pago</h3>
                <p class="font-body-md text-on-surface-variant/70 text-center max-w-sm mt-2">Haz clic en un registro de la lista para ver sus detalles completos y comprobantes.</p>
            </div>
        </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        
        <div class="relative w-full max-w-3xl max-h-[90vh] bg-surface-container border border-white/10 rounded-3xl shadow-[0_24px_48px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-surface-container-high">
                <h2 class="font-title-lg text-title-lg text-on-surface">Registrar Nuevo Pago</h2>
                <button @click="closeModal" class="text-on-surface-variant hover:text-error transition-colors rounded-full p-1 hover:bg-error/10">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                <form @submit.prevent="savePayment" class="flex flex-col gap-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Fecha -->
                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Fecha <span class="text-error">*</span></label>
                            <input v-model="form.fecha" required type="date" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                        </div>

                        <!-- Buscar Cliente -->
                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Buscar Cliente <span class="text-error">*</span></label>
                            <div class="relative">
                                <select v-model="form.cliente_id" required class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                                    <option value="" disabled>Seleccione un cliente</option>
                                    <option v-for="cliente in clients" :key="cliente.id" :value="cliente.id" class="bg-surface-container text-on-surface">
                                        {{ cliente.cliente }}
                                    </option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">arrow_drop_down</span>
                            </div>
                        </div>

                        <!-- Banco -->
                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Banco</label>
                            <input v-model="form.banco" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                        </div>

                        <!-- Referencia -->
                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">No. de Boleta o Referencia</label>
                            <input v-model="form.referencia" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                        </div>

                        <!-- Monto Pagado -->
                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Monto Pagado <span class="text-error">*</span></label>
                            <input v-model="form.monto_pagado" @input="formatInputCurrency('monto_pagado')" required type="text" placeholder="Q0.00" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                        </div>

                        <!-- Adjuntar Foto -->
                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Adjuntar Foto (1 foto)</label>
                            <input type="file" accept=".png,.jpg,.jpeg" @change="(e) => handleImageUpload(e, 'foto')" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors file:mr-4 file:py-1 file:px-4 file:rounded-xl file:border-0 file:bg-primary/20 file:text-primary file:font-semibold hover:file:bg-primary/30 file:cursor-pointer cursor-pointer text-sm" />
                            <div v-if="previewFotos.foto" class="mt-2 text-primary text-xs flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span> Archivo adjunto
                            </div>
                        </div>
                    </div>

                    <hr class="border-white/10 my-2" />

                    <!-- Interes Section -->
                    <div class="bg-surface-container-highest/30 rounded-xl p-4 border border-white/5">
                        <div class="flex flex-col gap-1 mb-4">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Interés</label>
                            <input v-model="form.interes" @input="formatInputCurrency('interes')" type="text" placeholder="Q0.00" class="bg-surface-container-high/50 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors max-w-[50%]" />
                        </div>

                        <div v-if="parseFormatted(form.interes) > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Comprobante (Interés)</label>
                                <input type="file" accept=".png,.jpg,.jpeg,.pdf" @change="(e) => handleImageUpload(e, 'comprobante_interes')" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors file:mr-4 file:py-1 file:px-4 file:rounded-xl file:border-0 file:bg-primary/20 file:text-primary file:font-semibold hover:file:bg-primary/30 file:cursor-pointer cursor-pointer text-sm" />
                                <div v-if="previewFotos.comprobante_interes" class="mt-2 text-primary text-xs flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span> Archivo adjunto
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Fecha (Interés)</label>
                                <input v-model="form.fecha_interes" type="date" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>
                        </div>
                    </div>

                    <!-- Capital Section -->
                    <div class="bg-surface-container-highest/30 rounded-xl p-4 border border-white/5">
                        <div class="flex flex-col gap-1 mb-4">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Capital</label>
                            <input v-model="form.capital" @input="formatInputCurrency('capital')" type="text" placeholder="Q0.00" class="bg-surface-container-high/50 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors max-w-[50%]" />
                        </div>

                        <div v-if="parseFormatted(form.capital) > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Comprobante (Capital)</label>
                                <input type="file" accept=".png,.jpg,.jpeg,.pdf" @change="(e) => handleImageUpload(e, 'comprobante_capital')" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors file:mr-4 file:py-1 file:px-4 file:rounded-xl file:border-0 file:bg-primary/20 file:text-primary file:font-semibold hover:file:bg-primary/30 file:cursor-pointer cursor-pointer text-sm" />
                                <div v-if="previewFotos.comprobante_capital" class="mt-2 text-primary text-xs flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span> Archivo adjunto
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Fecha (Capital)</label>
                                <input v-model="form.fecha_capital" type="date" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            
            <div class="px-6 py-4 border-t border-white/10 bg-surface-container-high flex justify-end gap-3">
                <button @click="closeModal" class="px-4 py-2 font-body-sm text-on-surface-variant hover:text-on-surface transition-colors">Cancelar</button>
                <button @click="savePayment" class="bg-primary text-on-primary font-label-lg px-6 py-2 rounded-full hover:bg-primary-fixed transition-colors shadow-[0_0_15px_rgba(233,193,118,0.3)]">
                    Guardar
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { clientService } from '../../services/clientService';
import { paymentService } from '../../services/paymentService';
import Swal from 'sweetalert2';

const clients = ref([]);
const payments = ref([]); // Placeholder for actual backend fetching
const searchQuery = ref('');
const showModal = ref(false);
const selectedPayment = ref(null);

const filteredPayments = computed(() => {
    if (!searchQuery.value) return payments.value;
    const query = searchQuery.value.toLowerCase();
    return payments.value.filter(p => 
        (p.cliente_nombre && p.cliente_nombre.toLowerCase().includes(query)) || 
        (p.referencia && p.referencia.toLowerCase().includes(query))
    );
});

const form = ref({
    fecha: '',
    cliente_id: '',
    banco: '',
    referencia: '',
    monto_pagado: '',
    interes: '',
    fecha_interes: '',
    capital: '',
    fecha_capital: ''
});

// To store File objects
const filesToUpload = ref({
    foto: null,
    comprobante_interes: null,
    comprobante_capital: null
});

// To track if file is uploaded
const previewFotos = ref({
    foto: false,
    comprobante_interes: false,
    comprobante_capital: false
});

const loadClients = async () => {
    try {
        const response = await clientService.getAllClients();
        clients.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading clients', e);
    }
};

const loadPayments = async () => {
    try {
        const response = await paymentService.getAllPayments();
        payments.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading payments', e);
    }
};

onMounted(() => {
    loadClients();
    loadPayments();
});

const handleImageUpload = (event, type) => {
    const file = event.target.files[0];
    if (file) {
        filesToUpload.value[type] = file;
        previewFotos.value[type] = true;
    } else {
        filesToUpload.value[type] = null;
        previewFotos.value[type] = false;
    }
};

const parseFormatted = (val) => {
    if(!val) return 0;
    return parseFloat(val.toString().replace(/[^0-9.-]+/g,"")) || 0;
};

const formatInputCurrency = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    let num = parseInt(val, 10) / 100;
    form.value[field] = 'Q' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const openNewModal = () => {
    form.value = {
        fecha: '',
        cliente_id: '',
        banco: '',
        referencia: '',
        monto_pagado: '',
        interes: '',
        fecha_interes: '',
        capital: '',
        fecha_capital: ''
    };
    filesToUpload.value = { foto: null, comprobante_interes: null, comprobante_capital: null };
    previewFotos.value = { foto: false, comprobante_interes: false, comprobante_capital: false };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const savePayment = async () => {
    try {
        const payload = new FormData();
        payload.append('fecha', form.value.fecha);
        payload.append('cliente_id', form.value.cliente_id);
        payload.append('banco', form.value.banco || '');
        payload.append('referencia', form.value.referencia || '');
        payload.append('monto_pagado', parseFormatted(form.value.monto_pagado));
        
        if (filesToUpload.value.foto) {
            payload.append('foto', filesToUpload.value.foto);
        }

        const interesValue = parseFormatted(form.value.interes);
        payload.append('interes', interesValue);
        if (interesValue > 0) {
            payload.append('fecha_interes', form.value.fecha_interes || '');
            if (filesToUpload.value.comprobante_interes) {
                payload.append('comprobante_interes', filesToUpload.value.comprobante_interes);
            }
        }

        const capitalValue = parseFormatted(form.value.capital);
        payload.append('capital', capitalValue);
        if (capitalValue > 0) {
            payload.append('fecha_capital', form.value.fecha_capital || '');
            if (filesToUpload.value.comprobante_capital) {
                payload.append('comprobante_capital', filesToUpload.value.comprobante_capital);
            }
        }

        // Send payload to backend
        await paymentService.createPayment(payload);

        Swal.fire({
            icon: 'success',
            title: '¡Guardado!',
            text: 'El pago se procesó correctamente.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(233,193,118,0.2)]',
            }
        });

        closeModal();
        await loadPayments();
    } catch (e) {
        console.error(e);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema al preparar los datos.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl',
            }
        });
    }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #353535; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e9c176; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
