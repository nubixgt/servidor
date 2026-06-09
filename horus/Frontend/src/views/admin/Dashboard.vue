<template>
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <h2 class="font-headline-lg text-primary mb-2">Dashboard Ejecutivo</h2>
                <p class="font-body-lg text-on-surface-variant">Resumen consolidado de operaciones y rendimiento financiero.</p>
            </div>
            <div class="flex items-center gap-2 bg-surface-container-high border border-outline-variant rounded-lg p-1">
                <button class="px-4 py-1.5 rounded bg-primary text-on-primary font-label-caps shadow-sm">Hoy</button>
                <button class="px-4 py-1.5 rounded text-on-surface-variant hover:text-on-surface font-label-caps transition-colors">Esta Semana</button>
                <button class="px-4 py-1.5 rounded text-on-surface-variant hover:text-on-surface font-label-caps transition-colors">Este Mes</button>
            </div>
        </div>

        <!-- KPIs Grid (Bento Style) -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Card 1: Capital Colocado -->
            <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant relative overflow-hidden group hover:border-primary/50 transition-colors">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full group-hover:bg-primary/10 transition-colors"></div>
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <h3 class="font-label-caps text-on-surface-variant">Capital Colocado</h3>
                    <span class="material-symbols-outlined text-primary">trending_up</span>
                </div>
                <p class="font-headline-lg text-on-surface mb-1 relative z-10">Q12,450,000</p>
                <div class="flex items-center gap-2 text-primary font-body-sm relative z-10">
                    <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                    <span>+4.2% vs mes anterior</span>
                </div>
            </div>

            <!-- Card 2: Capital Disponible -->
            <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant relative overflow-hidden group hover:border-primary/50 transition-colors">
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <h3 class="font-label-caps text-on-surface-variant">Capital Disponible</h3>
                    <span class="material-symbols-outlined text-secondary">account_balance_wallet</span>
                </div>
                <p class="font-headline-lg text-on-surface mb-1 relative z-10">Q3,200,000</p>
                <div class="flex items-center gap-2 text-on-surface-variant font-body-sm relative z-10">
                    <span>20.4% de liquidez</span>
                </div>
            </div>

            <!-- Card 3: Ingresos por Intereses -->
            <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant relative overflow-hidden group hover:border-primary/50 transition-colors lg:col-span-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-tertiary">monetization_on</span>
                            <h3 class="font-label-caps text-on-surface-variant">Ingresos por Intereses (YTD)</h3>
                        </div>
                        <p class="font-headline-lg text-on-surface mb-1">Q1,840,500</p>
                        <div class="flex items-center gap-2 text-primary font-body-sm">
                            <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                            <span>+12.5% anual</span>
                        </div>
                    </div>
                    <div class="w-full sm:w-1/2 h-16 bg-surface-container rounded flex items-end pt-2 px-2 gap-1 opacity-80">
                        <!-- Simple CSS Sparkline -->
                        <div class="w-1/6 bg-tertiary-container h-[40%] rounded-t-sm"></div>
                        <div class="w-1/6 bg-tertiary-container h-[55%] rounded-t-sm"></div>
                        <div class="w-1/6 bg-tertiary-container h-[45%] rounded-t-sm"></div>
                        <div class="w-1/6 bg-tertiary-container h-[70%] rounded-t-sm"></div>
                        <div class="w-1/6 bg-tertiary-container h-[60%] rounded-t-sm"></div>
                        <div class="w-1/6 bg-tertiary h-[90%] rounded-t-sm shadow-[0_0_8px_rgba(176,198,249,0.5)]"></div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Operaciones -->
            <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex justify-between items-center">
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Préstamos Activos</h3>
                    <p class="font-title-md text-on-surface">342</p>
                </div>
                <div class="h-10 w-px bg-outline-variant mx-4"></div>
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Finalizados</h3>
                    <p class="font-title-md text-on-surface">1,204</p>
                </div>
            </div>

            <!-- Card 5: Entidades -->
            <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex justify-between items-center">
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Clientes</h3>
                    <p class="font-title-md text-on-surface">856</p>
                </div>
                <div class="h-10 w-px bg-outline-variant mx-4"></div>
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Inversionistas</h3>
                    <p class="font-title-md text-on-surface">45</p>
                </div>
            </div>

            <!-- Card 6: Riesgo -->
            <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex justify-between items-center border-l-2 border-l-error">
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Morosidad Total</h3>
                    <p class="font-title-md text-error">4.8%</p>
                </div>
                <div class="h-10 w-px bg-outline-variant mx-4"></div>
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">P. Vencidos</h3>
                    <p class="font-title-md text-on-surface">18</p>
                </div>
            </div>

            <!-- Card 7: Flujo Corto Plazo -->
            <div class="bg-surface-container p-5 rounded-xl border border-outline-variant flex justify-between items-center">
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Cobros (Hoy)</h3>
                    <p class="font-title-md text-on-surface">Q45,000</p>
                </div>
                <div class="h-10 w-px bg-outline-variant mx-4"></div>
                <div>
                    <h3 class="font-label-caps text-on-surface-variant mb-1">Semana</h3>
                    <p class="font-title-md text-on-surface">Q210,000</p>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section>
            <h2 class="font-headline-lg text-primary mb-6">Análisis Visual</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Bar Chart -->
                <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant lg:col-span-2 flex flex-col h-96">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-title-md text-on-surface">Préstamos Otorgados vs Intereses</h3>
                        <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">more_vert</span></button>
                    </div>
                    <div class="flex-1 relative w-full">
                        <canvas id="loansChart"></canvas>
                    </div>
                </div>

                <!-- Side Donut Chart -->
                <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant flex flex-col h-96">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-title-md text-on-surface">Salud de Cartera</h3>
                        <button class="text-on-surface-variant hover:text-primary"><span class="material-symbols-outlined">more_vert</span></button>
                    </div>
                    <div class="flex-1 relative w-full flex items-center justify-center">
                        <canvas id="healthChart"></canvas>
                        <!-- Center text for donut -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-2">
                            <span class="font-headline-lg text-on-surface">95.2%</span>
                            <span class="font-label-caps text-on-surface-variant">Al Corriente</span>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-primary"></div>
                            <span class="font-body-sm text-on-surface-variant">Sana</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-error"></div>
                            <span class="font-body-sm text-on-surface-variant">Mora</span>
                        </div>
                    </div>
                </div>

                <!-- Line Chart (Rentabilidad) -->
                <div class="bg-surface-container-high p-6 rounded-xl border border-outline-variant lg:col-span-3 flex flex-col h-80">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-title-md text-on-surface">Rentabilidad vs Capital Invertido (Top Socios)</h3>
                        <div class="flex gap-2">
                            <span class="px-2 py-1 bg-surface-container text-on-surface-variant text-xs rounded border border-outline-variant">Q1</span>
                            <span class="px-2 py-1 bg-primary text-on-primary text-xs rounded border border-primary">Q2</span>
                        </div>
                    </div>
                    <div class="flex-1 relative w-full">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
    if(window.Chart) {
        // Common Chart.js Defaults for Dark Theme
        Chart.defaults.color = '#d1c5b4'; // on-surface-variant
        Chart.defaults.font.family = 'Hanken Grotesk';
        Chart.defaults.scale.grid.color = '#353535'; // surface-variant
        Chart.defaults.plugins.tooltip.backgroundColor = '#252525';
        Chart.defaults.plugins.tooltip.titleColor = '#e9c176'; // primary
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;

        // Colors from Tailwind config
        const colors = {
            primary: '#e9c176',
            primaryContainer: '#c5a059',
            tertiary: '#b0c6f9',
            error: '#ffb4ab',
            surfaceContainer: '#20201f'
        };

        // 1. Loans vs Interests Bar Chart
        const ctxLoans = document.getElementById('loansChart').getContext('2d');
        new Chart(ctxLoans, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'],
                datasets: [
                    {
                        label: 'Capital Otorgado (M)',
                        data: [4.2, 3.8, 5.1, 4.8, 6.2, 5.9, 7.1],
                        backgroundColor: colors.surfaceContainer,
                        borderColor: colors.primaryContainer,
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Intereses Generados (k)',
                        data: [420, 380, 510, 480, 620, 590, 710],
                        type: 'line',
                        borderColor: colors.tertiary,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointBackgroundColor: colors.tertiary,
                        pointRadius: 4,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 12, usePointStyle: true } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, border: { dash: [4, 4] } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { display: false } }
                }
            }
        });

        // 2. Health Donut Chart
        const ctxHealth = document.getElementById('healthChart').getContext('2d');
        new Chart(ctxHealth, {
            type: 'doughnut',
            data: {
                labels: ['Cartera Sana', 'Morosidad < 30d', 'Morosidad > 30d'],
                datasets: [{
                    data: [95.2, 3.1, 1.7],
                    backgroundColor: [colors.primary, '#9a8f80', colors.error],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(context) { return context.label + ': ' + context.parsed + '%'; } } }
                }
            }
        });

        // 3. Profitability Line Chart
        const ctxProfit = document.getElementById('profitChart').getContext('2d');
        
        // Create gradient for line chart
        const gradient = ctxProfit.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(233, 193, 118, 0.2)'); // primary with opacity
        gradient.addColorStop(1, 'rgba(233, 193, 118, 0)');

        new Chart(ctxProfit, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6', 'Sem 7', 'Sem 8'],
                datasets: [{
                    label: 'Rentabilidad Promedio (%)',
                    data: [12.4, 12.6, 12.5, 13.1, 13.4, 13.2, 14.0, 14.5],
                    borderColor: colors.primary,
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.surfaceContainer,
                    pointBorderColor: colors.primary,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: false, min: 10, border: { dash: [4, 4] } }
                }
            }
        });
    }
});
</script>
