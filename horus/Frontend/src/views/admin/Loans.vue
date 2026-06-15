<template>
  <div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2.5 py-1 rounded bg-secondary-container/20 text-tertiary-container font-label-caps text-[10px] tracking-wider uppercase border border-tertiary-container/30">ACTIVO</span>
                <span v-if="form.id" class="text-on-surface-variant font-body-sm text-xs">Ref: LN-{{ form.id }}</span>
            </div>
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-primary tracking-wide font-medium">Gestión de Préstamo</h2>
        </div>
        <div class="flex gap-3">
            <button @click="saveLoan" class="bg-transparent border border-primary text-primary font-body-sm py-2 px-5 rounded-xl hover:bg-primary/10 transition-colors shadow-[0_0_10px_rgba(233,193,118,0.15)] backdrop-blur-sm flex items-center gap-2 font-medium">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">save</span> Guardar
            </button>
        </div>
    </div>

    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Configuration -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Config Card -->
            <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl flex flex-col gap-5 relative overflow-hidden group border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary-container via-primary to-transparent opacity-70"></div>
                <h3 class="font-title-md text-title-md text-on-surface border-b border-outline-variant pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">tune</span> Configuración
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">CLIENTE</label>
                        <div class="relative">
                            <select v-model="form.cliente_id" @change="onClientChange" class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary appearance-none font-body-lg text-body-lg text-sm">
                                <option value="">Seleccione un cliente</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.cliente }}</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">MONTO PRINCIPAL</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">Q</span>
                                <input v-model="form.monto_principal" @input="formatCurrency('monto_principal'); calculateAmortization()" class="w-full bg-surface-container border border-outline-variant rounded py-2.5 pl-8 pr-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm text-right" type="text" placeholder="0.00"/>
                            </div>
                        </div>
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">TASA (%)</label>
                            <div class="relative">
                                <input v-model="form.tasa" @input="formatPercent('tasa'); calculateAmortization()" class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm text-right pr-8" type="text" placeholder="0"/>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">TIPO INTERÉS</label>
                            <select v-model="form.tipo_interes" @change="calculateAmortization" class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm">
                                <option value="Decreciente">Decreciente</option>
                                <option value="Fijo">Fijo</option>
                                <option value="Compuesto">Compuesto</option>
                                <option value="Simple">Simple</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">PLAZO (Meses)</label>
                            <input v-model="form.plazo" @input="calculateAmortization" type="number" min="1" class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm" placeholder="Meses">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block font-label-caps text-label-caps text-on-surface-variant mb-1.5">FECHA DESEMBOLSO</label>
                        <div class="relative">
                            <input v-model="form.fecha_desembolso" @change="calculateAmortization" class="w-full bg-surface-container border border-outline-variant rounded p-2.5 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary font-body-lg text-body-lg text-sm [color-scheme:dark]" type="date"/>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Summary Mini-card -->
            <div v-if="selectedClient" class="bg-surface-container-high/30 backdrop-blur-xl p-5 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] flex items-center gap-4">
                <div class="w-12 h-12 rounded bg-surface-variant flex items-center justify-center border border-outline shrink-0">
                    <span class="material-symbols-outlined text-on-surface-variant" style="font-variation-settings: 'FILL' 1;">corporate_fare</span>
                </div>
                <div>
                    <h4 class="font-title-md text-title-md text-sm text-on-surface">{{ selectedClient.cliente }}</h4>
                    <p class="font-body-sm text-body-sm text-on-surface-variant text-xs mt-1">DPI: {{ selectedClient.dpi || 'N/A' }}</p>
                    <a class="text-primary font-label-caps text-label-caps text-[10px] mt-2 inline-block hover:underline" href="#">VER PERFIL COMPLETO</a>
                </div>
            </div>
        </div>

        <!-- Right Column: Amortization & Summaries -->
        <div class="lg:col-span-8 flex flex-col gap-6">
            <!-- Summary Cards Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-primary/30 transition-all shadow-[0_8px_32px_rgba(233,193,118,0.05)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-transparent to-transparent opacity-60"></div>
                    <div class="absolute right-0 top-0 w-16 h-16 bg-primary/5 rounded-bl-full pointer-events-none group-hover:bg-primary/10 transition-colors"></div>
                    <p class="font-body-sm text-on-surface-variant mb-1 flex items-center gap-1.5 uppercase tracking-widest font-bold text-[10px] relative z-10">
                        <span class="material-symbols-outlined text-[14px]">trending_up</span> TOTAL INTERESES
                    </p>
                    <p class="font-headline-lg text-2xl lg:text-xl xl:text-2xl text-primary whitespace-nowrap relative z-10">{{ formatMoney(totals.intereses) }}</p>
                </div>
                
                <div class="bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-primary/30 transition-all shadow-[0_8px_32px_rgba(233,193,118,0.05)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-transparent to-transparent opacity-60"></div>
                    <p class="font-body-sm text-on-surface-variant mb-1 flex items-center gap-1.5 uppercase tracking-widest font-bold text-[10px] relative z-10">
                        <span class="material-symbols-outlined text-[14px]">health_and_safety</span> TOTAL SEGURO
                    </p>
                    <p class="font-headline-lg text-2xl lg:text-xl xl:text-2xl text-on-surface whitespace-nowrap relative z-10">{{ formatMoney(totals.seguro) }}</p>
                </div>
                
                <div class="bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-primary/40 relative overflow-hidden group hover:border-primary/60 transition-all shadow-[0_8px_32px_rgba(233,193,118,0.2)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/30 via-primary/5 to-transparent opacity-80"></div>
                    <p class="font-body-sm text-primary mb-1 flex items-center gap-1.5 uppercase tracking-widest font-bold text-[10px] relative z-10">
                        <span class="material-symbols-outlined text-[14px]">account_balance</span> COSTO TOTAL
                    </p>
                    <p class="font-headline-lg text-2xl lg:text-xl xl:text-2xl text-primary relative z-10 whitespace-nowrap drop-shadow-[0_0_8px_rgba(233,193,118,0.5)]">{{ formatMoney(totals.costoTotal) }}</p>
                </div>
            </div>

            <!-- Amortization Table Panel -->
            <div class="bg-surface-container-high/30 backdrop-blur-xl border border-white/5 flex-1 rounded-2xl flex flex-col overflow-hidden shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                <!-- Table Header Actions -->
                <div class="p-4 border-b border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4 bg-surface-container/50">
                    <h3 class="font-title-md text-title-md text-on-surface">Tabla de Amortización</h3>
                    <div class="flex gap-2">
                        <button @click="calculateAmortization" class="p-2 rounded bg-surface border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors group" title="Recalcular">
                            <span class="material-symbols-outlined text-[20px] group-active:rotate-180 transition-transform duration-300">calculate</span>
                        </button>
                        <button class="p-2 rounded bg-surface border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors" title="Imprimir PDF">
                            <span class="material-symbols-outlined text-[20px]">picture_as_pdf</span>
                        </button>
                        <button class="p-2 rounded bg-surface border border-outline-variant text-on-surface-variant hover:text-primary hover:border-primary transition-colors" title="Exportar Excel">
                            <span class="material-symbols-outlined text-[20px]">table_view</span>
                        </button>
                    </div>
                </div>

                <!-- Data Table Container -->
                <div class="overflow-x-auto h-[400px]">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="sticky top-0 z-20">
                            <tr class="bg-surface-container-highest border-b border-outline-variant">
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal">N°</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal">FECHA</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">CAPITAL</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">INTERÉS</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">SEGURO</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-primary-fixed-dim font-normal text-right">TOTAL CUOTA</th>
                                <th class="py-3 px-4 font-label-caps text-label-caps text-on-surface-variant font-normal text-right">SALDO PEND.</th>
                            </tr>
                        </thead>
                        <tbody class="font-body-sm text-body-sm text-on-surface divide-y divide-outline-variant/30">
                            <tr v-if="amortizationTable.length === 0">
                                <td colspan="7" class="text-center py-6 text-on-surface-variant">Configura el préstamo para generar la tabla.</td>
                            </tr>
                            <tr v-for="row in amortizationTable" :key="row.cuota" class="hover:bg-surface-container-high transition-colors">
                                <td class="py-3 px-4 text-on-surface-variant">{{ row.cuota }}</td>
                                <td class="py-3 px-4 text-on-surface-variant">{{ row.fecha }}</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">{{ formatNumber(row.capital) }}</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">{{ formatNumber(row.interes) }}</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">{{ formatNumber(row.seguro) }}</td>
                                <td class="py-3 px-4 text-right font-title-md text-title-md text-[14px] text-primary">{{ formatNumber(row.totalCuota) }}</td>
                                <td class="py-3 px-4 text-right text-on-surface-variant">{{ formatNumber(row.saldoPendiente) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination -->
                <div class="mt-auto p-4 border-t border-outline-variant flex justify-between items-center text-sm text-on-surface-variant bg-surface-container/30">
                    <span>Mostrando {{ amortizationTable.length > 0 ? '1' : '0' }} a {{ amortizationTable.length }} de {{ form.plazo || 0 }} cuotas</span>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { clientService } from '../../services/clientService';
import { prestamoService } from '../../services/prestamoService';
import Swal from 'sweetalert2';

const clients = ref([]);
const selectedClient = ref(null);

const form = ref({
    id: null,
    cliente_id: '',
    monto_principal: '',
    tasa: '',
    tipo_interes: 'Decreciente',
    plazo: '',
    fecha_desembolso: new Date().toISOString().split('T')[0]
});

const amortizationTable = ref([]);
const totals = ref({
    intereses: 0,
    seguro: 0,
    costoTotal: 0
});

const loadClients = async () => {
    try {
        const response = await clientService.getAllClients();
        clients.value = response.data.data;
    } catch (e) {
        console.error("Error loading clients", e);
    }
};

const onClientChange = () => {
    if (!form.value.cliente_id) {
        selectedClient.value = null;
        form.value.monto_principal = '';
        form.value.tasa = '';
        form.value.plazo = '';
        amortizationTable.value = [];
        return;
    }

    const client = clients.value.find(c => c.id === form.value.cliente_id);
    if (client) {
        selectedClient.value = client;
        
        // Auto-fill from Client database
        if (client.capital) {
            form.value.monto_principal = Number(client.capital).toLocaleString('en-US', {minimumFractionDigits: 2});
        }
        if (client.porcentaje) {
            form.value.tasa = client.porcentaje;
        }
        if (client.plazo) {
            form.value.plazo = client.plazo;
        }

        calculateAmortization();
    }
};

const parseFormatted = (val) => {
    if(!val) return 0;
    return parseFloat(val.toString().replace(/[^0-9.-]+/g,""));
};

const formatCurrency = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    let num = parseInt(val, 10) / 100;
    form.value[field] = num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const formatPercent = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9.]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    form.value[field] = val;
};

const formatMoney = (val) => {
    return 'Q ' + Number(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const formatNumber = (val) => {
    return Number(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const calculateAmortization = () => {
    const principal = parseFormatted(form.value.monto_principal);
    const rate = parseFormatted(form.value.tasa) / 100; // e.g. 12.5% = 0.125
    const months = parseInt(form.value.plazo) || 0;
    const startDate = new Date(form.value.fecha_desembolso);

    if (principal <= 0 || rate <= 0 || months <= 0 || !form.value.fecha_desembolso) {
        amortizationTable.value = [];
        totals.value = { intereses: 0, seguro: 0, costoTotal: 0 };
        return;
    }

    let table = [];
    let remainingPrincipal = principal;
    
    // Config Seguro - assuming fixed or formula. Let's make it 100.00 base, or scalable?
    // In original design it was 100.00 constantly. We will use 100.00 for now.
    const seguroMensual = 100.00;
    
    let totalInt = 0;
    let totalSeg = 0;

    // Fijo vs Decreciente logic
    // Decreciente: Interest calculated on remaining principal
    // Fijo: Interest calculated on initial principal always
    
    const principalPayment = principal / months;

    for (let i = 1; i <= months; i++) {
        // Calculate date (adding month)
        let paymentDate = new Date(startDate);
        paymentDate.setMonth(startDate.getMonth() + i);
        const dateStr = paymentDate.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });

        let interestPayment = 0;
        
        if (form.value.tipo_interes === 'Decreciente' || form.value.tipo_interes === 'Compuesto') {
            // Decreciente (Interés sobre saldos)
            // Note: standard is usually Rate/12, but if Rate is Monthly already?
            // If TASA is Anual, it should be rate / 12. In the design: 250k * 12.5% -> 31250 total? 
            // If the user treats TASA as Anual: Interest = balance * (rate/12).
            // Let's assume rate is monthly if they just enter 8%, but if they say 12.5% and want 31250...
            interestPayment = remainingPrincipal * (rate / 12);
        } else {
            // Fijo (Interés Simple total dividido entre meses)
            interestPayment = (principal * rate) / months;
        }

        const totalCuota = principalPayment + interestPayment + seguroMensual;
        remainingPrincipal -= principalPayment;
        
        // Handle floating point imprecision for last row
        if (remainingPrincipal < 0.01) remainingPrincipal = 0;

        totalInt += interestPayment;
        totalSeg += seguroMensual;

        table.push({
            cuota: i,
            fecha: dateStr,
            capital: principalPayment,
            interes: interestPayment,
            seguro: seguroMensual,
            totalCuota: totalCuota,
            saldoPendiente: remainingPrincipal
        });
    }

    amortizationTable.value = table;
    totals.value = {
        intereses: totalInt,
        seguro: totalSeg,
        costoTotal: principal + totalInt + totalSeg
    };
};

const saveLoan = async () => {
    if (!form.value.cliente_id || !form.value.monto_principal || !form.value.plazo) {
        Swal.fire('Error', 'Faltan datos en la configuración del préstamo', 'error');
        return;
    }

    try {
        const payload = {
            cliente_id: form.value.cliente_id,
            monto_principal: parseFormatted(form.value.monto_principal),
            tasa: parseFormatted(form.value.tasa),
            tipo_interes: form.value.tipo_interes,
            plazo: parseInt(form.value.plazo),
            fecha_desembolso: form.value.fecha_desembolso,
            total_intereses: totals.value.intereses,
            total_seguro: totals.value.seguro,
            costo_total: totals.value.costoTotal,
            estado: 'ACTIVO'
        };

        if (form.value.id) {
            await prestamoService.updatePrestamo(form.value.id, payload);
        } else {
            const res = await prestamoService.createPrestamo(payload);
            form.value.id = res.data.data.id;
        }

        Swal.fire({
            icon: 'success',
            title: '¡Guardado!',
            text: 'El préstamo ha sido guardado correctamente.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(233,193,118,0.2)]',
            }
        });
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Hubo un problema al guardar el préstamo', 'error');
    }
};

onMounted(() => {
    loadClients();
});
</script>
