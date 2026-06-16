<template>
  <div class="pb-12">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="font-headline-lg text-primary mb-2 tracking-wide font-medium">Reportes Financieros</h2>
            <p class="font-body-sm text-on-surface-variant">Visualización de métricas globales y generación de reportes.</p>
        </div>
        <button @click="openInvestorModal" class="bg-primary/20 text-primary border border-primary/50 font-body-sm py-2 px-5 rounded-xl hover:bg-primary/30 transition-colors shadow-[0_0_15px_rgba(233,193,118,0.2)] flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
            Reporte Inversionista
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
            <p class="font-label-caps text-on-surface-variant mb-2">Total Capital Prestado</p>
            <h3 class="font-display-sm text-on-surface">Q{{ formatCurrency(stats.capital_prestado) }}</h3>
        </div>
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
            <p class="font-label-caps text-on-surface-variant mb-2">Total Pagos Recibidos</p>
            <h3 class="font-display-sm text-tertiary-container">Q{{ formatCurrency(stats.pagos_recibidos) }}</h3>
        </div>
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
            <p class="font-label-caps text-on-surface-variant mb-2">Capital de Inversionistas</p>
            <h3 class="font-display-sm text-primary">Q{{ formatCurrency(stats.capital_inversionistas) }}</h3>
        </div>
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
            <p class="font-label-caps text-on-surface-variant mb-2">Total Egresos</p>
            <h3 class="font-display-sm text-error">Q{{ formatCurrency(stats.total_egresos) }}</h3>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Balance General Chart -->
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
            <h3 class="font-title-lg text-on-surface mb-6">Balance General</h3>
            <div class="h-[300px] w-full relative">
                <Bar v-if="chartDataLoaded" :data="barChartData" :options="barChartOptions" />
            </div>
        </div>

        <!-- Top Inversionistas Chart -->
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] flex flex-col">
            <h3 class="font-title-lg text-on-surface mb-6">Distribución de Inversiones (Top 5)</h3>
            <div class="flex-1 flex items-center justify-center relative min-h-[300px]">
                <div class="w-full h-[300px]">
                    <Pie v-if="chartDataLoaded" :data="pieChartData" :options="pieChartOptions" />
                </div>
            </div>
        </div>
    </div>

    <!-- Sección Estado de Cuenta Cliente -->
    <div class="mt-8">
        <h3 class="font-headline-md text-primary mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">account_balance_wallet</span>
            Estado de Cuenta por Cliente
        </h3>
        <div class="bg-surface-container-high/30 backdrop-blur-xl p-6 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
            <div class="flex flex-col md:flex-row md:items-end gap-4 mb-6">
                <div class="flex-1">
                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest block mb-2">Seleccionar Cliente</label>
                    <div class="relative">
                        <select v-model="selectedClientId" @change="onClientChange" class="w-full bg-surface-container-high/50 backdrop-blur-xl text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                            <option value="" disabled>-- Busque y seleccione un cliente --</option>
                            <option v-for="client in clientesList" :key="client.id" :value="client.id" class="bg-surface-container text-on-surface">{{ client.cliente }}</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                    </div>
                </div>
                <button :disabled="!selectedClientReport" @click="generateClientPDF" :class="['px-5 py-3 rounded-xl border font-body-sm transition-colors shadow-[0_0_15px_rgba(233,193,118,0.2)] flex items-center gap-2', selectedClientReport ? 'bg-primary/20 text-primary border-primary/50 hover:bg-primary/30' : 'bg-surface-container border-transparent text-on-surface-variant opacity-50 cursor-not-allowed']">
                    <span class="material-symbols-outlined text-lg">download</span>
                    Descargar Estado de Cuenta
                </button>
            </div>

            <!-- Tabla de Historial (Preview) -->
            <div v-if="selectedClientReport" class="border border-white/5 rounded-2xl overflow-hidden bg-surface-container/30">
                <!-- Info Header -->
                <div class="bg-surface-container/50 p-5 grid grid-cols-2 md:grid-cols-4 gap-4 border-b border-white/5">
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wider mb-1">Capital Prestado</p>
                        <p class="text-on-surface font-medium">Q{{ formatCurrency(selectedClientReport.cliente.capital) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wider mb-1">Tasa de Interés</p>
                        <p class="text-on-surface font-medium">{{ selectedClientReport.cliente.porcentaje }}%</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wider mb-1">Plazo</p>
                        <p class="text-on-surface font-medium">{{ selectedClientReport.cliente.plazo }} Meses</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-wider mb-1">Total Pagado a la Fecha</p>
                        <p class="text-tertiary-container font-medium">Q{{ formatCurrency(totalPagadoCliente) }}</p>
                    </div>
                </div>
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left font-body-sm">
                        <thead class="bg-surface-container text-on-surface-variant">
                            <tr>
                                <th class="py-3 px-4 font-medium uppercase text-[10px] tracking-wider">Fecha</th>
                                <th class="py-3 px-4 font-medium uppercase text-[10px] tracking-wider">Referencia</th>
                                <th class="py-3 px-4 font-medium uppercase text-[10px] tracking-wider">Abono Capital</th>
                                <th class="py-3 px-4 font-medium uppercase text-[10px] tracking-wider">Abono Interés</th>
                                <th class="py-3 px-4 font-medium uppercase text-[10px] tracking-wider text-right">Total Pagado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="selectedClientReport.pagos.length === 0">
                                <td colspan="5" class="py-6 text-center text-on-surface-variant">No hay pagos registrados para este cliente.</td>
                            </tr>
                            <tr v-for="pago in selectedClientReport.pagos" :key="pago.id" class="hover:bg-white/5 transition-colors">
                                <td class="py-3 px-4 text-on-surface">{{ formatDate(pago.fecha) }}</td>
                                <td class="py-3 px-4 text-on-surface-variant">{{ pago.referencia || 'N/A' }}</td>
                                <td class="py-3 px-4 text-on-surface">Q{{ formatCurrency(pago.abono_capital) }}</td>
                                <td class="py-3 px-4 text-on-surface">Q{{ formatCurrency(pago.interes) }}</td>
                                <td class="py-3 px-4 text-tertiary-container text-right font-medium">Q{{ formatCurrency(pago.monto_pagado) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Reporte Inversionista (Original) -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-surface-container-high/95 border border-white/10 p-8 rounded-3xl w-full max-w-xl shadow-[0_8px_32px_rgba(0,0,0,0.5)]">
            <div class="flex justify-between items-start mb-6">
                <h2 class="font-headline-md text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined">description</span>
                    Reporte Inversionista
                </h2>
                <button @click="showModal = false" class="text-on-surface-variant hover:text-error transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="mb-6">
                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest block mb-2">Seleccionar Inversionista</label>
                <div class="relative">
                    <select v-model="selectedInvestorId" @change="onInvestorChange" class="w-full bg-surface-container-high/50 backdrop-blur-xl text-on-surface font-body-lg py-3 px-4 rounded-xl border border-white/10 focus:border-primary focus:ring-0 transition-colors appearance-none cursor-pointer">
                        <option value="" disabled>-- Seleccione un inversionista --</option>
                        <option v-for="inv in inversionistasList" :key="inv.id" :value="inv.id" class="bg-surface-container text-on-surface">{{ inv.nombre }}</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">expand_more</span>
                </div>
            </div>

            <!-- Resumen Previo -->
            <div v-if="selectedInvestor" class="bg-surface-container-high/50 border border-white/5 rounded-2xl p-5 mb-8">
                <p class="font-label-caps text-on-surface-variant text-xs mb-3 border-b border-white/10 pb-2">Previsualización de Datos</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-outline uppercase tracking-wider mb-1">Capital Invertido</p>
                        <p class="text-on-surface font-medium">Q{{ formatCurrency(selectedInvestor.capital) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-outline uppercase tracking-wider mb-1">Rendimiento Estimado</p>
                        <p class="text-tertiary-container font-medium">Q{{ formatCurrency((selectedInvestor.capital * selectedInvestor.porcentaje) / 100) }} / mes</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] text-outline uppercase tracking-wider mb-1">Banco y Cuenta</p>
                        <p class="text-on-surface-variant font-body-sm">{{ selectedInvestor.banco }} - {{ selectedInvestor.numero_cuenta }}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <button @click="showModal = false" class="px-5 py-2.5 rounded-xl border border-white/10 text-on-surface hover:bg-white/5 font-body-sm transition-colors">Cancelar</button>
                <button :disabled="!selectedInvestor" @click="generatePDF" :class="['px-5 py-2.5 rounded-xl border font-body-sm transition-colors shadow-[0_0_15px_rgba(233,193,118,0.2)] flex items-center gap-2', selectedInvestor ? 'bg-primary/20 text-primary border-primary/50 hover:bg-primary/30' : 'bg-surface-container border-transparent text-on-surface-variant opacity-50 cursor-not-allowed']">
                    <span class="material-symbols-outlined text-lg">download</span>
                    Descargar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden HTML Template for PDF Inversionista -->
    <div style="display:none;">
        <div id="pdf-template" class="pdf-container" style="padding: 40px; font-family: 'Inter', sans-serif; background-color: #ffffff; color: #131313; width: 800px; box-sizing: border-box;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9c176; padding-bottom: 20px; margin-bottom: 30px;">
                <div>
                    <h1 style="font-size: 28px; color: #131313; margin: 0; font-weight: 700;">HORUS <span style="color: #e9c176;">EMPRESARIAL</span></h1>
                    <p style="font-size: 12px; color: #555555; margin: 5px 0 0 0;">Reporte de Inversión</p>
                </div>
                <div style="text-align: right;">
                    <p style="font-size: 12px; color: #555555; margin: 0;">Fecha de emisión: <strong style="color: #131313;">{{ currentDate }}</strong></p>
                </div>
            </div>

            <div v-if="selectedInvestor">
                <h2 style="font-size: 20px; color: #131313; margin-bottom: 20px; border-left: 4px solid #e9c176; padding-left: 10px;">Información del Inversionista</h2>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
                    <tbody>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; width: 30%; color: #555555; font-size: 14px;">Nombre Completo:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-weight: bold; font-size: 14px;">{{ selectedInvestor.nombre }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; color: #555555; font-size: 14px;">Fecha de Registro:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-size: 14px;">{{ formatDate(selectedInvestor.created_at) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; color: #555555; font-size: 14px;">Entidad Bancaria:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-size: 14px;">{{ selectedInvestor.banco }} (No. {{ selectedInvestor.numero_cuenta }})</td>
                        </tr>
                    </tbody>
                </table>

                <h2 style="font-size: 20px; color: #131313; margin-bottom: 20px; border-left: 4px solid #e9c176; padding-left: 10px;">Resumen Financiero</h2>
                <div style="background-color: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #eeeeee;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <tr>
                                <td style="padding: 10px 0; border-bottom: 1px dashed #dddddd; color: #333333; font-size: 16px;">Capital Invertido</td>
                                <td style="padding: 10px 0; border-bottom: 1px dashed #dddddd; text-align: right; font-weight: bold; font-size: 16px;">Q {{ formatCurrency(selectedInvestor.capital) }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 10px 0; border-bottom: 1px dashed #dddddd; color: #333333; font-size: 16px;">Tasa de Rendimiento Pactada</td>
                                <td style="padding: 10px 0; border-bottom: 1px dashed #dddddd; text-align: right; font-size: 16px;">{{ selectedInvestor.porcentaje }} %</td>
                            </tr>
                            <tr>
                                <td style="padding: 15px 0 5px 0; color: #131313; font-size: 18px; font-weight: bold;">Rendimiento Generado Estimado (Mensual)</td>
                                <td style="padding: 15px 0 5px 0; text-align: right; font-weight: bold; font-size: 22px; color: #131313;">Q {{ formatCurrency((selectedInvestor.capital * selectedInvestor.porcentaje) / 100) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div style="margin-top: 60px; text-align: center; border-top: 1px solid #eeeeee; padding-top: 20px;">
                <p style="font-size: 12px; color: #888888; margin: 0;">Este documento es un resumen informativo emitido por el sistema Horus Empresarial.</p>
                <p style="font-size: 12px; color: #888888; margin: 5px 0 0 0;">Generado automáticamente el {{ currentDate }}</p>
            </div>
        </div>
    </div>


    <!-- Hidden HTML Template for PDF Cliente -->
    <div style="display:none;">
        <div id="pdf-template-cliente" class="pdf-container" style="padding: 40px; font-family: 'Inter', sans-serif; background-color: #ffffff; color: #131313; width: 800px; box-sizing: border-box;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9c176; padding-bottom: 20px; margin-bottom: 30px;">
                <div>
                    <h1 style="font-size: 28px; color: #131313; margin: 0; font-weight: 700;">HORUS <span style="color: #e9c176;">EMPRESARIAL</span></h1>
                    <p style="font-size: 12px; color: #555555; margin: 5px 0 0 0;">Estado de Cuenta</p>
                </div>
                <div style="text-align: right;">
                    <p style="font-size: 12px; color: #555555; margin: 0;">Fecha de emisión: <strong style="color: #131313;">{{ currentDate }}</strong></p>
                </div>
            </div>

            <div v-if="selectedClientReport">
                <h2 style="font-size: 20px; color: #131313; margin-bottom: 20px; border-left: 4px solid #e9c176; padding-left: 10px;">Información del Cliente</h2>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                    <tbody>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; width: 30%; color: #555555; font-size: 14px;">Nombre Cliente:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-weight: bold; font-size: 14px;">{{ selectedClientReport.cliente.nombre }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; color: #555555; font-size: 14px;">Capital Otorgado:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-weight: bold; font-size: 14px;">Q {{ formatCurrency(selectedClientReport.cliente.capital) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; color: #555555; font-size: 14px;">Plazo del Crédito:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-size: 14px;">{{ selectedClientReport.cliente.plazo }} Meses</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; color: #555555; font-size: 14px;">Tasa de Interés:</td>
                            <td style="padding: 10px; border-bottom: 1px solid #eeeeee; font-size: 14px;">{{ selectedClientReport.cliente.porcentaje }}%</td>
                        </tr>
                    </tbody>
                </table>

                <h2 style="font-size: 20px; color: #131313; margin-bottom: 20px; border-left: 4px solid #e9c176; padding-left: 10px;">Historial de Pagos Recibidos</h2>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="padding: 10px; border: 1px solid #dddddd; text-align: left; color: #333;">Fecha</th>
                            <th style="padding: 10px; border: 1px solid #dddddd; text-align: left; color: #333;">Referencia</th>
                            <th style="padding: 10px; border: 1px solid #dddddd; text-align: right; color: #333;">Capital</th>
                            <th style="padding: 10px; border: 1px solid #dddddd; text-align: right; color: #333;">Interés</th>
                            <th style="padding: 10px; border: 1px solid #dddddd; text-align: right; color: #333;">Monto Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="selectedClientReport.pagos.length === 0">
                            <td colspan="5" style="padding: 15px; border: 1px solid #eeeeee; text-align: center; color: #888;">No hay pagos registrados.</td>
                        </tr>
                        <tr v-for="pago in selectedClientReport.pagos" :key="pago.id">
                            <td style="padding: 10px; border: 1px solid #eeeeee;">{{ formatDate(pago.fecha) }}</td>
                            <td style="padding: 10px; border: 1px solid #eeeeee; color: #555;">{{ pago.referencia || 'N/A' }}</td>
                            <td style="padding: 10px; border: 1px solid #eeeeee; text-align: right;">Q{{ formatCurrency(pago.abono_capital) }}</td>
                            <td style="padding: 10px; border: 1px solid #eeeeee; text-align: right;">Q{{ formatCurrency(pago.interes) }}</td>
                            <td style="padding: 10px; border: 1px solid #eeeeee; text-align: right; font-weight: bold;">Q{{ formatCurrency(pago.monto_pagado) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #fafafa;">
                            <td colspan="4" style="padding: 15px 10px; text-align: right; font-weight: bold; font-size: 14px; border: 1px solid #dddddd;">Total Abonado</td>
                            <td style="padding: 15px 10px; text-align: right; font-weight: bold; font-size: 16px; border: 1px solid #dddddd; color: #131313;">Q{{ formatCurrency(totalPagadoCliente) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer -->
            <div style="margin-top: 60px; text-align: center; border-top: 1px solid #eeeeee; padding-top: 20px;">
                <p style="font-size: 12px; color: #888888; margin: 0;">Estado de Cuenta Oficial emitido por Horus Empresarial S.A.</p>
                <p style="font-size: 12px; color: #888888; margin: 5px 0 0 0;">Generado automáticamente el {{ currentDate }}</p>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { reportService } from '../../services/reportService';
import { investorService } from '../../services/investorService';
import { clientService } from '../../services/clientService';
import html2pdf from 'html2pdf.js';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js';
import { Bar, Pie } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const chartDataLoaded = ref(false);
const stats = ref({
    capital_prestado: 0,
    pagos_recibidos: 0,
    capital_inversionistas: 0,
    total_egresos: 0
});

const barChartData = ref({
    labels: ['Capital Prestado', 'Pagos Recibidos', 'Capital Inversionistas', 'Egresos'],
    datasets: []
});

const pieChartData = ref({
    labels: [],
    datasets: []
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(19, 19, 19, 0.9)',
            titleColor: '#e9c176',
            bodyColor: '#ffffff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1
        }
    },
    scales: {
        y: { 
            beginAtZero: true,
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: 'rgba(255, 255, 255, 0.5)' }
        },
        x: {
            grid: { display: false },
            ticks: { color: 'rgba(255, 255, 255, 0.7)' }
        }
    }
};

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: { color: 'rgba(255, 255, 255, 0.7)' }
        },
        tooltip: {
            backgroundColor: 'rgba(19, 19, 19, 0.9)',
            titleColor: '#e9c176',
            bodyColor: '#ffffff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1
        }
    }
};

// Modal Inversionista
const showModal = ref(false);
const inversionistasList = ref([]);
const selectedInvestorId = ref("");
const selectedInvestor = ref(null);

// Cliente Reporte
const clientesList = ref([]);
const selectedClientId = ref("");
const selectedClientReport = ref(null);

const currentDate = computed(() => {
    return new Date().toLocaleDateString('es-GT', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' });
});

const totalPagadoCliente = computed(() => {
    if (!selectedClientReport.value || !selectedClientReport.value.pagos) return 0;
    return selectedClientReport.value.pagos.reduce((sum, pago) => sum + parseFloat(pago.monto_pagado || 0), 0);
});

const formatCurrency = (val) => {
    if (!val) return '0.00';
    return Number(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('es-GT');
};

const loadDashboardData = async () => {
    try {
        const response = await reportService.getDashboardData();
        if (response.data && response.data.data) {
            stats.value = response.data.data.stats;
            
            // Build Bar Chart
            barChartData.value = {
                labels: ['Prestado', 'Pagos Recibidos', 'Inversiones', 'Egresos'],
                datasets: [
                    {
                        label: 'Monto Q',
                        backgroundColor: ['#e9c176', '#8fa5d6', '#a1c29e', '#ffb4ab'],
                        borderColor: ['#e9c176', '#8fa5d6', '#a1c29e', '#ffb4ab'],
                        borderWidth: 1,
                        borderRadius: 8,
                        data: [
                            stats.value.capital_prestado,
                            stats.value.pagos_recibidos,
                            stats.value.capital_inversionistas,
                            stats.value.total_egresos
                        ]
                    }
                ]
            };

            // Build Pie Chart
            const topInv = response.data.data.charts.inversionistas;
            if (topInv && topInv.length > 0) {
                pieChartData.value = {
                    labels: topInv.map(i => i.nombre),
                    datasets: [
                        {
                            backgroundColor: ['#e9c176', '#8fa5d6', '#ffb4ab', '#e0e0e0', '#8fa5d688'],
                            borderWidth: 0,
                            data: topInv.map(i => i.capital)
                        }
                    ]
                };
            } else {
                pieChartData.value = { labels: ['Sin datos'], datasets: [{ data: [1], backgroundColor: ['#353535'] }] };
            }

            chartDataLoaded.value = true;
        }
    } catch (error) {
        console.error("Error loading dashboard data", error);
    }
};

const loadClientes = async () => {
    try {
        const res = await clientService.getAllClients();
        clientesList.value = res.data.data || [];
    } catch (error) {
        console.error("Error loading clients", error);
    }
};

const openInvestorModal = async () => {
    showModal.value = true;
    selectedInvestorId.value = "";
    selectedInvestor.value = null;
    
    if (inversionistasList.value.length === 0) {
        try {
            const res = await investorService.getAllInvestors();
            inversionistasList.value = res.data.data;
        } catch (error) {
            console.error("Error loading investors", error);
        }
    }
};

const onInvestorChange = () => {
    selectedInvestor.value = inversionistasList.value.find(i => i.id === selectedInvestorId.value);
};

const onClientChange = async () => {
    if (!selectedClientId.value) return;
    try {
        const res = await reportService.getClientReport(selectedClientId.value);
        if (res.data && res.data.data) {
            selectedClientReport.value = res.data.data;
        }
    } catch (error) {
        console.error("Error loading client report", error);
    }
};

const generatePDF = () => {
    if (!selectedInvestor.value) return;

    const element = document.getElementById('pdf-template');
    element.parentElement.style.display = 'block';

    const opt = {
        margin:       0,
        filename:     `Reporte_Inversionista_${selectedInvestor.value.nombre.replace(/\s+/g, '_')}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        element.parentElement.style.display = 'none';
        showModal.value = false;
    });
};

const generateClientPDF = () => {
    if (!selectedClientReport.value) return;

    const element = document.getElementById('pdf-template-cliente');
    element.parentElement.style.display = 'block';

    const opt = {
        margin:       0,
        filename:     `Estado_Cuenta_${selectedClientReport.value.cliente.nombre.replace(/\s+/g, '_')}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        element.parentElement.style.display = 'none';
    });
};

onMounted(() => {
    loadDashboardData();
    loadClientes();
});
</script>

<style scoped>
/* Scoped overrides if needed */
</style>
